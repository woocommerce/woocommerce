<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Cart;

use Automattic\WooCommerce\Enums\CartBehaviorOnLogout;
use Automattic\WooCommerce\Internal\Cart\CartLogoutBehavior;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the CartLogoutBehavior class.
 */
class CartLogoutBehaviorTest extends WC_Unit_Test_Case {
	use LoggerSpyTrait;

	/**
	 * The System Under Test.
	 *
	 * @var CartLogoutBehavior
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new CartLogoutBehavior();
	}

	/**
	 * @testdox Should carry the cart over to the new session when the store preserves carts on logout.
	 */
	public function test_cart_is_preserved_when_option_is_preserve(): void {
		update_option( 'woocommerce_cart_behavior_on_logout', CartBehaviorOnLogout::PRESERVE );
		$product_id = $this->add_product_to_cart();

		$this->sut->handle_wp_logout_capture();
		$this->destroy_session_like_logout_does();
		$this->sut->handle_wp_logout_restore();

		$this->assertSame(
			array( $product_id ),
			$this->get_product_ids_in_cart(),
			'The cart should still hold the product after logging out'
		);
		$this->assertNotEmpty(
			WC()->session->get( 'cart' ),
			'The new session should be seeded with the cart so the next request can read it back'
		);
	}

	/**
	 * @testdox Should leave the cart empty when the store clears carts on logout.
	 */
	public function test_cart_is_not_preserved_when_option_is_clear(): void {
		update_option( 'woocommerce_cart_behavior_on_logout', CartBehaviorOnLogout::CLEAR );
		$this->add_product_to_cart();

		$this->sut->handle_wp_logout_capture();
		$this->destroy_session_like_logout_does();
		$this->sut->handle_wp_logout_restore();

		$this->assertTrue( WC()->cart->is_empty(), 'The cart should be empty after logging out' );
		$this->assertEmpty( WC()->session->get( 'cart' ), 'The new session should not be seeded with the cart' );
	}

	/**
	 * @testdox Should preserve the cart by default, without the option being saved.
	 */
	public function test_cart_is_preserved_when_option_is_not_set(): void {
		delete_option( 'woocommerce_cart_behavior_on_logout' );
		$product_id = $this->add_product_to_cart();

		$this->sut->handle_wp_logout_capture();
		$this->destroy_session_like_logout_does();
		$this->sut->handle_wp_logout_restore();

		$this->assertSame(
			array( $product_id ),
			$this->get_product_ids_in_cart(),
			'The cart should be preserved when the store has not chosen a behavior'
		);
	}

	/**
	 * @testdox Should not seed the new session when the cart was already empty.
	 */
	public function test_empty_cart_does_not_seed_the_new_session(): void {
		update_option( 'woocommerce_cart_behavior_on_logout', CartBehaviorOnLogout::PRESERVE );

		$this->sut->handle_wp_logout_capture();
		$this->destroy_session_like_logout_does();
		$this->sut->handle_wp_logout_restore();

		$this->assertEmpty(
			WC()->session->get( 'cart' ),
			'Logging out with an empty cart should leave the new session without cart data'
		);
	}

	/**
	 * @testdox Should bracket the session handler's own wp_logout priority, whatever that priority is.
	 */
	public function test_hooks_bracket_the_real_session_teardown(): void {
		$session = $this->use_real_session_handler();
		$this->sut->register();

		// Read the teardown's priority off the live handler rather than hardcoding 10, so this fails if
		// core ever moves it instead of quietly passing against a stale number.
		$teardown_priority = has_action( 'wp_logout', array( $session, 'destroy_session' ) );
		$capture_priority  = has_action( 'wp_logout', array( $this->sut, 'handle_wp_logout_capture' ) );
		$restore_priority  = has_action( 'wp_logout', array( $this->sut, 'handle_wp_logout_restore' ) );

		$this->assertIsInt( $teardown_priority, 'The session handler should hook its teardown to wp_logout' );
		$this->assertIsInt( $capture_priority, 'The class should hook its capture to wp_logout' );
		$this->assertIsInt( $restore_priority, 'The class should hook its restore to wp_logout' );

		$this->assertLessThan(
			$teardown_priority,
			$capture_priority,
			'The cart must be captured before the session handler tears the session down'
		);
		$this->assertGreaterThan(
			$teardown_priority,
			$restore_priority,
			'The cart must be restored after the session handler tears the session down'
		);
	}

	/**
	 * @testdox Should carry the cart through a real wp_logout, including the cookie for the new guest session.
	 */
	public function test_cart_survives_a_real_logout_through_the_session_handler(): void {
		update_option( 'woocommerce_cart_behavior_on_logout', CartBehaviorOnLogout::PRESERVE );

		$session = $this->use_real_session_handler();
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );

		$product_id = $this->add_product_to_cart();
		$session->set_customer_session_cookie( true );
		$session->save_data();

		$customer_id_before = $session->get_customer_id();

		$this->sut->register();

		$cookies = array();
		$this->capture_cookies( $cookies );

		// The real thing: WC_Session_Handler::destroy_session() runs at priority 10 between the two
		// callbacks, deleting the session row, emptying the cart and issuing a new customer ID.
		do_action( 'wp_logout', $user_id );

		$customer_id_after = WC()->session->get_customer_id();

		$this->assertNotSame(
			$customer_id_before,
			$customer_id_after,
			'The session handler should have issued a new guest customer ID'
		);
		$this->assertSame(
			array( $product_id ),
			$this->get_product_ids_in_cart(),
			'The cart should still hold the product after a real logout'
		);
		$this->assertNotEmpty(
			WC()->session->get( 'cart' ),
			'The new guest session should be seeded with the cart'
		);

		// Without this cookie the seeded session is written but never read back on the next request,
		// which is the whole point of the restore. The mock handler has no cookie handling, so this
		// assertion is only reachable against the real one.
		$cookie_name = ( new \ReflectionProperty( \WC_Session_Handler::class, '_cookie' ) );
		$cookie_name->setAccessible( true );

		$this->assertArrayHasKey(
			$cookie_name->getValue( WC()->session ),
			$cookies,
			'A session cookie should have been set for the new guest session'
		);
		$this->assertStringStartsWith(
			$customer_id_after . '|',
			$cookies[ $cookie_name->getValue( WC()->session ) ],
			'The session cookie should point at the new guest customer ID, not the logged-out one'
		);
	}

	/**
	 * @testdox Should write the new guest session to the database during logout, not leave it to shutdown.
	 */
	public function test_restored_session_is_persisted_before_the_request_ends(): void {
		global $wpdb;

		update_option( 'woocommerce_cart_behavior_on_logout', CartBehaviorOnLogout::PRESERVE );

		$session = $this->use_real_session_handler();
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );

		$product_id = $this->add_product_to_cart();
		$session->set_customer_session_cookie( true );
		$session->save_data();

		$this->sut->register();

		$cookies = array();
		$this->capture_cookies( $cookies );

		do_action( 'wp_logout', $user_id );

		// PHPUnit never fires the shutdown action, so the handler's own save_data() callback at priority
		// 20 has not run. A row here can only come from the class writing the session itself.
		$stored = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s",
				WC()->session->get_customer_id()
			)
		);

		$this->assertNotNull(
			$stored,
			'The new guest session should already be in the database when the logout request ends'
		);

		$stored_cart = maybe_unserialize( maybe_unserialize( $stored )['cart'] ?? '' );

		$this->assertSame(
			array( $product_id ),
			array_values( array_map( 'intval', array_column( (array) $stored_cart, 'product_id' ) ) ),
			'The persisted session should hold the preserved cart'
		);
	}

	/**
	 * @testdox Should let the logout finish when restoring the cart throws, rather than replacing the redirect with a fatal.
	 */
	public function test_logout_survives_a_failure_while_restoring_the_cart(): void {
		update_option( 'woocommerce_cart_behavior_on_logout', CartBehaviorOnLogout::PRESERVE );
		$this->add_product_to_cart();

		// Stand in for an extension that raises while the cart is rebuilt. wp_logout has no error
		// boundary, so anything escaping here would surface as a fatal instead of the logout redirect.
		$explode = function () {
			throw new \RuntimeException( 'Extension blew up while the cart was rebuilt' );
		};
		add_action( 'woocommerce_cart_loaded_from_session', $explode );

		try {
			$this->sut->handle_wp_logout_capture();
			$this->destroy_session_like_logout_does();
			$this->sut->handle_wp_logout_restore();
		} finally {
			remove_action( 'woocommerce_cart_loaded_from_session', $explode );
		}

		$this->assertLogged(
			'error',
			'Could not restore the cart into the new session on logout',
			array( 'source' => 'cart-logout-behavior' )
		);
		$this->assertNotEmpty(
			WC()->session->get( 'cart' ),
			'The captured cart should still reach the session, so the next request can recover it'
		);
	}

	/**
	 * @testdox Should not log an error when the cart is carried over successfully.
	 */
	public function test_successful_preserve_logs_no_error(): void {
		update_option( 'woocommerce_cart_behavior_on_logout', CartBehaviorOnLogout::PRESERVE );
		$this->add_product_to_cart();

		$this->sut->handle_wp_logout_capture();
		$this->destroy_session_like_logout_does();
		$this->sut->handle_wp_logout_restore();

		$this->assertNoErrorLogged();
	}

	/**
	 * Add a simple product to the cart.
	 *
	 * @return int The ID of the product added to the cart.
	 */
	private function add_product_to_cart(): int {
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id() );

		return $product->get_id();
	}

	/**
	 * Get the product IDs currently held in the in-memory cart.
	 *
	 * @return array<int>
	 */
	private function get_product_ids_in_cart(): array {
		return array_values( array_map( 'intval', array_column( WC()->cart->get_cart_contents(), 'product_id' ) ) );
	}

	/**
	 * Reproduce what WC_Session_Handler::destroy_session() does to the cart between the two
	 * wp_logout callbacks. The test session handler is a bare WC_Session, so it has no
	 * destroy_session() of its own to call here.
	 */
	private function destroy_session_like_logout_does(): void {
		wc_empty_cart();
		WC()->session->set( 'cart', null );

		// forget_session() drops the whole session payload, not just the cart. Leaving cart_totals behind
		// would keep get_cart_from_session() out of the calculate_totals() branch that writes the restored
		// cart back to the session, so the fake has to clear it too. Customer ID rotation is left to
		// test_cart_survives_a_real_logout_through_the_session_handler(), which uses the real handler.
		WC()->session->set( 'cart_totals', null );
	}

	/**
	 * Swap the bare WC_Mock_Session_Handler the suite installs for the real WC_Session_Handler, so the
	 * wp_logout teardown, the customer ID rotation and the session cookie all behave as they do in
	 * production.
	 *
	 * @return \WC_Session_Handler The live session handler.
	 */
	private function use_real_session_handler(): \WC_Session_Handler {
		remove_filter( 'woocommerce_session_handler', array( $this, 'set_mock_session_handler' ) );
		WC()->initialize_session();

		return WC()->session;
	}

	/**
	 * Record the cookies WooCommerce tries to set, without emitting real headers from the CLI.
	 *
	 * @param array $cookies Filled with the last value set for each cookie name.
	 */
	private function capture_cookies( array &$cookies ): void {
		add_filter(
			'woocommerce_set_cookie_enabled',
			function ( $enabled, $name, $value ) use ( &$cookies ) {
				$cookies[ $name ] = $value;
				return false;
			},
			10,
			3
		);
	}
}
