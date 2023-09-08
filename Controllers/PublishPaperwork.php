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

class PublishPaperwork extends Ebrevet
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
			if (false == $this->rwgpsLibrary->is_good_route_id($route_id)) throw new \Exception("Invalid parameters.");
			$result = $this->rwgpsLibrary->download_route_data($route_id);
			if ($result !== true) throw new \exception($result);
			$this->die_info("Success!", "Route $route_id successfully fetched from RWGPS at ". $edata['now_str']);

		} catch (\Exception $e) {
			$this->die_exception($e);
		}
	}


	// PUBLISH PUBLISH PUBLISH PUBLISH PUBLISH PUBLISH 
	// PUBLISH PUBLISH PUBLISH PUBLISH PUBLISH PUBLISH 
	// PUBLISH PUBLISH PUBLISH PUBLISH PUBLISH PUBLISH 

	// BUG????? Does this publish cards? 

	// TODO Publish CARDS and QR CODE Sheets and ROUTE FILES  -- all docs should be static

	public function publish_cuesheet($event_code)
	{

		$event = $this->eventModel->eventByCode($event_code);
		$edata = $this->get_event_data($event);


		$route_event['cue_version'] = $cue_version = 1 + $edata['cue_version'];
		$route_event['cue_version_str'] = "$cue_version";

		$cue_data_P = $this->generate_pdf_cuesheet($edata, 'P');

		$cue_data_L = $this->generate_pdf_cuesheet($edata, 'L');

		$cue_data_C = $this->generate_csv_cuesheet($edata);


		$this->model_parando->set_event_cuesheet_version($event_id, $cue_version);

		// Publishing success page

		$event_name_dist = $route_event['event_name_dist'];
		$cue_url_L = $cue_data_L['cue_url'];
		$cue_url_P = $cue_data_P['cue_url'];
		$cue_url_C = $cue_data_C['cue_url'];
		$wizard_url = site_url(strtolower(get_class($this)) . "/wizard/$event_id");
		$info_url = site_url(strtolower(get_class($this)) . "/info/$event_id");
		$event_url = site_url("info/event/$event_id");

		$left_column = <<<EOT
<H3>Cue Sheet Published</H3>
<p>Cue Sheets (version $cue_version) successfully published to event database 
for $event_name_dist.  Don't refresh or reverse or you might accidentally publish again. Just press one of the buttons below.</p>
<ul>
<li><A HREF="$cue_url_L">PDF File Landscape</A>
<li><A HREF="$cue_url_P">PDF File Portrait</A>
<li><A HREF="$cue_url_C">Unformatted CSV File</A>
</ul>
<div class="button-container text-center">
<A HREF=$wizard_url>Return to Wizard for this Event</A>
<A HREF=$info_url>View Published Route</A>
<A HREF=$event_url>Go to Published Event Page</A>
</div>
EOT;

		$data = ['title' => "Cue Sheet Published", 'left_column' => $left_column, 'right_column' => ''];
		$this->simple_view('info_one_column', ['buttonlink'], $data);
	}

	private function generate_csv_cuesheet($edata)
	{
		$event_tagname = $this->model_parando->make_event_tagname($route_event);
		$cue_version = $route_event['cue_version'];
		$cue_basename = "$event_tagname-CueSheetV$cue_version";
		$cue_filename = Cuesheet::cuesheet_path . "/" . $cue_basename . ".csv";
		$cue_url = Cuesheet::cuesheet_baseurl . "/" . $cue_basename . ".csv";
		$this->cuesheet->set_controle_date_format($route_event);
		$header_text = $this->cuesheet->header_text_array($route_event);
		$cue_text = $this->cuesheet->cue_text_array($cues, $controles);
		$this->emit_csv(array_merge($header_text, $cue_text), $cue_filename, true);
		return compact('cue_url', 'cue_filename', 'cue_version');
	}

	private function generate_pdf_cuesheet($edata, $orientation)
	{
		$event_tagname = $this->model_parando->make_event_tagname($route_event);
		$cue_version = $route_event['cue_version'];
		$cue_basename = "$event_tagname-CueSheetV$cue_version";
		$params = ['event' => $route_event, 'orientation' => $orientation, 'size' => $size];
		$cuesheet = new Cuesheet($params);
		$cuesheet->set_controle_date_format($route_event);
		$cuesheet->AddPage();
		$cuesheet->draw_cuesheet_pages($route_event, $cues, $controles);
		$cue_filename = Cuesheet::cuesheet_path . "/" . $cue_basename . "-$orientation.pdf";
		$cue_url = Cuesheet::cuesheet_baseurl . "/" . $cue_basename . "-$orientation.pdf";
		$cuesheet->Output("F", $cue_filename);
		$fstat = @stat($cue_filename);
		if ($fstat === false || empty($fstat['size'])) $die->error(__METHOD__, "Failed to create cuesheet file '$cue_filename'");
		return compact('cue_url', 'cue_filename', 'cue_version');
	}
}
