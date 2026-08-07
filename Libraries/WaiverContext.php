<?php

//    Randonneuring.org Website Software
//    Copyright (C) 2026 Chris Nadovich
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU Affero General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU Affero General Public License for more details.
//
//    https://randonneuring.org/LICENSE.txt
//
//    You should have received a copy of the GNU Affero General Public License
//    along with this program.  If not, see <https://www.gnu.org/licenses/>.


declare(strict_types=1);

namespace App\Libraries;

use App\Models\Region;
use App\Models\Event;
use App\Models\Roster;
use App\Models\WaiverSession;
use App\Models\EventWaiverContext;

use DateTimeImmutable;
use DateTimeZone;
use JsonException;
use RuntimeException;

/**
 * Builds, stores, retrieves, validates, and renders waiver context.
 *
 * Immutable event-level context is stored in event_waiver_context.
 * Participant-level context is stored in waiver.context_data.
 * Rendering uses those stored snapshots rather than current event,
 * club, or participant records.
 */

class WaiverContext
{
    private const EVENT_CONTEXT_FIELDS = [
        'event_code',
        'event_name',
        'event_start_at',
        'event_timezone_name',
        'organizing_club',
        'club_acp_code',
        'indemnified_party_id',
        'template_name',
        'revision',
    ];

    private const PARTICIPANT_CONTEXT_FIELDS = [
        'participant_id',
        'participant_name',
    ];

    private const CONTEXT_FIELDS = [
        ...self::EVENT_CONTEXT_FIELDS,
        ...self::PARTICIPANT_CONTEXT_FIELDS,
    ];

    // private const DERIVED_EVENT_FIELDS = [
    //     'event_date',
    //     'event_time',
    // ];

    /**
     * Session-owned fields that are not part of context_data.
     */
    private const SESSION_METADATA_FIELDS = [
        'session_id',
        'created_at',
        'expires_at',
    ];


    private Event $eventModel;
    private Region $regionModel;
    private WaiverSession $waiverSessionModel;
    private Roster $rosterModel;
    private EventWaiverContext $eventWaiverContextModel;

    public function __construct(
        Event $eventModel,
        Region $regionModel,
        EventWaiverContext $eventWaiverContextModel,
        WaiverSession $waiverSessionModel,
        Roster $rosterModel,
    ) {
        $this->eventModel = $eventModel;
        $this->regionModel = $regionModel;
        $this->eventWaiverContextModel =
            $eventWaiverContextModel;
        $this->waiverSessionModel = $waiverSessionModel;
        $this->rosterModel = $rosterModel;
        helper('url');
    }

    /**
     * Create or reuse a locally sourced waiver session and return all data
     * needed to render its waiver form.
     *
     * @return array<string, mixed>
     */
    public function createFromLocalData(
        string $event_code,
        string $participant_id,
        int $lifetime_seconds = 3600
    ): array {
        $contextData = $this->buildLocalContextData(
            $event_code,
            $participant_id
        );

        $eventContext = $this->selectFields(
            $contextData,
            self::EVENT_CONTEXT_FIELDS
        );

        $storedEventContext =
            $this->eventWaiverContextModel
            ->getOrCreateImmutable($eventContext);

        $waiverSession =
            $this->waiverSessionModel->createOrReuseSession(
                event_waiver_context_id: (int) $storedEventContext['id'],
                participant_id: $contextData['participant_id'],
                participant_name: $contextData['participant_name'],
                callback_url: null,
                lifetime_seconds: $lifetime_seconds
            );

        /*
         * The model may have returned an already-existing pending session.
         * Therefore, render from the context stored in the returned session,
         * not necessarily from the context freshly built above.
         */
        return $this->buildFromSession($waiverSession);
    }

    /**
     * Create or reuse a waiver session from normalized external data.
     *
     * The supplied data must already have been normalized using an
     * authenticated region.
     *
     * @param array<string, mixed> $contextData
     * @return array<string, mixed>
     */
    public function createFromExternalData(
        array $contextData,
        int $lifetime_seconds = 3600
    ): array {
        $this->validateContextData(
            $contextData
        );

        $callback_url = trim(
            (string) ($contextData['callback_url'] ?? '')
        );

        if ($callback_url === '') {
            throw new RuntimeException(
                'External waiver callback_url is missing.'
            );
        }

        /*
     * Select and freeze only event-owned fields.
     */
        $eventContext = $this->selectFields(
            $contextData,
            self::EVENT_CONTEXT_FIELDS
        );

        $storedEventContext =
            $this->eventWaiverContextModel
            ->getOrCreateImmutable(
                $eventContext
            );

        /*
     * Participant identity and callback_url are stored directly in
     * the waiver row.
     */
        $waiver_session =
            $this->waiverSessionModel
            ->createOrReuseSession(
                event_waiver_context_id: (int) $storedEventContext['id'],
                participant_id: $contextData['participant_id'],
                participant_name: $contextData['participant_name'],
                callback_url: $callback_url,
                lifetime_seconds: $lifetime_seconds
            );

        /*
     * Render from the stored event and waiver rows, not directly from
     * the freshly supplied request.
     */
        return $this->buildFromSession(
            $waiver_session
        );
    }




    /**
     * Build all rendering data from an existing waiver session.
     *
     * Event-level data comes from the immutable event_waiver_context row.
     * Participant-level data comes from waiver.context_data.
     *
     * No current event, club, or participant database records are queried.
     *
     * @param array<string, mixed> $waiverSession
     * @return array<string, mixed>
     */
    public function buildFromSession(array $waiverSession): array
    {

        $participantData = $this->selectFields(
            $waiverSession,
            [
                'participant_id',
                'participant_name',
            ]
        );

        /*
     * Load the immutable event-level waiver context.
     */
        $eventContextId = (int) (
            $waiverSession['event_waiver_context_id'] ?? 0
        );

        if ($eventContextId <= 0) {
            throw new RuntimeException(
                'The waiver session has no event waiver context.'
            );
        }

        $eventContext =
            $this->eventWaiverContextModel->requireById(
                $eventContextId
            );

        /*
     * Confirm that the session, participant snapshot, and event
     * context agree with one another.
     */
        $this->validateSessionContext(
            $waiverSession,
            $eventContext
        );
        /*
     * Load the exact template frozen for this event.
     */
        $template_name = $eventContext['template_name'];

        $waiverTemplate = new WaiverTemplate(
            $template_name
        );

        $templateRevision = trim(
            $waiverTemplate->data['REVISION'][0] ?? ''
        );

        if ($templateRevision === '') {
            throw new RuntimeException(
                "REVISION not specified in template: $template_name"
            );
        }

        if ($templateRevision !== $eventContext['revision']) {
            throw new RuntimeException(
                'The installed waiver template revision does not '
                    . 'match the revision frozen for this event.'
            );
        }

        /*
     * Obtain presentation data associated with the indemnified party.
     */
        $indemnifiedParty = new IndemnifiedParty(
            $eventContext['indemnified_party_id']
        );

        $waiverLogoFile = $indemnifiedParty->logo_name ?? '';
        if ($waiverLogoFile === '') {
            $waiverLogoFile = 'local/images/rusa-logo.png';
        }

        $waiverLogoUrl = $waiverTemplate->logoUrl(
            $waiverLogoFile
        );


        if (
            filter_var(
                $waiverLogoUrl,
                FILTER_VALIDATE_URL
            ) === false
        ) {
            throw new RuntimeException(
                "Invalid waiver logo URL: $waiverLogoUrl"
            );
        }

        /*
     * event_start_at is stored in the event context as UTC database
     * time. Derive the human-readable event date and time using the
     * event's frozen timezone.
     */
        $eventTimezone = $this->makeTimezone(
            $eventContext['event_timezone_name']
        );

        try {
            $eventStartUtc = new DateTimeImmutable(
                $eventContext['event_start_at'],
                new DateTimeZone('UTC')
            );

            $createdUtc = new DateTimeImmutable(
                $waiverSession['created_at'],
                new DateTimeZone('UTC')
            );
        } catch (\Exception $e) {
            throw new RuntimeException(
                'The stored waiver timing data is invalid.',
                0,
                $e
            );
        }

        $eventStartLocal = $eventStartUtc->setTimezone(
            $eventTimezone
        );

        $event_date = $eventStartLocal->format(
            'j F Y'
        );

        $event_time = $eventStartLocal->format(
            'g:i A T'
        );

        $createdLocal = $createdUtc->setTimezone(
            $eventTimezone
        );

        $waiverTimestamp = $createdLocal->format(
            'F j, Y \a\t g:i A T \(P\)'
        );

        /*
     * This preserves the current local entry-point URL.
     *
     * Once the session-ID-based form endpoint is introduced, this
     * should become a session URL.
     */
        $thisWaiverUrl = site_url(
            'waiver/start/'
                . rawurlencode($eventContext['event_code'])
                . '/'
                . rawurlencode(
                    $participantData['participant_id']
                )
        );

        /*
     * The event row contains database-format event_start_at. Replace
     * that value in the template map with an ISO 8601 representation,
     * while also adding the derived display date and time.
     */

        $eventData = array_merge(
            $this->selectFields(
                $eventContext,
                self::EVENT_CONTEXT_FIELDS
            ),
            [
                'event_start_at' =>
                $eventStartLocal->format(DATE_ATOM),
                'event_date' => $event_date,
                'event_time' => $event_time,
            ]
        );


        $sessionMetadata = $this->selectFields(
            $waiverSession,
            self::SESSION_METADATA_FIELDS
        );

        /*
     * Make all event, participant, and session values available to
     * the waiver template.
     */
        $sessionData = array_merge(
            $eventData,
            $participantData,
            $sessionMetadata,
            [
                'waiver_timestamp' => $waiverTimestamp,
            ]
        );

        $waiverReplacements = [
            'waiver_logo_url' => $waiverLogoUrl,
            'this_waiver_url' => $thisWaiverUrl,
        ];

        $interpolatedTemplate =
            $waiverTemplate->interpolate_template(
                array_merge(
                    $sessionData,
                    $waiverReplacements
                )
            );

        $sectionMap = [
            'title' =>
            $interpolatedTemplate['TITLE'][0] ?? '',
            'header' =>
            $interpolatedTemplate['HEADER'][0] ?? '',
            'initial' =>
            $interpolatedTemplate['INITIAL'][0] ?? '',
            'preamble' =>
            $interpolatedTemplate['PREAMBLE'][0] ?? '',
            'footer' =>
            $interpolatedTemplate['FOOTER'][0] ?? '',
            'revision' =>
            $eventContext['revision'],
            'clause' =>
            $interpolatedTemplate['CLAUSE'] ?? [],
            'esc' =>
            $interpolatedTemplate['ESC'][0] ?? '',
            'signature' =>
            $interpolatedTemplate['SIGNATURE'][0] ?? '',
        ];

        $replacementMap = array_merge(
            $sessionData,
            $waiverReplacements,
            $sectionMap
        );

        return [
            'replacementMap'     => $replacementMap,
            'waiverSession'      => $waiverSession,
            'waiverTemplate'     => $waiverTemplate,
            'eventContext'       => $eventContext,
            'participantData' => $participantData,
            'eventTimezone'      => $eventTimezone,
        ];
    }


    /**
     * Normalize externally supplied event and participant data.
     *
     * Club-owned values are derived from the authenticated region row.
     * The external caller must not supply those values authoritatively.
     *
     * Required caller fields:
     *
     *     event_id
     *     event_name
     *     event_start_at
     *     participant_id
     *     participant_name
     *     callback_url
     *
     * @param array<string, mixed> $submitted_data
     * @param array<string, mixed> $region
     * @return array<string, string>
     */
    public function normalizeExternalContext(
        array $submitted_data,
        array $region
    ): array {
        $submitted_fields = [
            'event_id',
            'event_name',
            'event_start_at',
            'participant_id',
            'participant_name',
            'callback_url',
        ];

        foreach ($submitted_fields as $field) {
            if (
                !isset($submitted_data[$field])
                || !is_string($submitted_data[$field])
                || trim($submitted_data[$field]) === ''
            ) {
                throw new RuntimeException(
                    "External waiver field $field is missing or invalid."
                );
            }
        }

        /*
     * Values controlled by the external caller.
     */
        $event_id = trim($submitted_data['event_id']);
        $event_name = trim($submitted_data['event_name']);
        $event_start_at = trim(
            $submitted_data['event_start_at']
        );
        $participant_id = trim(
            $submitted_data['participant_id']
        );
        $participant_name = trim(
            $submitted_data['participant_name']
        );
        $callback_url = trim(
            $submitted_data['callback_url']
        );

        if (!WaiverSession::isValidParticipantId($participant_id)) {
            throw new RuntimeException(
                'External participant_id must contain only letters, '
                    . 'digits, underscores, and hyphens.'
            );
        }

        /*
     * event_id becomes the suffix of the globally namespaced
     * event_code. Restrict it to the same character set permitted by
     * validateEventCode().
     */
        if (
            preg_match(
                '/\A[A-Za-z0-9_-]+\z/',
                $event_id
            ) !== 1
        ) {
            throw new RuntimeException(
                'External event_id must contain only letters, '
                    . 'digits, underscores, and hyphens.'
            );
        }

        /*
     * Club-owned values come exclusively from the authenticated
     * region row.
     */
        $club_acp_code = $region['club_acp_code'];
        $organizing_club = $region['club_name'];
        $event_timezone_name = $region['event_timezone_name'];
        $indemnified_party_id = $region['indemnified_party_id'];

        if ($organizing_club === '') {
            throw new RuntimeException(
                "Club name missing for $club_acp_code."
            );
        }

        if ($event_timezone_name === '') {
            throw new RuntimeException(
                "Event timezone missing for $club_acp_code."
            );
        }

        /*
     * Construct rather than accept the globally namespaced event
     * code.
     */
        $event_code =
            $club_acp_code . '-' . $event_id;

        /*
     * Template identity is determined by the configured indemnified
     * party, not by the external caller.
     */
        $indemnifiedParty = new IndemnifiedParty(
            $indemnified_party_id
        );

        $template_name = trim(
            (string) $indemnifiedParty->template_name
        );

        if ($template_name === '') {
            throw new RuntimeException(
                'The indemnified party has no waiver template.'
            );
        }

        $waiverTemplate = new WaiverTemplate(
            $template_name
        );

        $revision = trim(
            $waiverTemplate->data['REVISION'][0] ?? ''
        );

        if ($revision === '') {
            throw new RuntimeException(
                "REVISION not specified in template: $template_name"
            );
        }

        /*
     * Validate the callback before storing it. WaiverSession should
     * perform its own validation too, since it owns the column.
     */
$this->validateCallbackUrlTemplate(
    $callback_url
);

        /*
     * Ensure the supplied timestamp is valid and represents the
     * event in the authenticated club's configured timezone.
     */
        $eventTimezone = $this->makeTimezone(
            $event_timezone_name
        );

        $eventStart = $this->parseEventStartAt(
            $event_start_at
        );

        /*
     * Canonicalize the timestamp into the club's configured timezone.
     * This gives equivalent timestamps one stable representation.
     */
        $event_start_at = $eventStart
            ->setTimezone($eventTimezone)
            ->format(DATE_ATOM);

        /*
     * CONTEXT_FIELDS contains the event and participant fields used
     * by local and external creation. callback_url is session-level
     * and is added separately.
     */
        $contextData = compact(
            self::CONTEXT_FIELDS
        );

        $this->validateContextData(
            $contextData
        );

        $contextData['callback_url'] =
            $callback_url;

        return $contextData;
    }


private function validateCallbackUrlTemplate(
    string $callback_url
): void {
    if (trim($callback_url) === '') {
        throw new RuntimeException(
            'Callback URL is missing.'
        );
    }

    /*
     * Temporarily replace valid placeholders with harmless text so
     * the surrounding URL structure can be validated.
     */
    $test_url = preg_replace(
        '/\{\{[A-Za-z0-9_]+\}\}/',
        'placeholder',
        $callback_url
    );

    if ($test_url === null) {
        throw new RuntimeException(
            'Callback URL template is invalid.'
        );
    }

    /*
     * Reject malformed or partial placeholder syntax.
     */
    if (
        str_contains($test_url, '{{')
        || str_contains($test_url, '}}')
        || str_contains($test_url, '{')
        || str_contains($test_url, '}')
    ) {
        throw new RuntimeException(
            'Callback URL contains an invalid replacement field.'
        );
    }

    if (
        filter_var(
            $test_url,
            FILTER_VALIDATE_URL
        ) === false
    ) {
        throw new RuntimeException(
            'Callback URL template is invalid.'
        );
    }

    $scheme = strtolower(
        (string) parse_url(
            $test_url,
            PHP_URL_SCHEME
        )
    );

    if ($scheme !== 'https') {
        throw new RuntimeException(
            'Callback URL must use HTTPS.'
        );
    }
}

    /**
     * Build normalized context from the local event, club, and roster data.
     */

    public function buildLocalContextData(
        string $event_code,
        string $participant_id
    ): array {

        if ($event_code === '') {
            throw new RuntimeException(
                'Event code was not specified.'
            );
        }

        if ($participant_id === '') {
            throw new RuntimeException(
                'Rider ID not specified.'
            );
        }

        // Event info from database

        $event = $this->eventModel->eventByCode($event_code);

        if (empty($event)) {
            throw new RuntimeException(
                "Event $event_code was not found."
            );
        }

        $local_event_id = $event['event_id'];
        $club_acp_code = $event['region_id'];
        $event_name = $this->eventModel->nameDist($event);

        // Club info from database

        $club = $this->regionModel->getClub($club_acp_code);

        if (empty($club)) {
            throw new RuntimeException(
                "Organizing club $club_acp_code was not found."
            );
        }

        $organizing_club = trim(
            (string) ($club['club_name'] ?? '')
        );

        if ($organizing_club === '') {
            throw new RuntimeException(
                "Club name missing for $club_acp_code."
            );
        }

        $indemnified_party_id = $club['indemnified_party_id'];

        $indemnifiedParty = new \App\Libraries\IndemnifiedParty($indemnified_party_id);
        $template_name = $indemnifiedParty->template_name;

        // This will try to fetch the waiver template. 
        $waiverTemplate = new WaiverTemplate($template_name);



        // Participant info from database

        // Here the $is_rusa boolean modifies the behavior of registered_riders because
        // we have a local copy of the RUSA member data it's possible to validate 
        // rider names.  This is a hack because the fact that we are using a waiver from some
        // party shouldn't necessarily affect how participant names are normalized in rosters. But
        // there you have it. This is the mess you get into when RUSA forces regions to create our 
        // own rider information databases because they won't share theirs with regions. 

        $roster = $this->rosterModel->registered_riders($local_event_id, $indemnified_party_id == 'rusa');

        if (!is_array($roster)) {
            throw new RuntimeException(
                "Event roster is invalid for $event_code."
            );
        }

        $participant = null;

        foreach ($roster as $rosterEntry) {
            if (
                isset($rosterEntry['rider_id'])
                && (string) $rosterEntry['rider_id']
                === $participant_id
            ) {
                $participant = $rosterEntry;
                break;
            }
        }

        if ($participant === null) {
            throw new RuntimeException(
                "Participant ID $participant_id not found "
                    . "in roster for event $event_code."
            );
        }

        $first_name = trim(
            (string) ($participant['first_name'] ?? '')
        );

        $last_name = trim(
            (string) ($participant['last_name'] ?? '')
        );

        $participant_name = trim(
            $first_name . ' ' . $last_name
        );

        if ($participant_name === '') {
            throw new RuntimeException(
                "Participant name missing for rider $participant_id."
            );
        }



        // Time

        $event_timezone_name = trim(
            (string) ($club['event_timezone_name'] ?? '')
        );

        if ($event_timezone_name === '') {
            throw new RuntimeException(
                "Event timezone missing for club $club_acp_code."
            );
        }

        $eventTimezone = $this->makeTimezone($event_timezone_name);

        try {
            $event_datetime = new DateTimeImmutable(
                $event['start_datetime'],
                $eventTimezone
            );
        } catch (\Exception $e) {
            throw new RuntimeException(
                'Invalid event start date or time.',
                0,
                $e
            );
        }

        $event_start_at = $event_datetime->format(DATE_ATOM);
        // $event_date = $event_datetime->format('j F Y');
        // $event_time = $event_datetime->format('g:i A T');

        $revision = trim(
            $waiverTemplate->data['REVISION'][0] ?? ''
        );

        if ($revision === '') {
            throw new RuntimeException(
                "REVISION not specified in template: $template_name"
            );
        }

        $contextData = compact(
            self::CONTEXT_FIELDS
        );

        $this->validateContextData($contextData);

        return $contextData;
    }


    public function encodeParticipantContext(
        array $participantContext
    ): string {
        $this->validateParticipantContext(
            $participantContext
        );

        try {
            return json_encode(
                $participantContext,
                JSON_THROW_ON_ERROR
                    | JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE
            );
        } catch (JsonException $e) {
            throw new RuntimeException(
                'Unable to encode participant waiver context.',
                0,
                $e
            );
        }
    }

    /**
     * Decode normalized context from waiver.context_data.
     *
     * @return array<string, mixed>
     */
    public function decodeParticipantContext(?string $contextJson): array
    {
        if ($contextJson === null || trim($contextJson) === '') {
            throw new RuntimeException(
                'The waiver session contains no context data.'
            );
        }

        try {
            $contextData = json_decode(
                $contextJson,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            throw new RuntimeException(
                'The waiver session contains invalid context data.',
                0,
                $e
            );
        }

        if (!is_array($contextData)) {
            throw new RuntimeException(
                'The waiver session context is not a JSON object.'
            );
        }

        $this->validateParticipantContext(
            $contextData
        );
        return $contextData;
    }

    /**
     * Validate the normalized context schema.
     *
     **/

    public function validateParticipantContext(
        array $participantContext
    ): void {
        foreach (
            self::PARTICIPANT_CONTEXT_FIELDS as $field
        ) {
            if (
                !isset($participantContext[$field])
                || !is_string($participantContext[$field])
                || trim($participantContext[$field]) === ''
            ) {
                throw new RuntimeException(
                    "Participant waiver context field $field "
                        . 'is missing or invalid.'
                );
            }
        }

        if (
            !WaiverSession::isValidParticipantId(
                $participantContext['participant_id']
            )
        ) {
            throw new RuntimeException(
                'Participant waiver context field participant_id '
                    . 'must contain only letters, digits, underscores, '
                    . 'and hyphens.'
            );
        }
    }

    public function validateContextData(
        array $contextData
    ): void {
        foreach (self::CONTEXT_FIELDS as $field) {
            if (
                !isset($contextData[$field])
                || !is_string($contextData[$field])
                || trim($contextData[$field]) === ''
            ) {
                throw new RuntimeException(
                    "Waiver context field $field is missing or invalid."
                );
            }
        }

        $this->validateParticipantContext(
            $contextData
        );

        $this->validateEventCode(
            $contextData['event_code'],
            $contextData['club_acp_code']
        );

        $this->makeTimezone(
            $contextData['event_timezone_name']
        );

        $this->parseEventStartAt(
            $contextData['event_start_at']
        );
    }

    private function validateEventCode(
        string $event_code,
        string $club_acp_code
    ): void {
        if ($event_code !== trim($event_code)) {
            throw new RuntimeException(
                'Event code must not contain surrounding whitespace.'
            );
        }

        if ($club_acp_code !== trim($club_acp_code)) {
            throw new RuntimeException(
                'Club ACP code must not contain surrounding whitespace.'
            );
        }

        if ($event_code === '' || $club_acp_code === '') {
            throw new RuntimeException(
                'Event code and club ACP code must not be empty.'
            );
        }

        $pattern = '/\A'
            . preg_quote($club_acp_code, '/')
            . '-[A-Za-z0-9_-]+\z/';

        if (preg_match($pattern, $event_code) !== 1) {
            throw new RuntimeException(
                "Event code must begin with $club_acp_code- "
                    . 'and have a suffix containing only letters, '
                    . 'digits, underscores, and hyphens.'
            );
        }
    }

    /**
     * Confirm that a stored waiver session agrees with its participant
     * snapshot and immutable event waiver context.
     *
     * @param array<string, mixed> $waiverSession
     * @param array<string, mixed> $eventContext
     */
    private function validateSessionContext(
        array $waiverSession,
        array $eventContext
    ): void {
        $requiredSessionFields = array_merge(
            [
                'event_waiver_context_id',
                'participant_id',
                'participant_name',
            ],
            self::SESSION_METADATA_FIELDS
        );

        foreach ($requiredSessionFields as $field) {
            if (
                !isset($waiverSession[$field])
                || trim((string) $waiverSession[$field]) === ''
            ) {
                throw new RuntimeException(
                    "Waiver session field $field is missing."
                );
            }
        }

        if (
            !isset($eventContext['id'])
            || (int) $eventContext['id'] <= 0
        ) {
            throw new RuntimeException(
                'The event waiver context has no valid ID.'
            );
        }

        if (
            (int) $waiverSession['event_waiver_context_id']
            !== (int) $eventContext['id']
        ) {
            throw new RuntimeException(
                'The waiver session does not match the '
                    . 'event waiver context.'
            );
        }


        if (
            !isset($eventContext['event_start_at'])
            || !is_string($eventContext['event_start_at'])
            || trim($eventContext['event_start_at']) === ''
        ) {
            throw new RuntimeException(
                'The event waiver context has no event start time.'
            );
        }

        try {
            $createdAt = new DateTimeImmutable(
                $waiverSession['created_at'],
                new DateTimeZone('UTC')
            );

            $eventStart = new DateTimeImmutable(
                $eventContext['event_start_at'],
                new DateTimeZone('UTC')
            );
        } catch (\Exception $e) {
            throw new RuntimeException(
                'The stored waiver timing data is invalid.',
                0,
                $e
            );
        }

        if ($createdAt >= $eventStart) {
            throw new RuntimeException(
                'The waiver session was created at or after '
                    . 'the event start time.'
            );
        }
    }

    private function makeTimezone(
        string $timezoneName
    ): DateTimeZone {
        try {
            return new DateTimeZone($timezoneName);
        } catch (\Exception $e) {
            throw new RuntimeException(
                "Invalid event timezone: $timezoneName",
                0,
                $e
            );
        }
    }

    private function parseEventStartAt(
        string $event_start_at
    ): DateTimeImmutable {
        $eventStart = DateTimeImmutable::createFromFormat(
            DATE_ATOM,
            $event_start_at
        );

        $errors = DateTimeImmutable::getLastErrors();

        if (
            $eventStart === false
            || (
                $errors !== false
                && (
                    $errors['warning_count'] !== 0
                    || $errors['error_count'] !== 0
                )
            )
            || $eventStart->format(DATE_ATOM) !== $event_start_at
        ) {
            throw new RuntimeException(
                'Waiver context event_start_at must be '
                    . 'a valid ISO 8601 timestamp.'
            );
        }

        return $eventStart;
    }

    /**
     * Select named fields from an array.
     *
     * @param array<string, mixed> $source
     * @param list<string> $fields
     * @return array<string, mixed>
     */
    public function selectFields(
        array $source,
        array $fields
    ): array {
        $selected = [];

        foreach ($fields as $field) {
            if (!array_key_exists($field, $source)) {
                throw new RuntimeException(
                    "Expected field $field is missing."
                );
            }

            $selected[$field] = $source[$field];
        }

        return $selected;
    }
}
