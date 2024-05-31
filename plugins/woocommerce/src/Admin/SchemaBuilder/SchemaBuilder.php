<?php

namespace Automattic\WooCommerce\Admin\SchemaBuilder;

use Automattic\WooCommerce\Admin\SchemaBuilder\Types\SchemaString;
use Automattic\WooCommerce\Admin\SchemaBuilder\Types\SchemaObject;
use Automattic\WooCommerce\Admin\SchemaBuilder\Types\SchemaNumber;
use Automattic\WooCommerce\Admin\SchemaBuilder\Types\SchemaArray;
use Automattic\WooCommerce\Admin\SchemaBuilder\Types\SchemaBoolean;
use Automattic\WooCommerce\Admin\SchemaBuilder\Types\SchemaReference;

/**
 * Schema Builder.
 */
class SchemaBuilder {

    /**
     * String type.
     *
     * @return SchemaString
     */
    public static function string() {
        return new SchemaString();
    }

    /**
     * Number type.
     *
     * @return SchemaNumber
     */
    public static function number() {
        return new SchemaNumber();
    }

    /**
     * Object type.
     *
     * @param array $properties Properties.
     * @return SchemaObject
     */
    public static function object( $properties ) {
        return new SchemaObject( $properties );
    }

    /**
     * Array type.
     *
     * @param array $items Items.
     * @return SchemaArray
     */
    public static function array( $items ) {
        return new SchemaArray( $items );
    }

    /**
     * Boolean type.
     *
     * @return SchemaBoolean
     */
    public static function boolean() {
        return new SchemaBoolean();
    }

    /**
     * Reference type.
     *
     * @param string $pointer Pointer.
     * @return SchemaReference
     */
    public static function reference( $pointer ) {
        return new SchemaReference( $pointer );
    }

}