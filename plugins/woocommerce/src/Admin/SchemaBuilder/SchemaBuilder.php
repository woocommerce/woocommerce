<?php

namespace Automattic\WooCommerce\Admin\SchemaBuilder;

use Automattic\WooCommerce\Admin\SchemaBuilder\Types\SchemaString;
use Automattic\WooCommerce\Admin\SchemaBuilder\Types\SchemaObject;
use Automattic\WooCommerce\Admin\SchemaBuilder\Types\SchemaNumber;
use Automattic\WooCommerce\Admin\SchemaBuilder\Types\SchemaArray;

/**
 * Schema Builder.
 */
class SchemaBuilder {

    /**
     * String type.
     */
    public static function string() {
        return new SchemaString();
    }

    /**
     * Number type.
     */
    public static function number() {
        return new SchemaNumber();
    }

    /**
     * Object type.
     */
    public static function object( $properties ) {
        return new SchemaObject( $properties );
    }

    /**
     * Array type.
     */
    public static function array( $properties ) {
        return new SchemaArray( $properties );
    }

}