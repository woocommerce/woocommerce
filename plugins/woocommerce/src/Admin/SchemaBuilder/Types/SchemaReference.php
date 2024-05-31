<?php

namespace Automattic\WooCommerce\Admin\SchemaBuilder\Types;

class SchemaReference {

    /**
     * Pointer.
     *
     * @var string
     */
    private $pointer;

    /**
     * Constructor.
     *
     * @param SchemaTypeInterface[]
     */
    public function __construct( $pointer ) {
        $this->pointer = $pointer;
    }

    /**
     * Get the JSON.
     *
     * @return array
     */
    public function get_json() {
        return array(
            '$data' => $this->pointer,
        );
    }

}