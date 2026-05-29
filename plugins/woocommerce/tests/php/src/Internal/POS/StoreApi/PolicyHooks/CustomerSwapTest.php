<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CustomerSwap;
use WC_Customer;
use WC_Unit_Test_Case;

/**
 * Tests for CustomerSwap.
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
	 * Original WC()->customer to restore in tearDown.
	 *
	 * @var WC_Customer|null
	 */
	private $original_customer;

	/**
	 * Original current_user_id captured in setUp so it can be restored.
	 *
	 * @var int
	 */
	private $original_user_id;

	public function setUp(): void {
		parent::setUp();
		$this->sut               = new CustomerSwap();
		$this->original_customer = WC()->customer;
		$this->original_user_id  = get_current_user_id();
	}

	public function tearDown(): void {
		remove_filter( 'rest_dispatch_request', array( $this->sut, 'swap_customer' ), 11 );
		Context::set_test_override( null );
		WC()->customer = $this->original_customer;
		wp_set_current_user( $this->original_user_id );
		parent::tearDown();
	}

	/**
	 * @testdox register() attaches the dispatch hook inside POS context.
	 */
	public function test_register_attaches_filter_in_pos_context(): void {
		Context::set_test_override( true );

		$this->sut->register();

		$this->assertNotFalse( has_filter( 'rest_dispatch_request', array( $this->sut, 'swap_customer' ) ) );
	}

	/**
	 * @testdox register() does not attach the dispatch hook outside POS context.
	 */
	public function test_register_skips_filter_outside_pos_context(): void {
		Context::set_test_override( false );

		$this->sut->register();

		$this->assertFalse( has_filter( 'rest_dispatch_request', array( $this->sut, 'swap_customer' ) ) );
	}

	/**
	 * @testdox swap_customer replaces WC()->customer with a blank guest WC_Customer.
	 */
	public function test_swaps_to_guest_customer(): void {
		$admin_id = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		// Pre-seed the admin's saved billing profile — the leak we're guarding against
		// is exactly this data ending up on the order via WC()->customer.
		update_user_meta( $admin_id, 'billing_first_name', 'LeakedCashier' );
		update_user_meta( $admin_id, 'billing_email', 'leaked-cashier@example.com' );

		// Start with WC()->customer set to the admin's customer (the state
		// initialize_cart would otherwise produce).
		wp_set_current_user( $admin_id );
		WC()->customer = new WC_Customer( $admin_id );
		$this->assertSame( 'LeakedCashier', WC()->customer->get_billing_first_name() );

		// CustomerSwap's contract assumes CurrentUserSwap has already run for
		// the request — by the time we get here current_user is 0, so the
		// session data store's set_defaults will not populate billing_email
		// from wp_get_current_user(). Mirror that order here.
		wp_set_current_user( 0 );

		$this->sut->swap_customer( null );

		$this->assertInstanceOf( WC_Customer::class, WC()->customer );
		$this->assertSame( 0, WC()->customer->get_id(), 'POS customer must be a guest (id 0).' );
		$this->assertSame(
			'',
			WC()->customer->get_billing_first_name(),
			'POS customer must not expose the cashier saved billing profile.'
		);
		$this->assertSame( '', WC()->customer->get_billing_email() );
		$this->assertSame(
			'',
			WC()->customer->get_billing_country(),
			'CustomerSwap must strip the store-base country default applied by set_defaults.'
		);
		$this->assertSame( '', WC()->customer->get_billing_state() );
		$this->assertSame( '', WC()->customer->get_shipping_country() );
		$this->assertSame( '', WC()->customer->get_shipping_state() );

		wp_delete_user( $admin_id );
	}

	/**
	 * @testdox swap_customer returns the incoming dispatch result unchanged.
	 */
	public function test_returns_dispatch_result_unchanged(): void {
		$sentinel = new \WP_REST_Response( array( 'sentinel' => true ), 200 );

		$result = $this->sut->swap_customer( $sentinel );

		$this->assertSame( $sentinel, $result );
	}
}
