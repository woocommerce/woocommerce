<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class NameNode extends Node implements TypeNode
{
    public string $kind = NodeKind::NAME;

    public string $value;
}
