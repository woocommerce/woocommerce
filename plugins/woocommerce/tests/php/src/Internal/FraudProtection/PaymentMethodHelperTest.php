<?php
/**
 * PaymentMethodHelperTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\PaymentMethodHelper;

/**
 * Tests for PaymentMethodHelper.
 *
 * @covers \Automattic\WooCommerce\Internal\FraudProtection\PaymentMethodHelper
 */
class PaymentMethodHelperTest extends \WC_Unit_Test_Case {

	/**
	 * Test get_payment_method_name returns gateway title for known gateway.
	 */
	public function test_get_payment_method_name_returns_gateway_title_for_known_gateway(): void {
		// Test with a known gateway (bacs is available by default in WooCommerce).
		$result = PaymentMethodHelper::get_payment_method_name( 'bacs' );

		// Should return a readable name, not just the ID.
		$this->assertNotEmpty( $result );
		$this->assertEquals( 'Direct bank transfer', $result );
	}

	/**
	 * Test get_payment_method_name returns ID when gateway not found.
	 */
	public function test_get_payment_method_name_returns_id_when_gateway_not_found(): void {
		// Test that get_payment_method_name returns the ID as fallback.
		$result = PaymentMethodHelper::get_payment_method_name( 'nonexistent_gateway' );
		$this->assertEquals( 'nonexistent_gateway', $result );
	}
}
