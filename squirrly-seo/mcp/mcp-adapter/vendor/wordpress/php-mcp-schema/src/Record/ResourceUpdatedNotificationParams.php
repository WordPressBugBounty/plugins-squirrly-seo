<?php

/**
 * This file is generated. Do not edit it directly.
 */

declare(strict_types=1);

namespace SquirrlySEO\Vendor\WP\McpSchema\Record;

final class ResourceUpdatedNotificationParams extends \SquirrlySEO\Vendor\WP\McpSchema\Record
{
    public const DEFINITION = 'ResourceUpdatedNotificationParams';

    /**
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Record\NotificationMetaObject|\stdClass|null
     */
    public function getMeta()
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Record\NotificationMetaObject|\stdClass|null $value */
        $value = $this->declaredValue('_meta');

        return $value;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        /** @var string $value */
        $value = $this->declaredValue('uri');

        return $value;
    }
}
