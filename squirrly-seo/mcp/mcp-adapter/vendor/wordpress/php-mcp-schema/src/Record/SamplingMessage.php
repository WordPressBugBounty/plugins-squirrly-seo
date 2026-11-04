<?php

/**
 * This file is generated. Do not edit it directly.
 */

declare(strict_types=1);

namespace SquirrlySEO\Vendor\WP\McpSchema\Record;

final class SamplingMessage extends \SquirrlySEO\Vendor\WP\McpSchema\Record
{
    public const DEFINITION = 'SamplingMessage';

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
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Record\AudioContent|\SquirrlySEO\Vendor\WP\McpSchema\Record\ImageContent|\SquirrlySEO\Vendor\WP\McpSchema\Record\TextContent|\SquirrlySEO\Vendor\WP\McpSchema\Record\ToolResultContent|\SquirrlySEO\Vendor\WP\McpSchema\Record\ToolUseContent|array<int, \SquirrlySEO\Vendor\WP\McpSchema\Contract\SamplingMessageContentBlock>
     */
    public function getContent()
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Record\AudioContent|\SquirrlySEO\Vendor\WP\McpSchema\Record\ImageContent|\SquirrlySEO\Vendor\WP\McpSchema\Record\TextContent|\SquirrlySEO\Vendor\WP\McpSchema\Record\ToolResultContent|\SquirrlySEO\Vendor\WP\McpSchema\Record\ToolUseContent|array<int, \SquirrlySEO\Vendor\WP\McpSchema\Contract\SamplingMessageContentBlock> $value */
        $value = $this->declaredValue('content');

        return $value;
    }

    /**
     * @return 'assistant'|'user'
     */
    public function getRole(): string
    {
        /** @var 'assistant'|'user' $value */
        $value = $this->declaredValue('role');

        return $value;
    }
}
