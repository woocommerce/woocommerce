<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

#[\Attribute(\Attribute::TARGET_ALL)]
class Description
{
    public string $description;

    public function __construct(string $description)
    {
        $this->description = $description;
    }
}
