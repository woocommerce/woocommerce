<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Utils;

use Automattic\WooCommerce\Vendor\GraphQL\Error\ClientAware;
use Automattic\WooCommerce\Vendor\GraphQL\Error\CoercionError;
use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\EnumType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InputObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InputType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ListOfType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\NonNull;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ScalarType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;

/**
 * @phpstan-type CoercedValue array{errors: null, value: mixed}
 * @phpstan-type CoercedErrors array{errors: array<int, CoercionError>, value: null}
 *
 * @phpstan-import-type InputPath from CoercionError
 */
class Value
{
    /**
     * Coerce the given value to match the given Automattic\WooCommerce\Vendor\GraphQL Input Type.
     *
     * Returns either a value which is valid for the provided type,
     * or a list of encountered coercion errors.
     *
     * @param mixed $value
     * @param InputType&Type $type
     *
     * @phpstan-param InputPath|null $path
     *
     * @throws InvariantViolation
     *
     * @phpstan-return CoercedValue|CoercedErrors
     */
    public static function coerceInputValue($value, InputType $type, ?array $path = null, ?Schema $schema = null): array
    {
        if ($type instanceof NonNull) {
            if ($value === null) {
                return self::ofErrors([
                    CoercionError::make("Expected non-nullable type \"{$type}\" not to be null.", $path, $value),
                ]);
            }

            // @phpstan-ignore-next-line wrapped type is known to be input type after schema validation
            return self::coerceInputValue($value, $type->getWrappedType(), $path, $schema);
        }

        if ($value === null) {
            // Explicitly return the value null.
            return self::ofValue(null);
        }

        // Account for type loader returning a different scalar instance than
        // the built-in singleton used in field definitions. Resolve the actual
        // type from the schema to ensure the correct parseValue() is called.
        if ($schema !== null && Type::isBuiltInScalar($type)) {
            $schemaType = $schema->getType($type->name);
            assert($schemaType instanceof ScalarType, "Schema must provide a ScalarType for built-in scalar \"{$type->name}\".");
            $type = $schemaType;
        }

        if ($type instanceof ScalarType || $type instanceof EnumType) {
            try {
                return self::ofValue($type->parseValue($value));
            } catch (\Throwable $error) {
                if (
                    $error instanceof Error
                    || ($error instanceof ClientAware && $error->isClientSafe())
                ) {
                    return self::ofErrors([
                        CoercionError::make($error->getMessage(), $path, $value, $error),
                    ]);
                }

                return self::ofErrors([
                    CoercionError::make("Expected type \"{$type->name}\".", $path, $value, $error),
                ]);
            }
        }

        if ($type instanceof ListOfType) {
            $itemType = $type->getWrappedType();
            assert($itemType instanceof InputType, 'known through schema validation');

            if (is_iterable($value)) {
                $errors = [];
                $coercedValue = [];
                foreach ($value as $index => $itemValue) {
                    $coercedItem = self::coerceInputValue(
                        $itemValue,
                        $itemType,
                        [...$path ?? [], $index],
                        $schema,
                    );

                    if (isset($coercedItem['errors'])) {
                        $errors = self::add($errors, $coercedItem['errors']);
                    } else {
                        $coercedValue[] = $coercedItem['value'];
                    }
                }

                return $errors === []
                    ? self::ofValue($coercedValue)
                    : self::ofErrors($errors);
            }

            // Lists accept a non-list value as a list of one.
            $coercedItem = self::coerceInputValue($value, $itemType, null, $schema);

            return isset($coercedItem['errors'])
                ? $coercedItem
                : self::ofValue([$coercedItem['value']]);
        }

        assert($type instanceof InputObjectType, 'we handled all other cases at this point');

        if ($value instanceof \stdClass) {
            // Cast objects to associative array before checking the fields.
            // Note that the coerced value will be an array.
            $value = (array) $value;
        } elseif (! is_array($value)) {
            return self::ofErrors([
                CoercionError::make("Expected type \"{$type->name}\" to be an object.", $path, $value),
            ]);
        }

        $errors = [];
        $coercedValue = [];
        $fields = $type->getFields();
        foreach ($fields as $fieldName => $field) {
            if (array_key_exists($fieldName, $value)) {
                $fieldValue = $value[$fieldName];
                $coercedField = self::coerceInputValue(
                    $fieldValue,
                    $field->getType(),
                    [...$path ?? [], $fieldName],
                    $schema,
                );

                if (isset($coercedField['errors'])) {
                    $errors = self::add($errors, $coercedField['errors']);
                } else {
                    $coercedValue[$fieldName] = $coercedField['value'];
                }
            } elseif ($field->defaultValueExists()) {
                $coercedValue[$fieldName] = $field->defaultValue;
            } elseif ($field->getType() instanceof NonNull) {
                $errors = self::add(
                    $errors,
                    CoercionError::make("Field \"{$fieldName}\" of required type \"{$field->getType()->toString()}\" was not provided.", $path, $value)
                );
            }
        }

        // Ensure every provided field is defined.
        foreach ($value as $fieldName => $field) {
            if (array_key_exists($fieldName, $fields)) {
                continue;
            }

            $suggestions = Utils::suggestionList(
                (string) $fieldName,
                array_keys($fields)
            );
            $message = "Field \"{$fieldName}\" is not defined by type \"{$type->name}\"."
                . ($suggestions === []
                    ? ''
                    : ' Did you mean ' . Utils::quotedOrList($suggestions) . '?');

            $errors = self::add(
                $errors,
                CoercionError::make($message, $path, $value)
            );
        }

        // Validate OneOf constraints if this is a OneOf input type
        if ($type->isOneOf()) {
            $providedFieldCount = count($coercedValue);
            $nullFieldName = null;

            if ($providedFieldCount !== 1) {
                $errors = self::add(
                    $errors,
                    CoercionError::make("OneOf input object \"{$type->name}\" must specify exactly one field.", $path, $value)
                );
            } else {
                foreach ($coercedValue as $fieldName => $fieldValue) {
                    if ($fieldValue === null) {
                        $nullFieldName = $fieldName;
                    }
                }

                if ($nullFieldName !== null) {
                    $errors = self::add(
                        $errors,
                        CoercionError::make("OneOf input object \"{$type->name}\" field \"{$nullFieldName}\" must be non-null.", $path, $value)
                    );
                }
            }
        }

        return $errors === []
            ? self::ofValue($type->parseValue($coercedValue))
            : self::ofErrors($errors);
    }

    /**
     * @param array<int, CoercionError> $errors
     *
     * @phpstan-return CoercedErrors
     */
    private static function ofErrors(array $errors): array
    {
        return ['errors' => $errors, 'value' => null];
    }

    /**
     * @param mixed $value any value
     *
     * @phpstan-return CoercedValue
     */
    private static function ofValue($value): array
    {
        return ['errors' => null, 'value' => $value];
    }

    /**
     * @param array<int, CoercionError> $errors
     * @param CoercionError|array<int, CoercionError> $errorOrErrors
     *
     * @return array<int, CoercionError>
     */
    private static function add(array $errors, $errorOrErrors): array
    {
        $moreErrors = is_array($errorOrErrors)
            ? $errorOrErrors
            : [$errorOrErrors];

        return array_merge($errors, $moreErrors);
    }
}
