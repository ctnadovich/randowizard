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

class Ebrevet extends BaseController
{
	protected $eventModel;
	protected $regionModel;
	protected $checkinModel;
	protected $rosterModel;

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
		$this->checkinModel = model('Checkin');
		$this->rosterModel = model('Roster');

		$this->unitsLibrary = new \App\Libraries\Units();
		$this->rwgpsLibrary = new \App\Libraries\Rwgps();
		$this->controletimesLibrary = new \App\Libraries\Controletimes();
	}

	public $minimum_app_version = '1.2.4';


	////////////////////////////////////////////////////////////
	// 
	// CHECK IN
	//

	public function post_checkin($club_acp_code = null)
	{

		$status = 'OK';
		$rider_id = '?';
		$event_code = '?';

		try {

			$club = $this->regionModel->getClub($club_acp_code);
			if (empty($club)) {
				throw new \Exception("NO SUCH CLUB");
			}

			$d = $this->request->getJSON(true);

			if (empty($d)) throw new \Exception('NO DATA');

			if (
				empty($d['event_id']) || empty($d['app_version']) ||
				sizeof(explode('-', $d['event_id'])) != 2 ||
				empty($d['rider_id']) ||
				empty($d['start_style']) ||
				empty($d['timestamp']) ||
				empty($d['signature'])
			) {
				throw new \Exception('MISSING PARAMETER');
			}


			$app_version = $d['app_version'];
			$event_code = $d['event_id'];  // Global Event identifier Club-LEI
			$rider_id = $d['rider_id'];
			$timestamp = $d['timestamp'];   // TODO compare with check in time
			$signature = $d['signature'];
			$start_style = $d['start_style'];

			list($a, $b, $c) = explode('.', $app_version);
			list($ar, $br, $cr) = explode('.', $this->minimum_app_version);

			if ($a + 0 < $ar + 0 || $b + 0 < $br + 0 || $c + 0 < $cr + 0)
				throw new \Exception("UPDATE APP. SERVER NEEDS v{$this->minimum_app_version}");

			$correct_signature = $this->make_signature($d, $club['epp_secret']);
			if ($signature != $correct_signature) throw new \Exception('INVALID DATA');

			if (0 == preg_match('/^(\d+)-(\d+)$/', $event_code, $m)) {
				throw new \Exception('INVALID EVENT ID');
			}

			list($all, $json_club_code, $local_event_id) = $m;
			// list($json_club_code, $json_local_event_id) = explode('-', $json_event_id);

			if ($club_acp_code != $json_club_code) throw new \Exception('INCONSISTENT PARAMETERS');

			// The local event ID is the primary key into events. As such, 
			// the club code is an unecessary constraint. That being said, 
			// if the club code isn't correct for the selected event, the
			// overconstrained search will reveal this. 

			$event = $this->eventModel->getEvent($club_acp_code, $local_event_id);

			if (empty($event)) {
				throw new \Exception("NO SUCH EVENT");
			}

			//  Maybe someday have checkins at places other than controls
			//
			// 			if(empty($d['control_index'])){
			// 				$notes[]="Check In between controls.";
			// 			}

			$preride = ($start_style == "preRide");

			if (key_exists('comment', $d) && !empty($d['comment']))
				$comment = $d['comment'];
			else
				$comment = "";

			if (key_exists('outcome', $d)) $outcome = $d['outcome'];

			if (
				isset($outcome) && is_array($outcome) &&
				array_key_exists('overall_outcome', $outcome) &&
				array_key_exists('check_in_times', $outcome) &&
				is_array($outcome['check_in_times'])
			) {
				$overall_outcome = $outcome['overall_outcome'];
				$check_in_times = $outcome['check_in_times'];

				$this->checkinModel->record(
					$local_event_id,
					$rider_id,
					$check_in_times,
					($preride) ? 1 : 0,
					$comment,
					$d
				);

				$result = $this->rosterModel->get_result($local_event_id, $rider_id);

				if ($result != 'finish' ) {  // roster finish is immutable
					if ($overall_outcome == 'finish') {
							if (array_key_exists('finish_elapsed_time', $d))
								$this->rosterModel->record_finish($local_event_id, $rider_id, $d['finish_elapsed_time']);
					}else{
							$this->rosterModel->upsert_result($local_event_id, $rider_id, $overall_outcome);
					}
				}
			} else {
				throw new \Exception('NO OUTCOMES');
			}
		} catch (\Exception $e) {
			$status = $e->GetMessage();
		}

		$response = ['status' => $status, 'event_id' => $event_code, 'rider_id' => $rider_id];
		$this->emit_json($response);
	}

	private function make_signature($d, $epp_secret)
	{
		extract($d);
		$plaintext = "$timestamp-$event_id-$rider_id-$epp_secret";
		$ciphertext = hash('sha256', $plaintext);
		return strtoupper(substr($ciphertext, 0, 8));
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

		$club = $this->regionModel->getClub($club_acp_code);

		if (empty($club)) {
			$this->die_message("Not Found", "Club ACP code $club_acp_code not found");
		}

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
				$published_edata = $this->process_event($event);
			} catch (\Exception $e) {
				$status = $e->GetMessage();
				$event_errors[] = $status;
				continue;
			}

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


	private function process_event($event)
	{

		// Create globally unique event ID
		$local_event_id = $event['id'];
		$club_acp_code = $event['region_id'];
		$event_id = "$club_acp_code-$local_event_id";

		if (empty($event['route_url'])) throw new \Exception('NO MAP URL FOR ROUTE');
		if (empty($event['start_datetime'])) throw new \Exception('NO START TIME FOR EVENT');

		$club = $this->regionModel->getClub($club_acp_code);
		if (empty($club)) {
			throw new \Exception("UNKNOWN CLUB");
		}
		$epp_secret = $club['epp_secret'];

		// Try to get route data
		$route_id = $this->rwgpsLibrary->extract_route_id($event['route_url']);

		if ($route_id == null) {
			throw new \Exception('NO RWGPS MAP FOR ROUTE');
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
			throw new \Exception("INVALID DATE OR TIME");
		}
		$utc_tz = new \DateTimeZone('UTC');
		$event_datetime->SetTimezone($utc_tz);
		$start_datetime_utc = $event_datetime->format('c'); // Y-m-d H:i T';);
		$start_time_window = [
			'on_time' => $start_datetime_utc,
			'start_style' => 'massStart'   // The only start style supported
		];

		list($controles, $controle_warnings) = $this->rwgpsLibrary->extract_controles($route);

		$event_distance = $event['distance'];
		$event_gravel_distance = $event['gravel_distance'];
		$event_type = strtolower($event['sanction']);
		$route_event = compact('event_datetime', 'event_type', 'event_distance', 'event_gravel_distance', 'event_tz');

		$this->controletimesLibrary->compute_open_close($controles, $route_event);

		if (sizeof($controles) == 0) {
			throw new \Exception("NO CONTROLS");
		}

		if (sizeof($controle_warnings) > 0) {
			throw new \Exception("ERRORS IN CONTROLS");
		}


		$controls = [];
		foreach ($controles as $cdata) {

			$a = $cdata['attributes'];

			//	$sif=(array_key_exists('start', $cdata))?'start':
			//		((array_key_exists('finish', $cdata))?'finish':'intermediate');

			if (empty($cdata['open']) || empty($cdata['close'])) {
				throw new \Exception("OPEN/ClOSE MISSING: " . print_r($controles, true));
			}

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
				'dist_km'=>$cd_km,
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

		return $published_edata;
	}



	////////////////////////////////////////////////
	// EVENT CHECKIN STATUS
	//

	public function checkin_status($event_code = null)
	{

		$checkin_table = '';

		try {

			if ($event_code === null) throw new \Exception("MISSING PARAMETER");

			if (0 == preg_match('/^(\d+)-(\d+)$/', $event_code, $m)) {
				throw new \Exception('INVALID EVENT ID');
			}

			list($all, $club_acp_code, $local_event_id) = $m;

			$club = $this->regionModel->getClub($club_acp_code);
			if (empty($club)) {
				throw new \Exception("UNKNOWN CLUB");
			}
			$epp_secret = $club['epp_secret'];

			$event = $this->eventModel->getEvent($club_acp_code, $local_event_id);
			if (empty($event)) {
				throw new \Exception("NO SUCH EVENT");
			}

			$edata = $this->process_event($event);
		} catch (\Exception $e) {
			$status = $e->GetMessage();

			$this->die_message('ERROR', $status);
		}

		// Establish 'event','route','controles','warnings','cues','route_event'

		$event_name_dist = $edata['name'] . ' ' . $edata['distance'] . 'K';
		$controles = $edata['controls'];
		$ncontroles = count($controles);


		// $this->die_message(__METHOD__, print_r($controles,true));

		$title = "$event_name_dist";
		$subject = $title;


		$reclass = $this->unitsLibrary;

		$headlist = [];
		$controle_num = 0;
		foreach ($controles as $c) {
			$controle_num++;
			$cd_mi = $c['dist_mi'] . " mi";
			$cd_km = $c['dist_km'];
			$is_start = isset($c['start']);
			$is_finish = isset($c['finish']);
			$number = ($is_start) ? "START" : (($is_finish) ? "FINISH" : "Control $controle_num");
			$open = (new \DateTime($c['open']))->setTimezone(new DateTimeZone($club['event_timezone_name']))->format('D-H:i');
			$close = (new \DateTime($c['close']))->setTimezone(new DateTimeZone($club['event_timezone_name']))->format('D-H:i');
			
			// $close = $c['close']; // ->format('D-H:i');
			$name = $c['name'];
			$headlist[] = compact('number', 'cd_mi', 'cd_km', 'is_start', 'is_finish', 'open', 'close', 'name');
		}

		$headlist = $this->flipDiagonally($headlist);
		foreach ($headlist as $key => $row) {
			$head_row[$key] = '<TH></TH><TH>' . implode('</TH><TH>', $row) . '</TH>';
		}
		$checkin_table .= "<TR class='w3-blue'>" . $head_row['number'] . "<TH ROWSPAN=4>Final</TH></TR>";
		$checkin_table .= "<TR class='w3-light-blue' style='font-size: 0.7em;'>" . $head_row['name'] . "</TR>";
		$checkin_table .= "<TR class='w3-light-blue'>" . $head_row['cd_mi'] . "</TR>";
		$checkin_table .= "<TR class='w3-light-blue'>" . $head_row['close'] . "</TR>";


		$riders_seen = $this->checkinModel->riders_seen($local_event_id);

		foreach ($riders_seen as $rider_id) {


			// $this->die_message(__METHOD__, print_r($club,true));

			$first_name = "?";
			$last_name = "?";
			$rider = "$first_name $last_name ($rider_id)";

			// Assume $rider_id = $rusa_id; // assumption

			$checklist = [];
			for ($i = 0; $i < $ncontroles; $i++) {
				$open = $controles[$i]['open'];
				$close = $controles[$i]['close'];
				$c = $this->checkinModel->get_checkin($local_event_id, $rider_id, $i, $club['event_timezone_name']);
				if (empty($c)) {
					$checklist[] = '-';
				} else {

					$checkin_time = $c['checkin_time'];

					$el = "";
					if ($c['preride']) {
						$el = "<br><span class='green italic sans smaller'>Preride</span>";
					} elseif ($checkin_time < $open) {
						$el = "<br><span class='red italic sans smaller'>EARLY!</span>";
					} elseif ($checkin_time > $close) {
						$el = "<br><span class='red italic sans smaller'>LATE!</span>";
					}

					$control_index = $i;
					$d = compact('control_index', 'event_code', 'rider_id');
					$checkin_code = $this->make_checkin_code($d, $epp_secret);

					if ($this->isAdmin()) {
						$el .= "&nbsp; <i title='$checkin_code' class='fa fa-check-circle' style='color: #355681;'></i>";
					}

					// && false===strpos(strtolower($comment), 'automatic check in')
					if (!empty($comment)) {
						$el .= "&nbsp; <i title='$comment' class='fa fa-comment' style='color: #355681;'></i>";
					}

					$checklist[] = $checkin_time->format('H:i') . $el;
				}
			}
			$checkins = implode('</TD><TD>', $checklist);

			$finish_text = "";
			$r = $this->rosterModel->get_record($local_event_id, $rider_id);

			if(empty($r)) $this->die_message('ERROR',"Rider ID=$rider_id seen in event=$local_event_id but not found in roster.");

			if ($r['result'] == "finish") {
				$elapsed_array = explode(':', $r['elapsed_time'], 3);
				if (count($elapsed_array) == 3) {
					list($hh, $mm, $ss) = $elapsed_array;
					$elapsed_hhmm =  "$hh$mm";
					$d = compact('elapsed_hhmm', 'global_event_id', 'rider_id');
					$finish_code = $this->make_finish_code($d, $epp_secret);

					$finish_text = $hh .  "h&nbsp;" . $mm . "m";

					if ($this->isAdmin()) {
						$finish_text .= "<br>($finish_code)";
					}
				}
			}

			$checkin_table .= "<TR><TD>$rider</TD><TD>$checkins</TD><TD>$finish_text</TD></TR>";
		}



		$view_data = compact(
			'title',
			'subject',
			'checkin_table'
		);

		$this->viewData = array_merge($this->viewData, $view_data);
		return $this->load_view(['head','checkin_status','foot']);



		// $this->load->view('simple_header', array_merge($view_data, ['page_styles' => ['table']]));
		// $this->load->view('simple_body', $view_data);
		// $this->load->view('simple_footer', $view_data);
	}

	private function isAdmin()
	{
		return true; // $this->session->get_data('login_is_admin');
	}

	private function flipDiagonally($arr)
	{
		$out = [];
		foreach ($arr as $key => $subarr) {
			foreach ($subarr as $subkey => $subvalue) {
				$out[$subkey][$key] = $subvalue;
			}
		}
		return $out;
	}

	private function make_checkin_code($d, $epp_secret)
	{
		extract($d);
		$plaintext = "$control_index-$event_code-$rider_id-$epp_secret";
		$ciphertext = hash('sha256', $plaintext);
		$plain_code = strtoupper(substr($ciphertext, 0, 4));
		$xycode = str_replace(['0', '1'], ['X', 'Y'], $plain_code);
		return $xycode;
	}

	private function make_finish_code($d, $epp_secret)
	{
		extract($d);
		$plaintext = "Finished:$elapsed_hhmm-$global_event_id-$rider_id-$epp_secret";
		$ciphertext = hash('sha256', $plaintext);
		$plain_code = strtoupper(substr($ciphertext, 0, 4));
		$xycode = str_replace(['0', '1'], ['X', 'Y'], $plain_code);
		return $xycode;
	}
}
