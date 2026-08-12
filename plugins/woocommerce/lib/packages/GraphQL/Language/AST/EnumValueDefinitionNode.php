<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class EnumValueDefinitionNode extends Node
{
    public string $kind = NodeKind::ENUM_VALUE_DEFINITION;

    public NameNode $name;

    /** @var NodeList<DirectiveNode> */
    public NodeList $directives;

    public ?StringValueNode $description = null;
}
