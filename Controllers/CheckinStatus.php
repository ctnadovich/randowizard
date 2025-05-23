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
	// public $rusaModel;
	public $cryptoLibrary;


	public function initController(
		RequestInterface $request,
		ResponseInterface $response,
		LoggerInterface $logger
	) {
		parent::initController($request, $response, $logger);

		// $this->rusaModel = model('Rusa');
		// $this->cryptoLibrary = new \App\Libraries\Crypto();
	}


	////////////////////////////////////////////////
	// EVENT CHECKIN STATUS
	//

	public function checkin_status($event_code = null, $view = 'html')
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



			$website_url = $edata['website_url'];
			$club_name = $edata['club_name'];
			$icon_url = $edata['icon_url'];
			$club_event_info_url = $edata['club_event_info_url'];
			$club_acp_code = $edata['club_acp_code'];


			$title = "$event_name_dist";
			$subject = $title;


			if ($view == 'json') {
				$checkin_table = $this->make_checkin_table($edata,'json');
                $this->emit_json($checkin_table);
				return "";
			} else {
				$checkin_table = $this->make_checkin_table($edata);
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
			}
		} catch (\Exception $e) {
			$this->die_exception($e);
		}
	}



	// private function flipDiagonally($arr)
	// {
	// 	$out = [];
	// 	foreach ($arr as $key => $subarr) {
	// 		foreach ($subarr as $subkey => $subvalue) {
	// 			$out[$subkey][$key] = $subvalue;
	// 		}
	// 	}
	// 	return $out;
	// }
}
