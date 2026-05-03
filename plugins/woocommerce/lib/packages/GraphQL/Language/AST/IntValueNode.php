<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class IntValueNode extends Node implements ValueNode
{
    public string $kind = NodeKind::INT;

    public string $value;
}
