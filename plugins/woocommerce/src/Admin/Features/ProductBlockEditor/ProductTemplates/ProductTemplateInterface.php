<?php
/**
 * Product Block Editor product template interface.
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

/**
 * Product Block Editor product template interface.
 */
interface ProductTemplateInterface {

	/**
	 * Get the name of the template.
	 *
	 * @return string Template name
	 */
	public function get_name();

	/**
	 * Get the template layout.
	 *
	 * @return array Array of blocks
	 */
	public function get_template();

}
