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

class EventProcessor extends BaseController
{
	protected $eventModel;
	protected $regionModel;
	protected $checkinModel;
	protected $rosterModel;

	protected $rwgpsLibrary;
	protected $controletimesLibrary;
	protected $cuesheetLibrary;
	protected $unitsLibrary;

	public function initController(
		RequestInterface $request,
		ResponseInterface $response,
		LoggerInterface $logger
	) {
		parent::initController($request, $response, $logger);

		$this->eventModel = model('Event');
		$this->regionModel = model('Region');
		$this->checkinModel = model('Checkin');
		$this->rosterModel = model('Roster');

		$this->unitsLibrary = new \App\Libraries\Units();
		$this->rwgpsLibrary = new \App\Libraries\Rwgps();
		$this->cuesheetLibrary = new \App\Libraries\Cuesheet();
		$this->controletimesLibrary = new \App\Libraries\Controletimes();
	}

	public $minimum_app_version = '1.2.4';



	protected function emit_json($data)
	{
		$j = json_encode($data);
		header("Content-Type: application/json; charset=UTF-8");
		echo $j;
		exit();
	}

	public function get_event_by_code($event_code)
	{

		// try {

		if ($event_code === null) throw new \Exception("MISSING PARAMETER");

		if (0 == preg_match('/^(\d+)-(\d+)$/', $event_code, $m)) {
			throw new \Exception('INVALID EVENT ID');
		}

		list($all, $club_acp_code, $local_event_id) = $m;



		$event = $this->eventModel->getEvent($club_acp_code, $local_event_id);
		if (empty($event)) {
			throw new \Exception("NO SUCH EVENT");
		}

		return $event;
	}


	protected function get_event_data($event)
	{



		if (!is_array($event)) {
			throw new \Exception(__METHOD__ . ": BAD PARAMETER. NOT ARRAY.");
		}
		// Create globally unique event ID
		$local_event_id = $event['event_id'];
		$club_acp_code = $event['region_id'];
		$event_code = "$club_acp_code-$local_event_id";

		if (empty($event['route_url'])) throw new \Exception('NO MAP URL FOR ROUTE');
		if (empty($event['start_datetime'])) throw new \Exception('NO START TIME FOR EVENT');

		$club = $this->regionModel->getClub($club_acp_code);
		if (empty($club)) {
			throw new \Exception("UNKNOWN CLUB");
		}

		// Try to get route data
		$route_url = $event['route_url'];
		$route_id = $this->rwgpsLibrary->extract_route_id($event['route_url']);

		if ($route_id == null) {
			throw new \Exception('NO RWGPS MAP FOR ROUTE');
		} else {
			$route = $this->rwgpsLibrary->get_route($route_id);
			$has_rwgps_route = true;
			$rwgps_url = $this->rwgpsLibrary->make_route_url($route_id);
		}

		// And now the controls

		list($route_controles, $controle_warnings) = $this->rwgpsLibrary->extract_controles($route);

		if (sizeof($route_controles) == 0) {
			throw new \Exception("NO CONTROLS. Please mark all the controls with the cue-type 'CONTROL'.");
		}

		list($cues, $cue_warnings) = $this->rwgpsLibrary->extract_cues($route);

		if (sizeof($cues) == 0) {
			throw new \Exception("NO CUES");
		}

/* 		if (sizeof($controle_warnings) > 0 || sizeof($cue_warnings) > 0) {
			$error_text  = '';
			if (sizeof($controle_warnings) > 0)
				$error_text .= ("</h4>ERRORS IN CONTROLS</h4> <ul><li>" . implode('</li><li>', $controle_warnings) . "</li></ul>");
			if (sizeof($cue_warnings) > 0)
				$error_text .= ("</h4>ERRORS IN CUES</h4> <ul><li>" . implode('</li><li>', $cue_warnings) . "</li></ul>");
			throw new \Exception("<h3>Errors in Route Data</h3>$error_text<p>Please correct these errors in the 
													map data (<A HREF='$route_url'>$route_url</a>), then re-fetch the data to the event manager.</p>");
		}
 */


		$other_warnings = [];

		/////////////////////////////////////////////////////////
		// Now that we have the route and controls, we can start 
		// putting together all the required data. 
		//
		// EVENT DATA

		// TIME
		$event_timezone_name = $club['event_timezone_name'];  // For now, events can't have individual TZ
		$event_tz = new \DateTimeZone($event_timezone_name);

		$now = new \DateTime('now', $event_tz);
		$now_str = $now->format($this->controletimesLibrary->event_datetime_format);

		$start_datetime_str = $event['start_datetime'];
		$event_datetime = @date_create($start_datetime_str, $event_tz);
		if (false == $event_datetime) {
			throw new \Exception("INVALID START DATE OR TIME");
		}

		$event_datetime_str = $event_datetime->format($this->controletimesLibrary->event_datetime_format);
		$event_datetime_str_verbose = $event_datetime->format($this->controletimesLibrary->event_datetime_format_verbose);
		$event_date_str = $event_datetime->format('j F Y');
		$event_date_str_ymd = $event_datetime->format('Y-m-d');
		$event_time_str = $event_datetime->format('g:i A T');

		$utc_tz = new \DateTimeZone('UTC');
		$event_datetime_utc = clone $event_datetime;
		$event_datetime_utc->SetTimezone($utc_tz);
		$start_datetime_utc = $event_datetime_utc->format('c'); // Y-m-d H:i T';);
		$start_time_window = [
			'on_time' => $start_datetime_utc,
			'start_style' => 'massStart'   // The only start style supported
		];

		// Basic Route Event Data 

		$event_distance = $event['distance'];
		$event_gravel_distance = $event['gravel_distance'];
		$event_type = strtolower($event['sanction']);
		$event_type_uc = strtoupper($event_type);
		$route_event = compact('route', 'event', 'event_datetime', 'event_datetime_str', 'event_type', 'event_distance', 'event_gravel_distance', 'event_tz');

		// With the controls and route_event, now we can compute control times

		$this->controletimesLibrary->compute_open_close($route_controles, $route_event);


		// Processing of individual controls

		$controls = [];
		foreach ($route_controles as $cdata) {

			$a = $cdata['attributes'];

			if (empty($cdata['open']) || empty($cdata['close'])) {
				throw new \Exception("OPEN/ClOSE MISSING: " . print_r($cdata, true));
			}

			$openDatetime = $cdata['open'];
			$closeDatetime = $cdata['close'];
			$openDatetime->setTimezone($utc_tz);
			$closeDatetime->setTimezone($utc_tz);

			$reclass = $this->unitsLibrary;

			$cd_mi = round($cdata['d'] / ($reclass::m_per_km * $reclass::km_per_mi), 1);
			$cd_km = round($cdata['d'] / ($reclass::m_per_km), 1);

			$question = (array_key_exists('question', $a)) ? $a['question'] : '';

			$controls[] = [
				'dist_mi' => $cd_mi,
				'dist_km' => $cd_km,
				'long' => $cdata['x'],
				'lat' => $cdata['y'],
				'name' => $a['name'] ?? 'CONTROL NAME MISSING',
				'style' => $a['style'] ?? 'undefined',
				'address' => $a['address'] ?? 'CONTROL ADDRESS MISSING',
				'open' => $openDatetime->format('c'),
				'close' => $closeDatetime->format('c')
				// 'question'=>$question,
				// 'sif'=>$sif

			];
		}

		// More Event Data

		$start_city = $event['start_city'];
		$start_state = $event['start_state'];
		$event_location = "$start_city, $start_state";
		$event_name = $event['name'];
		$distance = $event['distance'];
		$event_name_dist = $event_name . ' ' . $distance . 'K';
		$gravel_distance = $event['gravel_distance'];
		$sanction = $event['sanction'];
		$type = $event['type'];
		$start_city = $event['start_city'];
		$start_state = $event['start_state'];
		$cue_version = $event['cue_version'];
		$event_info_url = $event['info_url'];
		$organizer_name = $event['emergency_contact'];
		$organizer_phone = $event['emergency_phone'];
		$event_description = $event['description'];

		$event_tagname_components = [$sanction, $distance . "K", $club_acp_code, $event_date_str_ymd];
		$event_tagname = strtoupper(implode('-', $event_tagname_components));

		$has_cuesheet = (isset($event['cue_version']) && $event['cue_version'] > 0);

		$cue_version = $has_cuesheet ? $event['cue_version'] : 0;
		$cue_version_str = $cue_version ?: "None";
		$cue_next_version = $cue_version + 1;

		foreach ($this->cuesheetLibrary->cue_types as $t)
			$cue_url[$t] = $this->cuesheetLibrary->make_url($event_tagname, $cue_version, $t);

		if (false == $has_cuesheet) {
			$published_at = 0;
			$cue_gentime_str = 'Never';
		} else {
			$published_at = $this->cuesheetLibrary->cueVersionPublishedAt($event_tagname, $cue_version);

			if ($published_at == 0)
				throw new \Exception("This can't happen. Event data indicates published cues, but cuesheet files were not found.");

			date_default_timezone_set($event_timezone_name);
			$cue_gentime_str = date("Y-m-j H:i:s T", $published_at);
		}

		// URLs  (Maybe these should go someplace else? )
		$checkin_post_url = site_url("/ebrevet/post_checkin/$club_acp_code");  // TODO, should go someplace else. Roster model?

		// $route_event_id = "$route_id/$local_event_id";

		$download_url = site_url("recache/$event_code");
		$event_info_url = site_url("event_info/$event_code");
		$event_publish_url = site_url("publish/$event_code");
		$event_preview_url = site_url("preview/$event_code");

		$download_note = 'Download Note';
		$this_organization = $club_name = $club['club_name'];

		// Route

		$route_name = $route['name'];

		$route_updated_at = $route['updated_at'];
		$last_update_datetime = new \DateTime('@' . $route['updated_at']);
		$last_update_datetime->SetTimezone($event_tz);
		$last_update = $last_update_datetime->format("Y-m-j H:i:s T");

		$last_download_datetime = new \DateTime('@' . $route['downloaded_at']);
		$last_download_datetime->SetTimezone($event_tz);
		$last_download = $last_download_datetime->format("Y-m-j H:i:s T");

		$download_note = $route['download_note'];

		if (!empty($route['description']))
			$route_tags = $this->rwgpsLibrary->parse_description($route['description'], $this->rwgpsLibrary->valid_event_description_keys);
		else
			$route_tags = [];

		// Only one route_tag supported 
		$pavement_type = (empty($route_tags['pavement_type'])) ? "Unspecified" : $route_tags['pavement_type'];

		$df_links = [];
		foreach ($route['route_datafile'] as $ext => $fn) {
			$uc_ext = strtoupper($ext);
			$base_fn = basename($fn);
			$df_links[] = "<A TITLE='$uc_ext file download' HREF='" . $route['saved_route_url'][$ext] . "'>$uc_ext</A>";
		}
		$df_links_txt = implode(', ', $df_links);

		//  [track_type] => loop [terrain] => climbing [difficulty] => hard [unpaved_pct] => 0 [surface] => paved 
		//$this->die_message(__METHOD__, print_r($route['unpaved_pct'],true));

		$terrain = empty($route['terrain']) ? '-' : ucfirst($route['terrain']);
		$surface = empty($route['surface']) ? '-' : ucfirst($route['surface']);
		$difficulty = empty($route['difficulty']) ? '-' : $route['difficulty'];
		$unpaved_pct = (isset($route['unpaved_pct'])) ? $route['unpaved_pct'] . "%" : '-';

		$units = $this->unitsLibrary;
		$distance_km = round($route['distance'] / $units::m_per_km, 1);
		$distance_mi = round($distance_km / $units::km_per_mi, 1);
		$climbing_ft = round($route['elevation_gain'] * $units::ft_per_m);


		$edata = compact(
			'checkin_post_url',
			'climbing_ft',
			'club_acp_code',
			'club_name',
			'controls',
			'controle_warnings',
			'cue_next_version',
			'cue_version_str',
			'cue_version',
			'cue_url',
			'cue_warnings',
			'cue_gentime_str',
			'cues',
			'df_links_txt',
			'difficulty',
			'distance_km',
			'distance_mi',
			'distance',
			'download_url',
			'download_note',
			'event_code',
			'event_date_str',
			'event_datetime',
			'event_datetime_str',
			'event_datetime_str_verbose',
			'event_description',
			'event_info_url',
			'event_location',
			'event_name_dist',
			'event_name',
			'event_preview_url',
			'event_publish_url',
			'event_tagname',
			'event_time_str',
			'event_type_uc',
			'event_type',
			'event_tz',
			'event_info_url',
			'gravel_distance',
			'has_cuesheet',
			'has_rwgps_route',
			'last_download',
			'last_update',
			'local_event_id',
			'now',
			'now_str',
			'organizer_name',
			'organizer_phone',
			'pavement_type',
			'published_at',
			'route_controles',
			'route_id',
			'route_name',
			'route_tags',
			'route_updated_at',
			'rwgps_url',
			'sanction',
			'start_city',
			'start_state',
			'start_time_window',
			'surface',
			'terrain',
			'this_organization',
			'unpaved_pct'
		);


		return array_merge($club, $route_event, $edata);
	}


	public function published_edata($route_event)
	{
		extract($route_event);
		$event_id = $event_code;  // published event ID is two part code
		$name = $event_name;
		return compact(
			'event_id',
			'name',
			'distance',
			'gravel_distance',
			'sanction',
			'type',
			'start_city',
			'start_state',
			'cue_version',
			// 'club_name',
			'club_acp_code',
			// 'region','club_logo_url',
			// 'club_website_url',
			'checkin_post_url',
			'event_info_url',
			// 'event_tzname',
			'organizer_name',
			'organizer_phone',
			'start_time_window',
			'controls'
		);
	}

	protected function isAdmin($club_acp_code = null)
	{
		if (null === $club_acp_code) return false;
		if (false == $this->isLoggedIn()) return false;
		$authorized_regions = $this->session->get('authorized_regions');
		return (false === array_search($club_acp_code, $authorized_regions)) ? false : true;
	}

	


	protected function format_attributes($alist)
	{
		$ca = "<TABLE class='w3-table-all'";
		foreach ($alist as $k => $v) {
			if (is_string($v))
				$ca .= "<TR><TD>#$k</TD><TD>$v</TD></TR>";
		}
		return $ca . "</TABLE>";
	}

	protected function emit_csv($array, $filename = 'data.csv', $write = false)
	{

		$output = null;

		if ($write) {
			$output = fopen($filename, 'w') or throw new \Exception("Can't open $filename");
		} else {
			$output = fopen("php://output", 'w') or throw new \Exception("Can't open php://output");
			header("Content-Type:application/csv");
			header("Content-Disposition:attachment;filename=$filename");
		}
		foreach ($array as $row) {
			fputcsv($output, $row);
		}
		fclose($output) or die("Can't close output.");
	}
}
