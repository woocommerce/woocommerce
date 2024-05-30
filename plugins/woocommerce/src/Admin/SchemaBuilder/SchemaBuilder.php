<?php

namespace Automattic\WooCommerce\Admin\SchemaBuilder;

use Automattic\WooCommerce\Admin\SchemaBuilder\Types\SchemaString;
use Automattic\WooCommerce\Admin\SchemaBuilder\Types\SchemaObject;

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
     * String type.
     */
    public static function object( $properties ) {
        return new SchemaObject( $properties );
    }

}