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
            $this->viewData['status_text']=$status_text;

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


            return $this->load_view($view_list, false);

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
                    $controles_table .= "<TR><TH>Controle</TH><TH>Distance</TH><TH>Open/Close</TH><TH>Location</TH></TR>";
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
                $open_str = $route_controles[$i]['open']->format('m-d H:i');
                $close_str = $route_controles[$i]['close']->format('m-d H:i');
                switch ($style) {
                    case 'wizard':
                        $ca = $this->format_attributes($route_controles[$i]['attributes']);
                        $cn = (isset($route_controles[$i]['n'])) ? $route_controles[$i]['n'] : "";
                        $controles_table .= "<TR><TD>$controle_num$is_start$is_finish</TD><TD>$cd_mi mi<br>$cd_km km</TD><TD>$open_str<br>$close_str</TD><TD>$cn</TD><TD>$ca</TD></TR>";
                        break;
                    case 'info':
                        $cd = $this->format_control_description_table($route_controles[$i]['attributes']);
                        $controles_table .= "<TR><TD>$controle_num$is_start$is_finish</TD><TD>$cd_mi mi<br>$cd_km km</TD><TD>$open_str<br>$close_str</TD><TD>$cd</TD></TR>";
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
}
