<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Introspection;
use Automattic\WooCommerce\Vendor\GraphQL\Type\SchemaConfig;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

/**
 * Registry of built-in Automattic\WooCommerce\Vendor\GraphQL types and base class for all other types.
 */
abstract class Type implements \JsonSerializable
{
    public const INT = 'Int';
    public const FLOAT = 'Float';
    public const STRING = 'String';
    public const BOOLEAN = 'Boolean';
    public const ID = 'ID';

    /** @var list<string> */
    public const BUILT_IN_SCALAR_NAMES = [
        self::INT,
        self::FLOAT,
        self::STRING,
        self::BOOLEAN,
        self::ID,
    ];

    /**
     * @deprecated use {@see Type::BUILT_IN_SCALAR_NAMES}
     *
     * @var list<string>
     */
    public const STANDARD_TYPE_NAMES = self::BUILT_IN_SCALAR_NAMES;

    /**
     * Names of all built-in types: built-in scalars and introspection types.
     *
     * @see Type::BUILT_IN_SCALAR_NAMES for just the built-in scalar names.
     *
     * @var list<string>
     */
    public const BUILT_IN_TYPE_NAMES = [
        ...self::BUILT_IN_SCALAR_NAMES,
        ...Introspection::TYPE_NAMES,
    ];

    /** @var array<string, ScalarType>|null */
    protected static ?array $builtInScalars;

    /** @var array<string, Type&NamedType>|null */
    protected static ?array $builtInTypes;

    /**
     * Returns the built-in Int scalar type.
     *
     * @api
     */
    public static function int(): ScalarType
    {
        return static::$builtInScalars[self::INT] ??= new IntType(); // @phpstan-ignore missingType.checkedException (static configuration is known to be correct)
    }

    /**
     * Returns the built-in Float scalar type.
     *
     * @api
     */
    public static function float(): ScalarType
    {
        return static::$builtInScalars[self::FLOAT] ??= new FloatType(); // @phpstan-ignore missingType.checkedException (static configuration is known to be correct)
    }

    /**
     * Returns the built-in String scalar type.
     *
     * @api
     */
    public static function string(): ScalarType
    {
        return static::$builtInScalars[self::STRING] ??= new StringType(); // @phpstan-ignore missingType.checkedException (static configuration is known to be correct)
    }

    /**
     * Returns the built-in Boolean scalar type.
     *
     * @api
     */
    public static function boolean(): ScalarType
    {
        return static::$builtInScalars[self::BOOLEAN] ??= new BooleanType(); // @phpstan-ignore missingType.checkedException (static configuration is known to be correct)
    }

    /**
     * Returns the built-in ID scalar type.
     *
     * @api
     */
    public static function id(): ScalarType
    {
        return static::$builtInScalars[self::ID] ??= new IDType(); // @phpstan-ignore missingType.checkedException (static configuration is known to be correct)
    }

    /**
     * Wraps the given type in a list type.
     *
     * @template T of Type
     *
     * @param T|callable():T $type
     *
     * @return ListOfType<T>
     *
     * @api
     */
    public static function listOf($type): ListOfType
    {
        return new ListOfType($type);
    }

    /**
     * Wraps the given type in a non-null type.
     *
     * @param NonNull|(NullableType&Type)|callable():(NullableType&Type) $type
     *
     * @api
     */
    public static function nonNull($type): NonNull
    {
        if ($type instanceof NonNull) {
            return $type;
        }

        return new NonNull($type);
    }

    /**
     * Returns all built-in types: built-in scalars and introspection types.
     *
     * @api
     *
     * @return array<string, Type&NamedType>
     */
    public static function builtInTypes(): array
    {
        return self::$builtInTypes ??= array_merge(
            Introspection::getTypes(),
            self::builtInScalars()
        );
    }

    /**
     * Returns all built-in scalar types.
     *
     * @api
     *
     * @return array<string, ScalarType>
     */
    public static function builtInScalars(): array
    {
        return [
            self::INT => static::int(),
            self::FLOAT => static::float(),
            self::STRING => static::string(),
            self::BOOLEAN => static::boolean(),
            self::ID => static::id(),
        ];
    }

    /**
     * Returns all built-in scalar types.
     *
     * @deprecated use {@see Type::builtInScalars()}
     *
     * @return array<string, ScalarType>
     */
    public static function getStandardTypes(): array
    {
        return self::builtInScalars();
    }

    /**
     * Allows partially or completely overriding the standard types globally.
     *
     * @deprecated prefer per-schema scalar overrides via {@see SchemaConfig::$types} or {@see SchemaConfig::$typeLoader}
     *
     * @param array<ScalarType> $types
     *
     * @throws InvariantViolation
     */
    public static function overrideStandardTypes(array $types): void
    {
        // Reset caches that might contain instances of built-in scalars
        static::$builtInTypes = null;
        Introspection::resetCachedInstances();
        Directive::resetCachedInstances();

        foreach ($types as $type) {
            // @phpstan-ignore-next-line generic type is not enforced by PHP
            if (! $type instanceof ScalarType) {
                $typeClass = ScalarType::class;
                $notType = Utils::printSafe($type);
                throw new InvariantViolation("Expecting instance of {$typeClass}, got {$notType}");
            }

            if (! self::isBuiltInScalarName($type->name)) {
                $standardTypeNames = implode(', ', self::BUILT_IN_SCALAR_NAMES);
                $notStandardTypeName = Utils::printSafe($type->name);
                throw new InvariantViolation("Expecting one of the following names for a standard type: {$standardTypeNames}; got {$notStandardTypeName}");
            }

            static::$builtInScalars[$type->name] = $type;
        }
    }

    /**
     * Determines if the given type is a built-in scalar (Int, Float, String, Boolean, ID).
     *
     * Does not unwrap NonNull/List wrappers — checks the type instance directly.
     * ScalarType is a NamedType, so {@see Type::getNamedType()} is unnecessary.
     *
     * @param mixed $type
     *
     * @phpstan-assert-if-true ScalarType $type
     *
     * @api
     */
    public static function isBuiltInScalar($type): bool
    {
        return $type instanceof ScalarType
            && self::isBuiltInScalarName($type->name);
    }

    /** Checks if the given name is one of the built-in scalar type names (ID, String, Int, Float, Boolean). */
    public static function isBuiltInScalarName(string $name): bool
    {
        return in_array($name, self::BUILT_IN_SCALAR_NAMES, true);
    }

    /**
     * Determines if the given type is an input type.
     *
     * @param mixed $type
     *
     * @api
     */
    public static function isInputType($type): bool
    {
        return self::getNamedType($type) instanceof InputType;
    }

    /**
     * Returns the underlying named type of the given type.
     *
     * @return (Type&NamedType)|null
     *
     * @phpstan-return ($type is null ? null : Type&NamedType)
     *
     * @api
     */
    public static function getNamedType(?Type $type): ?Type
    {
        if ($type instanceof WrappingType) {
            return $type->getInnermostType();
        }

        assert($type === null || $type instanceof NamedType, 'only other option');

        return $type;
    }

    /**
     * Determines if the given type is an output type.
     *
     * @param mixed $type
     *
     * @api
     */
    public static function isOutputType($type): bool
    {
        return self::getNamedType($type) instanceof OutputType;
    }

    /**
     * Determines if the given type is a leaf type.
     *
     * @param mixed $type
     *
     * @api
     */
    public static function isLeafType($type): bool
    {
        return $type instanceof LeafType;
    }

    /**
     * Determines if the given type is a composite type.
     *
     * @param mixed $type
     *
     * @api
     */
    public static function isCompositeType($type): bool
    {
        return $type instanceof CompositeType;
    }

    /**
     * Determines if the given type is an abstract type.
     *
     * @param mixed $type
     *
     * @api
     */
    public static function isAbstractType($type): bool
    {
        return $type instanceof AbstractType;
    }

    /**
     * Unwraps a potentially non-null type to return the underlying nullable type.
     *
     * @return Type&NullableType
     *
     * @api
     */
    public static function getNullableType(Type $type): Type
    {
        if ($type instanceof NonNull) {
            return $type->getWrappedType();
        }

        assert($type instanceof NullableType, 'only other option');

        return $type;
    }

    abstract public function toString(): string;

    public function __toString(): string
    {
        return $this->toString();
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): string
    {
        return $this->toString();
    }
}
