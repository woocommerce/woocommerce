<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class DirectiveDefinitionNode extends Node implements TypeSystemDefinitionNode
{
    public string $kind = NodeKind::DIRECTIVE_DEFINITION;

    public NameNode $name;

    public ?StringValueNode $description = null;

    /** @var NodeList<InputValueDefinitionNode> */
    public NodeList $arguments;

    public bool $repeatable;

    /** @var NodeList<NameNode> */
    public NodeList $locations;

    public function __construct(array $vars)
    {
        parent::__construct($vars);
        $this->arguments ??= new NodeList([]);
    }
}
