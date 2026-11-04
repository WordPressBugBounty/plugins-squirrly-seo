<?php

/**
 * This file is generated. Do not edit it directly.
 */

declare(strict_types=1);

namespace SquirrlySEO\Vendor\WP\McpSchema\Record;

final class ElicitRequest extends \SquirrlySEO\Vendor\WP\McpSchema\Record implements \SquirrlySEO\Vendor\WP\McpSchema\Contract\InputRequest, \SquirrlySEO\Vendor\WP\McpSchema\Contract\ServerRequest
{
    public const DEFINITION = 'ElicitRequest';

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
     * @return 'elicitation/create'
     */
    public function getMethod(): string
    {
        /** @var 'elicitation/create' $value */
        $value = $this->declaredValue('method');

        return $value;
    }

    /**
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Contract\ElicitRequestParams
     */
    public function getParams(): \SquirrlySEO\Vendor\WP\McpSchema\Contract\ElicitRequestParams
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Contract\ElicitRequestParams $value */
        $value = $this->declaredValue('params');

        return $value;
    }
}
