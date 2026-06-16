<?php
/**
 * MultiCurrencyBookingsCompatibilityProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects multi-currency Bookings compatibility decisions without registering hooks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyBookingsCompatibilityProjectionService {

	private const FILTER_PREFIX = 'wcpay_multi_currency_';

	/**
	 * Project the Bookings compatibility hook/filter manifest.
	 *
	 * @return array<string,array<int,array<string,mixed>>>
	 *
	 * @since 11.0.0
	 */
	public static function get_hook_manifest(): array {
		return array(
			'filters' => array(
				self::hook_entry( 'woocommerce_bookings_calculated_booking_cost', 'adjust_amount_for_calculated_booking_cost', 50 ),
				self::hook_entry( 'woocommerce_product_get_block_cost', 'get_price', 50 ),
				self::hook_entry( 'woocommerce_product_get_cost', 'get_price', 50 ),
				self::hook_entry( 'woocommerce_product_get_display_cost', 'get_price', 50 ),
				self::hook_entry( 'woocommerce_product_booking_person_type_get_block_cost', 'get_price', 50 ),
				self::hook_entry( 'woocommerce_product_booking_person_type_get_cost', 'get_price', 50 ),
				self::hook_entry( 'woocommerce_product_get_resource_base_costs', 'get_resource_prices', 50 ),
				self::hook_entry( 'woocommerce_product_get_resource_block_costs', 'get_resource_prices', 50 ),
				self::hook_entry( self::filter_name( 'should_convert_product_price' ), 'should_convert_product_price', 50, 2 ),
				self::hook_entry( 'woocommerce_bookings_process_cost_rules_cost', 'get_price', 50 ),
				self::hook_entry( 'woocommerce_bookings_process_cost_rules_base_cost', 'get_price', 50 ),
			),
			'actions' => array(
				self::hook_entry( 'wp_ajax_wc_bookings_calculate_costs', 'add_wc_price_args_filter_for_ajax', 9 ),
				self::hook_entry( 'wp_ajax_nopriv_wc_bookings_calculate_costs', 'add_wc_price_args_filter_for_ajax', 9 ),
			),
		);
	}

	/**
	 * Tell whether Bookings compatibility hooks should register.
	 *
	 * @param bool $bookings_available Whether Bookings runtime is available.
	 * @param bool $is_admin           Whether this is an admin request.
	 * @param bool $is_ajax            Whether this is an Ajax request.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_register( bool $bookings_available, bool $is_admin, bool $is_ajax ): bool {
		return $bookings_available && ( ! $is_admin || $is_ajax );
	}

	/**
	 * Tell whether a calculated booking cost should be converted.
	 *
	 * @param bool $is_cart_add_to_cart_context Whether cart add-to-cart is currently calculating cost.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_adjust_calculated_booking_cost( bool $is_cart_add_to_cart_context ): bool {
		return ! $is_cart_add_to_cart_context;
	}

	/**
	 * Get the native price projection type for a Bookings price.
	 *
	 * @param bool $is_price_html_context Whether booking price HTML is rendering.
	 * @return string
	 *
	 * @since 11.0.0
	 */
	public static function get_booking_price_type( bool $is_price_html_context ): string {
		return $is_price_html_context ? 'product' : 'exchange_rate';
	}

	/**
	 * Tell whether a Bookings price hook value should be converted.
	 *
	 * @param mixed $price                                           Bookings price.
	 * @param bool  $is_cart_add_to_cart_cost_calculation_context Whether cart add-to-cart cost calculation is running.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_convert_booking_price( $price, bool $is_cart_add_to_cart_cost_calculation_context ): bool {
		return (bool) $price && ! $is_cart_add_to_cart_cost_calculation_context;
	}

	/**
	 * Tell whether default product-price conversion should run for Bookings products.
	 *
	 * @param bool   $should_convert       Existing product conversion decision.
	 * @param string $product_type         Product type.
	 * @param bool   $is_price_html_context Whether booking price HTML is rendering.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_convert_booking_product_price( bool $should_convert, string $product_type, bool $is_price_html_context ): bool {
		if ( ! $should_convert || 'booking' !== $product_type ) {
			return $should_convert;
		}

		return $is_price_html_context ? false : $should_convert;
	}

	/**
	 * Build a hook entry.
	 *
	 * @param string $hook          Hook name.
	 * @param string $callback      Callback method.
	 * @param int    $priority      Hook priority.
	 * @param int    $accepted_args Accepted args.
	 * @return array<string,mixed>
	 */
	private static function hook_entry( string $hook, string $callback, int $priority = 10, int $accepted_args = 1 ): array {
		return array(
			'hook'          => $hook,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}

	/**
	 * Build a multi-currency filter name.
	 *
	 * @param string $suffix Filter suffix.
	 * @return string
	 */
	private static function filter_name( string $suffix ): string {
		return self::FILTER_PREFIX . $suffix;
	}
}
