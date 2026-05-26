<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class EnumValueNode extends Node implements ValueNode
{
    public string $kind = NodeKind::ENUM;

    public string $value;
}
