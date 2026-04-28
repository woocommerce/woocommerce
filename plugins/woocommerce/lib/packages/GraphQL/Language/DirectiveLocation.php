<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language;

/**
 * Enumeration of available directive locations.
 */
class DirectiveLocation
{
    public const QUERY = 'QUERY';
    public const MUTATION = 'MUTATION';
    public const SUBSCRIPTION = 'SUBSCRIPTION';
    public const FIELD = 'FIELD';
    public const FRAGMENT_DEFINITION = 'FRAGMENT_DEFINITION';
    public const FRAGMENT_SPREAD = 'FRAGMENT_SPREAD';
    public const INLINE_FRAGMENT = 'INLINE_FRAGMENT';
    public const VARIABLE_DEFINITION = 'VARIABLE_DEFINITION';

    public const EXECUTABLE_LOCATIONS = [
        self::QUERY => self::QUERY,
        self::MUTATION => self::MUTATION,
        self::SUBSCRIPTION => self::SUBSCRIPTION,
        self::FIELD => self::FIELD,
        self::FRAGMENT_DEFINITION => self::FRAGMENT_DEFINITION,
        self::FRAGMENT_SPREAD => self::FRAGMENT_SPREAD,
        self::INLINE_FRAGMENT => self::INLINE_FRAGMENT,
        self::VARIABLE_DEFINITION => self::VARIABLE_DEFINITION,
    ];

    public const SCHEMA = 'SCHEMA';
    public const SCALAR = 'SCALAR';
    public const OBJECT = 'OBJECT';
    public const FIELD_DEFINITION = 'FIELD_DEFINITION';
    public const ARGUMENT_DEFINITION = 'ARGUMENT_DEFINITION';
    public const IFACE = 'INTERFACE';
    public const UNION = 'UNION';
    public const ENUM = 'ENUM';
    public const ENUM_VALUE = 'ENUM_VALUE';
    public const INPUT_OBJECT = 'INPUT_OBJECT';
    public const INPUT_FIELD_DEFINITION = 'INPUT_FIELD_DEFINITION';

    public const TYPE_SYSTEM_LOCATIONS = [
        self::SCHEMA => self::SCHEMA,
        self::SCALAR => self::SCALAR,
        self::OBJECT => self::OBJECT,
        self::FIELD_DEFINITION => self::FIELD_DEFINITION,
        self::ARGUMENT_DEFINITION => self::ARGUMENT_DEFINITION,
        self::IFACE => self::IFACE,
        self::UNION => self::UNION,
        self::ENUM => self::ENUM,
        self::ENUM_VALUE => self::ENUM_VALUE,
        self::INPUT_OBJECT => self::INPUT_OBJECT,
        self::INPUT_FIELD_DEFINITION => self::INPUT_FIELD_DEFINITION,
    ];

    public const LOCATIONS = self::EXECUTABLE_LOCATIONS + self::TYPE_SYSTEM_LOCATIONS;

    public static function has(string $name): bool
    {
        return isset(self::LOCATIONS[$name]);
    }
}
