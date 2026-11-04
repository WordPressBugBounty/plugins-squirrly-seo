<?php

/**
 * This file is generated. Do not edit it directly.
 */

declare(strict_types=1);

namespace SquirrlySEO\Vendor\WP\McpSchema\Record;

final class ElicitResult extends \SquirrlySEO\Vendor\WP\McpSchema\Record implements \SquirrlySEO\Vendor\WP\McpSchema\Contract\ClientResult, \SquirrlySEO\Vendor\WP\McpSchema\Contract\InputResponse
{
    public const DEFINITION = 'ElicitResult';

    /**
     * Declared in: 2025-11-25.
     *
     * @return \stdClass|null
     */
    public function getMeta(): ?\stdClass
    {
        /** @var \stdClass|null $value */
        $value = $this->declaredValue('_meta');

        return $value;
    }

    /**
     * @return 'accept'|'cancel'|'decline'
     */
    public function getAction(): string
    {
        /** @var 'accept'|'cancel'|'decline' $value */
        $value = $this->declaredValue('action');

        return $value;
    }

    /**
     * @return \stdClass|null
     */
    public function getContent(): ?\stdClass
    {
        /** @var \stdClass|null $value */
        $value = $this->declaredValue('content');

        return $value;
    }
}
