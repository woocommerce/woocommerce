<?php
/**
 * WooCommerce Product Usage.
 *
 * This class defines method to be used by Woo extensions to control product usage based on subscription status.
 *
 * @package WooCommerce\ProductUsage
 */

declare( strict_types = 1 );


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product usagee
 */
class WC_Product_Usage {
	/**
	 * Load Product Usage class.
	 *
	 * @since 9.3.0
	 */
	public static function load() {
		self::includes();
	}

	/**
	 * Include support files.
	 *
	 * @since 9.3.0
	 */
	protected static function includes() {
		require_once WC_ABSPATH . 'includes/product-usage/class-wc-product-usage-rule-set.php';
	}

	/**
	 * Get product usage rule if it needs to be applied to the given product id.
	 *
	 * @param int $product_id product id to get feature restriction rules.
	 * @since 9.3.0
	 */
	public static function get_rules_for_product( int $product_id ): ?WC_Product_Usage_Rule_Set {
		$rules = self::get_product_usage_restriction_rule( $product_id );
		if ( null === $rules ) {
			return null;
		}

		// When there is no subscription for the product, restrict usage.
		if ( ! WC_Helper::has_product_subscription( $product_id ) ) {
			return new WC_Product_Usage_Rule_Set( $rules );
		}

		$subscriptions = wp_list_filter( WC_Helper::get_installed_subscriptions(), array( 'product_id' => $product_id ) );
		if ( empty( $subscriptions ) ) {
			return new WC_Product_Usage_Rule_Set( $rules );
		}

		// Product should only have a single connected subscription on current store.
		$product_subscription = current( $subscriptions );
		if ( $product_subscription['expired'] ) {
			return new WC_Product_Usage_Rule_Set( $rules );
		}

		return null;
	}

	/**
	 * Get the product usage rule for a product.
	 *
	 * @param int $product_id product id to get feature restriction rules.
	 * @return array|null
	 * @since 9.3.0
	 */
	private static function get_product_usage_restriction_rule( int $product_id ): ?array {
		try {
			$rules = WC_Helper::get_product_usage_notice_rules();
		} catch ( Exception $e ) {
			return null;
		}

		if ( empty( $rules['restricted_products'][ $product_id ] ) ) {
			return null;
		}

		return $rules['restricted_products'][ $product_id ];
	}
}

WC_Product_Usage::load();
