<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi;

use Automattic\WooCommerce\Internal\POS\StoreApi\POSSessionHandler;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use WC_Unit_Test_Case;

/**
 * Tests for POSSessionHandler.
 *
 * The handler's contract: the session belongs to the in-person customer,
 * never to the operator processing the sale — resumed from a Cart-Token
 * header or started fresh, with browser cookies and the `?session=` request
 * path playing no role in either direction.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\POSSessionHandler
 */
class POSSessionHandlerTest extends WC_Unit_Test_Case {

	/**
	 * Original current_user_id captured in setUp so it can be restored.
	 *
	 * @var int
	 */
	private $original_user_id;

	/**
	 * Backup of $_SERVER['HTTP_CART_TOKEN'].
	 *
	 * @var string|null
	 */
	private $original_http_cart_token;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_user_id = get_current_user_id();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$this->original_http_cart_token = $_SERVER['HTTP_CART_TOKEN'] ?? null;
		unset( $_SERVER['HTTP_CART_TOKEN'] );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		wp_set_current_user( $this->original_user_id );
		if ( null === $this->original_http_cart_token ) {
			unset( $_SERVER['HTTP_CART_TOKEN'] );
		} else {
			$_SERVER['HTTP_CART_TOKEN'] = $this->original_http_cart_token;
		}
		unset( $_GET['session'] );
		parent::tearDown();
	}

	/**
	 * @testdox generate_customer_id returns a fresh guest id even when the operator is authenticated.
	 */
	public function test_generate_customer_id_never_uses_the_operator(): void {
		$operator_id = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
		wp_set_current_user( $operator_id );

		$handler = new POSSessionHandler();
		$id      = $handler->generate_customer_id();

		$this->assertSame( 'pos_', substr( $id, 0, 4 ) );
		$this->assertNotSame( (string) $operator_id, $id );
		// Calling again must yield a fresh id — every transaction gets its own.
		$this->assertNotSame( $id, $handler->generate_customer_id() );
	}

	/**
	 * @testdox init without a Cart-Token starts a fresh guest session, ignoring the operator.
	 */
	public function test_init_without_token_starts_fresh_guest_session(): void {
		$operator_id = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
		wp_set_current_user( $operator_id );

		$handler = new POSSessionHandler();
		$handler->init();

		$this->assertSame( 'pos_', substr( $handler->get_customer_id(), 0, 4 ) );
		$this->assertNotSame( (string) $operator_id, $handler->get_customer_id() );
	}

	/**
	 * @testdox init with a valid Cart-Token header resumes that transaction's session and data.
	 */
	public function test_init_with_valid_token_resumes_transaction_session(): void {
		// First request of the transaction: fresh guest session with some cart data.
		$first = new POSSessionHandler();
		$first->init();
		$first->set( 'cart', array( 'line_1' => array( 'product_id' => 42 ) ) );
		$first->save_data();
		$transaction_id = $first->get_customer_id();

		// Later request: the client replays the Cart-Token header.
		$_SERVER['HTTP_CART_TOKEN'] = CartTokenUtils::get_cart_token( $transaction_id );

		$second = new POSSessionHandler();
		$second->init();

		$this->assertSame( $transaction_id, $second->get_customer_id(), 'The token session should be resumed, not a fresh one started.' );
		$this->assertSame( array( 'line_1' => array( 'product_id' => 42 ) ), $second->get( 'cart' ), 'The transaction cart data should be restored.' );
	}

	/**
	 * Tokens minted for web sessions — user-keyed (numeric) or guest (`t_`,
	 * the prefix web guests share) — must not resume as POS transactions: a
	 * web shopper's token replayed at the register would hand their live cart
	 * to the POS surface.
	 *
	 * @testdox init with a token for a web session (user or t_ guest) starts a fresh POS session instead.
	 */
	public function test_init_rejects_web_session_tokens(): void {
		foreach ( array( '123', 't_' . wc_rand_hash( '', 30 ) ) as $web_session_id ) {
			$_SERVER['HTTP_CART_TOKEN'] = CartTokenUtils::get_cart_token( $web_session_id );

			$handler = new POSSessionHandler();
			$handler->init();

			$this->assertNotSame( $web_session_id, $handler->get_customer_id() );
			$this->assertSame( 'pos_', substr( $handler->get_customer_id(), 0, 4 ) );
		}
	}

	/**
	 * The reverse direction of the cross-surface guard: a POS transaction
	 * token presented to the public Store API session handler must not open
	 * the transaction session there (wc/store has no capability gate).
	 *
	 * @testdox The web Store API session handler does not resume pos_ sessions.
	 */
	public function test_web_store_api_handler_rejects_pos_tokens(): void {
		$pos = new POSSessionHandler();
		$pos->init();
		$pos->set( 'cart', array( 'pos_line' => array( 'product_id' => 11 ) ) );
		$pos->save_data();

		$_SERVER['HTTP_CART_TOKEN'] = CartTokenUtils::get_cart_token( $pos->get_customer_id() );

		$web = new \Automattic\WooCommerce\StoreApi\SessionHandler();
		$web->init();

		$this->assertNotSame( $pos->get_customer_id(), $web->get_customer_id() );
		$this->assertNull( $web->get( 'cart' ), 'The POS transaction cart must not be readable through the web Store API handler.' );
	}

	/**
	 * The web handler's foreign-token replacement session must be a fresh
	 * guest — keyed to the logged-in user it would clobber their real session
	 * row with empty data — and must carry a real expiration, or the cleanup
	 * cron purges it mid-session.
	 *
	 * @testdox The web handler's foreign-token fallback never adopts the logged-in user and gets a real expiration.
	 */
	public function test_web_handler_foreign_token_fallback_is_safe(): void {
		$operator_id = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
		wp_set_current_user( $operator_id );

		$_SERVER['HTTP_CART_TOKEN'] = CartTokenUtils::get_cart_token( 'pos_' . wc_rand_hash( '', 28 ) );

		$web = new \Automattic\WooCommerce\StoreApi\SessionHandler();
		$web->init();

		$this->assertNotSame( (string) $operator_id, $web->get_customer_id(), 'The fallback session must never be keyed to the logged-in user.' );
		$this->assertSame( 't_', substr( $web->get_customer_id(), 0, 2 ) );

		$expiration = new \ReflectionProperty( $web, 'session_expiration' );
		$expiration->setAccessible( true );
		$this->assertGreaterThan( time(), $expiration->getValue( $web ), 'The fallback session must not be born expired.' );
	}

	/**
	 * @testdox init with an invalid Cart-Token starts a fresh guest session.
	 */
	public function test_init_with_invalid_token_starts_fresh_guest_session(): void {
		$_SERVER['HTTP_CART_TOKEN'] = 'not-a-valid-token';

		$handler = new POSSessionHandler();
		$handler->init();

		$this->assertSame( 'pos_', substr( $handler->get_customer_id(), 0, 4 ) );
	}

	/**
	 * Regression guard: the parent's `?session=` restore path clones another
	 * session's cart into the current one. POS must never run it — a stale or
	 * crafted token would preload a new transaction with another sale's items.
	 *
	 * @testdox init ignores the ?session= request parameter entirely.
	 */
	public function test_init_ignores_session_request_parameter(): void {
		$donor = new POSSessionHandler();
		$donor->init();
		$donor->set( 'cart', array( 'donor_line' => array( 'product_id' => 7 ) ) );
		$donor->save_data();

		$_GET['session'] = CartTokenUtils::get_cart_token( $donor->get_customer_id() );

		$handler = new POSSessionHandler();
		$handler->init();

		$this->assertNotSame( $donor->get_customer_id(), $handler->get_customer_id() );
		$this->assertNull( $handler->get( 'cart' ), 'A fresh transaction must start empty — never cloned from a ?session= token.' );
	}

	/**
	 * @testdox init binds no cookie hooks and set_customer_session_cookie is a no-op.
	 */
	public function test_cookies_play_no_role(): void {
		$handler = new POSSessionHandler();
		$handler->init();

		$this->assertFalse(
			has_action( 'woocommerce_set_cart_cookies', array( $handler, 'set_customer_session_cookie' ) ),
			'The cookie-write hook must not be bound: a transaction must never clobber the operator browser session.'
		);

		// Direct calls (third-party code) must not produce a cookie either.
		/**
		 * This filter is documented in includes/class-wc-session-handler.php.
		 *
		 * @since 3.6.0
		 */
		$cookie_name = apply_filters( 'woocommerce_cookie', 'wp_woocommerce_session_' . COOKIEHASH );
		$handler->set_customer_session_cookie( true );
		$this->assertArrayNotHasKey( $cookie_name, $_COOKIE );
	}

	/**
	 * @testdox A session cookie present on the request is never read.
	 */
	public function test_existing_session_cookie_is_ignored(): void {
		$donor = new POSSessionHandler();
		$donor->init();
		$donor->set( 'cart', array( 'cookie_line' => array( 'product_id' => 9 ) ) );
		$donor->save_data();

		// Forge a structurally-plausible cookie pointing at the donor session.
		// Content barely matters: the POS handler must not even look at it.
		/**
		 * This filter is documented in includes/class-wc-session-handler.php.
		 *
		 * @since 3.6.0
		 */
		$cookie_name             = apply_filters( 'woocommerce_cookie', 'wp_woocommerce_session_' . COOKIEHASH );
		$_COOKIE[ $cookie_name ] = $donor->get_customer_id() . '||' . ( time() + DAY_IN_SECONDS ) . '||x';

		try {
			$handler = new POSSessionHandler();
			$handler->init();

			$this->assertNotSame( $donor->get_customer_id(), $handler->get_customer_id() );
			$this->assertNull( $handler->get( 'cart' ) );
		} finally {
			unset( $_COOKIE[ $cookie_name ] );
		}
	}

	/**
	 * The parent's cookie/rekey surface (direct init_session_cookie,
	 * forget_session, destroy_session calls from third-party code) must stay
	 * cookie-free and operator-free.
	 *
	 * @testdox Direct session lifecycle calls never adopt the operator or read cookies.
	 */
	public function test_lifecycle_methods_stay_pos_scoped(): void {
		$operator_id = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
		wp_set_current_user( $operator_id );

		$handler = new POSSessionHandler();
		$handler->init();
		$handler->set( 'cart', array( 'line' => array( 'product_id' => 3 ) ) );
		$handler->save_data();
		$original_id = $handler->get_customer_id();

		// destroy_session must delete the row and re-key to a fresh pos_ id.
		$handler->destroy_session();
		$this->assertSame( 'pos_', substr( $handler->get_customer_id(), 0, 4 ) );
		$this->assertNotSame( $original_id, $handler->get_customer_id() );
		$this->assertFalse( $handler->get_session( $original_id, false ), 'The destroyed session row must be gone.' );

		// Direct init_session_cookie (e.g. via wc_set_customer_auth_cookie
		// paths) must yield a fresh pos_ session, never the operator id.
		$handler->init_session_cookie();
		$this->assertSame( 'pos_', substr( $handler->get_customer_id(), 0, 4 ) );
		$this->assertNotSame( (string) $operator_id, $handler->get_customer_id() );
	}

	/**
	 * @testdox has_session is always true, so session data persists regardless of who is authenticated.
	 */
	public function test_has_session_is_always_true(): void {
		wp_set_current_user( 0 );
		$handler = new POSSessionHandler();
		$handler->init();

		$this->assertTrue( $handler->has_session() );
	}
}
