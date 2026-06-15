<?php
/**
 * MultiCurrencyCompatibilityProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects multi-currency compatibility metadata without registering hooks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyCompatibilityProjectionService {

	private const FILTER_PREFIX = 'wcpay_multi_currency_';

	/**
	 * Project compatibility integrations that load when more than one currency is enabled.
	 *
	 * @param bool $has_additional_currencies Whether the store has additional enabled currencies.
	 * @return string[]
	 *
	 * @since 11.0.0
	 */
	public static function get_compatibility_integrations( bool $has_additional_currencies ): array {
		if ( ! $has_additional_currencies ) {
			return array();
		}

		return array(
			'WooCommerceBookings',
			'WooCommerceFedEx',
			'WooCommerceNameYourPrice',
			'WooCommercePreOrders',
			'WooCommerceProductAddOns',
			'WooCommerceSubscriptions',
			'WooCommerceUPS',
			'WooCommerceDeposits',
			'WooCommercePointsAndRewards',
		);
	}

	/**
	 * Project the base compatibility hook/filter manifest.
	 *
	 * @return array<string,array<int,array<string,mixed>>>
	 *
	 * @since 11.0.0
	 */
	public static function get_hook_manifest(): array {
		return array(
			'actions' => array(
				array(
					'hook'     => 'init',
					'callback' => 'init_compatibility_classes',
					'priority' => 11,
				),
			),
			'filters' => array(
				array(
					'hook'     => 'woocommerce_admin_sales_record_milestone_enabled',
					'callback' => 'attach_order_modifier',
					'priority' => 10,
					'context'  => 'cron',
				),
				array(
					'hook'     => self::filter_name( 'override_selected_currency' ),
					'callback' => 'override_selected_currency',
					'priority' => 10,
				),
				array(
					'hook'        => self::filter_name( 'should_hide_widgets' ),
					'callback'    => 'should_hide_widgets',
					'priority'    => 10,
					'deprecated'  => true,
					'replaced_by' => self::filter_name( 'should_disable_currency_switching' ),
				),
				array(
					'hook'     => self::filter_name( 'should_disable_currency_switching' ),
					'callback' => 'should_disable_currency_switching',
					'priority' => 10,
				),
				array(
					'hook'     => self::filter_name( 'should_convert_coupon_amount' ),
					'callback' => 'should_convert_coupon_amount',
					'priority' => 10,
				),
				array(
					'hook'     => self::filter_name( 'should_convert_product_price' ),
					'callback' => 'should_convert_product_price',
					'priority' => 10,
				),
				array(
					'hook'     => self::filter_name( 'should_return_store_currency' ),
					'callback' => 'should_return_store_currency',
					'priority' => 10,
				),
			),
		);
	}

	/**
	 * Project switching-disable reasons from explicit non-mutating inputs.
	 *
	 * @param array<string,mixed> $query_args               Query arguments.
	 * @param bool                $subscription_context     Whether a subscription context disables switching.
	 * @param bool                $external_filter_disabled Whether external filters disable switching.
	 * @return string[]
	 *
	 * @since 11.0.0
	 */
	public static function get_switching_disable_reasons(
		array $query_args = array(),
		bool $subscription_context = false,
		bool $external_filter_disabled = false
	): array {
		$reasons = array();

		if ( array_key_exists( 'pay_for_order', $query_args ) ) {
			$reasons[] = 'pay_for_order';
		}

		if ( $subscription_context ) {
			$reasons[] = 'subscription_context';
		}

		if ( $external_filter_disabled ) {
			$reasons[] = 'external_filter';
		}

		return $reasons;
	}

	/**
	 * Project whether currency switching should be disabled.
	 *
	 * @param array<string,mixed> $query_args               Query arguments.
	 * @param bool                $subscription_context     Whether a subscription context disables switching.
	 * @param bool                $external_filter_disabled Whether external filters disable switching.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_disable_currency_switching(
		array $query_args = array(),
		bool $subscription_context = false,
		bool $external_filter_disabled = false
	): bool {
		return ! empty( self::get_switching_disable_reasons( $query_args, $subscription_context, $external_filter_disabled ) );
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
