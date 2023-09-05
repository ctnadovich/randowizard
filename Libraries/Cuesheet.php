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

class Cuesheet extends FPDF {

	// distances	
	const km_per_mi = 1.609344;
	const ft_per_m = 3.2808398950131;
	const m_per_km = 1000.0;

	// colors
	const BLACK=0;
	const LIGHT_GRAY=192;
	const LIGHTEST_GRAY=225;
	const FILL_WARN=[255,255,225];

	// paths
	const cuesheet_path="/home/tomr/public_html/assets/parando/cuesheets";
	const cuesheet_baseurl="https://parando.org/assets/parando/cuesheets";

	// Constructor parameters
	private $orientation='P'; // P - portrait; L - landscape
	private $unit='in'; // in, cm, mm, pt NOT SETTABLE
	private $size='letter'; // A3, A4, A5, letter, legal
  
	// Configuration variables
	public $controle_index_origin=1;  	// if set to 0 the first 
										// controle will be START
										// the first intermediate will be number 1
										// and the final FINISH
	public $logo_url="https://parando.org/images/parando3D.png";
	public $logo_width=0.75; // fraction of frame width
	public $logo_height=0.31; // fraction of frame height
	public $logo_center_y=0.27; // fraction of overall card height
	

	// layout constants
	public $vmargin=1.0;  // space between top/bottom page edge and cues
	public $hmargin=1.0;  // space between left/right page edge and cues
	public $vmargin_L=1.75;  // space between top/bottom page edge and cues
	public $hmargin_L=0.75;  // space between left/right page edge and cues
	public $vmargin_P=1.0;  // space between top/bottom page edge and cues
	public $hmargin_P=1.0;  // space between left/right page edge and cues
	public $baseline_skip = 0.15;  // Space between text lines
	public $row_padding = 0.05; // padding above and below cue text
	public $column_padding=0.05; // Horizontal padding in dist cols
	public $thin_width=0.01; // Thin lines
	public $thick_width=0.02; // Thick lines
	public $font_normal='Helvetica';	// default font family
	public $font_fixed='Courier';	// fixed width font
	public $font_style=''; // 'B', 'I'
	public $font_current;
	public $em_points=9; // default font size
	public $font_size=1; // multiplier
	public $fine_print=0.75; // em size of fine text
	public $big_print=1.5; // em size of big text

	// Cuesheet content
	public $dist_cols=3;  // Number of initial columns that give distances before the turn column
	public $column_headers=["Tot","Seg","Leg","Cue","Description"];
	
    // Other class variables
	private $page_height;
	private $page_width;
	private $event;

	public function __construct($params=[]){
		$this->orientation=(isset($params['orientation']))?$params['orientation']:'P';
		$this->size=(isset($params['size']))?$params['size']:'letter';
		parent::__construct($this->orientation, $this->unit, $this->size);

        if($params==[]) return; 

		$this->event=$params['event'];

		if(!empty($this->event['cue_abbreviations'])){
			if('NONE'==strtoupper(trim($this->event['cue_abbreviations'])))
				$this->cue_abbreviations=[];
			else{
				$abbrevs=explode(',',$this->event['cue_abbreviations']);
				foreach($abbrevs as $abbrev){
					$kv=explode(':',$abbrev);
					if(count($kv)==2){
						list($k,$v)=$kv;
						$k=trim($k); $v=trim($v);
						if(!empty($k)){
							if(!empty($v))
								$this->cue_abbreviations[$k]=$v;
							else if(isset($this->cue_abbreviations[$k]))
								unset($this->cue_abbreviations[$k]);
								
						}
					}
				}
			}
		}

		ksort($this->cue_abbreviations);

		if(!empty($this->event['comment']))
			$this->cuesheet_comment=$this->event['comment'];

		$this->interpolate_and_clean($this->cuesheet_comment);

		if(!empty($this->event['in_case_of_emergency']))
			$this->icoe=$this->event['in_case_of_emergency'];

		$this->interpolate_and_clean($this->icoe);

		$this->page_height = $this->GetPageHeight();
		$this->page_width = $this->GetPageWidth();
		$this->SetMargins(0,0,0);
		switch($this->orientation){
			case 'L':
				$this->vmargin=$this->vmargin_L;
				$this->hmargin=$this->hmargin_L;
				break;
			case 'P':
				$this->vmargin=$this->vmargin_P;
				$this->hmargin=$this->hmargin_P;
				break;
		}
		$this->SetAutoPageBreak(false,0);
		$this->font_normal();
		$this->SetTitle($this->event['event_name_dist']);
		$this->SetSubject($this->event['event_name_dist']);
		$this->SetCreator($this->event['this_organization'] . ' brevet card rendering software');
		$this->SetAuthor($this->event['this_organization']);
	}


	private function interpolate_strings($s,$map){
			$vars=[];
			foreach ($map as $k => $v){
				if((is_string($v) || is_int($v) || is_float($v)) && !empty($k)){
					$tag = '{$' . $k . '}';
					$vars[$tag]=$v . '';
				}
			}
//			return print_r($vars,true);
			return strtr($s,$vars); // . '<pre>'. print_r($vars,true) . '</pre>';
	}

	private function interpolate_and_clean(&$s){
			$s=$this->interpolate_strings($s,$this->event);
			$s=$this->clean_string($s);
	}

	
	public function font_normal(){
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
	public function font_size($x=1){
		$this->font_size = $x;
		$this->SetFont($this->font_current,$this->font_style,$this->em_points*$this->font_size);
	}
	public function font_normalprint(){
		$this->font_size = 1;
		$this->SetFont($this->font_current,$this->font_style,$this->em_points*$this->font_size);
	}
	public function font_fineprint(){
		$this->font_size=$this->fine_print;
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

	// PDF Cuesheet Rendering
	
	public $cue_abbreviations=[
		'L'=>'Left', 
		'R'=>'Right', 
		'T'=>'T Intersection', 
		'B'=>'Bear', 
		'X'=>'Cross', 
		'TRO'=>'To Remain On', 
		'***'=>'Easy to miss', 
		'SS'=>'Stop Sign', 
		'TFL'=>'Traffic Light', 
		'B/C'=>'Becomes', 
		'Q'=>'Quick', 
		'FMR'=>'Follow Main Road', 
		'LMR'=>'Leave Main Road', 
		'NM'=>'Not Marked', 
		'SO'=>'Straight On'
	];

	public $cue_replace=[
		['(Crossing|Cross)', '', 'X'],
		['Continue (straight onto|onto)', 'B/C ', 'SO'],
		['Turn right onto', '', 'R'],
		['Turn left onto', '', 'L'],
		['Turn right (to stay on|to remain on|TRO)', 'TRO ', 'R'],
		['Turn left (to stay on|to remain on|TRO)', 'TRO ', 'L'],
		['T right onto', '', 'TR'],
		['T left onto', '', 'TL'],
		['T right (to stay on|to remain on|TRO)', 'TRO ', 'TR'],
		['T left (to stay on|to remain on|TRO)', 'TRO ', 'TL'],
		['(Bear|Slight) right onto', '', 'BR'],
		['(Bear|Slight) left onto', '', 'BL'],
		['(Bear|Slight) right TRO', 'TRO ', 'BR'],
		['(Bear|Slight) left TRO', 'TRO ', 'BL'],
		['(Turn |)(Immediate|Immed|Immd|Quick) left (in|on)to', '', 'QL'],
		['(Turn |)(Immediate|Immed|Immd|Quick) right (in|on)to', '', 'QR'],
		['(Turn |)(Immediate|Immed|Immd|Quick) left TRO', 'TRO', 'QL'],
		['(Turn |)(Immediate|Immed|Immd|Quick) right TRO', 'TRO', 'QR'],
		['First left onto', '', '1st L'],
		['First right onto', '', '1st R'],
		['First left (to stay on|to remain on|TRO)', 'TRO ', '1st L'],
		['First right onto (to stay on|to remain on|TRO)', 'TRO ', '1st R'],
		['Second left (to stay on|to remain on|TRO)', 'TRO ', '2nd L'],
		['Second right onto (to stay on|to remain on|TRO)', 'TRO ', '2nd R']
	];

	
	public $turn_replace=[
		'Straight'=>'SO',
		'Right'=>'R',
		'Left'=>'L',
		'Slight Right'=>'BR',
		'Slight Left'=>'BL',
		'Generic'=>'NOTE'
	];

	public $cuesheet_comment='';
	public $icoe='If abandoning ride or to report a problem call the organizer: {$organizer_name} ({$organizer_phone}). For Medical/Safety Emergencies Call 911 First!';

	public function replace_cues($line){
		$note=$line[$this->dist_cols+1];
		$turn=$line[$this->dist_cols+0];

		$stars_pattern="/^\*{2,}\s*/";
		if(preg_match($stars_pattern,$note)){;
			$note_r=preg_replace($stars_pattern,'',$note);
			$starred_cue=true;
			$note=$line[$this->dist_cols+1]=$note_r;
		}else{
			$starred_cue=false;
		}

		$nr=1;
		foreach($this->cue_replace as $r){
			list($pattern, $note_replace, $turn_replace)=$r;
			$note_r=preg_replace("/^$pattern\s*/i", $note_replace, $note);
			if($note_r != $note){
				$note=$line[$this->dist_cols+1]=$note_r; // . " ($nr)";
				$turn=$line[$this->dist_cols+0]=$turn_replace;
				break;
			}
			$nr++;
		}
		
		// dual cues
		$pattern="/^(t|turn|slight|bear)\s+(left|l|right|r)\s*(\+|then)\s*(immediate|immed|imm|quick|q)\s*(left|l|right|r)\s*(onto)?\s*/i";
		if(preg_match($pattern, $note, $m)){
			$note=$line[$this->dist_cols+1]=ucfirst(preg_replace($pattern, '', $note));
			$tb=strtolower($m[1]);
			$t=($tb=='t')?"T":(($tb=='slight'||$tb=='bear')?"B":"");
			$a=(strtolower($m[2][0])=='l')?"L":"R";
			$b=(strtolower($m[5][0])=='l')?"L":"R";
			$turn=$line[$this->dist_cols+0]="$t$a+Q$b";
		}
		
		if(isset($this->turn_replace[$turn])) $turn=$line[$this->dist_cols+0]=$this->turn_replace[$turn];
		if($starred_cue) $note=$line[$this->dist_cols+1]="*** " . $note;
		return $line;
  	}
  	
  	private function draw_page_frame(){
		$x = $this->hmargin;
		$y = $this->vmargin;
		$w = $this->page_width - 2*$this->hmargin;
		$h = $this->page_height - 2*$this->vmargin;
		$this->draw_rect($x,$y,$w,$h,$this->thin_width,self::LIGHTEST_GRAY);
  	}
	
	private $datetime_format = '';
	private $datetime_format_specific = 'M-d H:i T';
	private $datetime_format_generic = '\D\a\y-z H:i';

	public function set_controle_date_format($re){
		$this->datetime_format=(empty($re['event_date']) || empty($re['event_time']))?$this->datetime_format_generic:$this->datetime_format_specific;
	}

	public function draw_cuesheet_pages($route_event, $cues, $controles){
	
		$x = $this->hmargin;
		$y = $this->vmargin;
		$full_width = $this->page_width - 2*$this->hmargin;
		$h = $this->page_height - 2*$this->vmargin;

		$text_columns= ($this->orientation=='P')?1:2;
		$w = $text_column_width = ($full_width - ($text_columns-1)*$this->column_padding)/$text_columns;
		
		$this->draw_page_frame();

		// Title Block
		$this->SetXY($x,$y);
		$this->font_bigprint();
		$this->font_italic();
		$this->Cell($w, 2*$this->baseline_skip, $route_event['this_organization'], 0, 2, 'C');
		$this->font_bold();
		$this->MultiCell($w, 2*$this->baseline_skip, $route_event['event_name_dist'], 0, 'C');
		$this->font_italic();
		$this->font_size(1.2);
		$this->SetX($x);
		$this->Cell($w, 2*$this->baseline_skip, $route_event['event_datetime_str_verbose'], 0, 2, 'C');

		// Header 
		$this->font_normalprint();
		$this->font_plain();
		$header=$this->header_text_array($route_event);
		$header=array_slice($header,3,count($header)-3); // these lines were already printed
		foreach($header as $line){
			for($i=0; $i<count($line); $i++){
				$sw=$this->GetStringWidth($line[$i])+2*$this->column_padding;
				$mw[$i]=(isset($mw[$i]))?max($sw,$mw[$i]):$sw;
			}
		}	
		
		if(array_sum($mw)>$w) $mw[1]=$w-$mw[0];
		
		$y=$this->GetY()+$this->baseline_skip;
		$indent=($w-($mw[0]+$mw[1]))/2;
		$this->render_header_lines($x+$indent,$y,$header,$mw);	
		
		// In case of emergency
		$this->font_normalprint();
		$this->font_italic();
		$this->SetXY($x, $this->GetY()+$this->baseline_skip);
		$this->SetFillColor(...self::FILL_WARN);
		$this->MultiCell($w, $this->baseline_skip, $this->icoe, 0, 'C', true);
		$n=$this->MultiCellLines($w, $this->baseline_skip, $this->icoe, 0, 'C');
		$this->SetY($this->GetY()+$this->baseline_skip*($n-1));
		
		// Abbreviation Glossary
		if($this->cue_abbreviations != []){
		$glossary=implode(', ', array_map(function($v,$k){return "$k:$v";}, $this->cue_abbreviations, array_keys($this->cue_abbreviations)));
		$this->font_normalprint();
		$this->font_italic();
		$y_now=$this->GetY();
		$this->SetXY($x, $y_now);
		$this->MultiCell($w, $this->baseline_skip, $glossary, 0, 'C');
		$n=$this->MultiCellLines($w, $this->baseline_skip, $glossary, 0, 'C');
		$this->draw_rect($x, $y_now-$this->row_padding, $w, $this->baseline_skip*$n+2*$this->row_padding);
		$this->SetY($this->GetY()+$this->baseline_skip);
		}

		if(!empty($this->cuesheet_comment)){
		$this->font_normalprint();
		$this->font_plain();
		$y_now=$this->GetY();
		$this->SetXY($x, $y_now);
		$this->MultiCell($w, $this->baseline_skip, $this->cuesheet_comment, 0, 'C');
		$n=$this->MultiCellLines($w, $this->baseline_skip, $this->cuesheet_comment, 0, 'C');
		$this->draw_rect($x, $y_now-$this->row_padding, $w, $this->baseline_skip*$n+2*$this->row_padding);
		$this->SetY($this->GetY()+$this->baseline_skip);
		}
		
		// Cues
		$this->font_normalprint();
		$this->font_plain();
		$this->SetXY($x, $this->GetY());
		$this->render_cues($full_width,$h,$this->cue_text_array($cues, $controles), $text_columns);
		
	}
	
	private function render_cues($full_width,$h,$cue_lines,$text_columns=1){

		// $x = $this->GetX();
		$text_column=0;
		$w=$text_column_width = ($full_width - ($text_columns-1)*$this->column_padding)/$text_columns;
		$x = $this->hmargin + $text_column*($text_column_width+$this->column_padding);
		$max_y = $this->vmargin + $h;
		
		// Determine maximum widths of each cue column
		foreach($cue_lines as $line){
			$i=0;
			$controle_i=array_shift($line); 
			if($controle_i===false){ // only consider non_controles
				foreach($line as $field){
					$sw=$this->GetStringWidth($field);
					$max_width[$i]=(isset($max_width[$i]))?max($sw,$max_width[$i]):$sw;
					$i++;
				}
			}else{
				$last_controle=$controle_i;
			}
		}
		
		$width_dist_cols=2*$this->column_padding+max(array_slice($max_width,0,$this->dist_cols));
		for($i=0; $i<$this->dist_cols; $i++)$max_width[$i]=$width_dist_cols;
		$max_width[$this->dist_cols]=$width_turn_col=2*$this->column_padding+$max_width[$this->dist_cols];
		$max_width[$this->dist_cols+1]=$width_note_col=$w - $this->dist_cols*$width_dist_cols - $width_turn_col;

		// Render all lines
		foreach($cue_lines as $line){
			$this_y=$this->GetY();
			$line_height=$this->cue_line_height($line,$max_width);
			// $line_height = $note_lines*$this->baseline_skip + 2*$this->row_padding;

			if($this_y+$line_height >= $max_y){ // Start new column, or page
				$text_column = ($text_column+1) % $text_columns;
				if($text_column==0) { // New Page
					$this->AddPage();
					$this->draw_page_frame();
				}
				$x = $this->hmargin + $text_column*($text_column_width+$this->column_padding);
				$this->SetXY($x,$this->vmargin);		
				$this_y=$this->vmargin;
			}

			$controle_i=array_shift($line);
			if($controle_i>0){
				$height=$this->render_controle_line($line,$max_width);
				$this->SetXY($x, $this_y+$height);
				// Column Header After Controle Cue (except the finish controle)
				if($controle_i!==$last_controle){
					$this_y=$this->GetY();
					$this->font_bold();
					
					
			$line_height=$this->baseline_skip + 2*$this->row_padding;
			if($this_y+$line_height >= $max_y){ // Start new column, or page
				$text_column = ($text_column+1) % $text_columns;
				if($text_column==0) { // New Page
					$this->AddPage();
					$this->draw_page_frame();
				}
				$x = $this->hmargin + $text_column*($text_column_width+$this->column_padding);
				$this->SetXY($x,$this->vmargin);		
				$this_y=$this->vmargin;
			}
					
					
					$height=$this->render_cue_line($this->column_headers,$max_width);
					$this->font_plain();
					$this->SetXY($x, $this_y+$height);
				}
			}else{
				$height=$this->render_cue_line($line,$max_width);
			}
			$this->SetXY($x, $this_y+$height);
		}
	}

	public function clean_string($s){
		// $s = trim(iconv('UTF-8', 'windows-1252', $s));
		$s=trim($s);
		$s=iconv("UTF-8", "ISO-8859-1//TRANSLIT", $s);
		return preg_replace('/\s+/', ' ', $s);
  	}
  	
  	private function cue_line_height($line, $width){
		$w_description=$width[$this->dist_cols+1];
		$controle_i=array_shift($line);
		if($controle_i>0){
			list($controle_str, $style_text, $open_time_str, $close_time_str, $name, $address, $phone)=$line;
			$height_title=$this->baseline_skip + 2*$this->row_padding;
			$name_phone=$this->clean_string("$name $phone");
			$address=$this->clean_string($address);
			$description="$name $phone\n$address";
			$controle_lines=$this->MultiCellLines($w_description,$this->baseline_skip,$description,0,'L');
			$height_description=($controle_lines)*$this->baseline_skip + 2*$this->row_padding;
			$height_rule=$this->row_padding;
			$height=$height_title+$height_description+$height_rule;
		}else{
			$notes_width=$width[$this->dist_cols+1];
			$line[$this->dist_cols+1]=$this->clean_string($line[$this->dist_cols+1]); // clean note
			$line=$this->replace_cues($line);
			$note=$line[$this->dist_cols+1];
			$note_lines=$this->MultiCellLines($notes_width,$this->baseline_skip,$note,0,'L');
			$height=$note_lines*$this->baseline_skip + 2*$this->row_padding;
		}
		return $height;
  	}

//  public $replace_log=[];
	
	private function render_cue_line($line,$width){
			$this->SetLineWidth($this->thin_width);
			$this->SetDrawColor(self::BLACK);
			$notes_width=$width[$this->dist_cols+1];
			$line[$this->dist_cols+1]=$this->clean_string($line[$this->dist_cols+1]); // clean note

			$line_r=$this->replace_cues($line);
			if($line_r != $line){
//				$this->replace_log[]=[$line, $line_r];
				$line=$line_r; 
			}

			$note=$line[$this->dist_cols+1];
			$note_lines=$this->MultiCellLines($notes_width,$this->baseline_skip,$note,0,'L');
			$height=$note_lines*$this->baseline_skip + 2*$this->row_padding;
			$y_now=$this->GetY();
			$i=0;
			foreach($line as $field){
				$x_now=$this->GetX();
				if($i<=$this->dist_cols){
					$this->Cell($width[$i], $height, $field, 1, 0, 'C');
				}else{
					$this->draw_rect($x_now,$y_now,$width[$i],$height,$this->thin_width,self::BLACK);
					$this->SetXY($x_now,$y_now+$this->row_padding);
					$this->MultiCell($width[$i], $this->baseline_skip, $field, 0, 'L');
				}	
				$i++;
			}
			return $height;
	}

	private function render_controle_line($line,$width){
			$this->SetLineWidth($this->thin_width);
			$this->SetDrawColor(self::BLACK);
		$y_now=$this->GetY();
		$x_now=$this->GetX();
		$w_total=0;
		foreach($width as $wi) $w_total+=$wi;
		$w_description=$width[$this->dist_cols+1];
		$w_dist=$w_total-$w_description;
		list($controle_str, $style_text, $open_time_str, $close_time_str, $name, $address, $phone, $photo, $comment)=$line;

		$this->SetFillColor(self::LIGHTEST_GRAY);

		// Controle title
		$this->SetXY($x_now,$y_now);
		$height_title=$this->baseline_skip + 2*$this->row_padding;
		$this->font_bold();
		$this->Cell($w_total, $height_title, $controle_str, 1, 0, 'L',true); 
		$this->SetXY($x_now,$y_now);
		$this->font_italic();
		$this->Cell($w_total, $height_title, strtoupper($style_text), 0, 0, 'R',false); 
		$y_now+=$height_title;

		// Compute height
		$name_phone=$this->clean_string("$name $phone");
		$address=$this->clean_string($address);
		$description="$name $phone\n$address";
		$description_lines=$this->MultiCellLines($w_description,$this->baseline_skip,$description,0,'L');
		$comment_lines=(empty($comment))?0:$this->MultiCellLines($w_description,$this->baseline_skip,"$comment",0,'L');
		$photo_lines=(empty($photo))?0:$this->MultiCellLines($w_description,$this->baseline_skip,"Take Photo: $photo",0,'L');
		$controle_lines=$description_lines+$comment_lines+$photo_lines;
		$height=($controle_lines)*$this->baseline_skip + 2*$this->row_padding;

		// Borders
		$this->draw_rect($x_now,$y_now,$w_total,$height); // Overall outline
		$this->draw_rect($x_now+$w_dist,$y_now,$w_description,$height); // Outline of Description

		// Open Close Times
		$this->font_bold();
		$this->SetXY($x_now,$y_now+$this->row_padding+$this->baseline_skip*($controle_lines-2)/2);
		$open_close="Open: $open_time_str\nClose: $close_time_str";
		$this->MultiCell($w_dist, $this->baseline_skip, $open_close, 0, 'C');

		// Description
		$this->font_italic();
		$this->SetXY($x_now+$w_dist,$y_now+$this->row_padding);
		$this->MultiCell($w_description, $this->baseline_skip, $description, 0, 'L'); // Description
		if($comment_lines>0){ 
			$this->font_plain();
			$this->SetX($x_now+$w_dist);
			$this->MultiCell($w_description, $this->baseline_skip, $comment, 0, 'L');
		}
		if($photo_lines>0) {
		  $this->font_bold();
			$this->SetX($x_now+$w_dist);
			$this->MultiCell($w_description, $this->baseline_skip, "Take photo: $photo", 0, 'L');
		}
		$this->font_plain();
		$y_now+=$height;
		
		// Bottom Rule
		$rule_height=$this->row_padding;
		$this->Rect($x_now,$y_now,$w_total,$rule_height,"DF");
		
		
		// Debug
		
		$debug="";
		
		if(!empty($debug)){
			$this->SetXY($x_now+$w_total,$y_now+$this->row_padding);
			$this->MultiCell($w_description, $this->baseline_skip, $debug, 0, 'L');
		}

		$this->SetFillColor(255);

		return $height+$height_title+$rule_height;
	}
	
	private function MultiCellLines($w, $h, $txt, $border=0, $align='J', $fill=false) { 
		$cw=&$this->CurrentFont['cw']; 
		if($w==0) $w=$this->w - $this->rMargin - $this->x; 
		$wmax=($w-2*$this->cMargin)*1000/$this->FontSize; 
		$s=str_replace("\r",'',$txt); 
		$nb=strlen($s); 
		if($nb>0 && $s[$nb-1]=="\n") $nb--; 
		$NumLines=0; 
		$sep=-1; 
		$i=0; $j=0; $l=0; $ns=0; $nl=1; 
		while($i<$nb) { //Get next character 
			$c=$s[$i]; 
			if($c=="\n") { //Explicit line break 
				if($this->ws>0) $this->ws=0; 
				$NumLines++; 
				$i++; 
				$sep=-1; 
				$j=$i; 
				$l=0; 
				$ns=0; 
				$nl++; 
				continue; 
			} 
			if($c==' ') { 
				$sep=$i; $ls=$l; $ns++; 
			} 
			$l+=$cw[$c]; 
			if($l>$wmax) { //Automatic line break 
				if($sep==-1) { 
					if($i==$j) $i++; 
					if($this->ws>0) $this->ws=0; 
					$NumLines++; 
				} else { 
					if($align=='J') $this->ws=($ns>1) ? ($wmax-$ls)/1000*$this->FontSize/($ns-1) : 0; 
					$NumLines++; 
					$i=$sep+1; 
				} 
				$sep=-1; 
				$j=$i; $l=0; $ns=0; $nl++; 
			} else $i++; 
		} //Last chunk 
		$NumLines++; 
		return $NumLines; 
	} 
	


	public function cue_text_array($cues,$controles){
		$cue_lines=[];
		$d_total=0;
		$d_segment_start=0;
		
		foreach($cues as $cue){

			$d_total_mi=number_format(($cue['d'])/self::m_per_km/self::km_per_mi,1);
			$d_leg_mi=number_format(($cue['d']-$d_total)/self::m_per_km/self::km_per_mi,1);
			$d_segment_mi=number_format(($cue['d']-$d_segment_start)/self::m_per_km/self::km_per_mi,1);
			
			if($cue['t']=='Control'){
				if(isset($cue['controle_i'])){
					$controle_i=$cue['controle_i'];
					$controle=$controles[$controle_i];
				}else{
					trigger_error("Index not found in controle cue",E_USER_ERROR);
				}
				$d_segment_start=$d_total; // total at last non-controle cue // $cue['d'];
				
				$ca=$controle['attributes'];	
				$style_text=$ca['style'];

				if(empty($this->datetime_format)) trigger_error("The datetime_format property not set for this cuesheet.",E_USER_ERROR);
				
				$open_time_str = $controle['open']->format($this->datetime_format);
				$close_time_str = $controle['close']->format($this->datetime_format);
				
				$cd_mi=round($controle['d']/(self::m_per_km*self::km_per_mi),1);
				$cd_km=round($controle['d']/(self::m_per_km),1);
				if(isset($controle['start'])){
					$controle_str="Start Controle";
				}elseif(isset($controle['finish'])){
					$controle_str="Finish Controle";
				}else{
					$controle_number=$controle_i+$this->controle_index_origin;
					$controle_str="Controle $controle_number";
				}				
				$cue_lines[]=[$controle_i+1, $controle_str, $style_text, $open_time_str, $close_time_str, 
					$ca['name'], $ca['address'], ((isset($ca['phone']))?$ca['phone']:''), ((isset($ca['photo']))?$ca['photo']:''), ((isset($ca['comment']))?$ca['comment']:'')];
			}else{
				$cue_lines[]=[false, $d_total_mi, $d_segment_mi, $d_leg_mi, $cue['t'] ,$cue['n']];
				$d_total=$cue['d'];
			}
		}
		return $cue_lines;
	}
	
	public function header_text_array($route_event){
		
		extract($route_event);	

		$lines=[
			["Event:", "$event_name_dist"],
			["Event Start Date/Time:", " $event_datetime_str"],
			["Official Distance:", " $event_distance km"],
			["Event Type:", strtoupper($event_type)],
			["Distance:", " $distance_mi mi / $distance_km km"],
			["Climbing:", " $climbing_ft ft"]
		];

		if(!empty($pavement_type)) $lines[]=["Pavement:", " $pavement_type"];

		if($event_type=='permanent')
			$lines[]= ["Permanent route:", " $rusa_route_id"];

		$lines = array_merge($lines, 
			[
				["Organizer:", " $organizer_name ($organizer_phone)"],
				["RWGPS Name:", " $route_name"],
				["RWGPS URL:", " $route_url"],
				["Modified:", " $last_update"],
				["Cues Generated:", " $now_str"]
			]
		);
		
		if($event_type!='permanent')
			$lines[]=	["Cuesheet Version:", " $cue_version"];

		return $lines;
	
	}
	

	private function render_header_lines($x, $y, $lines, $width){
		$baseline_skip=$this->baseline_skip;

		$this->SetLineWidth($this->thin_width);
		$this->SetDrawColor(self::BLACK);

		$y_now=$y; // First row

		for($j=0; $j<count($lines); $j++){ // for each line
			$line=$lines[$j];

			// Find field with max number of wrapped lines
			$n_max=0;
			for($i=0; $i<count($line); $i++){
				$text=$line[$i];
				$n=$this->MultiCellLines($width[$i], $this->baseline_skip, $text, 0, 'C');
				if($n>$n_max) $n_max=$n;
			}	
			
			// Now we know the height of this row
			$height=$n_max*$this->baseline_skip + 2*$this->row_padding;
			
			$x_now=$x; // Start a row
			
			// Render the fields in this row
			for($i=0; $i<count($line); $i++){
				$text=$line[$i];
				$this->Rect($x_now, $y_now, $width[$i], $height);
				$this->SetXY($x_now, $y_now+$this->row_padding);
				$this->MultiCell($width[$i], $this->baseline_skip, $text, 0, 'C');
				$x_now+=$width[$i];
			}
			
			$y_now+=$height; // move to next row
		}
		//$this->SetXY($x,$y+$n_line*$height);
	}


}
