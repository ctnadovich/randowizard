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
            $this->viewData['title'] = $this->viewData['subject'] = "$event_name_dist";


            $this->viewData = array_merge($this->viewData, $edata);


            $this->viewData['body'] = $this->generate_view_html($wizard);
            return $this->load_view(['head', 'event_info', 'foot']);
        } catch (\Exception $e) {
            $status = $e->GetMessage();

            $this->die_message('ERROR', $status);
        }
    }


    private function generate_view_html($wizard = false)
    {

        extract($this->viewData); // All route_event variables are now local


        // Route Description Tags
        if ($wizard) {
            $route_tag_data = ""; // "<H4>DESCRIPTION TAGS</H4>";
            if (!empty($route_tags['unrecognized'])) {
                $route_tag_data .= "<span class=red>WARNING: Unrecognized tags found in RWGPS route Description.</span> These tags have been ignored. Please delete them!<br/><div class='half-width-indented bordered'>";
                $route_tag_data .= implode(', ', $route_tags['unrecognized']);
                $route_tag_data .= "</div>";
                unset($route_tags['unrecognized']);
            }
            if (empty($route_tags)) {
                $route_tag_data .= "<p>No VALID tags found in route Description. (Consider adding #pavement_type=whatever to the RWGPS route Description)</p>";
            } else {
                $route_tag_data .= "VALID tags found in route Description:<br/><div class=vspace></div><div class='indent smaller tt'>";
                $route_tag_data .= $this->format_attributes($route_tags);
                $route_tag_data .= "</div>";
            }
        } else {
            $route_tag_data = "";
        }

        // CUESHEETS CUESHEETS CUESHEETS CUESHEETS CUESHEETS CUESHEETS CUESHEETS CUESHEETS CUESHEETS 

        $cue_sheet_info = "<H4>CUE SHEETS</h4>";

        // No Cues
        if ($has_cuesheet === false) {
            $cue_sheet_info .= "<p>At this time no cue sheet is available for this event.</p>";
            $published_at = 0;
            $cuesheet_gentime_str = "Never";

            // Cue Wizard Cuesheet
        } elseif ($cuesheet_location == 'auto') {

            $cue_basename = "$event_tagname-CueSheetV$cue_version";
            $csv_filename = $this->cuesheetLibrary->cuesheet_path . "/" . $cue_basename . ".csv";
            if (file_exists($csv_filename))
                $published_at = filectime($csv_filename);
            else
                $published_at = 0;

            if ($cue_version > 0 && $published_at > 0) {
                date_default_timezone_set($event_tzname);
                $cuesheet_gentime_str = date("Y-m-j H:i:s T", $published_at);
                $cuesheet_version_str = "$cue_version";
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
            $buttons = "<A HREF=$cuesheet_url_P>Cue Sheet (Portrait)</A>";
            $buttons .= "<A HREF=$cuesheet_url_L>Cue Sheet (Landscape)</A>";
            $buttons .= "<A HREF=$cuesheet_url_C>Cue Sheet (CSV)</A>";
            $cue_sheet_info .= "<div class='button-container text-center'>$buttons</DIV>";

            // Static cue link
        } else {
            $cue_sheet_info .= <<<EOT
<p>A legacy Cuesheet is available for this event.
<div class='narrower routeinfo'><TABLE WIDTH=100%>
<TR><TD>Cuesheet</TD><TD><A HREF=$cuesheet_url>$cuesheet_url</a></TD></TR>
</TABLE></DIV>
EOT;
            $buttons = "<A HREF=$cuesheet_url>Download Cue Sheet</A>";
            $cue_sheet_info .= "<div class='button-container text-center'>$buttons</DIV>";
        }




        // More simplified version for wizards

        if ($wizard) {
            $cue_sheet_info .= "<H4>PUBLISHED CUE SHEETS</h4>";
            if ($has_cuesheet === false) {
                $cue_sheet_info .= "<p>At this time no cue sheet is available for this event.</p>";
            } elseif ($cuesheet_location != 'auto') {
                $cue_sheet_info .= "<p>WARNING: A Legacy Cue Sheet is available for this route.</p>";
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


        // Controle table
        $ncontroles = count($controls);
        $control_table_style = ($wizard) ? 'wizard' : 'info';
        $controles_table = $this->make_controles_table($controls, $route_controles, $control_table_style);


        return $route_tag_data . $cue_sheet_info . $controles_table;


        if ($wizard) {
            $wizard_controles_table = $controles_table;
            $user_controles_table = "";
        } else {
            $wizard_controles_table = "";
            $user_controles_table = $controles_table;
        }


        // Wizard Text

        $wizard_body = "";
        $warning_body = "";
        $download_body = "";
        $generate_form = "";

        if ($wizard) {

            $wizard_body = "<H3>CUE WIZARD</H3>";

            if ($updated_at > $published_at)
                $warnings[] = "Route data newer than published cuesheets.";

            if (!empty($warnings)) {
                $cw = implode('</li><li>', $warnings);
                $warning_body = <<<EOT
<h4>ROUTE DATA VALIDATION</H4>
<div class="bordered narrower sans red">
<div class="vspace"></div>
<p>Errors found in the route data</p>
<ul><li>$cw</li></ul>
<p>Fix these Errors by previewing to check, and then publish again.</p>
</div><div class="vspace"></div>
EOT;
            } else {
                $warning_body = <<<EOT
<h4>ROUTE DATA VALIDATION</H4>
<p>No errors or warnings.</p>
EOT;
            }

            // Various URLs for download forms and buttons
            $this_url = site_url(strtolower(get_class($this)));
            $route_event_id = "$route_id/$event_id";
            $download_url = "$this_url/recache/$event_id";
            $event_url = "$this_url/event/$event_id";
            $publish_url = "$this_url/publish_cuesheet/$event_id";

            $download_body = <<<EOT
<h4>DOWLOAD LATEST ROUTE DATA</h4>
<P>If you made changes in the route on RWGPS that 
affect the published cues, brevet cards, etc... you must re-dowload the data here
and then publish again.</p> 
<div class="bordered narrower text-center">
  $download_note<br/>
  <div class="button-container"><A HREF=$download_url>Re-download updated route from RWGPS</A></div>
  <P>Last Download: $last_download</P>
</div>
<div class="vspace"></div>
EOT;

            // IS IT TRUE THAT SOME PAPERWORK GOES LIVE INSTANTLY (EG BREVET CARDS) OR ARE THEY PUBLISHED TOO?  BUG?

            $generate_form = <<<EOT
<h4>CUESHEET AND BREVET CARD PREVIEW</h4>
<P>Press the buttons below for <b>preview versions</b> of the cuesheet and brevet cards generated
now based on the last route data downloaded. This paperwork won't go live till you press
the 'Publish Cuesheets to Event' button.</P>
<FORM ACTION=$event_url METHOD=POST  enctype="multipart/form-data">
<div class="narrower button-stack">
    <BUTTON TYPE=SUBMIT NAME='view' value='pdf_cue_portrait'>PDF Cuesheet (portrait)</BUTTON>
    <BUTTON TYPE=SUBMIT NAME='view' value='pdf_cue_landscape'>PDF Cuesheet (landscape)</BUTTON>
    <BUTTON TYPE=SUBMIT NAME='view' value='csv_cue'>CSV Cue Sheet</BUTTON>
    <BUTTON TYPE=SUBMIT NAME='view' value='search_replace_changes'>Cue Sheet Search Replace Changes</BUTTON>
    <BUTTON TYPE=SUBMIT NAME='view' value='card_outside_blank'>Brevet Card Outside Blank</BUTTON>
    <BUTTON TYPE=SUBMIT NAME='view' value='card_outside_roster'>Brevet Card Outside Roster</BUTTON>
    <BUTTON TYPE=SUBMIT NAME='view' value='card_inside'>Brevet Card Inside</BUTTON>
    <BUTTON TYPE=SUBMIT NAME='view' value='qrpdf'>QR Codes</BUTTON>
</DIV><div class=vspace></div>

<h4>CUESHEET PUBLISH</h4>
<p>Once you are happy with the cuesheets generated above, press the button below 
to publish this new version of the cuesheet to the $this_organization event info page. <B>Don't
forget to publish after you make changes!</b></P>
<div class="bordered narrower text-center">
Last Published on: $cuesheet_gentime_str, Version: $cue_version_str<BR/>
<div class="button-container"><A HREF=$publish_url>Publish New (Ver $cue_next_version) Cuesheets to Event</A></div>
</div>
<div class="vspace"></div>
</FORM>
EOT;
        }

        // MAP and Elevation Profile
        $map_divid = 'randomap'; // must match ID in randomap.css
        $graph_divid = 'epdivid'; // must match ID in randomap.css
        $route = $this->rwgps->get_route($route_id);
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

        // Wizard User Disclaimer
        $wizard_fineprint = $this->route_event->disclaimer_html;

        // General Disclaimer Paragraph
        $route_fineprint = ($wizard) ? "" : "<DIV CLASS='narrower fine-print bordered'><p>" . $this->model_parando->get_paragraph("auto_cue") . "</p></div>";

        // PUT THE PAGE TOGETHER
        // PUT THE PAGE TOGETHER
        // PUT THE PAGE TOGETHER
        // PUT THE PAGE TOGETHER
        // PUT THE PAGE TOGETHER

        $page_body = <<<EOT
<!-- All -->
<H3>$event_name_dist</H3>
$general_info
$available_route_data

<!-- Wizard Only -->
$wizard_body
$warning_body
$route_tag_data

<!-- All -->
$cue_sheet_info

<!-- Wizard Only -->
$download_body
$generate_form

<!-- User Only -->
$route_fineprint

<!-- All -->
$map_body
$controles_table

<div class=bigskip></div>
$wizard_fineprint
EOT;

        $data = ['title' => $event_name_dist, 'left_column' => $page_body, 'right_column' => ""];
        $this->simple_view('info_one_column', ['randomap', 'table', 'buttonlink', 'form'], $data);
    }



    private function make_controles_table($controls, $route_controles, $style = 'info')
    {
        $controles_table = "";
        $ncontroles = count($controls);
        if ($ncontroles > 0) {
            $controles_table = "<div class='w3-container'><H4>CONTROLS</H4>";
            $controles_table .= "<TABLE class='w3-table-all w3-padding'>";

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
            $controles_table .= "</TABLE></DIV>";
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
