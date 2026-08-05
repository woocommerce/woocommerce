<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class UnionTypeDefinitionNode extends Node implements TypeDefinitionNode
{
    public string $kind = NodeKind::UNION_TYPE_DEFINITION;

    public NameNode $name;

    /** @var NodeList<DirectiveNode> */
    public NodeList $directives;

    /** @var NodeList<NamedTypeNode> */
    public NodeList $types;

    public ?StringValueNode $description = null;

    public function getName(): NameNode
    {
        return $this->name;
    }
}
