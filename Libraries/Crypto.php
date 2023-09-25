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

class Crypto
{

    public function make_start_code($d, $rider_id, $epp_secret)
    {
        extract($d);
        $plaintext = "$cue_version-$event_code-$rider_id-$epp_secret";
        $code = strtoupper(substr(hash('sha256', $plaintext), 0, 4));
        $code = str_replace(['0', '1'], ['X', 'Y'], $code);
        return $code;
    }

    public function make_checkin_code($d, $epp_secret)
    {
        extract($d);
        $plaintext = "$control_index-$event_code-$rider_id-$epp_secret";
        $ciphertext = hash('sha256', $plaintext);
        $plain_code = strtoupper(substr($ciphertext, 0, 4));
        $xycode = str_replace(['0', '1'], ['X', 'Y'], $plain_code);
        return $xycode;
    }

    public function make_finish_code($d, $epp_secret)
    {
        extract($d);
        $plaintext = "Finished:$elapsed_hhmm-$global_event_id-$rider_id-$epp_secret";
        $ciphertext = hash('sha256', $plaintext);
        $plain_code = strtoupper(substr($ciphertext, 0, 4));
        $xycode = str_replace(['0', '1'], ['X', 'Y'], $plain_code);
        return $xycode;
    }
}

?>