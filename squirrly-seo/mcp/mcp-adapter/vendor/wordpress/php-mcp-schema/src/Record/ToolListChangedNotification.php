<?php

/**
 * This file is generated. Do not edit it directly.
 */

declare(strict_types=1);

namespace SquirrlySEO\Vendor\WP\McpSchema\Record;

final class ToolListChangedNotification extends \SquirrlySEO\Vendor\WP\McpSchema\Record implements \SquirrlySEO\Vendor\WP\McpSchema\Contract\ServerNotification
{
    public const DEFINITION = 'ToolListChangedNotification';

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
     * @return 'notifications/tools/list_changed'
     */
    public function getMethod(): string
    {
        /** @var 'notifications/tools/list_changed' $value */
        $value = $this->declaredValue('method');

        return $value;
    }

    /**
     * @return \SquirrlySEO\Vendor\WP\McpSchema\Record\NotificationParams|null
     */
    public function getParams(): ?\SquirrlySEO\Vendor\WP\McpSchema\Record\NotificationParams
    {
        /** @var \SquirrlySEO\Vendor\WP\McpSchema\Record\NotificationParams|null $value */
        $value = $this->declaredValue('params');

        return $value;
    }
}
