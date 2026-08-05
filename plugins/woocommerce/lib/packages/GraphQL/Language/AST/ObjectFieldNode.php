<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class ObjectFieldNode extends Node
{
    public string $kind = NodeKind::OBJECT_FIELD;

    public NameNode $name;

    /** @var VariableNode|NullValueNode|IntValueNode|FloatValueNode|StringValueNode|BooleanValueNode|EnumValueNode|ListValueNode|ObjectValueNode */
    public ValueNode $value;
}
