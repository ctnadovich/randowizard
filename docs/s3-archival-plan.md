# S3 Waiver Archival Plan

## Objective

Store an additional immutable copy of every completed waiver PDF and its
metadata in Amazon S3. Successful S3 archival and verification are required
before a waiver is officially marked completed in the local database.

The existing local filesystem copy remains the primary retrieval source. S3 is
an archival copy rather than a replacement for local storage.

## S3 retention assumptions

The S3 bucket will be configured outside the application with:

- Versioning enabled.
- S3 Object Lock enabled.
- A suitably long default retention policy in Compliance mode.
- Block Public Access enabled.
- Appropriate encryption.

The bucket owns the retention duration. Application code will not submit,
shorten, replace, or otherwise control retention settings.

The application's AWS identity should be able to upload and verify objects in
the designated waiver prefix, but should not have permissions that allow it to:

- Delete archived object versions.
- bypass Governance retention;
- change object retention;
- change the bucket's Object Lock configuration.

It may require `s3:GetObjectRetention` in order to confirm that the bucket's
default retention was applied.

## Application configuration

The following deployment-specific values can initially be represented by
placeholders and supplied through application configuration later:

- S3 bucket name.
- AWS region.
- Optional object-key prefix, such as `waivers/`.
- Optional custom endpoint for testing with an S3-compatible service.

The AWS SDK should use its default credential-provider chain. No access-key
values need to be embedded in the application. This permits credentials to come
from an instance role, container role, environment, or standard AWS credential
configuration.

The AWS SDK for PHP version 3 has been installed in the parent CodeIgniter
Composer project and is available through its existing autoloader.

## Storage architecture

`Libraries/WaiverStorage.php` is the appropriate integration boundary because
it already owns immutable document keys, local PDF and metadata storage, and
retrieval.

The implementation should keep local storage and S3 archival conceptually
separate. This may be done with separate backend classes behind a storage
interface or with explicit local and archive operations coordinated by
`WaiverStorage`. S3 should not become the primary source used by the existing
document endpoint.

The S3 archive consists of two independently versioned objects:

- The completed PDF.
- Its JSON metadata record.

Object keys should be deterministic and include the public session ID. The
existing local document key has the useful form:

```text
<event_code>/<participant_id>/<session_id>.pdf
```

The metadata object can use the same base key with a metadata suffix.

## Required completion order

Finalization should occur in this order:

```text
Render PDF
  -> calculate local SHA-256
  -> store local PDF successfully
  -> store local metadata successfully
  -> upload PDF to S3
  -> verify the exact uploaded PDF version
  -> upload metadata to S3
  -> verify the exact uploaded metadata version
  -> record the archive keys and version IDs in waiver_archive
  -> atomically mark the waiver session completed
  -> record signer completion in the access log
```

The waiver must not be marked completed until both S3 objects have been
uploaded and verified and their indexing record has been written locally.

S3 archival will initially be synchronous. The added request latency is
accepted for the first implementation. If S3 delays prove operationally
unacceptable, asynchronous archival can be reconsidered later.

## S3 verification

Each upload should provide a checksum and capture the returned S3 version ID.
Archival is successful only after verifying the exact returned version.

Verification should confirm:

- The version ID is nonempty.
- `HeadObject` succeeds for the exact bucket, key, and version ID.
- The stored content length matches the local content.
- The S3 checksum matches the checksum computed locally.
- Object Lock retention is present.
- The lock mode is Compliance.
- The retain-until date is in the future.

The application does not need to verify a specific retention duration because
that policy belongs to the bucket.

The existing locally calculated PDF SHA-256 remains the canonical digest. No
duplicate checksum column is required in the archive table.

## Archive database table

A separate archive table is preferred over putting S3 fields directly on the
reusable `waiver` row. One waiver row represents a participant/event
relationship and may accumulate multiple session attempts. Each successfully
verified S3 archive belongs to one specific session attempt.

The proposed table is:

```text
waiver_archive
--------------
id
waiver_id
session_id
bucket
document_key
document_version_id
metadata_key
metadata_version_id
archived_at
```

Recommended schema characteristics:

- `id` is an auto-incrementing primary key.
- `waiver_id` is a foreign key to `waiver.id` and uses the same integer type.
- `session_id` is `CHAR(32)`.
- `bucket` can be `VARCHAR(63)`.
- Object keys and version IDs use sufficiently large `VARCHAR` fields.
- `archived_at` is a SQL `DATETIME` written and interpreted as UTC.
- `session_id` has a unique constraint.
- `waiver_id` has an index.

An illustrative schema is:

```sql
CREATE TABLE waiver_archive (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    waiver_id BIGINT UNSIGNED NOT NULL,
    session_id CHAR(32) NOT NULL,
    bucket VARCHAR(63) NOT NULL,
    document_key VARCHAR(1024) NOT NULL,
    document_version_id VARCHAR(1024) NOT NULL,
    metadata_key VARCHAR(1024) NOT NULL,
    metadata_version_id VARCHAR(1024) NOT NULL,
    archived_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY waiver_archive_session_id (session_id),
    KEY waiver_archive_waiver_id (waiver_id),
    FOREIGN KEY (waiver_id) REFERENCES waiver(id)
);
```

The actual `waiver_id` type must match the deployed `waiver.id` definition.

A CodeIgniter model should insert an archive record only after both S3 versions
have been verified. An existing record for a session must not be modified.

## Session identity and audit history

The current system maintains one `waiver` row per immutable event context and
participant ID. A pending session may be reused, while an expired or cancelled
session can be reset with a new public session ID. Completed sessions remain
terminal.

To preserve the identity of every attempt, add a nullable `session_id CHAR(32)`
column to `waiver_access_log`. It is nullable for compatibility with historical
rows; new log entries should always contain the active session ID.

This creates the following identity model:

```text
waiver.id              stable participant/event relationship
session_id             individual signing attempt
waiver_access_log      activity for each attempt
waiver_archive         immutable artifacts produced by each attempt
```

Session replacement should create explicit lifecycle audit entries:

```text
old session ID -> session_replaced
new session ID -> session_created
```

Other useful lifecycle entries include `session_reused`, `session_expired`, and
`session_cancelled`. Existing HTTP-oriented events such as `signer_start`,
`signer_completed`, `document_view`, and `reference_view` remain in use.

The access-log API can continue returning all entries chronologically. Entries
can additionally be grouped by session ID to distinguish multiple attempts for
one participant/event pair.

## Retrying after S3 failures

S3 failure leaves the session non-completed. The participant may retry, or the
session may later be cancelled and replaced so the participant can fill out a
new waiver.

Local immutable storage may already have succeeded before an S3 failure. The
current local storage implementation rejects an existing path. It should be
extended to:

1. Read an existing PDF and metadata record.
2. Verify that they are byte-for-byte identical to the expected artifacts.
3. Reuse them when identical.
4. Fail loudly if they differ.

A replacement session receives a new public session ID, so its local and S3
keys do not collide with artifacts from the abandoned attempt. Resetting a
session must not delete old access logs, archive rows, local files, or S3
objects.

## Final database failure policy

There is an unavoidable failure window after successful S3 archival but before
the final conditional database completion update. The initial implementation
will not attempt distributed rollback or automatic reconciliation.

If the final update fails:

- Abort immediately.
- Leave the waiver non-completed.
- Leave all immutable local and S3 artifacts untouched.
- Throw a detailed runtime error.
- Write the same reconciliation information to the application log.

The error and log entry should include:

- Public session ID.
- Internal waiver row ID.
- Event code.
- Participant ID.
- S3 bucket.
- PDF key and version ID.
- Metadata key and version ID.
- Local document key.
- Locally calculated PDF SHA-256.
- Current session status, expiration time, and event start time when available.
- The underlying database exception or an explanation that the conditional
  update affected no row.

The message must explicitly state that local storage and S3 archival succeeded,
but the waiver was not officially marked completed and manual reconciliation
may be required.

The `waiver_archive` row normally provides durable indexing for an archived but
uncompleted session. A still narrower failure window exists if S3 succeeds but
the archive-table insert fails. The runtime error must preserve the S3 keys and
version IDs so an administrator can locate and reconcile those objects.

## Required code and schema changes

Implementation is expected to involve:

1. Adding the `waiver_archive` database table.
2. Adding `session_id` to `waiver_access_log`.
3. Adding a `WaiverArchive` CodeIgniter model.
4. Updating `WaiverAccessLog` to record the active session ID.
5. Recording session creation and replacement lifecycle events.
6. Adding S3 archive and verification behavior at the storage layer.
7. Making local immutable writes safely reusable when existing contents match.
8. Updating `Waiver::finalize()` to archive after all local writes and before
   database completion.
9. Extending the final conditional completion update to require a confirmed
   archive record for the active session.
10. Returning archive and per-session audit information where appropriate in
    the reference API.
11. Adding focused tests for success, checksum mismatch, missing retention,
    S3 failure, local retry, session replacement, and final database failure.

Database migrations and the deployed schema live outside this repository. No
database changes should be executed as part of application-code development
without separate authorization.
