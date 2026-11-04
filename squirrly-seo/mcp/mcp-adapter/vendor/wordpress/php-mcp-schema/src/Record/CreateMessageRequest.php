<?php

/**
 * This file is generated. Do not edit it directly.
 */

declare(strict_types=1);

namespace SquirrlySEO\Vendor\WP\McpSchema\Record;

final class CreateMessageRequest extends \SquirrlySEO\Vendor\WP\McpSchema\Record implements \SquirrlySEO\Vendor\WP\McpSchema\Contract\InputRequest, \SquirrlySEO\Vendor\WP\McpSchema\Contract\ServerRequest
{
    public const DEFINITION = 'CreateMessageRequest';

    /**
     * Declared in: 2025-11-25.
     *
     * @return float|int|null|string
     */
    public function getId()
    {
        /** @var float|int|null|string $value */
        $value = $this->declaredValue('id');

        return $value;
    }

    /**
     * Declared in: 2025-11-25.
     *
     * @return '2.0'|null
     */
    public function getJsonrpc(): ?string
    {
        /** @var '2.0'|null $value */
        $value = $this->declaredValue('jsonrpc');

        return $value;
    }

    /**
     * @return 'sampling/createMessage'
     */
    public function getMethod(): string
    {
        /** @var 'sampling/createMessage' $value */
        $value = $this->declaredValue('method');

        return $value;
    }

    /**
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Record\CreateMessageRequestParams
     */
    public function getParams(): \SquirrlySEO\Vendor\WP\McpSchema\Record\CreateMessageRequestParams
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Record\CreateMessageRequestParams $value */
        $value = $this->declaredValue('params');

        return $value;
    }
}
