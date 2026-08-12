<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

/**
 * @phpstan-type OperationType 'query'|'mutation'|'subscription'
 */
class OperationDefinitionNode extends Node implements ExecutableDefinitionNode, HasSelectionSet
{
    public string $kind = NodeKind::OPERATION_DEFINITION;

    public ?NameNode $name = null;

    /** @var OperationType */
    public string $operation;

    /** @var NodeList<VariableDefinitionNode> */
    public NodeList $variableDefinitions;

    /** @var NodeList<DirectiveNode> */
    public NodeList $directives;

    public SelectionSetNode $selectionSet;

    public function __construct(array $vars)
    {
        parent::__construct($vars);
        $this->directives ??= new NodeList([]);
        $this->variableDefinitions ??= new NodeList([]);
    }

    public function getSelectionSet(): SelectionSetNode
    {
        return $this->selectionSet;
    }
}
