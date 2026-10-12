<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Checkout;

use Automattic\WooCommerce\Internal\Checkout\PaymentMethodPreselection;
use WC_Unit_Test_Case;

/**
 * Tests for the PaymentMethodPreselection class.
 */
class PaymentMethodPreselectionTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should preselect a payment method when the option has never been saved.
	 */
	public function test_is_enabled_by_default(): void {
		delete_option( PaymentMethodPreselection::OPTION_NAME );

		$this->assertTrue( PaymentMethodPreselection::is_enabled(), 'Existing stores must keep the current preselection behavior' );
	}

	/**
	 * @testdox Should follow the saved option value.
	 * @testWith ["yes", true]
	 *           ["no", false]
	 *
	 * @param string $option_value The saved option value.
	 * @param bool   $expected     Whether preselection should be on.
	 */
	public function test_is_enabled_follows_the_option( string $option_value, bool $expected ): void {
		update_option( PaymentMethodPreselection::OPTION_NAME, $option_value );

		$this->assertSame( $expected, PaymentMethodPreselection::is_enabled() );
	}
}
