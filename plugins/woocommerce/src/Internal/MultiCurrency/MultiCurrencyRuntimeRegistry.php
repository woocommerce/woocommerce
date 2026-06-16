<?php
/**
 * MultiCurrencyRuntimeRegistry class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyAdminNoteProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyAdminNoticeProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyAsyncPriceProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyCompatibilityProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRestProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySettingsProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStorefrontProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySubscriptionsCompatibilityProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyTrackingProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyUserSettingsProjectionService;

/**
 * Coordinates native multi-currency registration metadata behind the runtime arbiter.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyRuntimeRegistry {

	private const BLOCKER_PLUGIN_MULTI_CURRENCY_ACTIVE = 'plugin_multi_currency_active';
	private const BLOCKER_NO_MULTI_CURRENCY_OWNER      = 'no_multi_currency_owner';
	private const BLOCKER_CORE_NOT_OWNER               = 'core_multi_currency_not_owner';

	/**
	 * Runtime owner arbiter.
	 *
	 * @var MultiCurrencyRuntimeArbiter
	 */
	private MultiCurrencyRuntimeArbiter $arbiter;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyRuntimeArbiter $arbiter Runtime owner arbiter.
	 */
	final public function init( MultiCurrencyRuntimeArbiter $arbiter ): void {
		$this->arbiter = $arbiter;
	}

	/**
	 * Get the native multi-currency registration manifest for the current site.
	 *
	 * @return array<string,mixed>
	 */
	public function get_registration_manifest(): array {
		$owner           = $this->arbiter->get_runtime_owner();
		$should_register = $this->arbiter->should_core_register();

		return array(
			'owner'           => $owner,
			'should_register' => $should_register,
			'blockers'        => $should_register ? array() : self::get_registration_blockers( $owner ),
			'hook_groups'     => $should_register ? self::get_core_hook_groups() : array(),
		);
	}

	/**
	 * Get core-owned multi-currency hook groups for later live registration.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function get_core_hook_groups(): array {
		return array(
			'frontend_prices'             => self::get_frontend_price_hook_group(),
			'frontend_currencies'         => self::get_frontend_currency_hook_group(),
			'store_currency_lifecycle'    => self::get_store_currency_lifecycle_hook_group(),
			'selected_currency'           => self::get_selected_currency_hook_group(),
			'analytics'                   => self::get_analytics_hook_group(),
			'compatibility'               => MultiCurrencyCompatibilityProjectionService::get_hook_manifest(),
			'subscriptions_compatibility' => MultiCurrencySubscriptionsCompatibilityProjectionService::get_hook_manifest(),
			'async_prices'                => MultiCurrencyAsyncPriceProjectionService::get_hook_manifest( true, false, false, false, false ),
			'storefront'                  => MultiCurrencyStorefrontProjectionService::get_hook_manifest( 2, true ),
			'settings'                    => MultiCurrencySettingsProjectionService::get_hook_manifest(),
			'rest'                        => MultiCurrencyRestProjectionService::get_route_manifest( true ),
			'rest_request_overrides'      => self::get_rest_request_override_hook_group(),
			'user_settings'               => MultiCurrencyUserSettingsProjectionService::get_hook_manifest( 2 ),
			'admin_notices'               => MultiCurrencyAdminNoticeProjectionService::get_hook_manifest(),
			'admin_notes'                 => MultiCurrencyAdminNoteProjectionService::get_hook_manifest( true ),
			'tracking'                    => self::get_tracking_hook_group(),
		);
	}

	/**
	 * Get blockers for the current owner.
	 *
	 * @param string $owner Runtime owner.
	 * @return string[]
	 */
	private static function get_registration_blockers( string $owner ): array {
		if ( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN === $owner ) {
			return array( self::BLOCKER_PLUGIN_MULTI_CURRENCY_ACTIVE );
		}

		if ( MultiCurrencyRuntimeArbiter::OWNER_NONE === $owner ) {
			return array( self::BLOCKER_NO_MULTI_CURRENCY_OWNER );
		}

		return array( self::BLOCKER_CORE_NOT_OWNER );
	}

	/**
	 * Get the preserved store-currency lifecycle hook metadata.
	 *
	 * @return array{filters: array<int,array<string,mixed>>, actions: array<int,array<string,mixed>>}
	 */
	private static function get_store_currency_lifecycle_hook_group(): array {
		return array(
			'filters' => array(),
			'actions' => array(
				self::hook_entry( 'init', 'sync_store_currency', 10 ),
			),
		);
	}

	/**
	 * Get the preserved frontend price hook metadata.
	 *
	 * @return array{filters: array<int,array<string,mixed>>, actions: array<int,array<string,mixed>>}
	 */
	private static function get_frontend_price_hook_group(): array {
		return array(
			'filters' => array(
				self::hook_entry( 'woocommerce_product_get_price', 'get_product_price_string', 99, 2 ),
				self::hook_entry( 'woocommerce_product_get_regular_price', 'get_product_price_string', 99, 2 ),
				self::hook_entry( 'woocommerce_product_get_sale_price', 'get_product_price_string', 99, 2 ),
				self::hook_entry( 'woocommerce_product_variation_get_price', 'get_product_price_string', 99, 2 ),
				self::hook_entry( 'woocommerce_product_variation_get_regular_price', 'get_product_price_string', 99, 2 ),
				self::hook_entry( 'woocommerce_product_variation_get_sale_price', 'get_product_price_string', 99, 2 ),
				self::hook_entry( 'woocommerce_variation_prices', 'get_variation_price_range', 99 ),
				self::hook_entry( 'woocommerce_get_variation_prices_hash', 'add_exchange_rate_to_variation_prices_hash', 99 ),
				self::hook_entry( 'woocommerce_shipping_zone_shipping_methods', 'convert_free_shipping_method_min_amount', 99 ),
				self::hook_entry( 'woocommerce_shipping_method_add_rate_args', 'convert_shipping_method_rate_cost', 99 ),
				self::hook_entry( 'woocommerce_coupon_get_amount', 'get_coupon_amount', 99, 2 ),
				self::hook_entry( 'woocommerce_coupon_get_minimum_amount', 'get_coupon_min_max_amount', 99 ),
				self::hook_entry( 'woocommerce_coupon_get_maximum_amount', 'get_coupon_min_max_amount', 99 ),
				self::hook_entry( 'woocommerce_new_order', 'add_order_meta', 99, 2 ),
				self::hook_entry( 'rest_post_dispatch', 'maybe_modify_price_ranges_rest_response', 10, 3 ),
				self::hook_entry( 'query_loop_block_query_vars', 'maybe_modify_price_ranges_query_var', 10, 3 ),
			),
			'actions' => array(),
		);
	}

	/**
	 * Get the preserved frontend currency hook metadata.
	 *
	 * @return array{filters: array<int,array<string,mixed>>, actions: array<int,array<string,mixed>>}
	 */
	private static function get_frontend_currency_hook_group(): array {
		return array(
			'filters' => array(
				self::hook_entry( 'woocommerce_currency', 'get_woocommerce_currency', 900 ),
				self::hook_entry( 'wc_get_price_decimals', 'get_price_decimals', 900 ),
				self::hook_entry( 'wc_get_price_decimal_separator', 'get_price_decimal_separator', 900 ),
				self::hook_entry( 'wc_get_price_thousand_separator', 'get_price_thousand_separator', 900 ),
				self::hook_entry( 'woocommerce_price_format', 'get_woocommerce_price_format', 900 ),
				self::hook_entry( 'option_woocommerce_currency_pos', 'get_woocommerce_currency_pos', 900 ),
				self::hook_entry( 'woocommerce_order_get_total', 'maybe_init_order_currency_from_order_total_prop', 900, 2 ),
				self::hook_entry( 'woocommerce_get_formatted_order_total', 'maybe_clear_order_currency_after_formatted_order_total', 900, 4 ),
				self::hook_entry( 'woocommerce_thankyou_order_id', 'init_order_currency', 10 ),
				self::hook_entry( 'woocommerce_cart_hash', 'add_currency_to_cart_hash', 900 ),
				self::hook_entry( 'woocommerce_shipping_method_add_rate_args', 'fix_price_decimals_for_shipping_rates', 900, 2 ),
			),
			'actions' => array(
				self::hook_entry( 'before_woocommerce_pay', 'init_order_currency_from_query_vars', 10 ),
				self::hook_entry( 'woocommerce_account_view-order_endpoint', 'init_order_currency', 9 ),
			),
		);
	}

	/**
	 * Get the preserved analytics hook metadata.
	 *
	 * @return array{filters: array<int,array<string,mixed>>, actions: array<int,array<string,mixed>>}
	 */
	private static function get_analytics_hook_group(): array {
		return array(
			'filters' => array(
				self::hook_entry( 'woocommerce_analytics_report_should_use_cache', 'disable_report_caching', 10 ),
				self::hook_entry( 'woocommerce_analytics_update_order_stats_data', 'update_order_stats_data', 99999, 2 ),
				self::hook_entry( 'woocommerce_analytics_orders_query_args', 'apply_customer_currency_args', 10 ),
				self::hook_entry( 'woocommerce_analytics_orders_stats_query_args', 'apply_customer_currency_args', 10 ),
				self::hook_entry( 'woocommerce_analytics_clauses_select', 'filter_select_clauses', 20, 2 ),
				self::hook_entry( 'woocommerce_analytics_clauses_join', 'filter_join_clauses', 20, 2 ),
				self::hook_entry( 'woocommerce_analytics_clauses_where_orders_subquery', 'filter_where_clauses', 10 ),
				self::hook_entry( 'woocommerce_analytics_clauses_where_orders_stats_total', 'filter_where_clauses', 10 ),
				self::hook_entry( 'woocommerce_analytics_clauses_where_orders_stats_interval', 'filter_where_clauses', 10 ),
				self::hook_entry( 'woocommerce_analytics_clauses_select_orders_subquery', 'filter_select_orders_clauses', 10 ),
				self::hook_entry( 'woocommerce_analytics_clauses_select_orders_stats_total', 'filter_select_orders_clauses', 10 ),
			),
			'actions' => array(),
		);
	}

	/**
	 * Get the preserved selected currency hook metadata.
	 *
	 * @return array{filters: array<int,array<string,mixed>>, actions: array<int,array<string,mixed>>}
	 */
	private static function get_selected_currency_hook_group(): array {
		return array(
			'filters' => array(),
			'actions' => array(
				self::hook_entry( 'init', 'update_selected_currency_by_url', 11 ),
				self::hook_entry( 'init', 'update_selected_currency_by_geolocation', 12 ),
				self::hook_entry( 'wp_footer', 'display_geolocation_currency_update_notice', 10 ),
				self::hook_entry( 'woocommerce_created_customer', 'set_new_customer_currency_meta', 10 ),
				self::hook_entry( 'woocommerce_edit_account_form', 'add_presentment_currency_switch', 10 ),
				self::hook_entry( 'woocommerce_save_account_details', 'save_presentment_currency', 10 ),
			),
		);
	}

	/**
	 * Get the preserved non-Store REST request override hook metadata.
	 *
	 * @return array{filters: array<int,array<string,mixed>>, actions: array<int,array<string,mixed>>}
	 */
	private static function get_rest_request_override_hook_group(): array {
		return array(
			'filters' => array(
				self::hook_entry( 'wcpay_multi_currency_override_selected_currency', 'get_currency_from_query_param|get_store_currency_code', 10 ),
				self::hook_entry( 'wcpay_multi_currency_should_return_store_currency', '__return_true', 10 ),
				self::hook_entry( 'wcpay_multi_currency_should_convert_product_price', '__return_false', 10 ),
			),
			'actions' => array(),
		);
	}

	/**
	 * Get the preserved tracking hook metadata.
	 *
	 * @return array{filters: array<int,array<string,mixed>>, actions: array<int,array<string,mixed>>}
	 */
	private static function get_tracking_hook_group(): array {
		return array(
			'filters'     => array(
				self::hook_entry( 'woocommerce_tracker_data', 'add_tracker_data', 50 ),
			),
			'actions'     => array(),
			'tracker_key' => MultiCurrencyTrackingProjectionService::TRACKER_KEY,
		);
	}

	/**
	 * Build hook metadata.
	 *
	 * @param string $hook          Hook name.
	 * @param string $callback      Callback marker.
	 * @param int    $priority      Hook priority.
	 * @param int    $accepted_args Accepted argument count.
	 * @return array<string,mixed>
	 */
	private static function hook_entry( string $hook, string $callback, int $priority, int $accepted_args = 1 ): array {
		return array(
			'hook'          => $hook,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}
}
