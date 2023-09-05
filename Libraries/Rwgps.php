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

	class Rwgps {

	// const km_per_mi = 1.609344;
	// const ft_per_m = 3.2808398950131;
	// const m_per_km = 1000.0;
	const start_finish_neigborhood = 3; // The start/finish must be within this many KM of the ends of the course
	const rwgps_base_url="https://ridewithgps.com/routes/";
	const saved_route_path="/var/www/html/randonneuring.org/ci/public/assets/local/routes";
	const saved_route_url="https://randonneuring.org/assets/local/routes";

		public $valid_event_description_keys=[
						'pavement_type',
					];

		public $depreciated_event_description_keys=[
						'name',
						'type',
						'distance',
						'cue_abbreviations',
						'comment',
						'in_case_of_emergency',
						'time',
						'date',
						'tzname',
						'organization',
						'organizer_name',
						'organizer_phone',
						'organizer_rusa_id',
						'rusa_route_id',
						'location',
						'logo_url',
						'event_url',
						'roster_url'
					];


        public function __construct(){
			// parent::__construct();
		}
	 
	public function get_route($route_id, $auto_fetch=true){
		if(empty($route_id) || !$this->is_good_route_id($route_id)) throw new \Exception(__METHOD__ . ": Bad route ID: $route_id");


		if(false===$this->route_is_cached($route_id)){ // Route not on file, fetch in from RWGPS
			if(!$auto_fetch) throw new \Exception(__METHOD__ . ": Route $route_id not in cache and auto_fetch disabled.");
			$result=$this->download_route_data($route_id);
			if($result!==true)
            throw new \Exception(__METHOD__ . ": Invalid or Missing RWGPS route:<br>" . print_r($result,true));
			$download_note="Route $route_id downloaded from RWGPS web site.";
		}else{
 			$download_note="Route $route_id retrieved from local cache.";
		}
		
		$route_filename=$this->make_route_filename($route_id); // JSON data
		clearstatcache(true,$route_filename);
		$fstat=@stat($route_filename);
		if($fstat===false)throw new \Exception(__METHOD__ . ": $download_note<br>Couldn't stat '$route_filename' that was just written??!!");
		$sfile=@fopen($route_filename,"r");
		if(false===$sfile) throw new \Exception(__METHOD__ . ": $download_note<br>Could not open route file '$route_filename' for reading.");
    $fsize=$fstat['size'];
		$json=fread($sfile,$fstat['size']);
		if(false===$json)throw new \Exception(__METHOD__ . ": $download_note<br>Could not read file '$route_filename'.");
		fclose($sfile); 
		$jsize=strlen($json);
		$decode=$this->json_route_decode($json); // Decoded RWGPS route object
		if($decode['route']===false || empty($decode['route']))
			throw new \Exception(__METHOD__ . ": $download_note<br>Failed JSON decode of file $route_filename (F=$fsize; J=$jsize)<br>" . $decode['status']);
		else
			$route=$decode['route'];

		$route['downloaded_at']=$fstat['mtime'];
		$route['download_note']=$download_note;

			foreach($this->cache_data_extensions as $ext){
				$route['route_datafile'][$ext]=$this->make_route_filename($route_id,$ext);
				$route['saved_route_url'][$ext]=$this->make_saved_route_url($route_id,$ext);
			}

		
		return $route;
	}

		public function json_route_decode($json){
		
ini_set('memory_limit', '1024M'); // or you could use 1G

			$route=false; $status='';
			if(empty($json))
				$status="Empty or missing RWGPS data";
			else{
				$route = json_decode($json,true);
				if(null===$route){
					$jlen=strlen($json);
					$status="Could not decode $jlen bytes of raw JSON data:<br>".print_r($json,true);
					$route=false;
				}else{
					extract($route);
					if(!isset($name,$updated_at,$id,$distance,$course_points,$track_points)){
						$status="Missing data in JSON route object:<br>" . print_r($route,true);
						$route=false;
					}
				}
			}		
			return ['route'=>$route, 'status'=>$status];
		}
		
		public function make_route_url($route_id){
			return self::rwgps_base_url . $route_id;
		}
	
		public function make_route_filename($route_id,$ext='json'){
			return self::saved_route_path . "/RWGPS-$route_id.$ext";
		}

		public function make_saved_route_url($route_id,$ext='json'){
			return self::saved_route_url . "/RWGPS-$route_id.$ext";
		}
		
		public function extract_route_id($url){
			$rbu = self::rwgps_base_url;
			if(preg_match("!^$rbu(\d+)\$!",$url,$m)){
				$route_id=$m[1]; // $this->die_error(__METHOD__, "URL=$url; ID=$route_id; M=".print_r($m,true));
			}else{
				$route_id=null; // throw new \Exception(__METHOD__ . ": Malformed map url $url");
			}
			return $route_id;
		}
		
		public function is_good_route_id($route_id){
			return is_numeric($route_id) && $route_id > 1000;
		}


		public $cache_data_extensions = ['json','gpx'];

		public function route_is_cached($route_id){
			if(empty($route_id) || !$this->is_good_route_id($route_id)) throw new \Exception(__METHOD__ . ": Bad route ID");
			foreach($this->cache_data_extensions as $ext){
				$route_filename=$this->make_route_filename($route_id,$ext);
				$fstat=@stat($route_filename);
				if($fstat===false) return false;
			}
			return true;
		}

		public function download_route_data($route_id){
			if(empty($route_id) || !$this->is_good_route_id($route_id)) throw new \Exception(__METHOD__ . ": Bad route ID");
			foreach($this->cache_data_extensions as $ext){
				$result=$this->download_route_data_ext($route_id,$ext);
				if($result!==true) return $result;
			}
			return true;
		}

		private function download_route_data_ext($route_id,$ext='json'){
			$url=$this->make_route_url($route_id);
			$filename=$this->make_route_filename($route_id,$ext);
			$data = @file_get_contents("$url.$ext");
			if(empty($data)) 
				return "Failed to download $ext data from $url";
			$route=$this->json_route_decode($data);
			$sfile=fopen($filename,"w");
			if(false===$sfile)
					return "Could not open route file '$filename' for write.";
			$written=fwrite($sfile,$data);
			$to_write=strlen($data);
			if( false===$written || 0===$written)
					return "Write of route file '$filename' completely failed.";
			if($written<strlen($data))
					return "Incomplete write of '$filename'. $written of $to_write bytes written.";
			if(false===fclose($sfile))
					return "Could not close file '$filename' after write.";
			else
					return true;
		}
	
		public function parse_description($d,$valid=null){ // parses 'description' field and returns array of attribute name/value pairs
			$d=preg_replace("/(\s+)/",' ',$d); // collapse whitespace and remove newlines
			$d=explode('#',$d); // split on hashtags
			$attrib=[];
			$unrecognized=[];
			if(count($d)>1){
				array_shift($d);  // drop stuff before first hashtag
				foreach($d as $a){
					$kv=preg_split('/[:=]/',$a,2); // split on colon or equal
					if(is_array($kv) && count($kv)==2){
						list($k,$v)=$kv;
						$k=strtolower(trim($k)); $v=trim($v);  // lowcase key, remove leading/trailing whitespace
						if(is_array($valid) && false === array_search($k, $valid)) 
							$unrecognized[]="#$k=$v";
						else
							$attrib[$k]=$v; // build attribute array
					}
				}
			}
			if(count($unrecognized)>0) $attrib['unrecognized']=$unrecognized;
			return $attrib;
		}
		
		public $valid_controle_keys=[
			'name',
			'address',
			'phone',
			'style',
			'question',
			'tzname',
			'photo',
			'comment'
		];
		
		public $valid_controle_styles=[
			'staffed',
			'overnight',
			'merchant',
			'photo',
			'open',
			'info',
			'postcard'
		];

		public function extract_controles($route){  
		
			$warnings=[];
			$controles=[];
			
			foreach($route['course_points'] as $pt){ // merge elevation into cues and make separate list of controles
				if(!isset($pt['i'])) throw new \Exception(__METHOD__ . ": No index ('i') attribute. Is this an old route? Incompatible RWGPS route point:" . print_r($pt,true));
				$cue=array_merge($pt, ['e'=>$route['track_points'][$pt['i']]['e']]);
				if($cue['t']=="Control"){
					$controles[]=$cue;
				}
			}
			
			$ncontroles=count($controles);
			if($ncontroles==0){ 
				$warnings[]='No controles found';
			}elseif($ncontroles==1){ 
				$warnings[]="Too few controles; Only one controle found";
			}
			
			$distance_km=round($route['distance']/units::m_per_km,2);
	
			for($i=0; $i<$ncontroles; $i++){
				$controle_num=$i+1;
				$c=$controles[$i];
					if(empty($c['n'])){
						$warnings[]="Controle $controle_num has no note";
					}			
					if(empty($c['description'])){
						$warnings[]="Controle $controle_num has no description";
						$controles[$i]['attributes']=[];
					}else{
						$ca=$this->parse_description($c['description']);
						$controles[$i]['attributes']=$ca;
						if(empty($ca)){					
							$warnings[]="Controle $controle_num has no attributes set in description";
						}else{	
							foreach($ca as $k=>$v){
								if(false === array_search($k, $this->valid_controle_keys)) $warnings[]="Unrecognized attribute '$k' for controle $controle_num";
							}
							if(empty($ca['name'])) $warnings[]="Controle $controle_num has no name  set.";
							if(empty($ca['address'])) $warnings[]="Controle $controle_num has no address  set.";
							if(empty($ca['style'])) $warnings[]="Controle $controle_num has no style  set.";
							if(isset($ca['style']) && !in_array($ca['style'],$this->valid_controle_styles)) 
								$warnings[]="Controle $controle_num has unknown style '" . 
								$ca['style'] . "'. Known styles are: " . implode(' ', $this->valid_controle_styles);
							
							if(isset($ca['style']) && $ca['style']=='info' && empty($ca['question'])) 
								$warnings[]="Controle $controle_num is info controle but has no question set.";
							if(isset($ca['style']) && $ca['style']=='photo' && empty($ca['photo'])) 
								$warnings[]="Controle $controle_num is photo style controle but has no #photo attribute set.";
						}
					}
					
				if(isset($ca['tzname'])) $controles[$i]['tz']=new \DateTimeZone( $ca['tzname'] );
				
				if(($route['distance'] - $c['d']) < units::m_per_km * self::start_finish_neigborhood){  // Controles in the last KM are considered a finish controle
					$controles[$i]['finish']=true;
					if(isset($finish_controle)){
						$warnings[]='Multiple finish controles';
					}else{
						$finish_controle=$i;
					}
				}
				if($c['d']/units::m_per_km < self::start_finish_neigborhood){  // Controles in first KM are considered a start controle
					$controles[$i]['start']=true;
					if(isset($start_controle)){
						$warnings[]='Multiple start controles';
					}else{
						$start_controle=$i;
					}
				}
			}
			
			if(!isset($start_controle)){
				$warnings[]="No Start Controle";
			}
			
			if(!isset($finish_controle)){
				$warnings[]="No Finish Controle";
			}
			
			return [$controles, $warnings];
	
		}
		
		public function extract_cues($route){  
		
			$warnings=[];
			$cues=[];
			$controle_i=0;
			
			foreach($route['course_points'] as $pt){ // merge elevation into cue
				$cue=array_merge($pt, ['e'=>$route['track_points'][$pt['i']]['e']]);
				if($cue['t']=="Control"){
					$cue['controle_i']=$controle_i++;
				}
				$cues[]=$cue;
			}
			
			return [$cues, $warnings];
		}

		
	}

?>
