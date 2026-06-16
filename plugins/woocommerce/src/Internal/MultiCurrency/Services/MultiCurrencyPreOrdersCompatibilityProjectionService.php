<?php
/**
 * MultiCurrencyPreOrdersCompatibilityProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects multi-currency Pre-Orders compatibility decisions without registering hooks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyPreOrdersCompatibilityProjectionService {

	/**
	 * Project the Pre-Orders compatibility hook/filter manifest.
	 *
	 * @return array<string,array<int,array<string,mixed>>>
	 *
	 * @since 11.0.0
	 */
	public static function get_hook_manifest(): array {
		return array(
			'filters' => array(
				array(
					'hook'          => 'wc_pre_orders_fee',
					'callback'      => 'convert_pre_orders_fee',
					'priority'      => 10,
					'accepted_args' => 1,
				),
			),
			'actions' => array(),
		);
	}

	/**
	 * Tell whether Pre-Orders compatibility hooks should register.
	 *
	 * @param bool $pre_orders_available Whether Pre-Orders runtime is available.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_register( bool $pre_orders_available ): bool {
		return $pre_orders_available;
	}

	/**
	 * Tell whether Pre-Orders fee args include a convertible amount.
	 *
	 * @param array<mixed> $args Pre-Orders fee args.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_convert_fee_amount( array $args ): bool {
		return array_key_exists( 'amount', $args );
	}
}
