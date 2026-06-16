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
	 * @testdox Should disable mixed purchase for switch carts only.
	 */
	public function test_disables_mixed_purchase_for_switch_carts_only(): void {
		$this->assertSame( 'yes', MultiCurrencySubscriptionsCompatibilityProjectionService::maybe_disable_mixed_cart( 'yes', false ) );
		$this->assertSame( 'no', MultiCurrencySubscriptionsCompatibilityProjectionService::maybe_disable_mixed_cart( 'yes', true ) );
	}
}
