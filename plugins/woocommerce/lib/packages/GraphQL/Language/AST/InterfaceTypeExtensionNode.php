<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class InterfaceTypeExtensionNode extends Node implements TypeExtensionNode
{
    public string $kind = NodeKind::INTERFACE_TYPE_EXTENSION;

    public NameNode $name;

    /** @var NodeList<DirectiveNode> */
    public NodeList $directives;

    /** @var NodeList<NamedTypeNode> */
    public NodeList $interfaces;

    /** @var NodeList<FieldDefinitionNode> */
    public NodeList $fields;

    public function getName(): NameNode
    {
        return $this->name;
    }
}
