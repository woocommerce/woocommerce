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
					'hook'          => 'woocommerce_subscriptions_product_price',
					'callback'      => 'get_subscription_product_price',
					'priority'      => 50,
					'accepted_args' => 2,
				),
				array(
					'hook'          => 'woocommerce_product_get__subscription_sign_up_fee',
					'callback'      => 'get_subscription_product_signup_fee',
					'priority'      => 50,
					'accepted_args' => 2,
				),
				array(
					'hook'          => 'woocommerce_product_variation_get__subscription_sign_up_fee',
					'callback'      => 'get_subscription_product_signup_fee',
					'priority'      => 50,
					'accepted_args' => 2,
				),
				array(
					'hook'          => 'woocommerce_subscription_price_string_details',
					'callback'      => 'maybe_set_current_my_account_subscription',
					'priority'      => 50,
					'accepted_args' => 2,
				),
				array(
					'hook'          => 'woocommerce_get_formatted_subscription_total',
					'callback'      => 'maybe_clear_current_my_account_subscription',
					'priority'      => 50,
					'accepted_args' => 2,
				),
				array(
					'hook'          => 'wc_price',
					'callback'      => 'maybe_get_explicit_format_for_subscription_total',
					'priority'      => 50,
					'accepted_args' => 1,
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
	 * Project whether a direct subscription product price should be converted.
	 *
	 * @param mixed $price                        Subscription product price.
	 * @param bool  $should_convert_product_price Product conversion decision after Subscriptions guards.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_convert_subscription_product_price( $price, bool $should_convert_product_price ): bool {
		return (bool) $price && $should_convert_product_price;
	}

	/**
	 * Project whether a subscription sign-up fee should be converted.
	 *
	 * @param mixed $price                               Subscription sign-up fee.
	 * @param bool  $is_switch_product                   Whether the product matches the active switch cart item.
	 * @param bool  $is_subscription_price_setup_context Whether Subscriptions is setting prices for calculation.
	 * @param bool  $is_switch_proration_context         Whether switch proration totals are being calculated.
	 * @param bool  $has_changed_signup_fee_meta         Whether sign-up fee meta was already mutated.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_convert_subscription_signup_fee(
		$price,
		bool $is_switch_product,
		bool $is_subscription_price_setup_context,
		bool $is_switch_proration_context,
		bool $has_changed_signup_fee_meta
	): bool {
		if ( ! $price ) {
			return false;
		}

		if ( ! $is_switch_product ) {
			return true;
		}

		return ! $is_subscription_price_setup_context
			&& ! $is_switch_proration_context
			&& ! $has_changed_signup_fee_meta;
	}

	/**
	 * Project whether the current subscription should be tracked while totals format.
	 *
	 * @param bool $is_my_subscriptions_template_context Whether the My Account subscriptions template is rendering.
	 * @param bool $is_formatted_order_total_context     Whether a subscription formatted total is being calculated.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_set_current_my_account_subscription(
		bool $is_my_subscriptions_template_context,
		bool $is_formatted_order_total_context
	): bool {
		return $is_my_subscriptions_template_context || $is_formatted_order_total_context;
	}

	/**
	 * Project explicit subscription total price HTML.
	 *
	 * @param string      $html_price                         Price HTML.
	 * @param string|null $currency_code                      Currency code.
	 * @param bool        $has_additional_currencies_enabled Whether additional currencies are enabled.
	 * @return string
	 *
	 * @since 11.0.0
	 */
	public static function get_explicit_subscription_total_price_html(
		string $html_price,
		?string $currency_code,
		bool $has_additional_currencies_enabled
	): string {
		if ( ! $has_additional_currencies_enabled || null === $currency_code || '' === trim( $currency_code ) ) {
			return $html_price;
		}

		$currency_code  = strtoupper( trim( $currency_code ) );
		$price_to_check = html_entity_decode( wp_strip_all_tags( $html_price ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 );

		return false === strpos( $price_to_check, $currency_code )
			? $html_price . ' ' . $currency_code
			: $html_price;
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
