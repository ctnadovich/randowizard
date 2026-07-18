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

class WaiverTemplate
{
    // This is where waivers are stored. Maybe someday a RUSA URL? 
    private string $template_baseurl = "https://randonneuring.org/assets/local/waivers/";

    public function setTemplateBaseURL(string $url)
    {
        $this->template_baseurl = $url;
    }

    // Library provides waiver template fetching and interpolation functions. 

    public string $template_name;
    public array $waiver_template;

    public function __construct(string $template_name)
    {
        $this->template_name = $template_name;
        $this->waiver_template = $this->get_waiver_template();
    }

    // Waiver fetcher, no interpolation


    private function get_waiver_template(): array
    {

        $waiver_url = $this->template_baseurl . $this->template_name;

        $contents = @file_get_contents($waiver_url);

        if ($contents === false) {
            throw new \RuntimeException("Unable to fetch waiver template from $waiver_url");
        }

        $result = [];

        $currentTag = null;
        $currentText = '';

        $lines = preg_split('/\R/', $contents);

        foreach ($lines as $line) {
            if (preg_match('/^\[([A-Z0-9_]+)\]$/', trim($line), $matches)) {
                if ($currentTag !== null) {
                    $result[$currentTag][] = $this->safe_text(rtrim($currentText));
                }

                $currentTag = $matches[1];
                $currentText = '';
            } else {
                if ($currentTag !== null) {
                    $currentText .= $line . "\n";
                }
            }
        }

        if ($currentTag !== null) {
            $result[$currentTag][] = $this->safe_text(rtrim($currentText));
        }

        return $result;
    }

    public function interpolate_template(array $replaceMap, $allowUndefined = true): array
    {
        $waiverTemplate = $this->waiver_template;
        foreach ($waiverTemplate as $tag => $strings) {
            foreach ($strings as $i => $text) {
                $text = preg_replace_callback(
                    '/\{\{([A-Za-z0-9_]+)\}\}/',
                    function ($matches) use ($replaceMap, $allowUndefined) {
                        $name = $matches[1];

                        if (!array_key_exists($name, $replaceMap)) {
                            if ($allowUndefined)
                                return '{{' . $name . '}}';   // if no mapping, leave tag in place
                            else
                                throw new \RuntimeException("Undefined replacement: $name");
                        } else {
                            return (string)$replaceMap[$name];
                        }
                    },
                    $text
                );

                // Replace **text** with bold HTML
                $text = preg_replace(
                    '/\*\*(.+?)\*\*/s',
                    '<b>$1</b>',
                    $text
                );
                
                $waiverTemplate[$tag][$i] = $text;
            }
        }

        return $waiverTemplate;
    }


    private function safe_text(string $s): string
    {
        $map = [
            "\u{2018}" => "'",  // left single quote
            "\u{2019}" => "'",  // right single quote
            "\u{201C}" => '"',  // left double quote
            "\u{201D}" => '"',  // right double quote
            "\u{2013}" => '-',  // en dash
            "\u{2014}" => '-',  // em dash
            "\u{2026}" => '...', // ellipsis
            "\u{00A0}" => ' ',  // non-breaking space
        ];

        $s = strtr($s, $map);

        // Convert remaining text to Windows-1252, replacing unrepresentable chars
        return iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $s);
    }
}
