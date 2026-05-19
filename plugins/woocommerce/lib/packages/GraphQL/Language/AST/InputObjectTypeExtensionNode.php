<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class InputObjectTypeExtensionNode extends Node implements TypeExtensionNode
{
    public string $kind = NodeKind::INPUT_OBJECT_TYPE_EXTENSION;

    public NameNode $name;

    /** @var NodeList<DirectiveNode> */
    public NodeList $directives;

    /** @var NodeList<InputValueDefinitionNode> */
    public NodeList $fields;

    public function getName(): NameNode
    {
        return $this->name;
    }
}
