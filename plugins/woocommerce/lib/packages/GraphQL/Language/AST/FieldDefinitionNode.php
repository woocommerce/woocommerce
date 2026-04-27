<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class FieldDefinitionNode extends Node
{
    public string $kind = NodeKind::FIELD_DEFINITION;

    public NameNode $name;

    /** @var NodeList<InputValueDefinitionNode> */
    public NodeList $arguments;

    /** @var NamedTypeNode|ListTypeNode|NonNullTypeNode */
    public TypeNode $type;

    /** @var NodeList<DirectiveNode> */
    public NodeList $directives;

    public ?StringValueNode $description = null;
}
