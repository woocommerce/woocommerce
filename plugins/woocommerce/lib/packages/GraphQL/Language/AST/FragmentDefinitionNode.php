<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class FragmentDefinitionNode extends Node implements ExecutableDefinitionNode, HasSelectionSet
{
    public string $kind = NodeKind::FRAGMENT_DEFINITION;

    public NameNode $name;

    /**
     * Note: fragment variable definitions are experimental and may be changed
     * or removed in the future.
     *
     * Thus, this property is the single exception where this is not always a NodeList but may be null.
     *
     * @var NodeList<VariableDefinitionNode>|null
     */
    public ?NodeList $variableDefinitions = null;

    public NamedTypeNode $typeCondition;

    /** @var NodeList<DirectiveNode> */
    public NodeList $directives;

    public SelectionSetNode $selectionSet;

    public function __construct(array $vars)
    {
        parent::__construct($vars);
        $this->directives ??= new NodeList([]);
    }

    public function getSelectionSet(): SelectionSetNode
    {
        return $this->selectionSet;
    }
}
