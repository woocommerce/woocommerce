<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FieldNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\HasFieldsType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\NamedType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\QueryValidationContext;

class FieldsOnCorrectType extends ValidationRule
{
    public function getVisitor(QueryValidationContext $context): array
    {
        return [
            NodeKind::FIELD => function (FieldNode $node) use ($context): void {
                $fieldDef = $context->getFieldDef();
                if ($fieldDef !== null && $fieldDef->isVisible()) {
                    return;
                }

                $type = $context->getParentType();
                if (! $type instanceof NamedType) {
                    return;
                }

                // This isn't valid. Let's find suggestions, if any.
                $schema = $context->getSchema();
                $fieldName = $node->name->value;
                // First determine if there are any suggested types to condition on.
                $suggestedTypeNames = $this->getSuggestedTypeNames($schema, $type, $fieldName);
                // If there are no suggested types, then perhaps this was a typo?
                $suggestedFieldNames = $suggestedTypeNames === []
                    ? $this->getSuggestedFieldNames($type, $fieldName)
                    : [];

                // Report an error, including helpful suggestions.
                $context->reportError(new Error(
                    static::undefinedFieldMessage(
                        $node->name->value,
                        $type->name,
                        $suggestedTypeNames,
                        $suggestedFieldNames
                    ),
                    [$node]
                ));
            },
        ];
    }

    /**
     * Go through all implementations of a type, as well as the interfaces
     * that it implements. If any of those types include the provided field,
     * suggest them, sorted by how often the type is referenced, starting
     * with interfaces.
     *
     * @throws InvariantViolation
     *
     * @return array<int, string>
     */
    protected function getSuggestedTypeNames(Schema $schema, Type $type, string $fieldName): array
    {
        if (Type::isAbstractType($type)) {
            $suggestedObjectTypes = [];
            $interfaceUsageCount = [];

            foreach ($schema->getPossibleTypes($type) as $possibleType) {
                if (! $possibleType->hasField($fieldName)) {
                    continue;
                }

                // This object type defines this field.
                $suggestedObjectTypes[] = $possibleType->name;
                foreach ($possibleType->getInterfaces() as $possibleInterface) {
                    if (! $possibleInterface->hasField($fieldName)) {
                        continue;
                    }

                    // This interface type defines this field.
                    $interfaceUsageCount[$possibleInterface->name] = isset($interfaceUsageCount[$possibleInterface->name])
                        ? $interfaceUsageCount[$possibleInterface->name] + 1
                        : 0;
                }
            }

            // Suggest interface types based on how common they are.
            arsort($interfaceUsageCount);
            $suggestedInterfaceTypes = array_keys($interfaceUsageCount);

            // Suggest both interface and object types.
            return array_merge($suggestedInterfaceTypes, $suggestedObjectTypes);
        }

        // Otherwise, must be an Object type, which does not have suggested types.
        return [];
    }

    /**
     * For the field name provided, determine if there are any similar field names
     * that may be the result of a typo.
     *
     * @throws InvariantViolation
     *
     * @return array<int, string>
     */
    protected function getSuggestedFieldNames(Type $type, string $fieldName): array
    {
        if ($type instanceof HasFieldsType) {
            return Utils::suggestionList(
                $fieldName,
                $type->getFieldNames()
            );
        }

        // Otherwise, must be a Union type, which does not define fields.
        return [];
    }

    /**
     * @param array<string> $suggestedTypeNames
     * @param array<string> $suggestedFieldNames
     */
    public static function undefinedFieldMessage(
        string $fieldName,
        string $type,
        array $suggestedTypeNames,
        array $suggestedFieldNames
    ): string {
        $message = "Cannot query field \"{$fieldName}\" on type \"{$type}\".";

        if ($suggestedTypeNames !== []) {
            $suggestions = Utils::quotedOrList($suggestedTypeNames);

            $message .= " Did you mean to use an inline fragment on {$suggestions}?";
        } elseif ($suggestedFieldNames !== []) {
            $suggestions = Utils::quotedOrList($suggestedFieldNames);

            $message .= " Did you mean {$suggestions}?";
        }

        return $message;
    }
}
