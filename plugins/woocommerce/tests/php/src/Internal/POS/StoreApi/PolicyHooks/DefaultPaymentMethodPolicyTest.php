<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\DefaultPaymentMethodPolicy;
use WC_Unit_Test_Case;

/**
 * Tests for the POS Default Payment Method policy hook.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\DefaultPaymentMethodPolicy
 */
class DefaultPaymentMethodPolicyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var DefaultPaymentMethodPolicy
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new DefaultPaymentMethodPolicy();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_store_api_order_default_payment_method', array( $this->sut, 'maybe_clear_default_payment_method' ) );
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * The filter is installed unconditionally; the POS gating happens inside the
	 * callback, not at registration time.
	 *
	 * @testdox register() attaches the empty-default filter unconditionally.
	 */
	public function test_register_attaches_filter_unconditionally(): void {
		$this->sut->register();

		$this->assertNotFalse( has_filter( 'woocommerce_store_api_order_default_payment_method', array( $this->sut, 'maybe_clear_default_payment_method' ) ) );
	}

	/**
	 * @testdox maybe_clear_default_payment_method clears the default on POS requests.
	 */
	public function test_clears_default_in_pos_context(): void {
		Context::set_test_override( true );

		$this->assertSame( '', $this->sut->maybe_clear_default_payment_method( 'woocommerce_payments' ) );
	}

	/**
	 * @testdox maybe_clear_default_payment_method returns its input unchanged outside POS context.
	 */
	public function test_returns_input_unchanged_outside_pos_context(): void {
		Context::set_test_override( false );

		$this->assertSame( 'woocommerce_payments', $this->sut->maybe_clear_default_payment_method( 'woocommerce_payments' ) );
	}
}
