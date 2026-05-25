<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class FloatValueNode extends Node implements ValueNode
{
    public string $kind = NodeKind::FLOAT;

    public string $value;
}
