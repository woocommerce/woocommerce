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
 * Product usage.
 */
class WC_Product_Usage {
	private const RULE_FEATURE_RESTRICTIONS_ENABLED    = 'feature_restrictions_enabled';
	private const RULE_GRACE_PERIOD_AFTER_EXPIRY       = 'grace_period_after_expiry';
	private const RULE_RESTRICT_IF_NOT_CONNECTED       = 'restrict_if_not_connected';
	private const RULE_RESTRICT_IF_NO_SUBSCRIPTION     = 'restrict_if_no_subscription';

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

		if ( 0 === $rules[ self::RULE_FEATURE_RESTRICTIONS_ENABLED ] ) {
			return null;
		}

		if ( 1 === $rules[ self::RULE_RESTRICT_IF_NO_SUBSCRIPTION ] && ! WC_Helper::has_product_subscription( $product_id ) ) {
			// When there is no subscription for the product, restrict usage.
			return new WC_Product_Usage_Rule_Set( $rules );
		}

		$subscriptions = wp_list_filter( WC_Helper::get_installed_subscriptions(), array( 'product_id' => $product_id ) );
		if ( empty( $subscriptions ) && 0 === $rules[ self::RULE_RESTRICT_IF_NOT_CONNECTED ] ) {
			// No connected product subscription and rule mentions not to restrict products that are not connected.
			return null;
		}

		// Product should only have a single connected subscription on current store.
		$product_subscription = current( $subscriptions );

		if ( $product_subscription['expired'] ) {
			if ( 0 === $rules[ self::RULE_GRACE_PERIOD_AFTER_EXPIRY ] ) {
				return new WC_Product_Usage_Rule_Set( $rules );
			}

			// If the subscription is within the grace period, even if it's expired, avoid returning rule set.
			if ( (int) $product_subscription['expired'] - strtotime( 'now' ) > DAY_IN_SECONDS * $rules[ self::RULE_GRACE_PERIOD_AFTER_EXPIRY ] ) {
				return null;
			}

			return new WC_Product_Usage_Rule_Set( $rules );
		}

		return new WC_Product_Usage_Rule_Set( $rules );
	}

	/**
	 * Get the product usage rule for a product.
	 *
	 * @param int $product_id product id to get feature restriction rules.
	 * @return array|null
	 * @since 9.3.0
	 */
	private static function get_product_usage_restriction_rule( int $product_id ): ?array {
		$restrictions = WC_Helper::get_product_feature_restrictions();

		return $restrictions[ $product_id ] ?? null;
	}
}

WC_Product_Usage::load();
