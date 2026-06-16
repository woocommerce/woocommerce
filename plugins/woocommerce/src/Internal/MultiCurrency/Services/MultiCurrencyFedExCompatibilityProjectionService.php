<?php
/**
 * MultiCurrencyFedExCompatibilityProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects multi-currency FedEx compatibility decisions without registering hooks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyFedExCompatibilityProjectionService {

	private const FILTER_PREFIX = 'wcpay_multi_currency_';

	/**
	 * Project the FedEx compatibility hook/filter manifest.
	 *
	 * @return array<string,array<int,array<string,mixed>>>
	 *
	 * @since 11.0.0
	 */
	public static function get_hook_manifest(): array {
		return array(
			'filters' => array(
				self::hook_entry( self::filter_name( 'should_convert_product_price' ), 'should_convert_product_price' ),
				self::hook_entry( self::filter_name( 'should_return_store_currency' ), 'should_return_store_currency' ),
			),
			'actions' => array(),
		);
	}

	/**
	 * Tell whether FedEx compatibility hooks should register.
	 *
	 * @param bool $fedex_available Whether FedEx runtime is available.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_register( bool $fedex_available ): bool {
		return $fedex_available;
	}

	/**
	 * Tell whether product prices should convert during FedEx calculations.
	 *
	 * @param bool $should_convert            Existing product conversion decision.
	 * @param bool $is_fedex_shipping_context Whether FedEx shipping calculation is running.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_convert_product_price( bool $should_convert, bool $is_fedex_shipping_context ): bool {
		return $should_convert && ! $is_fedex_shipping_context;
	}

	/**
	 * Tell whether native multi-currency should force store currency.
	 *
	 * @param bool $should_return             Existing store-currency decision.
	 * @param bool $is_fedex_shipping_context Whether FedEx shipping calculation is running.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_return_store_currency( bool $should_return, bool $is_fedex_shipping_context ): bool {
		return $should_return || $is_fedex_shipping_context;
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
