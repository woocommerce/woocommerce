<?php

namespace Automattic\WooCommerce\Admin\SchemaBuilder\Types;

class SchemaObject extends AbstractSchemaType {

    /**
     * Properties.
     *
     * @var SchemaTypeInterface[]
     */
    private $properties = [];

    /**
     * Title.
     *
     * @var string|null
     */
    private $title = null;

    /**
     * Constructor.
     *
     * @param SchemaTypeInterface[]
     */
    public function __construct( $properties ) {
        $this->properties = $properties;
    }

    /**
     * Schema type.
     *
     * @return string
     */
    public function get_type() {
        return 'object';
    }

    /**
     * Get the JSON.
     *
     * @return array
     */
    public function get_json() {
        $json               = parent::get_json();
        $json['title']      = $this->title;
        $json['properties'] = array_map(
            function ( $item ) {
                return $item->get_json();
            },
            $this->properties
        );
        return $json;
    }

}