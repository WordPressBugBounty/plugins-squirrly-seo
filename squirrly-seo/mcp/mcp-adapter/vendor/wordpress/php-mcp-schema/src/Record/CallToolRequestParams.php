<?php

/**
 * This file is generated. Do not edit it directly.
 */

declare(strict_types=1);

namespace SquirrlySEO\Vendor\WP\McpSchema\Record;

final class CallToolRequestParams extends \SquirrlySEO\Vendor\WP\McpSchema\Record
{
    public const DEFINITION = 'CallToolRequestParams';

    /**
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Record\RequestMetaObject|\stdClass|null
     */
    public function getMeta()
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Record\RequestMetaObject|\stdClass|null $value */
        $value = $this->declaredValue('_meta');

        return $value;
    }

    /**
     * @return \stdClass|null
     */
    public function getArguments(): ?\stdClass
    {
        /** @var \stdClass|null $value */
        $value = $this->declaredValue('arguments');

        return $value;
    }

    /**
     * Declared in: 2026-07-28.
     *
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Record\InputResponses|null
     */
    public function getInputResponses(): ?\SquirrlySEO\Vendor\WP\McpSchema\Record\InputResponses
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Record\InputResponses|null $value */
        $value = $this->declaredValue('inputResponses');

        return $value;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        /** @var string $value */
        $value = $this->declaredValue('name');

        return $value;
    }

    /**
     * Declared in: 2026-07-28.
     *
     * @return null|string
     */
    public function getRequestState(): ?string
    {
        /** @var null|string $value */
        $value = $this->declaredValue('requestState');

        return $value;
    }

    /**
     * Declared in: 2025-11-25.
     *
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Record\TaskMetadata|null
     */
    public function getTask(): ?\SquirrlySEO\Vendor\WP\McpSchema\Record\TaskMetadata
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Record\TaskMetadata|null $value */
        $value = $this->declaredValue('task');

        return $value;
    }
}
