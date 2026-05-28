<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class ScalarTypeDefinitionNode extends Node implements TypeDefinitionNode
{
    public string $kind = NodeKind::SCALAR_TYPE_DEFINITION;

    public NameNode $name;

    /** @var NodeList<DirectiveNode> */
    public NodeList $directives;

    public ?StringValueNode $description = null;

    public function getName(): NameNode
    {
        return $this->name;
    }
}
