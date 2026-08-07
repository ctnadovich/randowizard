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


namespace App\Models;

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Model;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

class WaiverSession extends Model
{

    public const SESSION_IDENTITY_FIELDS = [
        'event_waiver_context_id',
        'participant_id',
        'participant_name',
        'callback_url',
    ];

    public const ACTIVE_SESSION_FIELDS = [
        'session_id',
        'created_at',
        'expires_at',
        'status',
    ];

    public const COMPLETION_FIELDS = [
        'completed_at',
        'document_key',
        'document_sha256',
    ];


    /**
     * Complete set of database columns accepted by the model.
     */
    protected $allowedFields = [
        ...self::SESSION_IDENTITY_FIELDS,
        ...self::ACTIVE_SESSION_FIELDS,
        ...self::COMPLETION_FIELDS,
    ];
    protected $table            = 'waiver';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;
    protected $protectFields = true;


    /*
     * Waiver session statuses
     */
    public const STATUS_PENDING   = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED   = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Participant IDs are opaque caller identifiers restricted to a
     * URL- and filename-safe ASCII character set.
     */
    public static function isValidParticipantId(
        string $participantId
    ): bool {
        return preg_match(
            '/\A[A-Za-z0-9_-]+\z/',
            $participantId
        ) === 1;
    }


    /**
     * Create a new waiver session, or reuse the existing session for the
     * same participant and immutable event waiver context.
     *
     * Because the waiver table has a unique constraint on
     * (event_waiver_context_id, participant_id), there can be only one
     * waiver row for each participant/event combination.
     *
     * @return array<string, mixed>
     */
    public function createOrReuseSession(
        int $event_waiver_context_id,
        string $participant_id,
        string $participant_name,
        ?string $callback_url,
        int $lifetime_seconds = 3600
    ): array {
        if ($event_waiver_context_id <= 0) {
            throw new RuntimeException(
                'Event waiver context ID must be greater than zero.'
            );
        }

        if (!self::isValidParticipantId($participant_id)) {
            throw new RuntimeException(
                'Participant ID must contain only letters, digits, '
                    . 'underscores, and hyphens.'
            );
        }

        $this->assertNonEmpty(
            $participant_name,
            'participant name'
        );

        if ($lifetime_seconds <= 0) {
            throw new RuntimeException(
                'Waiver session lifetime must be greater than zero.'
            );
        }

        $existing =
            $this->findByParticipantAndEventContext(
                $event_waiver_context_id,
                $participant_id
            );


        if ($existing !== null) {

            $this->assertImmutableFieldsMatch(
                $existing,
                $participant_name,
                $callback_url
            );

            return $this->handleExistingSession(
                $existing,
                $lifetime_seconds
            );
        }



        /*
     * A simultaneous request could insert the same event/participant
     * combination after the SELECT above but before this INSERT.
     *
     * If the unique constraint rejects our insert, retrieve the row
     * that won the race and process it as an existing session.
     */
        try {
            return $this->insertNewSession(
                $event_waiver_context_id,
                $participant_id,
                $participant_name,
                $callback_url,
                $lifetime_seconds
            );
        } catch (DatabaseException $e) {
            $existing =
                $this->findByParticipantAndEventContext(
                    $event_waiver_context_id,
                    $participant_id
                );

            if ($existing !== null) {

                $this->assertImmutableFieldsMatch(
                    $existing,
                    $participant_name,
                    $callback_url
                );

                return $this->handleExistingSession(
                    $existing,
                    $lifetime_seconds
                );
            }

            throw $e;
        }
    }

    private function assertImmutableFieldsMatch(
        array $existing,
        string $participant_name,
        ?string $callback_url
    ): void {
        if (
            (string) ($existing['participant_name'] ?? '')
            !== $participant_name
        ) {
            throw new RuntimeException(
                'The supplied participant name does not match '
                    . 'the participant identity previously stored '
                    . 'for this event and participant ID.'
            );
        }

        if (
            ($existing['callback_url'] ?? null)
            !== $callback_url
        ) {
            throw new RuntimeException(
                'The supplied callback URL does not match '
                    . 'the callback URL previously stored for '
                    . 'this waiver.'
            );
        }
    }

    /**
     * Find the single waiver session belonging to a participant and
     * immutable event waiver context.
     *
     * @return array<string, mixed>|null
     */
    public function findByParticipantAndEventContext(
        int $event_waiver_context_id,
        string $participant_id
    ): ?array {
        if ($event_waiver_context_id <= 0) {
            throw new RuntimeException(
                'Event waiver context ID must be greater than zero.'
            );
        }

        $this->assertNonEmpty(
            $participant_id,
            'participant ID'
        );

        return $this
            ->where(
                'event_waiver_context_id',
                $event_waiver_context_id
            )
            ->where(
                'participant_id',
                $participant_id
            )
            ->first();
    }

    /**
     * Find a waiver session by its opaque public session ID.
     *
     * This method returns expired sessions as well. Use
     * getActiveSession() when processing a signing request.
     *
     * @return array<string, mixed>|null
     */
    public function findBySessionId(string $sessionId): ?array
    {
        if (!$this->isValidSessionIdFormat($sessionId)) {
            return null;
        }

        return $this
            ->where('session_id', strtolower($sessionId))
            ->first();
    }

    /**
     * Retrieve a session only when it has been completed.
     *
     * @return array<string, mixed>|null
     */
    public function getCompletedSession(string $sessionId): ?array
    {
        $session = $this->findBySessionId($sessionId);

        if ($session === null) {
            return null;
        }

        if ($session['status'] !== self::STATUS_COMPLETED) {
            return null;
        }

        return $session;
    }



    /**
     * Retrieve a session only when it is pending, unexpired, and the
     * associated event has not started.
     *
     * @return array<string, mixed>|null
     */
    public function getActiveSession(
        string $sessionId
    ): ?array {
        if (!$this->isValidSessionIdFormat($sessionId)) {
            return null;
        }

        $now = $this->formatDateTime(
            $this->nowUtc()
        );

        $session = $this
            ->select('waiver.*')
            ->join(
                'event_waiver_context AS e',
                'e.id = waiver.event_waiver_context_id'
            )
            ->where(
                'waiver.session_id',
                strtolower($sessionId)
            )
            ->where(
                'waiver.status',
                self::STATUS_PENDING
            )
            ->where('waiver.expires_at >', $now)
            ->where('e.event_start_at >', $now)
            ->first();

        if ($session !== null) {
            return $session;
        }

        /*
     * Preserve the prior behavior of marking an overdue pending
     * session expired.
     */
        $session = $this->findBySessionId($sessionId);

        if (
            $session !== null
            && $session['status'] === self::STATUS_PENDING
            && $this->isExpired($session)
        ) {
            $this->markExpiredById(
                (int) $session['id']
            );
        }

        return null;
    }


    /**
     * Mark a waiver session completed.
     *
     * This uses a conditional UPDATE so two simultaneous submissions
     * cannot both change a pending session to completed.
     */

    public function completeSession(
        string $sessionId,
        string $documentKey,
        string $documentSha256
    ): bool {
        if (!$this->isValidSessionIdFormat($sessionId)) {
            return false;
        }

        $this->assertNonEmpty($documentKey, 'document key');

        $documentSha256 = strtolower($documentSha256);

        if (!preg_match('/\A[a-f0-9]{64}\z/', $documentSha256)) {
            throw new \InvalidArgumentException(
                'Invalid SHA-256 document digest.'
            );
        }

        $now = $this->formatDateTime(
            $this->nowUtc()
        );



        $sql = <<<'SQL'
UPDATE waiver AS w
JOIN event_waiver_context AS e
  ON e.id = w.event_waiver_context_id
SET
    w.status = ?,
    w.completed_at = ?,
    w.document_key = ?,
    w.document_sha256 = ?
WHERE w.session_id = ?
  AND w.status = ?
  AND w.expires_at > ?
  AND e.event_start_at > ?
SQL;

        $this->db->query(
            $sql,
            [
                self::STATUS_COMPLETED,
                $now,
                $documentKey,
                $documentSha256,
                strtolower($sessionId),
                self::STATUS_PENDING,
                $now,
                $now,
            ]
        );



        return $this->db->affectedRows() === 1;
    }

    /**
     * Cancel a pending session.
     */
    public function cancelSession(string $sessionId): bool
    {
        if (!$this->isValidSessionIdFormat($sessionId)) {
            return false;
        }

        $this->builder()
            ->where('session_id', strtolower($sessionId))
            ->where('status', self::STATUS_PENDING)
            ->set('status', self::STATUS_CANCELLED)
            ->update();

        return $this->db->affectedRows() === 1;
    }

    /**
     * Mark all overdue pending sessions expired.
     *
     * This can be called periodically or before administrative queries.
     *
     * @return int Number of rows changed
     */
    public function expirePastSessions(): int
    {
        $this->builder()
            ->where('status', self::STATUS_PENDING)
            ->where('expires_at <', $this->formatDateTime($this->nowUtc()))
            ->set('status', self::STATUS_EXPIRED)
            ->update();

        return $this->db->affectedRows();
    }


    /**
     * Determine whether a participant has already completed a waiver
     * for the specified immutable event waiver context.
     */
    public function participantHasCompletedWaiver(
        int $event_waiver_context_id,
        string $participant_id
    ): bool {
        if ($event_waiver_context_id <= 0) {
            throw new RuntimeException(
                'Event waiver context ID must be greater than zero.'
            );
        }

        $this->assertNonEmpty(
            $participant_id,
            'participant ID'
        );

        return $this->builder()
            ->where(
                'event_waiver_context_id',
                $event_waiver_context_id
            )
            ->where(
                'participant_id',
                $participant_id
            )
            ->where(
                'status',
                self::STATUS_COMPLETED
            )
            ->countAllResults() > 0;
    }

    /**
     * Determine whether a retrieved session has expired.
     *
     * @param array<string, mixed> $session
     */
    public function isExpired(array $session): bool
    {
        if (empty($session['expires_at'])) {
            throw new RuntimeException(
                'The waiver session has no expiration time.'
            );
        }

        $expiresAt = new DateTimeImmutable(
            $session['expires_at'],
            $this->utcTimezone()
        );

        return $expiresAt < $this->nowUtc();
    }

    /**
     * Return all valid status strings.
     *
     * @return list<string>
     */
    public static function validStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_COMPLETED,
            self::STATUS_EXPIRED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Insert a new waiver session row.
     *
     * @return array<string, mixed>
     */
    private function insertNewSession(
        int $event_waiver_context_id,
        string $participant_id,
        string $participant_name,
        ?string $callback_url,
        int $lifetime_seconds
    ): array {
        $createdAt = $this->nowUtc();

        $expiresAt = $createdAt->modify(
            "+{$lifetime_seconds} seconds"
        );

        $data = [
            'event_waiver_context_id' => $event_waiver_context_id,
            'participant_id' => $participant_id,
            'participant_name'        => $participant_name,
            'callback_url'     => $callback_url,
            'session_id'     => $this->generateSessionId(),
            'created_at'     => $this->formatDateTime($createdAt),
            'expires_at'     => $this->formatDateTime($expiresAt),
            'status'         => self::STATUS_PENDING,
        ];

        $id = $this->insert($data, true);

        if ($id === false) {
            throw new RuntimeException(
                'Unable to create the waiver session: '
                    . implode('; ', $this->errors())
            );
        }

        return $this->requireSessionById(
            (int) $id,
            'The waiver session was inserted but could not be retrieved.'
        );
    }

    /**
     * Decide whether to reuse, return, or renew an existing waiver row.
     *
     * @param array<string, mixed> $existing
     * @return array<string, mixed>
     */
    private function handleExistingSession(
        array $existing,
        int $lifetime_seconds
    ): array {
        $status = $existing['status'] ?? null;

        if ($status === self::STATUS_COMPLETED) {
            return $existing;
        }

        if (
            $status === self::STATUS_PENDING
            && !$this->isExpired($existing)
        ) {
            return $existing;
        }

        if (
            $status === self::STATUS_PENDING
            || $status === self::STATUS_EXPIRED
            || $status === self::STATUS_CANCELLED
        ) {
            return $this->resetSession(
                (int) $existing['id'],
                $lifetime_seconds
            );
        }

        throw new RuntimeException(
            'Invalid waiver session status.'
        );
    }

    /**
     * Reset a non-completed waiver row as a fresh signing session.
     *
     * The participant and immutable event identity remain unchanged.
     *
     * @return array<string, mixed>
     */
    private function resetSession(
        int $id,
        int $lifetime_seconds
    ): array {
        if ($id <= 0) {
            throw new RuntimeException(
                'Waiver session ID must be greater than zero.'
            );
        }

        if ($lifetime_seconds <= 0) {
            throw new RuntimeException(
                'Waiver session lifetime must be greater than zero.'
            );
        }

        $existing = $this->requireSessionById(
            $id,
            'Waiver session not found.'
        );

        if ($existing['status'] === self::STATUS_COMPLETED) {
            throw new RuntimeException(
                'A completed waiver session cannot be reset.'
            );
        }

        $createdAt = $this->nowUtc();

        $expiresAt = $createdAt->modify(
            "+{$lifetime_seconds} seconds"
        );

        $resetData = array_merge(
            [
                'session_id'   => $this->generateSessionId(),
                'created_at'   => $this->formatDateTime($createdAt),
                'expires_at'   => $this->formatDateTime($expiresAt),
                'status'       => self::STATUS_PENDING,
            ],
            array_fill_keys(
                self::COMPLETION_FIELDS,
                null
            )
        );

        if ($this->update($id, $resetData) === false) {
            throw new RuntimeException(
                'Unable to reset the waiver session: '
                    . implode('; ', $this->errors())
            );
        }

        return $this->requireSessionById(
            $id,
            'The reset waiver session could not be retrieved.'
        );
    }


    /**
     * @return array<string, mixed>
     */
    private function requireSessionById(
        int $id,
        string $errorMessage
    ): array {
        $session = $this->find($id);

        if ($session === null) {
            throw new RuntimeException($errorMessage);
        }

        return $session;
    }

    private function markExpiredById(int $id): bool
    {
        $this->builder()
            ->where('id', $id)
            ->where('status', self::STATUS_PENDING)
            ->set('status', self::STATUS_EXPIRED)
            ->update();

        return $this->db->affectedRows() === 1;
    }

    private function generateSessionId(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function isValidSessionIdFormat(string $sessionId): bool
    {
        return preg_match('/\A[0-9a-fA-F]{32}\z/', $sessionId) === 1;
    }

    private function nowUtc(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->utcTimezone());
    }

    private function utcTimezone(): DateTimeZone
    {
        return new DateTimeZone('UTC');
    }

    private function formatDateTime(DateTimeImmutable $dateTime): string
    {
        return $dateTime->format('Y-m-d H:i:s');
    }


    private function assertNonEmpty(string $value, string $description): void
    {
        if (trim($value) === '') {
            throw new RuntimeException(
                ucfirst($description) . ' must not be empty.'
            );
        }
    }
}
