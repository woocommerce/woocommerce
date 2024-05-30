<?php

namespace Automattic\WooCommerce\Admin\SchemaBuilder\Types;

/**
 * Interface SchemaTypeInterface
 *
 * @since 8.5.0
 */
interface SchemaTypeInterface {

    /**
     * Get the schema type.
     *
     * @return string
     */
	public function get_type();

    /**
     * Set a description.
     *
     * @param string $description Description.
     * @return SchemaTypeInterface
     */
	public function description();

    /**
     * Get the JSON.
     *
     * @return array
     */
	public function get_json();

}
