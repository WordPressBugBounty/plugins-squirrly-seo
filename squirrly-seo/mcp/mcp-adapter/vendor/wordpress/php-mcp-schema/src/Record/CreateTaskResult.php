<?php

/**
 * This file is generated. Do not edit it directly.
 */

declare(strict_types=1);

namespace SquirrlySEO\Vendor\WP\McpSchema\Record;

final class CreateTaskResult extends \SquirrlySEO\Vendor\WP\McpSchema\Record
{
    public const DEFINITION = 'CreateTaskResult';

    /**
     * @return \stdClass|null
     */
    public function getMeta(): ?\stdClass
    {
        /** @var \stdClass|null $value */
        $value = $this->declaredValue('_meta');

        return $value;
    }

    /**
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Record\Task
     */
    public function getTask(): \SquirrlySEO\Vendor\WP\McpSchema\Record\Task
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Record\Task $value */
        $value = $this->declaredValue('task');

        return $value;
    }
}
