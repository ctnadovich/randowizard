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

class RouteManager extends EventProcessor
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

    public function route_manager($event_code = null)
    {

        try {
            extract($this->eventModel->parseEventCode($event_code));


            $this->die_not_admin($club_acp_code);

            $event = $this->eventModel->eventByCode($event_code);
            if (empty($event['route_url'])) $this->die_info(
                'No Route Map URL',
                'Sorry, but you can not use any Route Manager (CueWizard) functions until you add a route URL to your event.'
            );


            // Still needed by views in case we die getting event data
            $edata['event_name_dist'] = $event_name_dist = $this->eventModel->nameDist($event);
            $edata['route_url'] = $route_url = $event['route_url'];
            $edata['download_url'] = $download_url = site_url("recache/$event_code");
            $edata['publish_is_stale'] = null;

            try {
                $edata = $this->get_event_data($event);
                $edata['fatal_route_error'] = null;
            } catch (\Exception $e) {
                // $this->die_exception($e);
                $error_text =  $e->getMessage();
                $edata['fatal_route_error'] = $error_text;
                $edata['route_has_warnings'] = true;
            }

            $this->viewData = array_merge($this->viewData, $edata);

            $this->viewData['warnings_body'] = $this->generate_warnings($edata);

            $view_list = [];

            $view_list[] = ['event_info_panel', [
                'panel_title' => 'Route Overview',
                'panel_data' => view('route_info_table', $this->viewData)
            ]];

            // $view_list[] = ['event_info_panel', ['panel_title' => 'Route Data Validation', 'panel_data' => $this->generate_warnings()]];

            // $view_list[] = ['event_info_panel', ['panel_title' => 'Route Description Tags', 'panel_data' => $this->generate_route_tag_data()]];

            $view_list[] = ['event_info_panel', [
                'panel_title' => 'Route Manager', 'panel_data' => view('fetch_preview_publish', $this->viewData)
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



    private function generate_warnings($edata)
    {

        $warning_body = '';
        extract($edata); // All route_event variables are now local


        if ($route_has_warnings || $publish_is_stale) {
            $warning_body .= <<<EOT
<P class='w3-text-red'>Errors 
were found in the event or the route data.
Fix these Errors and fetch/review/preview/publish again.</p>
EOT;
        }

        if (!empty($fatal_route_error)) {
            $warning_body .= <<<EOT
</h4>FATAL ERROR IN ROUTE/EVENT DATA</h4> 
<div class='w3-panel w3-border'>$fatal_route_error</div>
EOT;
        }


        if (!empty($controle_warnings))
            $warning_body .= ("<h4>ERRORS IN CONTROLS</h4> <ul><li>" . implode('</li><li>', $controle_warnings) . "</li></ul>");
        if (!empty($cue_warnings))
            $warning_body .= ("<h4>ERRORS IN CUES</h4> <ul><li>" . implode('</li><li>', $cue_warnings) . "</li></ul>");
        if (!empty($other_warnings))
            $warning_body .= ("<h4>EVENT ERRORS</h4> <ul><li>" . implode('</li><li>', $other_warnings) . "</li></ul>");


        if ($publish_is_stale) {
            $warning_body .= <<<EOT
<h4>STALE PUBLISHED ROUTE</h4>
<p>Fetched route data was changed after the route was published. Please
publish again so the latest route data becomes live. </p> 
<ul><li>Route last changed $last_update</li><li>Route last published $published_at_str</li></ul>
EOT;
        }

        if (empty($warning_body)) {
            $warning_body .= <<<EOT
<p>No errors or warnings.</p>
EOT;
        }


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
