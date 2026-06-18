<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FieldNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentSpreadNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\HasSelectionSet;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InlineFragmentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\OperationDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\VariableNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Visitor;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Argument;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\CompositeType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Directive;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\FieldDefinition;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InputType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\TypeInfo;

/**
 * An instance of this class is passed as the "this" context to all validators,
 * allowing access to commonly useful contextual information from within a
 * validation rule.
 *
 * @phpstan-type VariableUsage array{node: VariableNode, type: (Type&InputType)|null, defaultValue: mixed}
 */
class QueryValidationContext implements ValidationContext
{
    protected Schema $schema;

    protected DocumentNode $ast;

    /** @var list<Error> */
    protected array $errors = [];

    private TypeInfo $typeInfo;

    /** @var array<string, FragmentDefinitionNode> */
    private array $fragments;

    /** @var \SplObjectStorage<HasSelectionSet, array<int, FragmentSpreadNode>> */
    private \SplObjectStorage $fragmentSpreads;

    /** @var \SplObjectStorage<OperationDefinitionNode, array<int, FragmentDefinitionNode>> */
    private \SplObjectStorage $recursivelyReferencedFragments;

    /** @var \SplObjectStorage<HasSelectionSet, array<int, VariableUsage>> */
    private \SplObjectStorage $variableUsages;

    /** @var \SplObjectStorage<HasSelectionSet, array<int, VariableUsage>> */
    private \SplObjectStorage $recursiveVariableUsages;

    public function __construct(Schema $schema, DocumentNode $ast, TypeInfo $typeInfo)
    {
        $this->schema = $schema;
        $this->ast = $ast;
        $this->typeInfo = $typeInfo;

        $this->fragmentSpreads = new \SplObjectStorage();
        $this->recursivelyReferencedFragments = new \SplObjectStorage();
        $this->variableUsages = new \SplObjectStorage();
        $this->recursiveVariableUsages = new \SplObjectStorage();
    }

    public function reportError(Error $error): void
    {
        $this->errors[] = $error;
    }

    /** @return list<Error> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getDocument(): DocumentNode
    {
        return $this->ast;
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    /**
     * @throws \Exception
     *
     * @phpstan-return array<int, VariableUsage>
     */
    public function getRecursiveVariableUsages(OperationDefinitionNode $operation): array
    {
        $usages = $this->recursiveVariableUsages[$operation] ?? null;

        if ($usages === null) {
            $usages = $this->getVariableUsages($operation);
            $fragments = $this->getRecursivelyReferencedFragments($operation);

            $allUsages = [$usages];
            foreach ($fragments as $fragment) {
                $allUsages[] = $this->getVariableUsages($fragment);
            }

            $usages = array_merge(...$allUsages);
            $this->recursiveVariableUsages[$operation] = $usages;
        }

        return $usages;
    }

    /**
     * @param HasSelectionSet&Node $node
     *
     * @throws \Exception
     *
     * @phpstan-return array<int, VariableUsage>
     */
    private function getVariableUsages(HasSelectionSet $node): array
    {
        if (! isset($this->variableUsages[$node])) {
            $usages = [];
            $typeInfo = new TypeInfo($this->schema);
            Visitor::visit(
                $node,
                Visitor::visitWithTypeInfo(
                    $typeInfo,
                    [
                        NodeKind::VARIABLE_DEFINITION => static fn () => Visitor::skipNode(),
                        NodeKind::VARIABLE => static function (VariableNode $variable) use (&$usages, $typeInfo): void {
                            $usages[] = [
                                'node' => $variable,
                                'type' => $typeInfo->getInputType(),
                                'defaultValue' => $typeInfo->getDefaultValue(),
                            ];
                        },
                    ]
                )
            );

            return $this->variableUsages[$node] = $usages;
        }

        return $this->variableUsages[$node];
    }

    /** @return array<int, FragmentDefinitionNode> */
    public function getRecursivelyReferencedFragments(OperationDefinitionNode $operation): array
    {
        $fragments = $this->recursivelyReferencedFragments[$operation] ?? null;

        if ($fragments === null) {
            $fragments = [];
            $collectedNames = [];
            $nodesToVisit = [$operation];
            while ($nodesToVisit !== []) {
                $node = array_pop($nodesToVisit);
                $spreads = $this->getFragmentSpreads($node);
                foreach ($spreads as $spread) {
                    $fragName = $spread->name->value;

                    if ($collectedNames[$fragName] ?? false) {
                        continue;
                    }

                    $collectedNames[$fragName] = true;
                    $fragment = $this->getFragment($fragName);
                    if ($fragment === null) {
                        continue;
                    }

                    $fragments[] = $fragment;
                    $nodesToVisit[] = $fragment;
                }
            }

            $this->recursivelyReferencedFragments[$operation] = $fragments;
        }

        return $fragments;
    }

    /**
     * @param OperationDefinitionNode|FragmentDefinitionNode $node
     *
     * @return array<int, FragmentSpreadNode>
     */
    public function getFragmentSpreads(HasSelectionSet $node): array
    {
        $spreads = $this->fragmentSpreads[$node] ?? null;
        if ($spreads === null) {
            $spreads = [];

            $setsToVisit = [$node->getSelectionSet()];
            while ($setsToVisit !== []) {
                $set = array_pop($setsToVisit);

                foreach ($set->selections as $selection) {
                    if ($selection instanceof FragmentSpreadNode) {
                        $spreads[] = $selection;
                    } else {
                        assert($selection instanceof FieldNode || $selection instanceof InlineFragmentNode);

                        $selectionSet = $selection->selectionSet;
                        if ($selectionSet !== null) {
                            $setsToVisit[] = $selectionSet;
                        }
                    }
                }
            }

            $this->fragmentSpreads[$node] = $spreads;
        }

        return $spreads;
    }

    public function getFragment(string $name): ?FragmentDefinitionNode
    {
        if (! isset($this->fragments)) {
            $fragments = [];
            foreach ($this->getDocument()->definitions as $statement) {
                if ($statement instanceof FragmentDefinitionNode) {
                    $fragments[$statement->name->value] = $statement;
                }
            }

            $this->fragments = $fragments;
        }

        return $this->fragments[$name] ?? null;
    }

    public function getType(): ?Type
    {
        return $this->typeInfo->getType();
    }

    /** @return (CompositeType&Type)|null */
    public function getParentType(): ?CompositeType
    {
        return $this->typeInfo->getParentType();
    }

    /** @return (Type&InputType)|null */
    public function getInputType(): ?InputType
    {
        return $this->typeInfo->getInputType();
    }

    /** @return (Type&InputType)|null */
    public function getParentInputType(): ?InputType
    {
        return $this->typeInfo->getParentInputType();
    }

    public function getFieldDef(): ?FieldDefinition
    {
        return $this->typeInfo->getFieldDef();
    }

    public function getDirective(): ?Directive
    {
        return $this->typeInfo->getDirective();
    }

    public function getArgument(): ?Argument
    {
        return $this->typeInfo->getArgument();
    }
}
