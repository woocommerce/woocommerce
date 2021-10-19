<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

/**
 * Products Task
 */
class Products {
	/**
	 * Get the task arguments.
	 *
	 * @return array
	 */
	public static function get_task() {
		return array(
			'id'          => 'products',
			'title'       => __( 'Add my products', 'woocommerce' ),
			'content'     => __(
				'Start by adding the first product to your store. You can add your products manually, via CSV, or import them from another service.',
				'woocommerce'
			),
			'is_complete' => self::has_products(),
			'can_view'    => true,
			'time'        => __( '1 minute per product', 'woocommerce' ),
		);
	}

	/**
	 * Check if the store has any published products.
	 *
	 * @return bool
	 */
	public static function has_products() {
		$product_query = new \WC_Product_Query(
			array(
				'limit'  => 1,
				'return' => 'ids',
				'status' => array( 'publish' ),
			)
		);
		$products      = $product_query->get_products();

		return 0 !== count( $products );
	}
}
