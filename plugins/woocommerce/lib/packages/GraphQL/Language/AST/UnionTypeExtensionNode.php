<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class UnionTypeExtensionNode extends Node implements TypeExtensionNode
{
    public string $kind = NodeKind::UNION_TYPE_EXTENSION;

    public NameNode $name;

    /** @var NodeList<DirectiveNode> */
    public NodeList $directives;

    /** @var NodeList<NamedTypeNode> */
    public NodeList $types;

    public function getName(): NameNode
    {
        return $this->name;
    }
}
