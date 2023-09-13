<?php
/**
 * VariableProductTemplate
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\Groups\General;

/**
 * Variable Product Template.
 */
class VariableProductTemplate extends AbstractProductFormTemplate implements ProductFormTemplateInterface {
    use General;

	/**
	 * VariableProductTemplate constructor.
	 */
	public function __construct() {
		$this->add_general_group();
		$this->add_general_blocks();
	}

	/**
	 * Get the template ID.
	 */
	public function get_id(): string {
		return 'variable-product';
	}

	/**
	 * Get the template title.
	 */
	public function get_title(): string {
		return __( 'Variable Product Template', 'woocommerce' );
	}

	/**
	 * Get the template description.
	 */
	public function get_description(): string {
		return __( 'Template for the variable product form', 'woocommerce' );
	}

}
