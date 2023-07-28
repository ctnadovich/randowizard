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

// How we like to format input fields

if (!function_exists('input_field')) {
    function input_field($tag, $label, $err, $type='text', $clear = false){
        $class = empty($err[$tag])?'w3-input':'w3-input w3-bottombar w3-border-purple';
        $value = empty($err[$tag]) && !$clear?set_value($tag):'';
        return <<<EOF
        <p><input type="$type" name="$tag" class="$class" value="$value" style="width:90%">
        <label>$label</label></p>
      EOF;
      }
}
?>