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

class PostCheckin extends EventProcessor
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

}