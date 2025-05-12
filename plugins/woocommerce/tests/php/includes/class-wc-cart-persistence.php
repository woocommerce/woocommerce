<?php
declare(strict_types=1);

use Automattic\WooCommerce\Internal\Cart\SavedCart;

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
	 * @var string|null
	 */
	private $cart_item_key;

	/**
	 * Setup test user, product, and saved cart.
	 */
	public function setUp(): void {
		parent::setUp();
		// Create a user and a product for testing.
		$this->user_id = wp_create_user( 'persist_user', 'password', 'persist_user@example.com' );
		$this->product = WC_Helper_Product::create_simple_product();
		// Pre-populate a saved cart for the user.
		$saved_cart = array(
			'key1' => array(
				'product_id'   => $this->product->get_id(),
				'variation_id' => 0,
				'variation'    => array(),
				'quantity'     => 2,
				'line_total'   => 20,
				'data_hash'    => wc_get_cart_item_data_hash( $this->product ),
			),
		);
		SavedCart::update_saved_cart( $this->user_id, $saved_cart );
		WC()->cart->empty_cart();
		wp_set_current_user( 0 ); // Start as guest.
	}

	/**
	 * Cleanup persistent cart, product, and cart.
	 */
	public function tearDown(): void {
		parent::tearDown();
		SavedCart::delete_saved_cart( $this->user_id );
		if ( $this->product ) {
			$this->product->delete( true );
		}
		WC()->cart->empty_cart();
		wp_set_current_user( 0 );
	}

	/**
	 * For a logged out user with a cart, after logging in the session customer ID is migrated but the saved cart does not overwrite the cart contents.
	 */
	public function test_guest_cart_not_overwritten_by_saved_cart_on_login() {
		// Guest adds item to cart.
		WC()->cart->empty_cart();
		$guest_product = WC_Helper_Product::create_simple_product();
		$guest_key     = WC()->cart->add_to_cart( $guest_product->get_id(), 1 );
		WC()->cart->calculate_totals();
		$this->assertCount( 1, WC()->cart->get_cart() );
		// Simulate login and session migration.
		wp_set_current_user( $this->user_id );
		/**
		 * Simulate WooCommerce guest session migration to user session.
		 *
		 * @since 1.0.0
		 */
		do_action( 'woocommerce_guest_session_to_user_id', 't_foo', $this->user_id );
		WC()->cart->get_cart_from_session();
		// Cart should still have the guest item, not the saved cart.
		$cart = WC()->cart->get_cart();
		$this->assertCount( 1, $cart );
		$this->assertArrayHasKey( $guest_key, $cart );
		$guest_product->delete( true );
	}

	/**
	 * For a logged out user without a cart, after logging in the saved cart replaces the current cart.
	 */
	public function test_guest_cart_empty_replaced_by_saved_cart_on_login() {
		// Guest cart is empty.
		WC()->cart->empty_cart();
		$this->assertCount( 0, WC()->cart->get_cart() );
		// Simulate login and session migration.
		wp_set_current_user( $this->user_id );
		/**
		 * Simulate WooCommerce guest session migration to user session.
		 *
		 * @since 1.0.0
		 */
		do_action( 'woocommerce_guest_session_to_user_id', 't_foo', $this->user_id );
		WC()->cart->get_cart_from_session();
		// Cart should now match the saved cart.
		$cart = WC()->cart->get_cart();
		$this->assertCount( 1, $cart );
		$first = reset( $cart );
		$this->assertEquals( $this->product->get_id(), $first['product_id'] );
	}

	/**
	 * When adding an item to the cart, the saved cart is updated.
	 */
	public function test_saved_cart_updated_on_add_to_cart() {
		wp_set_current_user( $this->user_id );
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->cart->calculate_totals();
		// Triggers persistent_cart_update via hooks.
		$saved = SavedCart::get_saved_cart( $this->user_id );
		$this->assertNotEmpty( $saved );
		$found = false;
		foreach ( $saved as $item ) {
			if ( $item['product_id'] === $this->product->get_id() ) {
				$found = true;
			}
		}
		$this->assertTrue( $found );
	}

	/**
	 * When removing an item from the cart, the saved cart is updated.
	 */
	public function test_saved_cart_updated_on_remove_from_cart() {
		wp_set_current_user( $this->user_id );
		WC()->cart->empty_cart();
		$key = WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->cart->calculate_totals();
		// Remove item.
		WC()->cart->remove_cart_item( $key );
		WC()->cart->calculate_totals();
		$saved = SavedCart::get_saved_cart( $this->user_id );
		$this->assertEmpty( $saved );
	}

	/**
	 * This use case is fixed @https://github.com/woocommerce/woocommerce/issues/26374
	 * Custom cart data is persisted to the saved cart when update_cart_action is triggered.
	 */
	public function test_update_cart_action_cart_updated_persists_custom_cart_data() {
		wp_set_current_user( $this->user_id );
		WC()->cart->empty_cart();
		$key = WC()->cart->add_to_cart( $this->product->get_id(), 1 );

		// Simulate update_cart_action with filter.
		add_filter(
			'woocommerce_update_cart_action_cart_updated',
			function ( $cart_updated ) {
				$cart = WC()->cart->get_cart();
				foreach ( $cart as $key => $item ) {
					$cart[ $key ]['custom_key'] = 'custom_value';
				}
				WC()->cart->set_cart_contents( $cart );
				return true;
			},
			10,
			1
		);

		/**
		 * Simulate update event.
		 *
		 * @since 1.0.0
		 */
		$cart_updated = apply_filters( 'woocommerce_update_cart_action_cart_updated', false );
		if ( $cart_updated ) {
			WC()->cart->calculate_totals();
		}

		$saved = SavedCart::get_saved_cart( $this->user_id );
		$found = false;
		foreach ( $saved as $item ) {
			if ( isset( $item['custom_key'] ) && 'custom_value' === $item['custom_key'] ) {
				$found = true;
			}
		}
		$this->assertTrue( $found, 'Custom cart data was not persisted to the saved cart.' );
	}

	/**
	 * Saved cart is isolated between users.
	 */
	public function test_saved_cart_is_isolated_between_users() {
		$user1    = wp_create_user( 'persist_user1', 'password', 'persist_user1@example.com' );
		$user2    = wp_create_user( 'persist_user2', 'password', 'persist_user2@example.com' );
		$product1 = WC_Helper_Product::create_simple_product();
		$product2 = WC_Helper_Product::create_simple_product();

		// User 1 adds product1 to cart and saves.
		wp_set_current_user( $user1 );
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product1->get_id(), 1 );
		WC()->cart->calculate_totals();
		$saved1 = SavedCart::get_saved_cart( $user1 );
		$this->assertNotEmpty( $saved1 );

		// User 2 adds product2 to cart and saves.
		wp_set_current_user( $user2 );
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product2->get_id(), 1 );
		WC()->cart->calculate_totals();
		$saved2 = SavedCart::get_saved_cart( $user2 );
		$this->assertNotEmpty( $saved2 );

		// Check isolation.
		$this->assertNotEquals( $saved1, $saved2 );
		$this->assertEquals( $product1->get_id(), reset( $saved1 )['product_id'] );
		$this->assertEquals( $product2->get_id(), reset( $saved2 )['product_id'] );

		// Cleanup.
		SavedCart::delete_saved_cart( $user1 );
		SavedCart::delete_saved_cart( $user2 );
		$product1->delete( true );
		$product2->delete( true );
	}

	/**
	 * Test that empty_cart(true) clears the saved cart, and empty_cart(false) does not.
	 */
	public function test_empty_cart_clears_or_preserves_saved_cart() {
		wp_set_current_user( $this->user_id );
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->cart->calculate_totals();
		// Confirm saved cart exists.
		$this->assertNotEmpty( SavedCart::get_saved_cart( $this->user_id ) );

		// empty_cart(false) should NOT clear saved cart.
		WC()->cart->empty_cart( false );
		$this->assertNotEmpty( SavedCart::get_saved_cart( $this->user_id ) );

		// Add again, then empty_cart(true) should clear saved cart.
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->cart->calculate_totals();
		$this->assertNotEmpty( SavedCart::get_saved_cart( $this->user_id ) );
		WC()->cart->empty_cart( true );
		$this->assertEmpty( SavedCart::get_saved_cart( $this->user_id ) );
	}
}
