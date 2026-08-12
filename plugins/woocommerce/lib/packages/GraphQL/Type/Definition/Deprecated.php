<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

#[\Attribute(\Attribute::TARGET_ALL)]
class Deprecated
{
    public string $reason;

    public function __construct(string $reason = Directive::DEFAULT_DEPRECATION_REASON)
    {
        $this->reason = $reason;
    }
}
