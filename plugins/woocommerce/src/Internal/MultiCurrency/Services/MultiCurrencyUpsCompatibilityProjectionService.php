<?php
/**
 * MultiCurrencyUpsCompatibilityProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects multi-currency UPS compatibility decisions without registering hooks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyUpsCompatibilityProjectionService {

	private const FILTER_PREFIX = 'wcpay_multi_currency_';

	/**
	 * Project the UPS compatibility hook/filter manifest.
	 *
	 * @return array<string,array<int,array<string,mixed>>>
	 *
	 * @since 11.0.0
	 */
	public static function get_hook_manifest(): array {
		return array(
			'filters' => array(
				array(
					'hook'          => self::filter_name( 'should_return_store_currency' ),
					'callback'      => 'should_return_store_currency',
					'priority'      => 10,
					'accepted_args' => 1,
				),
			),
			'actions' => array(),
		);
	}

	/**
	 * Tell whether UPS compatibility hooks should register.
	 *
	 * @param bool $ups_available Whether UPS runtime is available.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_register( bool $ups_available ): bool {
		return $ups_available;
	}

	/**
	 * Tell whether native multi-currency should force store currency.
	 *
	 * @param bool $should_return          Existing store-currency decision.
	 * @param bool $is_ups_shipping_context Whether UPS shipping calculation is running.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_return_store_currency( bool $should_return, bool $is_ups_shipping_context ): bool {
		return $should_return || $is_ups_shipping_context;
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
