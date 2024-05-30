<?php

namespace Automattic\WooCommerce\Admin\SchemaBuilder\Types;

/**
 * Interface SchemaTypeInterface
 *
 * @since 8.5.0
 */
interface SchemaTypeInterface {

    /**
     * Set a description.
     *
     * @param string $description Description.
     * @return SchemaTypeInterface
     */
	public function description();

}
