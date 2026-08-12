<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Server;

use Automattic\WooCommerce\Vendor\GraphQL\Error\ClientAware;

class RequestError extends \Exception implements ClientAware
{
    public function isClientSafe(): bool
    {
        return true;
    }
}
