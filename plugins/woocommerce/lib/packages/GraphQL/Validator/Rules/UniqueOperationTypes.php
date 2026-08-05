<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SchemaDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SchemaExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Visitor;
use Automattic\WooCommerce\Vendor\GraphQL\Language\VisitorOperation;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\SDLValidationContext;

/**
 * Unique operation types.
 *
 * A Automattic\WooCommerce\Vendor\GraphQL document is only valid if it has only one type per operation.
 */
class UniqueOperationTypes extends ValidationRule
{
    public function getSDLVisitor(SDLValidationContext $context): array
    {
        $schema = $context->getSchema();
        $definedOperationTypes = [];
        $existingOperationTypes = $schema !== null
            ? [
                'query' => $schema->getQueryType(),
                'mutation' => $schema->getMutationType(),
                'subscription' => $schema->getSubscriptionType(),
            ]
            : [];

        /**
         * @param SchemaDefinitionNode|SchemaExtensionNode $node
         */
        $checkOperationTypes = static function ($node) use ($context, &$definedOperationTypes, $existingOperationTypes): VisitorOperation {
            foreach ($node->operationTypes as $operationType) {
                $operation = $operationType->operation;
                $alreadyDefinedOperationType = $definedOperationTypes[$operation] ?? null;

                if (isset($existingOperationTypes[$operation])) {
                    $context->reportError(
                        new Error(
                            "Type for {$operation} already defined in the schema. It cannot be redefined.",
                            $operationType,
                        ),
                    );
                } elseif ($alreadyDefinedOperationType !== null) {
                    $context->reportError(
                        new Error(
                            "There can be only one {$operation} type in schema.",
                            [$alreadyDefinedOperationType, $operationType],
                        ),
                    );
                } else {
                    $definedOperationTypes[$operation] = $operationType;
                }
            }

            return Visitor::skipNode();
        };

        return [
            NodeKind::SCHEMA_DEFINITION => $checkOperationTypes,
            NodeKind::SCHEMA_EXTENSION => $checkOperationTypes,
        ];
    }
}
