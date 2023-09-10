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

class Map extends Units{


    public function __construct(){
			// parent::__construct();
    }

	// Elevation profile graph
	public function generate_ep_script($route, $controles, $graph_divid='eprofile', $epvar='epvar'){
	
	
ini_set('memory_limit', '1024M'); // or you could use 1G

		$csv_data=[];
		foreach($route['track_points'] as $p){
			$d=$p['d']; 
			$cd_mi=round($d/(self::m_per_km*self::km_per_mi),3);
			$ce_ft=$p['e']*(self::ft_per_m);
			$csv_data[] = "[$cd_mi,$ce_ft]";
		}
		$csv_text='['. implode(',' , $csv_data) .']';

		$ep_an=[];
		foreach($controles as $i=>$c){
			$d=$c['d'];
			$cd_mi=round($d/(self::m_per_km*self::km_per_mi),3);
			$controle_num=$i+1;
			$pu_text=implode('; ', $this->make_pu_array($controle_num,$c));
			$ep_an[]="{series: \"Elev\", x:$cd_mi, shortText:\"$controle_num\", text:\"$pu_text\", height: 18, width: 18, cssClass: \"elevation-annotation\"}";
		}
		$ep_an_func = "$epvar.setAnnotations([" . implode(',',$ep_an) . "]);" ;

		$ep_script=<<<EOT
<script type="text/javascript"
  src="https://randonneuring.org/assets/js/dygraph.min.js"></script>
<script type="text/javascript">
$epvar = new Dygraph(document.getElementById("$graph_divid"),$csv_text,
  {labels: [ "Dist", "Elev" ], ylabel: "Elevation (ft)", xlabel: "Distance (mi)", colors: ["#355681"]});
$epvar.ready(function() { $ep_an_func });
</script>
EOT;

		return $ep_script;

	}

	// Pop-Up information for controles available on map
	private function make_pu_array($controle_num,$c){
			$pu_arr=[];

			$cd_mi=round($c['d']/(self::m_per_km*self::km_per_mi),1);
			$cd_km=round($c['d']/(self::m_per_km),1);
			$ce_ft=round($c['e']*(self::ft_per_m),1);
			$is_sf=(isset($c['start']))?" [START]":((isset($c['finish']))?" [FINISH]":"");
			$pu_arr[] ="Controle $controle_num$is_sf";
			if(isset($c['attributes'])){
				$at=$c['attributes'];
				$pu_arr[] = isset($at['name'])?'Name: ' . $at['name']:'';
				$pu_arr[] = isset($at['style'])?'Style: ' . $at['style']:'';
			}
			$pu_arr[] ="Dist: $cd_mi mi ($cd_km km)";
			$pu_arr[] ="Elev: $ce_ft ft";
			return $pu_arr;
	}

	// Generates javascript to create Leaflet map in specified div id. 
	public function generate_map_script($route,$controles,$divid='randomap',$mapvar='randomapv',$provider='mapbox'){

		$markers="";
		foreach($controles as $i=>$c){
			$lat=$c['y']; $lng=$c['x']; 

			$controle_num=$i+1;
			$pos=isset($c['finish'])?'-28px':'0px';
			$controle_num_text="<div style=\"position:relative; top:$pos;\">$controle_num</div>";
			$markers.="var icon$i = L.divIcon({className: 'controle-icon', html: '$controle_num_text'});\n";

			$markers.="var marker$i = L.marker([$lat, $lng],{icon: icon$i, title: 'Controle $controle_num (click for info)'}).addTo($mapvar);\n";

			$pu_text=implode('<br>', $this->make_pu_array($controle_num,$c));
			$markers.="marker$i.bindPopup(\"$pu_text\");\n";
		}

		$track="";
		$comma=false;
		if(isset($route['track_points'])){
			$track="var polygon = L.polyline([";
			foreach($route['track_points'] as $p){
				if($comma) $track.=","; $comma=true;
				$lat=$p['y']; $lng=$p['x'];
				$track.="[$lat,$lng]";
			}
			$track.="],{fill: false}).addTo($mapvar);";
		}
		
		$ul=$route['bounding_box'][0];
		$ul_lat=$ul['lat'];
		$ul_lng=$ul['lng'];
		$lr=$route['bounding_box'][1];
		$lr_lat=$lr['lat'];
		$lr_lng=$lr['lng'];


		switch($provider){
			case 'mapbox':
				$script=<<<EOT
 <script src="https://randonneuring.org/assets/js/leaflet.js"
   integrity="sha512-gZwIG9x3wUXg2hdXF6+rVkLF/0Vi9U8D2Ntg4Ga5I5BZpVkVxlJWbSQtXPSiUTtC0TjtGOmxa1AJPuV0CPthew=="
   crossorigin=""></script>

<script>
var $mapvar = L.map('$divid',{scrollWheelZoom: false}).fitBounds([[$ul_lat,$ul_lng], [$lr_lat,$lr_lng]]);
L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}', {
    attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
    maxZoom: 18,
    id: 'mapbox/streets-v11',
    tileSize: 512,
    zoomOffset: -1,
    accessToken: 'pk.eyJ1IjoiY3RuYWRvdmljaCIsImEiOiJjbG1jOW10a24xNXp6M2NycWtzYmFhMm9nIn0.gYN6kTjXYEPXpfdJk8AUNw'
}).addTo($mapvar);
$markers
$track
</script>
EOT;
				break;

			case 'stamen-toner':
				if(!isset($layer_type)) $layer_type='toner';
			case 'stamen-terrain':
				if(!isset($layer_type)) $layer_type='terrain';
			case 'stamen':
				if(!isset($layer_type)) $layer_type='terrain';
				$file_type='png';
				$script=<<<EOT
<script src="https://randonneuring.org/assets/js/leaflet.js"
   integrity="sha512-gZwIG9x3wUXg2hdXF6+rVkLF/0Vi9U8D2Ntg4Ga5I5BZpVkVxlJWbSQtXPSiUTtC0TjtGOmxa1AJPuV0CPthew=="
   crossorigin=""></script>
<script>
var $mapvar = L.map('$divid',{scrollWheelZoom: false}).fitBounds([[$ul_lat,$ul_lng], [$lr_lat,$lr_lng]]);
L.tileLayer('https://stamen-tiles-{s}.a.ssl.fastly.net/$layer_type/{z}/{x}/{y}.$file_type', {
    attribution: [
                'Map tiles by <a href="http://stamen.com/">Stamen Design</a>, ',
                'under <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a>. ',
                'Data by <a href="http://openstreetmap.org/">OpenStreetMap</a>, ',
                'under <a href="http://creativecommons.org/licenses/by-sa/3.0">CC BY SA</a>.'
            ].join(""),
		type: '$file_type',
		minZoom: 1,
    maxZoom: 18,
		subdomains: "a b c d".split(" "),
    scheme: 'xyz'}).addTo($mapvar);
$markers
$track
</script>
EOT;
				break;
			default:
				throw new \Exception(__METHOD__ . ": Uknown map tile provider: $provider");
				break;
		}
		return $script;
	}
	
}







?>