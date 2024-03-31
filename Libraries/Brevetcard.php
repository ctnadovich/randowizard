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

require_once(APPPATH . 'Libraries/Myfpdf.php');
// require_once(APPPATH . 'Libraries/Crypto.php');

use App\Libraries\Myfpdf;
// use App\Libraries\Crypto;

class Brevetcard extends Myfpdf
{

    // // colors
    // const BLACK=0;
    // const LIGHT_GRAY=224;

    // distances	
    const km_per_mi = 1.609344;
    const ft_per_m = 3.2808398950131;
    const m_per_km = 1000.0;


    // Configuration variables
    public $orientation = 'P'; // P - portrait; L - landscape
    public $unit = 'in'; // in, cm, mm, pt
    public $size = 'letter'; // A3, A4, A5, letter, legal
    public $n_folds = 2;  // PostCard=1, BiFold=2, TriFold=3
    public $n_cards = 2;  // Cards per page
    public $controle_index_origin = 1;      // if set to 0 the first 
    // controle will be START
    // the first intermediate will be number 1
    // and the final FINISH
    // public $parando_logo_url="https://parando.org/images/parando3D.png";
    public $rusa_logo_url = "https://randonneuring.org/assets/local/images/rusa-logo.png";
    public $logo_width = 0.75; // fraction of frame width
    public $logo_height = 0.31; // fraction of frame height
    public $logo_center_y = 0.27; // fraction of overall card height

    public $page3_image_width = 0.9; // fraction of frame width
    public $page3_image_height = 0.95; // fraction of frame height
    public $page3_image_center_y = 0.5; // fraction of overall card height

    public $single_card_height = 5.0; // Height of card when one per page

    // layout constants
    //public $fmargin=0.4;  // space between page edge and fold frame
    //public $cut_margin=0.3;  // space between cards at page cut
    //public $inter_margin=0.1;  // space between fold frame and fold frame
    public $fmargin = 0.2;  // space between page edge and fold frame
    public $cut_margin = 0.4;  // space between cards at page cut
    public $inter_margin = 0.4;  // space between fold frame and fold frame (twice fmargin)

    public $cmargin = 0.04;  // space between fold frame and controle cell
    public $cpf = 5; // controles per fold
    public $lpc = 7; // lines per controle
    public $lpf = 10; // lines on front

    public $stamp_width_fraction = 0.2; // Fraction (0<frac<1) of width to reserve for stamp

    /*         public $thin_width=0.01; // Thin lines
        public $thick_width=0.02; // Thick lines
        public $font_normal='Helvetica';	// default font family
        public $font_fixed='Courier';	// fixed width font
        public $font_style=''; // 'B', 'I'
        public $font_current;
        public $em_points=6; // default font size
        public $font_size=1; // multiplier
        public $fine_print=0.75; // em size of fine text
        public $big_print=1.5; // em size of big text
 */
    // Other class variables
    private $page_height;
    private $page_width;
    private $event;
    protected $CI;

    private $cryptoLibrary;

    public function __construct($params = [])
    { // , $orientation="P", $unit="in", $size="letter") {

        $this->orientation = (isset($params['orientation'])) ? $params['orientation'] : 'P';
        $this->size = (isset($params['size'])) ? $params['size'] : 'letter';
        parent::__construct($this->orientation, $this->unit, $this->size);

        if (empty($params['edata'])) return;

        $this->event = $params['edata'];

        $this->page_height = $this->GetPageHeight();
        $this->page_width = $this->GetPageWidth();
        $this->SetMargins(0, 0, 0);
        $this->SetAutoPageBreak(false, 0);
        $this->font_normal();
        $this->SetTitle($this->event['event_name_dist']);
        $this->SetSubject($this->event['event_name_dist']);
        $this->SetCreator($this->event['this_organization'] . ' brevet card rendering software');
        $this->SetAuthor($this->event['this_organization']);

        $this->cryptoLibrary = new \App\Libraries\Crypto();
    }

    /*        public function font_normal(){
            $this->font_current=$this->font_normal;
            $this->SetFont($this->font_normal,$this->font_style,$this->em_points*$this->font_size);
        }
        public function font_fixed(){
            $this->font_current=$this->font_fixed;
            $this->SetFont($this->font_fixed,$this->font_style,$this->em_points*$this->font_size);
        }
        public function font_style($s){
            $this->font_style=$s;
            $this->SetFont($this->font_current,$this->font_style,$this->em_points*$this->font_size);
        }
        public function font_bold(){
            $this->font_style="B";
            $this->SetFont($this->font_current,$this->font_style,$this->em_points*$this->font_size);
        }
        public function font_italic(){
            $this->font_style="I";
            $this->SetFont($this->font_current,$this->font_style,$this->em_points*$this->font_size);
        }
        public function font_underline(){
            $this->font_style="U";
            $this->SetFont($this->font_current,$this->font_style,$this->em_points*$this->font_size);
        }
        public function font_plain(){
            $this->font_style="";
            $this->SetFont($this->font_current,$this->font_style,$this->em_points*$this->font_size);
        }
        public function font_resize($x=1){
            $this->font_size = $x;
            $this->SetFont($this->font_current,$this->font_style,$this->em_points*$this->font_size);
        }
        public function font_fineprint(){
            $this->font_size=$this->fine_print;
            $this->SetFont($this->font_current,$this->font_style,$this->em_points*$this->font_size);
        }
        public function font_superfineprint(){
            $this->font_size=$this->fine_print/1.4;
            $this->SetFont($this->font_current,$this->font_style,$this->em_points*$this->font_size);
        }
        public function font_bigprint(){
            $this->font_size=$this->big_print;
            $this->SetFont($this->font_current,$this->font_style,$this->em_points*$this->font_size);
        }
    
        private function draw_line($x1,$y1,$x2,$y2,$width=null,$color=self::BLACK){
            if(empty($width)) $width=$this->thin_width;
            $this->SetLineWidth($width);
            $this->SetDrawColor($color);
            $this->Line($x1,$y1,$x2,$y2); 
        }
    
        private function draw_rect($x1,$y1,$w,$h,$width=null,$color=self::BLACK){
            if(empty($width)) $width=$this->thin_width;
            $this->SetLineWidth($width);
            $this->SetDrawColor($color);
            $this->Rect($x1,$y1,$w,$h); 
        }
  */
    private function frame_dims()
    {

        $w = $this->page_width;
        $nc = $this->n_cards;
        $nf = $this->n_folds;
        $ifm = $this->inter_margin;
        $fm = $this->fmargin;
        // 		$fwl=$fwr=$w/$nf - $fm - $ifm/2;
        // 		$fwc=$w/$nf - $ifm;

        //$upw=$w-2*$fm;   //  useful page, page width minus margins




        $fw = $w / $nf - 2 * $fm;  // $ifm == 2*$fm

        if ($nc == 1 && $nf == 4) {
            $h = $this->single_card_height;
            $fh = $h - 2 * $fm;
            $tm = ($this->page_height - $h) / 2;
        } else {
            $h = $this->page_height;
            $fh = ($h / $nc - 2 * $fm);
            $tm = $fm;
        }
        return compact('h', 'w', 'nc', 'nf', 'ifm', 'fm', 'tm', 'fw', 'fh');
    }

    private function frame_x($f)
    {
        extract($this->frame_dims());

        $x = $fm; // +$ifm/2;
        if ($f > 0) $x += ($f) * ($fw + $ifm);

        return $x;
    }

    private function frame_w($f)
    {

        extract($this->frame_dims());

        return $fw;
    }

    private function frame_y($c)
    {

        extract($this->frame_dims());

        $y = $tm + $c * ($fh + 2 * $fm);


        return $y;
    }

    private function draw_fold_lines()
    {
        extract($this->frame_dims());
        //$xlist=[];
        for ($f = 0; $f <= $nf; $f++) {
            $x = $fm + $f * ($fw + $ifm);
            $this->draw_line($x, $tm, $x, $tm + $fh, $this->thin_width, self::LIGHT_GRAY); // vrule between folds
        }
        //trigger_error(print_r($xlist,true),E_USER_ERROR);

    }

    private function draw_cut_lines()
    {
        extract($this->frame_dims());
        for ($c = 0; $c < $nc; $c++) {
            $y = $this->frame_y($c);
            // $this->draw_line($fm,$y,$w-$fm,$y, $this->thin_width, self::LIGHT_GRAY); // hrule above and below cards
            // $this->draw_line($fm,$y+$fh,$w-$fm,$y+$fh, $this->thin_width, self::LIGHT_GRAY); 

            if ($nc > 1) $this->draw_line($fm, $y + $fh + $fm, $w - $fm, $y + $fh + $fm, $this->thin_width, self::LIGHT_GRAY);
        }
    }

    private function draw_frames($linewidth, $color)
    {
        extract($this->frame_dims());
        for ($c = 0; $c < $nc; $c++) {
            for ($f = 0; $f < $nf; $f++) {
                $y = $this->frame_y($c); // ($c+1)*$fm + $c*$fh;
                $x = $this->frame_x($f); // $fm + $f*($fw+$ifm);
                $fw = $this->frame_w($f);
                $this->draw_rect($x, $y, $fw, $fh, $linewidth, $color);  // fold frames					
            }
        }
    }

    private function draw_logo($f, $logo_url)
    {
        extract($this->frame_dims());
        for ($c = 0; $c < $nc; $c++) {
            $y = $this->frame_y($c) + $fh * $this->logo_center_y - $fh * $this->logo_height / 2;
            $fw = $this->frame_w($f);
            $x = $this->frame_x($f, true) + $fw / 2 - $fw * $this->logo_width / 2;
            $this->Image($logo_url, $x, $y, $fw * $this->logo_width, $fh * $this->logo_height);
        }
    }

    private function draw_page3($f, $image_url = null)
    {
        extract($this->frame_dims());
        for ($c = 0; $c < $nc; $c++) {
            $y = $this->frame_y($c) + $fh * $this->page3_image_center_y - $fh * $this->page3_image_height / 2;
            $fw = $this->frame_w($f);
            $fx = $this->frame_x($f, true);
            $x = $fx + $fw / 2 - $fw * $this->page3_image_width / 2;
            //trigger_error("F=$f; FX=$fx; FW=$fw; X=$x; Y=$x",E_USER_ERROR); 
            // $this->draw_rect($x,$y,$fw*$this->page3_image_width,$fh*$this->page3_image_height);
            if (!empty($image_url)) $this->Image($image_url, $x, $y, $fw * $this->page3_image_width, $fh * $this->page3_image_height);
        }
    }



    public $barcode_center_row = 5;

    private function draw_barcode($f, $riders)
    {
        extract($this->frame_dims());
        $baseline_skip = $fh / ($this->lpf + 1);
        $barcode_center_y = $baseline_skip * $this->barcode_center_row + 0.1 * $baseline_skip;
        $barcode_height = $baseline_skip * 1.2;
        for ($c = 0; $c < $nc; $c++) {
            $rider = (isset($riders[$c])) ? $riders[$c] : [];
            $rusa_id = (!empty($rider['rusa_id'])) ? $rider['rusa_id'] : '';
            $last_name = (isset($rider['last_name'])) ? strtoupper($rider['last_name']) : '';
            $first_init = (isset($rider['first_name'])) ? strtoupper($rider['first_name'])[0] : '';
            $code = $rusa_id . $last_name . $first_init;
            if (empty($code)) continue;
            $y = $this->frame_y($c) + $barcode_center_y - $barcode_height / 2;
            $fw = $this->frame_w($f);
            $barcode_width = 0.9 * $fw;
            $x = $this->frame_x($f, true) + $fw / 2 - $barcode_width / 2;
            $this->code128($x, $y, $code, $barcode_width, $barcode_height);
        }
    }



    // High Level Rendering Functions

    // Card Outside


    public function draw_card_outside($route_event, $riders = [])
    {
        $logo_fold = $this->n_folds - 1;
        $sig_fold = $this->n_folds - 2;
        $page3_fold = $this->n_folds - 3;
        $page4_fold = $this->n_folds - 4;
        // $this->draw_fold_lines();
        $this->draw_cut_lines();
        $this->draw_frames($this->thin_width, self::LIGHT_GRAY);
        $nc = $this->n_cards;
        $logo_url = $this->rusa_logo_url;
        if (!empty($route_event['icon_url']))    $logo_url = $route_event['icon_url'];
        switch ($this->n_folds) {
            case 2:
                $this->em_points = 14;
                $this->lpf = 16;
                $this->logo_width = 0.62;
                $this->draw_logo($logo_fold, $logo_url);
                for ($c = 0; $c < $nc; $c++) {
                    $this->render_text_lines($c, $logo_fold, $this->front_body_text($route_event), true); // front is rightmost fold
                }
                $this->em_points = 11;
                $this->lpf = 22;
                for ($c = 0; $c < $nc; $c++) {
                    $r = (isset($riders[$c])) ? $riders[$c] : [];
                    $this->render_text_lines($c, $sig_fold, $this->back_body_text($route_event, $r), true); // back is leftmost fold
                }
                $this->SetFillColor(0);
                // 				for($c=0; $c<$nc; $c++){ 
                // 					$this->draw_barcode($sig_fold,$riders);
                // 				}
                break;
            case 3:
                $this->em_points = 10;
                $this->lpf = 16;
                $this->logo_width = 0.93;
                $this->draw_logo($logo_fold, $logo_url);
                for ($c = 0; $c < $nc; $c++) {
                    $this->render_text_lines($c, $logo_fold, $this->front_body_text($route_event), true); // front is rightmost fold
                }

                $this->em_points = 9;
                $this->lpf = 25;
                for ($c = 0; $c < $nc; $c++) {
                    $r = (isset($riders[$c])) ? $riders[$c] : [];
                    $this->render_text_lines($c, $sig_fold, $this->back_body_text($route_event, $r), true); // back is leftmost fold
                }
                $this->SetFillColor(0);
                // 				for($c=0; $c<$nc; $c++){ 
                // 					$this->draw_barcode($sig_fold,$riders);
                // 				}

                // $logo_url = $this->CI->model_parando->get_card_image($route_event['event_id'], 3);
                $logo_url = key_exists('page3_image', $route_event) ? $route_event['page3_image'] : null;

                for ($c = 0; $c < $nc; $c++) {
                    $this->draw_page3($page3_fold, $logo_url);
                }

                break;
            case 4:
                $this->em_points = 10;
                $this->lpf = 16;
                $this->logo_width = 0.87;
                $this->draw_logo($logo_fold, $logo_url);
                for ($c = 0; $c < $nc; $c++) {
                    $this->render_text_lines($c, $logo_fold, $this->front_body_text($route_event), true); // front is rightmost fold
                }

                $this->em_points = 9;
                $this->lpf = 25;
                for ($c = 0; $c < $nc; $c++) {
                    $r = (isset($riders[$c])) ? $riders[$c] : [];
                    $this->render_text_lines($c, $sig_fold, $this->back_body_text($route_event, $r), true); // back is leftmost fold
                }
                $this->SetFillColor(0);

                // 				for($c=0; $c<$nc; $c++){ 
                // 					$this->draw_barcode($sig_fold,$riders);
                // 				}

                $logo_url = key_exists('page3_image', $route_event) ? $route_event['page3_image'] : null;

                for ($c = 0; $c < $nc; $c++) {
                    $this->draw_page3($page3_fold, $logo_url);
                }

                // $logo_url = $this->CI->model_parando->get_card_image($route_event['event_id'], 4);

                for ($c = 0; $c < $nc; $c++) {
                    $this->draw_page3($page4_fold, null);
                }

                break;
            default:
                trigger_error("Cards with " . $this->n_folds . " panels are unsupported", E_USER_ERROR);
                break;
        }
    }

    // Render the marked up text $lines array at fold $f of the card

    private function render_text_lines($c, $f, $lines, $outer = false)
    {
        extract($this->frame_dims());
        $baseline_skip = $fh / ($this->lpf + 1);

        $this->SetLineWidth($this->thin_width);
        $this->SetDrawColor(self::BLACK);

        // for($c=0; $c<$nc; $c++){ // for each card


        $y = $this->frame_y($c); //$fm + $c*$h/$nc;
        $x = $this->frame_x($f, $outer); //$fm + $f*$fw + $f*$ifm;
        $fw = $this->frame_w($f);

        if (false && $c == 0) { // debug text
            $this->SetXY($fm, ($h / $this->n_cards) - 4 * $baseline_skip);
            $this->MultiCell($fw, 2 * $baseline_skip, "w=$w; fw=$fw; f=$f; fm=$fm; ifm=$ifm; x=$x; y=$y");
        }

        $this->SetXY($x, $y - $baseline_skip);
        $last_height = $baseline_skip;

        foreach ($lines as $line) { // for each line
            $style = (isset($line['style'])) ? array_fill_keys(explode(',', $line['style']), true) : [];
            $font = (isset($line['font'])) ? explode(',', $line['font']) : [];

            foreach ($font as $method) $this->{'font_' . $method}();

            $row = (isset($line['row'])) ? $line['row'] : 0;
            $text = (isset($line['text'])) ? $this->my_utf8_decode($line['text']) : ''; //$text = "($x)$text";
            $tmargin = (isset($line['tmargin'])) ? $line['tmargin'] : 0;
            $fill = (isset($line['fill'])) ? explode(',', $line['fill']) : [255, 255, 255];
            $height = (isset($line['height'])) ? $line['height'] * $baseline_skip : $baseline_skip;
            $text = (isset($style['toupper'])) ? strtoupper($text) : $text;
            $indent = (isset($line['col']) && is_numeric($line['col']) && $line['col'] > 1) ? str_repeat(' ', $line['col'] - 1) : '';
            $border = (isset($style['border'])) ? 1 : 0;
            $justify = (isset($line['align'])) ? $line['align'] : 'C';

            if (isset($line['fill'])) $this->SetFillColor(...$fill);
            if (isset($line['fontsize'])) $this->font_resize($line['fontsize']);

            $string_width = $this->GetStringWidth($indent . $text);
            $dash_width = $this->GetStringWidth('-');

            if ($row > 0) {
                $this->SetY($y + $baseline_skip / 2 + ($row - 1) * $baseline_skip);
            } elseif (!isset($line['align']) || ($line['align'] != 'F' && $line['align'] != 'R')) {
                $this->SetY($this->GetY() + $last_height + $tmargin * $baseline_skip);
            }

            if (isset($style['multi'])) { // multiline text cell

                $width = (isset($line['width'])) ? $fw * $line['width'] / 100.0 : $fw;
                if (isset($line['align']) && $line['align'] == 'C') {
                    $this->SetX($x + ($fw - $width) / 2);
                } elseif (isset($line['align']) && $line['align'] == 'F') {
                    // stay where we are
                } else {
                    $this->SetX($x);
                }
                $this->MultiCell($width, $height, $indent . $text, $border, "C", isset($line['fill']));  // text

            } elseif (isset($style['rect'])) { // rectangle without text

                $width = (isset($line['width'])) ? $fw * $line['width'] / 100.0 : $fw;
                if (isset($line['align']) && $line['align'] == 'C') {
                    $rect_x = ($x + ($fw - $width) / 2);
                } elseif (isset($line['align']) && $line['align'] == 'F') {
                    $rect_x = $this->GetX(); // stay where we are
                } else {
                    $rect_x = ($x);
                }
                $style = '';
                if (isset($line['fill'])) $style .= 'F';
                if ($border == 1) $style .= 'D';
                $this->Rect($rect_x, $this->GetY(), $width, $height, $style);  // text

            } else { // ordinary text cell

                if (isset($style['fit'])) {
                    $save_size = $this->font_size;
                    $save_width = $string_width;
                    if ($string_width > $fw) {
                        $this->font_resize($fw / $string_width);
                        $string_width = $fw;
                    }
                }
                $width = (isset($line['width'])) ? $fw * $line['width'] / 100.0 : $string_width + 2 * $dash_width;
                if (isset($line['align']) && $line['align'] == 'C') {
                    $this->SetX($x + ($fw - $width) / 2);
                } elseif (isset($line['align']) && $line['align'] == 'F') {
                    // stay where we are
                } elseif (isset($line['align']) && $line['align'] == 'R') {
                    $this->SetX($x + ($fw - $width));
                } else {
                    $this->SetX($x);
                }
                $this->Cell($width, $height, $indent . $text, $border, 0, "C");  // text

                if (isset($style['fit'])) {
                    $this->font_resize($save_size);
                    $string_width = $save_width;
                }
            }
            $last_height = (isset($style['multi'])) ? 0 : $height;
        }
        // }

    }


    // Text lines with markup for the front of the card

    private function front_body_text($route_event)
    {
        extract($route_event);

        $type_string = $event_sanction;
        $type_string_size = ($this->n_folds > 2) ? 0.9 : 1;

        if (empty($event_date_str)) {
            $event_date = "Date:________________";
        } else {
            $event_date = $event_date_str;
        }

        switch ($event_sanction) {
            case 'acp':
                $type_string = "Brevet de Randonneurs Mondiaux";
                $distance_string = "Randonnée of {$event_distance}km";
                $sanctioned_by = "Audax Club Parisien";
                $event_date = $event_datetime->format('j F Y');
                $body = [
                    ['text' => $type_string, 'font' => 'normal,bold', 'fontsize' => $type_string_size, 'align' => 'C', 'row' => 1],
                    ['text' => $event_name, 'align' => 'C', 'row' => 8],
                    ['text' => $distance_string, 'align' => 'C', 'font' => 'resize'],
                    ['text' => "Starting from " . $event_location, 'align' => 'C', 'font' => 'plain', 'row' => 10.25],
                    ['text' => "On " . $event_date, 'align' => 'C'],
                    ['text' => 'Organized By', 'align' => 'C', 'font' => 'plain', 'row' => 12.5, 'fontsize' => $type_string_size * 0.8],
                    ['text' => $this_organization, 'align' => 'C', 'font' => 'italic', 'fontsize' => $type_string_size],
                    ['text' => "Verified and Validated Exclusively by", 'align' => 'C', 'font' => 'plain', 'row' => 14.75, 'fontsize' => $type_string_size * 0.8],
                    ['text' => $sanctioned_by, 'align' => 'C', 'font' => 'italic', 'fontsize' => $type_string_size]
                ];
                break;
            case 'rusa':
                if ($event_distance < 200) {
                    $type_string = "RUSA Populaire";
                    $distance_string = "Populaire of {$event_distance}km";
                } else {
                    $type_string = "RUSA Brevet";
                    $distance_string = "Brevet of {$event_distance}km";
                }
                $sanctioned_by = "Randonneurs USA";
                $body = [
                    ['text' => $type_string, 'font' => 'normal,bold', 'fontsize' => $type_string_size, 'align' => 'C', 'row' => 1],
                    ['text' => $event_name, 'align' => 'C', 'row' => 8],
                    ['text' => $distance_string, 'align' => 'C', 'font' => 'resize'],
                    ['text' => "Starting from " . $event_location, 'align' => 'C', 'font' => 'plain', 'row' => 10.25],
                    ['text' => "On " . $event_date, 'align' => 'C'],
                    ['text' => 'Organized By', 'align' => 'C', 'font' => 'plain', 'row' => 12.5, 'fontsize' => $type_string_size * 0.8],
                    ['text' => $this_organization, 'align' => 'C', 'font' => 'italic', 'fontsize' => $type_string_size],
                    ['text' => "Verified and Validated Exclusively by", 'align' => 'C', 'font' => 'plain', 'row' => 14.75, 'fontsize' => $type_string_size * 0.8],
                    ['text' => $sanctioned_by, 'align' => 'C', 'font' => 'italic', 'fontsize' => $type_string_size]
                ];
                break;
            case 'permanent':
                if ($event_distance < 200) {
                    $type_string = "RUSA Permanent Populaire";
                } else {
                    $type_string = "RUSA Permanent";
                }
                $distance_string = "Permanent of {$event_distance}km";
                $organized_by = $organizer_name . ' (' . $organizer_rusa_id . ')';
                $sanctioned_by = "Randonneurs USA";
                $xbody = [
                    ['text' => $type_string, 'font' => 'normal,bold', 'fontsize' => $type_string_size, 'align' => 'C', 'row' => 1],
                    ['text' => $distance_string, 'align' => 'C', 'row' => 8],
                    ['text' => $event_name, 'align' => 'C', 'style' => 'fit'],
                    ['text' => $event_location, 'align' => 'C', 'font' => 'bold'],
                    ['text' => 'Permanent Route# ' . $rusa_route_id, 'align' => 'C', 'fontsize' => $type_string_size * 0.75],
                    ['text' => $event_date, 'align' => 'C'],
                    ['text' => 'Organized By ' . $organized_by, 'align' => 'C', 'font' => 'plain', 'fontsize' => $type_string_size, 'style' => 'fit'],
                    ['text' => "Verified and Validated Exclusively", 'align' => 'C'],
                    ['text' => "by", 'align' => 'C'],
                    ['text' => $sanctioned_by, 'align' => 'C']
                ];
                $body = [
                    ['text' => $type_string, 'font' => 'normal,bold', 'fontsize' => $type_string_size, 'align' => 'C', 'row' => 1],
                    ['text' => $event_name, 'align' => 'C', 'row' => 8, 'style' => 'fit'],
                    ['text' => $distance_string, 'align' => 'C', 'font' => 'resize'],
                    ['text' => 'Route# ' . $rusa_route_id, 'align' => 'C', 'fontsize' => $type_string_size * 0.75],
                    ['text' => "Starting from " . $event_location, 'align' => 'C', 'font' => 'plain', 'row' => 11.25],
                    ['text' => "On " . $event_date, 'align' => 'C'],
                    ['text' => 'Organized By ' . $organized_by, 'align' => 'C', 'font' => 'plain', 'fontsize' => $type_string_size * 0.9, 'style' => 'fit', 'row' => 13.5],
                    ['text' => "Verified and Validated Exclusively by", 'align' => 'C', 'font' => 'plain', 'row' => 14.75, 'fontsize' => $type_string_size * 0.8],
                    ['text' => $sanctioned_by, 'align' => 'C', 'font' => 'italic', 'fontsize' => $type_string_size]
                ];
                break;
            default:
                throw new \Exception("Unknown event sanction '$event_sanction'. Can't render.");
                break;
        }
        return $body;
    }

    private function back_body_text($route_event, $rider = null)
    {
        extract($route_event);

        $ebox_width = ($this->n_folds > 2) ? 95 : 95;

        $type_string = $event_sanction;
        $rusa_id = (!empty($rider['rusa_id'])) ? $rider['rusa_id'] : '';
        $last_name = (isset($rider['last_name'])) ? ($rider['last_name'] . ', ') : 'Name: ';
        $first_name = (isset($rider['first_name'])) ? ($rider['first_name']) : '';
        $street = ''; // $street=(isset($rider['m_street']))?($rider['m_street']):'';
        $city = (isset($rider['city'])) ? $rider['city'] : '';
        $state = (isset($rider['state'])) ? $rider['state'] : '';
        $zip = ''; // $zip=(isset($rider['m_zip']))?$rider['m_zip']:'';
        $country = (isset($rider['country'])) ? $rider['country'] : '';

        $start_code = (!empty($rider['rusa_id'])) ?
            $this->cryptoLibrary->make_start_code($route_event, $rusa_id, $epp_secret) : '';



        if (empty($street . $city . $state . $zip . $country)) {
            $address1 = 'Address:';
            $address2 = '';
        } else {
            $city_state = "$city $state"; // $zip";	
            if (!empty($country)) $city_state .= " ($country)";
            if (empty($street)) {
                $address1 = $city_state;
                $address2 = '';
            } else {
                $address1 = $street;
                $address2 = $city_state;
            }
        }

        if (isset($route_event['organizer_name'], $route_event['organizer_phone'])) {
            $emergency_contact = $route_event['organizer_name'] . ' ' . $route_event['organizer_phone'];
        } else {
            trigger_error("Emergency contact not set (organizer_name and organizer_phone), can't render brevet card.", E_USER_ERROR);
        }

        switch ($event_sanction) {
            case 'acp':
                $cert = 'Homologation';
                break;
            case 'rusa':
                $cert = 'Certification';
                break;
            default:
                $cert = 'Validation';
                break;
        }

        $ai = 5;

        $body = [
            ['text' => "$last_name", 'style' => 'toupper', 'font' => 'resize,bold', 'row' => 1],
            ['text' => "$first_name", 'font' => 'normal,plain', 'align' => 'F'],
            ['text' => $address1, 'col' => $ai],
            ['text' => $address2, 'col' => $ai],
            ['text' => "RUSA Num: $rusa_id", 'font' => 'plain', 'col' => $ai],

            ['text' => "START CODE: ", 'font' => 'plain', 'col' => $ai, 'tmargin' => 0.25],
            ['text' => "$start_code", 'font' => 'bold', 'align' => 'F'],

            ['text' => "(Cue Ver: $cue_version_str)    ", 'font' => 'plain', 'align' => 'R'],

            ['text' => "FINISH CODE: ", 'font' => 'plain', 'col' => $ai, 'tmargin' => 0.25],
            ['width' => 25, 'style' => 'border', 'align' => 'F', 'height' => 1.25],
            ['text' => " ** Required for EPP/eBrevet **", 'align' => 'F', 'font' => 'superfineprint',],



            ['text' => 'Rider Signature at Finish:', 'font' => 'plain,resize', 'tmargin' => 0.25],
            ['width' => 90, 'style' => 'border', 'align' => 'C', 'height' => 2],
            ['text' => 'Ride Completed in ', 'tmargin' => 0.75],
            ['width' => 15, 'style' => 'border', 'align' => 'F'], ['text' => 'Hrs', 'align' => 'F'],
            ['width' => 15, 'style' => 'border', 'align' => 'F'], ['text' => 'Mins', 'align' => 'F'],
            ['text' => 'Organizer Signature:', 'tmargin' => 0.5],
            ['width' => 90, 'style' => 'border', 'align' => 'C', 'height' => 2],
            ['style' => 'border,rect', 'width' => 90, 'tmargin' => 0.5, 'height' => 2.5, 'align' => 'C', 'fill' => '255,255,224'],
            ['text' => "If abandoning ride or to report a problem call", 'row' => 16.5, 'align' => 'C', 'fontsize' => 0.75],
            ['text' => $emergency_contact, 'align' => 'C', 'font' => 'bold', 'row' => 17.25, 'fontsize' => 0.85],
            ['text' => "For Medical/Safety Emergencies Call 911 First!", 'row' => 18.0, 'align' => 'C', 'font' => 'underline', 'fontsize' => 0.75],
            ['text' => "$cert:", 'tmargin' => 0.25, 'font' => 'plain,resize']
        ];

        return $body;
    }


    // Card Inside

    public function draw_card_inside($controles, $start_stamp = null)
    {
        switch ($this->n_folds) {
            case 2:
                $this->em_points = 8.5;
                $this->fine_print = 0.75;
                break;
            case 3:
                $this->em_points = 7;
                $this->fine_print = 0.62;
                break;
            case 4:
                $this->em_points = 7;
                $this->fine_print = 0.62;
                break;
            default:
                trigger_error("Cards with " . $this->n_folds . " panels are unsupported", E_USER_ERROR);
                break;
        }
        // $this->draw_fold_lines();
        $this->draw_cut_lines();
        $this->draw_frames($this->thick_width, self::BLACK);
        $this->draw_controles($controles, $start_stamp);
    }

    private $cert_text = "Stamp or Initials";
    private $datetime_format = '';
    private $datetime_format_specific = 'M-d H:i T';
    private $datetime_format_generic = '\D\a\y-z H:i';

    public function set_controle_date_format($re)
    {
        $this->datetime_format = (empty($re['event_date_str']) || empty($re['event_time_str'])) ? $this->datetime_format_generic : $this->datetime_format_specific;
    }

    private $postcard_text = "Mail postcard";

    private function controle_body_text($controle)
    {

        $ca = $controle['attributes'];
        $style_text = strtolower($ca['style'] ?? "unspecified");
        $event_tz = $this->event['event_tz'];

        $open_time_str = $controle['open']->setTimezone($event_tz)->format($this->datetime_format);
        $close_time_str = $controle['close']->setTimezone($event_tz)->format($this->datetime_format);

        $cd_mi = round($controle['d'] / (self::m_per_km * self::km_per_mi), 1);
        $cd_km = round($controle['d'] / (self::m_per_km), 1);
        $is_start = (isset($controle['start'])) ? " [START]" : "";
        $is_finish = (isset($controle['finish'])) ? " [FINISH]" : "";
        $dzaddress = $this->delete_zipcode($ca['address'] ?? "NO ADDRESS");

        switch ($style_text) {
            case 'overnight':
            case 'staffed':
            case 'merchant':
            case 'open':
            case 'unspecified':
                if(array_key_exists('timed',$ca) && strtolower($ca['timed']) == 'no'){
                    $body = [
                        ['text' => ($ca['name'] ?? 'NO NAME'), 'row' => 1, 'col' => 3, 'style' => 'I'],
                        ['text' => $dzaddress, 'row' => 2, 'col' => 3, 'style' => 'I'],
                        ['text' => "Distance:",  'row' => 4, 'style' => 'B'],
                        ['text' => $cd_km . ' km / ' . $cd_mi . ' mi', 'row' => 4, 'col' => 16]
                    ];
                }else{
                    $body = [
                        ['text' => ($ca['name'] ?? 'NO NAME'), 'row' => 1, 'col' => 3, 'style' => 'I'],
                        ['text' => $dzaddress, 'row' => 2, 'col' => 3, 'style' => 'I'],
                        ['text' => 'Open:', 'row' => 4, 'style' => 'B'],
                        ['text' => $open_time_str, 'row' => 4, 'col' => 11],
                        ['text' => 'Close:', 'row' => 5, 'style' => 'B'],
                        ['text' => $close_time_str, 'row' => 5, 'col' => 11],
                        ['text' => "Distance:",  'row' => 6, 'style' => 'B'],
                        ['text' => $cd_km . ' km / ' . $cd_mi . ' mi', 'row' => 6, 'col' => 16]
                    ];
                }
                
                
                break;
            case 'info':
                $body = [
                    ['text' => $ca['name'], 'row' => 1, 'col' => 3, 'style' => 'I'],
                    ['text' => $dzaddress, 'row' => 2, 'col' => 3, 'style' => 'I'],
                    ['text' => "Distance:",  'row' => 4, 'style' => 'B'],
                    ['text' => $cd_km . ' km / ' . $cd_mi . ' mi', 'row' => 4, 'col' => 16],
                    ['text' => 'Q:', 'row' => 5, 'style' => 'B'],
                    ['text' => $ca['question'], 'row' => 5, 'col' => 6]
                ];
                break;
            case 'photo':
                $body = [
                    ['text' => $ca['name'], 'row' => 1, 'col' => 3, 'style' => 'I'],
                    ['text' => $dzaddress, 'row' => 2, 'col' => 3, 'style' => 'I']
                ];
                if ($is_finish) {
                    $body[] = ['text' => $dzaddress, 'row' => 2, 'col' => 3, 'style' => 'I'];
                    $body[] = ['text' => 'Open:', 'row' => 3, 'style' => 'B'];
                    $body[] = ['text' => $open_time_str, 'row' => 3, 'col' => 11];
                    $body[] = ['text' => 'Close:', 'row' => 4, 'style' => 'B'];
                    $body[] = ['text' => $close_time_str, 'row' => 4, 'col' => 11];
                    $body[] = ['text' => "Distance:",  'row' => 5, 'style' => 'B'];
                    $body[] = ['text' => $cd_km . ' km / ' . $cd_mi . ' mi', 'row' => 5, 'col' => 16];
                    $body[] = ['text' => 'Take photo:', 'row' => 6, 'style' => 'B'];
                    $body[] = ['text' => $ca['photo'], 'row' => 6, 'col' => 19];
                } else {
                    $body[] = ['text' => "Distance:",  'row' => 4, 'style' => 'B'];
                    $body[] = ['text' => $cd_km . ' km / ' . $cd_mi . ' mi', 'row' => 4, 'col' => 16];
                    $body[] = ['text' => 'Take photo:', 'row' => 5, 'style' => 'B'];
                    $body[] = ['text' => $ca['photo'], 'row' => 5, 'col' => 19];
                }
                break;
            case 'postcard':
                $body = [
                    ['text' => $ca['name'], 'row' => 1, 'col' => 3, 'style' => 'I'],
                    ['text' => $dzaddress, 'row' => 2, 'col' => 3, 'style' => 'I'],
                    ['text' => "Distance:",  'row' => 4, 'style' => 'B'],
                    ['text' => $cd_km . ' km / ' . $cd_mi . ' mi', 'row' => 4, 'col' => 16],
                    ['text' => $this->postcard_text, 'row' => 5]
                ];
                break;
            default:
                trigger_error(__METHOD__ . " Unknown controle style '$style_text'. Can't render.", E_USER_ERROR);
                break;
        }
        return $body;
    }

    private function delete_zipcode($str)
    {
        $str = preg_replace('/\s*\d{5}\s*$/', '', $str);
        return $str;
    }

    private function draw_controles($controles, $start_stamp = null)
    {
        extract($this->frame_dims());

        $nh = ($fh - 2 * $this->cmargin) / $this->cpf;
        $baseline_skip = $nh / ($this->lpc + 1);
        $this->SetLineWidth($this->thin_width);
        $this->SetDrawColor(self::BLACK);

        for ($c = 0; $c < $this->n_cards; $c++) {

            $n = 0;
            $controle_count = $this->controle_index_origin;
            $num_controles = count($controles);

            foreach ($controles as $controle) {

                $ca = $controle['attributes'];
                $style_text = $ca['style'] ?? 'unspecified';

                $f = (int)($n / $this->cpf);
                $fw = $this->frame_w($f);
                $nw = $fw - 2 * $this->cmargin;

                $x = $this->frame_x($f) + $this->cmargin;
                $y0 = $this->frame_y($c); //($c+1)*$fm + $c*$fh;
                $y = $y0 + ($n % $this->cpf) * $nh + $this->cmargin;
                $n++;
                $this->SetXY($x, $y); // upper left of control cell
                $this->Cell($nw, $nh, "", 1);  // Controle Border

                if ($style_text == 'staffed' || $style_text == 'overnight' || $style_text == 'merchant' || $style_text == 'open') {  // Draw stamp region for Timed Controles
                    $nw_text_box = $nw * (1 - $this->stamp_width_fraction);
                    $cc_y = $y;
                    $cc_x = $x + $nw * (1 - $this->stamp_width_fraction); // upper left of control cell
                    $cc_w = $nw * $this->stamp_width_fraction;
                    $cc_h = $nh;
                    $this->SetXY($cc_x, $cc_y);
                    $this->Cell($cc_w, $cc_h, "", 1);  // Stamp Border				

                    if (filter_var($start_stamp, FILTER_VALIDATE_URL) && $n == 1) {
                        $this->font_resize();
                        $this->font_bold();
                        $this->Image($start_stamp, $cc_x + $cc_w / 4, $cc_y + $cc_h / 5, $cc_w / 2, $cc_w / 2);
                        $this->SetXY($cc_x, $cc_y + 2 * $cc_h / 3);
                        $open_time_str = $controle['open']->format('H:i');
                        $this->Cell($cc_w, $baseline_skip, $open_time_str, 0, 0, "C");
                        $this->font_plain();
                    }

                    $this->font_fineprint();
                    $this->SetXY($x + $nw_text_box, $y + $baseline_skip / 2); // first line
                    $this->Cell($nw * $this->stamp_width_fraction, $baseline_skip, "Arrival Time:", 0, 0, "C");  // text
                    $this->SetXY($x + $nw_text_box, $y + $baseline_skip / 2 + ($this->lpc - 1) * $baseline_skip); // last line
                    $this->Cell($nw * $this->stamp_width_fraction, $baseline_skip, $this->cert_text, 0, 0, "C");  // text
                    $this->font_resize();
                    $nw_text = $nw_text_box; //-$baseline_skip;	// text width is narrower than box
                } else {
                    $nw_text = $nw; //-$baseline_skip;
                }

                if (true) { // Counted controles
                    if ($controle_count == 0 || isset($controle['start'])) {
                        $controle_count_text = "Start Controle";
                    } elseif (isset($controle['finish']) || $this->controle_index_origin == 0 && $controle_count == ($this->controle_index_origin + $num_controles - 1)) {
                        $controle_count_text = "Finish Controle";
                    } else {
                        $controle_count_text = "Controle $controle_count";
                    }
                    $this->font_bold();
                    $this->SetXY($x, $y + $baseline_skip / 2); // first line
                    $this->Cell($nw_text, $baseline_skip, $controle_count_text, 0, 0, "L");  // text
                    $controle_count++;
                    $this->font_plain();
                    $this->SetXY($x, $y + $baseline_skip / 2); // first line
                    $this->Cell($nw_text, $baseline_skip, strtoupper($style_text), 0, 0, "R");  // text
                } else {
                    $this->font_bold();
                    $this->SetXY($x, $y + $baseline_skip / 2); // first line
                    $this->Cell($nw_text, $baseline_skip, $style_text, 0, 0, "L");  // text
                }
                $lines = $this->controle_body_text($controle);
                foreach ($lines as $line) {
                    $font_style = (empty($line['style'])) ? '' : $line['style'];
                    $this->font_style($font_style);
                    $dash_width = $this->GetStringWidth('-');
                    $indent = (isset($line['col'])) ? ($line['col'] - 1) * $dash_width : 0;
                    $this->SetXY($x + $indent, $y + $baseline_skip / 2 + $line['row'] * $baseline_skip);
                    $this->MultiCell($nw_text - $indent, $baseline_skip, $line['text'], 0, "L");  // text
                }
                $this->font_normal();
            }
        }
    }

    /*******************************************************************************
     * Script :  PDF_Code128  http://www.fpdf.org/en/script/script88.php
     * Version : 1.2
     * Date :    2016-01-31
     * Auteur :  Roland Gautier
     *
     * Version   Date        Detail
     * 1.2       2016-01-31  Compatibility with FPDF 1.8
     * 1.1       2015-04-10  128 control characters FNC1 to FNC4 accepted
     * 1.0       2008-05-20  First release	// CODE128 Bar Codes	
     ********************************************************************************/

    protected $T128;                                         // Tableau des codes 128
    protected $ABCset = "";                                  // jeu des caractères éligibles au C128
    protected $Aset = "";                                    // Set A du jeu des caractères éligibles
    protected $Bset = "";                                    // Set B du jeu des caractères éligibles
    protected $Cset = "";                                    // Set C du jeu des caractères éligibles
    protected $SetFrom;                                      // Convertisseur source des jeux vers le tableau
    protected $SetTo;                                        // Convertisseur destination des jeux vers le tableau
    protected $JStart = array("A" => 103, "B" => 104, "C" => 105); // Caractères de sélection de jeu au début du C128
    protected $JSwap = array("A" => 101, "B" => 100, "C" => 99);   // Caractères de changement de jeu

    //____________________________ Extension du constructeur _______________________
    private function code128_init()
    {

        $this->T128[] = array(2, 1, 2, 2, 2, 2);           //0 : [ ]               // composition des caractères
        $this->T128[] = array(2, 2, 2, 1, 2, 2);           //1 : [!]
        $this->T128[] = array(2, 2, 2, 2, 2, 1);           //2 : ["]
        $this->T128[] = array(1, 2, 1, 2, 2, 3);           //3 : [#]
        $this->T128[] = array(1, 2, 1, 3, 2, 2);           //4 : [$]
        $this->T128[] = array(1, 3, 1, 2, 2, 2);           //5 : [%]
        $this->T128[] = array(1, 2, 2, 2, 1, 3);           //6 : [&]
        $this->T128[] = array(1, 2, 2, 3, 1, 2);           //7 : [']
        $this->T128[] = array(1, 3, 2, 2, 1, 2);           //8 : [(]
        $this->T128[] = array(2, 2, 1, 2, 1, 3);           //9 : [)]
        $this->T128[] = array(2, 2, 1, 3, 1, 2);           //10 : [*]
        $this->T128[] = array(2, 3, 1, 2, 1, 2);           //11 : [+]
        $this->T128[] = array(1, 1, 2, 2, 3, 2);           //12 : [,]
        $this->T128[] = array(1, 2, 2, 1, 3, 2);           //13 : [-]
        $this->T128[] = array(1, 2, 2, 2, 3, 1);           //14 : [.]
        $this->T128[] = array(1, 1, 3, 2, 2, 2);           //15 : [/]
        $this->T128[] = array(1, 2, 3, 1, 2, 2);           //16 : [0]
        $this->T128[] = array(1, 2, 3, 2, 2, 1);           //17 : [1]
        $this->T128[] = array(2, 2, 3, 2, 1, 1);           //18 : [2]
        $this->T128[] = array(2, 2, 1, 1, 3, 2);           //19 : [3]
        $this->T128[] = array(2, 2, 1, 2, 3, 1);           //20 : [4]
        $this->T128[] = array(2, 1, 3, 2, 1, 2);           //21 : [5]
        $this->T128[] = array(2, 2, 3, 1, 1, 2);           //22 : [6]
        $this->T128[] = array(3, 1, 2, 1, 3, 1);           //23 : [7]
        $this->T128[] = array(3, 1, 1, 2, 2, 2);           //24 : [8]
        $this->T128[] = array(3, 2, 1, 1, 2, 2);           //25 : [9]
        $this->T128[] = array(3, 2, 1, 2, 2, 1);           //26 : [:]
        $this->T128[] = array(3, 1, 2, 2, 1, 2);           //27 : [;]
        $this->T128[] = array(3, 2, 2, 1, 1, 2);           //28 : [<]
        $this->T128[] = array(3, 2, 2, 2, 1, 1);           //29 : [=]
        $this->T128[] = array(2, 1, 2, 1, 2, 3);           //30 : [>]
        $this->T128[] = array(2, 1, 2, 3, 2, 1);           //31 : [?]
        $this->T128[] = array(2, 3, 2, 1, 2, 1);           //32 : [@]
        $this->T128[] = array(1, 1, 1, 3, 2, 3);           //33 : [A]
        $this->T128[] = array(1, 3, 1, 1, 2, 3);           //34 : [B]
        $this->T128[] = array(1, 3, 1, 3, 2, 1);           //35 : [C]
        $this->T128[] = array(1, 1, 2, 3, 1, 3);           //36 : [D]
        $this->T128[] = array(1, 3, 2, 1, 1, 3);           //37 : [E]
        $this->T128[] = array(1, 3, 2, 3, 1, 1);           //38 : [F]
        $this->T128[] = array(2, 1, 1, 3, 1, 3);           //39 : [G]
        $this->T128[] = array(2, 3, 1, 1, 1, 3);           //40 : [H]
        $this->T128[] = array(2, 3, 1, 3, 1, 1);           //41 : [I]
        $this->T128[] = array(1, 1, 2, 1, 3, 3);           //42 : [J]
        $this->T128[] = array(1, 1, 2, 3, 3, 1);           //43 : [K]
        $this->T128[] = array(1, 3, 2, 1, 3, 1);           //44 : [L]
        $this->T128[] = array(1, 1, 3, 1, 2, 3);           //45 : [M]
        $this->T128[] = array(1, 1, 3, 3, 2, 1);           //46 : [N]
        $this->T128[] = array(1, 3, 3, 1, 2, 1);           //47 : [O]
        $this->T128[] = array(3, 1, 3, 1, 2, 1);           //48 : [P]
        $this->T128[] = array(2, 1, 1, 3, 3, 1);           //49 : [Q]
        $this->T128[] = array(2, 3, 1, 1, 3, 1);           //50 : [R]
        $this->T128[] = array(2, 1, 3, 1, 1, 3);           //51 : [S]
        $this->T128[] = array(2, 1, 3, 3, 1, 1);           //52 : [T]
        $this->T128[] = array(2, 1, 3, 1, 3, 1);           //53 : [U]
        $this->T128[] = array(3, 1, 1, 1, 2, 3);           //54 : [V]
        $this->T128[] = array(3, 1, 1, 3, 2, 1);           //55 : [W]
        $this->T128[] = array(3, 3, 1, 1, 2, 1);           //56 : [X]
        $this->T128[] = array(3, 1, 2, 1, 1, 3);           //57 : [Y]
        $this->T128[] = array(3, 1, 2, 3, 1, 1);           //58 : [Z]
        $this->T128[] = array(3, 3, 2, 1, 1, 1);           //59 : [[]
        $this->T128[] = array(3, 1, 4, 1, 1, 1);           //60 : [\]
        $this->T128[] = array(2, 2, 1, 4, 1, 1);           //61 : []]
        $this->T128[] = array(4, 3, 1, 1, 1, 1);           //62 : [^]
        $this->T128[] = array(1, 1, 1, 2, 2, 4);           //63 : [_]
        $this->T128[] = array(1, 1, 1, 4, 2, 2);           //64 : [`]
        $this->T128[] = array(1, 2, 1, 1, 2, 4);           //65 : [a]
        $this->T128[] = array(1, 2, 1, 4, 2, 1);           //66 : [b]
        $this->T128[] = array(1, 4, 1, 1, 2, 2);           //67 : [c]
        $this->T128[] = array(1, 4, 1, 2, 2, 1);           //68 : [d]
        $this->T128[] = array(1, 1, 2, 2, 1, 4);           //69 : [e]
        $this->T128[] = array(1, 1, 2, 4, 1, 2);           //70 : [f]
        $this->T128[] = array(1, 2, 2, 1, 1, 4);           //71 : [g]
        $this->T128[] = array(1, 2, 2, 4, 1, 1);           //72 : [h]
        $this->T128[] = array(1, 4, 2, 1, 1, 2);           //73 : [i]
        $this->T128[] = array(1, 4, 2, 2, 1, 1);           //74 : [j]
        $this->T128[] = array(2, 4, 1, 2, 1, 1);           //75 : [k]
        $this->T128[] = array(2, 2, 1, 1, 1, 4);           //76 : [l]
        $this->T128[] = array(4, 1, 3, 1, 1, 1);           //77 : [m]
        $this->T128[] = array(2, 4, 1, 1, 1, 2);           //78 : [n]
        $this->T128[] = array(1, 3, 4, 1, 1, 1);           //79 : [o]
        $this->T128[] = array(1, 1, 1, 2, 4, 2);           //80 : [p]
        $this->T128[] = array(1, 2, 1, 1, 4, 2);           //81 : [q]
        $this->T128[] = array(1, 2, 1, 2, 4, 1);           //82 : [r]
        $this->T128[] = array(1, 1, 4, 2, 1, 2);           //83 : [s]
        $this->T128[] = array(1, 2, 4, 1, 1, 2);           //84 : [t]
        $this->T128[] = array(1, 2, 4, 2, 1, 1);           //85 : [u]
        $this->T128[] = array(4, 1, 1, 2, 1, 2);           //86 : [v]
        $this->T128[] = array(4, 2, 1, 1, 1, 2);           //87 : [w]
        $this->T128[] = array(4, 2, 1, 2, 1, 1);           //88 : [x]
        $this->T128[] = array(2, 1, 2, 1, 4, 1);           //89 : [y]
        $this->T128[] = array(2, 1, 4, 1, 2, 1);           //90 : [z]
        $this->T128[] = array(4, 1, 2, 1, 2, 1);           //91 : [{]
        $this->T128[] = array(1, 1, 1, 1, 4, 3);           //92 : [|]
        $this->T128[] = array(1, 1, 1, 3, 4, 1);           //93 : [}]
        $this->T128[] = array(1, 3, 1, 1, 4, 1);           //94 : [~]
        $this->T128[] = array(1, 1, 4, 1, 1, 3);           //95 : [DEL]
        $this->T128[] = array(1, 1, 4, 3, 1, 1);           //96 : [FNC3]
        $this->T128[] = array(4, 1, 1, 1, 1, 3);           //97 : [FNC2]
        $this->T128[] = array(4, 1, 1, 3, 1, 1);           //98 : [SHIFT]
        $this->T128[] = array(1, 1, 3, 1, 4, 1);           //99 : [Cswap]
        $this->T128[] = array(1, 1, 4, 1, 3, 1);           //100 : [Bswap]                
        $this->T128[] = array(3, 1, 1, 1, 4, 1);           //101 : [Aswap]
        $this->T128[] = array(4, 1, 1, 1, 3, 1);           //102 : [FNC1]
        $this->T128[] = array(2, 1, 1, 4, 1, 2);           //103 : [Astart]
        $this->T128[] = array(2, 1, 1, 2, 1, 4);           //104 : [Bstart]
        $this->T128[] = array(2, 1, 1, 2, 3, 2);           //105 : [Cstart]
        $this->T128[] = array(2, 3, 3, 1, 1, 1);           //106 : [STOP]
        $this->T128[] = array(2, 1);                       //107 : [END BAR]

        for ($i = 32; $i <= 95; $i++) {                                            // jeux de caractères
            $this->ABCset .= chr($i);
        }
        $this->Aset = $this->ABCset;
        $this->Bset = $this->ABCset;

        for ($i = 0; $i <= 31; $i++) {
            $this->ABCset .= chr($i);
            $this->Aset .= chr($i);
        }
        for ($i = 96; $i <= 127; $i++) {
            $this->ABCset .= chr($i);
            $this->Bset .= chr($i);
        }
        for ($i = 200; $i <= 210; $i++) {                                           // controle 128
            $this->ABCset .= chr($i);
            $this->Aset .= chr($i);
            $this->Bset .= chr($i);
        }
        $this->Cset = "0123456789" . chr(206);

        for ($i = 0; $i < 96; $i++) {                                                   // convertisseurs des jeux A & B
            @$this->SetFrom["A"] .= chr($i);
            @$this->SetFrom["B"] .= chr($i + 32);
            @$this->SetTo["A"] .= chr(($i < 32) ? $i + 64 : $i - 32);
            @$this->SetTo["B"] .= chr($i);
        }
        for ($i = 96; $i < 107; $i++) {                                                 // contrôle des jeux A & B
            @$this->SetFrom["A"] .= chr($i + 104);
            @$this->SetFrom["B"] .= chr($i + 104);
            @$this->SetTo["A"] .= chr($i);
            @$this->SetTo["B"] .= chr($i);
        }
    }

    //________________ Fonction encodage et dessin du code 128 _____________________
    public function code128($x, $y, $code, $w, $h)
    {

        if (empty($this->T128)) $this->code128_init();

        $Aguid = "";                                                                      // Création des guides de choix ABC
        $Bguid = "";
        $Cguid = "";
        for ($i = 0; $i < strlen($code); $i++) {
            $needle = substr($code, $i, 1);
            $Aguid .= ((strpos($this->Aset, $needle) === false) ? "N" : "O");
            $Bguid .= ((strpos($this->Bset, $needle) === false) ? "N" : "O");
            $Cguid .= ((strpos($this->Cset, $needle) === false) ? "N" : "O");
        }

        $SminiC = "OOOO";
        $IminiC = 4;

        $crypt = "";
        while ($code > "") {
            // BOUCLE PRINCIPALE DE CODAGE
            $i = strpos($Cguid, $SminiC);                                                // forçage du jeu C, si possible
            if ($i !== false) {
                $Aguid[$i] = "N";
                $Bguid[$i] = "N";
            }

            if (substr($Cguid, 0, $IminiC) == $SminiC) {                                  // jeu C
                $crypt .= chr(($crypt > "") ? $this->JSwap["C"] : $this->JStart["C"]);  // début Cstart, sinon Cswap
                $made = strpos($Cguid, "N");                                             // étendu du set C
                if ($made === false) {
                    $made = strlen($Cguid);
                }
                if (fmod($made, 2) == 1) {
                    $made--;                                                            // seulement un nombre pair
                }
                for ($i = 0; $i < $made; $i += 2) {
                    $crypt .= chr(strval(substr($code, $i, 2)));                          // conversion 2 par 2
                }
                $jeu = "C";
            } else {
                $madeA = strpos($Aguid, "N");                                            // étendu du set A
                if ($madeA === false) {
                    $madeA = strlen($Aguid);
                }
                $madeB = strpos($Bguid, "N");                                            // étendu du set B
                if ($madeB === false) {
                    $madeB = strlen($Bguid);
                }
                $made = (($madeA < $madeB) ? $madeB : $madeA);                         // étendu traitée
                $jeu = (($madeA < $madeB) ? "B" : "A");                                // Jeu en cours

                $crypt .= chr(($crypt > "") ? $this->JSwap[$jeu] : $this->JStart[$jeu]); // début start, sinon swap

                $crypt .= strtr(substr($code, 0, $made), $this->SetFrom[$jeu], $this->SetTo[$jeu]); // conversion selon jeu

            }
            $code = substr($code, $made);                                           // raccourcir légende et guides de la zone traitée
            $Aguid = substr($Aguid, $made);
            $Bguid = substr($Bguid, $made);
            $Cguid = substr($Cguid, $made);
        }                                                                          // FIN BOUCLE PRINCIPALE

        $check = ord($crypt[0]);                                                   // calcul de la somme de contrôle
        for ($i = 0; $i < strlen($crypt); $i++) {
            $check += (ord($crypt[$i]) * $i);
        }
        $check %= 103;

        $crypt .= chr($check) . chr(106) . chr(107);                               // Chaine cryptée complète

        $i = (strlen($crypt) * 11) - 8;                                            // calcul de la largeur du module
        $modul = $w / $i;

        for ($i = 0; $i < strlen($crypt); $i++) {                                      // BOUCLE D'IMPRESSION
            $c = $this->T128[ord($crypt[$i])];
            for ($j = 0; $j < count($c); $j++) {
                $this->Rect($x, $y, $c[$j] * $modul, $h, "F");
                $x += ($c[$j++] + $c[$j]) * $modul;
            }
        }
    }
}
