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

class Checkin extends Model
{
	protected $table      = 'checkin';
	protected $primaryKey = 'id';
	protected $returnType     = 'array';
	protected $allowedFields = ['rider_id', 'event_id', 'control_number', 'time', 'preride', 'comment', 'notes'];

	public function get_checkin($local_event_id, $rider_id, $control_index, $timezone_name = 'utc'){
		$this->where([
			'event_id' => $local_event_id,
			'rider_id' => $rider_id,
			'control_number' => $control_index
		]);

		$checkin = $this->first();

		if(empty($checkin)) return null;

		$checkin_time = new \DateTime($checkin['time'], new \DateTimeZone('utc'));
		if($timezone_name != 'utc') $checkin_time->setTimezone(new \DateTimeZone($timezone_name));

		$checkin['checkin_time']=$checkin_time;

		return $checkin;

	}

	public function riders_seen($local_event_id){
		$this->distinct();
		$this->select('rider_id');
		$this->where([
			'event_id' => $local_event_id
		]);
		return array_map( function ($r){return reset($r);} ,$this->findAll());  // remove array encapsulation of rider_id

	}

	public function record(
		$local_event_id,
		$rider_id,
		$checkin_control_index,
		$check_in_times,
		$check_in_comments,
		$preride,
		$current_comment = '',
		$notes = []
	) {
		if (!is_array($check_in_times)) throw new \Error('CHECK_IN_TIMES NOT ARRAY');

		foreach ($check_in_times as $control_index => $checkin_time) {

			$comment='';
			if($checkin_control_index == $control_index)
			  $comment = $current_comment;
			if(!empty($check_in_comments[$control_index])) 
			  $comment=$check_in_comments[$control_index];
  

			$this->where([
				'rider_id' => $rider_id,
				'event_id' => $local_event_id,
				'control_number' => $control_index
			]);

			// See if there was a previous checkin
			// if NOT, then insert this one
			if (empty($this->first())) {
				$data = [
					'rider_id' => $rider_id,
					'event_id' => $local_event_id,
					'control_number' => $control_index,
					'time' => $checkin_time,
					'preride' => $preride,
					'comment' => $comment,
					'notes' => json_encode($notes)
				];

				$result = $this->insert($data, false);
				if (false === $result) throw new \Exception('FAILED TO SAVE CHECK IN');
			}
		}
	}
}
