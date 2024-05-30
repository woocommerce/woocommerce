<?php

namespace Automattic\WooCommerce\Admin\SchemaBuilder\Types;

class SchemaString extends AbstractSchemaType {

    /**
     * Schema type.
     *
     * @return string
     */
    public function get_type() {
        return 'string';
    }

}