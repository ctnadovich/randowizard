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

if (!function_exists('lm')) {
    /**
     * Render the waiver's lightweight markup as HTML.
     *
     * Supported markup:
     *   **text**  -> bold
     *   !!text!!  -> red
     */
    function lm(string $text): string
    {
        /*
         * Escape the original template text first so that template content
         * cannot inject arbitrary HTML.
         */
        $text = esc($text);

        $text = preg_replace(
            '/\*\*(.+?)\*\*/s',
            '<strong>$1</strong>',
            $text
        );

        $text = preg_replace(
            '/!!(.+?)!!/s',
            '<span class="lm-red">$1</span>',
            $text
        );

        return $text;
    }
}