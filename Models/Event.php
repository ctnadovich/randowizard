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

class Event extends Model
{
    protected $table      = 'event';
    protected $primaryKey = 'id';
    protected $returnType     = 'array';
    protected $allowedFields = [];

    public function getEventsForClub($club_acp_code){
        $this->select('event.*, state.fullname as start_state');
        $this->join('state','state.id=event.start_state_id');
        $this->where('event.region_id',$club_acp_code);
        return $this->findAll();
    }

    public function getEvent($club_acp_code, $local_event_id){
        $this->select('event.*, state.fullname as start_state');
        $this->join('state','state.id=event.start_state_id');
        $this->where(['event.region_id'=>$club_acp_code, 'event.id'=>$local_event_id ]);  // overconstrained
        return $this->first();
    }


    public function getEventTable(){

        $sql =   
            "SELECT event.id as event_id, event.*, region.*, 
            s1.code as event_start_state, s2.code as region_state,
            tz.name as event_timezone_name
            from event, region, state as s1, state as s2, tz
            WHERE event.region_id=region.id and 
            s1.id=event.start_state_id and 
            s2.id=region.state_id  and
            tz.id=region.event_timezone_id
            order by region_state, region_name, start_datetime";
        $q=$this->query($sql);
        $r = $q->getResultArray();

        return $r;
    }

}