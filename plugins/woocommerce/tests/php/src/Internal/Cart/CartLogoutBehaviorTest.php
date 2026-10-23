<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Cart;

use Automattic\WooCommerce\Enums\CartBehaviorOnLogout;
use Automattic\WooCommerce\Internal\Cart\CartLogoutBehavior;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;
use WC_Customer;
use WC_Helper_Product;
use WC_Session_Handler;
use WC_Unit_Test_Case;

/**
 * Tests for the CartLogoutBehavior class.
 *
 * Every logout here goes through the real wp_logout() with the real WC_Session_Handler, so the
 * session row, the customer ID rotation and the cookies behave as they do in production.
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
	 * The session handler the suite installed, put back after each test.
	 *
	 * @var \WC_Session|null
	 */
	private $original_session;

	/**
	 * The customer object the suite installed, put back after each test.
	 *
	 * @var WC_Customer|null
	 */
	private $original_customer;

	/**
	 * Cookies WooCommerce tried to set during the test, by name.
	 *
	 * @var array<string, string>
	 */
	private $cookies = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut               = new CartLogoutBehavior();
		$this->original_session  = WC()->session;
		$this->original_customer = WC()->customer;
		$this->cookies           = array();

		add_filter(
			'woocommerce_set_cookie_enabled',
			function ( $enabled, $name, $value ) {
				$this->cookies[ $name ] = $value;
				return false;
			},
			10,
			3
		);
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		// The base teardown does not reset these, and later tests expect the suite's mock session.
		WC()->session  = $this->original_session;
		WC()->customer = $this->original_customer;

		parent::tearDown();
	}

	/**
	 * @testdox Should write the cart into the new guest session on logout when the store preserves carts.
	 */
	public function test_cart_survives_a_real_logout(): void {
		update_option( 'woocommerce_cart_behavior_on_logout', CartBehaviorOnLogout::PRESERVE );
		$this->sut->register();
		$this->log_in_with_a_session();
		$product_id         = $this->add_product_to_cart();
		$customer_id_before = WC()->session->get_customer_id();
		$loads_before       = did_action( 'woocommerce_cart_loaded_from_session' );

		wp_logout();

		$customer_id_after = WC()->session->get_customer_id();
		$this->assertNotSame( $customer_id_before, $customer_id_after, 'Logout should issue a new guest customer ID' );
		$this->assertSame(
			array( $product_id ),
			$this->get_product_ids_in_stored_session( $customer_id_after ),
			'The new guest session row should hold the cart before the request ends'
		);
		$this->assertStringStartsWith(
			$customer_id_after . '|',
			$this->cookies[ $this->get_session_cookie_name() ] ?? '',
			'The session cookie should point at the new guest customer ID'
		);
		$this->assertSame(
			$loads_before,
			did_action( 'woocommerce_cart_loaded_from_session' ),
			'The cart should not be rebuilt during the logout request, so the load hooks fire once per request as before'
		);
	}

	/**
	 * @testdox Should leave the new guest session without a cart when the store clears carts or has no stored choice.
	 *
	 * @testWith ["clear"]
	 *           [null]
	 *
	 * @param string|null $option Stored option value, or null for none.
	 */
	public function test_cart_is_cleared_on_logout( ?string $option ): void {
		if ( null === $option ) {
			delete_option( 'woocommerce_cart_behavior_on_logout' );
		} else {
			update_option( 'woocommerce_cart_behavior_on_logout', $option );
		}
		$this->sut->register();
		$this->log_in_with_a_session();
		$this->add_product_to_cart();

		wp_logout();

		$this->assertTrue( WC()->cart->is_empty(), 'The cart should be emptied on logout' );
		$this->assertNull( $this->get_stored_session( WC()->session->get_customer_id() ), 'No guest session should be written' );
	}

	/**
	 * @testdox Should not write a guest session when the cart was already empty.
	 */
	public function test_empty_cart_writes_no_guest_session(): void {
		update_option( 'woocommerce_cart_behavior_on_logout', CartBehaviorOnLogout::PRESERVE );
		$this->sut->register();
		$this->log_in_with_a_session();

		wp_logout();

		$this->assertNull( $this->get_stored_session( WC()->session->get_customer_id() ), 'No guest session should be written for an empty cart' );
		$this->assertSame( '', $this->cookies[ $this->get_session_cookie_name() ] ?? '', 'The only session cookie set should be the one logout expires' );
	}

	/**
	 * @testdox Should keep the previous shopper's customer data out of the guest session.
	 */
	public function test_previous_customer_data_is_not_written_into_the_guest_session(): void {
		update_option( 'woocommerce_cart_behavior_on_logout', CartBehaviorOnLogout::PRESERVE );
		$this->sut->register();
		$user_id = $this->log_in_with_a_session();
		$this->add_product_to_cart();
		WC()->customer->set_billing_address_1( '1 Private Lane' );
		WC()->customer->set_billing_email( 'previous-shopper@example.test' );

		wp_logout();

		// Everything hooked to shutdown, including the customer save that would have leaked the data.
		// The buffer flush is skipped because it would also close PHPUnit's own buffers.
		remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );
		do_action( 'shutdown' );

		$this->assertSame( 0, WC()->customer->get_id(), 'The customer object should be a guest after logout' );
		$this->assertNotSame( $user_id, WC()->customer->get_id() );
		$stored = $this->get_stored_session( WC()->session->get_customer_id() );
		$this->assertNotNull( $stored, 'The guest session should exist' );
		$this->assertStringNotContainsString( '1 Private Lane', wp_json_encode( $stored ), 'The guest session must not hold the previous shopper\'s address' );
		$this->assertStringNotContainsString( 'previous-shopper@example.test', wp_json_encode( $stored ), 'The guest session must not hold the previous shopper\'s email' );
	}

	/**
	 * @testdox Should drop items a guest is not allowed to add before writing the guest session.
	 */
	public function test_items_a_guest_cannot_add_are_dropped(): void {
		update_option( 'woocommerce_cart_behavior_on_logout', CartBehaviorOnLogout::PRESERVE );
		$this->sut->register();
		$this->log_in_with_a_session();
		$members_only_id = $this->add_product_to_cart();
		$public_id       = $this->add_product_to_cart();

		add_filter(
			'woocommerce_add_to_cart_validation',
			function ( $passed, $product_id ) use ( $members_only_id ) {
				if ( $members_only_id === $product_id && 0 === get_current_user_id() ) {
					wc_add_notice( 'Members only.', 'error' );
					return false;
				}
				return $passed;
			},
			10,
			2
		);

		wp_logout();

		$this->assertSame(
			array( $public_id ),
			$this->get_product_ids_in_stored_session( WC()->session->get_customer_id() ),
			'Only the item a guest may add should be carried over'
		);
		$this->assertSame( 0, wc_notice_count(), 'Notices raised by the validation callbacks should not reach the next page' );
	}

	/**
	 * @testdox Should let the logout finish and log the error when restoring the cart throws.
	 */
	public function test_logout_finishes_when_restore_throws(): void {
		update_option( 'woocommerce_cart_behavior_on_logout', CartBehaviorOnLogout::PRESERVE );
		$this->set_up_spy_logger();
		$this->sut->register();
		$this->log_in_with_a_session();
		$this->add_product_to_cart();
		add_filter(
			'woocommerce_add_to_cart_validation',
			function () {
				throw new \RuntimeException( 'Extension exploded' );
			}
		);

		wp_logout();

		$this->assertTrue( WC()->cart->is_empty(), 'The logout should complete with the cart emptied' );
		$this->assertLogged( 'error', 'Extension exploded' );
		$this->tear_down_spy_logger();
	}

	/**
	 * @testdox Should bracket the session handler's own wp_logout priority.
	 */
	public function test_hooks_bracket_the_session_teardown(): void {
		$session = $this->use_real_session_handler();
		$this->sut->register();

		$teardown = has_action( 'wp_logout', array( $session, 'destroy_session' ) );
		$capture  = has_action( 'wp_logout', array( $this->sut, 'handle_wp_logout_capture' ) );
		$restore  = has_action( 'wp_logout', array( $this->sut, 'handle_wp_logout_restore' ) );

		$this->assertIsInt( $teardown, 'The session handler should hook its teardown to wp_logout' );
		$this->assertLessThan( $teardown, $capture, 'The capture must run before the session is destroyed' );
		$this->assertGreaterThan( $teardown, $restore, 'The restore must run after the session is destroyed' );
	}

	/**
	 * @testdox Should do nothing with a session handler that is not WC_Session_Handler.
	 */
	public function test_custom_session_handler_is_left_alone(): void {
		update_option( 'woocommerce_cart_behavior_on_logout', CartBehaviorOnLogout::PRESERVE );
		$this->sut->register();
		$user_id = $this->log_in_with_a_session();
		$this->add_product_to_cart();
		WC()->session = new \WC_Mock_Session_Handler();

		wp_logout();

		$this->assertSame( $user_id, WC()->customer->get_id(), 'The restore should not touch anything when the session handler is not WC_Session_Handler' );
	}

	/**
	 * Log a customer in with a real session handler, a saved session row and a customer object
	 * wired up the way WooCommerce::initialize_cart() does it.
	 *
	 * @return int The user ID.
	 */
	private function log_in_with_a_session(): int {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );

		$session = $this->use_real_session_handler();
		$session->set_customer_session_cookie( true );
		$session->set( 'seeded', true );
		$session->save_data();

		WC()->customer = new WC_Customer( $user_id, true );
		add_action( 'shutdown', array( WC()->customer, 'save' ), 10 );

		return $user_id;
	}

	/**
	 * Swap the suite's mock session for the real WC_Session_Handler.
	 *
	 * @return WC_Session_Handler The live session handler.
	 */
	private function use_real_session_handler(): WC_Session_Handler {
		$session = new WC_Session_Handler();
		$session->init();
		WC()->session = $session;

		return $session;
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
	 * Read a session row straight from the database.
	 *
	 * @param string $customer_id Session key.
	 * @return array|null The unserialized session, or null when there is no row.
	 */
	private function get_stored_session( string $customer_id ): ?array {
		global $wpdb;

		$stored = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$customer_id
			)
		);

		if ( null === $stored ) {
			return null;
		}

		return array_map( 'maybe_unserialize', (array) maybe_unserialize( $stored ) );
	}

	/**
	 * Get the product IDs held in a stored session's cart.
	 *
	 * @param string $customer_id Session key.
	 * @return array<int>
	 */
	private function get_product_ids_in_stored_session( string $customer_id ): array {
		$stored = $this->get_stored_session( $customer_id );

		return array_values( array_map( 'intval', array_column( (array) ( $stored['cart'] ?? array() ), 'product_id' ) ) );
	}

	/**
	 * Get the name of the session cookie.
	 *
	 * @return string
	 */
	private function get_session_cookie_name(): string {
		$property = new \ReflectionProperty( WC_Session_Handler::class, '_cookie' );
		$property->setAccessible( true );

		return $property->getValue( new WC_Session_Handler() );
	}
}
