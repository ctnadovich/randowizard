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


// Post Card Rendering Class /////////////////////////////////////////////////////////////////////

require_once(APPPATH . 'Libraries/Myfpdf.php');

use App\Libraries\Myfpdf;

class Postcard extends Myfpdf
{
    // Constructor parameters
    private $orientation = 'L'; // P - portrait; L - landscape
    private $unit = 'in'; // in, cm, mm, pt NOT SETTABLE
    private $size = 'letter'; // A3, A4, A5, letter, legal

    // Configuration variables
    public $logo_width = 0.6; // fraction of frame width
    public $logo_height = 0.6; // fraction of frame height


    // layout constants

    private $nrows = 2; // Fixed 2x2 layout of postcards in landscape
    private $ncols = 2;

    public $vmargin = 0.75;  // space between top/bottom page edge and cues
    public $hmargin = 0.75;  // space between left/right page edge and cues

    public $baseline_skip = 0.15;  // Space between text lines
    public $thin_width = 0.01; // Thin lines
    public $thick_width = 0.03; // Thick lines
    public $font_normal = 'Helvetica';    // default font family
    public $font_fixed = 'Courier';    // fixed width font
    public $font_style = ''; // 'B', 'I'
    public $font_current;
    public $em_points = 9; // default font size
    public $font_size = 1; // multiplier
    public $fine_print = 0.75; // em size of fine text
    public $big_print = 1.2; // em size of big text

    // Other class variables
    private $page_height;
    private $page_width;
    private $card_width;
    private $card_height;
    private $cards_per_page;
    private $params;
    private $title;

    private $club;
    private $edata;
    protected $ci;

    public function __construct($params)
    {
        if (empty($params)) trigger_error("Must specify parameters.", E_USER_ERROR);
        $this->params = $params;

        parent::__construct($this->orientation, $this->unit, $this->size);

        $this->page_height = $this->GetPageHeight();
        $this->page_width = $this->GetPageWidth();
        $this->SetMargins(0, 0, 0);
        $this->SetAutoPageBreak(false, 0);
        $this->font_normal();
        $this->SetTitle($this->title . " for " . $params['name_dist']);
        $this->SetSubject($this->title . " for " . $params['name_dist']);
        $this->SetCreator($this->params['this_organization'] . ' Postcard rendering software');
        $this->SetAuthor($this->params['this_organization']);

        $this->club = $this->params['club'];
        $this->edata = $this->params['edata'];

        $this->cards_per_page = $this->nrows * $this->ncols;
        $this->card_width = $this->page_width / $this->ncols;
        $this->card_height = $this->page_height / $this->nrows;
    }


    // Useful functions

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
    public function render_fronts()
    {

        $this->AddPage();
        for ($i = 1; $i < $this->nrows; $i++)
            $this->draw_line(0, $i * $this->card_height, $this->page_width, $i * $this->card_height, $this->thin_width, self::LIGHT_GRAY);
        for ($i = 1; $i < $this->ncols; $i++)
            $this->draw_line($i * $this->card_width, 0, $i * $this->card_width, $this->page_height, $this->thin_width, self::LIGHT_GRAY);

        for ($row = 0; $row < $this->nrows; $row++) {
            for ($col = 0; $col < $this->ncols; $col++) {
                $x = $col * $this->card_width;
                $y = $row * $this->card_height;
                $this->render_card_front($x, $y);
            }
        }
    }

    private $front_font_size = 1.5;

    private function render_card_front($x, $y)
    {

        extract($this->params);

        $logo_url = $club['icon_url'];
        $event_date = $edata['event_date_str'];

        $w = $this->logo_width * $this->card_width;
        $h = $this->logo_height * $this->card_height;
        $x_logo = $x + $this->card_width / 2 - $w / 2;
        $y_logo = $y + $this->card_height / 2 - $h / 2;
        $this->Image($logo_url, $x_logo, $y_logo, $w, $h);

        $this->font_resize($this->front_font_size);
        $this->SetXY($x, $y + ($this->card_height - $h) / 4);
        $this->Cell($this->card_width, $this->front_font_size * $this->baseline_skip, $name_dist, 0, 1, 'C');
        $this->SetXY($x, $y + $this->card_height - ($this->card_height - $h) / 4 - $this->front_font_size * $this->baseline_skip);
        $this->Cell($this->card_width, $this->front_font_size * $this->baseline_skip, $event_date, 0, 1, 'C');
    }



    public function render_backs()
    {

        $irider = 0;

        foreach ($this->params['roster'] as $r) {


            $col = $irider % $this->ncols;
            $row = floor($irider / $this->ncols) % $this->nrows;

            $x = $col * $this->card_width;
            $y = $row * $this->card_height;

            if (($irider % $this->cards_per_page) == 0) {
                $this->AddPage();
                for ($i = 1; $i < $this->nrows; $i++)
                    $this->draw_line(0, $y + $i * $this->card_height, $this->page_width, $y + $i * $this->card_height, $this->thin_width, self::LIGHT_GRAY);
                for ($i = 1; $i < $this->ncols; $i++)
                    $this->draw_line($x + $i * $this->card_width, 0, $x + $i * $this->card_width, $this->page_height, $this->thin_width, self::LIGHT_GRAY);
            }

            $irider++;
            $r->irider = $irider;

            $this->render_card_back($x, $y, $r);
        }
    }

    private $padding = 0.16;
    private $addr_top = 0.55;

    private function render_card_back($x, $y, $r)
    {

        extract(json_decode(json_encode($r), true)); // Convert class to array and extract
        $rider_name = "NAME: $first_name $last_name";
        $rider_id = "RUSA: $rusa_id";
        $club = $this->club;
        $edata = $this->edata;
        $tagname = $edata['event_tagname'];

        $club_name = $club['club_name'];
        $club_street = $club['street'];
        $club_csz = $club['city'] . ', ' . $club['state'] . ' ' . $club['zip'];
        $club_address = <<<EOF
$club_name
$club_street
$club_csz
EOF;

        $this->draw_line(
            $x + $this->card_width / 2,
            $y + $this->padding * $this->card_height,
            $x + $this->card_width / 2,
            $y + (1 - $this->padding) * $this->card_height,
            $this->thick_width,
            self::BLACK
        );
        $this->font_bigprint();
        $this->SetXY($x + $this->padding * $this->card_width / 2, $y + $this->padding * $this->card_height);
        $this->Cell((1 - $this->padding) * $this->card_width, $this->baseline_skip, $tagname);
        $this->SetXY($x + $this->padding * $this->card_width / 2, $y + $this->padding * $this->card_height + 2 * $this->big_print * $this->baseline_skip);
        $this->Cell((1 - $this->padding) * $this->card_width, $this->baseline_skip, $rider_name);
        $this->SetXY($x + $this->padding * $this->card_width / 2, $y + $this->padding * $this->card_height + 3 * $this->big_print * $this->baseline_skip);
        $this->Cell((1 - $this->padding) * $this->card_width, $this->baseline_skip, $rider_id);

        $this->SetXY($x + (1 + $this->padding) * $this->card_width / 2, $y + $this->addr_top * $this->card_height + $this->big_print * $this->baseline_skip);
        $this->MultiCell((1 - $this->padding) * $this->card_width, $this->big_print * $this->baseline_skip, $club_address);
    }
}
