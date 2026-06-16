<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySubscriptionsCompatibilityProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencySubscriptionsCompatibilityProjectionService class.
 */
class MultiCurrencySubscriptionsCompatibilityProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should project subscription compatibility hook manifest without registering hooks.
	 */
	public function test_projects_subscription_compatibility_hook_manifest(): void {
		$this->assertSame(
			array(
				'actions' => array(),
				'filters' => array(
					array(
						'hook'          => 'wcpay_multi_currency_override_selected_currency',
						'callback'      => 'override_selected_currency',
						'priority'      => 50,
						'accepted_args' => 1,
					),
					array(
						'hook'          => 'wcpay_multi_currency_should_disable_currency_switching',
						'callback'      => 'should_disable_currency_switching',
						'priority'      => 50,
						'accepted_args' => 1,
					),
					array(
						'hook'          => 'wcpay_multi_currency_should_convert_product_price',
						'callback'      => 'should_convert_product_price',
						'priority'      => 50,
						'accepted_args' => 2,
					),
					array(
						'hook'          => 'wcpay_multi_currency_should_convert_coupon_amount',
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
			),
			MultiCurrencySubscriptionsCompatibilityProjectionService::get_hook_manifest()
		);
	}

	/**
	 * @testdox Should disable switching for subscription carts and switch requests.
	 */
	public function test_disables_switching_for_subscription_carts_and_switch_requests(): void {
		$this->assertFalse( MultiCurrencySubscriptionsCompatibilityProjectionService::should_disable_currency_switching( false, false, false ) );
		$this->assertTrue( MultiCurrencySubscriptionsCompatibilityProjectionService::should_disable_currency_switching( true, false, false ) );
		$this->assertTrue( MultiCurrencySubscriptionsCompatibilityProjectionService::should_disable_currency_switching( false, true, false ) );
		$this->assertTrue( MultiCurrencySubscriptionsCompatibilityProjectionService::should_disable_currency_switching( false, false, true ) );
	}

	/**
	 * @testdox Should skip product conversion for renewal and resubscribe calculation contexts.
	 */
	public function test_skips_product_conversion_for_subscription_calculation_contexts(): void {
		$this->assertFalse( MultiCurrencySubscriptionsCompatibilityProjectionService::should_convert_product_price( true, true, false, false, true, false ) );
		$this->assertFalse( MultiCurrencySubscriptionsCompatibilityProjectionService::should_convert_product_price( true, false, true, false, true, false ) );
		$this->assertFalse( MultiCurrencySubscriptionsCompatibilityProjectionService::should_convert_product_price( true, false, false, false, false, true ) );
		$this->assertTrue( MultiCurrencySubscriptionsCompatibilityProjectionService::should_convert_product_price( true, true, false, true, true, false ) );
		$this->assertTrue( MultiCurrencySubscriptionsCompatibilityProjectionService::should_convert_product_price( true, true, false, false, false, false ) );
		$this->assertFalse( MultiCurrencySubscriptionsCompatibilityProjectionService::should_convert_product_price( false, false, false, false, false, false ) );
	}

	/**
	 * @testdox Should skip coupon conversion for subscription coupon contexts.
	 */
	public function test_skips_coupon_conversion_for_subscription_coupon_contexts(): void {
		$this->assertFalse( MultiCurrencySubscriptionsCompatibilityProjectionService::should_convert_coupon_amount( true, 'recurring_percent', false, false, false ) );
		$this->assertFalse( MultiCurrencySubscriptionsCompatibilityProjectionService::should_convert_coupon_amount( true, 'renewal_fee', true, false, true ) );
		$this->assertTrue( MultiCurrencySubscriptionsCompatibilityProjectionService::should_convert_coupon_amount( true, 'renewal_fee', true, true, true ) );
		$this->assertTrue( MultiCurrencySubscriptionsCompatibilityProjectionService::should_convert_coupon_amount( true, 'fixed_cart', true, false, true ) );
		$this->assertFalse( MultiCurrencySubscriptionsCompatibilityProjectionService::should_convert_coupon_amount( false, 'fixed_cart', false, false, false ) );
	}

	/**
	 * @testdox Should convert direct subscription product prices only when product conversion remains enabled.
	 */
	public function test_converts_direct_subscription_product_prices_only_when_product_conversion_remains_enabled(): void {
		$this->assertTrue( MultiCurrencySubscriptionsCompatibilityProjectionService::should_convert_subscription_product_price( '10.00', true ) );
		$this->assertFalse( MultiCurrencySubscriptionsCompatibilityProjectionService::should_convert_subscription_product_price( 0, true ) );
		$this->assertFalse( MultiCurrencySubscriptionsCompatibilityProjectionService::should_convert_subscription_product_price( '10.00', false ) );
	}

	/**
	 * @testdox Should convert subscription sign-up fees unless a switch-proration guard applies.
	 *
	 * @dataProvider subscription_signup_fee_conversion_data
	 *
	 * @param mixed $price                               Sign-up fee price.
	 * @param bool  $is_switch_product                   Whether the product matches the active switch cart item.
	 * @param bool  $is_subscription_price_setup_context Whether Subscriptions is setting prices for calculation.
	 * @param bool  $is_switch_proration_context         Whether a repeated switch proration total calculation is active.
	 * @param bool  $has_changed_signup_fee_meta         Whether sign-up fee meta was already mutated.
	 * @param bool  $expected                            Expected conversion decision.
	 */
	public function test_converts_subscription_signup_fees_unless_switch_proration_guard_applies(
		$price,
		bool $is_switch_product,
		bool $is_subscription_price_setup_context,
		bool $is_switch_proration_context,
		bool $has_changed_signup_fee_meta,
		bool $expected
	): void {
		$this->assertSame(
			$expected,
			MultiCurrencySubscriptionsCompatibilityProjectionService::should_convert_subscription_signup_fee(
				$price,
				$is_switch_product,
				$is_subscription_price_setup_context,
				$is_switch_proration_context,
				$has_changed_signup_fee_meta
			)
		);
	}

	/**
	 * @testdox Should set current My Account subscription only while subscription totals are formatting.
	 */
	public function test_sets_current_my_account_subscription_only_while_subscription_totals_are_formatting(): void {
		$this->assertTrue( MultiCurrencySubscriptionsCompatibilityProjectionService::should_set_current_my_account_subscription( true, false ) );
		$this->assertTrue( MultiCurrencySubscriptionsCompatibilityProjectionService::should_set_current_my_account_subscription( false, true ) );
		$this->assertFalse( MultiCurrencySubscriptionsCompatibilityProjectionService::should_set_current_my_account_subscription( false, false ) );
	}

	/**
	 * @testdox Should append explicit subscription total currency code only when needed.
	 */
	public function test_appends_explicit_subscription_total_currency_code_only_when_needed(): void {
		$this->assertSame(
			'<span>$10.00</span> EUR',
			MultiCurrencySubscriptionsCompatibilityProjectionService::get_explicit_subscription_total_price_html( '<span>$10.00</span>', 'EUR', true )
		);
		$this->assertSame(
			'<span>$10.00 EUR</span>',
			MultiCurrencySubscriptionsCompatibilityProjectionService::get_explicit_subscription_total_price_html( '<span>$10.00 EUR</span>', 'EUR', true )
		);
		$this->assertSame(
			'<span>$10.00</span>',
			MultiCurrencySubscriptionsCompatibilityProjectionService::get_explicit_subscription_total_price_html( '<span>$10.00</span>', 'EUR', false )
		);
		$this->assertSame(
			'<span>$10.00</span>',
			MultiCurrencySubscriptionsCompatibilityProjectionService::get_explicit_subscription_total_price_html( '<span>$10.00</span>', '', true )
		);
	}

	/**
	 * Data provider for subscription sign-up fee conversion decisions.
	 *
	 * @return array<string,array{0:mixed,1:bool,2:bool,3:bool,4:bool,5:bool}>
	 */
	public function subscription_signup_fee_conversion_data(): array {
		return array(
			'empty price'                  => array( 0, false, false, false, false, false ),
			'regular subscription product' => array( '10.00', false, false, false, false, true ),
			'switch price setup'           => array( '10.00', true, true, false, false, false ),
			'switch proration repeat'      => array( '10.00', true, false, true, false, false ),
			'switch changed fee meta'      => array( '10.00', true, false, false, true, false ),
			'switch fee after apportion'   => array( '10.00', true, false, false, false, true ),
		);
	}

	/**
	 * @testdox Should disable mixed purchase for switch carts only.
	 */
	public function test_disables_mixed_purchase_for_switch_carts_only(): void {
		$this->assertSame( 'yes', MultiCurrencySubscriptionsCompatibilityProjectionService::maybe_disable_mixed_cart( 'yes', false ) );
		$this->assertSame( 'no', MultiCurrencySubscriptionsCompatibilityProjectionService::maybe_disable_mixed_cart( 'yes', true ) );
	}
}
