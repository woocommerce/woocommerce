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
		remove_filter( 'woocommerce_store_api_order_default_payment_method', '__return_empty_string' );
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * @testdox register() attaches the empty-default filter inside POS context.
	 */
	public function test_register_attaches_filter_in_pos_context(): void {
		Context::set_test_override( true );

		$this->sut->register();

		$this->assertNotFalse( has_filter( 'woocommerce_store_api_order_default_payment_method', '__return_empty_string' ) );
	}

	/**
	 * @testdox register() does not attach the filter outside POS context.
	 */
	public function test_register_skips_filter_outside_pos_context(): void {
		Context::set_test_override( false );

		$this->sut->register();

		$this->assertFalse( has_filter( 'woocommerce_store_api_order_default_payment_method', '__return_empty_string' ) );
	}
}
