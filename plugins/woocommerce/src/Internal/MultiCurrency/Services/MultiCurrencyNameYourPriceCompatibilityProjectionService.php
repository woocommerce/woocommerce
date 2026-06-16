<?php
/**
 * MultiCurrencyNameYourPriceCompatibilityProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects multi-currency Name Your Price compatibility decisions without registering hooks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyNameYourPriceCompatibilityProjectionService {

	private const FILTER_PREFIX = 'wcpay_multi_currency_';

	/**
	 * Project the Name Your Price compatibility hook/filter manifest.
	 *
	 * @return array<string,array<int,array<string,mixed>>>
	 *
	 * @since 11.0.0
	 */
	public static function get_hook_manifest(): array {
		return array(
			'filters' => array(
				self::hook_entry( 'wc_nyp_raw_minimum_price', 'get_nyp_prices' ),
				self::hook_entry( 'wc_nyp_raw_maximum_price', 'get_nyp_prices' ),
				self::hook_entry( 'wc_nyp_raw_suggested_price', 'get_nyp_prices' ),
				self::hook_entry( 'woocommerce_get_cart_item_from_session', 'convert_cart_currency', 20, 2 ),
				self::hook_entry( self::filter_name( 'should_convert_product_price' ), 'should_convert_product_price', 50, 2 ),
				self::hook_entry( 'wc_nyp_edit_in_cart_args', 'edit_in_cart_args', 10, 2 ),
				self::hook_entry( 'wc_nyp_get_initial_price', 'get_initial_price', 10, 3 ),
			),
			'actions' => array(
				self::hook_entry( 'woocommerce_add_cart_item_data', 'add_initial_currency', 20, 3 ),
			),
		);
	}

	/**
	 * Tell whether Name Your Price compatibility hooks should register.
	 *
	 * @param bool $name_your_price_available Whether Name Your Price runtime is available.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_register( bool $name_your_price_available ): bool {
		return $name_your_price_available;
	}

	/**
	 * Tell whether a raw NYP price should be converted.
	 *
	 * @param mixed $price Raw price.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_convert_raw_price( $price ): bool {
		return (bool) $price;
	}

	/**
	 * Tell whether initial cart currency should be stored.
	 *
	 * @param bool $is_name_your_price_product Whether the product is NYP-enabled.
	 * @param bool $has_nyp_price              Whether cart item data includes an NYP price.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_store_initial_currency( bool $is_name_your_price_product, bool $has_nyp_price ): bool {
		return $is_name_your_price_product && $has_nyp_price;
	}

	/**
	 * Tell whether cart-session NYP currency conversion should run.
	 *
	 * @param bool $name_your_price_function_available Whether the NYP function is available.
	 * @param bool $has_original_price                 Whether cart item data has the original price.
	 * @param bool $has_original_currency              Whether cart item data has the original currency.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_convert_cart_currency( bool $name_your_price_function_available, bool $has_original_price, bool $has_original_currency ): bool {
		return $name_your_price_function_available && $has_original_price && $has_original_currency;
	}

	/**
	 * Tell whether default product-price conversion should run for NYP products.
	 *
	 * @param bool $should_convert                 Existing product conversion decision.
	 * @param bool $selected_matches_product_meta Whether the selected currency matches stored product meta.
	 * @param bool $is_name_your_price_product    Whether the product is NYP-enabled.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_convert_product_price( bool $should_convert, bool $selected_matches_product_meta, bool $is_name_your_price_product ): bool {
		return $should_convert && ! $selected_matches_product_meta && ! $is_name_your_price_product;
	}

	/**
	 * Tell whether an initial edit price should be converted from request currency.
	 *
	 * @param bool   $has_raw_price           Whether the request has a raw NYP price.
	 * @param bool   $has_source_currency     Whether the request has an NYP source currency.
	 * @param string $source_currency_code    Source currency code.
	 * @param string $selected_currency_code  Selected currency code.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_convert_initial_price( bool $has_raw_price, bool $has_source_currency, string $source_currency_code, string $selected_currency_code ): bool {
		return $has_raw_price && $has_source_currency && $source_currency_code !== $selected_currency_code;
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
