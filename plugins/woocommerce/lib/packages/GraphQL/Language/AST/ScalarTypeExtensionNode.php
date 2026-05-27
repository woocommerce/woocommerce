<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class ScalarTypeExtensionNode extends Node implements TypeExtensionNode
{
    public string $kind = NodeKind::SCALAR_TYPE_EXTENSION;

    public NameNode $name;

    /** @var NodeList<DirectiveNode> */
    public NodeList $directives;

    public function getName(): NameNode
    {
        return $this->name;
    }
}
