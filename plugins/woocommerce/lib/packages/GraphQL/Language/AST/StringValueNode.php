<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class StringValueNode extends Node implements ValueNode
{
    public string $kind = NodeKind::STRING;

    public string $value;

    public bool $block = false;
}
