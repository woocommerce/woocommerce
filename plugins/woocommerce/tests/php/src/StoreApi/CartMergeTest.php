<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\StoreApi;

use Automattic\WooCommerce\StoreApi\SessionHandler;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use WC_Cart_Session;
use WC_Helper_Product;
use WC_Session;
use WC_Unit_Test_Case;

/**
 * Tests for guest/saved cart merge behaviour on login.
 *
 * The classic cookie flow (WC_Cart_Session::get_cart_from_session) merges carts via the
 * _woocommerce_load_saved_cart_after_login user meta set by the wp_login hook. Headless/token
 * logins (JWT, OAuth, custom) never fire wp_login, so SessionHandler merges the guest cart
 * token into the user's session itself. See https://github.com/woocommerce/woocommerce/issues/55653.
 *
 * The `test_classic_*` tests pin the canonical one-shot semantics; the `test_store_api_*` tests
 * cover the equivalent behaviour for the Store API token flow.
 */
class CartMergeTest extends WC_Unit_Test_Case {

	/**
	 * Session handler in place before the test swapped it.
	 *
	 * @var WC_Session
	 */
	private $original_session;

	/**
	 * Logged-in user used across the merge scenarios.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Product stored in the user's saved (persistent) cart.
	 *
	 * @var \WC_Product
	 */
	private $saved_product;

	/**
	 * Product stored in the guest session cart.
	 *
	 * @var \WC_Product
	 */
	private $guest_product;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_session = WC()->session;
		$this->user_id          = $this->factory->user->create( array( 'role' => 'customer' ) );
		$this->saved_product    = WC_Helper_Product::create_simple_product();
		$this->guest_product    = WC_Helper_Product::create_simple_product();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		WC()->cart->empty_cart();
		WC()->session = $this->original_session;
		unset( $_SERVER['HTTP_CART_TOKEN'] );
		wp_set_current_user( 0 );
		delete_user_meta( $this->user_id, '_woocommerce_load_saved_cart_after_login' );

		parent::tearDown();
	}

	/**
	 * @testdox Classic login merges the guest cart and the saved cart into one cart.
	 */
	public function test_classic_login_merges_guest_and_saved_cart(): void {
		$this->save_persistent_cart( array( $this->saved_product->get_id() => 1 ) );
		WC()->session->set( 'cart', $this->build_cart_array( array( $this->guest_product->get_id() => 1 ) ) );

		$this->log_in_via_wp_login();

		$contents = $this->load_cart();

		$this->assertArrayHasKey( $this->saved_product->get_id(), $contents, 'Saved cart item should be merged in after login.' );
		$this->assertArrayHasKey( $this->guest_product->get_id(), $contents, 'Guest cart item should survive the merge.' );
	}

	/**
	 * @testdox Classic merge is one-shot and does not resurrect a removed item on the next load.
	 */
	public function test_classic_merge_is_one_shot(): void {
		$this->save_persistent_cart( array( $this->saved_product->get_id() => 1 ) );
		WC()->session->set( 'cart', $this->build_cart_array( array( $this->guest_product->get_id() => 1 ) ) );
		$this->log_in_via_wp_login();

		$merged = $this->load_cart();
		$this->assertArrayHasKey( $this->saved_product->get_id(), $merged, 'Saved cart item should be merged in on first load.' );

		// User removes the item that came from the saved cart, then a second request loads the cart again.
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $this->guest_product->get_id(), 1 );
		WC()->session->set( 'cart', ( new WC_Cart_Session( WC()->cart ) )->get_cart_for_session() );

		$contents = $this->load_cart();

		$this->assertArrayNotHasKey( $this->saved_product->get_id(), $contents, 'Removed saved-cart item must not reappear on reload (merge is one-shot).' );
		$this->assertArrayHasKey( $this->guest_product->get_id(), $contents, 'Guest cart item should remain after reload.' );
	}

	/**
	 * @testdox When the same product is in both carts the session quantity wins rather than summing.
	 */
	public function test_classic_merge_session_quantity_wins_on_collision(): void {
		$this->save_persistent_cart( array( $this->saved_product->get_id() => 1 ) );
		WC()->session->set( 'cart', $this->build_cart_array( array( $this->saved_product->get_id() => 3 ) ) );
		$this->log_in_via_wp_login();

		$contents = $this->load_cart();

		$this->assertArrayHasKey( $this->saved_product->get_id(), $contents, 'Product present in both carts should appear once.' );
		$this->assertSame( 3, $contents[ $this->saved_product->get_id() ], 'Session quantity should win on collision, not be summed with the saved quantity.' );
	}

	/**
	 * @testdox Token login merges the guest cart token into the user's saved cart (issue #55653).
	 */
	public function test_store_api_token_login_merges_guest_and_saved_cart(): void {
		$this->save_persistent_cart( array( $this->saved_product->get_id() => 1 ) );
		$this->start_store_api_guest_request( array( $this->guest_product->get_id() => 1 ) );

		// Authenticated as the user via a token (e.g. JWT determine_current_user) — wp_login never fires.
		wp_set_current_user( $this->user_id );
		$this->dispatch_store_api_request();

		$contents = $this->load_cart();

		$this->assertArrayHasKey( $this->saved_product->get_id(), $contents, 'Saved cart item should be merged in for a token login.' );
		$this->assertArrayHasKey( $this->guest_product->get_id(), $contents, 'Guest cart token item should survive the merge.' );
	}

	/**
	 * @testdox Token-login merge is one-shot and does not resurrect a removed item on the next load.
	 */
	public function test_store_api_token_merge_is_one_shot(): void {
		$this->save_persistent_cart( array( $this->saved_product->get_id() => 1 ) );
		$this->start_store_api_guest_request( array( $this->guest_product->get_id() => 1 ) );
		wp_set_current_user( $this->user_id );

		$this->dispatch_store_api_request();
		$merged = $this->load_cart();
		$this->assertArrayHasKey( $this->saved_product->get_id(), $merged, 'Saved cart item should be merged in on first load for a token login.' );

		// User removes the merged-in saved item, leaving just the guest item, and the session is persisted.
		WC()->session->set( 'cart', $this->build_cart_array( array( $this->guest_product->get_id() => 1 ) ) );
		WC()->session->save_data();

		// A second request still carries the now-stale guest token.
		$this->dispatch_store_api_request();
		$contents = $this->load_cart();

		$this->assertArrayNotHasKey( $this->saved_product->get_id(), $contents, 'Removed saved-cart item must not reappear on reload for token logins.' );
		$this->assertArrayHasKey( $this->guest_product->get_id(), $contents, 'Guest cart token item should remain after reload.' );
	}

	/**
	 * @testdox A user-scoped cart token from another user is never treated as a guest cart.
	 */
	public function test_store_api_ignores_a_user_scoped_token_for_merge(): void {
		$this->save_persistent_cart( array( $this->saved_product->get_id() => 1 ) );

		// A token scoped to a different user (a numeric id, not a t_ guest session).
		$other_user_id = (string) $this->factory->user->create( array( 'role' => 'customer' ) );
		$this->seed_session( $other_user_id, array( $this->guest_product->get_id() => 1 ) );
		$_SERVER['HTTP_CART_TOKEN'] = CartTokenUtils::get_cart_token( $other_user_id );

		// Authenticated as our user, presenting the other user's token.
		wp_set_current_user( $this->user_id );
		$this->dispatch_store_api_request();
		$this->load_cart();

		$foreign_session = ( new SessionHandler() )->get_session( $other_user_id, array() );
		$this->assertNotEmpty( $foreign_session, 'A user-scoped token must not be consumed (deleted) as if it were a guest cart.' );
		$this->assertEmpty(
			get_user_meta( (int) $this->user_id, '_woocommerce_load_saved_cart_after_login', true ),
			'No saved-cart merge should be triggered for a user-scoped token.'
		);
	}

	/**
	 * Persist the given items as the user's saved (persistent) cart.
	 *
	 * @param array<int,int> $items Map of product ID to quantity.
	 */
	private function save_persistent_cart( array $items ): void {
		// Write the persistent-cart meta directly in the same shape persistent_cart_update() stores.
		// Going via the cart + empty_cart() would trigger the persistent-cart-destroy hook and wipe it.
		update_user_meta(
			$this->user_id,
			'_woocommerce_persistent_cart_' . get_current_blog_id(),
			array( 'cart' => $this->build_cart_array( $items ) )
		);
	}

	/**
	 * Build a session-format cart array for the given items.
	 *
	 * @param array<int,int> $items Map of product ID to quantity.
	 * @return array
	 */
	private function build_cart_array( array $items ): array {
		// Build while logged out so empty_cart() cannot fire the persistent-cart-destroy hook
		// and wipe a saved cart belonging to the current user.
		$previous = get_current_user_id();
		wp_set_current_user( 0 );

		WC()->cart->empty_cart();
		foreach ( $items as $product_id => $quantity ) {
			WC()->cart->add_to_cart( $product_id, $quantity );
		}
		$cart_array = ( new WC_Cart_Session( WC()->cart ) )->get_cart_for_session();
		WC()->cart->empty_cart();

		wp_set_current_user( $previous );

		return $cart_array;
	}

	/**
	 * Persist a guest Store API session in the DB and set the matching Cart-Token header.
	 *
	 * @param array<int,int> $items Map of product ID to quantity for the guest cart.
	 */
	private function start_store_api_guest_request( array $items ): void {
		// Session keys are stored in a char(32) column, so keep the guest id within 32 chars
		// or the truncated stored key won't match the id carried in the token.
		$guest_id = substr( 't_' . wc_rand_hash(), 0, 32 );

		$this->seed_session( $guest_id, $items );
		$_SERVER['HTTP_CART_TOKEN'] = CartTokenUtils::get_cart_token( $guest_id );
	}

	/**
	 * Persist a Store API session row in the DB under the given key with the given cart.
	 *
	 * @param string         $session_id Session key (guest t_... id or user id).
	 * @param array<int,int> $items      Map of product ID to quantity.
	 */
	private function seed_session( string $session_id, array $items ): void {
		$handler = new SessionHandler();
		$this->set_protected_property( $handler, '_customer_id', $session_id );
		$this->set_protected_property( $handler, 'session_expiration', time() + DAY_IN_SECONDS );
		$handler->set( 'cart', $this->build_cart_array( $items ) );
		$handler->save_data();
	}

	/**
	 * Boot a fresh Store API SessionHandler for the current Cart-Token header, as a request would.
	 */
	private function dispatch_store_api_request(): void {
		WC()->session = new SessionHandler();
		WC()->session->init();
	}

	/**
	 * Set a protected property on an object via reflection.
	 *
	 * @param object $instance Target object.
	 * @param string $name     Property name.
	 * @param mixed  $value    Value to set.
	 */
	private function set_protected_property( object $instance, string $name, $value ): void {
		$property = new \ReflectionProperty( $instance, $name );
		$property->setAccessible( true );
		$property->setValue( $instance, $value );
	}

	/**
	 * Simulate a classic login by setting the current user and the merge flag wp_login would set.
	 */
	private function log_in_via_wp_login(): void {
		wp_set_current_user( $this->user_id );
		update_user_meta( $this->user_id, '_woocommerce_load_saved_cart_after_login', 1 );
	}

	/**
	 * Load the cart from the session and return a map of product ID to total quantity.
	 *
	 * @return array<int,int>
	 */
	private function load_cart(): array {
		// Note: do not empty_cart() here — that also clears the session 'cart' key we are loading from.
		( new WC_Cart_Session( WC()->cart ) )->get_cart_from_session();

		$contents = array();
		foreach ( WC()->cart->get_cart_contents() as $item ) {
			$contents[ $item['product_id'] ] = ( $contents[ $item['product_id'] ] ?? 0 ) + $item['quantity'];
		}

		return $contents;
	}
}
