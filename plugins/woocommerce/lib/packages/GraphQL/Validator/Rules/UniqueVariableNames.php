<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NameNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\VariableDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\QueryValidationContext;

class UniqueVariableNames extends ValidationRule
{
    /** @var array<string, NameNode> */
    protected array $knownVariableNames;

    public function getVisitor(QueryValidationContext $context): array
    {
        $this->knownVariableNames = [];

        return [
            NodeKind::OPERATION_DEFINITION => function (): void {
                $this->knownVariableNames = [];
            },
            NodeKind::VARIABLE_DEFINITION => function (VariableDefinitionNode $node) use ($context): void {
                $variableName = $node->variable->name->value;
                if (! isset($this->knownVariableNames[$variableName])) {
                    $this->knownVariableNames[$variableName] = $node->variable->name;
                } else {
                    $context->reportError(new Error(
                        static::duplicateVariableMessage($variableName),
                        [$this->knownVariableNames[$variableName], $node->variable->name]
                    ));
                }
            },
        ];
    }

    public static function duplicateVariableMessage(string $variableName): string
    {
        return "There can be only one variable named \"{$variableName}\".";
    }
}
