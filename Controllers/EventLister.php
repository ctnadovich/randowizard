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

class EventLister extends EventProcessor
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
	// FUTURE EVENTS (for eBrevet JSON)
	//

	private $secret;
	private $club_acp_code;
	private $nonce;

	public function json_future_events($club_acp_code = null, $nonce = null)
	{
		$this->json_list_events('future', $club_acp_code, $nonce);
	}

	public function json_past_events($club_acp_code = null, $nonce = null)
	{
		$this->json_list_events('past', $club_acp_code, $nonce);
	}

	public function json_all_events($club_acp_code = null, $nonce = null)
	{
		$this->json_list_events('all', $club_acp_code, $nonce);
	}


	public function json_list_events($filter = 'future', $club_acp_code = null, $nonce = null)
	{

		$this->club_acp_code = $club_acp_code;
		if ($nonce === null) {
			$this->nonce = $nonce = 'nonoce';
		} else {
			$this->nonce = $nonce;
		}

		$event_errors = [];
		$event_list = [];

		// Must specify a valid club

		if (empty($club_acp_code) || !is_numeric($club_acp_code)) {
			$this->die_json('Invalid parameter.');
		}

		$club = $this->regionModel->getClub($club_acp_code);
		if (empty($club)) {
			$this->die_json("No such region: $club_acp_code");
		}

		$this->secret = $secret = $club['epp_secret'];


		// Process events for club

		$all_events = $this->eventModel->getEventsForClub($club_acp_code);

		foreach ($all_events as $event) {

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
				$event_data = $this->get_event_data($event);
			} catch (\Exception $e) {
				$status = $e->GetMessage();
				$event_errors[] = $status;
				continue;
			}


			if($filter == 'future'){
					$cutoff_datetime = $event_data['cutoff_datetime'];
					$grace_duration_hours = 12; // future is defined as events closing no more than this many hours ago
					$grace_duration = new \DateInterval("PT{$grace_duration_hours}H"); // 12 hour grace time
					$cutoff_datetime->add($grace_duration);
					$now = new \DateTime('now');
					if ($now >= $cutoff_datetime) continue;  // this was a past event

			}elseif($filter=='past'){
					$cutoff_datetime = $event_data['cutoff_datetime'];
					$grace_duration_hours = 12; // future is defined as events closing no more than this many hours ago
					$grace_duration = new \DateInterval("PT{$grace_duration_hours}H"); // 12 hour grace time
					$cutoff_datetime->add($grace_duration);
					$now = new \DateTime('now');
					if ($now <= $cutoff_datetime) continue;  // this was a future (or ongoing) event
			}else{ /* include all events */ }
			


			$event_list[] = $this->published_edata($event_data);
			$event_id_code = $event_data['event_code'];

			$control_warnings = $event_data['controle_warnings'] ?? [];
			$cue_warnings = $event_data['cue_warnings'] ?? [];
			$other_warnings = $event_data['other_warnings'] ?? [];

			$nWarnings = sizeof($control_warnings) + sizeof($cue_warnings) + sizeof($other_warnings);
			$s = $nWarnings > 1 ? 's' : '';

			if ($nWarnings > 0) {
				$event_errors[] = "$nWarnings warning$s in Event ID=$event_id_code";
			}
		}

		$minimum_app_version = $this->minimum_app_version;

		$payload = compact('club_acp_code', 'nonce', 'minimum_app_version', 'event_list', 'event_errors');
		$signature = $this->sign_json($payload, $this->secret);
		$payload['signature'] = $signature;

		$this->emit_json($payload);
	}

	private function die_json($message)
	{
		$event_errors = [$message];
		$event_list = [];
		$minimum_app_version = $this->minimum_app_version;
		$nonce = $this->nonce;
		$club_acp_code = $this->club_acp_code;

		$payload = compact('club_acp_code', 'nonce', 'minimum_app_version', 'event_list', 'event_errors');
		$signature = $this->sign_json($payload, $this->secret);
		$payload['signature'] = $signature;

		$this->emit_json($payload);
	}

	private function sign_json($data, $secret)
	{
		// Encode JSON with no spaces to ensure consistency
		$json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		// Generate HMAC using SHA-256
		$hmac = hash_hmac('sha256', $json, $secret, true); // true = raw binary output

		// Return base64-encoded signature
		return base64_encode($hmac);
	}

	private function make_signature($event_list)
	{
		return $this->sign_json($event_list, $this->secret);
	}

	private function make_signature_old($event_list)
	{  // missing parms?
		$event_list_hash = hash('sha256', json_encode($event_list));
		$minimum_app_version = $this->minimum_app_version;
		$plaintext = $this->club_acp_code . '-' . $this->nonce . '-' . $this->minimum_app_version . '-' .
			$event_list_hash . '-' . $this->secret;
		return hash('sha256', $plaintext);
	}

	////////////////////////////////////////////////////////////
	// 
	// Regional Events
	//

	public function regional_events($club_acp_code = null)
	{

		if (empty($club_acp_code) || !is_numeric($club_acp_code)) {
			$this->die_message_notrace('Error', 'Missing or invalid ACP Club Code parameter.');
		}

		$club = $this->regionModel->getClub($club_acp_code);
		if ($club == null) {
			$this->die_message_notrace('Error', 'Unknown ACP Club.');
		}

		$this->viewData = array_merge($this->viewData, $club);
		$this->viewData['title'] = $club['club_name'] . ' Events';

		$all_events = $this->eventModel->getEventsForClub($club_acp_code);

		$this->viewData['future_events_table'] = $this->make_event_table($club, $all_events, 'future');
		$this->viewData['past_events_table'] = $this->make_event_table($club, $all_events, 'past');
		$this->viewData['underway_events_table'] = $this->make_event_table($club, $all_events, 'underway');

		return $this->load_view('regional_events', $club_acp_code);
	}

	public function eventful_regions()
	{

		try {
			$er_array = $this->regionModel->hasEvents();

			$this->viewData['eventful_regions'] = $er_array;

			return $this->load_view('eventful_regions');
		} catch (\Exception $e) {
			$this->die_exception($e);
		}
	}


	private function make_event_table($club, $all_events = [], $timerange = 'all')
	{

		$headings = [
			'Date',
			'Sanction',
			'Event',
			'Info',
			'Results',
			''
		];

		$year = null;
		$rows = [];
		foreach ($all_events as $event) {

			if ($this->eventModel->statusQ($event, 'hidden')) continue;

			extract($event);
			$isUnderway = $this->eventModel->isUnderwayQ($event);
			$startDatetime = (new \DateTime($start_datetime, $club['event_timezone']));
			$now = new \DateTime();
			if ($timerange == 'future' && $startDatetime < $now) continue;
			if ($timerange == 'past' && ($startDatetime > $now || $isUnderway)) continue;
			if ($timerange == 'underway' && !$isUnderway) continue;

			if ($this->eventModel->statusQ($event, 'canceled')) {
				$status = "CANCELED";
				$status_style = 'w3-text-gray';
			} elseif ($this->eventModel->statusQ($event, 'suspended')) {
				$status = "SUSPENDED";
				$status_style = 'w3-text-gray';
			} elseif ($isUnderway) {
				$status = "<span class='w3-deep-orange w3-text-white w3-padding w3-margin-top'>UNDERWAY!</span>";
				$status_style = '';
			} else {
				$status = '';
				$status_style = '';
			}

			$start_year = $startDatetime->format('Y');

			if ($start_year != $year) {
				$rows[] = "<TR class='w3-gray'><TH COLSPAN=6>$start_year</TH></TR>";
				$rows[] = "<TR><TH>" . implode('</TH><TH>', $headings) . "</TH></TR>";

				$year = $start_year;
			}

			$no_route = empty($route_url);

			$sdtxt = $startDatetime->format("M j @ H:i T");
			$event_code = $this->eventModel->getEventCode($event);
			$infolink = $no_route ? "<i class='fa fa-circle-info' style='color: lightgray;'></i>" :
				"<A class='w3-button' TITLE='Info' HREF='" . site_url("event_info/$event_code") . "'><i class='fa fa-circle-info'></i></a>";
			$resultslink = $no_route ? "<i class='fa fa-users' style='color: lightgray;'></i>" :
				"<A class='w3-button' TITLE='Riders/Results' HREF='" . site_url("roster_info/$event_code") . "'><i class='fa fa-users'></a>";
			$row = [$sdtxt,  "$sanction", "$name $distance K", $infolink,  $resultslink, $status];

			$rows[] = "<TR class='$status_style'><TD>" . implode('</TD><TD>', $row) . "</TD></TR>";
		}

		if (count($rows) == 0) {
			return null;
		} else {
			$event_table  = "<table class='w3-table-all w3-centered'>";
			$event_table .= implode('', $rows);
			$event_table .= "</table>";
			return $event_table;
		}
	}
}
