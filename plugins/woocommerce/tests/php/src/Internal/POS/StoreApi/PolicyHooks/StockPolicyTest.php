<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\StockPolicy;
use WC_Unit_Test_Case;

/**
 * Tests for the POS Stock policy hook.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\StockPolicy
 */
class StockPolicyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var StockPolicy
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new StockPolicy();
	}

	/**
	 * Tear down — clear the POS context override left by any test that set it.
	 */
	public function tearDown(): void {
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * @testdox allow_oversell_for_pos returns the original value outside POS context.
	 */
	public function test_passthrough_outside_pos_context(): void {
		Context::set_test_override( false );

		$this->assertFalse( $this->sut->allow_oversell_for_pos( false ) );
		$this->assertTrue( $this->sut->allow_oversell_for_pos( true ) );
	}

	/**
	 * @testdox allow_oversell_for_pos returns true inside POS context regardless of input.
	 */
	public function test_forces_in_stock_inside_pos_context(): void {
		Context::set_test_override( true );

		$this->assertTrue( $this->sut->allow_oversell_for_pos( false ) );
		$this->assertTrue( $this->sut->allow_oversell_for_pos( true ) );
	}

	/**
	 * @testdox register() attaches the expected filters.
	 */
	public function test_register_attaches_filters(): void {
		$this->sut->register();

		$this->assertNotFalse(
			has_filter( 'woocommerce_product_is_in_stock', array( $this->sut, 'allow_oversell_for_pos' ) )
		);
		$this->assertNotFalse(
			has_filter( 'woocommerce_variation_is_in_stock', array( $this->sut, 'allow_oversell_for_pos' ) )
		);

		// Cleanup so we don't leak filter state into other tests.
		remove_filter( 'woocommerce_product_is_in_stock', array( $this->sut, 'allow_oversell_for_pos' ) );
		remove_filter( 'woocommerce_variation_is_in_stock', array( $this->sut, 'allow_oversell_for_pos' ) );
	}
}
