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

class Generate extends EventProcessor
{

	public $unitsLibrary;
	public $rwgpsLibrary;

	public function initController(
		RequestInterface $request,
		ResponseInterface $response,
		LoggerInterface $logger
	) {
		parent::initController($request, $response, $logger);


		$this->unitsLibrary = new \App\Libraries\Units();
		$this->rwgpsLibrary = new \App\Libraries\Rwgps();
	}


	// TODO ACCESS CONTROLS
	// Access control requires $event_code be known, or at least acp_club_code

	public function generate($event_code, $generate = null)
	{

		try {

			if (empty($generate) && $this->request->is('post')) {
				$generate = $this->request->getVar('generate');
			}

			if (empty($generate)) throw new \Exception("Nothing to generate -- type missing or empty.");
			if (!method_exists($this, 'generate_' . $generate)) throw new \Exception("No such generation type: '$generate'");

			$event = $this->eventModel->eventByCode($event_code);
			$edata = $this->get_event_data($event);
			$route_manager_url = site_url("route_manager/$event_code");
			if($edata['route_has_warnings']) $this->die_message_notrace('ERRORS FOUND',"The '$generate' cannot be
			generated because errors have 
			been found in the route or event data. Please <A HREF='$route_manager_url'>go to the 
			Route Manager</A> to see what these errors
			are so that they can be fixed.");


			$this->viewData = array_merge($this->viewData, $edata);

			return $this->{'generate_' . $generate}($edata);
		} catch (\Exception $e) {
			$this->die_exception($e);
		}
	}

	private function die_if_warnings($warnings)
	{ // for views that want to die if there are warnings

		if (!empty($warnings)) {
			$cw = implode('</li><li>', $warnings);
			$body = <<<EOT
<p>The requested function is not available because of Errors in route data.</p>
<ul><li>$cw</li></ul>
<p>Fix these Errors by editing the RWGPS route, saving in RWGPS, and re-downloading.</p>
EOT;
			$this->die_message('Route Errors', $body, ['backtrace' => false]);
		}
	}

	// VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS 
	// VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS 
	// VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS VIEWS 

	// Raw JSON View

	private function generate_json($edata)
	{
		$route = $this->rwgpsLibrary->get_route($edata['route_id']);
		header("Content-Type: application/json; charset=UTF-8");
		echo json_encode($route);
		exit();
	}

	// Cue processing SEARCH/REPLACE VIEW

	private function generate_search_replace($edata)
	{

		extract($edata);
		$controles = $edata['route_controles'];

		$cuesheetLibrary = new \App\Libraries\Cuesheet();
		$cuesheetLibrary->set_controle_date_format($edata);
		$cue_text = $cuesheetLibrary->cue_text_array($cues, $controles);

		$change_log = [];
		foreach ($cue_text as $line) {
			$controle_i = array_shift($line);
			if (empty($controle_i)) {
				$line[$cuesheetLibrary->dist_cols + 1] = $cuesheetLibrary->clean_string($line[$cuesheetLibrary->dist_cols + 1]); // clean note
				$line_r = $cuesheetLibrary->replace_cues($line);
				$dist = $line[0];
				$dir = $line[$cuesheetLibrary->dist_cols + 0];
				$dir_r = $line_r[$cuesheetLibrary->dist_cols + 0];
				$note = $line[$cuesheetLibrary->dist_cols + 1];
				$note_r = $line_r[$cuesheetLibrary->dist_cols + 1];
				if ($note_r != $note) {
					$change_log[] = [$dist, "$dir: $note", ">>>>>", "$dir_r: $note_r"];
				}
			}
		}


		$body = <<<EOT
<H4>Cue Search/Replace Changes</H4>
<div class='w3-container'><TABLE class='w3-table-all'><TR><TD>
EOT;
		$func = function ($a) {
			return implode("</TD><TD>", $a);
		};
		$body .= implode("</TD></TR><TR><TD>", array_map($func, $change_log));
		$body .= <<<EOT
</TD></TR></TABLE></DIV>

EOT;

		$this->viewData['panel_data'] = $body;
		$this->viewData['panel_title'] = $event_name_dist;

		return $this->load_view(['event_info_panel']);
	}

	// CUE SHEET VIEWS
	// CUE SHEET VIEWS
	// CUE SHEET VIEWS
	// PDF Rendered cue_sheet Views

	private function generate_pdf_cue($edata)
	{
		$this->_view_pdf_cue($edata, 'P');
	}
	private function generate_pdf_cue_portrait($edata)
	{
		$this->_view_pdf_cue($edata, 'P');
	}
	private function generate_pdf_cue_landscape($edata)
	{
		$this->_view_pdf_cue($edata, 'L');
	}

	private function _view_pdf_cue($edata, $orientation = 'P', $size = 'letter')
	{

		$event_tagname = $edata['event_tagname'];

		$cuesheetLibrary = new \App\Libraries\Cuesheet(['edata' => $edata, 'orientation' => $orientation, 'size' => $size]);

		// $cuesheetLibrary->__construct();

		$cuesheetLibrary->AddPage();
		$cuesheetLibrary->set_controle_date_format($edata);
		$cuesheetLibrary->draw_cuesheet_pages($edata);
		$cuesheetLibrary->Output("I", "$event_tagname-CueSheet-$orientation.pdf");

		exit();
	}


	private function generate_csv_cue($edata)
	{

		extract($edata);
		$controles = $edata['route_controles'];

		$cuesheetLibrary = new \App\Libraries\Cuesheet();
		$cuesheetLibrary->set_controle_date_format($edata);

		$event_tagname = $edata['event_tagname'];
		$csv_filename = "$event_tagname-CueSheet.csv";


		$header_text = $cuesheetLibrary->header_text_array($edata);
		$cue_text = $cuesheetLibrary->cue_text_array($cues, $controles);
		$this->emit_csv(array_merge($header_text, $cue_text), $csv_filename);
		exit();
	}


	// BREVET CARD VIEWS
	// BREVET CARD VIEWS
	// BREVET CARD VIEWS

	// PDF Rendered Brevet Card Views

	private function generate_card_inside_validatefirst($edata)
	{
		$this->generate_card($edata, ['side' => 'inside', 'validate_first' => true]);  // stamp first control with logo
	}

	private function generate_card_inside($edata)
	{
		$this->generate_card($edata, ['side' => 'inside']);
	}

	private function generate_card_outside_blank($edata)
	{
		$this->generate_card($edata, ['side' => 'outside']);
	}

	private function generate_card_outside_roster($edata)
	{
		$this->generate_card($edata, ['side' => 'outside', 'roster' => 'true']);
	}

	private function generate_card($edata, $opts = [])
	{

		$this->die_not_admin($edata['club_acp_code']);

		$controles = $edata['route_controles'];
		$orientation = count($controles) > 15 ? "L" : "P";

		// $edata['cue_version'] = "(Last Published: " . (($edata['cue_version'] ?? 0) ?: 'None') . ")";

		$brevetcardLibrary = new \App\Libraries\Brevetcard(['edata' => $edata, 'orientation' => $orientation]);

		if (count($controles) > 15) $brevetcardLibrary->n_cards = 1;

		while (count($controles) > ($brevetcardLibrary->cpf * $brevetcardLibrary->n_folds)) {
			$brevetcardLibrary->n_folds++;
		}

		// $brevetcardLibrary->AddPage();
		$brevetcardLibrary->set_controle_date_format($edata);

		$event_tagname = $edata['event_tagname'];

		if (($opts['side'] ?? '') == 'inside') {
			// set false if you don't want the first controle validated when cards are prined
			$icon_url = (isset($opts['validate_first']) && isset($edata['icon_url'])) ? $edata['icon_url'] : null;
			$brevetcardLibrary->AddPage();
			$brevetcardLibrary->draw_card_inside($controles, $icon_url);
			$brevetcardLibrary->Output("I", "$event_tagname-CardInside.pdf");
		} elseif (($opts['side'] ?? '') == 'outside') {
			$edata['page3_image'] = $opts['page3_image'] ?? null;

			if (isset($opts['roster'])) {
				if (!isset($edata['roster'])) throw new \Exception("No roster in data.");
				$roster = $edata['roster'];
				$n_riders = count($roster);
				if (0 == $n_riders) $this->die_message("No Riders", "No brevet cards generated.", ['backtrace' => false]);
				$n_cards = $brevetcardLibrary->n_cards;


				// $this->die_message($n_cards, print_r($roster,true));

				for ($i = 0; $i < $n_riders; $i += $n_cards) {
					$r = array_slice($roster, $i, $n_cards);
					$brevetcardLibrary->AddPage();
					$brevetcardLibrary->draw_card_outside($edata, $r);
				}
				$brevetcardLibrary->Output("I", "$event_tagname-CardOutsideRoster.pdf");
			} else {
				$brevetcardLibrary->AddPage();
				$brevetcardLibrary->draw_card_outside($edata);
				$brevetcardLibrary->Output("I", "$event_tagname-CardOutsideBlank.pdf");
			}
		} else {
			throw new \Exception("Unknown card side.");
		}

		exit();
	}

	// SIGN IN ROSTER SHEETS

	private function generate_signin_sheet($edata, $opts = [])
	{

		$this->die_not_admin($edata['club_acp_code']);


		$roster = $edata['roster'];
		$n_riders = count($roster);
		if (0 == $n_riders) $this->die_message("No Riders", "No sign in sheet generated.", ['backtrace' => false]);


		$tagname = $edata['event_tagname'];
		extract($this->make_roster_table_array($edata));
		$params = [
			'edata' => $edata,
			'roster_table' => $roster_table_array,
			'header_row' => $header_row
		];
		$signinLibrary =  new \App\Libraries\Signin($params);
		$signinLibrary->AddPage();
		$signinLibrary->render_sheet();
		$signinLibrary->Output("I", $tagname . "-SignIn.pdf");
		exit();
	}



	private function make_roster_table_array($event)
	{

		$i = 1;
		$roster_table_array = [];

		$club = $this->regionModel->getClub($event['club_acp_code']);
		if (empty($club)) {
			throw new \Exception("UNKNOWN CLUB");
		}
		$epp_secret = $club['epp_secret'];

		foreach ($event['roster'] as $row) {

			$full_name =  $row['last_name'] . ', ' . $row['first_name'];

			$rusa_id = $row['rusa_id'];

			$cryptoLibrary =  new \App\Libraries\Crypto();
			$start_code = $cryptoLibrary->make_start_code($event, $rusa_id, $epp_secret);
			$dns = (!empty($row['result'])) ? $row['result'] : "";
			if ($dns == "FINISH") $dns = "FINISH: " . $row['elapsed_time'];

			$header_row = [
				['font' => 'bold', 'text' => '#', 'align' => 'C', 'width' => 4],
				['font' => 'bold', 'text' => 'Start Code', 'align' => 'C', 'style' => 'fit', 'width' => 5],
				['font' => 'bold', 'text' => 'RUSA', 'align' => 'C', 'width' => 6],
				['font' => 'bold', 'text' => 'Name', 'align' => 'C', 'width' => 30],
				['font' => 'bold', 'text' => 'Sign', 'align' => 'C', 'width' => 30],
				['font' => 'bold', 'text' => 'Time In', 'align' => 'C', 'width' => 25],
			];

			$roster_table_array[] = [
				['text' => "$i"],
				['text' => "$start_code", 'font' => 'fineprint'],
				['text' => "$rusa_id"],
				['text' => "$full_name", 'align' => 'L', 'style' => 'fit'],
				['text' => ""],
				['text' => "$dns"]
			];


			$i++;
		}

		return compact('header_row', 'roster_table_array');
	}

	// CSV DOWNLOAD

	private function generate_rusacsv($edata)
	{
		$event_code = $edata['event_code'];
		$local_event_id = $edata['local_event_id'];
		$filename = "results-$event_code.csv";
		$rusa_results = $this->rosterModel->get_rusa_results($local_event_id);

		// $this->die_message(__METHOD__, print_r(array_keys(reset($rusa_results)), true));

		if(empty($rusa_results)) $this->die_message_notrace('No Results', 'Nothing to download.');

		return $this->csv_noquote($rusa_results, $filename);
		// $this->load_view('upload_success');
	}

	private function csv_noquote($result_array, $filename = 'data.csv')
	{
		$delimiter = ",";
		$newline = "\r\n";
		$enclosure = "";
		$data = $this->csv_from_result($result_array, $delimiter, $newline, $enclosure);
		return $this->response->download($filename, $data)->setContentType('text/csv');	
	}


	function csv_from_result($result_array, $delimiter = ',', $newline = "\n", $enclosure = '"') {
		
		$out = '';
		$column_headers = array_keys(reset($result_array));
	
		// Write the column headers
		foreach ($column_headers as $field) {
			$out .= $enclosure.str_replace($enclosure, $enclosure.$enclosure, $field).$enclosure.$delimiter;
		}
		$out = rtrim($out);
		$out .= $newline;
	
		// Write the data rows
		foreach ($result_array as $row) {
			foreach ($row as $item) {
				$out .= $enclosure.str_replace($enclosure, $enclosure.$enclosure, $item).$enclosure.$delimiter;
			}
			$out = rtrim($out);
			$out .= $newline;
		}
	
		return $out;
	}
	

	// MAP AND EP ONLY

	private function generate_map($edata)
	{

		$mapLibrary = new \App\Libraries\Map();

		$controles = $edata['route_controles'];
		$route = $edata['route'];
		$route_name = $edata['route_name'];
		$route_id = $edata['route_id'];

		$provider = 'mapbox';

		$name = $route_name;
		$title = "$name (RWGPS #$route_id)";

		$map_divid = 'randomap';
		$graph_divid = 'eprofile';
		$map_script = $mapLibrary->generate_map_script($route, $controles, $map_divid, 'randomapv', $provider);
		$ep_script = $mapLibrary->generate_ep_script($route, $controles, $graph_divid);


		return $this->load_view([['map', compact('title', 'map_script', 'ep_script')]]);
	}
}
