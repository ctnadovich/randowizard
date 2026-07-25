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

class WaiverSessionModel extends Model
{
    protected $table            = 'waiver';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType = 'array';

    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;

    protected $protectFields = true;

    protected $allowedFields = [
        'session_id',
        'event_code',
        'participant_id',
        'template_name',
        'revision',
        'created_at',
        'expires_at',
        'status',
    ];

    /*
     * Waiver session statuses
     */
    public const STATUS_PENDING   = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED   = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Create a new waiver session, or reuse the existing pending,
     * unexpired session for this participant and event.
     *
     * Because the table has a unique constraint on
     * (event_code, participant_id), there can only be one row for
     * each participant/event combination.
     *
     * @return array<string, mixed>
     */
    public function createOrReuseSession(
        string $eventCode,
        string $participantId,
        string $templateName,
        string $revision,
        int $lifetimeSeconds = 3600
    ): array {
        $this->assertNonEmpty($eventCode, 'event code');
        $this->assertNonEmpty($participantId, 'participant ID');
        $this->assertNonEmpty($templateName, 'template name');
        $this->assertNonEmpty($revision, 'template revision');

        if ($lifetimeSeconds <= 0) {
            throw new RuntimeException(
                'Waiver session lifetime must be greater than zero.'
            );
        }

        $existing = $this->findByParticipantAndEvent(
            $eventCode,
            $participantId
        );

        if ($existing !== null) {
            return $this->handleExistingSession(
                $existing,
                $templateName,
                $revision,
                $lifetimeSeconds
            );
        }

        /*
         * A simultaneous request could insert the same
         * event/participant combination after our SELECT but before
         * this INSERT. If that happens, retrieve the row that won
         * the race.
         */
        try {
            return $this->insertNewSession(
                $eventCode,
                $participantId,
                $templateName,
                $revision,
                $lifetimeSeconds
            );
        } catch (DatabaseException $e) {
            $existing = $this->findByParticipantAndEvent(
                $eventCode,
                $participantId
            );

            if ($existing !== null) {
                return $this->handleExistingSession(
                    $existing,
                    $templateName,
                    $revision,
                    $lifetimeSeconds
                );
            }

            throw $e;
        }
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
     * Retrieve a session only when it is pending and unexpired.
     *
     * If the session has passed its expiration time, its status is
     * changed to "expired" and null is returned.
     *
     * @return array<string, mixed>|null
     */
    public function getActiveSession(string $sessionId): ?array
    {
        $session = $this->findBySessionId($sessionId);

        if ($session === null) {
            return null;
        }

        if ($session['status'] !== self::STATUS_PENDING) {
            return null;
        }

        if ($this->isExpired($session)) {
            $this->markExpiredById((int) $session['id']);

            return null;
        }

        return $session;
    }

    /**
     * Find the single session belonging to a participant at an event.
     *
     * @return array<string, mixed>|null
     */
    public function findByParticipantAndEvent(
        string $eventCode,
        string $participantId
    ): ?array {
        return $this
            ->where('event_code', $eventCode)
            ->where('participant_id', $participantId)
            ->first();
    }

    /**
     * Mark a waiver session completed.
     *
     * This uses a conditional UPDATE so two simultaneous submissions
     * cannot both change a pending session to completed.
     */
    public function completeSession(string $sessionId): bool
    {
        if (!$this->isValidSessionIdFormat($sessionId)) {
            return false;
        }

        $now = $this->nowUtc();

        $this->builder()
            ->where('session_id', strtolower($sessionId))
            ->where('status', self::STATUS_PENDING)
            ->where('expires_at >=', $this->formatDateTime($now))
            ->set('status', self::STATUS_COMPLETED)
            ->update();

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
     * Determine whether this participant has completed the waiver.
     */
    public function participantHasCompletedWaiver(
        string $eventCode,
        string $participantId
    ): bool {
        return $this
            ->where('event_code', $eventCode)
            ->where('participant_id', $participantId)
            ->where('status', self::STATUS_COMPLETED)
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
     * Insert a new session row.
     *
     * @return array<string, mixed>
     */
    private function insertNewSession(
        string $eventCode,
        string $participantId,
        string $templateName,
        string $revision,
        int $lifetimeSeconds
    ): array {
        $createdAt = $this->nowUtc();
        $expiresAt = $createdAt->add(
            new DateInterval('PT' . $lifetimeSeconds . 'S')
        );

        $data = [
            'session_id'    => $this->generateSessionId(),
            'event_code'    => $eventCode,
            'participant_id' => $participantId,
            'template_name' => $templateName,
            'revision'      => $revision,
            'created_at'    => $this->formatDateTime($createdAt),
            'expires_at'    => $this->formatDateTime($expiresAt),
            'status'        => self::STATUS_PENDING,
        ];

        $id = $this->insert($data, true);

        if ($id === false) {
            throw new RuntimeException(
                'Unable to create the waiver session: ' .
                implode('; ', $this->errors())
            );
        }

        $session = $this->find((int) $id);

        if ($session === null) {
            throw new RuntimeException(
                'The waiver session was inserted but could not be retrieved.'
            );
        }

        return $session;
    }

    /**
     * Decide what to do with an existing participant/event row.
     *
     * @param array<string, mixed> $existing
     * @return array<string, mixed>
     */
    private function handleExistingSession(
        array $existing,
        string $templateName,
        string $revision,
        int $lifetimeSeconds
    ): array {
        /*
         * A completed waiver must never silently become pending again.
         */
        if ($existing['status'] === self::STATUS_COMPLETED) {
            return $existing;
        }

        /*
         * Reuse an active pending session only if it represents the same
         * template and revision.
         */
        if (
            $existing['status'] === self::STATUS_PENDING
            && !$this->isExpired($existing)
            && $existing['template_name'] === $templateName
            && $existing['revision'] === $revision
        ) {
            return $existing;
        }

        /*
         * The unique event/participant constraint prevents inserting
         * another row. Reset this non-completed row as a fresh session.
         */
        return $this->resetSession(
            (int) $existing['id'],
            $templateName,
            $revision,
            $lifetimeSeconds
        );
    }

    /**
     * Reset a non-completed row as a fresh signing session.
     *
     * @return array<string, mixed>
     */
    private function resetSession(
        int $id,
        string $templateName,
        string $revision,
        int $lifetimeSeconds
    ): array {
        $existing = $this->find($id);

        if ($existing === null) {
            throw new RuntimeException('Waiver session not found.');
        }

        if ($existing['status'] === self::STATUS_COMPLETED) {
            throw new RuntimeException(
                'A completed waiver session cannot be reset.'
            );
        }

        $createdAt = $this->nowUtc();
        $expiresAt = $createdAt->add(
            new DateInterval('PT' . $lifetimeSeconds . 'S')
        );

        $updated = $this->update($id, [
            'session_id'   => $this->generateSessionId(),
            'template_name' => $templateName,
            'revision'     => $revision,
            'created_at'   => $this->formatDateTime($createdAt),
            'expires_at'   => $this->formatDateTime($expiresAt),
            'status'       => self::STATUS_PENDING,
        ]);

        if ($updated === false) {
            throw new RuntimeException(
                'Unable to reset the waiver session: ' .
                implode('; ', $this->errors())
            );
        }

        $session = $this->find($id);

        if ($session === null) {
            throw new RuntimeException(
                'The reset waiver session could not be retrieved.'
            );
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