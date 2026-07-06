<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi;

use Automattic\WooCommerce\Internal\POS\StoreApi\CustomFeesStore;
use Automattic\WooCommerce\Internal\POS\StoreApi\POSSessionHandler;
use WC_Unit_Test_Case;

/**
 * Tests for the POS custom fees store.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\CustomFeesStore
 */
class CustomFeesStoreTest extends WC_Unit_Test_Case {

	/**
	 * Session backing the store under test.
	 *
	 * @var POSSessionHandler
	 */
	private $session;

	/**
	 * The System Under Test.
	 *
	 * @var CustomFeesStore
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->session = new POSSessionHandler();
		$this->session->init();
		$this->sut = new CustomFeesStore( $this->session );
	}

	/**
	 * @testdox add() stores a spec retrievable via get_all().
	 */
	public function test_add_stores_spec(): void {
		$spec = $this->sut->add( 'Gift wrap', 5.5, true, 'reduced-rate' );

		$this->assertSame( 'Gift wrap', $spec['name'] );
		$this->assertSame( 5.5, $spec['amount'] );
		$this->assertTrue( $spec['taxable'] );
		$this->assertSame( 'reduced-rate', $spec['tax_class'] );
		$this->assertSame( array( $spec['id'] => $spec ), $this->sut->get_all() );
	}

	/**
	 * A replayed request (client retry) must not double-charge the customer.
	 *
	 * @testdox Adding the identical fee twice is an idempotent upsert.
	 */
	public function test_identical_fee_is_idempotent(): void {
		$first  = $this->sut->add( 'Gift wrap', 5.5 );
		$second = $this->sut->add( 'Gift wrap', 5.5 );

		$this->assertSame( $first['id'], $second['id'] );
		$this->assertCount( 1, $this->sut->get_all() );
	}

	/**
	 * @testdox Fees with different content coexist.
	 */
	public function test_different_fees_coexist(): void {
		$this->sut->add( 'Gift wrap', 5.5 );
		$this->sut->add( 'Deposit', 10.0 );

		$this->assertCount( 2, $this->sut->get_all() );
	}

	/**
	 * @testdox apply_to_cart() registers every stored fee with the cart's fees API.
	 */
	public function test_apply_to_cart_registers_stored_fees(): void {
		$this->sut->add( 'Gift wrap', 5.5 );
		$this->sut->add( 'Deposit', 10.0 );

		$cart = WC()->cart;
		$cart->fees_api()->remove_all_fees();

		$this->sut->apply_to_cart( $cart );

		$fees = $cart->fees_api()->get_fees();
		$this->assertCount( 2, $fees );
		$this->assertEqualSets( array( 'Gift wrap', 'Deposit' ), array_values( wp_list_pluck( $fees, 'name' ) ) );

		$cart->fees_api()->remove_all_fees();
	}

	/**
	 * @testdox The store survives a session save/resume round trip.
	 */
	public function test_fees_survive_session_round_trip(): void {
		$this->sut->add( 'Gift wrap', 5.5 );
		$this->session->save_data();

		$resumed                    = new POSSessionHandler();
		$_SERVER['HTTP_CART_TOKEN'] = \Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils::get_cart_token( $this->session->get_customer_id() );
		try {
			$resumed->init();
			$this->assertCount( 1, ( new CustomFeesStore( $resumed ) )->get_all() );
		} finally {
			unset( $_SERVER['HTTP_CART_TOKEN'] );
		}
	}
}
