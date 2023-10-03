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

class Rba extends Model
{
    protected $table      = 'rba';
    protected $primaryKey = 'id';
    protected $returnType     = 'array';
    protected $allowedFields = ['region_id', 'user_id'];

    public function hasRBA($region_id)
    {
        $rbas = $this->where('region_id', $region_id)->findAll();
        return count($rbas) > 0 ? true : false;
    }

    public function getAuthorizedRegions($user_id)
    {
        $this->where('user_id', $user_id);
        return $this->findColumn('region_id');
    }

    public function deleteRBAUser($user_id)
    {
        $this->where('user_id', $user_id);
        return $this->delete();
    }

    public function insertRBAforRegion($user_id, $region_id){
        $this->insert(compact('user_id','region_id'));
    }


    public function getAuthorizedRegionObjects($user_id)
    {
        $this->select('rba.region_id as club_acp_code, region.*, state.code as state_code');
        $this->join('region', 'rba.region_id=region.id');
        // $this->join('tz', 'region.event_timezone_id=tz.id');
        $this->join('state', 'region.state_id=state.id');
        // $this->join('country', 'region.country_id=country.id');
        $this->where('user_id', $user_id);
        return $this->findAll();
    }

    public function getAuthorizedUsers($region_id){
        $this->where('region_id', $region_id);
        return $this->findColumn('user_id');
    }

    public function getAuthorizedUserObjects($region_id){
        $this->select('user_id, user.*');
        $this->join('user', 'rba.user_id=user.id');
       $this->where('region_id', $region_id);
        return $this->findAll();
    }


}
