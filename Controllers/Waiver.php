<?php

//    Randonneuring.org Website Software
//    Copyright (C) 2026 Chris Nadovich
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
use DateTime;
use Psr\Log\LoggerInterface;

class Waiver extends EventProcessor
{

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
    }

    public function waiver(string $event_code, string $rider_id = '', string $function = 'capture')
    {
        try {

            if ($this->request->is('post')) {
                $function = $this->request->getVar('function');
            }

            if (empty($function)) throw new \Exception("Nothing to do -- function missing or empty.");
            if (!method_exists($this, '_waiver_' . $function)) throw new \Exception("No such function type: '$function'");

            $event = $this->eventModel->eventByCode($event_code);
            $edata = $this->get_event_data($event);

            return $this->{'_waiver_' . $function}($edata, $rider_id);
        } catch (\Exception $e) {
            $this->die_exception($e);
        }
    }

    // Show a waiver as HTML and capture the necessary signatures

    private function _waiver_capture(array $edata, string $rider_id)
    {

        if ($edata['is_rusa']) {
            $template_name = 'rusa_waiver_template.txt'; // TODO This shouldn't be hardcoded. 
            $waiver_view = 'rusa_waiver'; // TODO This shouldn't be hardcoded. 
            $this->viewData['indemnified_logo'] = "https://randonneuring.org/assets/local/images/rusa-logo.png";
        } else {
            throw new \RuntimeException("Waiver for non-RUSA event is not defined.");
        }

        if (empty($rider_id)) {
            throw new \RuntimeException("Rider ID not specified.");
        }

        // Find rider in roster 
        $rider = [];
        foreach ($edata['roster'] as $r) {
            if ($r['rider_id'] == $rider_id) {
                $rider = $r;
                break;
            }
        }

        if (empty($rider)) {
            throw new \RuntimeException("Rider ID $rider_id not found in roster.");
        }

        $this->viewData = array_merge($this->viewData, $this->getContentMap($template_name, $edata, $rider));
        $this->viewData['style_head'] = view('default_style_head', $this->viewData);
        $this->viewData['body_style'] = 'class="w3-light-grey"';


        $views =  view('head', $this->viewData);
        $views .=  view($waiver_view, $this->viewData);
        $views .=  view('foot', $this->viewData);

        return $views;
    }

    // Save the captured waiver 

    private function _waiver_save(array $edata, $rider_id)
    {


        $waiver_id = $this->request->getVar('waiver_id');
        $dataUrl = $this->request->getVar('signature_png');

        if (!preg_match('/^data:image\/png;base64,/', $dataUrl)) {
            throw new \RuntimeException("Invalid signature data");
        }

        $pngData = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1));

        if ($pngData === false) {
            throw new \RuntimeException("Could not decode signature");
        }

        $signature_preview = "<img src='" . htmlspecialchars($dataUrl, ENT_QUOTES, 'UTF-8') . "' alt='Signature'>";

        // $signatureFile = "/tmp/signature-$waiver_id.png";
        // file_put_contents($signatureFile, $pngData);


        $body = "Signature for waiver ID: $waiver_id<br>$signature_preview";
        $this->viewData['body'] = $body;

        return $this->load_view(['waiver']);
    }


    // This function performs template interpolation and returns a replacement
    // map that the view (or anything else) can use to render the page


    private function getContentMap(string $template_name, array $edata, array $rider)
    {

        $waiverLibrary =  new \App\Libraries\WaiverTemplate($template_name);

        if (empty($edata) || empty($edata['event_code'])) {
            throw new \RuntimeException("Event data missing");
        }

        if (empty($rider) || empty($rider['rider_id'])) {
            throw new \RuntimeException("Rider not specified");
        }

        $club_acp_code = $edata['club_acp_code'];
        $club = $this->regionModel->getClub($club_acp_code);
        $epp_secret = $club['epp_secret'];

        $event_id = $edata['event_code'];
        $rider_id = $rider['rider_id'];

        $event_timezone_name = $club['event_timezone_name'];  // For now, events can't have individual TZ
        $event_tz = new \DateTimeZone($event_timezone_name);
        $now = new \DateTime('now', $event_tz);

        $waiver_date_time = $now->format($this->controletimesLibrary->event_datetime_format);
        $waiver_date = $now->format($this->controletimesLibrary->event_date_format);
        $waiver_time = $now->format($this->controletimesLibrary->event_time_format);
        $waiver_timecode = $now->format('YmdHisv');

        $waiver_id = "$event_id-$rider_id"; // maybe this needs an authentication hash?

        $plaintext = "$event_id-$rider_id-$waiver_timecode-$epp_secret";
        $hash = strtoupper(substr(hash('sha256', $plaintext), 0, 4));
        $waiver_id = "$event_id-$rider_id-$waiver_timecode-$hash";

        $this_waiver_url = site_url("waiver/$event_id/$rider_id");
        
        $replacementMap = [

            // Event
            'event_id' => $edata['event_code'],
            'club_acp_code' => $club_acp_code,
            'event_name' => $edata['event_name_dist'],
            'event_date' => $edata['event_date_str'],
            'event_tagname' => $edata['event_tagname'],
            'is_rusa' => $edata['is_rusa'],

            // Club
            // 'club' => $club,
            'organizing_club' => $club['club_name'],

            // Rider
            'rider_name' => $rider['first_name'] . ' ' . $rider['last_name'],
            'rider_id' => $rider_id,

            // Waiver
            'this_waiver_url' => $this_waiver_url,
            'waiver_session_id' => $waiver_id,  // This should go someplace in the template
            'waiver_date' => $waiver_date,
            'waiver_time' => $waiver_time,
            'waiver_date_time' => $waiver_date_time

        ];

        $interpolated_template = $waiverLibrary->interpolate_template($replacementMap);


// PRe-interpolate marginal sections
        // but only the first of these
        $title = $interpolated_template['TITLE'][0] ?? '';
        $header = $interpolated_template['HEADER'][0] ?? '';
        $initial = $interpolated_template['INITIAL'][0] ?? '';
        $preamble = $interpolated_template['PREAMBLE'][0] ?? '';
        $footer = $interpolated_template['FOOTER'][0] ?? '';
        $revision = $interpolated_template['REVISION'][0] ?? '';
        $signature = $interpolated_template['SIGNATURE'][0] ?? '';
        $esc = $interpolated_template['ESC'][0] ?? '';

        // and all of the clauses
        $clause = $interpolated_template['CLAUSE'] ?? [];

        $sectionMap = compact(['title', 'header', 'initial', 'preamble', 'footer', 'revision', 'clause', 'esc', 'signature']);

        /* 

        // BEGIN HTML Generation -- this needs to move into a view

                extract($replacementMap);


        $html_out = '';
        $html_out .= "<div class='w3-container'>";
        $html_out .= "<div class='w3-panel w3-center w3-text-red'><B>$header</B></div>";
        $html_out .= "<div class='w3-panel w3-center' style='text-decoration: underline'><B>$title</B></div>";
        $html_out .= "<div class=waiver-preamble>$preamble</div>";

        $html_out .= "<div class=waiver-body>";
        foreach ($clause as $c) {
            $html_out .= "<p class=waiver-clause>$c</p>";
        }
        $html_out .= "</div>";


        $id_type = ($is_rusa) ? "RUSA #" : "RIDER #";

        $html_out .= "<div class=w3-panel>";

        $html_out .= "NAME: $rider_name</br>";
        $html_out .= "$id_type: $rider_id</br>";
        $html_out .= "Waiver ID: $waiver_id</br>";
        $html_out .= "Waiver Generated: $waiver_date_time</br>";
        $html_out .= 'SIGNATURE:';

        $html_out .= "</div>"; // signature-block

        $html_out .= "<div class=w3-panel>";

        $html_out .= $this->signature_capture_form($replacementMap);

        $html_out .= <<<EOT
<div class='w3-panel w3-center w3-text-red'><b>$footer</b></div>
<div class='w3-small'>$revision</div>
</div>
EOT;
 */
        return array_merge($replacementMap, $sectionMap);
    }

        private function make_waiver_id($edata, $rider_id, $epp_secret)
    {
        extract($d);
        $plaintext = "$cue_version-$event_code-$rider_id-$epp_secret";
        $code = strtoupper(substr(hash('sha256', $plaintext), 0, 4));
        $code = str_replace(['0', '1'], ['X', 'Y'], $code);
        return $code;
    }



    private function signature_capture_form(array $replacementMap)
    {

        extract($replacementMap);
        $waiver_parameters_input = "";
        foreach ($replacementMap as $k => $v) {
            $waiver_parameters_input .= "<input type='hidden' name='$k' value='$v'>\n";
        }



        return <<<EOF
<form method="post" action="/waiver/$event_id/$rider_id/save">
  <canvas id="sig" style="border:1px solid #999; width:100%; height:200px;"></canvas>

  <input type="hidden" name="signature_png" id="signature_png">
  <input type="hidden" name="function" value="save">
  $waiver_parameters_input

  <button type="button" id="clear">Clear</button>
  <button type="submit">Sign Waiver</button>
</form></div>


<script src="https://cdn.jsdelivr.net/npm/signature_pad@5.0.7/dist/signature_pad.umd.min.js"></script>
<script>
const canvas = document.getElementById('sig');
const signaturePad = new SignaturePad(canvas);

function resizeCanvas() {
  const ratio = Math.max(window.devicePixelRatio || 1, 1);
  const rect = canvas.getBoundingClientRect();

  canvas.width = rect.width * ratio;
  canvas.height = rect.height * ratio;
  canvas.getContext("2d").scale(ratio, ratio);

  signaturePad.clear();
}

window.addEventListener("resize", resizeCanvas);
resizeCanvas();

document.getElementById('clear').onclick = () => signaturePad.clear();

document.querySelector('form').addEventListener('submit', function(e) {
  if (signaturePad.isEmpty()) {
    e.preventDefault();
    alert("Please sign before submitting.");
    return;
  }

  document.getElementById('signature_png').value =
    signaturePad.toDataURL("image/png");
});
</script>

EOF;
    }
}
