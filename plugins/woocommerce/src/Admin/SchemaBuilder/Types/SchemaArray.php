<?php

namespace Automattic\WooCommerce\Admin\SchemaBuilder\Types;

class SchemaArray extends AbstractSchemaType {

    /**
     * Items.
     *
     * @var SchemaTypeInterface[]
     */
    private $items = [];

    /**
     * Constructor.
     *
     * @param SchemaTypeInterface[]
     */
    public function __construct( $items ) {
        $this->items = $items;
    }

    /**
     * Schema type.
     *
     * @return string
     */
    public function get_type() {
        return 'array';
    }

    /**
     * Get the JSON.
     *
     * @return array
     */
    public function get_json() {
        $json          = parent::get_json();
        $json['items'] = array_map(
            function ( $item ) {
                return $item->get_json();
            },
            $this->items
        );
        return $json;
    }

}