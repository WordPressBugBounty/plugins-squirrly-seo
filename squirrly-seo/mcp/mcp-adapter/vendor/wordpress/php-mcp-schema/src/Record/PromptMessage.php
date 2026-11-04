<?php

/**
 * This file is generated. Do not edit it directly.
 */

declare(strict_types=1);

namespace SquirrlySEO\Vendor\WP\McpSchema\Record;

final class PromptMessage extends \SquirrlySEO\Vendor\WP\McpSchema\Record
{
    public const DEFINITION = 'PromptMessage';

    /**
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Contract\ContentBlock
     */
    public function getContent(): \SquirrlySEO\Vendor\WP\McpSchema\Contract\ContentBlock
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Contract\ContentBlock $value */
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
