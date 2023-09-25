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


// Brevet Card Rendering Class /////////////////////////////////////////////////////////////////////

require_once(APPPATH . 'Libraries/fpdf/fpdf.php');

use FPDF;

class Myfpdf extends FPDF
{
	const ORIENTATION = 'P'; // P - portrait; L - landscape
	const UNIT = 'in'; // in, cm, mm, pt NOT SETTABLE
	const SIZE = 'letter'; // A3, A4, A5, letter, legal

	// colors
	const BLACK = 0;
	const LIGHT_GRAY = 192;
	const LIGHTEST_GRAY = 225;
	const RED = [255, 0, 0];
	const FILL_WARN = [255, 255, 225];

	public $thin_width = 0.01; // Thin lines
	public $thick_width = 0.02; // Thick lines
	public $font_normal = 'Helvetica';	// default font family
	public $font_fixed = 'Courier';	// fixed width font
	public $font_style = ''; // 'B', 'I'
	public $font_current = 'Helvetica';
	public $em_points = 9; // default font size
	public $font_size = 1; // multiplier
	public $fine_print = 0.75; // em size of fine text
	public $big_print = 1.5; // em size of big text

	public $baseline_skip = 0.15;  // Space between text lines


	public function __construct($orientation = self::ORIENTATION, $unit = self::UNIT, $size = self::SIZE)
	{
		parent::__construct($orientation, $unit, $size);
	}

	public function font_normal()
	{
		$this->font_current = $this->font_normal;
		$this->SetFont($this->font_normal, $this->font_style, $this->em_points * $this->font_size);
	}
	public function font_fixed()
	{
		$this->font_current = $this->font_fixed;
		$this->SetFont($this->font_fixed, $this->font_style, $this->em_points * $this->font_size);
	}
	public function font_style($s)
	{
		$this->font_style = $s;
		$this->SetFont($this->font_current, $this->font_style, $this->em_points * $this->font_size);
	}
	public function font_bold()
	{
		$this->font_style = "B";
		$this->SetFont($this->font_current, $this->font_style, $this->em_points * $this->font_size);
	}
	public function font_italic()
	{
		$this->font_style = "I";
		$this->SetFont($this->font_current, $this->font_style, $this->em_points * $this->font_size);
	}
	public function font_underline()
	{
		$this->font_style = "U";
		$this->SetFont($this->font_current, $this->font_style, $this->em_points * $this->font_size);
	}
	public function font_plain()
	{
		$this->font_style = "";
		$this->SetFont($this->font_current, $this->font_style, $this->em_points * $this->font_size);
	}
	public function font_size($x = 1)
	{
		$this->font_size = $x;
		$this->SetFont($this->font_current, $this->font_style, $this->em_points * $this->font_size);
	}
	public function font_resize($x = 1)
	{
		$this->font_size = $x;
		$this->SetFont($this->font_current, $this->font_style, $this->em_points * $this->font_size);
	}
	public function font_normalprint()
	{
		$this->font_size = 1;
		$this->SetFont($this->font_current, $this->font_style, $this->em_points * $this->font_size);
	}
	public function font_fineprint()
	{
		$this->font_size = $this->fine_print;
		$this->SetFont($this->font_current, $this->font_style, $this->em_points * $this->font_size);
	}
	public function font_superfineprint()
	{
		$this->font_size = $this->fine_print / 1.4;
		$this->SetFont($this->font_current, $this->font_style, $this->em_points * $this->font_size);
	}
	public function font_bigprint()
	{
		$this->font_size = $this->big_print;
		$this->SetFont($this->font_current, $this->font_style, $this->em_points * $this->font_size);
	}

	public function draw_line($x1, $y1, $x2, $y2, $width = null, $color = self::BLACK)
	{
		if (empty($width)) $width = $this->thin_width;
		$this->SetLineWidth($width);
		$this->SetDrawColor($color);
		$this->Line($x1, $y1, $x2, $y2);
	}

	public function draw_rect($x1, $y1, $w, $h, $width = null, $color = self::BLACK)
	{
		if (empty($width)) $width = $this->thin_width;
		$this->SetLineWidth($width);
		$this->SetDrawColor($color);
		$this->Rect($x1, $y1, $w, $h);
	}

	protected function my_utf8_decode($item)
	{
		return mb_convert_encoding($item, "ISO-8859-1", mb_detect_encoding($item, "UTF-8, ISO-8859-1, ISO-8859-15", true));
	}
}
