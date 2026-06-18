<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\ShippingPolicy;
use WC_Unit_Test_Case;

/**
 * Tests for the POS Shipping policy hook.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\ShippingPolicy
 */
class ShippingPolicyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ShippingPolicy
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new ShippingPolicy();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_cart_needs_shipping', array( $this->sut, 'maybe_disable_shipping' ) );
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * The filter is installed unconditionally; the POS gating happens inside the
	 * callback, not at registration time.
	 *
	 * @testdox register() attaches the needs-shipping opt-out unconditionally.
	 */
	public function test_register_attaches_filter_unconditionally(): void {
		$this->sut->register();

		$this->assertNotFalse( has_filter( 'woocommerce_cart_needs_shipping', array( $this->sut, 'maybe_disable_shipping' ) ) );
	}

	/**
	 * @testdox maybe_disable_shipping reports no shipping needs on POS requests.
	 */
	public function test_disables_shipping_in_pos_context(): void {
		Context::set_test_override( true );

		$this->assertFalse( $this->sut->maybe_disable_shipping( true ) );
	}

	/**
	 * @testdox maybe_disable_shipping returns its input unchanged outside POS context.
	 */
	public function test_returns_input_unchanged_outside_pos_context(): void {
		Context::set_test_override( false );

		$this->assertTrue( $this->sut->maybe_disable_shipping( true ) );
		$this->assertFalse( $this->sut->maybe_disable_shipping( false ) );
	}
}
