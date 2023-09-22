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


	public function initController(
		RequestInterface $request,
		ResponseInterface $response,
		LoggerInterface $logger
	) {
		parent::initController($request, $response, $logger);

		$this->rusaModel = model('Rusa');
	}


	////////////////////////////////////////////////
	// EVENT CHECKIN STATUS
	//

	public function checkin_status($event_code = null)
	{

		try {

			$checkin_table = '';

			$event = $this->eventModel->eventByCode($event_code);
			$edata = $this->get_event_data($event);

			/* 

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
 */
			// Establish 'event','route','controles','warnings','cues','route_event'

			$event_name_dist = $edata['event_name_dist'];
			$controles = $edata['controls'];
			$ncontroles = count($controles);

			$local_event_id = $edata['local_event_id'];
			$epp_secret = $edata['epp_secret'];

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
				$open = (new \DateTime($c['open']))->setTimezone(new \DateTimeZone($edata['event_timezone_name']))->format('D-H:i');
				$close = (new \DateTime($c['close']))->setTimezone(new \DateTimeZone($edata['event_timezone_name']))->format('D-H:i');

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


			// $registeredRiders = $this->checkinModel->registeredRiders$registeredRiders($local_event_id);
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
			

				$checklist = [];
				for ($i = 0; $i < $ncontroles; $i++) {
					$open = $controles[$i]['open'];
					$close = $controles[$i]['close'];
					$c = $this->checkinModel->get_checkin($local_event_id, $rider_id, $i, $edata['event_timezone_name']);
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

				if (empty($r)){ // $this->die_message('ERROR', "Rider ID=$rider_id seen in event=$local_event_id but not found in roster.");
					$r['result']='NOT IN ROSTER';
				}
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
			return $this->load_view(['checkin_status']);
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
