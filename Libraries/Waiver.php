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


namespace App\Libraries;

class Waiver extends Myfpdf
{

	// distances	
	// const km_per_mi = 1.609344;
	// const ft_per_m = 3.2808398950131;
	// const m_per_km = 1000.0;

	// colors
	const BLACK = [0, 0, 0];
	const RED = [255, 0, 0];

	// paths
	// const cuesheet_path="/home/tomr/public_html/assets/parando/cuesheets";
	// const cuesheet_baseurl="https://parando.org/assets/parando/cuesheets";

	// Constructor parameters
	private $orientation = 'P'; // P - portrait; L - landscape
	private $unit = 'in'; // in, cm, mm, pt NOT SETTABLE
	private $size = 'letter'; // A3, A4, A5, letter, legal

	/* 	// Configuration variables
	public $logo_url="https://parando.org/images/parando3D.png";
	public $logo_width=0.75; // fraction of frame width
	public $logo_height=0.31; // fraction of frame height
	public $logo_center_y=0.27; // fraction of overall card height
 */

	// layout constants
	public $vmargin = 1.0;  // space between top/bottom page edge and cues
	public $hmargin = 1.0;  // space between left/right page edge and cues
	public $vmargin_L = 1.75;  // space between top/bottom page edge and cues
	public $hmargin_L = 0.75;  // space between left/right page edge and cues
	public $vmargin_P = 0.75;  // space between top/bottom page edge and cues
	public $hmargin_P = 0.75;  // space between left/right page edge and cues
	public $baseline_skip = 0.15;  // Space between text lines
	public $row_padding = 0.05; // padding above and below cue text
	public $column_padding = 0.05; // Horizontal padding in dist cols
	public $thin_width = 0.01; // Thin lines
	public $thick_width = 0.02; // Thick lines
	public $font_normal = 'Helvetica';	// default font family
	public $font_fixed = 'Courier';	// fixed width font
	public $font_style = ''; // 'B', 'I'
	public $font_current;
	public $em_points = 8; // default font size
	public $font_size = 1; // multiplier
	public $fine_print = 0.75; // em size of fine text
	public $big_print = 1.5; // em size of big text

	// Other class variables
	private $page_height;
	private $page_width;
	private $edata;
	private $club;
	private $event;
	private $release_type;

	public function __construct($params)
	{
		$this->orientation = (isset($params['orientation'])) ? $params['orientation'] : 'P';
		$this->size = (isset($params['size'])) ? $params['size'] : 'letter';
		parent::__construct($this->orientation, $this->unit, $this->size);
		$this->edata = $params['edata'];
		$this->club = $params['club'];
		$this->event = $this->edata['event'];
		// $this->release_type = $params['release_type'];
		$this->page_height = $this->GetPageHeight();
		$this->page_width = $this->GetPageWidth();
		$this->SetMargins(0, 0, 0);
		switch ($this->orientation) {
			case 'L':
				$this->vmargin = $this->vmargin_L;
				$this->hmargin = $this->hmargin_L;
				break;
			case 'P':
				$this->vmargin = $this->vmargin_P;
				$this->hmargin = $this->hmargin_P;
				break;
		}
		$this->SetAutoPageBreak(false, 0);
		$this->font_normal();
		$this->SetTitle('Participant Waiver for ' . $params['region_name_dist']);
		$this->SetSubject('Participant Waiver for ' . $params['region_name_dist']);
		$this->SetCreator($params['this_organization'] . ' RUSA waiver rendering software');
		$this->SetAuthor($params['this_organization']);
	}


	// Waiver Rendering

	private function put_string_centered($x, $y, $s)
	{
		$w = $this->GetStringWidth($s);
		$this->SetXY($x - $w / 2, $y);
		$this->Cell($w, $this->baseline_skip, $s, 0, 'C');
	}

	private function crlf()
	{
		$x = $this->hmargin;
		$y = $this->GetY() + $this->baseline_skip;
		$this->SetXY($x, $y);
	}

	public function render_release()
	{

		$x = $this->hmargin;
		$y = $this->vmargin;
		$full_width = $this->page_width - 2 * $this->hmargin;
		$h = $this->page_height - 2 * $this->vmargin;
		$w = $this->page_width - 2 * $this->hmargin;

		$this->SetXY($x, $y);
		$this->font_normal();
		$this->SetLeftMargin($this->hmargin);
		$this->SetTextColor(...self::RED);
		$this->MultiCell($w, $this->baseline_skip, $data['header'], 0, 'C');
		$this->SetTextColor(...self::BLACK);
		$this->font_bold();
		$this->MultiCell($w, $this->baseline_skip, $data['title'], 0, 'C');
		$this->font_plain();
		$this->MultiCell($w, $this->baseline_skip, $data['preamble'], 0, 'L');
		foreach ($data['clause'] as $c) {
			$this->MultiCell($w, $this->baseline_skip, $c, 0, 'L');
		}

		$this->crlf();

		$date_width = 0.2 * $w;
		$name_width = 0.4 * $w;
		$sign_width = 0.4 * $w;

		$line_shrink = 0.95;

		$date_center = $this->hmargin + $date_width / 2;
		$name_center = $this->hmargin + $date_width + $name_width / 2;
		$sign_center = $this->hmargin + $date_width + $name_width + $sign_width / 2;

		$x = $this->hmargin + $w / 6;
		$y = $this->GetY();
		$this->put_string_centered($date_center, $y, 'DATE');

		$x = $this->hmargin + $w / 2;
		$y = $this->GetY();
		$this->put_string_centered($name_center, $y, 'NAME (PRINTED)');

		$x = $this->hmargin + 5 * $w / 6;
		$y = $this->GetY();
		$this->put_string_centered($sign_center, $y, 'SIGNATURE (Only if 18 or older)');

		$this->crlf();
		$this->crlf();
		$this->crlf();
		$this->crlf();

		$y = $this->GetY();
		$this->draw_line($date_center - $line_shrink * $date_width / 2, $y, $date_center + $line_shrink * $date_width / 2, $y);
		$this->draw_line($name_center - $line_shrink * $name_width / 2, $y, $name_center + $line_shrink * $name_width / 2, $y);
		$this->draw_line($sign_center - $line_shrink * $sign_width / 2, $y, $sign_center + $line_shrink * $sign_width / 2, $y);

		$this->font_size(.8);
		$this->SetTextColor(...self::RED);
		$this->MultiCell($w, $this->baseline_skip, $data['footer'], 0, 'C');
	}


	public function get_release_html($signature_line = false, $map = null)
	{
		$release_text = "<div class=release-form>";
		$release_text .= '<div class=release-header>' . $this->interpolate_strings($this->model_parando->get_paragraph('release_header'), $map) . '</div>';
		$release_text .= '<div class=release-title>' . $this->interpolate_strings($this->model_parando->get_paragraph('release_title'), $map) . '</div>';
		$release_text .= '<p>' . $this->interpolate_strings($this->model_parando->get_paragraph('release_preamble'), $map) . '</p>';

		$clauses = $this->model_parando->get_paragraph_array('release');
		$needed = count($clauses);
		$found = 0;
		for ($i = 1; $i <= $needed; $i++) {
			$ai = 'agree' . $i;
			$release_text .= "<p>" . $this->interpolate_strings($clauses[$ai], $map) . "</p>";
		}
		if ($signature_line) {
			$release_text .= '<div class=release-signature>';

			$release_text .= <<<EOT
<TABLE WIDTH=100%>
<TR><TH WIDTH=20%>DATE</TH><TH>NAME (PRINTED)</TH><TH>SIGNATURE (only if age 18 or over)</TH></TR>
<TR><TD></TD><TD></TD><TD></TD></TR>
</TABLE>
EOT;

			$release_text .= '</div>';
		}
		$release_text .= '<div class=release-footer>' . $this->interpolate_strings($this->model_parando->get_paragraph('release_footer'), $map)  . '</div>';
		$release_text .= "</div>";
		return $release_text;
	}

	private function interpolate_strings($s, $map)
	{
		$vars = [];
		foreach ($map as $k => $v) {
			if ((is_string($v) || is_int($v) || is_float($v)) && !empty($k)) {
				$tag = '{$' . $k . '}';
				$vars[$tag] = $v . '';
			}
		}
		$s_interp = strtr($s, $vars);
		//$this->die_error(__METHOD__, '<pre>'. print_r($vars,true) . '----\n' . print_r($s,true). '----\n' . print_r($s_interp,true) .'</pre>');
		return $s_interp;
	}

	private function interpolate_and_strip($d, $map)
	{
		$out = strip_tags($this->interpolate_strings($d, $map));
		return $this->he_decode($out);
	}

	private function he_decode($str)
	{
		$non_iso = [
			'&ldquo;' => '"',
			'&rdquo;' => '"'
		];
		return html_entity_decode(strtr($str, $non_iso), ENT_QUOTES | ENT_XHTML, 'ISO-8859-1');
	}



	public function get_release_array($map = null)
	{
		$release_text = [];
		$release_text['header'] = ($this->interpolate_and_strip($this->model_parando->get_paragraph('release_header'), $map));
		$release_text['title'] = ($this->interpolate_and_strip($this->model_parando->get_paragraph('release_title'), $map));
		$release_text['preamble'] = ($this->interpolate_and_strip($this->model_parando->get_paragraph('release_preamble'), $map));

		$clauses = $this->model_parando->get_paragraph_array('release');
		$needed = count($clauses);
		$release_text['clause'] = [];
		for ($i = 1; $i <= $needed; $i++) {
			$ai = 'agree' . $i;
			$release_text['clause'][] = $this->interpolate_and_strip($clauses[$ai], $map);
		}
		$release_text['signature'] =  <<<EOT
<TABLE WIDTH=100%>
<TR><TH WIDTH=20%>DATE</TH><TH>NAME (PRINTED)</TH><TH>SIGNATURE (only if age 18 or over)</TH></TR>
<TR><TD></TD><TD></TD><TD></TD></TR>
</TABLE>
EOT;
		$release_text['footer'] = ($this->interpolate_and_strip($this->model_parando->get_paragraph('release_footer'), $map));
		return $release_text;
	}
}
