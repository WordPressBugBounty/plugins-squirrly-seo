<?php

/**
 * This file is generated. Do not edit it directly.
 */

declare(strict_types=1);

namespace SquirrlySEO\Vendor\WP\McpSchema\Record;

final class GetTaskPayloadResult extends \SquirrlySEO\Vendor\WP\McpSchema\Record implements \SquirrlySEO\Vendor\WP\McpSchema\Contract\ClientResult, \SquirrlySEO\Vendor\WP\McpSchema\Contract\ServerResult
{
    public const DEFINITION = 'GetTaskPayloadResult';

    /**
     * @return \stdClass|null
     */
    public function getMeta(): ?\stdClass
    {
        /** @var \stdClass|null $value */
        $value = $this->declaredValue('_meta');

        return $value;
    }
}
