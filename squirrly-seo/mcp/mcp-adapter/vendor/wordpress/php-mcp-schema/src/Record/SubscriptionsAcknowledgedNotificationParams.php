<?php

/**
 * This file is generated. Do not edit it directly.
 */

declare(strict_types=1);

namespace SquirrlySEO\Vendor\WP\McpSchema\Record;

final class SubscriptionsAcknowledgedNotificationParams extends \SquirrlySEO\Vendor\WP\McpSchema\Record
{
    public const DEFINITION = 'SubscriptionsAcknowledgedNotificationParams';

    /**
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Record\NotificationMetaObject|null
     */
    public function getMeta(): ?\SquirrlySEO\Vendor\WP\McpSchema\Record\NotificationMetaObject
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Record\NotificationMetaObject|null $value */
        $value = $this->declaredValue('_meta');

        return $value;
    }

    /**
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Record\SubscriptionFilter
     */
    public function getNotifications(): \SquirrlySEO\Vendor\WP\McpSchema\Record\SubscriptionFilter
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Record\SubscriptionFilter $value */
        $value = $this->declaredValue('notifications');

        return $value;
    }
}
