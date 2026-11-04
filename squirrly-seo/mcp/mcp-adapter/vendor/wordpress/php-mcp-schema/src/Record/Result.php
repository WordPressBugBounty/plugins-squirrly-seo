<?php

/**
 * This file is generated. Do not edit it directly.
 */

declare(strict_types=1);

namespace SquirrlySEO\Vendor\WP\McpSchema\Record;

final class Result extends \SquirrlySEO\Vendor\WP\McpSchema\Record implements \SquirrlySEO\Vendor\WP\McpSchema\Contract\ClientResult, \SquirrlySEO\Vendor\WP\McpSchema\Contract\ServerResult
{
    public const DEFINITION = 'Result';

    /**
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Record\ResultMetaObject|\stdClass|null
     */
    public function getMeta()
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Record\ResultMetaObject|\stdClass|null $value */
        $value = $this->declaredValue('_meta');

        return $value;
    }

    /**
     * Declared in: 2026-07-28.
     *
     * @return null|string
     */
    public function getResultType(): ?string
    {
        /** @var null|string $value */
        $value = $this->declaredValue('resultType');

        return $value;
    }
}
