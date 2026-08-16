<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class InputValueDefinitionNode extends Node
{
    public string $kind = NodeKind::INPUT_VALUE_DEFINITION;

    public NameNode $name;

    /** @var NamedTypeNode|ListTypeNode|NonNullTypeNode */
    public TypeNode $type;

    /** @var VariableNode|NullValueNode|IntValueNode|FloatValueNode|StringValueNode|BooleanValueNode|EnumValueNode|ListValueNode|ObjectValueNode|null */
    public ?ValueNode $defaultValue = null;

    /** @var NodeList<DirectiveNode> */
    public NodeList $directives;

    public ?StringValueNode $description = null;
}
