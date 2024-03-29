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

require_once(APPPATH . 'Libraries/Secret/Secrets.php'); 

use Secrets;

class RegionLister extends BaseController
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
	// Region List (for eBrevet JSON)
	//

	public function json_region_list()
	{
		$region_list = $this->regionModel->getRegions();

		$region_list_hash = hash('sha256', json_encode($region_list));
        $secret = Secrets::region_list_secret;
        $now = new \DateTime('now', new DateTimeZone('UTC'));
        $timestamp = $now->format('c');

		$plaintext = "$region_list_hash-$timestamp-$secret";
		$signature = hash('sha256', $plaintext);

		$this->emit_json(compact('region_list', 'timestamp', 'signature'));
	}

}
