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
    protected $allowedFields = ['event_id','rider_id','result','elapsed_time','comment'];

    public function n_riders($local_event_id){
        $this->where([
            'event_id' => $local_event_id
        ]);
        return count($this->findAll());// $this->countAllResults();
    }

    public function registered_riders($local_event_id){
        $this->where([
            'event_id' => $local_event_id
        ]);
        return $this->findAll();
    }

    public function get_record($local_event_id, $rider_id){
        $this->where([
            'rider_id' => $rider_id,
            'event_id' => $local_event_id
        ]);
        $this->select(['result']);
        return $this->first();
    }

    public function record_finish($local_event_id, $rider_id, $elapsed_time){
        $this->where([
            'rider_id' => $rider_id,
            'event_id' => $local_event_id
        ]);

        $data=['result'=>'finish', 'elapsed_time'=>$elapsed_time];

        $this->update($data);

    }

    public function upsert_result($local_event_id, $rider_id, $result){
        $this->where([
            'rider_id' => $rider_id,
            'event_id' => $local_event_id
        ]);

        $data=['result'=>$result];

        $this->upsert($data);

    }



}