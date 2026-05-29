<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\DefaultPaymentMethodPolicy;
use WC_Unit_Test_Case;

/**
 * Tests for DefaultPaymentMethodPolicy.
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

	public function setUp(): void {
		parent::setUp();
		$this->sut = new DefaultPaymentMethodPolicy();
	}

	public function tearDown(): void {
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * @testdox no_default_for_pos returns the incoming method outside POS context.
	 */
	public function test_passthrough_outside_pos_context(): void {
		Context::set_test_override( false );

		$this->assertSame( 'woocommerce_payments', $this->sut->no_default_for_pos( 'woocommerce_payments' ) );
		$this->assertSame( '', $this->sut->no_default_for_pos( '' ) );
	}

	/**
	 * @testdox no_default_for_pos returns an empty string inside POS context.
	 */
	public function test_returns_empty_inside_pos_context(): void {
		Context::set_test_override( true );

		$this->assertSame( '', $this->sut->no_default_for_pos( 'woocommerce_payments' ) );
		$this->assertSame( '', $this->sut->no_default_for_pos( 'stripe' ) );
	}
}
