<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NameNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\OperationDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Visitor;
use Automattic\WooCommerce\Vendor\GraphQL\Language\VisitorOperation;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\QueryValidationContext;

class UniqueOperationNames extends ValidationRule
{
    /** @var array<string, NameNode> */
    protected array $knownOperationNames;

    public function getVisitor(QueryValidationContext $context): array
    {
        $this->knownOperationNames = [];

        return [
            NodeKind::OPERATION_DEFINITION => function (OperationDefinitionNode $node) use ($context): VisitorOperation {
                $operationName = $node->name;

                if ($operationName !== null) {
                    if (! isset($this->knownOperationNames[$operationName->value])) {
                        $this->knownOperationNames[$operationName->value] = $operationName;
                    } else {
                        $context->reportError(new Error(
                            static::duplicateOperationNameMessage($operationName->value),
                            [$this->knownOperationNames[$operationName->value], $operationName]
                        ));
                    }
                }

                return Visitor::skipNode();
            },
            NodeKind::FRAGMENT_DEFINITION => static fn (): VisitorOperation => Visitor::skipNode(),
        ];
    }

    public static function duplicateOperationNameMessage(string $operationName): string
    {
        return "There can be only one operation named \"{$operationName}\".";
    }
}
