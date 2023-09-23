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

class PublishPaperwork extends EventProcessor
{

	public $unitsLibrary;
	public $cuesheetLibrary;
	public $rwgpsLibrary;

	public function initController(
		RequestInterface $request,
		ResponseInterface $response,
		LoggerInterface $logger
	) {
		parent::initController($request, $response, $logger);


		$this->unitsLibrary = new \App\Libraries\Units();
		$this->cuesheetLibrary = new \App\Libraries\Cuesheet();
		$this->rwgpsLibrary = new \App\Libraries\Rwgps();
	}


	// TODO ACCESS CONTROLS
	// Access control requires $event_code be known, or at least acp_club_code


	// DOWNLOAD DOWNLOAD DOWNLOAD DOWNLOAD DOWNLOAD DOWNLOAD DOWNLOAD
	// DOWNLOAD DOWNLOAD DOWNLOAD DOWNLOAD DOWNLOAD DOWNLOAD DOWNLOAD
	// DOWNLOAD DOWNLOAD DOWNLOAD DOWNLOAD DOWNLOAD DOWNLOAD DOWNLOAD

	public function recache($event_code)
	{

		try {
			$event = $this->eventModel->eventByCode($event_code);
			$edata = $this->get_event_data($event);

			$route_id = $edata['route_id'];

			$this->viewData['event_name_dist']=$edata['event_name_dist'];
			$this->viewData['route_url']=$edata['route_url'];
			$this->viewData['route_manager_url']=$edata['route_manager_url'];
			$this->viewData['event_info_url']=$edata['event_info_url'];

			if (false == $this->rwgpsLibrary->is_good_route_id($route_id)) throw new \Exception("Invalid parameters.");
			$result = $this->rwgpsLibrary->download_route_data($route_id);
			if ($result !== true) throw new \exception($result);

			$route=$this->rwgpsLibrary->get_route($route_id);

			$last_update_datetime = new \DateTime('@' . $route['updated_at']);
			$last_update_datetime->SetTimezone($edata['event_tz']);
			$this->viewData['last_update'] = $last_update_datetime->format("Y-m-j H:i:s T");
	
			$last_download_datetime = new \DateTime('@' . $route['downloaded_at']);
			$last_download_datetime->SetTimezone($edata['event_tz']);
			$this->viewData['last_download'] = $last_download_datetime->format("Y-m-j H:i:s T");
	
			return $this->load_view('fetch_success');

		} catch (\Exception $e) {
			$this->die_exception($e);
		}
	}


	// PUBLISH PUBLISH PUBLISH PUBLISH PUBLISH PUBLISH 
	// PUBLISH PUBLISH PUBLISH PUBLISH PUBLISH PUBLISH 
	// PUBLISH PUBLISH PUBLISH PUBLISH PUBLISH PUBLISH 

	// BUG????? Does this publish cards? No!
	// Can't publish static cards because cards are customized to roster.

	public function publish($event_code)
	{

		try {
			$event = $this->eventModel->eventByCode($event_code);
			$edata = $this->get_event_data($event);
			$this->viewData = array_merge($this->viewData, $edata);

			$edata['cue_version'] = $cue_version = 1 + $edata['cue_version'];
			$edata['cue_version_str'] = $cue_version_str = "$cue_version";

			$cue_data_P = $this->publish_pdf_cuesheet($edata, 'P');
			$cue_data_L = $this->publish_pdf_cuesheet($edata, 'L');
			$cue_data_C = $this->publish_csv_cuesheet($edata);

			$this->eventModel->set_cuesheet_version($event_code, $cue_version);
			
		} catch (\Exception $e) {
			$this->die_exception($e);
		}

		// Publishing success page

		$cue_url['P'] = $cue_data_P['cue_url'];
		$cue_url['L'] = $cue_data_L['cue_url'];
		$cue_url['C'] = $cue_data_C['cue_url'];

		$this->viewData['cue_url'] = $cue_url;
		$this->viewData['cue_version'] = $cue_version;
		$this->viewData['cue_version_str'] = $cue_version_str;


		return $this->load_view('publish_success');
	}

	private function publish_csv_cuesheet($edata)
	{

		extract($edata);
		$controles = $edata['route_controles'];

		$cuesheetLibrary = new \App\Libraries\Cuesheet();
		$cuesheetLibrary->set_controle_date_format($edata);

		$event_tagname = $edata['event_tagname'];
		$csv_filename = "$event_tagname-CueSheet.csv";


		$header_text = $cuesheetLibrary->header_text_array($edata);
		$cue_text = $cuesheetLibrary->cue_text_array($cues, $controles);
		// $this->emit_csv(array_merge($header_text, $cue_text), $csv_filename);


		$event_tagname = $edata['event_tagname'];
		$cue_version = $edata['cue_version'];
		$cue_basename = "$event_tagname-CueSheetV$cue_version";
		$cue_filename = $cuesheetLibrary::cuesheet_path . "/" . $cue_basename . ".csv";
		$cue_url = $cuesheetLibrary::cuesheet_baseurl . "/" . $cue_basename . ".csv";
		$cuesheetLibrary->set_controle_date_format($edata);
		$header_text = $cuesheetLibrary->header_text_array($edata);
		$cue_text = $cuesheetLibrary->cue_text_array($cues, $controles);
		$this->emit_csv(array_merge($header_text, $cue_text), $cue_filename, true);
		return compact('cue_url', 'cue_filename', 'cue_version');
	}

	private function publish_pdf_cuesheet($edata, $orientation = 'P', $size = 'letter')
	{
		$event_tagname = $edata['event_tagname'];

		$cuesheetLibrary = new \App\Libraries\Cuesheet(['edata' => $edata, 'orientation' => $orientation, 'size' => $size]);

		$cuesheetLibrary->AddPage();
		$cuesheetLibrary->set_controle_date_format($edata);
		$cuesheetLibrary->draw_cuesheet_pages($edata);

		$cue_version = $edata['cue_version'];
		$cue_basename = "$event_tagname-CueSheetV$cue_version";

		$cue_filename = $cuesheetLibrary::cuesheet_path . "/" . $cue_basename . "-$orientation.pdf";
		$cue_url = $cuesheetLibrary::cuesheet_baseurl . "/" . $cue_basename . "-$orientation.pdf";
		$cuesheetLibrary->Output("F", $cue_filename);
		$fstat = @stat($cue_filename);
		if ($fstat === false || empty($fstat['size'])) throw new \Exception("Failed to create cuesheet file '$cue_filename'");
		return compact('cue_url', 'cue_filename', 'cue_version');
	}
}
