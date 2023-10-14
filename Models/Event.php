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
    protected $allowedFields = ['cue_version','last_changed'];

    // public function getEventsForClub($club_acp_code)
    // {
    //     $this->select('event.*, state.fullname as start_state');
    //     $this->join('state', 'state.id=event.start_state_id');
    //     $this->where('event.region_id', $club_acp_code);
    //     return $this->findAll();
    // }

    public function nameDist($event)
    {
        return $event['name'] . ' ' . $event['distance'] . "K";
    }



    public function getEvent($club_acp_code, $local_event_id)
    {

        $sql =
            "SELECT event.id as event_id, event.*, region.*, 
            s1.code as start_state, s2.code as region_state,
            tz.name as timezone_name
            from event, region, state as s1, state as s2, tz
            WHERE event.id = '$local_event_id' and
            region.id = '$club_acp_code' and 
            event.region_id=region.id and 
            s1.id=event.start_state_id and 
            s2.id=region.state_id  and
            tz.id=region.event_timezone_id
            order by region_state, region_name, start_datetime";
        $q = $this->query($sql);
        return $q->getRowArray();
    }

    public function getEventsForClub($club_acp_code)
    {

        $sql =
            "SELECT event.id as event_id, event.*, region.*, 
            s1.code as start_state, s2.code as region_state,
            tz.name as timezone_name
            from event, region, state as s1, state as s2, tz
            WHERE event.region_id=region.id and 
            s1.id=event.start_state_id and 
            s2.id=region.state_id  and
            tz.id=region.event_timezone_id and
            event.region_id = " . $this->escape($club_acp_code) . "
            order by start_datetime";
        $q = $this->query($sql);
        return $q->getResultArray();
    }

    public function getEventCode($event)
    {
        $local_event_id = $event['event_id'];
		$club_acp_code = $event['region_id'];
        $event_code = "$club_acp_code-$local_event_id";
        return $event_code;
    }

    public function parseEventCode($event_code)
    {
        if ($event_code === null) throw new \Exception("MISSING PARAMETER");

        if (0 == preg_match('/^(\d+)-(\d+)$/', $event_code, $m)) {
            throw new \Exception('INVALID EVENT ID');
        }

        list($all, $club_acp_code, $local_event_id) = $m;

        return compact('club_acp_code', 'local_event_id');
    }


    public function eventByCode($event_code)
    {

        extract($this->parseEventCode($event_code));


        $event = $this->getEvent($club_acp_code, $local_event_id);
        if (empty($event)) {
            throw new \Exception("NO SUCH EVENT");
        }

        return $event;
    }

    public function set_cuesheet_version($event_code, $cue_version)
    {

        $event = $this->eventByCode($event_code);

        $event_id = $event['event_id'];

        $this->update($event_id, ['cue_version' => $cue_version])  or throw new \Exception('Could not set cuesheet version');
    }



    private function duration($distance_km){
        $speed_limits=[
            ['d'=>200, 'min'=>15, 'max'=>34],
            ['d'=>400, 'min'=>15, 'max'=>32],
            ['d'=>600, 'min'=>15, 'max'=>30],
            ['d'=>1000, 'min'=>11.428, 'max'=>28],
            ['d'=>1300, 'min'=>13.333, 'max'=>26],
            ['d'=>1E99, 'min'=>200/24, 'max'=>30]
        ];
        $prev=0;
        $close=0;
        foreach($speed_limits as $limit){
            $segment=min($distance_km,$limit['d']) - $prev;
            $close+=($segment)/$limit['min'];
            $prev+=$segment;
            if($distance_km<$limit['d']) break;
        }

        $minutes = floor($close * 60);

        return new \DateInterval("PT" . $minutes . "M");
    }

    public function isUnderwayQ($event){

        extract($event);
        $startDatetime = new \DateTime($start_datetime, new \DateTimeZone($timezone_name));
        $now = new \DateTime();
        if ($startDatetime > $now) return false;
        $cutoff_interval = $this->duration($distance);
        $startDatetime->add($cutoff_interval);
        if ($startDatetime < $now) return false;
        return true;

    }

    public function getCutoffDatetime($event){
        extract($event);
        $startDatetime = new \DateTime($start_datetime, new \DateTimeZone($timezone_name));
        $cutoff_interval = $this->duration($distance);
        return $startDatetime->add($cutoff_interval);
    }

	public function statusQ($event,$attribute){

 		if(is_array($attribute)){
			$attribs=implode('|',$attribute);
			return (preg_match('/' . $attribs . '/', $event['status']) === 1);
		}else{
			return (strpos($event['status'], $attribute)!== false);
		}
  }

}
