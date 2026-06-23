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


namespace App\Libraries;

class Waiver extends Myfpdf
{

	// colors
	const BLACK = [0, 0, 0];
	const RED = [255, 0, 0];

	// paths
	const template_baseurl = "https://randonneuring.org/assets/local/waivers/";


	// layout constants
	public $vmargin = 0.75;  // space between top/bottom page edge and cues
	public $hmargin = 0.75;  // space between left/right page edge and cues

	public $em_points = 8; // default font size

	// Other layout variables
	private float $page_height;
	private float $page_width;

	// Rider
	private string $rider_name = '';
	private string $rider_id = '';

	// Required configuration parameters

	private string $template_name;
	private int $n_riders;

	private $requiredParameters = [
		'template_name',
		'n_riders'
	];

	private $replacementParameters = [
		'organizing_club',
		'event_name',
		'event_date',
	];


	private string $organizing_club;
	private string $event_name;
	private string $event_date;

	public function __construct($params)
	{
		// $this->orientation = (isset($params['orientation'])) ? $params['orientation'] : 'P';
		// $this->size = (isset($params['size'])) ? $params['size'] : 'letter';
		parent::__construct(); // $this->orientation, $this->unit, $this->size);

		// Initialize REQUIRED PARAMETERS

		foreach (array_merge($this->requiredParameters, $this->replacementParameters) as $p) {
			if (array_key_exists($p, $params)) {
				$this->$p = $params[$p];
			} else {
				throw new \RuntimeException("Missing required parameter: $p");
			}
		}

		$replacementMap = [];
		foreach ($this->replacementParameters as $name) {
			$replacementMap[$name] = $this->$name; // should all have been initialized when checked above
		}

		$this->get_waiver_template($replacementMap);

		// Optional formatting
		$this->page_height = $this->GetPageHeight();
		$this->page_width = $this->GetPageWidth();
		$this->SetMargins(0, 0, 0);
		$this->SetAutoPageBreak(false, 0);
		$this->font_normal();

		// Document 
		if ($this->n_riders > 0) {
			$sPlural = ($this->n_riders > 1) ? 's' : '';
			$n = (string) $this->n_riders;
			$this->SetTitle("Participant Waiver$sPlural ($n) for event: " . $this->event_name);
		} else {
			$this->SetTitle("Participant Waiver Blank for event: " . $this->event_name);
		}
		$this->SetSubject("Event Date: " . $this->event_date);
		$this->SetCreator($this->organizing_club . ' RUSA waiver rendering software');
		$this->SetAuthor($this->organizing_club);
	}

	// Setter for rider

	public function set_rider(array $rider)
	{
		$this->rider_name = $rider['first_name'] . ' ' . $rider['last_name'];
		$this->rider_id = $rider['rider_id'];
	}

	public function set_blank()
	{
		$this->rider_name = '';
		$this->rider_id = '';
	}

	// Waiver fetcher
	// Does event interpolation with provided map

	private $waiver_template = [];

	private function get_waiver_template(array $replacementMap): void
	{

		$waiver_url = self::template_baseurl . $this->template_name;

		$contents = @file_get_contents($waiver_url);

		if ($contents === false) {
			throw new \RuntimeException("Unable to fetch waiver template from $waiver_url");
		}

		$result = [];

		$currentTag = null;
		$currentText = '';

		$lines = preg_split('/\R/', $contents);

		foreach ($lines as $line) {
			if (preg_match('/^\[([A-Z0-9_]+)\]$/', trim($line), $matches)) {
				if ($currentTag !== null) {
					$result[$currentTag][] = $this->fpdf_safe_text(rtrim($currentText));
				}

				$currentTag = $matches[1];
				$currentText = '';
			} else {
				if ($currentTag !== null) {
					$currentText .= $line . "\n";
				}
			}
		}

		if ($currentTag !== null) {
			$result[$currentTag][] = $this->fpdf_safe_text(rtrim($currentText));
		}

		$this->waiver_template = $this->interpolate_template($result, $replacementMap);
	}


	public function interpolate_template(array $waiverTemplate, array $replaceMap): array
	{
		foreach ($waiverTemplate as $tag => $strings) {
			foreach ($strings as $i => $text) {
				$waiverTemplate[$tag][$i] = preg_replace_callback(
					'/\{\{([A-Za-z0-9_]+)\}\}/',
					function ($matches) use ($replaceMap) {
						$name = $matches[1];

						if (!array_key_exists($name, $replaceMap)) {
							return '{{' . $name . '}}';   // if no mapping, leave tag in place
						} else {
							return (string)$replaceMap[$name];
						}
					},
					$text
				);
			}
		}

		return $waiverTemplate;
	}

	// Waiver Rendering

	// This function render
	// a waiver. It's assumed that event interpolations 
	// already occured. If a rider is specified
	// the rider name and ID will be interpolated, otherwise
	// left blank. 

	public function render_release()
	{
		$x = $this->hmargin;
		$y = $this->vmargin;
		$full_width = $this->page_width - 2 * $this->hmargin;
		$h = $this->page_height - 2 * $this->vmargin;
		$w = $this->page_width - 2 * $this->hmargin;

		// only the first of these
		$title = $this->waiver_template['TITLE'][0] ?? '';
		$header = $this->waiver_template['HEADER'][0] ?? '';
		$footer = $this->waiver_template['FOOTER'][0] ?? '';

		// and all of these
		$clause = $this->waiver_template['CLAUSE'] ?? [];

		// Interpolate rider name in preamble
		if (empty($this->rider_name)) {
			$rider_name_preamble = "_______________________________________";
		} else {
			$rider_name_preamble = $this->rider_name;
		}
		$template_with_rider = $this->interpolate_template($this->waiver_template, ['rider_name' => $rider_name_preamble]);
		$preamble = $template_with_rider['PREAMBLE'][0] ?? '';


		$this->SetXY($x, $y);
		$this->font_normal();
		$this->SetLeftMargin($this->hmargin);

		// $stuff = print_r($preamble, true);
		// $this->MultiCell($w, $this->baseline_skip, $stuff, 0, 'L');

		$this->SetTextColor(...self::RED);
		$this->MultiCell($w, $this->baseline_skip, $header, 0, 'C');
		$this->crlf();
		$this->SetTextColor(...self::BLACK);
		$this->font_bold();
		$this->MultiCell($w, $this->baseline_skip, $title, 0, 'C');
		$this->crlf();

		$this->font_plain();
		$this->MultiCell($w, $this->baseline_skip, $preamble, 0, 'L');
		$this->crlf();


		foreach ($clause as $c) {
			$this->MultiCell($w, $this->baseline_skip, $c, 0, 'L');
			$this->crlf();
		}

		$this->crlf();

		$date_width = 0.2 * $w;
		$name_width = 0.4 * $w;
		$sign_width = 0.4 * $w;

		$line_shrink = 0.95;

		$name_center = $this->hmargin + $name_width / 2;
		$sign_center = $this->hmargin + $name_width + $sign_width / 2;
		$date_center = $this->hmargin + $name_width + $sign_width + $date_width / 2;


		$y = $this->GetY();

		$this->put_string_centered($name_center, $y, 'NAME (PRINTED)');
		$this->put_string_centered($sign_center, $y, 'SIGNATURE (Only if 18 or older)');
		$this->put_string_centered($date_center, $y, 'DATE');

		$this->crlf();
		$this->crlf();
		$this->crlf();
		$this->crlf();

		$y = $this->GetY();

		if (!empty($this->rider_name)) {
			$this->font_largerprint();
			$this->put_string_centered($name_center, $y - 1.2 * $this->baseline_skip, "{$this->rider_name} (ID# {$this->rider_id})");
			$this->font_normalprint();
		}
		$this->draw_line($name_center - $line_shrink * $name_width / 2, $y, $name_center + $line_shrink * $name_width / 2, $y);
		$this->draw_line($sign_center - $line_shrink * $sign_width / 2, $y, $sign_center + $line_shrink * $sign_width / 2, $y);
		$this->draw_line($date_center - $line_shrink * $date_width / 2, $y, $date_center + $line_shrink * $date_width / 2, $y);

		$this->font_fineprint();
		$this->SetTextColor(...self::RED);
		$this->crlf();

		$this->MultiCell($w, $this->baseline_skip, $footer, 0, 'C');
		$this->font_normalprint();
	}


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
}
