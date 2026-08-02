<?php

//    Randonneuring.org Website Software
//    Copyright (C) 2023 Chris Nadovich
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

use CodeIgniter\Model;

class Region extends Model
{
    protected $table      = 'region';
    protected $primaryKey = 'id';
    protected $returnType     = 'array';
    protected $allowedFields = ['rba_user_id','waiver_api_key_hash'];

    public function getRegionsOld()
    {
        $this->select('region.*, region.id as club_acp_code, tz.name as event_timezone_name, state.code as state_code, country.code as country_code');
        $this->join('tz', 'region.event_timezone_id=tz.id');
        $this->join('state', 'region.state_id=state.id');
        $this->join('country', 'region.country_id=country.id');
        return $this->findAll();
    }

    public function getRegions()
    {
        $this->select('region.*, region.id as club_acp_code, state.code as state_code, country.code as country_code');
        $this->join('state', 'region.state_id=state.id');
        $this->join('country', 'region.country_id=country.id');
        return $this->findAll();
    }
    public function getRegionsEbrevet()
    {
        $this->select('region.id as club_acp_code, region_name, state.fullname as state_name, club_name, website_url, icon_url, event_timezone_name, 
        state.code as state_code, country.code as country_code, options as options');
        $this->join('state', 'region.state_id=state.id');
        $this->join('country', 'region.country_id=country.id');
        return $this->findAll();
    }


    public function getRegionsEbrevetOld()
    {
        $this->select('region.id as club_acp_code, region_name, state.fullname as state_name, club_name, website_url, icon_url, tz.name as event_timezone_name, 
        state.code as state_code, country.code as country_code, options as options');
        $this->join('tz', 'region.event_timezone_id=tz.id');
        $this->join('state', 'region.state_id=state.id');
        $this->join('country', 'region.country_id=country.id');
        return $this->findAll();
    }

    public function getClubOld($club_acp_code)
    {
        $this->select('region.*, tz.name as event_timezone_name, state.code as region_state_code, country.code as region_country_code');
        $this->join('tz', 'region.event_timezone_id=tz.id');
        $this->join('state', 'region.state_id=state.id');
        $this->join('country', 'region.country_id=country.id');
        $this->where('region.id', $club_acp_code);
        $result = $this->first();

        if (empty($result)) {
            return null;
        } else {
            $result['event_timezone'] = new \DateTimeZone($result['event_timezone_name']);
            return $result;
        }
    }

    public function getClub($club_acp_code)
    {
        $this->select('region.*, state.code as region_state_code, country.code as region_country_code, region.id as club_acp_code');
        $this->join('state', 'region.state_id=state.id');
        $this->join('country', 'region.country_id=country.id');
        $this->where('region.id', $club_acp_code);
        $result = $this->first();

        if (empty($result)) {
            return null;
        } else {
            $result['event_timezone'] = new \DateTimeZone($result['event_timezone_name']);
            $result['indemnified_party_id'] =
                $this->indemnifiedPartyId(
                    $club_acp_code
                );
            return $result;
        }
    }


    public function hasOption($club_acp_code, $option)
    {
        $this->select('region.options');
        $this->where('region.id', $club_acp_code);
        $result = $this->first();
        $opt_list = explode(',', $result['options']);
        return false !== array_search($option, $opt_list);
    }

    public function getAuthorizedRegions($user_id)
    {
        $this->where('rba_user_id', $user_id);
        return $this->findColumn('id');
    }

    public function hasEvents()
    {
        $this->select('region.id as region_id, count(region.id) as event_count, state.code as state_code, region.region_name, region.club_name');
        $this->join('event', 'region.id=event.region_id');
        $this->join('tz', 'region.event_timezone_id=tz.id');
        $this->join('state', 'region.state_id=state.id');
        $this->join('country', 'region.country_id=country.id');
        $this->where("FIND_IN_SET('hidden',status)=0");
        $this->orderBy('state_code ASC, region_name ASC');
        $this->groupBy("region.id");
        return $this->findAll();
    }

    private function indemnifiedPartyId(
        string $club_acp_code
    ): string {
        return $this->hasOption(
            $club_acp_code,
            'rusa'
        )
            ? 'rusa'
            : 'other';
    }

    /**
     * Generate and store a new Waiver API key for a club.
     *
     * The plaintext key is returned exactly once. Only its hash is
     * stored in the database.
     *
     * @throws \RuntimeException
     */
 public function generateWaiverApiKey(
    string $club_acp_code
): string {
    $club = $this->getClub($club_acp_code);

    if (empty($club)) {
        throw new \RuntimeException(
            "Club $club_acp_code was not found."
        );
    }

    $api_key = bin2hex(
        random_bytes(32)
    );

    $waiver_api_key_hash = password_hash(
        $api_key,
        PASSWORD_DEFAULT
    );

    if ($waiver_api_key_hash === false) {
        throw new \RuntimeException(
            'Unable to hash the API key.'
        );
    }

    $updated = $this->builder()
        ->where('id', $club_acp_code)
        ->update([
            'waiver_api_key_hash' =>
                $waiver_api_key_hash,
        ]);

    if ($updated === false) {
        throw new \RuntimeException(
            'Unable to store the API key.'
        );
    }

    return $api_key;
}
    private function generateApiKey(): string
    {
        return bin2hex(random_bytes(32));
    }


    /**
     * Authenticate a region for external waiver API access.
     *
     * @return array<string, mixed>|null
     */
    public function authenticateWaiverApi(
        string $club_acp_code,
        string $api_key
    ): ?array {
        if (
            $club_acp_code === ''
            || $club_acp_code !== trim($club_acp_code)
            || $api_key === ''
        ) {
            return null;
        }

        $region = $this->getClub(
            $club_acp_code
        );

        if (empty($region)) {
            return null;
        }

        $waiver_api_key_hash = trim(
            (string) (
                $region['waiver_api_key_hash'] ?? ''
            )
        );

        if ($waiver_api_key_hash === '') {
            return null;
        }

        if (
            !password_verify(
                $api_key,
                $waiver_api_key_hash
            )
        ) {
            return null;
        }

        return $region;
    }
}
