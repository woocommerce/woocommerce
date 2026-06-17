<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class ObjectTypeExtensionNode extends Node implements TypeExtensionNode
{
    public string $kind = NodeKind::OBJECT_TYPE_EXTENSION;

    public NameNode $name;

    /** @var NodeList<NamedTypeNode> */
    public NodeList $interfaces;

    /** @var NodeList<DirectiveNode> */
    public NodeList $directives;

    /** @var NodeList<FieldDefinitionNode> */
    public NodeList $fields;

    public function getName(): NameNode
    {
        return $this->name;
    }
}
