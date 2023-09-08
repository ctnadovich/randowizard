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

class EventInfo extends Ebrevet
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

    public function event_info($event_code = null, $wizard_parm = null)
    {

        try {

            $wizard = ($wizard_parm == 'wizard');

            if ($wizard && $this->isAdmin() == false)
                $this->die_message('Access Denied', 'Must be logged in as region administrator to access this function.');

            $event = $this->eventModel->eventByCode($event_code);
            $edata = $this->get_event_data($event);


            $event_name_dist = $edata['event_name'] . ' ' . $edata['distance'] . 'K';
            $this->viewData['event_name_dist'] = $this->viewData['title'] = $this->viewData['subject'] = "$event_name_dist";


            $this->viewData = array_merge($this->viewData, $edata);
        } catch (\Exception $e) {
            $this->die_exception($e);
        }

        $view_list = [];
        $view_list[] = 'event_head';
        $view_list[] = 'tab_bar';
        $view_list[] = ['event_info_tab', [
            'tab_id' => 'General-Info',
            'default_tab' => true,
            'panel_title' => 'General Event Info',
            'panel_data' => view('event_basic_info_table', $this->viewData)
        ]];
        $view_list[] = ['event_info_tab', [
            'tab_id' => 'GPS-Info',
            'panel_title' => 'GPS Navigation Data',
            'panel_data' => view('event_gps_nav_table', $this->viewData)
        ]];
        $view_list[] = ['event_info_tab', [
            'tab_id' => 'Control-Info',
            'panel_title' => 'Controls',
            'panel_data' => $this->make_controles_table((false) ? 'wizard' : 'info')
        ], ['saveData' => false]];
        $view_list[] = ['event_info_tab', [
            'tab_id' => 'Cuesheet-Info',
            'panel_title' => 'Cue Sheets', 
            'panel_data' => $this->generate_cuesheet_info()]];

       


        $view_list[] = ['event_info_panel', ['panel_title' => 'Route Description Tags', 'panel_data' => $this->generate_route_tag_data()]];

        $view_list[] = ['event_info_panel', ['panel_title' => 'Route Data Validation', 'panel_data' => $this->generate_warnings()]];

        $view_list[] = ['event_info_panel', [
            'panel_title' => 'Preview/Publish Paperwork', 'panel_data' => view('fetch_preview_publish', $this->viewData)
        ]];

        // $view_list[] = ['event_info_panel', ['panel_title' => 'Dowload and Publish', 'panel_data' => $this->generate_buttons()]];

        $view_list[] = 'event_foot';

        return $this->load_view($view_list);
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

    private function generate_cuesheet_info()
    {
        extract($this->viewData); // All route_event variables are now local

        $cue_sheet_info = "";

        // No Cues
        if ($has_cuesheet === false) {
            $cue_sheet_info .= "<p>At this time no cue sheet is available for this event.</p>";
            $this->viewData['published_at'] = $published_at = 0;
            $this->viewData['cuesheet_gentime_str'] = $cuesheet_gentime_str = "Never";

            // Cue Wizard Cuesheet
        } else {

            $cue_basename = "$event_tagname-CueSheetV$cue_version";
            $csv_filename = $this->cuesheetLibrary->cuesheet_path . "/" . $cue_basename . ".csv";
            if (file_exists($csv_filename))
                $this->viewData['published_at'] = $published_at = filectime($csv_filename);
            else
                $this->viewData['published_at'] = $published_at = 0;

            if ($cue_version > 0 && $published_at > 0) {
                date_default_timezone_set($event_tzname);
                $this->viewData['cuesheet_gentime_str'] = $cuesheet_gentime_str = date("Y-m-j H:i:s T", $published_at);
                $this->viewData['cuesheet_version_str'] = $cuesheet_version_str = "$cue_version";
            } else {
                $this->die_message(__METHOD__, "This can't happen. Cuesheet unexpectedly missing. CSV File: $csv_filename");
            }

            $cuesheet_url_P = $this->cuesheetLibrary->cuesheet_baseurl . "/" . $cue_basename . "-P.pdf";
            $cuesheet_url_L = $this->cuesheetLibrary->cuesheet_baseurl . "/" . $cue_basename . "-L.pdf";
            $cuesheet_url_C = $this->cuesheetLibrary->cuesheet_baseurl . "/" . $cue_basename . ".csv";
            $cue_sheet_info .= <<<EOT
<div class='narrower routeinfo'><TABLE WIDTH=100%>
<TR><TD>Cuesheet Generated</TD><TD>$cuesheet_gentime_str</TD></TR>
<TR><TD>Cuesheet Version</TD><TD>$cue_version</TD></TR>
</TABLE></DIV>
EOT;
            $buttons = "<A HREF='$cuesheet_url_P' CLASS='w3-btn'>Cue Sheet (Portrait)</A>";
            $buttons .= "<A HREF='$cuesheet_url_L' CLASS='w3-btn'>Cue Sheet (Landscape)</A>";
            $buttons .= "<A HREF='$cuesheet_url_C' CLASS='w3-btn'> Cue Sheet (CSV)</A>";
            $cue_sheet_info .= "<div class='w3-container'>$buttons</DIV>";
        }


        // More simplified version for wizards

        if (false) {
            $cue_sheet_info .= "<H4>PUBLISHED CUE SHEETS</h4>";
            if ($has_cuesheet === false) {
                $cue_sheet_info .= "<p>At this time no cue sheet is available for this event.</p>";
            } else {
                $cue_sheet_info .= <<<EOT
<div class='narrower routeinfo'><TABLE WIDTH=100%>
<TR><TD>Last Published</TD><TD>$cuesheet_gentime_str</TD></TR>
<TR><TD>Published Version</TD><TD>$cue_version_str</TD></TR>
<TR><TD>Published Cue Files</TD><TD><A HREF=$cuesheet_url_P>Portrait</A>, 
<A HREF=$cuesheet_url_L>Landscape</A>, <A HREF=$cuesheet_url_C>CSV</A></TD></TR>
</TABLE></DIV>
EOT;
            }
        }

        return $cue_sheet_info;
    }

    private function generate_warnings()
    {
        extract($this->viewData); // All route_event variables are now local

        if ($route_updated_at > $published_at)
            $warnings[] = "Route data newer than published cuesheets.";

        if (!empty($warnings)) {
            $cw = implode('</li><li>', $warnings);
            $warning_body = <<<EOT
<div class="w3-panel w3-red">
<p>Errors found in the route data</p>
<ul><li>$cw</li></ul>
<p>Fix these Errors by previewing to check, and then publish again.</p>
</div>
EOT;
        } else {
            $warning_body = <<<EOT
<p>No errors or warnings.</p>
EOT;
        }

        return $warning_body;
    }


    private function generate_map()
    {
        extract($this->viewData); // All route_event variables are now local
        /*
    // MAP and Elevation Profile
    $map_divid = 'randomap'; // must match ID in randomap.css
    $graph_divid = 'epdivid'; // must match ID in randomap.css
    $route = $route_event['route'];
    $map_script = $this->map->generate_map_script($route, $controles, $map_divid);
    $ep_script = $this->map->generate_ep_script($route, $controles, $graph_divid);
    $map_body = <<<EOT
<H4>MAP</H4>
<div class='narrower text-center'>
<div id=$map_divid></div>
<A HREF=$route_url>View in Ride With GPS</a>
<div class=bigskip></div>
<div id=$graph_divid style="margin-left:10%; margin-right:10%; width:80%; height:180px;"></div>
</div>
$map_script
$ep_script
EOT;

*/
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
