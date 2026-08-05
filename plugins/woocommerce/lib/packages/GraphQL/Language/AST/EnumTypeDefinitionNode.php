<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class EnumTypeDefinitionNode extends Node implements TypeDefinitionNode
{
    public string $kind = NodeKind::ENUM_TYPE_DEFINITION;

    public NameNode $name;

    /** @var NodeList<DirectiveNode> */
    public NodeList $directives;

    /** @var NodeList<EnumValueDefinitionNode> */
    public NodeList $values;

    public ?StringValueNode $description = null;

    public function getName(): NameNode
    {
        return $this->name;
    }

    public function __construct(array $vars)
    {
        parent::__construct($vars);
        $this->directives ??= new NodeList([]);
    }
}
