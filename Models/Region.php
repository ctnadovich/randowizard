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

    public function getClub($club_acp_code)
    {
        $this->select('region.*, tz.name as event_timezone_name');
        $this->join('tz', 'region.event_timezone_id=tz.id');
        $this->where('region.id', $club_acp_code);
        $result = $this->first();

        if (empty($result)) {
            return null;
        } else {
            $result['event_timezone'] = new \DateTimeZone($result['event_timezone_name']);
            return $result;
        }
    }
}
