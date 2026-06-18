<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Error;

/**
 * Caused by Automattic\WooCommerce\Vendor\GraphQL clients and can safely be displayed.
 */
class UserError extends \RuntimeException implements ClientAware
{
    public function isClientSafe(): bool
    {
        return true;
    }
}
