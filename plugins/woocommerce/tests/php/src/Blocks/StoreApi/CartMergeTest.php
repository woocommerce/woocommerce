<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi;

use Automattic\WooCommerce\StoreApi\SessionHandler;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use WC_Cache_Helper;
use WC_Cart_Session;
use WC_Helper_Product;
use WC_Session;
use WC_Unit_Test_Case;

/**
 * Tests for guest/saved cart merge behaviour on login.
 *
 * The classic cookie flow (WC_Cart_Session::get_cart_from_session) merges carts via the
 * _woocommerce_load_saved_cart_after_login user meta set by the wp_login hook. Headless/token
 * logins (JWT, OAuth, custom) never fire wp_login, so SessionHandler moves the guest session
 * to the user and sets that flag itself. See https://github.com/woocommerce/woocommerce/issues/55653.
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
		try {
			// The parent empties the cart, resets the current user and rolls back user meta.
			WC()->session = $this->original_session;
			unset( $_SERVER['HTTP_CART_TOKEN'] );
		} finally {
			parent::tearDown();
		}
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
		// The saved cart still holds the item, so only a consumed merge flag can keep it out. Avoid
		// empty_cart() here: logged in, it also wipes the saved cart and the assertion could never fail.
		$this->save_persistent_cart( array( $this->saved_product->get_id() => 1 ) );
		WC()->session->set( 'cart', $this->build_cart_array( array( $this->guest_product->get_id() => 1 ) ) );

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
	 * @testdox Token login writes the merged cart back to the user's saved cart.
	 */
	public function test_store_api_token_login_updates_saved_cart(): void {
		$this->save_persistent_cart( array( $this->saved_product->get_id() => 1 ) );
		$this->start_store_api_guest_request( array( $this->guest_product->get_id() => 1 ) );
		wp_set_current_user( $this->user_id );

		$this->dispatch_store_api_request();
		$this->load_cart();

		$saved_cart  = get_user_meta( $this->user_id, '_woocommerce_persistent_cart_' . get_current_blog_id(), true );
		$product_ids = array_column( $saved_cart['cart'] ?? array(), 'product_id' );
		$this->assertContains( $this->guest_product->get_id(), $product_ids, 'Guest item should be saved so it survives the session expiring.' );
		$this->assertContains( $this->saved_product->get_id(), $product_ids, 'Saved item should stay in the saved cart.' );
	}

	/**
	 * @testdox Token login keeps items already in the user's session cart.
	 */
	public function test_store_api_token_login_keeps_user_session_cart(): void {
		$session_product = WC_Helper_Product::create_simple_product();
		$this->seed_session( (string) $this->user_id, array( $session_product->get_id() => 1 ) );
		$this->start_store_api_guest_request( array( $this->guest_product->get_id() => 1 ) );
		wp_set_current_user( $this->user_id );

		$this->dispatch_store_api_request();
		$contents = $this->load_cart();

		$this->assertArrayHasKey( $session_product->get_id(), $contents, 'Item in the user session cart should survive the merge.' );
		$this->assertArrayHasKey( $this->guest_product->get_id(), $contents, 'Guest cart token item should survive the merge.' );
	}

	/**
	 * @testdox When the same product is in the user session and guest carts the guest quantity wins.
	 */
	public function test_store_api_token_merge_guest_quantity_wins_on_collision(): void {
		$this->seed_session( (string) $this->user_id, array( $this->saved_product->get_id() => 1 ) );
		$this->start_store_api_guest_request( array( $this->saved_product->get_id() => 3 ) );
		wp_set_current_user( $this->user_id );

		$this->dispatch_store_api_request();
		$contents = $this->load_cart();

		$this->assertArrayHasKey( $this->saved_product->get_id(), $contents, 'Product present in both carts should appear once.' );
		$this->assertSame( 3, $contents[ $this->saved_product->get_id() ], 'Guest quantity should win on collision, not be summed.' );
	}

	/**
	 * @testdox When the session starts without loading the cart, the saved cart merges once on the next cart load.
	 */
	public function test_store_api_token_merge_waits_for_cart_load(): void {
		$this->save_persistent_cart( array( $this->saved_product->get_id() => 1 ) );
		$this->start_store_api_guest_request( array( $this->guest_product->get_id() => 1 ) );
		wp_set_current_user( $this->user_id );

		// E.g. a products request: the session starts (QuantityLimits reads the draft order) but the cart never loads.
		$this->dispatch_store_api_request();

		// The next request still carries the guest token; product responses don't rotate it.
		$this->dispatch_store_api_request();
		$merged = $this->load_cart();

		$this->assertArrayHasKey( $this->saved_product->get_id(), $merged, 'Saved cart item should merge on the first cart load.' );
		$this->assertArrayHasKey( $this->guest_product->get_id(), $merged, 'Guest cart token item should survive the merge.' );

		// User removes the saved item; the saved cart still holds it, so only a one-shot merge keeps it out.
		$this->save_persistent_cart( array( $this->saved_product->get_id() => 1 ) );
		WC()->session->set( 'cart', $this->build_cart_array( array( $this->guest_product->get_id() => 1 ) ) );
		WC()->session->save_data();

		$this->dispatch_store_api_request();
		$contents = $this->load_cart();

		$this->assertArrayNotHasKey( $this->saved_product->get_id(), $contents, 'Saved cart must merge only once.' );
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
	 * @testdox Token login migrates the rest of the guest session (coupons, shipping, draft order) to the user.
	 */
	public function test_store_api_token_login_migrates_guest_session_data(): void {
		$this->start_store_api_guest_request(
			array( $this->guest_product->get_id() => 1 ),
			array(
				'applied_coupons'         => array( 'guestcoupon' ),
				'chosen_shipping_methods' => array( 'flat_rate:1' ),
				'store_api_draft_order'   => 123,
			)
		);
		$this->seed_session( (string) $this->user_id, array(), array( 'store_api_draft_order' => 456 ) );
		wp_set_current_user( $this->user_id );

		$this->dispatch_store_api_request();

		$this->assertSame( (string) $this->user_id, WC()->session->get_customer_id(), 'The session should switch to the logged-in user.' );
		$this->assertSame( array( 'guestcoupon' ), WC()->session->get( 'applied_coupons' ), 'Guest applied coupons should carry across.' );
		$this->assertSame( array( 'flat_rate:1' ), WC()->session->get( 'chosen_shipping_methods' ), 'Guest chosen shipping methods should carry across.' );
		$this->assertSame( 123, WC()->session->get( 'store_api_draft_order' ), 'Guest draft order should win over the user session, as in the cookie flow.' );
	}

	/**
	 * @testdox Token login fires woocommerce_guest_session_to_user_id with the guest and user session IDs.
	 */
	public function test_store_api_token_login_fires_guest_session_to_user_id(): void {
		$guest_id = $this->start_store_api_guest_request( array( $this->guest_product->get_id() => 1 ) );
		wp_set_current_user( $this->user_id );

		$calls    = array();
		$callback = function ( $guest_session_id, $user_session_id ) use ( &$calls ) {
			$calls[] = array( $guest_session_id, $user_session_id );
		};
		add_action( 'woocommerce_guest_session_to_user_id', $callback, 10, 2 );

		$this->dispatch_store_api_request();

		$this->assertSame( array( array( $guest_id, (string) $this->user_id ) ), $calls );
	}

	/**
	 * @testdox Token login renews the session expiry instead of inheriting it from a near-expired guest token.
	 */
	public function test_store_api_token_login_renews_session_expiry(): void {
		$short_expiry = function () {
			return MINUTE_IN_SECONDS;
		};
		add_filter( 'wc_session_expiration', $short_expiry );
		$this->start_store_api_guest_request( array( $this->guest_product->get_id() => 1 ) );
		remove_filter( 'wc_session_expiration', $short_expiry );
		wp_set_current_user( $this->user_id );

		$this->dispatch_store_api_request();

		global $wpdb;
		$expiry = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT session_expiry FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", (string) $this->user_id )
		);

		$this->assertGreaterThan( time() + HOUR_IN_SECONDS, $expiry, 'The user session should get a fresh expiry, not the guest token\'s.' );
	}

	/**
	 * @testdox Token login invalidates the cookie handler's cached guest and user session rows.
	 */
	public function test_store_api_token_login_invalidates_cached_sessions(): void {
		$guest_id = $this->start_store_api_guest_request( array( $this->guest_product->get_id() => 1 ) );
		wp_set_current_user( $this->user_id );

		$prefix = WC_Cache_Helper::get_cache_prefix( WC_SESSION_CACHE_GROUP );
		wp_cache_set( $prefix . $this->user_id, array( 'cart' => 'stale' ), WC_SESSION_CACHE_GROUP );
		wp_cache_set( $prefix . $guest_id, array( 'cart' => 'stale' ), WC_SESSION_CACHE_GROUP );

		$this->dispatch_store_api_request();

		$this->assertFalse( wp_cache_get( $prefix . $this->user_id, WC_SESSION_CACHE_GROUP ), 'Cached user session should be invalidated after the merge is saved.' );
		$this->assertFalse( wp_cache_get( $prefix . $guest_id, WC_SESSION_CACHE_GROUP ), 'Cached guest session should be invalidated once it is consumed.' );
	}

	/**
	 * @testdox A user-scoped cart token from another user is never treated as a guest cart.
	 */
	public function test_store_api_ignores_a_user_scoped_token_for_merge(): void {
		// A token scoped to a different user (a numeric id, not a t_ guest session).
		$other_user_id = (string) $this->factory->user->create( array( 'role' => 'customer' ) );
		$this->seed_session( $other_user_id, array( $this->guest_product->get_id() => 1 ) );
		$_SERVER['HTTP_CART_TOKEN'] = CartTokenUtils::get_cart_token( $other_user_id );

		// Authenticated as our user, presenting the other user's token.
		wp_set_current_user( $this->user_id );
		$this->dispatch_store_api_request();

		$foreign_session = ( new SessionHandler() )->get_session( $other_user_id, array() );
		$this->assertNotEmpty( $foreign_session, 'A user-scoped token must not be consumed (deleted) as if it were a guest cart.' );
		$this->assertEmpty(
			get_user_meta( $this->user_id, '_woocommerce_load_saved_cart_after_login', true ),
			'No saved-cart merge should be requested for a user-scoped token.'
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
	 * @param array<int,int>      $items Map of product ID to quantity for the guest cart.
	 * @param array<string,mixed> $data  Additional session data to store alongside the cart.
	 * @return string The guest session ID.
	 */
	private function start_store_api_guest_request( array $items, array $data = array() ): string {
		$guest_id = wc_rand_hash( 't_', 30 );

		$this->seed_session( $guest_id, $items, $data );
		$_SERVER['HTTP_CART_TOKEN'] = CartTokenUtils::get_cart_token( $guest_id );

		return $guest_id;
	}

	/**
	 * Persist a Store API session row in the DB under the given key with the given cart.
	 *
	 * @param string              $session_id Session key (guest t_... id or user id).
	 * @param array<int,int>      $items      Map of product ID to quantity.
	 * @param array<string,mixed> $data       Additional session data to store alongside the cart.
	 */
	private function seed_session( string $session_id, array $items, array $data = array() ): void {
		$handler = new SessionHandler();
		$this->set_protected_property( $handler, '_customer_id', $session_id );
		$this->set_protected_property( $handler, 'session_expiration', time() + DAY_IN_SECONDS );
		$handler->set( 'cart', $this->build_cart_array( $items ) );
		foreach ( $data as $key => $value ) {
			$handler->set( $key, $value );
		}
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
