<?php

/**
 * This file is generated. Do not edit it directly.
 */

declare(strict_types=1);

namespace SquirrlySEO\Vendor\WP\McpSchema\Record;

/**
 * Canonical record available in: 2026-07-28.
 *
 * The same short name is also used by \SquirrlySEO\Vendor\WP\McpSchema\Contract\ClientResult,
 * a canonical union construction root available in: 2025-11-25.
 */
final class ClientResult extends \SquirrlySEO\Vendor\WP\McpSchema\Record
{
    public const DEFINITION = 'ClientResult';

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
     * @return string
     */
    public function getResultType(): string
    {
        /** @var string $value */
        $value = $this->declaredValue('resultType');

        return $value;
    }
}
