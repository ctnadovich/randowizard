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


namespace App\Models;

use CodeIgniter\Model;

require_once(APPPATH . 'Libraries/Secret/Secrets.php');  // for RUSA API KEY

use Secrets;

class Rusa extends Model
{

    const rusa_check_members_url = "https://rusa.org/cgi-bin/check_members.pl";
    const rusa_membership_url = "https://rusa.org/cgi-bin/memberlist_PF.pl?type=1";

    const max_rusa_id = 999999;
    const min_rusa_year = 1990;
    const max_rusa_year = 2099;

    const rusa_exp_time = "23:59:59";


    const rusa_csv_fields = [
        'rusa_id',
        'expires',
        'last_name',
        'first_name',
        'city',
        'state',
        'country',
        'club',
        'club_id'
    ];

    protected $table      = 'rusa';
    protected $primaryKey = 'id';
    protected $returnType     = 'array';
    protected $allowedFields = ['first', 'last'];


    public function get_member($rusa_id)
    {
        $rusa_id = $rusa_id + 0;
        $this->where('rusa_id', $rusa_id);
        $m = $this->findAll();
        if (count($m) > 1) {  // Multiple entries
            throw new \Exception("Duplicate entries in RUSA member cache for ID=$rusa_id");
        } elseif (count($m) == 0) {
            return null; // not found
        }
        return reset($m);
    }


    public function get_member_by_first_last($first_name, $last_name)
    {
        $this->where('first_name', $first_name);
        $this->where('last_name', $last_name);
        $m = $this->findAll();
        if (count($m) > 1) {  // Multiple entries
            throw new \Exception("Duplicate entries in RUSA member cache for name: $first_name $last_name");
        } elseif (count($m) == 0) {
            return null; // not found
        }
        return $m;
    }

    public function query_rusa_id($rusa_id, $now_datetime=null)
    {

        $m = $this->check_member_cache($rusa_id);
        if ($m !== false) {
            $expires = $m['expires'];
            $tz_utc = new \DateTimeZone('utc');
            $exp_time = self::rusa_exp_time;
            $exp_datetime = new \DateTime("$expires $exp_time", $tz_utc);
            $now_datetime = $now_datetime ?? new \DateTime('now', $tz_utc);
            if ($exp_datetime > $now_datetime) return $m;
        }

        return $this->check_member_api($rusa_id);
    }



    private function check_member_cache($rusa_id)
    {
        $m = $this->get_member($rusa_id);
        if (null === $m) return false; // not found
        $m['checked_by'] = 'Cache';
        return $m;
    }

    // 		private function check_member_api($rusa_id){  //temporary wrapper before renaming
    // 		  return $this->query_rusa_id($rusa_id);
    // 		}

    private function check_member_api($rusa_id)
    {

        $api_key = Secrets::rusa_member_api_key;
        $url = self::rusa_check_members_url . "?mid_list=$rusa_id";
        $data = @file_get_contents("$url" . "&apikey=$api_key");
        if (empty($data)) trigger_error("Failed to download any RUSA API data from $url", E_USER_ERROR);
        $member_record = json_decode($data, true);
        if (null === $member_record) trigger_error("Could not decode RUSA API response from $url<br>JSON data:<br>" . print_r($data, true), E_USER_ERROR);
        if (count($member_record) != 1) trigger_error("Unexpected number of array elements in RUSA API response. Decoded data:<br>" . print_r($member_record, true), E_USER_ERROR);
        $m = reset($member_record); // First element 

        if (!array_key_exists('name', $m)) return false;  // not found

        // Field remapping and validation for RUSA API
        $field_map = [
            'mid' => 'rusa_id',
            'exp_date' => 'expires',
            'name' => 'last_first'
        ];
        foreach ($field_map as $old => $new) {
            if (array_key_exists($old, $m)) {
                if ($old != $new) {
                    $m[$new] = $m[$old];
                    unset($m[$old]);
                }
            } else trigger_error("Missing key '$old' in RUSA member query response: " . print_r($m, true), E_USER_ERROR);
        }

        // Split name on comma -- hope there are no names with commas!
        list($m['last_name'], $m['first_name']) = array_merge(explode(', ', $m['last_first'], 2), ['', '']);

        // Convert YYYY/MM/DD date to ISO 8601 YYYY-MM-DD
        $m['expires'] = str_replace('/', '-', $m['expires']);

        $m['checked_by'] = 'API';

        return $m;
    }


    public function last_name_mismatchq($a,$b){
        $a=preg_replace('/[^a-z]/','',strtolower($a));
        $b=preg_replace('/[^a-z]/','',strtolower($b));
        //$this->die_error(__METHOD__,"'$a=$b'");
        return (empty($a) || empty($b) || levenshtein($a,$b)>1);
    }

    public function rusa_status_at_date($rusa_id, $last_name, $event_cutoff_datetime)
    {
        $result = "This can't happen";

        if (empty($rusa_id)) {
            $result = ("No Rider ID");
        } else {
            $rusa_m = $this->query_rusa_id($rusa_id, $event_cutoff_datetime);
            if (false === $rusa_m) {
                $result = ("Rider ID '$rusa_id' is not a member of RUSA");
            } else {
                if ($this->last_name_mismatchq($rusa_m['last_name'], $last_name)) {
                    $a = preg_replace('/[^a-z]/', '', strtolower($rusa_m['last_name']));
                    $b = preg_replace('/[^a-z]/', '', strtolower($last_name));

                    $result = ("Last name '" . strtoupper($last_name) . "' does not match last name for Rider ID $rusa_id"); // ($a != $b)");
                } else {
                    $tz_utc = new \DateTimeZone('utc');
                    $rusa_exp = $rusa_m['expires'];
                    $checked_by = $rusa_m['checked_by'];
                    $exp_time = self::rusa_exp_time;
                    $rusa_expires_datetime = new \DateTime("$rusa_exp $exp_time", $tz_utc);
                    $rusa_expires_datetime_str = $rusa_expires_datetime->format('c');
                    $event_cutoff_datetime_str = $event_cutoff_datetime->format('c');

                    $now_datetime = new \DateTime('now', $tz_utc);

                    if ($now_datetime > $rusa_expires_datetime) {
                        $result = ("The RUSA membership for Rider ID $rusa_id expired on $rusa_exp.");
                    } else {
                        if ($event_cutoff_datetime > $rusa_expires_datetime) {
                            $result = ("RUSA membership of Rider ID $rusa_id expires, $rusa_expires_datetime_str, before the event cutoff $event_cutoff_datetime_str.");
                        }else{
                            $result = compact('rusa_expires_datetime', 'checked_by');
                        }
                    }
                }
            }
        }
        return $result;
    }
    public function get_member_list($all_records = true)
    {

        $url = self::rusa_membership_url;

        $rusa_file = @fopen($url, "r") or throw new \Exception("Unable to access RUSA membership data with URL: $url");

        $n_fields = count(self::rusa_csv_fields);

        $member_list = [];
        $line = 1;
        while (($row = fgetcsv($rusa_file)) !== FALSE) {
            if (($row_map = array_combine(self::rusa_csv_fields, $row)) === FALSE) throw new \Exception("CSV line $line does not have $n_fields fields");
            if (empty($row_map['rusa_id'])) {
                // ignore line
            } else {
                if ($row_map['rusa_id'] < 1 || $row_map['rusa_id'] > self::max_rusa_id) throw new \Exception("Malformed or out of range RUSA ID: " . $row_map['rusa_id']);
                if (isset($member_list[$row_map['rusa_id']])) throw new \Exception("CSV line $line Duplicate RUSA ID: " . $row_map['rusa_id']);
                list($y, $m, $d) = explode('/', $row_map['expires'], 3);
                if ($y < self::min_rusa_year || $y > self::max_rusa_year) throw new \Exception("CSV line $line has invalid year: " . $y);
                if ($m < 1 || $m > 12) throw new \Exception("CSV line $line has invalid month: " . $m);
                if ($d < 1 || $d > 31) throw new \Exception("CSV line $line has invalid day: " . $d);
                if (0 == strlen($row_map['first_name']) || 0 == strlen($row_map['last_name']))  throw new \Exception("CSV line $line has invalid name: ");

                $q = $this->db->query("SELECT code FROM country WHERE fullname = " . $this->db->escape($row_map['country']));
                $result = $q->getRowArray();
                $code = $result['code'] ?? null; 

                if ($code === null) throw new \Exception("No code found for country: " . $row_map['country'] . " Result: " . print_r($result,true));
                $row_map['country'] = $code;

                $uexpire = mktime(23, 59, 59, $m, $d, $y);
                if ($all_records || $uexpire > time()) {
                    $row_map['expires'] = date('Y-m-d', $uexpire);
                    $member_list[$row_map['rusa_id']] = $row_map;
                }
            }
            $line++;
        }
        fclose($rusa_file);

        return $member_list;
    }


    public function cache_update()
    {
        $m = $this->get_member_list();
        $n = $this->upsertBatch(array_values($m));
/*         $n = count($m);
        foreach ($m as $rusa_id => $mdata) {
            if (false === $this->upsert($mdata))
                throw new \Exception("Could not upsert record for RUSA ID $rusa_id");
        }
 */
        return $n;
    }
}
