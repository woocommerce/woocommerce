<?php

namespace Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\ProductFormTemplateInterface;

/**
 * Standard product form template.
 */
class StandardProductFormTemplate extends SimpleProductTemplate implements ProductFormTemplateInterface {
	/**
	 * Get the template ID.
	 */
	public function get_id(): string {
		return 'standard-product-form';
	}

	/**
	 * Get the template title.
	 */
	public function get_title(): string {
		return __( 'Standard product', 'woocommerce' );
	}

	/**
	 * Get the template description.
	 */
	public function get_description(): string {
		return __( 'A single physical or virtual product, e.g. a t-shirt or an eBook.', 'woocommerce' );
	}

	/**
	 * Get the template icon.
	 */
	public function get_icon(): string {
		return 'shipping';
	}

	/**
	 * Get the template order.
	 */
	public function get_order(): int {
		return 10;
	}

	/**
	 * Get the product data.
	 */
	public function get_product_data(): array {
		return array(
			'type' => 'simple',
		);
	}
}
