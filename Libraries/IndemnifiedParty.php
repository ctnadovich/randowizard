<?php

namespace App\Libraries;

use RuntimeException;

class IndemnifiedParty
{
    /**
     * Registry of all known indemnified parties.
     *
     * The array key is the indemnified_party_id.
     * Basic filenames for party dependent
     * resources are given here. 
     * The full path is constructed by the WaiverTemplate library
     * 
     */
    private const DEFINITIONS = [

        'rusa' => [
            'name' => 'Randonneurs USA',
            'template_name' => 'rusa_waiver_template.txt',
            'logo_name' => 'rusa-logo.png',
        ],

        'other' => [
            'name' => 'Example Organization',
            'template_name' => 'example_waiver.txt',
            'logo_name' => 'example-logo.png',
        ],
    ];

    public readonly string $id;
    public readonly string $name;
    public readonly string $template_name;
    public readonly string $waiver_view;
    public readonly ?string $logo_name;

    /**
     * Construct an indemnified party from its ID.
     *
     * Example:
     *
     *     $party = new IndemnifiedParty('rusa');
     */
    public function __construct(string $id)
    {
        $definition = self::DEFINITIONS[$id] ?? null;

        if ($definition === null) {
            throw new RuntimeException(
                "Unknown indemnified party '$id'."
            );
        }

        $this->id = $id;
        $this->name = $definition['name'];
        $this->template_name = $definition['template_name'];
        $this->logo_name = $definition['logo_name'];
    }

    /**
     * Returns all valid indemnified party IDs.
     */
    public static function ids(): array
    {
        return array_keys(self::DEFINITIONS);
    }

    /**
     * Returns true if the supplied ID is known.
     */
    public static function exists(string $id): bool
    {
        return array_key_exists($id, self::DEFINITIONS);
    }

    /**
     * Returns context fields contributed by this indemnified party.
     *
     * These become part of the immutable waiver context.
     */
    // public function toContextData(): array
    // {
    //     return [

    //         'indemnified_party_id' => $this->id,
    //         'indemnified_party_name' => $this->name,
    //         'template_name' => $this->template_name,
    //         'waiver_logo_name' => $this->logo_name,
    //     ];
    // }
}