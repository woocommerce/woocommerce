<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class ObjectValueNode extends Node implements ValueNode
{
    public string $kind = NodeKind::OBJECT;

    /** @var NodeList<ObjectFieldNode> */
    public NodeList $fields;
}
