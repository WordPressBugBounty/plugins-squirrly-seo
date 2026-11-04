<?php

/**
 * This file is generated. Do not edit it directly.
 */

declare(strict_types=1);

namespace SquirrlySEO\Vendor\WP\McpSchema\Record;

final class Icons extends \SquirrlySEO\Vendor\WP\McpSchema\Record
{
    public const DEFINITION = 'Icons';

    /**
     * @return array<int, \SquirrlySEO\Vendor\WP\McpSchema\Record\Icon>|null
     */
    public function getIcons(): ?array
    {
        /** @var array<int, \SquirrlySEO\Vendor\WP\McpSchema\Record\Icon>|null $value */
        $value = $this->declaredValue('icons');

        return $value;
    }
}
