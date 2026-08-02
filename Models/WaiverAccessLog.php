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

use CodeIgniter\Model;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

class WaiverAccessLog extends Model
{
    protected $table = 'waiver_access_log';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';

    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
    protected $protectFields = true;

    public const ACCESS_FIELDS = [
        'method',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    private const IDENTITY_FIELDS = [
        'waiver_id',
    ];

    protected $allowedFields = [
        ...self::IDENTITY_FIELDS,
        ...self::ACCESS_FIELDS,
    ];

    public const METHOD_EXTERNAL_START = 'external_start';
    public const METHOD_LOCAL_START = 'local_start';
    public const METHOD_SIGNER_START = 'signer_start';
    public const METHOD_SIGNER_COMPLETED = 'signer_completed';
    public const METHOD_COMPLETED_VIEW = 'completed_view';
    public const METHOD_DOCUMENT_VIEW = 'document_view';
    public const METHOD_REFERENCE_VIEW = 'reference_view';

    public function record(
        int $waiver_id,
        string $method,
        string $ip_address,
        ?string $user_agent
    ): void {
        if ($waiver_id <= 0) {
            throw new RuntimeException(
                'Waiver ID must be greater than zero.'
            );
        }

        if (trim($method) === '') {
            throw new RuntimeException(
                'Access-log method must not be empty.'
            );
        }

        if (trim($ip_address) === '') {
            throw new RuntimeException(
                'Access-log IP address must not be empty.'
            );
        }

        $data = compact(
            'waiver_id',
            'method',
            'ip_address',
            'user_agent'
        );

        $data['created_at'] = (
            new DateTimeImmutable(
                'now',
                new DateTimeZone('UTC')
            )
        )->format('Y-m-d H:i:s');

        if ($this->insert($data) === false) {
            throw new RuntimeException(
                'Unable to record waiver access: '
                    . implode('; ', $this->errors())
            );
        }
    }

    /**
     * Return all access-log entries for a waiver in chronological order.
     *
     * @return list<array<string, mixed>>
     */
    public function findByWaiverId(
        int $waiver_id
    ): array {
        if ($waiver_id <= 0) {
            throw new RuntimeException(
                'Waiver ID must be greater than zero.'
            );
        }

        return $this
            ->where('waiver_id', $waiver_id)
            ->orderBy('created_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
