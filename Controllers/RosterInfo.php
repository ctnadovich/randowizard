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
	// public $rusaModel;
	public $cryptoLibrary;


	public function initController(
		RequestInterface $request,
		ResponseInterface $response,
		LoggerInterface $logger
	) {
		parent::initController($request, $response, $logger);

		// $this->rusaModel = model('Rusa');
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
			
			$roster_table = $this->make_roster_table($edata);

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


}
