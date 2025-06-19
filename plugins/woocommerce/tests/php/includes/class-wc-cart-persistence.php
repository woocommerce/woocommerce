<?php
declare(strict_types=1);

// Include the mock session handler.
require_once __DIR__ . '/class-wc-mock-cart-persistence-session-handler.php';

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
	 * @var WC_Session_Handler
	 */
	private $old_session_handler;

	/**
	 * Setup test user and product.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->user_id = wp_create_user( 'persist_user', 'password', 'persist_user@example.com' );
		$this->product = WC_Helper_Product::create_simple_product();
		WC()->cart->empty_cart();
		wp_set_current_user( 0 ); // Start as guest.
		$this->old_session_handler = WC()->session;
		WC()->session              = new WC_Mock_Cart_Persistence_Session_Handler();
		WC()->session->init();
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
		wp_delete_user( $this->user_id );
		WC()->session = $this->old_session_handler;
	}

	/**
	 * Switches a user and simulates session load/save.
	 *
	 * @param int $user_id User ID to switch to.
	 */
	private function simulate_user_switch( $user_id ) {
		WC()->session->set_customer_session_cookie( true );
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
	 * Guest cart is merged with user cart after login if not empty.
	 */
	public function test_guest_cart_merged_on_login_if_not_empty() {
		WC()->cart->empty_cart();

		// User adds item A.
		$this->simulate_user_switch( $this->user_id );
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->cart->calculate_totals();
		$user_cart = WC()->cart->get_cart();

		// Log out, as guest add item B.
		$this->simulate_user_switch( 0 );
		$guest_product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $guest_product->get_id(), 1 );
		WC()->cart->calculate_totals();
		$guest_cart = WC()->cart->get_cart();

		// Log in again.
		$this->simulate_user_switch( $this->user_id );
		$cart_after = WC()->cart->get_cart();
		$this->assertEquals( $cart_after, array_merge( $guest_cart, $user_cart ) );

		$guest_product->delete( true );
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

	/**
	 * Cart data is not merged when WOOCOMMERCE_CHECKOUT constant is defined.
	 */
	public function test_cart_data_not_merged_when_woocommerce_checkout_constant_defined() {
		WC()->cart->empty_cart();

		// User adds item A.
		$this->simulate_user_switch( $this->user_id );
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->cart->calculate_totals();
		$user_cart = WC()->cart->get_cart();

		// Log out, as guest add item B.
		$this->simulate_user_switch( 0 );
		$guest_product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $guest_product->get_id(), 1 );
		WC()->cart->calculate_totals();
		$guest_cart = WC()->cart->get_cart();

		// Define WOOCOMMERCE_CHECKOUT constant before login.
		define( 'WOOCOMMERCE_CHECKOUT', true );

		// Log in again - should not merge carts due to WOOCOMMERCE_CHECKOUT constant.
		$this->simulate_user_switch( $this->user_id );
		$cart_after = WC()->cart->get_cart();

		// Should only have guest items when WOOCOMMERCE_CHECKOUT is defined.
		$this->assertEquals( $guest_cart, $cart_after );
		$this->assertNotEquals( $user_cart, $cart_after );

		$guest_product->delete( true );
	}
}
