# Randonneuring.org Waiver API Quick Start

The Randonneuring.org Waiver API lets another application create a waiver-signing session for a participant.

The basic workflow is:

1. Send the intial request with event and participant information to the API.
2. Receive a response with waiver session ID and waiver URL.
3. Save the session ID.
4. Redirect the participant to the waiver URL.
5. Receive the participant back at your callback URL (with embedded session ID) after the waiver is completed.
6. Verify and save the waiver PDF for your records.


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

The URL to which the participant is redirected after completing the waiver. The redirection is manual -- the participant will click a CONTINUE link to this URL. 

```json
"callback_url": "https://example.org/waiver-return/{{session_id}}"
```

### Callback URL Replacements

The callback URL may contain template replacements as a convenience to the caller. Any replacement supported by the waiver template system may be used in the callback URL. The above example shows `{{session_id}}` used as a replacement.

Other replacements include:

```text
{{session_id}}
{{event_code}}
{{participant_id}}
{{created_at}}
{{completed_at}}
{{expires_at}}
```
Before redirecting the participant back to the caller, the waiver system interpolates callback URL replacements with their actual values. There are several replacements available. None are strictly required, but in many situations ``session_id`` is critical. Whereas all the others can be looked up later using the session ID as the key, but in some cases they can be convenient to include in the URL (validation, testing, etc...)

The `{{session_id}}` replacement is the key to connecting the completed waiver back to the caller's records.
After the participant completes the waiver, the waiver system redirects the participant to the callback URL. The redirection is manual -- the participant will click a CONTINUE link to this URL.  With `{{session_id}}` replaced, assuming the Session ID is 'abc123xyz' the callback URL clicked by the participant might look like:

```text
https://example.org/waiver-return/abc123xyz
```
Without the session ID in the callback URL, the page that is called-back will not know what waiver was completed generating that callback. 

## Successful Response

A successful response to the request contains:

```json
{
  "session_id": "abc123xyz",
  "waiver_url": "https://randonneuring.org/waiver/abc123xyz",
  "document_url": "https://randonneuring.org/document/abc123xyz",
  "reference_url": "https://randonneuring.org/reference/abc123xyz",
  "expires_at": "2026-09-12T05:00:00-04:00"
}
```
### `session_id`

A unique identifier for the waiver session. This ID is locked to the event and participant details given in the initial 
request. The caller should record the session_id and use it as a key to reference a waiver before, during, and after completion.   

### `waiver_url`

The URL of the HTML waiver form. The caller should record this URL and direct the participant to it. The participant
is expected to read, sign, and review the form before hitting CONTIUE, which is a link to the callback URL. 

### `document_url`

The URL of the final signed PDF document. 

### `reference_url`

A URL that returns a JSON object that gives comprehensive information about the waiver session, including exact times various aspects were completed, IP address and User Agent of the participant and the caller, as well as a chronological log of all access to the waiver. 

### `expires_at`

The waiver session will expire at this time. The caller should record this time. If a callback is not received prior to this time, the caller can assume the waiver will never arrive and should abort any activity related to this session. 

In summary, after receiving the response the caller should:

1. Verify that the HTTP status is in the `200`-`299` range.
2. Parse the JSON response.
3. Save the session ID, waiver URL, document URL and expiration time.
4. Redirect the participant to `waiver_url`.

**Do not construct the waiver, document, or reference URLs yourself. Use the returned URLs in the response.**


## Callback Processing

After the callback is received, the caller knows the waiver has been completed. There's no obligation
for the caller to do anything further, but some options might be to direct the participant to a payment page, or maybe just a thank-you page. If the waiver is used as part of a registration system, the callback would return to that system. 


A typical response to the callback might be: 

1. Read the session ID from the callback URL.
2. Look up the session ID saved after the initial API call.
3. Identify the participant and event associated with that waiver.
4. Mark the waiver session as completed.
5. Retrieve or preserve the completed waiver PDF and/or reference data as needed.
6. Continue on with registration, payment, or display a thank you page

## Example Code

A very basic [shell script example](https://github.com/ctnadovich/randowizard/blob/main/WAIVER_EXAMPLE_curl.sh) is available. You can use this script to test your parameters and API credentials, as well 
as a reference implementation easily translated into your favorite web framework or language. 

## License

The waiver API software for the Randonneuring.org website is written in [PHP](https://www.php.net/) and requires the [CodeIgniter 4](https://www.codeigniter.com/) framework.

The source code for this website [is available for free download](https://github.com/ctnadovich/randowizard) under the terms of the GNU Affero General Public License.

*Copyright (C) 2026 [Chris Nadovich](https://nadovich.com/chris/contact.cgi)*

This program is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but **WITHOUT ANY WARRANTY**; without even the implied warranty of **MERCHANTABILITY** or **FITNESS FOR A PARTICULAR PURPOSE**. See the GNU Affero General Public License for more details.

> **Detailed License Terms:**  
> https://randonneuring.org/LICENSE.txt