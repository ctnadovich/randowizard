# Randonneuring.org Waiver API Quick Start

The Randonneuring.org Waiver API lets another application create a waiver-signing session for a participant.

The basic workflow is:

1. Send the event and participant information to the API.
2. Receive a waiver session ID and waiver URL.
3. Save the session ID.
4. Redirect the participant to the waiver URL.
5. Receive the participant back at your callback URL after the waiver is completed.
6. Verify and save the waiver PDF for your records.

For unusual edge cases or implementation details: **Use the source, Luke.**

## Initial Request

Begin with a `POST` to:

```text
https://randonneuring.org/waiver/startExternal
```

The request uses HTTP Basic authentication:

```text
Username: controlling club ACP code
Password: API key
```

Send and accept JSON:

```text
Accept: application/json
Content-Type: application/json
```

## API Key

Use the API key assigned to the region identified by the controlling club ACP code.

Region managers can generate an API key from the region-management page on randonneuring.org. Record the key when it is generated. Generating a replacement key invalidates the previous one.

Keep the key on the server. Do not place it in browser JavaScript, HTML, or a public repository.

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
    "participant_id": "123456",
    "participant_name": "Example Rider",
    "callback_url": "https://example.org/waiver-return/{{event_code}}/{{participant_id}}/{{session_id}}"
  }' \
  "https://randonneuring.org/waiver/startExternal"
```

## Request Fields

### `event_id`

An identifier assigned by the calling application.

It must be unique for each event within the region identified by the controlling club ACP code. It may contain letters, numbers, dashes, and underscores, but no spaces.

```json
"event_id": "my-event-2026-001"
```

Once the first waiver session is created for an `event_id`, the event details are locked to that ID. Changing the event name or start time requires a new event ID, and participants should sign new waivers using the new event details.

### `event_name`

The event name displayed in the waiver. It will usually include the event distance.

Spaces and punctuation are allowed.

```json
"event_name": "Example 200K"
```

The event name is locked to the `event_id` after the first waiver session is created.

### `event_start_at`

The event start date and time.

Use an ISO 8601 timestamp with a timezone offset:

```json
"event_start_at": "2026-09-12T06:00:00-04:00"
```

The event start time is also locked to the `event_id` after the first waiver session is created.

### `participant_id`

An identifier for the participant.

For RUSA regions this is normally the rider's numeric RUSA ID. For other events it may be a frame number, bib number, or another identifier chosen by the caller.

It may contain letters, numbers, dashes, and underscores, but no spaces.

```json
"participant_id": "123456"
```

### `participant_name`

The participant name displayed in the waiver.

```json
"participant_name": "Example Rider"
```

The caller is responsible for verifying the participant's identity and for ensuring that the person signing the waiver is the participant named in the request.

### `callback_url`

The URL to which the participant is redirected after completing the waiver.

```json
"callback_url": "https://example.org/waiver-return/{{event_code}}/{{participant_id}}/{{session_id}}"
```

The callback URL may contain template replacements. Any replacement supported by the waiver template system may be used in the callback URL.

Common replacements include:

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
https://example.org/waiver-return/938017-1234/123456/abc123xyz
```

The callback URL does not need to use all three replacements. These are also valid examples:

```text
https://caller.example.org/check_the_waiver_yet_again/{{session_id}}
```

```text
https://myclub.org/thank_you_for_registering/{{event_code}}/
```

## The Important Replacement: `{{session_id}}`

The `{{session_id}}` replacement is the key to connecting the completed waiver back to the caller's records.

When the caller creates the waiver session, the API returns a session ID:

```json
{
  "waiver_session_id": "abc123xyz",
  "waiver_url": "https://randonneuring.org/waiver/abc123xyz",
  "expires_at": "2026-09-12T05:00:00-04:00"
}
```

Save that session ID with the participant or registration record.

After the participant completes the waiver, the waiver system redirects the participant to the callback URL with `{{session_id}}` replaced:

```text
https://example.org/waiver-return/938017-1234/123456/abc123xyz
```

The caller can then:

1. Read the session ID from the callback URL.
2. Look up the session ID saved after the initial API call.
3. Identify the participant and event associated with that waiver.
4. Mark the waiver session as completed.
5. Retrieve or preserve the completed waiver PDF as needed.

Because the same session ID appears in both the initial API response and the callback, it identifies exactly which waiver was completed.

## Successful Response

A successful response contains:

```json
{
  "waiver_session_id": "abc123xyz",
  "waiver_url": "https://randonneuring.org/waiver/abc123xyz",
  "expires_at": "2026-09-12T05:00:00-04:00"
}
```

The current example script accepts either:

```text
waiver_session_id
```

or:

```text
session_id
```

as the session ID field.

After receiving the response:

1. Verify that the HTTP status is in the `200`-`299` range.
2. Parse the JSON response.
3. Save the session ID.
4. Redirect the participant to `waiver_url`.

Do not construct the waiver URL yourself. Use the returned `waiver_url`.

## Callback Route Example

A caller might define a route such as:

```text
GET /waiver-return/{event_code}/{participant_id}/{session_id}
```

using this callback template:

```text
https://example.org/waiver-return/{{event_code}}/{{participant_id}}/{{session_id}}
```

When the callback is received, use `session_id` to locate the waiver session saved after the initial API call.

The event and participant values are useful for routing, logging, and consistency checks, but the session ID is the direct link to the created waiver session.

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
  "event_start_at": "ISO-8601 timestamp with timezone offset",
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
Verify and preserve the completed waiver PDF
```
