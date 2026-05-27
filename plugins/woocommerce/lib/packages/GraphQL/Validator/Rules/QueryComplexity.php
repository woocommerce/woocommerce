<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Values;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FieldNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentSpreadNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InlineFragmentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeList;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\OperationDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SelectionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SelectionSetNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\VariableDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Visitor;
use Automattic\WooCommerce\Vendor\GraphQL\Language\VisitorOperation;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Directive;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\FieldDefinition;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Introspection;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\QueryValidationContext;

/**
 * @phpstan-import-type ASTAndDefs from QuerySecurityRule
 */
class QueryComplexity extends QuerySecurityRule
{
    protected int $maxQueryComplexity;

    protected int $queryComplexity;

    /** @var array<string, mixed> */
    protected array $rawVariableValues = [];

    /** @var NodeList<VariableDefinitionNode> */
    protected NodeList $variableDefs;

    /** @phpstan-var ASTAndDefs */
    protected \ArrayObject $fieldNodeAndDefs;

    protected QueryValidationContext $context;

    /** @throws \InvalidArgumentException */
    public function __construct(int $maxQueryComplexity)
    {
        $this->setMaxQueryComplexity($maxQueryComplexity);
    }

    public function getVisitor(QueryValidationContext $context): array
    {
        $this->queryComplexity = 0;
        $this->context = $context;
        $this->variableDefs = new NodeList([]);
        $this->fieldNodeAndDefs = new \ArrayObject();

        return $this->invokeIfNeeded(
            $context,
            [
                NodeKind::SELECTION_SET => function (SelectionSetNode $selectionSet) use ($context): void {
                    $this->fieldNodeAndDefs = $this->collectFieldASTsAndDefs(
                        $context,
                        $context->getParentType(),
                        $selectionSet,
                        null,
                        $this->fieldNodeAndDefs
                    );
                },
                NodeKind::VARIABLE_DEFINITION => function ($def): VisitorOperation {
                    $this->variableDefs[] = $def;

                    return Visitor::skipNode();
                },
                NodeKind::DOCUMENT => [
                    'leave' => function (DocumentNode $document) use ($context): void {
                        $errors = $context->getErrors();

                        if ($errors !== []) {
                            return;
                        }

                        if ($this->maxQueryComplexity === self::DISABLED) {
                            return;
                        }

                        foreach ($document->definitions as $definition) {
                            if (! $definition instanceof OperationDefinitionNode) {
                                continue;
                            }

                            $this->queryComplexity = $this->fieldComplexity($definition->selectionSet);

                            if ($this->queryComplexity > $this->maxQueryComplexity) {
                                $context->reportError(
                                    new Error(static::maxQueryComplexityErrorMessage(
                                        $this->maxQueryComplexity,
                                        $this->queryComplexity
                                    ))
                                );

                                return;
                            }
                        }
                    },
                ],
            ]
        );
    }

    /** @throws \Exception */
    protected function fieldComplexity(SelectionSetNode $selectionSet): int
    {
        $complexity = 0;

        foreach ($selectionSet->selections as $selection) {
            $complexity += $this->nodeComplexity($selection);
        }

        return $complexity;
    }

    /** @throws \Exception */
    protected function nodeComplexity(SelectionNode $node): int
    {
        switch (true) {
            case $node instanceof FieldNode:
                // Exclude __schema field and all nested content from complexity calculation
                if ($node->name->value === Introspection::SCHEMA_FIELD_NAME) {
                    return 0;
                }

                if ($this->directiveExcludesField($node)) {
                    return 0;
                }

                $childrenComplexity = isset($node->selectionSet)
                    ? $this->fieldComplexity($node->selectionSet)
                    : 0;

                $fieldDef = $this->fieldDefinition($node);
                if ($fieldDef instanceof FieldDefinition && $fieldDef->complexityFn !== null) {
                    $fieldArguments = $this->buildFieldArguments($node);

                    return ($fieldDef->complexityFn)($childrenComplexity, $fieldArguments);
                }

                return $childrenComplexity + 1;

            case $node instanceof InlineFragmentNode:
                return $this->fieldComplexity($node->selectionSet);

            case $node instanceof FragmentSpreadNode:
                $fragment = $this->getFragment($node);

                if ($fragment !== null) {
                    return $this->fieldComplexity($fragment->selectionSet);
                }
        }

        return 0;
    }

    protected function fieldDefinition(FieldNode $field): ?FieldDefinition
    {
        foreach ($this->fieldNodeAndDefs[$this->getFieldName($field)] ?? [] as [$node, $def]) {
            if ($node === $field) {
                return $def;
            }
        }

        return null;
    }

    /**
     * Will the given field be executed at all, given the directives placed upon it?
     *
     * @throws \Exception
     * @throws \ReflectionException
     * @throws InvariantViolation
     */
    protected function directiveExcludesField(FieldNode $node): bool
    {
        foreach ($node->directives as $directiveNode) {
            if ($directiveNode->name->value === Directive::DEPRECATED_NAME) {
                return false;
            }

            [$errors, $variableValues] = Values::getVariableValues(
                $this->context->getSchema(),
                $this->variableDefs,
                $this->getRawVariableValues()
            );
            if ($errors !== null && $errors !== []) {
                throw new Error(implode("\n\n", array_map(static fn (Error $error): string => $error->getMessage(), $errors)));
            }

            if ($directiveNode->name->value === Directive::INCLUDE_NAME) {
                $includeArguments = Values::getArgumentValues(
                    Directive::includeDirective(),
                    $directiveNode,
                    $variableValues
                );
                assert(is_bool($includeArguments['if']), 'ensured by query validation');

                return ! $includeArguments['if'];
            }

            if ($directiveNode->name->value === Directive::SKIP_NAME) {
                $skipArguments = Values::getArgumentValues(
                    Directive::skipDirective(),
                    $directiveNode,
                    $variableValues
                );
                assert(is_bool($skipArguments['if']), 'ensured by query validation');

                return $skipArguments['if'];
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    public function getRawVariableValues(): array
    {
        return $this->rawVariableValues;
    }

    /** @param array<string, mixed>|null $rawVariableValues */
    public function setRawVariableValues(?array $rawVariableValues = null): void
    {
        $this->rawVariableValues = $rawVariableValues ?? [];
    }

    /**
     * @throws \Exception
     * @throws Error
     *
     * @return array<string, mixed>
     */
    protected function buildFieldArguments(FieldNode $node): array
    {
        $rawVariableValues = $this->getRawVariableValues();
        $fieldDef = $this->fieldDefinition($node);

        /** @var array<string, mixed> $args */
        $args = [];

        if ($fieldDef instanceof FieldDefinition) {
            [$errors, $variableValues] = Values::getVariableValues(
                $this->context->getSchema(),
                $this->variableDefs,
                $rawVariableValues
            );

            if (is_array($errors) && $errors !== []) {
                throw new Error(implode("\n\n", array_map(static fn ($error) => $error->getMessage(), $errors)));
            }

            $args = Values::getArgumentValues($fieldDef, $node, $variableValues);
        }

        return $args;
    }

    public function getMaxQueryComplexity(): int
    {
        return $this->maxQueryComplexity;
    }

    /**
     * Complexity of the first operation exceeding the defined limit, or, in case no operation
     * exceeds the limit, complexity of the last defined operation.
     */
    public function getQueryComplexity(): int
    {
        return $this->queryComplexity;
    }

    /**
     * Set max query complexity. If equal to 0 no check is done. Must be greater or equal to 0.
     *
     * @throws \InvalidArgumentException
     */
    public function setMaxQueryComplexity(int $maxQueryComplexity): void
    {
        $this->checkIfGreaterOrEqualToZero('maxQueryComplexity', $maxQueryComplexity);

        $this->maxQueryComplexity = $maxQueryComplexity;
    }

    public static function maxQueryComplexityErrorMessage(int $max, int $count): string
    {
        return "Max query complexity should be {$max} but got {$count}.";
    }

    protected function isEnabled(): bool
    {
        return $this->maxQueryComplexity !== self::DISABLED;
    }
}
