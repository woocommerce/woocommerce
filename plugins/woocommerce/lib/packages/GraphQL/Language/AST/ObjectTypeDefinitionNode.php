<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class ObjectTypeDefinitionNode extends Node implements TypeDefinitionNode
{
    public string $kind = NodeKind::OBJECT_TYPE_DEFINITION;

    public NameNode $name;

    /** @var NodeList<NamedTypeNode> */
    public NodeList $interfaces;

    /** @var NodeList<DirectiveNode> */
    public NodeList $directives;

    /** @var NodeList<FieldDefinitionNode> */
    public NodeList $fields;

    public ?StringValueNode $description = null;

    public function getName(): NameNode
    {
        return $this->name;
    }
}
