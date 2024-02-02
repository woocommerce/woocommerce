<?php

namespace Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\ProductFormTemplateInterface;

/**
 * Grouped product form template.
 */
class GroupedProductFormTemplate extends SimpleProductTemplate implements ProductFormTemplateInterface {
	/**
	 * Get the template ID.
	 */
	public function get_id(): string {
		return 'grouped-product-form';
	}

	/**
	 * Get the template title.
	 */
	public function get_title(): string {
		return __( 'Grouped product', 'woocommerce' );
	}

	/**
	 * Get the template description.
	 */
	public function get_description(): string {
		return __( 'A set of products that go well together, e.g. camera kit.', 'woocommerce' );
	}

	/**
	 * Get the template icon.
	 */
	public function get_icon(): string {
		return 'group';
	}

	/**
	 * Get the template order.
	 */
	public function get_order(): int {
		return 20;
	}

	/**
	 * Get the product data.
	 */
	public function get_product_data(): array {
		return array(
			'type' => 'grouped',
		);
	}
}
