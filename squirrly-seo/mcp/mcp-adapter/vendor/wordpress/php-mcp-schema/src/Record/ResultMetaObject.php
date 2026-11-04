<?php

/**
 * This file is generated. Do not edit it directly.
 */

declare(strict_types=1);

namespace SquirrlySEO\Vendor\WP\McpSchema\Record;

final class ResultMetaObject extends \SquirrlySEO\Vendor\WP\McpSchema\Record
{
    public const DEFINITION = 'ResultMetaObject';

    /**
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Record\Implementation|null
     */
    public function getIoModelcontextprotocolServerInfo(): ?\SquirrlySEO\Vendor\WP\McpSchema\Record\Implementation
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Record\Implementation|null $value */
        $value = $this->declaredValue('io.modelcontextprotocol/serverInfo');

        return $value;
    }
}
