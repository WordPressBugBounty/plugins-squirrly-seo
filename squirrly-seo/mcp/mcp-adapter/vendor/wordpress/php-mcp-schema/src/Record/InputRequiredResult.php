<?php

/**
 * This file is generated. Do not edit it directly.
 */

declare(strict_types=1);

namespace SquirrlySEO\Vendor\WP\McpSchema\Record;

final class InputRequiredResult extends \SquirrlySEO\Vendor\WP\McpSchema\Record implements \SquirrlySEO\Vendor\WP\McpSchema\Contract\ServerResult
{
    public const DEFINITION = 'InputRequiredResult';

    /**
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Record\ResultMetaObject|null
     */
    public function getMeta(): ?\SquirrlySEO\Vendor\WP\McpSchema\Record\ResultMetaObject
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Record\ResultMetaObject|null $value */
        $value = $this->declaredValue('_meta');

        return $value;
    }

    /**
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Record\InputRequests|null
     */
    public function getInputRequests(): ?\SquirrlySEO\Vendor\WP\McpSchema\Record\InputRequests
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Record\InputRequests|null $value */
        $value = $this->declaredValue('inputRequests');

        return $value;
    }

    /**
     * @return null|string
     */
    public function getRequestState(): ?string
    {
        /** @var null|string $value */
        $value = $this->declaredValue('requestState');

        return $value;
    }

    /**
     * @return string
     */
    public function getResultType(): string
    {
        /** @var string $value */
        $value = $this->declaredValue('resultType');

        return $value;
    }
}
