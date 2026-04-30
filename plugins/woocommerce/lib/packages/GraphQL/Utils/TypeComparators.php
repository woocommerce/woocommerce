<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Utils;

use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ImplementingType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ListOfType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\NonNull;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;

class TypeComparators
{
    /** Provided two types, return true if the types are equal (invariant). */
    public static function isEqualType(Type $typeA, Type $typeB): bool
    {
        // Equivalent types are equal.
        if ($typeA === $typeB) {
            return true;
        }

        if (self::areSameBuiltInScalar($typeA, $typeB)) {
            return true;
        }

        // If either type is non-null, the other must also be non-null.
        if ($typeA instanceof NonNull && $typeB instanceof NonNull) {
            return self::isEqualType($typeA->getWrappedType(), $typeB->getWrappedType());
        }

        // If either type is a list, the other must also be a list.
        if ($typeA instanceof ListOfType && $typeB instanceof ListOfType) {
            return self::isEqualType($typeA->getWrappedType(), $typeB->getWrappedType());
        }

        // Otherwise the types are not equal.
        return false;
    }

    /**
     * Provided a type and a super type, return true if the first type is either
     * equal or a subset of the second super type (covariant).
     *
     * @throws InvariantViolation
     */
    public static function isTypeSubTypeOf(Schema $schema, Type $maybeSubType, Type $superType): bool
    {
        // Equivalent type is a valid subtype
        if ($maybeSubType === $superType) {
            return true;
        }

        if (self::areSameBuiltInScalar($maybeSubType, $superType)) {
            return true;
        }

        // If superType is non-null, maybeSubType must also be nullable.
        if ($superType instanceof NonNull) {
            if ($maybeSubType instanceof NonNull) {
                return self::isTypeSubTypeOf($schema, $maybeSubType->getWrappedType(), $superType->getWrappedType());
            }

            return false;
        }

        if ($maybeSubType instanceof NonNull) {
            // If superType is nullable, maybeSubType may be non-null.
            return self::isTypeSubTypeOf($schema, $maybeSubType->getWrappedType(), $superType);
        }

        // If superType type is a list, maybeSubType type must also be a list.
        if ($superType instanceof ListOfType) {
            if ($maybeSubType instanceof ListOfType) {
                return self::isTypeSubTypeOf($schema, $maybeSubType->getWrappedType(), $superType->getWrappedType());
            }

            return false;
        }

        if ($maybeSubType instanceof ListOfType) {
            // If superType is not a list, maybeSubType must also be not a list.
            return false;
        }

        if (Type::isAbstractType($superType)) {
            // If superType type is an abstract type, maybeSubType type may be a currently
            // possible object or interface type.

            return $maybeSubType instanceof ImplementingType
                && $schema->isSubType($superType, $maybeSubType);
        }

        return false;
    }

    /**
     * Built-in scalars may exist as different instances when a type loader
     * overrides them. Compare by name to handle this case.
     */
    private static function areSameBuiltInScalar(Type $typeA, Type $typeB): bool
    {
        return Type::isBuiltInScalar($typeA)
            && Type::isBuiltInScalar($typeB)
            && $typeA->name() === $typeB->name();
    }
}
