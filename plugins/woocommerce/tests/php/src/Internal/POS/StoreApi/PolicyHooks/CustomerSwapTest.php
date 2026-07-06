<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CustomerSwap;
use Automattic\WooCommerce\Internal\POS\StoreApi\POSSessionHandler;
use WC_Unit_Test_Case;

/**
 * Tests for the POS customer swap.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CustomerSwap
 */
class CustomerSwapTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CustomerSwap
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new CustomerSwap();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		Context::set_test_override( null );
		remove_all_filters( 'woocommerce_session_handler' );
		WC()->session = null;
		WC()->initialize_session();
		WC()->customer = new \WC_Customer( get_current_user_id(), true );
		parent::tearDown();
	}

	/**
	 * @testdox Outside POS context nothing is swapped.
	 */
	public function test_no_swap_outside_pos_context(): void {
		Context::set_test_override( false );
		$before = WC()->customer;

		$this->assertNull( $this->sut->maybe_swap_customer( null ) );
		$this->assertSame( $before, WC()->customer );
	}

	/**
	 * @testdox The swapped customer is blank, with the default store-base location stripped.
	 */
	public function test_swapped_customer_is_blank(): void {
		Context::set_test_override( true );

		$this->sut->maybe_swap_customer( null );

		$this->assertSame( 0, WC()->customer->get_id() );
		$this->assertSame( '', WC()->customer->get_billing_country() );
		$this->assertSame( '', WC()->customer->get_shipping_country() );
	}

	/**
	 * On eagerly-initialized requests the operator-derived customer has its
	 * save() bound to shutdown; left bound, it writes operator PII into the
	 * guest transaction session row.
	 *
	 * @testdox The swap unbinds the previous customer's shutdown save and binds none for the blank one.
	 */
	public function test_swap_unbinds_operator_shutdown_save(): void {
		Context::set_test_override( true );

		$operator_customer = new \WC_Customer( 0, true );
		WC()->customer     = $operator_customer;
		add_action( 'shutdown', array( $operator_customer, 'save' ), 10 );

		$this->sut->maybe_swap_customer( null );

		$this->assertFalse( has_action( 'shutdown', array( $operator_customer, 'save' ) ), 'The operator customer must not persist at shutdown.' );
		$this->assertFalse( has_action( 'shutdown', array( WC()->customer, 'save' ) ), 'The blank customer must not persist at shutdown either.' );
	}

	/**
	 * On /wp-json requests dispatch fires before WooCommerce initializes the
	 * session. Without one, WC_Customer(0, true) binds to the DB-backed data
	 * store and checkout's save() tries to create a WP user — a 500 on every
	 * guest checkout, or a stray account when a receipt email was provided.
	 *
	 * @testdox The swap initializes the POS session first so the blank customer uses the session store.
	 */
	public function test_swap_initializes_session_when_missing(): void {
		Context::set_test_override( true );
		remove_all_filters( 'woocommerce_session_handler' );
		wc_get_container()->get( \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\SessionHandlerSwap::class )->register();
		WC()->session = null;

		$this->sut->maybe_swap_customer( null );

		$this->assertInstanceOf( POSSessionHandler::class, WC()->session );
		$this->assertSame(
			'WC_Customer_Data_Store_Session',
			WC()->customer->get_data_store()->get_current_class_name(),
			'The blank customer must persist to the session, never to the users table.'
		);
	}
}
