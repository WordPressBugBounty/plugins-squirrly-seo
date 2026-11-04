<?php

/**
 * This file is generated. Do not edit it directly.
 */

declare(strict_types=1);

namespace SquirrlySEO\Vendor\WP\McpSchema\Record;

final class EmbeddedResource extends \SquirrlySEO\Vendor\WP\McpSchema\Record implements \SquirrlySEO\Vendor\WP\McpSchema\Contract\ContentBlock
{
    public const DEFINITION = 'EmbeddedResource';

    /**
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Record\MetaObject|\stdClass|null
     */
    public function getMeta()
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Record\MetaObject|\stdClass|null $value */
        $value = $this->declaredValue('_meta');

        return $value;
    }

    /**
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Record\Annotations|null
     */
    public function getAnnotations(): ?\SquirrlySEO\Vendor\WP\McpSchema\Record\Annotations
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Record\Annotations|null $value */
        $value = $this->declaredValue('annotations');

        return $value;
    }

    /**
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Record\BlobResourceContents|\SquirrlySEO\Vendor\WP\McpSchema\Record\TextResourceContents
     */
    public function getResource()
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Record\BlobResourceContents|\SquirrlySEO\Vendor\WP\McpSchema\Record\TextResourceContents $value */
        $value = $this->declaredValue('resource');

        return $value;
    }

    /**
     * @return 'resource'
     */
    public function getType(): string
    {
        /** @var 'resource' $value */
        $value = $this->declaredValue('type');

        return $value;
    }
}
