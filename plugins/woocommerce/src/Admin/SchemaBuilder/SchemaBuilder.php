<?php

namespace Automattic\WooCommerce\Admin\SchemaBuilder;

use Automattic\WooCommerce\Admin\SchemaBuilder\Types\SchemaString;

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

}