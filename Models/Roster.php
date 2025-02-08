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

class Roster extends Model
{
    protected $table      = 'roster';
    protected $primaryKey = 'id';
    protected $returnType     = 'array';
    protected $allowedFields = ['event_id','rider_id','first_name','last_name','result','elapsed_time','comment'];

    public function n_riders($local_event_id){
        $this->where([
            'event_id' => $local_event_id
        ]);
        return count($this->findAll());// $this->countAllResults();
    }

    public function registered_riders($local_event_id, $is_rusa = false){
        
        if($is_rusa) return $this->registered_rusa_riders($local_event_id);

        $this->where([
            'event_id' => $local_event_id
        ]);

        $this->orderBy('last_name');
        $this->orderBy('first_name');
        return $this->findAll();
    }

    private function registered_rusa_riders($local_event_id){
        $this->select('roster.id, roster.event_id, roster.rider_id, roster.result, roster.elapsed_time, roster.comment, roster.created, roster.last_change,
        roster.first_name as roster_first_name, roster.last_name as roster_last_name,
        rusa.first_name as first_name, rusa.last_name as last_name
        ');
        $this->where([
            'event_id' => $local_event_id
        ]);
        $this->join('rusa','rusa.rusa_id = roster.rider_id');
        $this->orderBy('rusa.last_name');
        $this->orderBy('rusa.first_name');
        return $this->findAll();
    }

    public function get_result($local_event_id, $rider_id){
        return $this->get_record($local_event_id, $rider_id);
    }

    public function get_record($local_event_id, $rider_id){
        $this->where([
            'rider_id' => $rider_id,
            'event_id' => $local_event_id
        ]);
        $this->select(['result','elapsed_time']);
        return $this->first();
    }

    public function record_finish($local_event_id, $rider_id, $elapsed_time){
        $this->where('rider_id', $rider_id);
        $this->where('event_id', $local_event_id);

        // throw new \Exception(__METHOD__ . "  $local_event_id, $rider_id, $elapsed_time");


        $this->set('result','finish');
        $this->set('elapsed_time',$elapsed_time);

        $this->update();

    }

    public function upsert_result($local_event_id, $rider_id, $result){
        $data=[
            'rider_id' => $rider_id,
            'event_id' => $local_event_id,
            'result'=>$result
        ];

        $this->upsert($data);

    }

    public function get_rusa_results($local_event_id){
		$sql="SELECT IF(rusa.rusa_id>0,rusa.rusa_id,'') as '#RUSA#',rusa.first_name as 'FirstName',rusa.last_name as 'LastName',
        HOUR(elapsed_time) as 'Hours', MINUTE(elapsed_time) as 'Minutes', 
        IF(result='FINISH',0,1) as 'DNF', 
        IF(country<>'US',1,0) as 'Foreign' FROM roster,rusa where 
        roster.rider_id=rusa.rusa_id AND
        roster.event_id='$local_event_id' AND 
        (result IS NULL OR (result<>'DNS' AND result<>'VOL'))
        ORDER BY rusa.last_name,rusa.first_name";
		$q=$this->query($sql);
		return $q->getResultArray();
	}



}