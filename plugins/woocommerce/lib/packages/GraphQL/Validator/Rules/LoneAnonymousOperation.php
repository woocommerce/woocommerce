<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\OperationDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\QueryValidationContext;

/**
 * Lone anonymous operation.
 *
 * A Automattic\WooCommerce\Vendor\GraphQL document is only valid if when it contains an anonymous operation
 * (the query shorthand) that it contains only that one operation definition.
 */
class LoneAnonymousOperation extends ValidationRule
{
    public function getVisitor(QueryValidationContext $context): array
    {
        $operationCount = 0;

        return [
            NodeKind::DOCUMENT => static function (DocumentNode $node) use (&$operationCount): void {
                $operationCount = 0;
                foreach ($node->definitions as $definition) {
                    if ($definition instanceof OperationDefinitionNode) {
                        ++$operationCount;
                    }
                }
            },
            NodeKind::OPERATION_DEFINITION => static function (OperationDefinitionNode $node) use (&$operationCount, $context): void {
                if ($node->name !== null || $operationCount <= 1) {
                    return;
                }

                $context->reportError(
                    new Error(static::anonOperationNotAloneMessage(), [$node])
                );
            },
        ];
    }

    public static function anonOperationNotAloneMessage(): string
    {
        return 'This anonymous operation must be the only defined operation.';
    }
}
