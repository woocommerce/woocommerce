<?php

namespace Automattic\WooCommerce\Admin\SchemaBuilder\Types;

abstract class AbstractSchemaType {

    /**
     * Format.
     *
     * @var string|null
     */
    protected $format = null;

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

}