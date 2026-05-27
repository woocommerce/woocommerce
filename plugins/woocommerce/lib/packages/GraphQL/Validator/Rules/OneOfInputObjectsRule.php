<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ObjectValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InputObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\QueryValidationContext;

/**
 * OneOf Input Objects validation rule.
 *
 * Validates that OneOf Input Objects have exactly one non-null field provided.
 */
class OneOfInputObjectsRule extends ValidationRule
{
    public function getVisitor(QueryValidationContext $context): array
    {
        return [
            NodeKind::OBJECT => static function (ObjectValueNode $node) use ($context): void {
                $type = $context->getInputType();

                if ($type === null) {
                    return;
                }

                $namedType = Type::getNamedType($type);
                if (! ($namedType instanceof InputObjectType)
                    || ! $namedType->isOneOf()
                ) {
                    return;
                }

                $providedFields = [];
                $nullFields = [];

                foreach ($node->fields as $fieldNode) {
                    $fieldName = $fieldNode->name->value;
                    $providedFields[] = $fieldName;

                    // Check if the field value is explicitly null
                    if ($fieldNode->value->kind === NodeKind::NULL) {
                        $nullFields[] = $fieldName;
                    }
                }

                $fieldCount = count($providedFields);

                if ($fieldCount === 0) {
                    $context->reportError(new Error(
                        static::oneOfInputObjectExpectedExactlyOneFieldMessage($namedType->name),
                        [$node]
                    ));

                    return;
                }

                if ($fieldCount > 1) {
                    $context->reportError(new Error(
                        static::oneOfInputObjectExpectedExactlyOneFieldMessage($namedType->name, $fieldCount),
                        [$node]
                    ));

                    return;
                }

                // At this point, $fieldCount === 1
                if (count($nullFields) > 0) {
                    // Exactly one field provided, but it's null
                    $context->reportError(new Error(
                        static::oneOfInputObjectFieldValueMustNotBeNullMessage($namedType->name, $nullFields[0]),
                        [$node]
                    ));
                }
            },
        ];
    }

    public static function oneOfInputObjectExpectedExactlyOneFieldMessage(string $typeName, ?int $providedCount = null): string
    {
        if ($providedCount === null) {
            return "OneOf input object '{$typeName}' must specify exactly one field.";
        }

        return "OneOf input object '{$typeName}' must specify exactly one field, but {$providedCount} fields were provided.";
    }

    public static function oneOfInputObjectFieldValueMustNotBeNullMessage(string $typeName, string $fieldName): string
    {
        return "OneOf input object '{$typeName}' field '{$fieldName}' must be non-null.";
    }
}
