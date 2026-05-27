<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class NonNullTypeNode extends Node implements TypeNode
{
    public string $kind = NodeKind::NON_NULL_TYPE;

    /** @var NamedTypeNode|ListTypeNode */
    public TypeNode $type;
}
