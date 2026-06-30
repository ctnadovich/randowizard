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


class Waiver extends EventProcessor
{

private function generate_waiver_html(array $edata){

		$template_name = 'rusa_waiver_template.txt'; // This shouldn't be hardcoded. 

		// First rider in roster (TESTING)
		$rider = $edata['roster'][0];
		$body = $this->render_waiver_html($template_name, $edata, $rider);

		$this->viewData['output'] = $body;

		return $this->load_view(['echo_output']);
		
	}




    // Waiver Rendering

    // This function renders
    // a waiver as HTML for a single rider, performing rider interpolation. 

    private function render_waiver_html(string $template_name, array $edata, array $rider)
    {

		$waiverLibrary =  new \App\Libraries\Waiver($template_name);

        // Event
        $event_id = $edata['event_code'];
		$club_acp_code = $edata['club_acp_code'];
		$event_name = $edata['event_name_dist'];
		$event_date = $edata['event_date_str'];
		$event_tagname = $edata['event_tagname'];
		$is_rusa = $edata['is_rusa'];

        // Club
		$club = $this->regionModel->getClub($club_acp_code);
		$organizing_club = $club['club_name'];

        // Rider
        $rider_name = $rider['first_name'] . ' ' . $rider['last_name'];
        $rider_id = $rider['rider_id'];

        $waiver_id = "$event_id-$rider_id";  // This should go someplace in the template

		$replacementMap = compact([
			'rider_name',
			'organizing_club',
			'event_name',
			'event_date',
			'waiver_id'
		]);

        $interpolated_template = $waiverLibrary->interpolate_template( $replacementMap );

        // Marginal sections
        // but only the first of these
        $title = $interpolated_template['TITLE'][0] ?? '';
        $header = $interpolated_template['HEADER'][0] ?? '';
        $preamble = $interpolated_template['PREAMBLE'][0] ?? '';
        $footer = $interpolated_template['FOOTER'][0] ?? '';

        // and all of the clauses
        $clause = $interpolated_template['CLAUSE'] ?? [];

		// BEGIN HTML Generation
		$html_out = '';
        $html_out .= "<div class=waiver-header>$header</div>";
        $html_out .= "<div class=waiver-title>$title</div>";
        $html_out .= "<div class=waiver-preamble>$preamble</div>";

        $html_out .= "<div class=waiver-body>";
        foreach ($clause as $c) {
            $html_out .= "<p class=waiver-clause>$c</p>";
        }
        $html_out .= "</div>";


        $id_type = ($is_rusa) ? "RUSA #" : "RIDER #";

        $html_out .= "<div class=waiver-signature-block>";

        $html_out .= "NAME: $rider_name</br>";
        $html_out .= "$id_type: $rider_id</br>";
        $html_out .= 'SIGNATURE (Only if 18 or older):';

        $html_out .= "</div>"; // signature-block

        $html_out .= <<<EOF
<form method="post" action="/waiver/sign">
  <canvas id="sig" style="border:1px solid #999; width:100%; height:200px;"></canvas>

  <input type="hidden" name="signature_png" id="signature_png">
  <input type="hidden" name="waiver_id" value="<?= htmlspecialchars($waiver_id) ?>">

  <button type="button" id="clear">Clear</button>
  <button type="submit">Sign Waiver</button>
</form>
<div class=waiver_footer>$footer</div>

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

        return $html_out;
    }





}