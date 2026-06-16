<?php
/**
 * MultiCurrencySubscriptionsCompatibilityProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects multi-currency Subscriptions compatibility decisions without registering hooks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencySubscriptionsCompatibilityProjectionService {

	private const FILTER_PREFIX = 'wcpay_multi_currency_';

	private const SUBSCRIPTION_PERCENT_COUPON_TYPES = array(
		'recurring_percent',
		'sign_up_fee_percent',
		'renewal_percent',
	);

	private const SUBSCRIPTION_RECURRING_COUPON_TYPES = array(
		'recurring_fee',
		'recurring_percent',
		'renewal_fee',
		'renewal_percent',
		'renewal_cart',
	);

	/**
	 * Project the Subscriptions compatibility hook/filter manifest.
	 *
	 * @return array<string,array<int,array<string,mixed>>>
	 *
	 * @since 11.0.0
	 */
	public static function get_hook_manifest(): array {
		return array(
			'actions' => array(),
			'filters' => array(
				array(
					'hook'          => self::filter_name( 'override_selected_currency' ),
					'callback'      => 'override_selected_currency',
					'priority'      => 50,
					'accepted_args' => 1,
				),
				array(
					'hook'          => self::filter_name( 'should_disable_currency_switching' ),
					'callback'      => 'should_disable_currency_switching',
					'priority'      => 50,
					'accepted_args' => 1,
				),
				array(
					'hook'          => self::filter_name( 'should_convert_product_price' ),
					'callback'      => 'should_convert_product_price',
					'priority'      => 50,
					'accepted_args' => 2,
				),
				array(
					'hook'          => self::filter_name( 'should_convert_coupon_amount' ),
					'callback'      => 'should_convert_coupon_amount',
					'priority'      => 50,
					'accepted_args' => 2,
				),
				array(
					'hook'          => 'option_woocommerce_subscriptions_multiple_purchase',
					'callback'      => 'maybe_disable_mixed_cart',
					'priority'      => 50,
					'accepted_args' => 1,
				),
			),
		);
	}

	/**
	 * Project whether currency switching should be disabled for subscription contexts.
	 *
	 * @param bool $should_disable                Existing switching-disable decision.
	 * @param bool $has_subscription_cart_context Whether the cart contains renewal, resubscribe, or switch data.
	 * @param bool $has_switch_request_context    Whether a verified switch-subscription request is active.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_disable_currency_switching(
		bool $should_disable,
		bool $has_subscription_cart_context,
		bool $has_switch_request_context
	): bool {
		return $should_disable || $has_subscription_cart_context || $has_switch_request_context;
	}

	/**
	 * Project whether product price conversion should run for Subscriptions contexts.
	 *
	 * @param bool $should_convert             Existing product conversion decision.
	 * @param bool $has_renewal_cart_item      Whether a renewal cart item is present.
	 * @param bool $has_resubscribe_cart_item  Whether a resubscribe cart item is present.
	 * @param bool $is_renewal_setup_context   Whether Subscriptions is setting up renewal cart prices.
	 * @param bool $is_price_calculation_context Whether the call stack is calculating product totals.
	 * @param bool $is_recurring_item_context  Whether recurring item data is being read from a subscription.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_convert_product_price(
		bool $should_convert,
		bool $has_renewal_cart_item,
		bool $has_resubscribe_cart_item,
		bool $is_renewal_setup_context,
		bool $is_price_calculation_context,
		bool $is_recurring_item_context
	): bool {
		if ( ! $should_convert ) {
			return false;
		}

		if ( $is_recurring_item_context ) {
			return false;
		}

		if (
			( $has_renewal_cart_item || $has_resubscribe_cart_item )
			&& ! $is_renewal_setup_context
			&& $is_price_calculation_context
		) {
			return false;
		}

		return true;
	}

	/**
	 * Project whether coupon amount conversion should run for Subscriptions contexts.
	 *
	 * @param bool   $should_convert          Existing coupon conversion decision.
	 * @param string $discount_type           Coupon discount type.
	 * @param bool   $has_renewal_cart_item   Whether a renewal cart item is present.
	 * @param bool   $is_early_renewal_context Whether Subscriptions is setting up early renewal coupons.
	 * @param bool   $is_apply_coupon_context Whether WooCommerce discounts are applying a coupon.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_convert_coupon_amount(
		bool $should_convert,
		string $discount_type,
		bool $has_renewal_cart_item,
		bool $is_early_renewal_context,
		bool $is_apply_coupon_context
	): bool {
		if ( ! $should_convert ) {
			return false;
		}

		$discount_type = strtolower( trim( $discount_type ) );

		if ( in_array( $discount_type, self::SUBSCRIPTION_PERCENT_COUPON_TYPES, true ) ) {
			return false;
		}

		if (
			$has_renewal_cart_item
			&& ! $is_early_renewal_context
			&& $is_apply_coupon_context
			&& in_array( $discount_type, self::SUBSCRIPTION_RECURRING_COUPON_TYPES, true )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Project the mixed-purchase setting for subscription switch carts.
	 *
	 * @param mixed $multiple_purchase_value Existing option value.
	 * @param bool  $has_switch_cart_item    Whether a subscription switch cart item is present.
	 * @return mixed
	 *
	 * @since 11.0.0
	 */
	public static function maybe_disable_mixed_cart( $multiple_purchase_value, bool $has_switch_cart_item ) {
		return $has_switch_cart_item ? 'no' : $multiple_purchase_value;
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
