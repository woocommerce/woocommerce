<?php
/**
 * MultiCurrencyDomainMap class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

/**
 * B0 extraction boundary for the native multi-currency core domain.
 *
 * This map is intentionally static: it records the source module, interface
 * supply strategy, hard-preserved storage keys, hook families that require
 * mutual exclusion, and compatibility integrations that B1/B2 must preserve.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyDomainMap {

	/**
	 * WooPayments source module being extracted.
	 *
	 * @var string
	 */
	const SOURCE_MODULE = 'includes/multi-currency';

	/**
	 * Core namespace for the extracted domain.
	 *
	 * @var string
	 */
	const CORE_NAMESPACE = 'Automattic\\WooCommerce\\Internal\\MultiCurrency';

	/**
	 * Get interfaces that should have core-native default implementations.
	 *
	 * @return string[]
	 */
	public static function get_core_default_interfaces(): array {
		return array( 'cache', 'localization', 'settings' );
	}

	/**
	 * Get interfaces supplied through the provider rate seam.
	 *
	 * @return string[]
	 */
	public static function get_rate_provider_interfaces(): array {
		return array( 'account', 'api_client' );
	}

	/**
	 * Get hard-preserved multi-currency order meta keys.
	 *
	 * @return string[]
	 */
	public static function get_preserved_order_meta_keys(): array {
		return array(
			'_wcpay_multi_currency_stripe_exchange_rate',
			'_wcpay_multi_currency_order_exchange_rate',
			'_wcpay_multi_currency_order_default_currency',
		);
	}

	/**
	 * Get hard-preserved multi-currency option keys and dynamic key patterns.
	 *
	 * @return string[]
	 */
	public static function get_preserved_option_keys(): array {
		return array(
			'wcpay_multi_currency_enabled_currencies',
			'wcpay_multi_currency_store_currency',
			'wcpay_multi_currency_stored_customer_currencies',
			'wcpay_multi_currency_show_store_currency_changed_notice',
			'wcpay_multi_currency_enable_auto_currency',
			'wcpay_multi_currency_enable_storefront_switcher',
			'wcpay_multi_currency_rendering_mode',
			'wcpay_multi_currency_setup_completed',
			'wcpay_multi_currency_exchange_rate_{currency}',
			'wcpay_multi_currency_manual_rate_{currency}',
			'wcpay_multi_currency_price_rounding_{currency}',
			'wcpay_multi_currency_price_charm_{currency}',
		);
	}

	/**
	 * Get hard-preserved multi-currency session and user meta keys.
	 *
	 * @return string[]
	 */
	public static function get_preserved_session_and_user_keys(): array {
		return array(
			'wcpay_currency',
		);
	}

	/**
	 * Get price/currency pipeline hooks that must never be double-owned.
	 *
	 * @return string[]
	 */
	public static function get_price_pipeline_hooks(): array {
		return array(
			'woocommerce_currency',
			'wc_get_price_decimals',
			'wc_get_price_decimal_separator',
			'wc_get_price_thousand_separator',
			'woocommerce_price_format',
			'option_woocommerce_currency_pos',
			'wc_price',
			'woocommerce_format_sale_price',
			'woocommerce_format_price_range',
			'woocommerce_product_get_price',
			'woocommerce_product_get_regular_price',
			'woocommerce_product_get_sale_price',
			'woocommerce_product_variation_get_price',
			'woocommerce_product_variation_get_regular_price',
			'woocommerce_product_variation_get_sale_price',
			'woocommerce_variation_prices',
			'woocommerce_get_variation_prices_hash',
			'woocommerce_shipping_zone_shipping_methods',
			'woocommerce_shipping_method_add_rate_args',
			'woocommerce_coupon_get_amount',
			'woocommerce_coupon_get_minimum_amount',
			'woocommerce_coupon_get_maximum_amount',
			'before_woocommerce_pay',
			'woocommerce_order_get_total',
			'woocommerce_get_formatted_order_total',
			'woocommerce_thankyou_order_id',
			'woocommerce_account_view-order_endpoint',
			'woocommerce_cart_hash',
			'woocommerce_new_order',
			'woocommerce_order_refunded',
			'woocommerce_order_status_changed',
			'woocommerce_order_query',
			'rest_post_dispatch',
			'query_loop_block_query_vars',
		);
	}

	/**
	 * Get compatibility integrations from the plugin multi-currency module.
	 *
	 * @return string[]
	 */
	public static function get_compatibility_integrations(): array {
		return array(
			'WooCommerceBookings',
			'WooCommerceDeposits',
			'WooCommerceFedEx',
			'WooCommerceNameYourPrice',
			'WooCommercePointsAndRewards',
			'WooCommercePreOrders',
			'WooCommerceProductAddOns',
			'WooCommerceSubscriptions',
			'WooCommerceUPS',
		);
	}
}
