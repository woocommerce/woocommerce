<?php

namespace Automattic\WooCommerce\Admin\SchemaBuilder\Types;

abstract class AbstractSchemaType {

    /**
     * Description.
     *
     * @var string|null
     */
    private $description = null;

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
        return array(
            'type'        => $this->get_type(),
            'description' => $this->description,
        );
    }

}