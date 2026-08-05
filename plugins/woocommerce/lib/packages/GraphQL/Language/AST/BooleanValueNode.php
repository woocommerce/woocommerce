<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class BooleanValueNode extends Node implements ValueNode
{
    public string $kind = NodeKind::BOOLEAN;

    public bool $value;
}
