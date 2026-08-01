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

namespace App\Models;

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Model;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

class EventWaiverContext extends Model
{
    protected $table            = 'event_waiver_context';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;
    protected $protectFields  = true;

    /**
     * Immutable event-level waiver fields.
     *
     * event_start_at is accepted as an ISO 8601 context value but is
     * normalized to UTC database format before storage.
     */
    public const EVENT_CONTEXT_FIELDS = [
        'event_code',
        'club_acp_code',
        'event_name',
        'event_start_at',
        'event_timezone_name',
        'organizing_club',
        'indemnified_party_id',
        'template_name',
        'revision',
    ];

    protected $allowedFields = [
        ...self::EVENT_CONTEXT_FIELDS,
        'created_at',
    ];

    /**
     * Obtain the immutable event waiver context for an event.
     *
     * The first call for an event_code creates the row. Later calls must
     * supply exactly the same event-level context.
     *
     * @param array<string, mixed> $eventContext
     * @return array<string, mixed>
     */
    public function getOrCreateImmutable(
        array $eventContext
    ): array {
        $normalized = $this->normalizeEventContext(
            $eventContext
        );

        $existing = $this->findByEventCode(
            $normalized['event_code']
        );

        if ($existing !== null) {
            $this->assertContextMatches(
                $existing,
                $normalized
            );

            return $existing;
        }

        $data = array_merge(
            $normalized,
            [
                'created_at' => $this->formatDateTime(
                    $this->nowUtc()
                ),
            ]
        );

        /*
         * Two simultaneous first-waiver requests may both find no row.
         * The unique event_code index decides which insert succeeds.
         */
        try {
            $id = $this->insert($data, true);
        } catch (DatabaseException $e) {
            $existing = $this->findByEventCode(
                $normalized['event_code']
            );

            if ($existing === null) {
                throw $e;
            }

            $this->assertContextMatches(
                $existing,
                $normalized
            );

            return $existing;
        }

        if ($id === false) {
            throw new RuntimeException(
                'Unable to create event waiver context: '
                    . implode('; ', $this->errors())
            );
        }

        $created = $this->find((int) $id);

        if ($created === null) {
            throw new RuntimeException(
                'The event waiver context was created '
                    . 'but could not be retrieved.'
            );
        }

        return $created;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByEventCode(
        string $eventCode
    ): ?array {
        if (trim($eventCode) === '') {
            return null;
        }

        return $this
            ->where('event_code', $eventCode)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function requireById(int $id): array
    {
        $eventContext = $this->find($id);

        if ($eventContext === null) {
            throw new RuntimeException(
                "Event waiver context $id was not found."
            );
        }

        return $eventContext;
    }

    /**
     * Validate and normalize proposed immutable event data.
     *
     * @param array<string, mixed> $eventContext
     * @return array<string, string>
     */
    private function normalizeEventContext(
        array $eventContext
    ): array {
        $normalized = [];

        foreach (self::EVENT_CONTEXT_FIELDS as $field) {
            if (
                !isset($eventContext[$field])
                || !is_string($eventContext[$field])
                || trim($eventContext[$field]) === ''
            ) {
                throw new RuntimeException(
                    "Event waiver context field $field "
                        . 'is missing or invalid.'
                );
            }

            $normalized[$field] = $eventContext[$field];
        }

        /*
         * Preserve identifiers and text exactly as validated by
         * WaiverContext. Only normalize the timestamp representation.
         */
        $eventStart = $this->parseEventStartAt(
            $normalized['event_start_at']
        );

        $normalized['event_start_at'] =
            $this->formatDateTime(
                $eventStart->setTimezone(
                    $this->utcTimezone()
                )
            );

        return $normalized;
    }

    /**
     * Confirm that a proposed definition agrees with the frozen row.
     *
     * @param array<string, mixed> $existing
     * @param array<string, string> $proposed
     */
    private function assertContextMatches(
        array $existing,
        array $proposed
    ): void {
        foreach (self::EVENT_CONTEXT_FIELDS as $field) {
            if (
                !array_key_exists($field, $existing)
                || (string) $existing[$field]
                    !== $proposed[$field]
            ) {
                throw new RuntimeException(
                    "Event waiver context field $field cannot "
                        . 'be changed after the first waiver '
                        . 'session has been created. Create a '
                        . 'new event code for the changed event.'
                );
            }
        }
    }

    private function parseEventStartAt(
        string $eventStartAt
    ): DateTimeImmutable {
        $eventStart = DateTimeImmutable::createFromFormat(
            DATE_ATOM,
            $eventStartAt
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
            || $eventStart->format(DATE_ATOM)
                !== $eventStartAt
        ) {
            throw new RuntimeException(
                'event_start_at must be a valid ISO 8601 '
                    . 'timestamp in DATE_ATOM format.'
            );
        }

        return $eventStart;
    }

    private function nowUtc(): DateTimeImmutable
    {
        return new DateTimeImmutable(
            'now',
            $this->utcTimezone()
        );
    }

    private function utcTimezone(): DateTimeZone
    {
        return new DateTimeZone('UTC');
    }

    private function formatDateTime(
        DateTimeImmutable $dateTime
    ): string {
        return $dateTime->format('Y-m-d H:i:s');
    }
}