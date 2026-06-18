<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CheckoutPaymentMethodPolicy;
use WC_Unit_Test_Case;

/**
 * Tests for the POS Checkout Payment Method policy hook.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CheckoutPaymentMethodPolicy
 */
class CheckoutPaymentMethodPolicyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CheckoutPaymentMethodPolicy
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new CheckoutPaymentMethodPolicy();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_store_api_checkout_require_payment_method', array( $this->sut, 'maybe_skip_payment_method_requirement' ) );
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * The filter is installed unconditionally; the POS gating happens inside the
	 * callback, not at registration time.
	 *
	 * @testdox register() attaches the deferred-payment opt-out unconditionally.
	 */
	public function test_register_attaches_filter_unconditionally(): void {
		$this->sut->register();

		$this->assertNotFalse( has_filter( 'woocommerce_store_api_checkout_require_payment_method', array( $this->sut, 'maybe_skip_payment_method_requirement' ) ) );
	}

	/**
	 * @testdox maybe_skip_payment_method_requirement drops the requirement on POS requests.
	 */
	public function test_drops_requirement_in_pos_context(): void {
		Context::set_test_override( true );

		$this->assertFalse( $this->sut->maybe_skip_payment_method_requirement( true ) );
	}

	/**
	 * @testdox maybe_skip_payment_method_requirement returns its input unchanged outside POS context.
	 */
	public function test_returns_input_unchanged_outside_pos_context(): void {
		Context::set_test_override( false );

		$this->assertTrue( $this->sut->maybe_skip_payment_method_requirement( true ) );
		$this->assertFalse( $this->sut->maybe_skip_payment_method_requirement( false ) );
	}
}
