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
    protected $allowedFields = ['rba_user_id'];

    public function getRegions()
    {
        $this->select('region.*, region.id as club_acp_code, tz.name as event_timezone_name, state.code as state_code, country.code as country_code');
        $this->join('tz', 'region.event_timezone_id=tz.id');
        $this->join('state', 'region.state_id=state.id');
        $this->join('country', 'region.country_id=country.id');
        return $this->findAll();
    }

    public function getRegionsEbrevet()
    {
        $this->select('region.id as club_acp_code, region_name, state.fullname as state_name, club_name, website_url, icon_url, tz.name as event_timezone_name, state.code as state_code, country.code as country_code');
        $this->join('tz', 'region.event_timezone_id=tz.id');
        $this->join('state', 'region.state_id=state.id');
        $this->join('country', 'region.country_id=country.id');
        return $this->findAll();
    }

    public function getClub($club_acp_code)
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

    public function getAuthorizedRegions($user_id){
        $this->where('rba_user_id',$user_id);
        return $this->findColumn('id');
    }

    public function hasEvents(){
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
}
