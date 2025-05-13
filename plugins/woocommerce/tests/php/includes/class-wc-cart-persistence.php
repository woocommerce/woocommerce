<?php
declare(strict_types=1);

use Automattic\WooCommerce\Tests\Blocks\StoreApi\MockSessionHandler;

/**
 * Class WC_Cart_Persistence_Test
 */
class WC_Cart_Persistence_Test extends \WC_Unit_Test_Case {
	/**
	 * @var int
	 */
	private $user_id;
	/**
	 * @var WC_Product
	 */
	private $product;

	/**
	 * Setup test user and product.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->user_id = wp_create_user( 'persist_user', 'password', 'persist_user@example.com' );
		$this->product = WC_Helper_Product::create_simple_product();
		WC()->cart->empty_cart();
		wp_set_current_user( 0 ); // Start as guest.
	}

	/**
	 * Cleanup product and cart.
	 */
	public function tearDown(): void {
		parent::tearDown();
		if ( $this->product ) {
			$this->product->delete( true );
		}
		WC()->cart->empty_cart();
		wp_set_current_user( 0 );
	}

	/**
	 * Switches a user and simulates session load/save.
	 *
	 * @param int $user_id User ID to switch to.
	 */
	private function simulate_user_switch( $user_id ) {
		WC()->session->save_data();
		if ( get_current_user_id() ) {
			wp_logout();
		}
		wp_set_current_user( $user_id );
		WC()->session->init_session_cookie();
		$cart_session = new WC_Cart_Session( WC()->cart );
		$cart_session->get_cart_from_session();
	}

	/**
	 * Cart persists across logout/login for a user.
	 */
	public function test_cart_persists_across_logout_login() {
		$this->simulate_user_switch( $this->user_id );
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->cart->calculate_totals();
		$cart_before = WC()->cart->get_cart();

		// Save session and log out.
		$this->simulate_user_switch( 0 );
		$this->assertEmpty( WC()->cart->get_cart() );

		// Log in again.
		$this->simulate_user_switch( $this->user_id );
		$cart_after = WC()->cart->get_cart();

		$this->assertEquals( $cart_before, $cart_after );
	}

	/**
	 * Guest cart is preserved after login if not empty.
	 */
	public function test_guest_cart_preserved_on_login_if_not_empty() {
		// We need to replace the WC_Session with a mock because this test relies on cookies being set which
		// is not easy with PHPUnit. This is a simpler approach.
		$old_session  = WC()->session;
		WC()->session = new MockSessionHandler();
		WC()->session->init();

		WC()->cart->empty_cart();

		// User adds item A.
		$this->simulate_user_switch( $this->user_id );
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->cart->calculate_totals();

		// Log out, as guest add item B.
		$this->simulate_user_switch( 0 );
		$guest_product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $guest_product->get_id(), 1 );
		WC()->cart->calculate_totals();
		$guest_cart = WC()->cart->get_cart();

		// Log in again.
		$this->simulate_user_switch( $this->user_id );
		$cart_after = WC()->cart->get_cart();
		$this->assertEquals( $guest_cart, $cart_after );

		$guest_product->delete( true );
		WC()->session = $old_session;
	}

	/**
	 * If guest cart is empty, user cart is restored.
	 */
	public function test_user_cart_restored_if_guest_cart_empty() {
		WC()->cart->empty_cart();

		// User adds item A.
		$this->simulate_user_switch( $this->user_id );
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->cart->calculate_totals();
		$user_cart = WC()->cart->get_cart();

		// Log out, as guest add item B, then empty cart.
		$this->simulate_user_switch( 0 );
		$guest_product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $guest_product->get_id(), 1 );
		WC()->cart->calculate_totals();
		WC()->cart->empty_cart();
		$this->assertCount( 0, WC()->cart->get_cart() );

		// Log in again.
		$this->simulate_user_switch( $this->user_id );
		$cart_after = WC()->cart->get_cart();

		$this->assertEquals( $user_cart, $cart_after );
		$guest_product->delete( true );
	}
}
