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

class EventWizard extends EventProcessor
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

    public function event_wizard($event_code = null)
    {

        try {
            extract($this->eventModel->parseEventCode($event_code));

            if ($this->isAdmin($club_acp_code) == false)
                $this->die_message('Access Denied', "Must be logged in as region event administrator to access this function.", ['backtrace' => false]);
            $event = $this->eventModel->eventByCode($event_code);
            if (empty($event['route_url'])) throw new \Exception('NO MAP URL FOR ROUTE');
            $route_url = $event['route_url'];

            try {
                $edata = $this->get_event_data($event);
            } catch (\Exception $e) {
                $error_text = $e->getMessage();
                $msg = <<<EOT
    <h3>Event Info Unavailable</h3>
    <p>I'm very sorry but I'm afraid 
    fatal errors were found in the event data. Processing cannot continue.
    To allow event info to be displayed properly, the event administrator must 
    correct the data in the route map (<A HREF='$route_url'>$route_url</a>), and re-fetch the data
    into the event manager. </p>
    <div class='w3-panel w3-border'>$error_text</div>
    EOT;

                $this->die_message('Error in Event Data', $msg, ['backtrace' => false]);
            }


            $event_name_dist = $edata['event_name'] . ' ' . $edata['distance'] . 'K';
            $this->viewData['event_name_dist'] = $this->viewData['title'] = $this->viewData['subject'] = "$event_name_dist";


            $this->viewData = array_merge($this->viewData, $edata);

            $view_list = [];
            $view_list[] = 'event_head';

            $view_list[] = ['event_info_panel', ['panel_title' => 'Route Data Validation', 'panel_data' => $this->generate_warnings()]];

            $view_list[] = ['event_info_panel', ['panel_title' => 'Route Description Tags', 'panel_data' => $this->generate_route_tag_data()]];

            $view_list[] = ['event_info_panel', [
                'panel_title' => 'Preview/Publish Paperwork', 'panel_data' => view('fetch_preview_publish', $this->viewData)
            ]];

            return $this->load_view($view_list);
        } catch (\Exception $e) {
            $this->die_exception($e);
        }
    }


    private function generate_route_tag_data()
    {

        extract($this->viewData); // All route_event variables are now local

        // Route Description Tags
        $route_tag_data = "<div class=w3-container>";
        if (!empty($route_tags['unrecognized'])) {
            $route_tag_data .= "<p><span class=red>WARNING: Unrecognized tags found in RWGPS route Description.</span>";
            $route_tag_data .= "These tags have been ignored. Please delete them!</p>";
            $route_tag_data .= "<UL><LI>";
            $route_tag_data .= implode('</LI><LI>', $route_tags['unrecognized']);
            $route_tag_data .= "</LI></UL>";

            unset($route_tags['unrecognized']);
        }
        if (empty($route_tags)) {
            $route_tag_data .= "<p>No VALID tags found in route Description.</p>";
        } else {
            $route_tag_data .= "<P>VALID tags found in route Description:</P>";
            $route_tag_data .= $this->format_attributes($route_tags);
        }
        $route_tag_data .= "</div>";


        return $route_tag_data;
    }



    private function generate_warnings()
    {

        $warning_body = '';
        extract($this->viewData); // All route_event variables are now local

        if (sizeof($controle_warnings) > 0)
            $warning_body .= ("</h3>ERRORS IN CONTROLS</h3> <ul><li>" . implode('</li><li>', $controle_warnings) . "</li></ul>");
        if (sizeof($cue_warnings) > 0)
            $warning_body .= ("</h3>ERRORS IN CUES</h3> <ul><li>" . implode('</li><li>', $cue_warnings) . "</li></ul>");

        if (!empty($warning_body)) {
            $warning_body .= <<<EOT
<div class='w3-container w3-red w3-center' style='width: 32%;'>Errors in route data</div>
<p>Fix these Errors by previewing to check, and then publish again.</p>
EOT;
        } else {
            $warning_body .= <<<EOT
<p>No errors or warnings.</p>
EOT;
        }

        if ($route_updated_at > $published_at)
            $warning_body .= "<h3>Stale Published Route</h3><p>Fetched route data is newer than published cuesheets. Don't forget
        to publish again so the latest route data becomes live.</p>";


        return $warning_body;
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
