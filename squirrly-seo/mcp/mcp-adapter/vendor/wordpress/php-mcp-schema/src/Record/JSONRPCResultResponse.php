<?php

/**
 * This file is generated. Do not edit it directly.
 */

declare(strict_types=1);

namespace SquirrlySEO\Vendor\WP\McpSchema\Record;

final class JSONRPCResultResponse extends \SquirrlySEO\Vendor\WP\McpSchema\Record implements \SquirrlySEO\Vendor\WP\McpSchema\Contract\JSONRPCMessage, \SquirrlySEO\Vendor\WP\McpSchema\Contract\JSONRPCResponse
{
    public const DEFINITION = 'JSONRPCResultResponse';

    /**
     * @return float|int|string
     */
    public function getId()
    {
        /** @var float|int|string $value */
        $value = $this->declaredValue('id');

        return $value;
    }

    /**
     * @return '2.0'
     */
    public function getJsonrpc(): string
    {
        /** @var '2.0' $value */
        $value = $this->declaredValue('jsonrpc');

        return $value;
    }

    /**
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Record\Result
     */
    public function getResult(): \SquirrlySEO\Vendor\WP\McpSchema\Record\Result
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Record\Result $value */
        $value = $this->declaredValue('result');

        return $value;
    }
}
