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


require_once(APPPATH . 'Libraries/Myfpdf.php');

use App\Libraries\Myfpdf;

class Signin extends Myfpdf
{

    // Constructor parameters
    private $orientation = 'P'; // P - portrait; L - landscape
    private $unit = 'in'; // in, cm, mm, pt NOT SETTABLE
    private $size = 'letter'; // A3, A4, A5, letter, legal

    // Configuration variables
    public $rusa_logo_url = "https://randonneuring.org/assets/local/images/rusa-logo.png";

    public $logo_width = 0.12; // fraction of frame width
    public $logo_height = 0.06; // fraction of frame height
    public $logo_center_y = 0.04; // fraction of overall page height
    public $logo_center_x = 0.8; // fraction of overall page width

    // layout constants
    public $vmargin = 1.0;  // space between top/bottom page edge and cues
    public $hmargin = 1.0;  // space between left/right page edge and cues
    public $vmargin_L = 1.75;  // space between top/bottom page edge and cues
    public $hmargin_L = 0.75;  // space between left/right page edge and cues
    public $vmargin_P = 0.5;  // space between top/bottom page edge and cues
    public $hmargin_P = 0.5;  // space between left/right page edge and cues
    public $baseline_skip = 0.15;  // Space between text lines
    public $row_padding = 0.05; // padding above and below cue text
    public $column_padding = 0.05; // Horizontal padding in dist cols

    // Other class variables
    private $page_height;
    private $page_width;
    private $title = "Contrôle Sign In";
    private $edata;
    private $roster_table;
    private $header_row;

    public function __construct($params)
    {
        if (empty($params)) trigger_error("Must specify parameters.", E_USER_ERROR);

        if (!isset($params['edata']) || !isset($params['roster_table'])|| !isset($params['header_row']))
            throw new \Exception('Missing parameters.');

        $this->edata = $params['edata'];
        $this->roster_table = $params['roster_table'];
        $this->header_row = $params['header_row'];


        if (isset($params['orientation'])) $this->orientation = $params['orientation'];
        if (isset($params['size'])) $this->size = $params['size'];
        if (isset($params['title'])) $this->title = $params['title'];

        parent::__construct($this->orientation, $this->unit, $this->size);
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
        $this->SetTitle($this->title . " for " . $this->edata['event_name_dist']);
        $this->SetSubject($this->title . " for " . $this->edata['event_name_dist']);
        $this->SetCreator($this->edata['club_name'] . ' Sign In Sheet rendering software');
        $this->SetAuthor($this->edata['club_name']);
    }

    // Sign In Sheet Rendering

    private function put_string_centered($x, $y, $s)
    {
        $w = $this->GetStringWidth($s);
        $this->SetXY($x - $w / 2, $y);
        $this->Cell($w, $this->baseline_skip, $s, 0, 'C');
    }

    private function crlf($lines = 1)
    {
        $height = $lines * $this->baseline_skip;
        $x = $this->hmargin;
        $y = $this->GetY() + $height;
        $this->SetXY($x, $y);
    }

    private function vskip($height)
    {
        $x = $this->hmargin;
        $y = $this->GetY() + $height;
        $this->SetXY($x, $y);
    }

    private function half_crlf()
    {
        $x = $this->hmargin;
        $y = $this->GetY() + $this->baseline_skip / 2;
        $this->SetXY($x, $y);
    }

    public function render_sheet()
    {

        $x = $this->hmargin;
        $y = $this->vmargin;
        $full_width = $this->page_width - 2 * $this->hmargin;
        $h = $this->page_height - 2 * $this->vmargin;
        $w = $this->page_width - 2 * $this->hmargin;

        $header_row_height = $this->baseline_skip;
        $table_row_height = 2.5 * $this->baseline_skip;

        $pw = $this->page_width;
        $ph = $this->page_height;

        $this->Image($this->edata['icon_url'] ?: $this->rusa_logo_url, $pw * $this->logo_center_x, $ph * $this->logo_center_y, $pw * $this->logo_width, $ph * $this->logo_height);

        $event_name_dist = $this->my_utf8_decode($this->edata['event_name_dist']);
        $cue_version = $this->edata['cue_version_str'];
        $this->SetXY($x, $y);
        $this->font_bigprint();
        $this->font_bold();
        $this->Cell($w, $this->baseline_skip,  $this->my_utf8_decode($this->title), 0, 'L');
        $this->crlf(1.5);
        $this->font_normalprint();
        $this->Cell($w, $this->baseline_skip,  $event_name_dist, 0, 'L');
        $this->font_plain();
        $this->crlf();
        $this->Cell($w, $this->baseline_skip, 'Cue Version: ' . $cue_version, 0, 'L');
        $this->crlf(1.5);
        $this->Cell($w, $this->baseline_skip, 'Start Date: ' . $this->edata['event_date_str'], 0, 'L');
        $this->crlf();
        $this->Cell($w, $this->baseline_skip, 'Start Time: ' . $this->edata['event_time_str'], 0, 'L');

        $this->crlf(1.5);

        $this->crlf();
        $this->Cell($w, $this->baseline_skip, $this->my_utf8_decode('Contrôle: _________________________________________'), 0, 'L');
        $this->crlf(2);

        $column_width = [];
        foreach ($this->header_row as $r) $column_width[] = $full_width * $r['width'] / 100.0;

        $this->render_row($this->header_row, $column_width, $header_row_height);

        foreach ($this->roster_table as $row) {
            $y = $this->GetY();
            if ($y + $table_row_height > ($this->page_height - $this->vmargin)) {
                $this->AddPage();
                $x = $this->hmargin;
                $y = $this->vmargin;
                $this->SetXY($x, $y);
                $this->render_row($this->header_row, $column_width, $header_row_height);
            }
            $this->render_row($row, $column_width, $table_row_height);
        }
    }

    private function render_row($r, $column_width = null, $baseline_skip = null)
    {

        $x = $this->hmargin;
        $y = $this->GetY();
        $full_width = $this->page_width - 2 * $this->hmargin;
        $full_height = $this->page_height - 2 * $this->vmargin;
        $w = $this->page_width - 2 * $this->hmargin;

        $height = (!empty($baseline_skip)) ? $baseline_skip : $this->baseline_skip;

        $this->SetLineWidth($this->thin_width);
        $this->SetDrawColor(self::BLACK);

        $nfield = count($r);

        for ($i = 0; $i < $nfield; $i++) {

            $field = $r[$i];

            $style = (isset($field['style'])) ? array_fill_keys(explode(',', $field['style']), true) : [];

            if (isset($style['hidden'])) continue;

            $font = (isset($field['font'])) ? explode(',', $field['font']) : [];

            $this->font_normal();
            $this->font_plain();
            $this->font_normalprint();

            foreach ($font as $method) $this->{'font_' . $method}();

            $text = (isset($field['text'])) ? $this->my_utf8_decode($field['text']) : ''; //$text = "($x)$text";
            $fill = (isset($field['fill'])) ? explode(',', $field['fill']) : [255, 255, 255];
            $text = (isset($style['toupper'])) ? strtoupper($text) : $text;
            $indent = (isset($field['col']) && is_numeric($field['col']) && $field['col'] > 1) ? str_repeat(' ', $field['col'] - 1) : '';
            $border = (isset($style['noborder'])) ? 0 : 1;
            $justify = (isset($field['align'])) ? $field['align'] : 'C';

            if (isset($field['fill'])) $this->SetFillColor(...$fill);
            if (isset($field['fontsize'])) $this->font_size($field['fontsize']);

            $string_width = $this->GetStringWidth($indent . $text);
            $dash_width = $this->GetStringWidth('-');

            if (isset($field['width'])) {
                $width = $full_width * $field['width'] / 100.0;
            } elseif (isset($column_width[$i])) {
                $width = $column_width[$i];
            } else {
                $width = $string_width + 2 * $dash_width;
            }

            if (isset($style['fit'])) {
                $save_size = $this->font_size;
                $need_width = $string_width + 2 * $dash_width;
                if ($need_width > $width) {
                    $this->font_size($width / $need_width);
                }
            }

            if (isset($style['multi'])) { // multiline text cell
                $x = $this->GetX();
                $y = $this->GetY();
                $nlines = substr_count($text, "\n") + 1;
                $this->MultiCell($width, $height / $nlines, $indent . $text, $border, $justify, isset($line['fill']));  // text
                $this->SetXY($x + $width, $y);
            } else {
                $this->Cell($width, $height, $indent . $text, $border, 0, $justify);  // text
            }

            if (isset($style['fit'])) {
                $this->font_size($save_size);
            }
        }

        $this->vskip($height);
    }

    protected function my_utf8_decode($item)
    {
        return mb_convert_encoding($item, "ISO-8859-1", mb_detect_encoding($item, "UTF-8, ISO-8859-1, ISO-8859-15", true));
    }
}
