<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class EnumTypeExtensionNode extends Node implements TypeExtensionNode
{
    public string $kind = NodeKind::ENUM_TYPE_EXTENSION;

    public NameNode $name;

    /** @var NodeList<DirectiveNode> */
    public NodeList $directives;

    /** @var NodeList<EnumValueDefinitionNode> */
    public NodeList $values;

    public function __construct(array $vars)
    {
        parent::__construct($vars);
        $this->directives ??= new NodeList([]);
    }

    public function getName(): NameNode
    {
        return $this->name;
    }
}
