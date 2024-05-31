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
        $properties = array();
        $required   = array();
        foreach( $this->properties as $property_name => $property ) {
            $properties[] = $property->get_json();
            if ( $property->is_required() ) {
                $required[] = $property_name;
            }
        }
        $json               = parent::get_json();
        $json['title']      = $this->title;
        $json['properties'] = $properties;
        if ( count( $required ) ) {
            $json['required'] = $required;
        }
        return $json;
    }

}