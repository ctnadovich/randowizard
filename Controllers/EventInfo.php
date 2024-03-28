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

class EventInfo extends EventProcessor
{

    public $unitsLibrary;
    public $cuesheetLibrary;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->unitsLibrary = new \App\Libraries\Units();
        $this->cuesheetLibrary = new \App\Libraries\Cuesheet();
    }


    ////////////////////////////////////////////////////////////
    // 
    // EVENT INFO
    //

    public function event_info($event_code = null)
    {

        try {


            $event = $this->eventModel->eventByCode($event_code);

            if (empty($event['route_url'])) throw new \Exception('NO MAP URL FOR ROUTE.');
            $route_url = $event['route_url'];

            $edata = $this->get_event_data($event);
        } catch (\Exception $e) {
            $this->die_data_exception($e);
        }

        try {
            $this->viewData = array_merge($this->viewData, $edata);

            // $event_name_dist = $edata['event_name'] . ' ' . $edata['distance'] . 'K';
            $this->viewData['title'] = $this->viewData['subject'] = $this->viewData['event_name_dist'];

            if ($this->eventModel->statusQ($event, 'canceled')) $status_text = "THIS EVENT IS CANCELED";
            elseif ($this->eventModel->statusQ($event, 'suspended')) $status_text = "THIS EVENT IS SUSPENDED";
            elseif ($this->eventModel->statusQ($event, 'hidden')) $status_text = "THIS EVENT IS HIDDEN";
            elseif ($this->eventModel->isUnderwayQ($event)) $status_text = "THIS EVENT IS UNDERWAY!";
            else $status_text = '';
            $this->viewData['status_text'] = $status_text;

            $start_control = reset($edata['controls_extra']);
            $finish_control = end($edata['controls_extra']);
            $eventTimezoneName = $edata['event_timezone_name'];
            $start_open_datetime = $start_control['open_datetime'];
            $finish_close_datetime = $finish_control['close_datetime'];
            $fcdt_copy = clone $finish_close_datetime;
            $fcdt_copy->setTimezone($edata['event_tz']);
            $cutoff_datetime_str = $fcdt_copy->format('m-d, g:i A T');

            $cutoff_interval = $start_open_datetime->diff($finish_close_datetime);
            $dhm = [];
            $tothours = $cutoff_interval->h + $cutoff_interval->d * 24;
            if ($tothours >  1)  $dhm[] = $tothours . ' hours';
            if ($tothours == 1)  $dhm[] = $tothours . ' hour';
            if ($cutoff_interval->i >  1)  $dhm[] = $cutoff_interval->i . ' minutes';
            if ($cutoff_interval->i == 1)  $dhm[] = $cutoff_interval->i . ' minute';
            $cutoff_interval_str = implode(', ', $dhm);

            $this->viewData = array_merge(
                $this->viewData,
                $this->sunrise_sunset(
                    $start_open_datetime,
                    $finish_close_datetime,
                    $start_control['lat'],
                    $start_control['long'],
                    $eventTimezoneName
                )
            );

            $this->viewData = array_merge($this->viewData,  compact('cutoff_interval_str', 'cutoff_datetime_str'));

            $view_list = [];
            $view_list[] = 'event_head';
            $view_list[] = 'tab_bar';

            $view_list[] = ['event_info_tab', [
                'tab_id' => 'General-Info',
                'default_tab' => true,
                'panel_title' => 'Event Overview',
                'panel_data' => view('event_basic_info_table', $this->viewData)
            ]];

            $view_list[] = ['event_info_tab', [
                'tab_id' => 'GPS-Info',
                'panel_title' => 'Navigation Data',
                'panel_data' => view('event_gps_nav_table', $this->viewData)
            ]];

            $view_list[] = ['event_info_tab', [
                'tab_id' => 'Control-Info',
                'panel_title' => 'Controls',
                'panel_data' => $this->make_controles_table((false) ? 'wizard' : 'info')
            ], ['saveData' => false]];

            $view_list[] = ['event_info_tab', [
                'tab_id' => 'Roster-Info',
                'panel_title' => 'Roster',
                'panel_data' => $this->make_roster_table($edata)
            ], ['saveData' => false]];

            $view_list[] = ['event_info_tab', [
                'tab_id' => 'Checkin-Info',
                'panel_title' => 'Check Ins',
                'panel_data' => $this->make_checkin_table($edata)
            ], ['saveData' => false]];


            return $this->load_view($view_list, $edata['club_acp_code']);
        } catch (\Exception $e) {
            $this->die_exception($e);
        }
    }



    private function make_controles_table($style = 'info')
    {

        extract($this->viewData); // All route_event variables are now local

        $controles_table = "";
        $ncontroles = count($controls);
        if ($ncontroles > 0) {
            $controles_table = "<TABLE class='w3-table-all w3-padding'>";

            switch ($style) {
                case 'wizard':
                    $controles_table .= "<TR><TH>Controle</TH><TH>Distance</TH><TH>Open/Close</TH><TH>Note</TH><TH>Description (attributes)</TH></TR>";
                    break;
                case 'info':
                    $controles_table .= "<TR><TH>Controle</TH><TH>Distance</TH><TH>Open/Close</TH><TH>Location</TH><TH>Map</TH></TR>";
                    break;
                default:
                    $this->die_message(__METHOD__, 'Unknown control table style.');
            }

            $reclass = $this->unitsLibrary;


            for ($i = 0; $i < $ncontroles; $i++) {
                $cd_mi = $controls[$i]['dist_mi']; // /($reclass::m_per_km*$reclass::km_per_mi),1);
                $cd_km = $controls[$i]['dist_km']; // /($reclass::m_per_km),1);
                $is_start = (isset($route_controles[$i]['start'])) ? " [START]" : "";
                $is_finish = (isset($route_controles[$i]['finish'])) ? " [FINISH]" : "";
                $controle_num = $i + 1;


				$open_daytime_o = $route_controles[$i]['open'];
				$open_daytime = clone $open_daytime_o;
				$open_daytime->setTimezone($event_tz);
				$open_str = $open_daytime->format('m-d H:i');

				$close_daytime_o = $route_controles[$i]['close'];
				$close_daytime = clone $close_daytime_o;
				$close_daytime->setTimezone($event_tz);
				$close_str = $close_daytime->format('m-d H:i');


                //$open_str = $route_controles[$i]['open']->format('m-d H:i');
                //$close_str = $route_controles[$i]['close']->format('m-d H:i');
                $lat = $controls_extra[$i]['lat'] ?? 40;
				$long = $controls_extra[$i]['long'] ?? -75;
                $maplink = "<A HREF='https://maps.google.com/?q=$lat,$long'><i style='font-size: 1.4em;' class='fa-solid fa-map-location-dot'></i></A>";


                switch ($style) {
                    case 'wizard':
                        $ca = $this->format_attributes($route_controles[$i]['attributes']);
                        $cn = (isset($route_controles[$i]['n'])) ? $route_controles[$i]['n'] : "";
                        $controles_table .= "<TR><TD>$controle_num$is_start$is_finish</TD><TD>$cd_mi mi<br>$cd_km km</TD><TD>$open_str<br>$close_str</TD><TD>$cn</TD><TD>$ca</TD></TR>";
                        break;
                    case 'info':
                        $cd = $this->format_control_description_table($route_controles[$i]['attributes']);
                        $controles_table .= "<TR><TD>$controle_num$is_start$is_finish</TD><TD>$cd_mi mi<br>$cd_km km</TD><TD>$open_str<br>$close_str</TD><TD>$cd</TD><TD>$maplink</TD></TR>";
                        break;
                }
            }
            $controles_table .= "</TABLE>";
        }
        return $controles_table;
    }

    private function format_control_description_table($alist)
    {
        $cdt = "<div style='font-size: .7em; font-family: Arial, Helvetica, sans-serif'>";
        $cd_field = ['name', 'address', 'style'];
        foreach ($cd_field as $k) {
            $v = (array_key_exists($k, $alist)) ? $alist[$k] : "";
            if ($k == 'style') $v = strtoupper($v);
            $n = ucfirst($k);
            $cdt .= "$v<BR>";
        }
        $cdt .= "</div>";
        return $cdt;
    }


    private function sunrise_sunset($start_datetime, $cutoff_datetime, $lat, $long, $timezoneId)
    {
        $sun_info = date_sun_info($start_datetime->getTimestamp(), $lat, $long); // Easton, PA
        date_default_timezone_set($timezoneId);
        $data['sunrise_str'] = date('H:i', $sun_info['sunrise']);
        $data['sunset_str'] =  date('H:i', $sun_info['sunset']);
        $data['riding_at_night'] =
            $sun_info['sunrise'] > $start_datetime->getTimestamp() ||
            $sun_info['sunset']  < $cutoff_datetime->getTimestamp();

        return $data;
    }
}
