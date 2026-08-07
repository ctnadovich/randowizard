# Waiver Subsystem Architecture

## Overview

The waiver subsystem is a relatively self-contained workflow within the larger
CodeIgniter application. `Controllers/Waiver.php` is its orchestration layer,
while context construction, session lifecycle rules, immutable document
storage, templates, and audit logging are delegated to dedicated models and
libraries.

```text
Local event roster                 External registration system
        |                          Basic auth + JSON request
        +---------------+----------------------+
                        v
                   WaiverContext
                        v
         immutable event/template snapshot
           + participant waiver session
                        v
                  HTML signing form
                        v
        server-side validation + PDF rendering
                        v
         immutable PDF and metadata storage
                        v
           atomic transition to completed
                        v
  completion page / PDF endpoint / JSON reference record
```

The subsystem's central design principle is that signing and rendering use
stored snapshots rather than mutable current event, region, or roster records.

## Main components

- `Controllers/Waiver.php` coordinates HTTP requests and responses.
- `Libraries/WaiverContext.php` normalizes local and external inputs, creates
  stored context, and reconstructs rendering data.
- `Models/EventWaiverContext.php` stores immutable event-level legal context.
- `Models/WaiverSession.php` manages participant sessions and their lifecycle.
- `Models/WaiverAccessLog.php` records access history.
- `Libraries/WaiverTemplate.php` loads, validates, and interpolates waiver text.
- `Libraries/IndemnifiedParty.php` maps party identifiers to templates and
  logos.
- `Libraries/WaiverStorage.php` stores completed PDFs and metadata without
  permitting overwrite.
- `Helpers/waiver_helper.php` safely renders the template's lightweight markup.
- `Views/waiver/default_theme/` contains the form, shared document, completion
  page, styles, and PDF wrapper.
- `README_WAIVER_API.md` documents the external integration contract.

## Controller endpoints

The controller exposes seven principal operations:

| Method | Purpose |
|---|---|
| `start(event_code, participant_id)` | Creates or reuses a session from the local event and roster database, then renders the form. |
| `startExternal()` | Authenticates an external system, validates JSON, and returns session resource URLs. |
| `session(session_id)` | Loads an active session by opaque ID and renders the signing form. |
| `finalize()` | Validates the submission, generates and stores the PDF, and completes the session. |
| `completed(session_id)` | Displays confirmation, checksum, PDF link, and optional callback link. |
| `document(session_id)` | Serves a completed PDF inline. |
| `reference(session_id)` | Returns session metadata, immutable event context, and access history as JSON. |

HTTP route declarations and framework filters live outside this repository, so
their exact configuration cannot be assessed here.

## Local waiver creation

The local path begins with an event code and participant ID. `WaiverContext`
loads:

- Event identity, name, and start time.
- The controlling region and timezone.
- The indemnified party and associated template.
- The participant name from the event roster.

It validates that the event, region, template, and participant exist before
creating or reusing a session. Local sessions do not have callback URLs.

## External API creation

External callers submit JSON to `waiver/startExternal` using HTTP Basic
authentication:

```text
username = controlling club ACP code
password = region waiver API key
```

The region API key is generated from 32 random bytes and returned as a
hexadecimal string. Only a PHP password hash is stored. Replacing the key
invalidates the previous one.

The request supplies:

- `event_id`
- `event_name`
- `event_start_at`
- `participant_id`
- `participant_name`
- `callback_url`

Club-owned data comes exclusively from the authenticated region rather than
from the caller. This includes the global event-code namespace, organizing club
name, event timezone, indemnified party, template, and template revision.

The global event code is constructed as:

```text
<authenticated ACP club code>-<external event ID>
```

Event and participant IDs may contain only letters, digits, underscores, and
hyphens. Event timestamps must be strict ISO 8601 values and are canonicalized
into the region's configured timezone before being stored in UTC.

Callback URLs must use HTTPS. They may contain these replacements:

```text
{{session_id}}
{{event_code}}
{{participant_id}}
```

The callback is a manual continuation link displayed after completion. Visiting
it does not finalize the waiver; finalization has already occurred.

A successful creation response contains:

- The opaque session ID.
- The waiver form URL.
- The future document URL.
- The JSON reference URL.
- The session expiration time in ISO 8601 UTC format.

## Immutable event context

`EventWaiverContext` freezes the event-level legal context when the first
session is created for an event code:

- Event code and event name.
- Club code and organizing-club name.
- Event start time and timezone.
- Indemnified party.
- Template filename and revision.

Later requests for the same event code must match every frozen field exactly.
Changing event details or the legal template therefore requires a new event
code.

Creation is race-aware. A unique database constraint on `event_code` determines
which of two simultaneous inserts succeeds. The other request reloads the
winning row and verifies that its context matches.

This separation prevents a stored waiver from changing because an organizer
later edits the underlying event, region, roster, or template selection.

## Session lifecycle

`WaiverSession` defines four states:

```text
pending -> completed
   |
   +----> expired
   +----> cancelled

expired/cancelled -> renewed pending session with a new public ID
completed         -> immutable terminal state
```

The public session ID contains 128 random bits encoded as 32 hexadecimal
characters. Possession of this ID acts as the access capability for signing and
retrieval endpoints.

There is one waiver database row per immutable event context and participant
ID, enforced by a unique constraint. Repeated creation behaves idempotently:

- A valid pending session is reused.
- A completed session is returned unchanged.
- An expired or cancelled session is reset with a new public session ID.
- The participant name and callback URL cannot change for the same
  event/participant pair.

The default lifetime is one hour. Signing is permitted only before both the
session expiration and the event start. `getActiveSession()` enforces these
conditions when loading the form. `completeSession()` enforces them again in a
conditional database update, preventing stale or concurrent submissions from
bypassing the deadline.

## Form rendering and submission

The browser form uses Signature Pad to capture the signature and initials as
PNG data URLs. It also requires:

- An assertion that the participant is at least 18 years old.
- Consent to use an electronic signature.

Client-side checks control the interface, but `finalize()` independently
enforces every requirement on the server.

Submitted images must:

- Use the exact `data:image/png;base64,` prefix.
- Contain strict, valid Base64 data.
- Decode to bytes beginning with the PNG signature.
- Stay below the two-million-character encoded limit for each image.

The server then reconstructs the waiver from the authoritative session and
immutable event context. It does not trust submitted participant, event, or
template details.

The shared `Views/waiver/default_theme/document.php` template renders both the
interactive HTML document and final PDF. This reduces the risk that the signed
PDF differs materially from what the participant saw.

## Templates and presentation

`WaiverTemplate` loads tagged text templates from the parent installation's
public waiver assets. It:

- Restricts template and logo names to simple filenames.
- Parses tagged sections such as revision, header, clauses, and consent text.
- Discovers and validates `{{replacement_fields}}`.
- Interpolates normalized context data.
- Converts template typography to Windows-1252-compatible text.

Template content supports a deliberately small markup language:

- `**text**` renders as bold.
- `!!text!!` renders as red.

The helper escapes the original content before introducing these controlled
HTML elements.

`IndemnifiedParty` is a registry mapping identifiers to organization names,
template filenames, and logos. It currently defines `rusa` and an `other`
example organization.

When rebuilding a session, the currently installed template revision must
match the revision frozen in the event context. A changed template file blocks
rendering rather than silently changing an existing event's legal document.

## PDF generation and completion

Finalization proceeds in this order:

1. Reconstruct the authoritative waiver data.
2. Add the submitted signature, initials, and acknowledgements.
3. Render a letter-sized PDF using Dompdf.
4. Calculate its SHA-256 digest.
5. Construct the document key
   `event_code/participant_id/session_id.pdf`.
6. Store the PDF and sidecar metadata immutably.
7. Atomically mark the session completed.
8. Record the completion in the access log.
9. Redirect to the completion page.

Dompdf remote assets are enabled because the logo is loaded through HTTPS, but
remote retrieval is restricted to `randonneuring.org`. The resulting bytes are
checked for a PDF signature before storage.

## Immutable document storage

`WaiverStorage` stores documents beneath `WRITEPATH/waivers`, outside this
repository. Every document-key component is validated to prevent path
traversal.

PDF and metadata files are opened in exclusive-create mode, so existing files
cannot be overwritten. They are made read-only after writing. The sidecar JSON
metadata includes:

- Session, event, and participant identifiers.
- Template name and revision.
- MIME type and byte length.
- Storage timestamp.
- SHA-256 digest.

This is filesystem-level protection against accidental changes, not true
write-once storage such as an object-lock service.

Database completion uses a conditional joined update. Only a pending,
unexpired session whose event has not begun can transition to completed, and
only one concurrent request can perform that transition.

The filesystem write and database update are not part of one transaction. A
failure after storage but before database completion can therefore leave an
orphaned document. The code reports this condition but contains no automated
reconciliation mechanism.

## Completion and callbacks

The completion page displays:

- Event and participant details.
- Completion time.
- A shortened SHA-256 digest with the full digest available in the UI.
- A link to the signed PDF.
- An optional external callback link.

Callback placeholders are URL-encoded before insertion. The fully interpolated
URL is validated again before it is displayed.

The callback is intentionally manual. A missing callback does not prove that
the participant failed to sign; external systems should query the reference URL
when authoritative status is needed.

## Document and reference retrieval

The document endpoint serves a PDF only for a completed session. The download
filename is safely constructed from the event and participant identifiers.

The reference endpoint is available for sessions in any state and returns:

```json
{
  "waiver": {
    "participant_id": "...",
    "participant_name": "...",
    "callback_url": "...",
    "session_id": "...",
    "created_at": "...",
    "expires_at": "...",
    "status": "...",
    "completed_at": "...",
    "document_sha256": "...",
    "document_url": "..."
  },
  "event_context": {
    "event_code": "...",
    "club_acp_code": "...",
    "event_name": "...",
    "event_start_at": "...",
    "event_timezone_name": "...",
    "organizing_club": "...",
    "indemnified_party_id": "...",
    "template_name": "...",
    "revision": "..."
  },
  "access_log": []
}
```

Internal database identifiers and the filesystem document key are omitted. UTC
database timestamps are converted into ISO 8601 values with explicit UTC
offsets.

The reference request records itself before retrieving the access history, so
the response includes the current lookup.

## Audit trail

`WaiverAccessLog` records the access type, IP address, user agent, and UTC
timestamp. Defined event types are:

- `external_start`
- `local_start`
- `signer_start`
- `signer_completed`
- `completed_view`
- `document_view`
- `reference_view`

Entries are returned in chronological order, with the database ID used as a
tie-breaker.

There is an implementation gap: `external_start` is defined but
`startExternal()` does not record it. As a result, the API documentation's claim
that reference metadata includes the original external caller's IP address and
user agent is not currently fulfilled for session creation.

## Security boundary

Only external session creation uses region API-key authentication. Signing,
completion, document, and reference endpoints rely on possession of the random
session ID. The session URL is therefore a bearer capability and must be
treated as sensitive.

The reference endpoint exposes participant information, callback URL, IP
addresses, and user agents to anyone possessing that ID. Completed PDFs are
protected by the same capability model.

Additional API characteristics include:

- Authentication failures from `startExternal()` return HTTP 400 JSON rather
  than the conventional HTTP 401 response.
- Creation returns a document URL before the PDF exists; it becomes usable only
  after completion.
- Repeating creation for an already completed waiver returns the completed
  session. Its returned document and reference URLs remain valid, but the
  returned waiver-form URL points to an endpoint that rejects completed
  sessions.
- The document endpoint retrieves the stored PDF but does not recalculate and
  compare its digest before serving it.

## Architectural assessment

The waiver subsystem has clearer separation and stronger lifecycle discipline
than much of the surrounding controller-oriented application. Its main
strengths are:

- Immutable legal and event context.
- Authoritative server-side document reconstruction.
- Strict input validation for identifiers, times, callbacks, and images.
- Conditional, race-resistant session creation and completion.
- Non-overwriting document storage and explicit SHA-256 metadata.
- A chronological access trail.
- Shared HTML/PDF document rendering.

Its principal architectural seams are:

- Capability URLs expose sensitive resources if leaked.
- External session creation is not currently added to the access log.
- Filesystem storage and database completion are not transactional.
- A few idempotency and HTTP-status edge cases remain in the external API.
- Stored document checksums are not verified during retrieval.
