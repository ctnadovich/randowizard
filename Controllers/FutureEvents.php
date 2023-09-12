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
use DateTimeZone;
use Psr\Log\LoggerInterface;

class FutureEvents extends EventProcessor
{
	

	public function initController(
		RequestInterface $request,
		ResponseInterface $response,
		LoggerInterface $logger
	) {
		parent::initController($request, $response, $logger);

		
	}


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

		// $club = $this->regionModel->getClub($club_acp_code);

		// if (empty($club)) {
		// 	$this->die_message("Not Found", "Club ACP code $club_acp_code not found");
		// }

		// Process events for club

		$future_events = $this->eventModel->getEventsForClub($club_acp_code);


		foreach ($future_events as $event) {

			$event_status = $event['status'];
			if ($event_status == 'hidden' || $event_status == 'canceled') continue;

			$event_code = $event['region_id'] . "-" . $event['id'];

			// Skip if no route mape
			if (empty($event['route_url'])) {
				$event_errors[] = "No route map URL for Event ID=$event_code, skipped";
				continue;
			}

			// Skip if no start time
			if (empty($event['start_datetime'])) {
				$event_errors[] = "No start Date and Time for Event ID=$event_code, skipped";
				continue;
			}


			try {
				$event_data = $this->get_event_data($event_code);
			} catch (\Exception $e) {
				$status = $e->GetMessage();
				$event_errors[] = $status;
				continue;
			}

			$event_list[] = $this->published_edata($event_data);
		}

		$minimum_app_version = $this->minimum_app_version;

		$this->emit_json(compact('minimum_app_version', 'event_list', 'event_errors'));
	}


}