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
	protected $rusaModel;

	protected $rwgpsLibrary;
	protected $controletimesLibrary;
	protected $cuesheetLibrary;
	protected $unitsLibrary;
	protected $cryptoLibrary;

	public function initController(
		RequestInterface $request,
		ResponseInterface $response,
		LoggerInterface $logger
	) {
		parent::initController($request, $response, $logger);

		$this->eventModel = model('Event');
		$this->checkinModel = model('Checkin');
		$this->rosterModel = model('Roster');
		$this->rusaModel = model('Rusa');

		$this->unitsLibrary = new \App\Libraries\Units();
		$this->rwgpsLibrary = new \App\Libraries\Rwgps();
		$this->cuesheetLibrary = new \App\Libraries\Cuesheet();
		$this->controletimesLibrary = new \App\Libraries\Controletimes();
		$this->cryptoLibrary = new \App\Libraries\Crypto();
	}

	public $minimum_app_version = '1.3.3';



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

		if (empty($event['route_url'])) $this->die_info(
			'No Route Map URL',
			'Sorry, but you can not use any Route Manager (CueWizard) functions until you add a route URL to your event.'
		);

		// if (empty($event['route_url']))
		// 	throw new \Exception('NO ROUTE FOR EVENT. You must specify a URL for the event route map.');

		$club = $this->regionModel->getClub($club_acp_code);
		if (empty($club)) {
			throw new \Exception("Fatal Error in Event ID=$event_code: UNKNOWN CLUB");
		}

		// Try to get route data
		$route_url = $event['route_url'];
		$route_id = $this->rwgpsLibrary->extract_route_id($route_url);

		if ($route_id == null) {
			throw new \Exception("Fatal Error in Event ID=$event_code: INVALID ROUTE URL: $route_url");
		} else {
			$route = $this->rwgpsLibrary->get_route($route_id);
			$has_rwgps_route = true;
			$rwgps_url = $this->rwgpsLibrary->make_route_url($route_id);
		}

		// And now the controls

		list($route_controles, $controle_warnings) = $this->rwgpsLibrary->extract_controles($route);

		if (sizeof($route_controles) == 0) {
			throw new \Exception("Fatal Error in Event ID=$event_code: NO CONTROLS. Please mark all the controls with the cue-type 'CONTROL'.");
		}

		list($cues, $cue_warnings) = $this->rwgpsLibrary->extract_cues($route);

		if (sizeof($cues) == 0) {
			throw new \Exception("Fatal Error in Event ID=$event_code: NO CUES");
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
		$event_type = strtolower($event['type']);
		$event_sanction = strtolower($event['sanction']);
		$event_type_uc = strtoupper($event_type);
		$route_event = compact('route', 'event', 'event_datetime', 'event_datetime_str', 'event_type', 'event_sanction', 'event_distance', 'event_gravel_distance', 'event_tz');

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

			$control_style = $a['style'] ?? 'undefined';
			switch ($control_style) {
				case 'open':
				case 'merchant':
				case 'staffed':
					if (array_key_exists('timed', $a) && strtolower($a['timed']) == 'no') {
						$timed = 'no';
					} else {
						$timed = 'yes';
					}
					break;
				default:
					$timed = 'no';
					break;
			}

			$controls[] = [
				'dist_mi' => $cd_mi,
				'dist_km' => $cd_km,
				'long' => $cdata['x'],
				'lat' => $cdata['y'],
				'name' => $a['name'] ?? 'CONTROL NAME MISSING',
				'style' => $a['style'] ?? 'undefined',
				'address' => $a['address'] ?? 'CONTROL ADDRESS MISSING',
				'timed' => $timed,
				'open' => $openDatetime->format('c'),
				'close' => $closeDatetime->format('c')
				// 'question'=>$question,
				// 'sif'=>$sif

			];
			$open_datetime = $openDatetime;
			$close_datetime = $closeDatetime;
			$controls_extra[] = compact('open_datetime', 'close_datetime', 'lat', 'long');
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
		$event_description = $event['description'] ?? '';

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
		if (empty($route_tags['pavement_type'])) {
			$pavement_type = ''; // $route['unpaved_pct'] < 1 ? "Less than 1% gravel" : "$unpaved_pct gravel";
		} else {
			$pavement_type = $route_tags['pavement_type'];
		}

		$units = $this->unitsLibrary;
		$distance_km = round($route['distance'] / $units::m_per_km, 1);
		$distance_mi = round($distance_km / $units::km_per_mi, 1);
		$climbing_ft = round($route['elevation_gain'] * $units::ft_per_m);


		// RETURN DATA

		$route_has_warnings = (sizeof($controle_warnings) > 0 ||
			sizeof($cue_warnings) > 0 ||
			sizeof($other_warnings) > 0);

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


	protected function make_roster_table($edata)
	{

		$local_event_id = $edata['local_event_id'];
		$club_acp_code = $edata['club_acp_code'];

		$is_rusa = $this->regionModel->hasOption($club_acp_code, 'rusa');

		$registeredRiders = $this->rosterModel->registered_riders($local_event_id);
		$roster_table = "<TABLE CLASS='w3-table-all w3-centered'>";

		$roster_table .= "<TR class='w3-dark-gray'><TH>Rider</TH>";
		$roster_table .= $is_rusa ? "<TH>Address</TH>" : ""; // <TH>ID Number</TH><TH>Status</TH></TR>";
		$roster_table .= "<TH>ID Number</TH><TH>Status</TH></TR>";


		foreach ($registeredRiders as $rider) {

			$rider_id = $rider['rider_id'];
			// Assume $rider_id = $rusa_id; // assumption

			if ($is_rusa) {
				// lets hope rusa.org sanitizes rider id parameters
				$rider_id_txt = "<A HREF='https://rusa.org/cgi-bin/membersearch_PF.pl?mid=$rider_id'>$rider_id</A>";

				$m = $this->rusaModel->get_member($rider_id);
				if (empty($m)) {
					$first_last = "NON RUSA";
				} else {
					$first_last = $m['first_name']  . ' ' . $m['last_name'];
				}
			} else {
				$m = [];
				$rider_id_txt = $rider_id;
				$first_last = $rider['first_name'] . ' ' . $rider['last_name'];
			}

			$city = $m['city'] ?? '';
			$state = $m['state'] ?? '';
			$city_state = (!empty($city) && !empty($state)) ? "$city, $state" : "$city$state";
			$country = $m['country'] ?? '';
			$address_rusa = (!empty($country) && $country != 'US') ? "$city_state ($country)" : $city_state;
			$address = $is_rusa ? $address_rusa : "";

			$r = $this->rosterModel->get_record($local_event_id, $rider_id);

			if (empty($r)) { // $this->die_message('ERROR', "Rider ID=$rider_id seen in event=$local_event_id but not found in roster.");
				$r['result'] = 'NOT IN ROSTER';
			}

			$rider_result = strtoupper($r['result'] ?? '');
			$rider_elapsed = $r['elapsed_time'] ?? '';

			if (!empty($rider_elapsed)) {
				$parts = explode(':', $rider_elapsed);
				$hhmm = implode(':', array_slice($parts, 0, 2));
				$rider_elapsed = "[$hhmm]";
			}

			if ($rider_result == 'FINISH')
				$rider_status = "$rider_result $rider_elapsed";
			else
				$rider_status = $rider_result;


			$rider_highlight = "";


			$roster_table .= "<TR><TD>$first_last</TD>";
			$roster_table .= $is_rusa?"<TD>$address</TD>":"";
			$roster_table .= "<TD>$rider_id_txt</TD><TD>$rider_status</TD></TR>";
		}
		$roster_table .= "</TABLE>";
		return $roster_table;
	}


	protected function make_checkin_table($edata)
	{
		$checkin_table = "<TABLE CLASS='w3-table-all w3-centered'>";

		$gravel_distance = $edata['gravel_distance'];
		$is_gravel = $gravel_distance > 0 ? true : false;

		$controles = $edata['controls'];
		$controles_extra = $edata['controls_extra'];
		$ncontroles = count($controles);

		$club_acp_code = $edata['club_acp_code'];
		$local_event_id = $edata['local_event_id'];
		$event_code = $edata['event_code'];
		$epp_secret = $edata['epp_secret'];

		$is_rusa = $this->regionModel->hasOption($club_acp_code, 'rusa');

		$reclass = $this->unitsLibrary;

		$is_untimed = [];
		$headlist = [];
		$controle_num = 0;
		foreach ($controles as $c) {
			$cd_mi = $c['dist_mi'] . " mi";
			$cd_km = $c['dist_km'];
			$is_start = isset($c['start']);
			$is_finish = isset($c['finish']);
			$is_intermediate = !$is_start && !$is_finish;

			$lat = $controles_extra[$controle_num]['lat'];
			$long = $controles_extra[$controle_num]['long'];

			$open_datetime = (new \DateTime($c['open']))->setTimezone(new \DateTimeZone($edata['event_timezone_name']));
			$close_datetime = (new \DateTime($c['close']))->setTimezone(new \DateTimeZone($edata['event_timezone_name']));
			$open = $open_datetime->format('D-H:i');
			$close = $close_datetime->format('D-H:i');


			$style = $c['style'];
			$really_timed = strtolower($c['timed'] ?? 'yes');
			$is_untimed[$controle_num] = $is_intermediate &&
				($is_gravel || ($style == 'info' || $style == 'photo' || $style == 'postcard') || ($really_timed == 'no'));
			$close = $is_untimed[$controle_num] ? 'Untimed' : $close_datetime->format('D-H:i');

			$controle_num++;
			$number = ($is_start) ? "START" : (($is_finish) ? "FINISH" : "Control $controle_num");

			// $close = $c['close']; // ->format('D-H:i');
			$name = $c['name'];
			$headlist[] = compact('number', 'cd_mi', 'cd_km', 'is_start', 'is_finish', 'open', 'close',  'name', 'lat', 'long');
		}

		$headlist = $this->flipDiagonally($headlist);

		foreach ($headlist as $key => $row) {
			if ($key == 'name') {
				for ($i = 0; $i < $controle_num; $i++)
					$row[$i] .= "<br><A HREF='https://maps.google.com/?q=" .
						$headlist['lat'][$i] . ',' . $headlist['long'][$i] . "'><i style='font-size: 1.4em;' class='fa-solid fa-map-location-dot'></i></A>";
			}

			if ($key == 'open_datetime' || $key == 'close_datetime') {
				$head_row[$key] = $row;
			} else {
				$head_row[$key] = '<TH></TH><TH>' . implode('</TH><TH>', $row) . '</TH>';
			}
		}
		$checkin_table .= "<TR class='w3-dark-gray'>" . $head_row['number'] . "<TH>Final</TH></TR>";
		$checkin_table .= "<TR class='w3-light-gray' style='font-size: 0.7em;'>" . $head_row['name'] . "<TH></TH></TR>";
		$checkin_table .= "<TR class='w3-light-gray'>" . $head_row['cd_mi'] . "<TH></TH></TR>";
		$checkin_table .= "<TR class='w3-light-gray'>" . $head_row['close'] . "<TH></TH></TR>";


		if ($is_rusa) {
			$registeredRiders = $this->rosterModel->registered_rusa_riders($local_event_id);
		} else {
			$registeredRiders = $this->rosterModel->registered_riders($local_event_id);
		}


		foreach ($registeredRiders as $rider) {

			$rider_id = $rider['rider_id'];
			// Assume $rider_id = $rusa_id; // assumption

			if ($is_rusa) {
				$m = $this->rusaModel->get_member($rider_id);
				if (empty($m)) {
					$first_last = "NON RUSA";
				} else {
					$first_last = $m['first_name']  . ' ' . $m['last_name'];
				}
			} else {
				$first_last = $rider['first_name'] . ' ' . $rider['last_name'];
			}


			$rider = "$first_last ($rider_id)";


			$r = $this->rosterModel->get_record($local_event_id, $rider_id);

			if (empty($r)) { // $this->die_message('ERROR', "Rider ID=$rider_id seen in event=$local_event_id but not found in roster.");
				$r['result'] = 'NOT IN ROSTER';
			}

			$rider_highlight = "";

			switch ($r['result']) {
				case 'finish':
					$elapsed_array = explode(':', $r['elapsed_time'], 3);
					if (count($elapsed_array) == 3) {
						list($hh, $mm, $ss) = $elapsed_array;
						$elapsed_hhmm =  "$hh$mm";
						$global_event_id = $event_code;
						$d = compact('elapsed_hhmm', 'global_event_id', 'rider_id');
						$finish_code = $this->cryptoLibrary->make_finish_code($d, $epp_secret);

						$finish_text = $hh .  "h&nbsp;" . $mm . "m";

						if ($this->isAdmin($club_acp_code)) {
							$finish_text .= "<br>($finish_code)";
						}
					} else {
						$finish_text = "RBA Review";
					}
					break;
				default:
					$finish_text = strtoupper($r['result']);
					break;
			}



			$checklist = [];
			$has_no_checkins = true;
			for ($i = 0; $i < $ncontroles; $i++) {
				$open = $controles[$i]['open'];
				$close = $controles[$i]['close'];
				$open_datetime = (new \DateTime($open))->setTimezone(new \DateTimeZone($edata['event_timezone_name']));
				$close_datetime = (new \DateTime($close))->setTimezone(new \DateTimeZone($edata['event_timezone_name']));

				$control_index = $i;
				$d = compact('control_index', 'event_code', 'rider_id');
				$checkin_code = $this->cryptoLibrary->make_checkin_code($d, $epp_secret);


				$c = $this->checkinModel->get_checkin($local_event_id, $rider_id, $i, $edata['event_timezone_name']);
				if (empty($c)) {
					if ($this->isAdmin($club_acp_code)) {
						$checklist[] = "<span title='$checkin_code'>-</span>";
					} else {
						$checklist[] = '-';
					}
				} else {

					$has_no_checkins = false;

					$checkin_time = $c['checkin_time'];  // a DateTime object
					$comment = $c['comment'] ?? '';
					if (false !== strpos(strtolower($comment), 'automatic check in')) $comment = '';


					$el = "";
					if ($c['preride']) {
						$el = "<br><span class='green italic sans smaller'>Preride</span>";
					} elseif ($checkin_time < $open_datetime && !$is_untimed[$i]) {
						$cit_str = $checkin_time->format('H:i');
						$open_str = $close_datetime->format('H:i');
						$el = "<br><span class='red italic sans smaller'>EARLY!</span>";
					} elseif ($checkin_time > $close_datetime && !$is_untimed[$i]) {
						$cit_str = $checkin_time->format('H:i');
						$close_str = $close_datetime->format('H:i');
						$el = "<br><span class='red italic sans smaller'>LATE! $cit_str &gt; $close_str</span>";
					}

					// $control_index = $i;
					// $d = compact('control_index', 'event_code', 'rider_id');
					// $checkin_code = $this->cryptoLibrary->make_checkin_code($d, $epp_secret);

					// if ($this->isAdmin($club_acp_code)) {
					// 	$el .= "&nbsp; <i title='$checkin_code' class='fa fa-check-circle' style='color: #355681;'></i>";
					// }

					// && false===strpos(strtolower($comment), 'automatic check in')
					if (!empty($comment)) {

						// $el .= "<br><div style='font-size: .5em; width: 70%; margin-left: 15%' class='speech-bubble''>". wordwrap($comment, 20, '<br>', true) . "</div>";
						$el .= "<br><div style='font-size: .5em; width: 70%; margin-left: 15%' class='w3-container w3-border w3-light-grey w3-round-large'>" . wordwrap($comment, 20, '<br>', true) . "</div>";

						// $el .= "<br><div style='width: 70%; margin: auto; font-size: .62em; font-weight: bold; font-style: italic; background-color: #E0E0FF; border-radius: .66em; font-family: Arial, Helvetica, sans-serif;'>". wordwrap($comment, 20, '<br>', true) . "</div>";

						// $el .= "&nbsp; <i title='$comment' class='fa fa-comment' style='color: #355681;'></i>";
					}

					$checkin_time_str = $checkin_time->format('H:i');

					if ($this->isAdmin($club_acp_code)) {
						$checkin_time_str = "<span title='$checkin_code'>$checkin_time_str</span>";
					}

					$checklist[] = $checkin_time_str . $el;
				}
			}




			if ($has_no_checkins) continue;


			$checkins = implode('</TD><TD>', $checklist);



			$checkin_table .= "<TR><TD>$rider</TD><TD>$checkins</TD><TD>$finish_text</TD></TR>";
		}

		$checkin_table .= "</TABLE>";




		return $checkin_table;
	}

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

	protected function die_data_exception($e)
	{
		$error_text = $e->getMessage();

		$msg = <<<EOT
<p>At this time complete information
for this event cannot be displayed.</p>
<div class='w3-panel w3-border'>$error_text</div>
EOT;

		$this->die_message('Event Information Unavailable', $msg, ['backtrace' => false]);
	}
}
