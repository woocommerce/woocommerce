<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Utils;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Error\SerializationError;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\StringValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\BlockString;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Printer;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Argument;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Directive;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\EnumType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\EnumValueDefinition;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\FieldDefinition;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ImplementingType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InputObjectField;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InputObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InterfaceType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\NamedType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ScalarType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\UnionType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Introspection;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;

/**
 * Prints the contents of a Schema in schema definition language.
 *
 * All sorting options sort alphabetically. If not given or `false`, the original schema definition order will be used.
 *
 * @phpstan-type Options array{
 *   sortArguments?: bool,
 *   sortEnumValues?: bool,
 *   sortFields?: bool,
 *   sortInputFields?: bool,
 *   sortTypes?: bool,
 * }
 *
 * @see \Automattic\WooCommerce\Vendor\GraphQL\Tests\Utils\SchemaPrinterTest
 */
class SchemaPrinter
{
    /**
     * @param array<string, bool> $options
     *
     * @phpstan-param Options $options
     *
     * @api
     *
     * @throws \JsonException
     * @throws Error
     * @throws InvariantViolation
     * @throws SerializationError
     */
    public static function doPrint(Schema $schema, array $options = []): string
    {
        return static::printFilteredSchema(
            $schema,
            static fn (Directive $directive): bool => ! Directive::isSpecifiedDirective($directive),
            static fn (NamedType $type): bool => ! $type->isBuiltInType(),
            $options
        );
    }

    /**
     * @param array<string, bool> $options
     *
     * @phpstan-param Options $options
     *
     * @api
     *
     * @throws \JsonException
     * @throws Error
     * @throws InvariantViolation
     * @throws SerializationError
     */
    public static function printIntrospectionSchema(Schema $schema, array $options = []): string
    {
        return static::printFilteredSchema(
            $schema,
            [Directive::class, 'isSpecifiedDirective'],
            [Introspection::class, 'isIntrospectionType'],
            $options
        );
    }

    /**
     * @param array<string, bool> $options
     *
     * @phpstan-param Options $options
     *
     * @throws \JsonException
     * @throws Error
     * @throws InvariantViolation
     * @throws SerializationError
     */
    public static function printType(Type $type, array $options = []): string
    {
        if ($type instanceof ScalarType) {
            return static::printScalar($type, $options);
        }

        if ($type instanceof ObjectType) {
            return static::printObject($type, $options);
        }

        if ($type instanceof InterfaceType) {
            return static::printInterface($type, $options);
        }

        if ($type instanceof UnionType) {
            return static::printUnion($type, $options);
        }

        if ($type instanceof EnumType) {
            return static::printEnum($type, $options);
        }

        if ($type instanceof InputObjectType) {
            return static::printInputObject($type, $options);
        }

        $unknownType = Utils::printSafe($type);
        throw new Error("Unknown type: {$unknownType}.");
    }

    /**
     * @param callable(Directive  $directive): bool $directiveFilter
     * @param callable(Type&NamedType $type): bool $typeFilter
     * @param array<string, bool> $options
     *
     * @phpstan-param Options $options
     *
     * @throws \JsonException
     * @throws Error
     * @throws InvariantViolation
     * @throws SerializationError
     */
    protected static function printFilteredSchema(Schema $schema, callable $directiveFilter, callable $typeFilter, array $options): string
    {
        $directives = array_filter($schema->getDirectives(), $directiveFilter);
        $types = array_filter($schema->getTypeMap(), $typeFilter);

        if (isset($options['sortTypes']) && $options['sortTypes']) {
            ksort($types);
        }

        $elements = [static::printSchemaDefinition($schema)];

        foreach ($directives as $directive) {
            $elements[] = static::printDirective($directive, $options);
        }

        foreach ($types as $type) {
            $elements[] = static::printType($type, $options);
        }

        /** @phpstan-ignore arrayFilter.strict */
        return implode("\n\n", array_filter($elements)) . "\n";
    }

    /**
     * @throws \JsonException
     * @throws InvariantViolation
     */
    protected static function printSchemaDefinition(Schema $schema): ?string
    {
        $queryType = $schema->getQueryType();
        $mutationType = $schema->getMutationType();
        $subscriptionType = $schema->getSubscriptionType();

        // Special case: When a schema has no root operation types, no valid schema
        // definition can be printed.
        if ($queryType === null && $mutationType === null && $subscriptionType === null) {
            return null;
        }

        // Only print a schema definition if there is a description or if it should
        // not be omitted because of having default type names.
        if ($schema->description !== null || ! static::hasDefaultRootOperationTypes($schema)) {
            return static::printDescription([], $schema) . "schema {\n"
                . ($queryType !== null ? "  query: {$queryType->name}\n" : '')
                . ($mutationType !== null ? "  mutation: {$mutationType->name}\n" : '')
                . ($subscriptionType !== null ? "  subscription: {$subscriptionType->name}\n" : '')
                . '}';
        }

        return null;
    }

    /**
     * Automattic\WooCommerce\Vendor\GraphQL schema define root types for each type of operation. These types are
     * the same as any other type and can be named in any manner, however there is
     * a common naming convention:.
     *
     * ```graphql
     *   schema {
     *     query: Query
     *     mutation: Mutation
     *     subscription: Subscription
     *   }
     * ```
     *
     * When using this naming convention, the schema description can be omitted.
     * When using this naming convention, the schema description can be omitted so
     * long as these names are only used for operation types.
     *
     * Note however that if any of these default names are used elsewhere in the
     * schema but not as a root operation type, the schema definition must still
     * be printed to avoid ambiguity.
     *
     * @throws InvariantViolation
     */
    protected static function hasDefaultRootOperationTypes(Schema $schema): bool
    {
        return $schema->getQueryType() === $schema->getType('Query')
            && $schema->getMutationType() === $schema->getType('Mutation')
            && $schema->getSubscriptionType() === $schema->getType('Subscription');
    }

    /**
     * @param array<string, bool> $options
     *
     * @phpstan-param Options $options
     *
     * @throws \JsonException
     * @throws InvariantViolation
     * @throws SerializationError
     */
    protected static function printDirective(Directive $directive, array $options): string
    {
        return static::printDescription($options, $directive)
            . 'directive @' . $directive->name
            . static::printArgs($options, $directive->args)
            . ($directive->isRepeatable ? ' repeatable' : '')
            . ' on ' . implode(' | ', $directive->locations);
    }

    /**
     * @param array<string, bool> $options
     * @param (Type&NamedType)|Directive|EnumValueDefinition|Argument|FieldDefinition|InputObjectField|Schema $def
     *
     * @throws \JsonException
     */
    protected static function printDescription(array $options, $def, string $indentation = '', bool $firstInBlock = true): string
    {
        $description = $def->description;
        if ($description === null) {
            return '';
        }

        $prefix = $indentation !== '' && ! $firstInBlock
            ? "\n{$indentation}"
            : $indentation;

        if (count(Utils::splitLines($description)) === 1) {
            $description = json_encode($description, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $description = BlockString::print($description);
            $description = $indentation !== ''
                ? str_replace("\n", "\n{$indentation}", $description)
                : $description;
        }

        return "{$prefix}{$description}\n";
    }

    /**
     * @param array<string, bool> $options
     * @param array<int, Argument> $args
     *
     * @phpstan-param Options $options
     *
     * @throws \JsonException
     * @throws InvariantViolation
     * @throws SerializationError
     */
    protected static function printArgs(array $options, array $args, string $indentation = ''): string
    {
        if ($args === []) {
            return '';
        }

        if (isset($options['sortArguments']) && $options['sortArguments']) {
            usort($args, static fn (Argument $left, Argument $right): int => $left->name <=> $right->name);
        }

        $allArgsWithoutDescription = true;
        foreach ($args as $arg) {
            $description = $arg->description;
            if ($description !== null && $description !== '') {
                $allArgsWithoutDescription = false;
                break;
            }
        }

        if ($allArgsWithoutDescription) {
            return '('
                . implode(
                    ', ',
                    array_map(
                        [static::class, 'printInputValue'],
                        $args
                    )
                )
                . ')';
        }

        $argsStrings = [];
        $firstInBlock = true;
        $previousHasDescription = false;
        foreach ($args as $arg) {
            $hasDescription = $arg->description !== null;
            if ($previousHasDescription && ! $hasDescription) {
                $argsStrings[] = '';
            }

            $argsStrings[] = static::printDescription($options, $arg, '  ' . $indentation, $firstInBlock)
                . '  '
                . $indentation
                . static::printInputValue($arg);
            $firstInBlock = false;
            $previousHasDescription = $hasDescription;
        }

        return "(\n"
            . implode("\n", $argsStrings)
            . "\n"
            . $indentation
            . ')';
    }

    /**
     * @param InputObjectField|Argument $arg
     *
     * @throws \JsonException
     * @throws InvariantViolation
     * @throws SerializationError
     */
    protected static function printInputValue($arg): string
    {
        $argDecl = "{$arg->name}: {$arg->getType()->toString()}";

        if ($arg->defaultValueExists()) {
            $defaultValueAST = AST::astFromValue($arg->defaultValue, $arg->getType());

            if ($defaultValueAST === null) {
                $inconvertibleDefaultValue = Utils::printSafe($arg->defaultValue);
                throw new InvariantViolation("Unable to convert defaultValue of argument {$arg->name} into AST: {$inconvertibleDefaultValue}.");
            }

            $printedDefaultValue = Printer::doPrint($defaultValueAST);
            $argDecl .= " = {$printedDefaultValue}";
        }

        return $argDecl . static::printDeprecated($arg);
    }

    /**
     * @param array<string, bool> $options
     *
     * @phpstan-param Options $options
     *
     * @throws \JsonException
     */
    protected static function printScalar(ScalarType $type, array $options): string
    {
        return static::printDescription($options, $type)
            . "scalar {$type->name}";
    }

    /**
     * @param array<string, bool> $options
     *
     * @phpstan-param Options $options
     *
     * @throws \JsonException
     * @throws InvariantViolation
     * @throws SerializationError
     */
    protected static function printObject(ObjectType $type, array $options): string
    {
        return static::printDescription($options, $type)
            . "type {$type->name}"
            . static::printImplementedInterfaces($type)
            . static::printFields($options, $type);
    }

    /**
     * @param array<string, bool> $options
     * @param ObjectType|InterfaceType $type
     *
     * @phpstan-param Options $options
     *
     * @throws \JsonException
     * @throws InvariantViolation
     * @throws SerializationError
     */
    protected static function printFields(array $options, $type): string
    {
        $fields = [];
        $firstInBlock = true;
        $previousHasDescription = false;
        $fieldDefinitions = $type->getFields();

        if (isset($options['sortFields']) && $options['sortFields']) {
            ksort($fieldDefinitions);
        }

        foreach ($fieldDefinitions as $f) {
            $hasDescription = $f->description !== null;
            if ($previousHasDescription && ! $hasDescription) {
                $fields[] = '';
            }

            $fields[] = static::printDescription($options, $f, '  ', $firstInBlock)
                . '  '
                . $f->name
                . static::printArgs($options, $f->args, '  ')
                . ': '
                . $f->getType()->toString()
                . static::printDeprecated($f);
            $firstInBlock = false;
            $previousHasDescription = $hasDescription;
        }

        return static::printBlock($fields);
    }

    /**
     * @param FieldDefinition|EnumValueDefinition|InputObjectField|Argument $deprecation
     *
     * @throws \JsonException
     * @throws InvariantViolation
     * @throws SerializationError
     */
    protected static function printDeprecated($deprecation): string
    {
        $reason = $deprecation->deprecationReason;
        if ($reason === null) {
            return '';
        }

        if ($reason === '' || $reason === Directive::DEFAULT_DEPRECATION_REASON) {
            return ' @deprecated';
        }

        $reasonAST = AST::astFromValue($reason, Type::string());
        assert($reasonAST instanceof StringValueNode);

        $reasonASTString = Printer::doPrint($reasonAST);

        return " @deprecated(reason: {$reasonASTString})";
    }

    protected static function printImplementedInterfaces(ImplementingType $type): string
    {
        $interfaces = $type->getInterfaces();

        return $interfaces === []
            ? ''
            : ' implements ' . implode(
                ' & ',
                array_map(
                    static fn (InterfaceType $interface): string => $interface->name,
                    $interfaces
                )
            );
    }

    /**
     * @param array<string, bool> $options
     *
     * @phpstan-param Options $options
     *
     * @throws \JsonException
     * @throws InvariantViolation
     * @throws SerializationError
     */
    protected static function printInterface(InterfaceType $type, array $options): string
    {
        return static::printDescription($options, $type)
            . "interface {$type->name}"
            . static::printImplementedInterfaces($type)
            . static::printFields($options, $type);
    }

    /**
     * @param array<string, bool> $options
     *
     * @phpstan-param Options $options
     *
     * @throws \JsonException
     * @throws InvariantViolation
     */
    protected static function printUnion(UnionType $type, array $options): string
    {
        $types = $type->getTypes();
        $types = $types === []
            ? ''
            : ' = ' . implode(' | ', $types);

        return static::printDescription($options, $type) . 'union ' . $type->name . $types;
    }

    /**
     * @param array<string, bool> $options
     *
     * @phpstan-param Options $options
     *
     * @throws \JsonException
     * @throws InvariantViolation
     * @throws SerializationError
     */
    protected static function printEnum(EnumType $type, array $options): string
    {
        $values = [];
        $firstInBlock = true;
        $valueDefinitions = $type->getValues();

        if (isset($options['sortEnumValues']) && $options['sortEnumValues']) {
            usort($valueDefinitions, static fn (EnumValueDefinition $left, EnumValueDefinition $right): int => $left->name <=> $right->name);
        }

        foreach ($valueDefinitions as $value) {
            $values[] = static::printDescription($options, $value, '  ', $firstInBlock)
                . '  '
                . $value->name
                . static::printDeprecated($value);
            $firstInBlock = false;
        }

        return static::printDescription($options, $type)
            . "enum {$type->name}"
            . static::printBlock($values);
    }

    /**
     * @param array<string, bool> $options
     *
     * @phpstan-param Options $options
     *
     * @throws \JsonException
     * @throws InvariantViolation
     * @throws SerializationError
     */
    protected static function printInputObject(InputObjectType $type, array $options): string
    {
        $fields = [];
        $firstInBlock = true;
        $fieldDefinitions = $type->getFields();

        if (isset($options['sortInputFields']) && $options['sortInputFields']) {
            ksort($fieldDefinitions);
        }

        foreach ($fieldDefinitions as $field) {
            $fields[] = static::printDescription($options, $field, '  ', $firstInBlock)
                . '  '
                . static::printInputValue($field);
            $firstInBlock = false;
        }

        return static::printDescription($options, $type)
            . "input {$type->name}"
            . ($type->isOneOf() ? ' @oneOf' : '')
            . static::printBlock($fields);
    }

    /** @param array<string> $items */
    protected static function printBlock(array $items): string
    {
        return $items === []
            ? ''
            : " {\n" . implode("\n", $items) . "\n}";
    }
}
