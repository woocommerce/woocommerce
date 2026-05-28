<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class ListTypeNode extends Node implements TypeNode
{
    public string $kind = NodeKind::LIST_TYPE;

    /** @var NamedTypeNode|ListTypeNode|NonNullTypeNode */
    public TypeNode $type;
}
