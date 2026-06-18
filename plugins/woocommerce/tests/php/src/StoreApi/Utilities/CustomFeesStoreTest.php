<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\CustomFeesStore;
use WC_Cart;
use WC_Unit_Test_Case;

/**
 * Tests for CustomFeesStore.
 *
 * @covers \Automattic\WooCommerce\StoreApi\Utilities\CustomFeesStore
 */
class CustomFeesStoreTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CustomFeesStore
	 */
	private $sut;

	/**
	 * Set up a session-backed store.
	 */
	public function setUp(): void {
		parent::setUp();
		WC()->initialize_session();
		$this->sut = new CustomFeesStore( WC()->session );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		WC()->session->set( 'store_api_custom_fees', null );
		parent::tearDown();
	}

	/**
	 * @testdox add stores the fee and returns a spec with a generated id.
	 */
	public function test_add_stores_fee_and_returns_id(): void {
		$spec = $this->sut->add( 'Gift wrap', 5.0 );

		$this->assertArrayHasKey( 'id', $spec );
		$this->assertNotSame( '', $spec['id'], 'A fee id should be generated.' );
		$this->assertSame( 'Gift wrap', $spec['name'] );
		$this->assertSame( 5.0, $spec['amount'] );
		$this->assertTrue( $this->sut->has( $spec['id'] ), 'The stored fee should be retrievable by id.' );
		$this->assertCount( 1, $this->sut->get_all() );
	}

	/**
	 * @testdox add is idempotent: re-adding an identical fee does not create a duplicate.
	 */
	public function test_add_identical_fee_is_idempotent(): void {
		$first  = $this->sut->add( 'Service fee', 3.5, true );
		$second = $this->sut->add( 'Service fee', 3.5, true );

		$this->assertSame( $first['id'], $second['id'], 'Identical fees must share a content-derived id.' );
		$this->assertCount( 1, $this->sut->get_all(), 'A retried add must not duplicate the fee.' );
	}

	/**
	 * @testdox add keeps distinct fees separate.
	 */
	public function test_add_distinct_fees_are_separate(): void {
		$a = $this->sut->add( 'Service fee', 3.5 );
		$b = $this->sut->add( 'Service fee', 4.0 );

		$this->assertNotSame( $a['id'], $b['id'], 'A different amount must yield a different fee id.' );
		$this->assertCount( 2, $this->sut->get_all() );
	}

	/**
	 * @testdox apply_to_cart re-applies every stored fee to the cart.
	 */
	public function test_apply_to_cart_reapplies_stored_fees(): void {
		$this->sut->add( 'Gift wrap', 5.0 );
		$this->sut->add( 'Rush handling', 2.5 );

		$cart = new WC_Cart();
		$this->sut->apply_to_cart( $cart );

		$applied = $cart->fees_api()->get_fees();
		$this->assertCount( 2, $applied, 'Both stored fees should be applied to the cart.' );

		$names = array_map(
			static function ( $fee ) {
				return $fee->name;
			},
			array_values( $applied )
		);
		$this->assertContains( 'Gift wrap', $names );
		$this->assertContains( 'Rush handling', $names );
	}
}
