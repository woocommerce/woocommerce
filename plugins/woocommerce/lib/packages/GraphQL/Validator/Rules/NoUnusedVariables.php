<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\OperationDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\VariableDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\QueryValidationContext;

class NoUnusedVariables extends ValidationRule
{
    /** @var array<int, VariableDefinitionNode> */
    protected array $variableDefs;

    public function getVisitor(QueryValidationContext $context): array
    {
        $this->variableDefs = [];

        return [
            NodeKind::OPERATION_DEFINITION => [
                'enter' => function (): void {
                    $this->variableDefs = [];
                },
                'leave' => function (OperationDefinitionNode $operation) use ($context): void {
                    $variableNameUsed = [];
                    $usages = $context->getRecursiveVariableUsages($operation);
                    $opName = $operation->name !== null
                        ? $operation->name->value
                        : null;

                    foreach ($usages as $usage) {
                        $node = $usage['node'];
                        $variableNameUsed[$node->name->value] = true;
                    }

                    foreach ($this->variableDefs as $variableDef) {
                        $variableName = $variableDef->variable->name->value;

                        if (! isset($variableNameUsed[$variableName])) {
                            $context->reportError(new Error(
                                static::unusedVariableMessage($variableName, $opName),
                                [$variableDef]
                            ));
                        }
                    }
                },
            ],
            NodeKind::VARIABLE_DEFINITION => function ($def): void {
                $this->variableDefs[] = $def;
            },
        ];
    }

    public static function unusedVariableMessage(string $varName, ?string $opName = null): string
    {
        return $opName !== null
            ? "Variable \"\${$varName}\" is never used in operation \"{$opName}\"."
            : "Variable \"\${$varName}\" is never used.";
    }
}
