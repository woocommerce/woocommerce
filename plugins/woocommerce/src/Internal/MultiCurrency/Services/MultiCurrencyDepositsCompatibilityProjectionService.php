<?php
/**
 * MultiCurrencyDepositsCompatibilityProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects multi-currency Deposits compatibility decisions without registering hooks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyDepositsCompatibilityProjectionService {

	private const FILTER_PREFIX = 'wcpay_multi_currency_';

	/**
	 * Project the Deposits compatibility hook/filter manifest.
	 *
	 * @return array<string,array<int,array<string,mixed>>>
	 *
	 * @since 11.0.0
	 */
	public static function get_hook_manifest(): array {
		return array(
			'filters' => array(
				self::hook_entry( 'woocommerce_get_cart_contents', 'modify_cart_item_deposit_amounts' ),
				self::hook_entry( 'woocommerce_product_get__wc_deposit_amount', 'modify_cart_item_deposit_amount_meta', 10, 2 ),
				self::hook_entry( self::filter_name( 'should_convert_product_price' ), 'maybe_convert_product_prices_for_deposits', 10, 2 ),
			),
			'actions' => array(
				self::hook_entry( 'woocommerce_deposits_create_order', 'modify_order_currency' ),
			),
		);
	}

	/**
	 * Tell whether Deposits compatibility hooks should register.
	 *
	 * @param bool        $deposits_available Whether Deposits runtime is available.
	 * @param string|null $deposits_version   Deposits version.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_register( bool $deposits_available, ?string $deposits_version ): bool {
		return $deposits_available && ( null === $deposits_version || version_compare( $deposits_version, '2.0.1', '<' ) );
	}

	/**
	 * Tell whether a cart item has a deposit amount that should be converted.
	 *
	 * @param array<mixed> $cart_item Cart item.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_convert_cart_item_deposit_amount( array $cart_item ): bool {
		return ! empty( $cart_item['is_deposit'] ) && array_key_exists( 'deposit_amount', $cart_item );
	}

	/**
	 * Tell whether product deposit amount meta should be converted.
	 *
	 * @param string|false $deposit_type                            Product deposit type.
	 * @param bool         $is_deposits_form_output_backtrace_context Whether Deposits form output is rendering.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_convert_deposit_amount_meta( $deposit_type, bool $is_deposits_form_output_backtrace_context ): bool {
		return 'percent' === $deposit_type && $is_deposits_form_output_backtrace_context;
	}

	/**
	 * Tell whether default product-price conversion should run for Deposits products.
	 *
	 * @param bool         $should_convert                 Existing product conversion decision.
	 * @param string|false $deposit_type                   Product deposit type.
	 * @param bool         $is_cart_totals_backtrace_context Whether cart totals are being calculated.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_convert_product_price( bool $should_convert, $deposit_type, bool $is_cart_totals_backtrace_context ): bool {
		if ( ! $should_convert ) {
			return false;
		}

		return ! ( 'plan' === $deposit_type && $is_cart_totals_backtrace_context );
	}

	/**
	 * Tell whether a remaining payment order should align to the original order currency.
	 *
	 * @param string $saved_currency    New order currency.
	 * @param string $original_currency Original order currency.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_align_order_currency( string $saved_currency, string $original_currency ): bool {
		return '' !== $saved_currency && '' !== $original_currency && $saved_currency !== $original_currency;
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
