<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class VariableDefinitionNode extends Node implements DefinitionNode
{
    public string $kind = NodeKind::VARIABLE_DEFINITION;

    public VariableNode $variable;

    /** @var NamedTypeNode|ListTypeNode|NonNullTypeNode */
    public TypeNode $type;

    /** @var VariableNode|NullValueNode|IntValueNode|FloatValueNode|StringValueNode|BooleanValueNode|EnumValueNode|ListValueNode|ObjectValueNode|null */
    public ?ValueNode $defaultValue = null;

    /** @var NodeList<DirectiveNode> */
    public NodeList $directives;

    public function __construct(array $vars)
    {
        parent::__construct($vars);
        $this->directives ??= new NodeList([]);
    }
}
