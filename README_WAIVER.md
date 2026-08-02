# Randonneuring.org Waiver API Quick Start

The Randonneuring.org Waiver API lets another application create a waiver-signing session for a participant.

The basic workflow is:

1. Send the event and participant information to the API.
2. Receive a waiver session ID and waiver URL.
3. Save the session ID.
4. Redirect the participant to the waiver URL.
5. Receive the participant back at your callback URL after the waiver is completed.

For obscure edge cases or implementation details: **Use the source, Luke.**

## Endpoint

```text
POST https://randonneuring.org/waiver/startExternal
```

The request uses HTTP Basic authentication:

```text
Username: club ACP code
Password: API key
```

Send and accept JSON:

```text
Accept: application/json
Content-Type: application/json
```

## Quick Start with curl

```bash
curl \
  --request POST \
  --user "938017:YOUR_API_KEY" \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data '{
    "event_id": "my-event-2026-001",
    "event_name": "Example 200K",
    "event_start_at": "2026-09-12T06:00:00-04:00",
    "participant_id": "rider-12345",
    "participant_name": "Example Rider",
    "callback_url": "https://example.org/waiver-return/{{event_code}}/{{participant_id}}/{{session_id}}"
  }' \
  "https://randonneuring.org/waiver/startExternal"
```

## Request Fields

### `event_id`

An event identifier assigned by the calling application.

Treat it as an opaque string.

```json
"event_id": "my-event-2026-001"
```

### `event_name`

The event name displayed in the waiver.

```json
"event_name": "Example 200K"
```

### `event_start_at`

The event start date and time.

Use an ISO 8601 timestamp with a timezone offset:

```json
"event_start_at": "2026-09-12T06:00:00-04:00"
```

### `participant_id`

A participant identifier assigned by the calling application.

Treat it as an opaque string.

```json
"participant_id": "rider-12345"
```

### `participant_name`

The participant name displayed in the waiver.

```json
"participant_name": "Example Rider"
```

### `callback_url`

The URL to which the participant is redirected after completing the waiver.

```json
"callback_url": "https://example.org/waiver-return/{{event_code}}/{{participant_id}}/{{session_id}}"
```

The callback URL may contain string-replacement fields:

```text
{{event_code}}
{{participant_id}}
{{session_id}}
```

Before redirecting the participant back to the caller, the waiver system replaces these strings with their actual values.

For example:

```text
https://example.org/waiver-return/{{event_code}}/{{participant_id}}/{{session_id}}
```

might become:

```text
https://example.org/waiver-return/938017-1234/rider-12345/abc123xyz
```

## The Important Replacement: `{{session_id}}`

The `{{session_id}}` replacement is the key to connecting the completed waiver back to the caller's records.

When the caller first creates the waiver session, the API returns a session ID:

```json
{
  "waiver_session_id": "abc123xyz",
  "waiver_url": "https://randonneuring.org/waiver/abc123xyz",
  "expires_at": "2026-09-12T05:00:00-04:00"
}
```

The caller should save that session ID with its participant or registration record.

Later, after the participant completes the waiver, the waiver system redirects the participant to the callback URL with `{{session_id}}` replaced:

```text
https://example.org/waiver-return/938017-1234/rider-12345/abc123xyz
```

The caller can then:

1. Read `abc123xyz` from the callback URL.
2. Look up the waiver session previously stored under that ID.
3. Determine which participant and event the completed waiver belongs to.
4. Mark that waiver session as completed.

Because the caller received and stored the same session ID when the session was created, the callback identifies exactly which waiver was completed.

## Successful Response

A successful response contains:

```json
{
  "waiver_session_id": "abc123xyz",
  "waiver_url": "https://randonneuring.org/waiver/abc123xyz",
  "expires_at": "2026-09-12T05:00:00-04:00"
}
```

The current test bench accepts either:

```text
waiver_session_id
```

or:

```text
session_id
```

as the session ID field.

After receiving the response, the caller should:

1. Verify that the HTTP status is successful.
2. Parse the JSON response.
3. Save the session ID.
4. Redirect the participant to `waiver_url`.

Do not construct the waiver URL yourself. Use the returned `waiver_url`.

## Callback Route Example

A caller might define a route such as:

```text
GET /waiver-return/{event_code}/{participant_id}/{session_id}
```

Using the callback template:

```text
https://example.org/waiver-return/{{event_code}}/{{participant_id}}/{{session_id}}
```

When the callback is received, the application should primarily use `session_id` to locate the waiver session it stored after the initial API call.

The event and participant values are convenient for routing, logging, and consistency checks, but the session ID is the direct link to the created waiver session.

## Error Handling

Treat any HTTP status outside the `200`-`299` range as a failed request.

Also treat a successful response as invalid if it does not contain either:

```text
waiver_session_id
```

or:

```text
session_id
```

For detailed validation rules, exact error responses, duplicate-request behavior, or unusual edge cases:

**Use the source, Luke.**

## Minimal Integration Summary

Create the waiver session:

```text
POST /waiver/startExternal
```

Send:

```json
{
  "event_id": "caller-event-id",
  "event_name": "Event name",
  "event_start_at": "ISO-8601 timestamp",
  "participant_id": "caller-participant-id",
  "participant_name": "Participant name",
  "callback_url": "https://caller.example/waiver-return/{{event_code}}/{{participant_id}}/{{session_id}}"
}
```

Then:

```text
Save the returned session ID
Redirect the participant to waiver_url
Receive the callback containing that session ID
Match it to the stored waiver session
Mark the waiver completed
```
