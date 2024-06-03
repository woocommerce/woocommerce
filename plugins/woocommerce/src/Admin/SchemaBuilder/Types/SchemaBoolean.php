<?php

namespace Automattic\WooCommerce\Admin\SchemaBuilder\Types;

use Automattic\WooCommerce\Admin\SchemaBuilder\Exceptions\IncompatibleSchemaFormatException;

class SchemaBoolean extends AbstractSchemaType {

    /**
     * Schema type.
     *
     * @return string
     */
    public function get_type() {
        return 'boolean';
    }

}