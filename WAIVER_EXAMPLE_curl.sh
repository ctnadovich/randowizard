#!/usr/bin/env bash

set -u

# ------------------------------------------------------------------
# Configuration
# ------------------------------------------------------------------

# Official Randonneuring.org WAIVER API URL 
BASE_URL="https://randonneuring.org"
ENDPOINT="${BASE_URL}/waiver/startExternal"

# Your region's controlling club ACP code
CLUB_ACP_CODE="938017"

# API Key for above region -- get this from the region manager at randonneuring.org
API_KEY="0000000000000000000000000000000000000000000000000000000000000000"

# Event information.
# MAY NOT CHANGE for a given EVENT_ID after the first waiver is begun

# EVENT_ID must be unique for every event within the region specified by CLUB_ACP_CODE
# May be any alpha numeric string, possibly with dashes or underscores. No blanks. 
EVENT_ID="api-test-001"

# EVENT_NAME can be any name descriptive of the event. Usually this includes the distance. 
EVENT_NAME="External Waiver API Test"

# The event start date and time as an ISO 8601 timestamp with a timezone offset
EVENT_START_AT="2099-08-15T08:00:00-04:00"

# Participant information.
# The human name and ID of the participant should be verified by the caller of 
# this waiver API system. Verifying that the signer of the document is truly the 
# identified participant is the responsibility of the API caller. 

# Human name of the participant
PARTICIPANT_NAME="API Test Participant"

# This is the numeric RUSA ID for all RUSA regions, and typically a frame/bib number for other events. 
# May be any alpha numeric string, possibly with dashes or underscores. No blanks. 
PARTICIPANT_ID="123456"

# CALLBACK
# Provide a callback URL with string replacements that will be automatically filled in
# by the waiver API system. Most useful is {{session_id}} as this associates the callback 
# with the original caller session. Any replacement allowed in the template is allowed here. 

CALLBACK_URL="https://caller.example.com/callback/{{event_code}}/{{participant_id}}/{{session_id}}"

# Other example callback urls
#CALLBACK_URL="https://caller.example.org/check_the_waiver_yet_again/{{session_id}}"
#CALLBACK_URL="https://myclub.org/thank_you_for_registering/{{event_code}}/"

# API Coding Example Follows (Bash shell script with curl)

# ------------------------------------------------------------------
# Construct request JSON
# ------------------------------------------------------------------

REQUEST_BODY=$(cat <<JSON
{
  "event_id": "${EVENT_ID}",
  "event_name": "${EVENT_NAME}",
  "event_start_at": "${EVENT_START_AT}",
  "participant_id": "${PARTICIPANT_ID}",
  "participant_name": "${PARTICIPANT_NAME}",
  "callback_url": "${CALLBACK_URL}"
}
JSON
)

echo "POST ${ENDPOINT}"
echo
echo "Request body:"
echo "${REQUEST_BODY}"
echo

# ------------------------------------------------------------------
# Perform request
# ------------------------------------------------------------------

RESPONSE_FILE=$(mktemp)
trap 'rm -f "${RESPONSE_FILE}"' EXIT

HTTP_STATUS=$(
    curl \
        --silent \
        --show-error \
        --output "${RESPONSE_FILE}" \
        --write-out "%{http_code}" \
        --request POST \
        --user "${CLUB_ACP_CODE}:${API_KEY}" \
        --header "Accept: application/json" \
        --header "Content-Type: application/json" \
        --data "${REQUEST_BODY}" \
        "${ENDPOINT}"
)

echo "HTTP status: ${HTTP_STATUS}"
echo
echo "Response body:"

if command -v jq >/dev/null 2>&1; then
    jq . "${RESPONSE_FILE}" 2>/dev/null \
        || cat "${RESPONSE_FILE}"
else
    cat "${RESPONSE_FILE}"
fi

echo

# ------------------------------------------------------------------
# Basic result checking
# ------------------------------------------------------------------

if [[ "${HTTP_STATUS}" -lt 200 || "${HTTP_STATUS}" -ge 300 ]]; then
    echo "ERROR: startExternal request failed." >&2
    exit 1
fi

if command -v jq >/dev/null 2>&1; then
    SESSION_ID=$(
        jq -r \
            '.session_id // empty' \
            "${RESPONSE_FILE}"
    )

    WAIVER_URL=$(
        jq -r \
            '.waiver_url // empty' \
            "${RESPONSE_FILE}"
    )

    EXPIRES_AT=$(
        jq -r \
            '.expires_at // empty' \
            "${RESPONSE_FILE}"
    )

    if [[ -z "${SESSION_ID}" ]]; then
        echo \
            "ERROR: Successful response contained no waiver session ID." \
            >&2
        exit 1
    fi

    echo "Waiver session ID: ${SESSION_ID}"
    echo "Waiver URL:        ${WAIVER_URL}"
    echo "Expires at:        ${EXPIRES_AT}"
fi

echo
echo "startExternal test succeeded."
