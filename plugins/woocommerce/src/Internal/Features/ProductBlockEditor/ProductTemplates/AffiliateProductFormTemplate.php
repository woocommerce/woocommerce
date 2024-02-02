<?php

namespace Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\ProductFormTemplateInterface;

/**
 * Affiliate product form template.
 */
class AffiliateProductFormTemplate extends SimpleProductTemplate implements ProductFormTemplateInterface {
	/**
	 * Get the template ID.
	 */
	public function get_id(): string {
		return 'affiliate-product-form';
	}

	/**
	 * Get the template title.
	 */
	public function get_title(): string {
		return __( 'Affiliate product', 'woocommerce' );
	}

	/**
	 * Get the template description.
	 */
	public function get_description(): string {
		return __( 'A link to a product sold on a different website, e.g. brand collab.', 'woocommerce' );
	}

	/**
	 * Get the template icon.
	 */
	public function get_icon(): string {
		return 'link';
	}

	/**
	 * Get the template order.
	 */
	public function get_order(): int {
		return 30;
	}

	/**
	 * Get the product data.
	 */
	public function get_product_data(): array {
		return array(
			'type' => 'external',
		);
	}
}
