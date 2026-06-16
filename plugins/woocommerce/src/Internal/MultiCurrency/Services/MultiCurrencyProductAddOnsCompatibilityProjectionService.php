<?php
/**
 * MultiCurrencyProductAddOnsCompatibilityProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects multi-currency Product Add-ons compatibility decisions without registering hooks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyProductAddOnsCompatibilityProjectionService {

	private const FILTER_PREFIX = 'wcpay_multi_currency_';

	/**
	 * Project the Product Add-ons compatibility hook/filter manifest.
	 *
	 * @return array<string,array<int,array<string,mixed>>>
	 *
	 * @since 11.0.0
	 */
	public static function get_hook_manifest(): array {
		return array(
			'filters' => array_merge(
				self::get_frontend_filter_manifest(),
				self::get_ajax_filter_manifest()
			),
			'actions' => array(),
		);
	}

	/**
	 * Project frontend Product Add-ons compatibility filters.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_frontend_filter_manifest(): array {
		return array(
			self::hook_entry( 'woocommerce_product_addons_option_price_raw', 'get_addons_price', 50, 2 ),
			self::hook_entry( 'woocommerce_product_addons_price_raw', 'get_addons_price', 50, 2 ),
			self::hook_entry( 'woocommerce_product_addons_params', 'product_addons_params', 50 ),
			self::hook_entry( 'woocommerce_product_addons_get_item_data', 'get_item_data', 50, 3 ),
			self::hook_entry( 'woocommerce_product_addons_update_product_price', 'update_product_price', 50, 4 ),
			self::hook_entry( 'woocommerce_product_addons_order_line_item_meta', 'order_line_item_meta', 50, 4 ),
			self::hook_entry( self::filter_name( 'should_convert_product_price' ), 'should_convert_product_price', 50, 2 ),
		);
	}

	/**
	 * Project Ajax Product Add-ons compatibility filters.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_ajax_filter_manifest(): array {
		return array(
			self::hook_entry( 'woocommerce_product_addons_ajax_get_product_price_including_tax', 'get_product_calculation_price', 50, 3 ),
			self::hook_entry( 'woocommerce_product_addons_ajax_get_product_price_excluding_tax', 'get_product_calculation_price', 50, 3 ),
		);
	}

	/**
	 * Tell whether Product Add-ons compatibility hooks should register.
	 *
	 * @param bool $product_addons_available Whether Product Add-ons runtime is available.
	 * @param bool $is_admin                 Whether this is an admin request.
	 * @param bool $is_ajax                  Whether this is an Ajax request.
	 * @param bool $is_cron                  Whether this is a cron request.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_register( bool $product_addons_available, bool $is_admin, bool $is_ajax, bool $is_cron ): bool {
		return $product_addons_available && ( self::should_register_frontend_filters( $is_admin, $is_cron ) || $is_ajax );
	}

	/**
	 * Tell whether frontend Product Add-ons filters should register.
	 *
	 * @param bool $is_admin Whether this is an admin request.
	 * @param bool $is_cron  Whether this is a cron request.
	 * @return bool
	 */
	public static function should_register_frontend_filters( bool $is_admin, bool $is_cron ): bool {
		return ! $is_admin && ! $is_cron;
	}

	/**
	 * Tell whether a raw add-on price should be converted.
	 *
	 * @param string $price_type Add-on price type.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_convert_addon_price( string $price_type ): bool {
		return 'percentage_based' !== $price_type;
	}

	/**
	 * Tell whether default product-price conversion should run for Product Add-ons products.
	 *
	 * @param bool $should_convert       Existing product conversion decision.
	 * @param bool $addons_were_converted Whether Product Add-ons already converted this cart product.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_convert_product_price( bool $should_convert, bool $addons_were_converted ): bool {
		return $should_convert && ! $addons_were_converted;
	}

	/**
	 * Get the amount to convert for an add-on.
	 *
	 * @param mixed  $price      Add-on price.
	 * @param mixed  $value      Add-on value.
	 * @param string $field_type Add-on field type.
	 * @return float
	 *
	 * @since 11.0.0
	 */
	public static function get_addon_conversion_amount( $price, $value, string $field_type ): float {
		if ( 'input_multiplier' !== $field_type || 0.0 === (float) $value ) {
			return (float) $price;
		}

		return (float) $price / (float) $value;
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
