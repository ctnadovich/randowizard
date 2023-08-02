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


namespace App\Libraries;


class Controletimes {

    public $valid_event_types = ['acp'=>true, 'rusa'=>true, 'permanent'=>true];
    
    public $gravel_allowance = 1; // minute per KM

	public $standard_closing_interval=[  // standard distances and cutoff times (in hours) for events
			'rm'=>['200'=>'PT13H30M','300'=>'PT20H','400'=>'PT27H','600'=>'PT40H','1000'=>'PT75H','1200'=>'PT90H'],
			'acp'=>['200'=>'PT13H30M','300'=>'PT20H','400'=>'PT27H','600'=>'PT40H','1000'=>'PT75H','1200'=>'PT90H'],
			'rusa'=>['200'=>'PT13H30M','300'=>'PT20H','400'=>'PT27H','600'=>'PT40H','1000'=>'PT75H','1200'=>'PT90H'],
		];
		
	public $controle_speed_limits=[
			'rm'=>[
				['d'=>60, 'min'=>20, 'max'=>34, 'close_add'=>1],  // use 'new' method for contoles <60 km
				['d'=>200, 'min'=>15, 'max'=>34],
				['d'=>400, 'min'=>15, 'max'=>32],
				['d'=>600, 'min'=>15, 'max'=>30],
				['d'=>1000, 'min'=>11.428, 'max'=>28],
				['d'=>1300, 'min'=>13.333, 'max'=>26]
			],
			'acp'=>[
				['d'=>60, 'min'=>20, 'max'=>34, 'close_add'=>1],  // use 'new' method for contoles <60 km
				['d'=>200, 'min'=>15, 'max'=>34],
				['d'=>400, 'min'=>15, 'max'=>32],
				['d'=>600, 'min'=>15, 'max'=>30],
				['d'=>1000, 'min'=>11.428, 'max'=>28],
				['d'=>1300, 'min'=>13.333, 'max'=>26]
			],
			'rusa'=>[
				['d'=>60, 'min'=>20, 'max'=>34, 'close_add'=>1],  // use 'new' method for contoles <60 km
				['d'=>200, 'min'=>15, 'max'=>34],
				['d'=>400, 'min'=>15, 'max'=>32],
				['d'=>600, 'min'=>15, 'max'=>30],
				['d'=>1000, 'min'=>11.428, 'max'=>28],
				['d'=>1300, 'min'=>13.333, 'max'=>26]
			],
			'permanent'=>[
				['d'=>700, 'min'=>15, 'max'=>30],
				['d'=>1300, 'min'=>13.3, 'max'=>30],
				['d'=>1900, 'min'=>12, 'max'=>30],
				['d'=>2500, 'min'=>10, 'max'=>30],
				['d'=>1E99, 'min'=>200/24, 'max'=>30]
			],
			'unknown'=>[
				['d'=>700, 'min'=>15, 'max'=>30],
				['d'=>1300, 'min'=>13.3, 'max'=>30],
				['d'=>1900, 'min'=>12, 'max'=>30],
				['d'=>2500, 'min'=>10, 'max'=>30],
				['d'=>1E99, 'min'=>200/24, 'max'=>30]
			]
		];
		
	// Standard formats for Date and Time
 	public $event_datetime_format='Y-m-d H:i T';
	public $event_datetime_format_verbose='l j F Y, H:i T';

	public $default_event_date="2000-01-01";
	public $default_event_time="00:00";
	
	// RUSA-isms
	
	public $round_minutes = true;
	public $floor_km = true;

	
// 	public function open_close(DateTime $start_datetime, $distance_km, $event_type, $final_controle_cutoff=0, $tz=null){
	private function open_close(\DateTime $start_datetime, $distance_km, $event_type, $tz=null){
	
// 		switch($event_type){
// 			case 'permanent':
// 				$limit_type='permanent';
// 				break;
// 			default:
// 				$limit_type='acp';
// 				break;
// 			
// 		}
	
		if(!isset($this->controle_speed_limits[$event_type]) )
			trigger_error("Unknown event type '$event_type'; cannot compute controle times.");
		
		$limits=$this->controle_speed_limits[$event_type];
		
		if($this->floor_km) $distance_km = floor($distance_km);
		
		// Compute controle open/close DateTimes

		$open=0;
		if($distance_km<1){
			$close=1;
		}else{
			$prev=0;
			$close=0;
			foreach($limits as $limit){
				$close_add = (isset($limit['close_add']))?$limit['close_add']:0;
				$segment=min($distance_km,$limit['d']) - $prev;
				$open+=($segment)/$limit['max'];
				$close+=($segment)/$limit['min'] + $close_add;
				$prev+=$segment;
				if($distance_km<$limit['d']) break;
			}
//			if($final_controle_cutoff>0){
//				$close=$final_controle_cutoff; // hours
//			}
		}
		

		$h=floor($open);
		if($this->round_minutes)
			$m=round(($open-$h)*60);
		else
			$m=floor(($open-$h)*60);
		
		$open_interval = new \DateInterval("PT{$h}H{$m}M");
		
		$h=floor($close);
		if($this->round_minutes)
			$m=round(($close-$h)*60);
		else
			$m=floor(($close-$h)*60);
			
		$close_interval = new \DateInterval("PT{$h}H{$m}M");

		$open_datetime = clone($start_datetime); 
		$open_datetime->add($open_interval); 	
		
		$close_datetime=clone($start_datetime);
		$close_datetime->add($close_interval); 
		
		if($tz !== null){
			$open_datetime->setTimezone($tz);
			$close_datetime->setTimezone($tz);
		}
		
		return [$open_datetime, $close_datetime];
	}
	
	public function make_event_datetime($route_event){
		if(empty($route_event['event_date']) || empty($route_event['event_time']) || empty($route_event['event_tz'])){
			return null;
		}
		return @date_create($route_event['event_date'].' '.$route_event['event_time'], $route_event['event_tz']);
	}
	
	public $km_places = 6;  // Set the precision of 
	
	public function compute_open_close(&$controles,$route_event){

		extract($route_event);

		if(empty($event_datetime)){
			$route_event['event_datetime'] = $event_datetime = $this->make_event_datetime($route_event);
		}
		
		if(!isset($event_gravel_distance)) trigger_error("Event gravel distance not specified. Can't compute control times.",E_USER_ERROR);
		if(empty($event_datetime)) trigger_error("Event date, time, or timezone not specified. Can't compute control times.",E_USER_ERROR);
		if(empty($event_distance)) trigger_error("Event official distance not specified. Can't compute control times.",E_USER_ERROR);
		
		
		for($i=0; $i<count($controles); $i++){ // Compute controle open/close times
		
			
			// $cd_km=round($controles[$i]['d']/(rwgps::m_per_km),$this->km_places);
			$cd_km=($controles[$i]['d']/(units::m_per_km));

			// SPECIAL CONSIDERATION FOR FINISH CONTROL
			if(isset($controles[$i]['finish'])){ 
			  if(!empty($event_distance)){
			  	// OFFICIAL DISTANCE CLOSING OVERRIDE
			  	$cd_km=$event_distance;
			  }
			}
			
			$tz=(isset($controle[$i]['tz']))?$controle[$i]['tz']:$route_event['event_tz'];

			$oc=$this->open_close($event_datetime, $cd_km, $event_type, $tz);
			$controles[$i]['open']=$oc[0];
			
			
			if(isset($controles[$i]['finish']) &&
			   isset($this->standard_closing_interval[$event_type][$event_distance])){

				$interval = $this->standard_closing_interval[$event_type][$event_distance];
				$close_interval = new \DateInterval($interval);		
				$close_datetime=clone($event_datetime);
				$close_datetime->add($close_interval); 
		
				if($tz !== null){
					$close_datetime->setTimezone($tz);
				}
				$controles[$i]['close']=$close_datetime;

			}else{
				$controles[$i]['close']=$oc[1];
			}
			
			// Consideration of gravel events
			if(isset($controles[$i]['finish']) && $event_gravel_distance>0){
			    $additional_time = round($event_gravel_distance * $this->gravel_allowance);
				$controles[$i]['close']->add(new \DateInterval("PT{$additional_time}M"));
			}
			
			
		}
	}
}

?>
