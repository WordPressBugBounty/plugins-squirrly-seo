<?php

/**
 * This file is generated. Do not edit it directly.
 */

declare(strict_types=1);

namespace SquirrlySEO\Vendor\WP\McpSchema\Record;

final class SubscriptionsAcknowledgedNotification extends \SquirrlySEO\Vendor\WP\McpSchema\Record implements \SquirrlySEO\Vendor\WP\McpSchema\Contract\ServerNotification
{
    public const DEFINITION = 'SubscriptionsAcknowledgedNotification';

    /**
     * @return '2.0'
     */
    public function getJsonrpc(): string
    {
        /** @var '2.0' $value */
        $value = $this->declaredValue('jsonrpc');

        return $value;
    }

    /**
     * @return 'notifications/subscriptions/acknowledged'
     */
    public function getMethod(): string
    {
        /** @var 'notifications/subscriptions/acknowledged' $value */
        $value = $this->declaredValue('method');

        return $value;
    }

    /**
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Record\SubscriptionsAcknowledgedNotificationParams
     */
    public function getParams(): \SquirrlySEO\Vendor\WP\McpSchema\Record\SubscriptionsAcknowledgedNotificationParams
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Record\SubscriptionsAcknowledgedNotificationParams $value */
        $value = $this->declaredValue('params');

        return $value;
    }
}
