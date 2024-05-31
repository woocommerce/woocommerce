<?php

namespace Automattic\WooCommerce\Admin\SchemaBuilder\Types;

use Automattic\WooCommerce\Admin\SchemaBuilder\Types\SchemaReference;

abstract class AbstractSchemaType {

    /**
     * Format.
     *
     * @var string|null
     */
    protected $format = null;

    /**
     * Required.
     *
     * @var bool
     */
    protected $required = false;

    /**
     * Description.
     *
     * @var string|null
     */
    protected $description = null;

    /**
     * Schema type.
     *
     * @return string
     */
    abstract function get_type();

    /**
     * Set a description.
     *
     * @param string $description Description.
     * @return SchemaTypeInterface
     */
    public function description( $description ) {
        $this->description = $description;
        return $this;
    }

    /**
     * Set the property as required.
     *
     * @return SchemaTypeInterface
     */
    public function required() {
        $this->required = true;
        return $this;
    }

    /**
     * Get the JSON.
     */
    public function get_json() {
        $json = array(
            'type'        => $this->get_type(),
        );

        if ( $this->description ) {
            $json['description'] = $this->description;
        }

        if ( $this->format ) {
            $json['format'] = $this->format;
        }

        return $json;
    }

    /**
     * Check if the property is required.
     */
    public function is_required() {
        return $this->required;
    }

    /**
     * Parse an input value.
     */
    public function parse_value( $input ) {
        if ( is_a( $input, SchemaReference::class ) ) {
            return $input->get_json();
        }

        return $input;
    }

}