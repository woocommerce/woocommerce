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
	public function test_cart_merged_on_login_if_not_empty() {
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
	 * Guest cart is preserved if logging in and there is no user cart persisted.
	 */
	public function test_guest_cart_persists_on_login_if_user_cart_is_empty() {
		WC()->cart->empty_cart();

		// User adds item A then removes everything so they have no cart.
		$this->simulate_user_switch( $this->user_id );
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->cart->calculate_totals();
		WC()->cart->empty_cart();
		$this->assertCount( 0, WC()->cart->get_cart() );

		// Log out, as guest add item B.
		$this->simulate_user_switch( 0 );
		$guest_product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $guest_product->get_id(), 1 );
		WC()->cart->calculate_totals();
		$guest_cart = WC()->cart->get_cart();

		// Log in again and confirm cart still has guest cart.
		$this->simulate_user_switch( $this->user_id );
		$cart_after = WC()->cart->get_cart();
		$this->assertEquals( $cart_after, $guest_cart );

		$guest_product->delete( true );
	}

	/**
	 * If guest cart is empty, user cart is restored.
	 */
	public function test_cart_restored_on_login_if_empty() {
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
	 * Test that different items in cart get merged so both items are in the final cart.
	 */
	public function test_different_items_merged_correctly() {
		WC()->cart->empty_cart();

		// User adds product A.
		$this->simulate_user_switch( $this->user_id );
		WC()->cart->add_to_cart( $this->product->get_id(), 2 );
		WC()->cart->calculate_totals();
		$user_cart = WC()->cart->get_cart();

		// Log out, as guest add different product B.
		$this->simulate_user_switch( 0 );
		$guest_product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $guest_product->get_id(), 3 );
		WC()->cart->calculate_totals();
		$guest_cart = WC()->cart->get_cart();

		// Log in again and verify both items are present.
		$this->simulate_user_switch( $this->user_id );
		$cart_after = WC()->cart->get_cart();

		// Check that both products are in the final cart.
		$this->assertCount( 2, $cart_after );

		// Verify user's product is present with correct quantity.
		$user_product_found  = false;
		$guest_product_found = false;

		foreach ( $cart_after as $cart_item ) {
			if ( $cart_item['product_id'] === $this->product->get_id() ) {
				$this->assertEquals( 2, $cart_item['quantity'] );
				$user_product_found = true;
			} elseif ( $cart_item['product_id'] === $guest_product->get_id() ) {
				$this->assertEquals( 3, $cart_item['quantity'] );
				$guest_product_found = true;
			}
		}

		$this->assertTrue( $user_product_found, 'User product should be in merged cart' );
		$this->assertTrue( $guest_product_found, 'Guest product should be in merged cart' );

		$guest_product->delete( true );
	}

	/**
	 * Test cart merge with overlapping items - Cart 1 has items A and B, Cart 2 has items B and C.
	 * End result should be a cart with items A, B, and C (with B quantity from guest cart).
	 */
	public function test_cart_merge_with_overlapping_items() {
		WC()->cart->empty_cart();

		// User adds product A (quantity 2) and product B (quantity 1).
		$this->simulate_user_switch( $this->user_id );
		$product_b = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $this->product->get_id(), 2 );
		WC()->cart->add_to_cart( $product_b->get_id(), 1 );
		WC()->cart->calculate_totals();

		// Log out, as guest add product B (quantity 3) and product C (quantity 2).
		$this->simulate_user_switch( 0 );
		$product_c = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product_b->get_id(), 3 );
		WC()->cart->add_to_cart( $product_c->get_id(), 2 );
		WC()->cart->calculate_totals();

		// Log in again and verify merge behavior.
		$this->simulate_user_switch( $this->user_id );
		$cart_after = WC()->cart->get_cart();

		// Should have 3 items: A, B (guest quantity), and C.
		$this->assertCount( 3, $cart_after );

		$product_a_quantity = 0;
		$product_b_quantity = 0;
		$product_c_quantity = 0;

		foreach ( $cart_after as $cart_item ) {
			if ( $cart_item['product_id'] === $this->product->get_id() ) {
				$product_a_quantity = $cart_item['quantity'];
			} elseif ( $cart_item['product_id'] === $product_b->get_id() ) {
				$product_b_quantity = $cart_item['quantity'];
			} elseif ( $cart_item['product_id'] === $product_c->get_id() ) {
				$product_c_quantity = $cart_item['quantity'];
			}
		}

		// Verify quantities are correct - guest cart quantities take precedence.
		$this->assertEquals( 2, $product_a_quantity, 'Product A should have quantity 2' );
		$this->assertEquals( 3, $product_b_quantity, 'Product B should use guest cart quantity (3)' );
		$this->assertEquals( 2, $product_c_quantity, 'Product C should have quantity 2' );

		$product_b->delete( true );
		$product_c->delete( true );
	}
}
