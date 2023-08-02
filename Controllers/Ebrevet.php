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

namespace App\Controllers;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Ebrevet extends BaseController
{
	protected $eventModel;
	protected $regionModel;
	protected $rwgpsLibrary;
	protected $controletimesLibrary;
	protected $unitsLibrary;

	public function initController(
		RequestInterface $request,
		ResponseInterface $response,
		LoggerInterface $logger
	) {
		parent::initController($request, $response, $logger);

		$this->eventModel = model('Event');
		$this->regionModel = model('Region');

		// $this->load->library('route_event');
		// $this->load->library('rwgps');

		$this->unitsLibrary = new \App\Libraries\Units();
		$this->rwgpsLibrary = new \App\Libraries\Rwgps();
		$this->controletimesLibrary = new \App\Libraries\Controletimes();

		// $this->load->library('controletimes');
	}


	// Published in future_event data, and used to 
	// reject checkins

	public $minimum_app_version = '1.2.4';  



	////////////////////////////////////////////////////////////
	// 
	// FUTURE EVENTS
	//

	public function future_events($club_acp_code = null)
	{

		$event_errors = [];
		$event_list = [];

		// Must specify a valid club

		if (empty($club_acp_code) || !is_numeric($club_acp_code)) {
			$this->die_message('Error', 'Invalid parameter.');
		}

		$club = $this->regionModel->getClub($club_acp_code);

		if (empty($club)) {
			$this->die_message("Not Found", "Club ACP code $club_acp_code not found");
		}

		// Process events for club

		$future_events = $this->eventModel->getEventsForClub($club_acp_code);


		foreach ($future_events as $event) {

			// Create globally unique event ID
			$local_event_id = $event['id'];
			$event_id = "$club_acp_code-$local_event_id";

			// Skip if hidden or canceled
			$event_status = $event['status'];
			if ($event_status == 'hidden' || $event_status == 'canceled') continue;

			// Skip if no route mape
			if (empty($event['route_url'])) {
				$event_errors[] = "No route map URL, skipped";
				continue;
			}

			// Skip if no start time
			if (empty($event['start_datetime'])) {
				$event_errors[] = "No start Date and Time for Event ID=$event_id, skipped";
				continue;
			}

			// Try to get route data
			$route_id = $this->rwgpsLibrary->extract_route_id($event['route_url']);



			if ($route_id == null) {
				$event_errors[] = "No RWGPS map for Event $local_event_id, skipped";
				continue;  // will only consider RWGPS maps
			} else {
				$route = $this->rwgpsLibrary->get_route($route_id);
			}

			// Figure out time zone
			$event_timezone_name = $club['event_timezone_name'];  // For now, events can't have individual TZ
			$event_tz = new \DateTimeZone($event_timezone_name);

			// Figure out start time
			$start_datetime_str = $event['start_datetime'];
			$event_datetime = @date_create($start_datetime_str, $event_tz);
			if (false == $event_datetime) {
				$event_errors[] = "Invalid Date and Time for Event ID=$event_id, skipped";
				continue;
			}
			$utc_tz = new \DateTimeZone('UTC');
			$event_datetime->SetTimezone($utc_tz);
			$start_datetime_utc = $event_datetime->format('c'); // Y-m-d H:i T';);
			$start_time_window = [
				'on_time' => $start_datetime_utc,
				'start_style' => 'massStart'
			];

			list($controles, $controle_warnings) = $this->rwgpsLibrary->extract_controles($route);

			$event_distance = $event['distance'];
			$event_gravel_distance = $event['gravel_distance'];
			$event_type = strtolower($event['sanction']);
			$route_event = compact('event_datetime', 'event_type', 'event_distance', 'event_gravel_distance', 'event_tz');

			$this->controletimesLibrary->compute_open_close($controles, $route_event);

			if (sizeof($controles) == 0) {
				$event_errors[] = "No controls for Event $local_event_id, skipped";
				continue;
			}

			if (sizeof($controle_warnings) > 0) {
				$event_errors[] = "Errors or warnings in controls for Event $local_event_id, skipped";
				continue;
			}


			$controls = [];
			foreach ($controles as $cdata) {

				$a = $cdata['attributes'];

				//	$sif=(array_key_exists('start', $cdata))?'start':
				//		((array_key_exists('finish', $cdata))?'finish':'intermediate');

				if (empty($cdata['open']) || empty($cdata['close']))
					$this->die_message(__METHOD__, "Controls for event $local_event_id: " . print_r($controles, true));

				$openDatetime = $cdata['open'];
				$closeDatetime = $cdata['close'];
				$openDatetime->setTimezone($utc_tz);
				$closeDatetime->setTimezone($utc_tz);

				$reclass = $this->unitsLibrary;

				$cd_mi = round($cdata['d'] / ($reclass::m_per_km * $reclass::km_per_mi), 1);
				$cd_km = round($cdata['d'] / ($reclass::m_per_km), 1);

				$question = (array_key_exists('question', $a)) ? $a['question'] : '';

				$controls[] = [
					'dist_mi' => $cd_mi,
					// 'dist_km'=>$cd_km,
					'long' => $cdata['x'],
					'lat' => $cdata['y'],
					'name' => $a['name'],
					'style' => $a['style'],
					'address' => $a['address'],
					'open' => $openDatetime->format('c'),
					'close' => $closeDatetime->format('c')
					// 'question'=>$question,
					// 'sif'=>$sif

				];
			}

			$name = $event['name'];
			$distance = $event['distance'];
			$gravel_distance = $event['gravel_distance'];
			$sanction = $event['sanction'];
			$type = $event['type'];
			$start_city = $event['start_city'];
			$start_state = $event['start_state'];
			$cue_version = $event['cue_version'];
			$checkin_post_url = site_url("/ebrevet/post_checkin/$club_acp_code");
			$event_info_url = $event['info_url'];
			$organizer_name = $event['emergency_contact'];
			$organizer_phone = $event['emergency_phone'];


			$published_edata = compact(
				'event_id',
				'name',
				'distance',
				'gravel_distance',
				'sanction',
				'type',
				'start_city',
				'start_state',
				'cue_version',
				// 'club_name',
				'club_acp_code',
				// 'region','club_logo_url',
				// 'club_website_url',
				'checkin_post_url',
				'event_info_url',
				// 'event_tzname',
				'organizer_name',
				'organizer_phone',
				'start_time_window',
				'controls'
			);

			$event_list[] = $published_edata;
		}

		$minimum_app_version = $this->minimum_app_version;

		$this->emit_json(compact('minimum_app_version', 'event_list', 'event_errors'));
	}

	private function emit_json($data)
	{
		$j = json_encode($data);
		header("Content-Type: application/json; charset=UTF-8");
		echo $j;
		exit();
	}
}
