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

		$other_warnings = [];

		if (!is_array($event)) {
			throw new \Exception(__METHOD__ . ": BAD PARAMETER. NOT ARRAY.");
		}
		// Create globally unique event ID
		$local_event_id = $event['event_id'];
		$club_acp_code = $event['region_id'];
		$event_code = $this->eventModel->getEventCode($event);

		if (empty($event['route_url'])) $this->die_info('No Route Map URL',  
		   'Sorry, but you can not use any Route Manager (CueWizard) functions until you add a route URL to your event.');

		// if (empty($event['route_url']))
		// 	throw new \Exception('NO ROUTE FOR EVENT. You must specify a URL for the event route map.');

		$club = $this->regionModel->getClub($club_acp_code);
		if (empty($club)) {
			throw new \Exception("UNKNOWN CLUB");
		}

		// Try to get route data
		$route_url = $event['route_url'];
		$route_id = $this->rwgpsLibrary->extract_route_id($route_url);

		if ($route_id == null) {
			throw new \Exception("INVALID ROUTE URL: $route_url");
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



		/////////////////////////////////////////////////////////
		// Now that we have the route and controls, we can start 
		// putting together all the required data. 
		//
		// EVENT DATA

		// TIME

		if (empty($event['start_datetime'])) {
			$other_warnings[] = "The start date/time for the event must be specified";
			$event['start_datetime'] = "2000-01-01 00:00:00";
		}

		$utc_tz = new \DateTimeZone('UTC');

		$event_timezone_name = $club['event_timezone_name'];  // For now, events can't have individual TZ
		$event_tz = new \DateTimeZone($event_timezone_name);

		$now = new \DateTime('now', $event_tz);
		$now_str = $now->format($this->controletimesLibrary->event_datetime_format);

		$start_datetime_str = $event['start_datetime'];
		$event_datetime = @date_create($start_datetime_str, $event_tz);
		if (false == $event_datetime) {
			throw new \Exception("INVALID START DATE OR TIME");
		}

		$last_event_change_datetime = @date_create($event['last_changed'], $utc_tz);
		if (false == $last_event_change_datetime) {
			throw new \Exception("INVALID UPDATE DATE OR TIME");
		}
		$last_event_change_datetime->SetTimezone($event_tz);
		$last_event_change_str = $last_event_change_datetime->format("Y-m-j H:i:s T");

		$event_datetime_str = $event_datetime->format($this->controletimesLibrary->event_datetime_format);
		$event_datetime_str_verbose = $event_datetime->format($this->controletimesLibrary->event_datetime_format_verbose);
		$event_date_str = $event_datetime->format('j F Y');
		$event_date_str_ymd = $event_datetime->format('Y-m-d');
		$event_time_str = $event_datetime->format('g:i A T');

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
		$controls_extra = [];
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

			$long = $cdata['x'];
			$lat = $cdata['y'];

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
			$open_datetime = $openDatetime; $close_datetime=$closeDatetime;
			$controls_extra[] = compact('open_datetime','close_datetime','lat','long');
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
		$club_event_info_url = $event['info_url'];
		$event_description = $event['description'];

		$roster = $this->rosterModel->registered_riders($local_event_id);

		if (empty($event['emergency_contact']) || empty($event['emergency_phone'])) {
			$other_warnings[] = 'Missing emergency contact or emergency phone number.';
			$organizer_name = "UNKNOWN";
			$organizer_phone = "UNKNOWN";
		} else {
			$organizer_name = $event['emergency_contact'];
			$organizer_phone = $event['emergency_phone'];
		}

		// $event_tagname_components = [$sanction, $distance . "K", $club_acp_code, $event_date_str_ymd];
		$event_tagname = $event_code; // strtoupper(implode('-', $event_tagname_components));

		$has_cuesheet = (isset($event['cue_version']) && $event['cue_version'] > 0);

		$cue_version = $has_cuesheet ? $event['cue_version'] : 0;
		$cue_version_str = $cue_version ?: "None";
		$cue_next_version = $cue_version + 1;

		foreach ($this->cuesheetLibrary->cue_types as $t)
			$cue_url[$t] = $this->cuesheetLibrary->make_url($event_tagname, $cue_version, $t);

		if (false == $has_cuesheet) {
			$published_at = 0;
			$published_at_datetime = null;
			$cue_gentime_str = $published_at_str = 'Never';
		} else {
			$published_at = $this->cuesheetLibrary->cueVersionPublishedAt($event_tagname, $cue_version);

			if ($published_at == 0)
				throw new \Exception("Event data indicates published cues, but cuesheet files were not found. Try setting the cue version to zero and publishing again.");

			$published_at_datetime = new \Datetime("@$published_at");
			$published_at_datetime->setTimezone($event_tz);
			$cue_gentime_str = $published_at_str = $published_at_datetime->format("Y-m-j H:i:s T");
		}

		// URLs  (Maybe these should go someplace else? )
		// URLs  (Maybe these should go someplace else? )
		// URLs  (Maybe these should go someplace else? )
		// URLs  (Maybe these should go someplace else? )
		// URLs  (Maybe these should go someplace else? )

		$checkin_post_url = site_url("/ebrevet/post_checkin/$club_acp_code");  // TODO, should go someplace else. Roster model?

		// $route_event_id = "$route_id/$local_event_id";

		$download_url = site_url("recache/$event_code");
		$event_publish_url = site_url("publish/$event_code");
		$event_preview_url = site_url("generate/$event_code");

		$route_manager_url = site_url("route_manager/$event_code");
		$event_info_url = site_url("event_info/$event_code");

		$download_note = 'Download Note';
		$this_organization = $club_name = $club['club_name'];

		$club_event_info_url = $club_event_info_url ?: $event_info_url;  // default

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
		$publish_is_stale = ($has_cuesheet && 
			($published_at_datetime < $last_update_datetime || 
		$published_at_datetime < $last_event_change_datetime));


		if (!empty($route['description']))
			$route_tags = $this->rwgpsLibrary->parse_description($route['description'], $this->rwgpsLibrary->valid_event_description_keys);
		else
			$route_tags = [];


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

		// Only one route_tag supported 
		if(empty($route_tags['pavement_type'])) {
			$pavement_type = ''; // $route['unpaved_pct'] < 1 ? "Less than 1% gravel" : "$unpaved_pct gravel";
		}else{
			$pavement_type = $route_tags['pavement_type'];
		}
		
		$units = $this->unitsLibrary;
		$distance_km = round($route['distance'] / $units::m_per_km, 1);
		$distance_mi = round($distance_km / $units::km_per_mi, 1);
		$climbing_ft = round($route['elevation_gain'] * $units::ft_per_m);


		// RETURN DATA

		$route_has_warnings = (sizeof($controle_warnings) > 0 ||
			sizeof($cue_warnings) > 0 ||
			sizeof($other_warnings) > 0 );

		$edata = compact(
			'checkin_post_url',
			'climbing_ft',
			'club_acp_code',
			'club_event_info_url',
			'club_name',
			'controle_warnings',
			'controls',
			'controls_extra',
			'cue_next_version',
			'cue_url',
			'cue_version_str',
			'cue_version',
			'cue_warnings',
			'cues',
			'df_links_txt',
			'difficulty',
			'distance_km',
			'distance_mi',
			'distance',
			'download_note',
			'download_url',
			'event_code',
			'event_date_str',
			'event_datetime_str_verbose',
			'event_datetime_str',
			'event_datetime',
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
			'event_timezone_name',
			'gravel_distance',
			'has_cuesheet',
			'has_rwgps_route',
			'last_download',
			'last_event_change_datetime',
			'last_event_change_str',
			'last_update',
			'last_update_datetime',
			'local_event_id',
			'now_str',
			'now',
			'organizer_name',
			'organizer_phone',
			'other_warnings',
			'pavement_type',
			'publish_is_stale',
			'published_at_datetime',
			'published_at_str',
			'published_at',
			'roster',
			'route_controles',
			'route_has_warnings',
			'route_id',
			'route_manager_url',
			'route_name',
			'route_tags',
			'route_updated_at',
			'route_url',
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
		$type = $event_type;
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

	protected function die_data_exception($e){
		$error_text = $e->getMessage();
		
		$msg = <<<EOT
<p>At this time complete information
for this event cannot be displayed.</p>
<div class='w3-panel w3-border'>$error_text</div>
EOT;

		$this->die_message('Event Information Unavailable', $msg, ['backtrace' => false]);

	}

}
