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

class RosterInfo extends EventProcessor
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

	public function roster_info($event_code = null)
	{

		try {

			try {
				$event = $this->eventModel->eventByCode($event_code);
				if (empty($event['route_url'])) throw new \Exception('NO MAP URL FOR ROUTE.');
				$route_url = $event['route_url'];
				$edata = $this->get_event_data($event);
			} catch (\Exception $e) {
				$this->die_data_exception($e);
			}

			$event_name_dist = $edata['event_name_dist'];
			$club_acp_code = $edata['club_acp_code'];
			$local_event_id = $edata['local_event_id'];
			$website_url = $edata['website_url'];
			$club_name = $edata['club_name'];
			$icon_url = $edata['icon_url'];
			$club_event_info_url = $edata['club_event_info_url'];
			$title = "$event_name_dist";
			$subject = $title;


			$registeredRiders = $this->rosterModel->registered_riders($local_event_id);
            $roster_table = "<TR class='w3-dark-gray'><TH>Rider</TH><TH>Address</TH><TH>ID Number</TH><TH>Status</TH></TR>";


			foreach ($registeredRiders as $rider) {

				$rider_id = $rider['rider_id'];
				// Assume $rider_id = $rusa_id; // assumption

                // lets hope rusa.org sanitizes rider id parameters
                $rider_id_txt = "<A HREF='https://rusa.org/cgi-bin/membersearch_PF.pl?mid=$rider_id'>$rider_id</A>";

				$m = $this->rusaModel->get_member($rider_id);
				if (empty($m)) {
					$first_last = "NON RUSA";
				} else {
					$first_last = $m['first_name']  . ' ' . $m['last_name'];
				}

                $city=$m['city'] ?? '';
                $state=$m['state'] ?? '';
                $city_state = (!empty($city) && !empty($state)) ? "$city, $state" : "$city$state";
                $country = $m['country'] ?? '';
                $address = (!empty($country) && $country != 'US') ? "$city_state ($country)" : $city_state; 

				$r = $this->rosterModel->get_record($local_event_id, $rider_id);

				if (empty($r)) { // $this->die_message('ERROR', "Rider ID=$rider_id seen in event=$local_event_id but not found in roster.");
					$r['result'] = 'NOT IN ROSTER';
				} 

                $rider_result = strtoupper($r['result'] ?? '');
                $rider_elapsed = $r['elapsed_time'] ?? '';

                if(!empty($rider_elapsed)){
                    $parts = explode(':',$rider_elapsed);
                    $hhmm = implode(':',array_slice($parts,0,2));
                    $rider_elapsed = "[$hhmm]";
                }

                if($rider_result=='FINISH')
                  $rider_status="$rider_result $rider_elapsed";
                else
                  $rider_status=$rider_result;


				$rider_highlight = "";


				$roster_table .= "<TR><TD>$first_last</TD><TD>$address</TD><TD>$rider_id_txt</TD><TD>$rider_status</TD></TR>";
			}


			$view_data = compact(
				'title',
				'subject',
				'roster_table',
				'icon_url',
				'club_name',
				'event_code',
				'website_url'
			);

			$this->viewData = array_merge($this->viewData, $view_data);
			return $this->load_view(['roster_info'], $club_acp_code);
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
