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

class CheckinStatus extends EventProcessor
{
	public $rusaModel;
	public $cryptoLibrary;


	public function initController(
		RequestInterface $request,
		ResponseInterface $response,
		LoggerInterface $logger
	) {
		parent::initController($request, $response, $logger);

		$this->rusaModel = model('Rusa');
		$this->cryptoLibrary = new \App\Libraries\Crypto();
	}


	////////////////////////////////////////////////
	// EVENT CHECKIN STATUS
	//

	public function checkin_status($event_code = null)
	{

		try {

			$checkin_table = '';

			// $event = $this->eventModel->eventByCode($event_code);
			// $edata = $this->get_event_data($event);

			try {


				$event = $this->eventModel->eventByCode($event_code);

				if (empty($event['route_url'])) throw new \Exception('NO MAP URL FOR ROUTE.');
				$route_url = $event['route_url'];

				$edata = $this->get_event_data($event);
			} catch (\Exception $e) {
				$this->die_data_exception($e);
			}

			$event_name_dist = $edata['event_name_dist'];

			$gravel_distance = $edata['gravel_distance'];
			$is_gravel = $gravel_distance > 0 ? true : false;


			$website_url = $edata['website_url'];
			$club_name = $edata['club_name'];
			$icon_url = $edata['icon_url'];
			$controles = $edata['controls'];
			$controles_extra = $edata['controls_extra'];
			$club_event_info_url = $edata['club_event_info_url'];
			$ncontroles = count($controles);
			$club_acp_code = $edata['club_acp_code'];

			$local_event_id = $edata['local_event_id'];
			$epp_secret = $edata['epp_secret'];

			$title = "$event_name_dist";
			$subject = $title;

			$reclass = $this->unitsLibrary;

			$is_untimed = [];
			$headlist = [];
			$controle_num = 0;
			foreach ($controles as $c) {
				$cd_mi = $c['dist_mi'] . " mi";
				$cd_km = $c['dist_km'];
				$is_start = isset($c['start']);
				$is_finish = isset($c['finish']);
				$is_intermediate = !$is_start && !$is_finish;

				$lat = $controles_extra[$controle_num]['lat'];
				$long = $controles_extra[$controle_num]['long'];

				$open_datetime = (new \DateTime($c['open']))->setTimezone(new \DateTimeZone($edata['event_timezone_name']));
				$close_datetime = (new \DateTime($c['close']))->setTimezone(new \DateTimeZone($edata['event_timezone_name']));
				$open = $open_datetime->format('D-H:i');
				$close = $close_datetime->format('D-H:i');


				$style = $c['style'];
				$really_timed = strtolower($c['timed'] ?? 'yes');
				$is_untimed['controle_num'] = $is_intermediate && 
				  ($is_gravel || ($style == 'info' || $style == 'photo' || $style == 'postcard') || ($really_timed=='no'));
				$close = $is_untimed['controle_num'] ? 'Untimed' : $close_datetime->format('D-H:i');

				$controle_num++;
				$number = ($is_start) ? "START" : (($is_finish) ? "FINISH" : "Control $controle_num");

				// $close = $c['close']; // ->format('D-H:i');
				$name = $c['name'];
				$headlist[] = compact('number', 'cd_mi', 'cd_km', 'is_start', 'is_finish', 'open', 'close',  'name', 'lat', 'long');
			}

			$headlist = $this->flipDiagonally($headlist);


			foreach ($headlist as $key => $row) {
				if ($key == 'name') {
					for ($i = 0; $i < $controle_num; $i++)
						$row[$i] .= "<br><A HREF='https://maps.google.com/?q=" .
							$headlist['lat'][$i] . ',' . $headlist['long'][$i] . "'><i style='font-size: 1.4em;' class='fa-solid fa-map-location-dot'></i></A>";
				}
				
				if ($key == 'open_datetime' || $key == 'close_datetime') {
					$head_row[$key] = $row;
				} else {
					$head_row[$key] = '<TH></TH><TH>' . implode('</TH><TH>', $row) . '</TH>';
				}
			}
			$checkin_table .= "<TR class='w3-dark-gray'>" . $head_row['number'] . "<TH>Final</TH></TR>";
			$checkin_table .= "<TR class='w3-light-gray' style='font-size: 0.7em;'>" . $head_row['name'] . "<TH></TH></TR>";
			$checkin_table .= "<TR class='w3-light-gray'>" . $head_row['cd_mi'] . "<TH></TH></TR>";
			$checkin_table .= "<TR class='w3-light-gray'>" . $head_row['close'] . "<TH></TH></TR>";


			$registeredRiders = $this->rosterModel->registered_riders($local_event_id);

			foreach ($registeredRiders as $rider) {

				$rider_id = $rider['rider_id'];
				// Assume $rider_id = $rusa_id; // assumption


				$m = $this->rusaModel->get_member($rider_id);
				if (empty($m)) {
					$first_last = "NON RUSA";
				} else {
					$first_last = $m['first_name']  . ' ' . $m['last_name'];
				}
				$rider = "$first_last ($rider_id)";


				$r = $this->rosterModel->get_record($local_event_id, $rider_id);

				if (empty($r)) { // $this->die_message('ERROR', "Rider ID=$rider_id seen in event=$local_event_id but not found in roster.");
					$r['result'] = 'NOT IN ROSTER';
				}

				$rider_highlight = "";

				switch ($r['result']) {
					case 'finish':
						$elapsed_array = explode(':', $r['elapsed_time'], 3);
						if (count($elapsed_array) == 3) {
							list($hh, $mm, $ss) = $elapsed_array;
							$elapsed_hhmm =  "$hh$mm";
							$global_event_id = $event_code;
							$d = compact('elapsed_hhmm', 'global_event_id', 'rider_id');
							$finish_code = $this->cryptoLibrary->make_finish_code($d, $epp_secret);

							$finish_text = $hh .  "h&nbsp;" . $mm . "m";

							if ($this->isAdmin($club_acp_code)) {
								$finish_text .= "<br>($finish_code)";
							}
						}else{
							$finish_text = "RBA Review";
						}
						break;
					default:
						$finish_text=strtoupper($r['result']);
						break;
				}



				$checklist = [];
				$has_no_checkins = true;
				for ($i = 0; $i < $ncontroles; $i++) {
					$open = $controles[$i]['open'];
					$close = $controles[$i]['close'];
					$open_datetime = (new \DateTime($open))->setTimezone(new \DateTimeZone($edata['event_timezone_name']));
					$close_datetime = (new \DateTime($close))->setTimezone(new \DateTimeZone($edata['event_timezone_name']));

					$control_index = $i;
					$d = compact('control_index', 'event_code', 'rider_id');
					$checkin_code = $this->cryptoLibrary->make_checkin_code($d, $epp_secret);

					$c = $this->checkinModel->get_checkin($local_event_id, $rider_id, $i, $edata['event_timezone_name']);
					if (empty($c)) {
						if ($this->isAdmin($club_acp_code)) {
							$checklist[] = "<span title='$checkin_code'>-</span>";
						} else {
							$checklist[] = '-';
						}
					} else {

						$has_no_checkins = false;

						$checkin_time = $c['checkin_time'];  // a DateTime object
						$comment = $c['comment'];

						$el = "";
						if ($c['preride']) {
							$el = "<br><span class='green italic sans smaller'>Preride</span>";
						} elseif ($checkin_time < $open_datetime && !$is_untimed[$i]) {
							$cit_str = $checkin_time->format('H:i');
							$open_str = $close_datetime->format('H:i');
							$el = "<br><span class='red italic sans smaller'>EARLY!</span>";
						} elseif ($checkin_time > $close_datetime && !$is_untimed[$i]) {
							$cit_str = $checkin_time->format('H:i');
							$close_str = $close_datetime->format('H:i');
							$el = "<br><span class='red italic sans smaller'>LATE! $cit_str &gt; $close_str</span>";
						}

						// $control_index = $i;
						// $d = compact('control_index', 'event_code', 'rider_id');
						// $checkin_code = $this->cryptoLibrary->make_checkin_code($d, $epp_secret);

						// if ($this->isAdmin($club_acp_code)) {
						// 	$el .= "&nbsp; <i title='$checkin_code' class='fa fa-check-circle' style='color: #355681;'></i>";
						// }

						// && false===strpos(strtolower($comment), 'automatic check in')
						if (!empty($comment)) {
							$el .= "&nbsp; <i title='$comment' class='fa fa-comment' style='color: #355681;'></i>";
						}

						$checkin_time_str = $checkin_time->format('H:i');

						if ($this->isAdmin($club_acp_code)) {
							$checkin_time_str = "<span title='$checkin_code'>$checkin_time_str</span>";
						}

						$checklist[] = $checkin_time_str . $el;
					}
				}

				if($has_no_checkins) continue;


				$checkins = implode('</TD><TD>', $checklist);



				$checkin_table .= "<TR><TD>$rider</TD><TD>$checkins</TD><TD>$finish_text</TD></TR>";
			}



			$view_data = compact(
				'title',
				'subject',
				'checkin_table',
				'icon_url',
				'club_name',
				'event_code',
				'website_url'
			);

			$this->viewData = array_merge($this->viewData, $view_data);
			return $this->load_view(['checkin_status'], $club_acp_code);
		} catch (\Exception $e) {
			$this->die_exception($e);
		}
	}

	// private function isAdmin()
	// {
	// 	return true; // $this->session->get_data('login_is_admin');
	// }

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
}
