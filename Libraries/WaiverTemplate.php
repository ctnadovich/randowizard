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
//    along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace App\Libraries;

class WaiverTemplate
{
    /*
     * Paths are relative to the CodeIgniter application base URL.
     *
     * IndemnifiedParty supplies only simple filenames. This class
     * determines where those resources live.
     */
    private const TEMPLATE_PATH =
    'assets/local/waivers/';

    private const LOGO_PATH =
    'assets/local/waiver-logos/';

    public readonly string $template_name;
    public readonly string $template_url;

    private readonly string $raw_contents;

    public array $data;

    public function __construct(string $template_name)
    {
        self::assertValidResourceName(
            $template_name,
            'template'
        );

        $this->template_name = $template_name;
        $this->template_url =
            self::templateUrl($template_name);

        $this->raw_contents = $this->fetchTemplate();

        $this->data = $this->parseTemplate(
            $this->raw_contents
        );
    }

    private function fetchTemplate(): string
    {
        $contents = @file_get_contents(
            $this->template_url
        );

        if ($contents === false) {
            throw new \RuntimeException(
                'Unable to fetch waiver template from '
                    . $this->template_url
            );
        }

        return $contents;
    }

    private function parseTemplate(
        string $contents
    ): array {
        $result = [];

        $currentTag = null;
        $currentText = '';

        $lines = preg_split('/\R/', $contents);

        if ($lines === false) {
            throw new \RuntimeException(
                'Unable to split waiver template into lines: '
                    . $this->template_url
            );
        }

        foreach ($lines as $line) {
            if (
                preg_match(
                    '/^\[([A-Z0-9_]+)\]$/',
                    trim($line),
                    $matches
                ) === 1
            ) {
                if ($currentTag !== null) {
                    $result[$currentTag][] =
                        $this->safe_text(
                            rtrim($currentText)
                        );
                }

                $currentTag = $matches[1];
                $currentText = '';
            } elseif ($currentTag !== null) {
                $currentText .= $line . "\n";
            }
        }

        if ($currentTag !== null) {
            $result[$currentTag][] =
                $this->safe_text(
                    rtrim($currentText)
                );
        }

        return $result;
    }

    /**
     * Construct the full URL for a waiver template.
     */
    public static function templateUrl(
        string $templateName
    ): string {
        self::assertValidResourceName(
            $templateName,
            'template'
        );

        return base_url(
            self::TEMPLATE_PATH . $templateName
        );
    }

    /**
     * Construct the full URL for a waiver logo.
     */
    public static function logoUrl(
        string $logoName
    ): string {
        self::assertValidResourceName(
            $logoName,
            'logo'
        );

        return base_url(
            self::LOGO_PATH . $logoName
        );
    }

    /**
     * Require a simple filename rather than an arbitrary URL or path.
     *
     * Valid examples:
     *
     *     rusa_waiver.txt
     *     rusa-logo.png
     *
     * Invalid examples:
     *
     *     ../private/file
     *     https://example.com/file.txt
     *     subdirectory/file.txt
     */
    private static function assertValidResourceName(
        string $name,
        string $resourceType
    ): void {
        if (
            $name === ''
            || preg_match(
                '/\A[A-Za-z0-9][A-Za-z0-9._-]*\z/',
                $name
            ) !== 1
        ) {
            throw new \InvalidArgumentException(
                "Invalid waiver $resourceType name: $name"
            );
        }
    }

    // Template validation methods 

    public function missingReplacementFields(
        array $contextData
    ): array {
        $missing = [];

        foreach ($this->replacementFields() as $field) {
            if (!array_key_exists($field, $contextData)) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    public function validateReplacementFields(
        array $contextData
    ): void {
        $missing = $this->missingReplacementFields(
            $contextData
        );

        if ($missing !== []) {
            throw new \RuntimeException(
                'Missing waiver context fields required '
                    . 'by template '
                    . $this->template_name
                    . ': '
                    . implode(', ', $missing)
            );
        }
    }

    // Interpolate all {{replace}} strings and return revised data (doesn't change $this->data)
    //
    // The default here is to allow undefined fields because we 
    // presume that validation was done earlier if desired. 

    public function interpolate_template(
        array $replaceMap,
        bool $allowUndefined = true
    ): array {
        $waiverTemplate = $this->data;

        foreach ($waiverTemplate as $tag => $strings) {
            foreach ($strings as $i => $text) {
                $text = preg_replace_callback(
                    '/\{\{([A-Za-z0-9_]+)\}\}/',
                    function (
                        array $matches
                    ) use (
                        $replaceMap,
                        $allowUndefined
                    ): string {
                        $name = $matches[1];

                        if (
                            !array_key_exists(
                                $name,
                                $replaceMap
                            )
                        ) {
                            if ($allowUndefined) {
                                return '{{' . $name . '}}';
                            }

                            throw new \RuntimeException(
                                "Undefined replacement: $name"
                            );
                        }

                        return (string) $replaceMap[$name];
                    },
                    $text
                );

                if ($text === null) {
                    throw new \RuntimeException(
                        'Error interpolating waiver template.'
                    );
                }

                $waiverTemplate[$tag][$i] = $text;
            }
        }

        return $waiverTemplate;
    }
    
    // returns a list of replacement fields that could be 
    // used to validate context fields

    public function replacementFields(): array
    {
        preg_match_all(
            '/\{\{([A-Za-z0-9_]+)\}\}/',
            $this->raw_contents,
            $matches
        );

        $fields = array_values(
            array_unique($matches[1] ?? [])
        );

        sort($fields);

        return $fields;
    }

    private function safe_text(string $s): string
    {
        $map = [
            "\u{2018}" => "'",
            "\u{2019}" => "'",
            "\u{201C}" => '"',
            "\u{201D}" => '"',
            "\u{2013}" => '-',
            "\u{2014}" => '-',
            "\u{2026}" => '...',
            "\u{00A0}" => ' ',
        ];

        $s = strtr($s, $map);

        $converted = iconv(
            'UTF-8',
            'windows-1252//TRANSLIT//IGNORE',
            $s
        );

        if ($converted === false) {
            throw new \RuntimeException(
                'Unable to convert waiver template text '
                    . 'to Windows-1252.'
            );
        }

        return $converted;
    }
}
