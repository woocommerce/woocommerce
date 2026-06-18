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
	 * Tear down — clear POS context override and any filters this test may have installed.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_product_is_in_stock', array( $this->sut, 'maybe_allow_oversell' ) );
		remove_filter( 'woocommerce_variation_is_in_stock', array( $this->sut, 'maybe_allow_oversell' ) );
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * The filters are installed unconditionally; the POS gating happens inside
	 * the callback, not at registration time.
	 *
	 * @testdox register() attaches the stock filters unconditionally.
	 */
	public function test_register_attaches_filters_unconditionally(): void {
		$this->sut->register();

		$this->assertNotFalse( has_filter( 'woocommerce_product_is_in_stock', array( $this->sut, 'maybe_allow_oversell' ) ) );
		$this->assertNotFalse( has_filter( 'woocommerce_variation_is_in_stock', array( $this->sut, 'maybe_allow_oversell' ) ) );
	}

	/**
	 * @testdox maybe_allow_oversell forces in-stock on POS requests.
	 */
	public function test_forces_in_stock_in_pos_context(): void {
		Context::set_test_override( true );

		$this->assertTrue( $this->sut->maybe_allow_oversell( false ) );
	}

	/**
	 * @testdox maybe_allow_oversell returns its input unchanged outside POS context.
	 */
	public function test_returns_input_unchanged_outside_pos_context(): void {
		Context::set_test_override( false );

		$this->assertFalse( $this->sut->maybe_allow_oversell( false ) );
		$this->assertTrue( $this->sut->maybe_allow_oversell( true ) );
	}
}
