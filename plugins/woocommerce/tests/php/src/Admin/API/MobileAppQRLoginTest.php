<?php
/**
 * Tests for the MobileAppQRLogin REST controller.
 *
 * @package WooCommerce\Admin\Tests\Admin\API
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\API;

use Automattic\WooCommerce\Admin\API\MobileAppQRLogin;
use Automattic\WooCommerce\Admin\API\RateLimits\QRLoginRateLimits;
use WC_REST_Unit_Test_Case;
use WP_Application_Passwords;
use WP_REST_Request;

/**
 * MobileAppQRLogin API controller test.
 *
 * @class MobileAppQRLoginTest.
 */
class MobileAppQRLoginTest extends WC_REST_Unit_Test_Case {

	/**
	 * Token generation endpoint.
	 *
	 * @var string
	 */
	const TOKEN_ENDPOINT = '/wc-admin/mobile-app/qr-login-token';

	/**
	 * Token exchange endpoint.
	 *
	 * @var string
	 */
	const EXCHANGE_ENDPOINT = '/wc-admin/mobile-app/qr-login-exchange';

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	private $admin_id;

	/**
	 * Shop manager user ID.
	 *
	 * @var int
	 */
	private $shop_manager_id;

	/**
	 * Subscriber user ID.
	 *
	 * @var int
	 */
	private $subscriber_id;

	/**
	 * Original value of $_SERVER['HTTPS'] (if any) before each test.
	 *
	 * @var string|null
	 */
	private $original_https;

	/**
	 * Original value of $_SERVER['SERVER_PORT'] (if any) before each test.
	 *
	 * Captured alongside HTTPS because `is_ssl()` returns true when
	 * SERVER_PORT === '443', regardless of the HTTPS header.
	 *
	 * @var string|null
	 */
	private $original_server_port;

	/**
	 * Original value of $_SERVER['HTTP_X_FORWARDED_PROTO'] (if any) before each test.
	 *
	 * Some hosts and reverse-proxy plugins use this header to derive scheme,
	 * so we normalize it across tests to keep `is_ssl()` deterministic.
	 *
	 * @var string|null
	 */
	private $original_http_x_forwarded_proto;

	/**
	 * Original value of $_SERVER['REMOTE_ADDR'] (if any) before each test.
	 *
	 * @var string|null
	 */
	private $original_remote_addr;

	/**
	 * Filters registered via force_site_url() so tearDown() can remove them.
	 *
	 * @var array<int, callable>
	 */
	private $site_url_filters = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->admin_id        = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->shop_manager_id = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
		$this->subscriber_id   = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		// Remember existing $_SERVER values so we can restore them in tearDown.
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unit-test fixture: values are captured for restoration only, never used for processing.
		$this->original_https                  = isset( $_SERVER['HTTPS'] ) ? (string) $_SERVER['HTTPS'] : null;
		$this->original_server_port            = isset( $_SERVER['SERVER_PORT'] ) ? (string) $_SERVER['SERVER_PORT'] : null;
		$this->original_http_x_forwarded_proto = isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ? (string) $_SERVER['HTTP_X_FORWARDED_PROTO'] : null;
		$this->original_remote_addr            = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : null;
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Default to HTTPS on for most tests; disable explicitly where needed.
		$this->force_https( true );

		// Default to an HTTPS site URL. The WP test framework ships with an
		// http:// default (example.org), so we explicitly normalize it here so
		// the controller's `insecure_site_url` check does not reject happy-path
		// tests. Individual tests override this via force_site_url() when they
		// need to exercise the http:// rejection path.
		$this->force_site_url( 'https://example.org' );

		// Default REMOTE_ADDR for exchange IP bucketing tests.
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );

		wp_delete_user( $this->admin_id );
		wp_delete_user( $this->shop_manager_id );
		wp_delete_user( $this->subscriber_id );

		// Clear any QR login data the tests may have written.
		$this->delete_all_qr_login_data();

		// Restore $_SERVER state.
		if ( null === $this->original_https ) {
			unset( $_SERVER['HTTPS'] );
		} else {
			$_SERVER['HTTPS'] = $this->original_https;
		}

		if ( null === $this->original_server_port ) {
			unset( $_SERVER['SERVER_PORT'] );
		} else {
			$_SERVER['SERVER_PORT'] = $this->original_server_port;
		}

		if ( null === $this->original_http_x_forwarded_proto ) {
			unset( $_SERVER['HTTP_X_FORWARDED_PROTO'] );
		} else {
			$_SERVER['HTTP_X_FORWARDED_PROTO'] = $this->original_http_x_forwarded_proto;
		}

		if ( null === $this->original_remote_addr ) {
			unset( $_SERVER['REMOTE_ADDR'] );
		} else {
			$_SERVER['REMOTE_ADDR'] = $this->original_remote_addr;
		}

		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );

		// Remove any pre_option_siteurl filters force_site_url() registered.
		foreach ( $this->site_url_filters as $priority => $filter ) {
			remove_filter( 'pre_option_siteurl', $filter, $priority );
		}
		$this->site_url_filters = array();

		parent::tearDown();
	}

	/**
	 * Toggle HTTPS state for `is_ssl()` checks.
	 *
	 * Disabling HTTPS clears every server indicator that `is_ssl()` (and common
	 * reverse-proxy plugins) inspect — `HTTPS`, `SERVER_PORT`, and
	 * `HTTP_X_FORWARDED_PROTO` — so leftover globals from earlier tests or the
	 * PHPUnit runner can never make a plain-HTTP request appear secure.
	 *
	 * @param bool $on Whether HTTPS should appear enabled.
	 */
	private function force_https( bool $on ): void {
		if ( $on ) {
			$_SERVER['HTTPS'] = 'on';
		} else {
			unset(
				$_SERVER['HTTPS'],
				$_SERVER['SERVER_PORT'],
				$_SERVER['HTTP_X_FORWARDED_PROTO']
			);
		}
	}

	/**
	 * Force `get_site_url()` to return the given URL for the duration of the test.
	 *
	 * Uses the `pre_option_siteurl` filter so we do not have to mutate and restore
	 * the real `siteurl` option. Filters stack — the last one registered with the
	 * highest priority wins — so callers can override an earlier setUp() default
	 * by calling this method again. All registered filters are removed in
	 * tearDown().
	 *
	 * @param string $url The URL to return from `get_site_url()`.
	 */
	private function force_site_url( string $url ): void {
		// Assign an incrementally higher priority so each subsequent call
		// overrides the previous one even though the earlier filter is still
		// registered (we cannot remove a closure by reference cleanly).
		$priority = 10 + count( $this->site_url_filters );
		$filter   = static function () use ( $url ) {
			return $url;
		};
		add_filter( 'pre_option_siteurl', $filter, $priority );
		$this->site_url_filters[ $priority ] = $filter;
	}

	/**
	 * Delete QR login data created by the controller.
	 */
	private function delete_all_qr_login_data(): void {
		global $wpdb;

		// Remove token transients keyed by sha256 hash (and their _timeout siblings).
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_\\_wc\\_qr\\_login\\_token\\_%' ESCAPE '\\\\' OR option_name LIKE '\\_transient\\_timeout\\_\\_wc\\_qr\\_login\\_token\\_%' ESCAPE '\\\\'"
		);

		// Remove database-backed token exchange claims.
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_wc\\_qr\\_login\\_claim\\_%' ESCAPE '\\\\'"
		);

		// Remove rate-limit rows.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}wc_rate_limits WHERE rate_limit_key LIKE %s",
				$wpdb->esc_like( QRLoginRateLimits::KEY_PREFIX ) . '%'
			)
		);

		wp_cache_flush();
	}

	/**
	 * Get a QR login rate-limit row.
	 *
	 * @param string $bucket Bucket name.
	 * @param string $identifier Bucket identifier.
	 * @return object|null
	 */
	private function get_qr_login_rate_limit_row( string $bucket, string $identifier ): ?object {
		global $wpdb;

		$key = QRLoginRateLimits::get_action_id( $bucket, $identifier );

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT rate_limit_key, rate_limit_expiry, rate_limit_remaining FROM {$wpdb->prefix}wc_rate_limits WHERE rate_limit_key = %s",
				$key
			)
		);
	}

	/**
	 * Count legacy transient-backed QR login rate-limit rows.
	 *
	 * @return int
	 */
	private function get_qr_login_rate_limit_transient_count(): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_\\_wc\\_qr\\_login\\_rate\\_%' ESCAPE '\\\\' OR option_name LIKE '\\_transient\\_timeout\\_\\_wc\\_qr\\_login\\_rate\\_%' ESCAPE '\\\\'"
		);
	}

	/**
	 * Extract the plaintext token from a `qr_url` deep link.
	 *
	 * @param string $qr_url The `woocommerce://qr-login?...` URL.
	 * @return string The plaintext token.
	 */
	private function token_from_qr_url( string $qr_url ): string {
		$query_string = wp_parse_url( $qr_url, PHP_URL_QUERY );
		$params       = array();
		wp_parse_str( (string) $query_string, $params );
		return isset( $params['token'] ) ? (string) $params['token'] : '';
	}

	/**
	 * Build the sha256 token hash used by controller storage keys.
	 *
	 * @param string $token Plaintext token.
	 * @return string
	 */
	private function token_hash( string $token ): string {
		return hash( 'sha256', $token );
	}

	/**
	 * Build the transient key for a plaintext token.
	 *
	 * @param string $token Plaintext token.
	 * @return string
	 */
	private function token_transient_key( string $token ): string {
		return MobileAppQRLogin::TOKEN_TRANSIENT_PREFIX . $this->token_hash( $token );
	}

	/**
	 * Build the database claim option key for a plaintext token.
	 *
	 * @param string $token Plaintext token.
	 * @return string
	 */
	private function token_claim_key( string $token ): string {
		return MobileAppQRLogin::CLAIM_OPTION_PREFIX . $this->token_hash( $token );
	}

	/**
	 * Issue a POST to the token-generation endpoint.
	 *
	 * @return \WP_REST_Response
	 */
	private function dispatch_generate(): \WP_REST_Response {
		$request = new WP_REST_Request( 'POST', self::TOKEN_ENDPOINT );
		return $this->server->dispatch( $request );
	}

	/**
	 * Issue a POST to the token-exchange endpoint.
	 *
	 * @param string|null $token Token to exchange. Null omits the parameter.
	 * @return \WP_REST_Response
	 */
	private function dispatch_exchange( ?string $token ): \WP_REST_Response {
		$request = new WP_REST_Request( 'POST', self::EXCHANGE_ENDPOINT );
		if ( null !== $token ) {
			$request->set_param( 'token', $token );
		}
		return $this->server->dispatch( $request );
	}

	// -----------------------------------------------------------------------
	// Permission / capability checks.
	// -----------------------------------------------------------------------

	/**
	 * @testdox Administrators can generate a token and receive a qr_url on the happy path.
	 */
	public function test_generate_token_happy_path_for_administrator(): void {
		wp_set_current_user( $this->admin_id );

		$response = $this->dispatch_generate();

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'qr_url', $data );
		$this->assertArrayHasKey( 'expires_at', $data );
		$this->assertArrayHasKey( 'ttl', $data );
		$this->assertSame( MobileAppQRLogin::TOKEN_TTL, $data['ttl'] );
		$this->assertStringStartsWith( 'woocommerce://qr-login?token=', $data['qr_url'] );
		$this->assertStringContainsString( '&siteUrl=', $data['qr_url'] );
	}

	/**
	 * @testdox Shop managers can generate a token because they have the manage_woocommerce capability.
	 */
	public function test_generate_token_happy_path_for_shop_manager(): void {
		wp_set_current_user( $this->shop_manager_id );

		$response = $this->dispatch_generate();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'qr_url', $response->get_data() );
	}

	/**
	 * @testdox Token generation rejects unauthenticated requests with a 401.
	 */
	public function test_generate_token_rejects_unauthenticated(): void {
		wp_set_current_user( 0 );

		$response = $this->dispatch_generate();

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
		$this->assertSame( 'woocommerce_rest_cannot_view', $response->get_data()['code'] );
	}

	/**
	 * @testdox Token generation rejects subscribers who lack the manage_woocommerce capability.
	 */
	public function test_generate_token_rejects_subscriber(): void {
		wp_set_current_user( $this->subscriber_id );

		$response = $this->dispatch_generate();

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
		$this->assertSame( 'woocommerce_rest_cannot_view', $response->get_data()['code'] );
	}

	// -----------------------------------------------------------------------
	// Generate: error paths.
	// -----------------------------------------------------------------------

	/**
	 * @testdox Token generation fails with ssl_required when the current request is not over HTTPS.
	 */
	public function test_generate_token_requires_https(): void {
		wp_set_current_user( $this->admin_id );
		$this->force_https( false );

		$response = $this->dispatch_generate();

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'ssl_required', $response->get_data()['code'] );
	}

	/**
	 * @testdox Token generation fails with insecure_site_url when the request is HTTPS but get_site_url() returns an HTTP URL.
	 */
	public function test_generate_token_rejects_http_site_url_even_when_request_is_https(): void {
		wp_set_current_user( $this->admin_id );
		// Simulate a misconfigured proxy: the request appears HTTPS but the canonical
		// site URL is still http:// (e.g. stale `siteurl` option).
		$this->force_https( true );
		$this->force_site_url( 'http://shop.example.com' );

		$response = $this->dispatch_generate();

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'insecure_site_url', $response->get_data()['code'] );
	}

	/**
	 * @testdox Token exchange fails with insecure_site_url when the site URL is not HTTPS.
	 */
	public function test_exchange_token_rejects_http_site_url(): void {
		// Mint a valid token while the site is correctly configured for HTTPS.
		wp_set_current_user( $this->admin_id );
		$plaintext = $this->token_from_qr_url( $this->dispatch_generate()->get_data()['qr_url'] );

		// Then simulate the site URL being downgraded before the exchange happens.
		wp_set_current_user( 0 );
		$this->force_site_url( 'http://shop.example.com' );

		$response = $this->dispatch_exchange( $plaintext );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'insecure_site_url', $response->get_data()['code'] );
	}

	/**
	 * @testdox Token exchange fails with ssl_required when the current request is not over HTTPS.
	 */
	public function test_exchange_token_requires_https(): void {
		wp_set_current_user( $this->admin_id );
		$plaintext = $this->token_from_qr_url( $this->dispatch_generate()->get_data()['qr_url'] );

		wp_set_current_user( 0 );
		$this->force_https( false );
		$response = $this->dispatch_exchange( $plaintext );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'ssl_required', $response->get_data()['code'] );
		$this->assertCount( 0, WP_Application_Passwords::get_user_application_passwords( $this->admin_id ) );
	}

	/**
	 * @testdox Token generation fails with 501 when Application Passwords are disabled site-wide.
	 */
	public function test_generate_token_requires_application_passwords_available(): void {
		wp_set_current_user( $this->admin_id );

		add_filter( 'wp_is_application_passwords_available', '__return_false' );

		try {
			$response = $this->dispatch_generate();

			$this->assertSame( 501, $response->get_status() );
			$this->assertSame( 'application_passwords_unavailable', $response->get_data()['code'] );
		} finally {
			remove_filter( 'wp_is_application_passwords_available', '__return_false' );
		}
	}

	/**
	 * @testdox Token exchange fails when Application Passwords were disabled after token generation.
	 */
	public function test_exchange_token_requires_application_passwords_available(): void {
		wp_set_current_user( $this->admin_id );
		$plaintext = $this->token_from_qr_url( $this->dispatch_generate()->get_data()['qr_url'] );

		add_filter( 'wp_is_application_passwords_available', '__return_false' );

		try {
			wp_set_current_user( 0 );
			$response = $this->dispatch_exchange( $plaintext );

			$this->assertSame( 501, $response->get_status() );
			$this->assertSame( 'application_passwords_unavailable', $response->get_data()['code'] );
			$this->assertCount( 0, WP_Application_Passwords::get_user_application_passwords( $this->admin_id ) );
			$this->assertIsArray( get_transient( $this->token_transient_key( $plaintext ) ) );
			$this->assertFalse( get_option( $this->token_claim_key( $plaintext ), false ) );
		} finally {
			remove_filter( 'wp_is_application_passwords_available', '__return_false' );
		}
	}

	/**
	 * @testdox Token exchange fails when the target user lacks the create_app_password capability.
	 */
	public function test_exchange_token_requires_create_app_password_capability(): void {
		wp_set_current_user( $this->admin_id );
		$plaintext = $this->token_from_qr_url( $this->dispatch_generate()->get_data()['qr_url'] );

		$deny_create_app_password = function ( $caps, $cap ) {
			if ( 'create_app_password' === $cap ) {
				return array( 'do_not_allow' );
			}
			return $caps;
		};
		add_filter( 'map_meta_cap', $deny_create_app_password, 10, 2 );

		try {
			wp_set_current_user( 0 );
			$response = $this->dispatch_exchange( $plaintext );

			$this->assertSame( rest_authorization_required_code(), $response->get_status() );
			$this->assertSame( 'rest_cannot_create_application_passwords', $response->get_data()['code'] );
			$this->assertCount( 0, WP_Application_Passwords::get_user_application_passwords( $this->admin_id ) );
			$this->assertIsArray( get_transient( $this->token_transient_key( $plaintext ) ) );
			$this->assertFalse( get_option( $this->token_claim_key( $plaintext ), false ) );
		} finally {
			remove_filter( 'map_meta_cap', $deny_create_app_password, 10 );
		}
	}

	/**
	 * @testdox Successful generation persists the sha256 hash of the token in a transient, not the plaintext.
	 */
	public function test_generate_token_stores_hashed_token_in_transient(): void {
		wp_set_current_user( $this->admin_id );

		$response = $this->dispatch_generate();
		$this->assertSame( 200, $response->get_status() );

		$plaintext = $this->token_from_qr_url( $response->get_data()['qr_url'] );
		$this->assertNotEmpty( $plaintext );

		// The plaintext itself should NOT be a transient key.
		$this->assertFalse(
			get_transient( MobileAppQRLogin::TOKEN_TRANSIENT_PREFIX . $plaintext ),
			'Plaintext token must not be used as the transient key.'
		);

		// The SHA256 hash of the plaintext IS the transient key.
		$token_data = get_transient( $this->token_transient_key( $plaintext ) );
		$this->assertIsArray( $token_data );
		$this->assertSame( $this->admin_id, $token_data['user_id'] );
		$this->assertSame( get_site_url(), $token_data['site_url'] );
		$this->assertGreaterThan( time(), $token_data['expires_at'] );
		$this->assertLessThanOrEqual( time() + MobileAppQRLogin::TOKEN_TTL, $token_data['expires_at'] );
	}

	/**
	 * @testdox Token generation enforces the per-user rate limit and rejects the request after the window cap is reached.
	 */
	public function test_generate_token_rate_limit_boundary(): void {
		wp_set_current_user( $this->admin_id );

		for ( $i = 1; $i <= MobileAppQRLogin::MAX_TOKENS_PER_WINDOW; $i++ ) {
			$response = $this->dispatch_generate();
			$this->assertSame(
				200,
				$response->get_status(),
				sprintf( 'Request #%d within the window should succeed.', $i )
			);
		}

		$response = $this->dispatch_generate();
		$this->assertSame( 429, $response->get_status() );
		$this->assertSame( 'rate_limit_exceeded', $response->get_data()['code'] );

		$row = $this->get_qr_login_rate_limit_row( QRLoginRateLimits::BUCKET_GENERATION, (string) $this->admin_id );
		$this->assertNotNull( $row );
		$this->assertSame(
			QRLoginRateLimits::get_action_id( QRLoginRateLimits::BUCKET_GENERATION, (string) $this->admin_id ),
			$row->rate_limit_key
		);
		$this->assertSame( 0, (int) $row->rate_limit_remaining );
		$this->assertSame( 0, $this->get_qr_login_rate_limit_transient_count() );
		$this->assertNull(
			$this->get_qr_login_rate_limit_row( QRLoginRateLimits::BUCKET_EXCHANGE_IP, '203.0.113.10' ),
			'Invalid-token traffic must not consume the broad exchange-IP bucket.'
		);
	}

	/**
	 * @testdox Token generation rate limit is bucketed per user so one user exhausting their quota does not affect another.
	 */
	public function test_generate_token_rate_limit_is_per_user(): void {
		wp_set_current_user( $this->admin_id );
		for ( $i = 0; $i < MobileAppQRLogin::MAX_TOKENS_PER_WINDOW; $i++ ) {
			$this->dispatch_generate();
		}
		$this->assertSame( 429, $this->dispatch_generate()->get_status() );

		// Switch to the shop manager — should start with a fresh bucket.
		wp_set_current_user( $this->shop_manager_id );
		$this->assertSame( 200, $this->dispatch_generate()->get_status() );
	}

	// -----------------------------------------------------------------------
	// Exchange: happy path + error paths.
	// -----------------------------------------------------------------------

	/**
	 * @testdox Token exchange returns Application Password credentials on the happy path.
	 */
	public function test_exchange_token_happy_path(): void {
		wp_set_current_user( $this->admin_id );
		$plaintext = $this->token_from_qr_url( $this->dispatch_generate()->get_data()['qr_url'] );

		// Unauthenticated exchange (as the mobile app would perform it).
		wp_set_current_user( 0 );
		$response = $this->dispatch_exchange( $plaintext );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'user_login', $data );
		$this->assertArrayHasKey( 'user_email', $data );
		$this->assertArrayHasKey( 'user_id', $data );
		$this->assertArrayHasKey( 'site_url', $data );
		$this->assertArrayHasKey( 'application_password', $data );
		$this->assertArrayHasKey( 'uuid', $data );

		$this->assertSame( $this->admin_id, $data['user_id'] );
		$this->assertSame( get_site_url(), $data['site_url'] );
		$this->assertNotEmpty( $data['application_password'] );

		// Confirm the Application Password is actually persisted for the user.
		$aps = WP_Application_Passwords::get_user_application_passwords( $this->admin_id );
		$this->assertCount( 1, $aps );
		$this->assertSame( $data['uuid'], $aps[0]['uuid'] );
	}

	/**
	 * @testdox Token exchange rejects unknown or tampered tokens with invalid_token.
	 */
	public function test_exchange_token_rejects_invalid_token(): void {
		$response = $this->dispatch_exchange( 'definitely-not-a-real-token' );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'invalid_token', $response->get_data()['code'] );
	}

	/**
	 * @testdox Token exchange rejects tokens whose stored expires_at is in the past with token_expired.
	 */
	public function test_exchange_token_rejects_expired_token(): void {
		wp_set_current_user( $this->admin_id );
		$plaintext = $this->token_from_qr_url( $this->dispatch_generate()->get_data()['qr_url'] );

		$transient_key            = $this->token_transient_key( $plaintext );
		$token_data               = get_transient( $transient_key );
		$token_data['expires_at'] = time() - 60;
		set_transient( $transient_key, $token_data, MobileAppQRLogin::TOKEN_TTL );

		wp_set_current_user( 0 );
		$response = $this->dispatch_exchange( $plaintext );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'token_expired', $response->get_data()['code'] );
	}

	/**
	 * @testdox Tokens are single-use and the second exchange attempt fails with invalid_token.
	 */
	public function test_exchange_token_is_single_use(): void {
		wp_set_current_user( $this->admin_id );
		$plaintext = $this->token_from_qr_url( $this->dispatch_generate()->get_data()['qr_url'] );

		wp_set_current_user( 0 );
		$first  = $this->dispatch_exchange( $plaintext );
		$second = $this->dispatch_exchange( $plaintext );

		$this->assertSame( 200, $first->get_status() );
		$this->assertSame( 401, $second->get_status() );
		$this->assertSame( 'invalid_token', $second->get_data()['code'] );
	}

	/**
	 * @testdox An active database claim blocks a duplicate exchange before an Application Password is created.
	 */
	public function test_exchange_token_active_claim_blocks_duplicate_exchange(): void {
		wp_set_current_user( $this->admin_id );
		$plaintext = $this->token_from_qr_url( $this->dispatch_generate()->get_data()['qr_url'] );
		$claim_key = $this->token_claim_key( $plaintext );

		$this->assertTrue(
			add_option( $claim_key, (string) ( time() + MobileAppQRLogin::TOKEN_TTL ), '', false )
		);

		wp_set_current_user( 0 );
		$response = $this->dispatch_exchange( $plaintext );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'invalid_token', $response->get_data()['code'] );
		$this->assertCount( 0, WP_Application_Passwords::get_user_application_passwords( $this->admin_id ) );
		$this->assertIsArray( get_transient( $this->token_transient_key( $plaintext ) ) );
	}

	/**
	 * @testdox A stale database claim is cleaned up and the token can be exchanged.
	 */
	public function test_exchange_token_reclaims_stale_claim(): void {
		wp_set_current_user( $this->admin_id );
		$plaintext = $this->token_from_qr_url( $this->dispatch_generate()->get_data()['qr_url'] );
		$claim_key = $this->token_claim_key( $plaintext );

		$this->assertTrue(
			add_option( $claim_key, (string) ( time() - 60 ), '', false )
		);

		wp_set_current_user( 0 );
		$response = $this->dispatch_exchange( $plaintext );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, WP_Application_Passwords::get_user_application_passwords( $this->admin_id ) );
		$this->assertFalse( get_option( $claim_key, false ) );
	}

	/**
	 * @testdox Token exchange returns 404 user_not_found when the associated user was deleted between generation and exchange.
	 */
	public function test_exchange_token_rejects_missing_user(): void {
		wp_set_current_user( $this->admin_id );
		$plaintext = $this->token_from_qr_url( $this->dispatch_generate()->get_data()['qr_url'] );

		wp_delete_user( $this->admin_id );
		// Avoid double-delete in tearDown().
		$this->admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );

		wp_set_current_user( 0 );
		$response = $this->dispatch_exchange( $plaintext );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'user_not_found', $response->get_data()['code'] );
	}

	/**
	 * @testdox Token exchange surfaces application_password_failed with status 500 when Application Password creation fails.
	 */
	public function test_exchange_token_handles_application_password_creation_failure(): void {
		wp_set_current_user( $this->admin_id );
		$plaintext = $this->token_from_qr_url( $this->dispatch_generate()->get_data()['qr_url'] );

		$deny_meta = function ( $check, $object_id, $meta_key ) {
			unset( $object_id );
			if ( '_application_passwords' === $meta_key ) {
				return false;
			}
			return $check;
		};
		add_filter( 'update_user_metadata', $deny_meta, 10, 3 );

		try {
			wp_set_current_user( 0 );
			$response = $this->dispatch_exchange( $plaintext );

			$this->assertSame( 500, $response->get_status() );
			$this->assertSame( 'application_password_failed', $response->get_data()['code'] );
			$this->assertIsArray( get_transient( $this->token_transient_key( $plaintext ) ) );
			$this->assertFalse( get_option( $this->token_claim_key( $plaintext ), false ) );
		} finally {
			remove_filter( 'update_user_metadata', $deny_meta, 10 );
		}

		$retry = $this->dispatch_exchange( $plaintext );

		$this->assertSame( 200, $retry->get_status() );
		$this->assertCount( 1, WP_Application_Passwords::get_user_application_passwords( $this->admin_id ) );
	}

	/**
	 * @testdox Token exchange enforces the invalid-token rate limit and rejects requests after the window cap is reached.
	 */
	public function test_exchange_token_rate_limit_boundary(): void {
		// Burn the invalid-token quota with random tokens.
		for ( $i = 1; $i <= MobileAppQRLogin::MAX_INVALID_EXCHANGE_ATTEMPTS; $i++ ) {
			$response = $this->dispatch_exchange( 'bad-token-' . $i );
			$this->assertSame(
				401,
				$response->get_status(),
				sprintf( 'Invalid-token response expected within the rate window, got %d on attempt %d.', $response->get_status(), $i )
			);
		}

		$response = $this->dispatch_exchange( 'bad-token-final' );
		$this->assertSame( 429, $response->get_status() );
		$this->assertSame( 'rate_limit_exceeded', $response->get_data()['code'] );

		$row = $this->get_qr_login_rate_limit_row( QRLoginRateLimits::BUCKET_INVALID_EXCHANGE, '203.0.113.10' );
		$this->assertNotNull( $row );
		$this->assertSame(
			QRLoginRateLimits::get_action_id( QRLoginRateLimits::BUCKET_INVALID_EXCHANGE, '203.0.113.10' ),
			$row->rate_limit_key
		);
		$this->assertSame( 0, (int) $row->rate_limit_remaining );
		$this->assertSame( 0, $this->get_qr_login_rate_limit_transient_count() );
	}

	/**
	 * @testdox Invalid-token exchange rate limit is bucketed per IP so a different IP gets its own fresh quota.
	 */
	public function test_exchange_token_rate_limit_is_per_ip(): void {
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
		for ( $i = 0; $i < MobileAppQRLogin::MAX_INVALID_EXCHANGE_ATTEMPTS; $i++ ) {
			$this->dispatch_exchange( 'bad-' . $i );
		}
		$this->assertSame( 429, $this->dispatch_exchange( 'bad-final' )->get_status() );

		// Different IP → fresh bucket.
		$_SERVER['REMOTE_ADDR'] = '198.51.100.25';
		$this->assertSame( 401, $this->dispatch_exchange( 'new-ip' )->get_status() );
	}

	/**
	 * @testdox QR login rate limits are persisted in wc_rate_limits and reset after expiry.
	 */
	public function test_qr_login_rate_limits_are_persistent_and_reset_after_expiry(): void {
		global $wpdb;

		$bucket     = QRLoginRateLimits::BUCKET_INVALID_EXCHANGE;
		$identifier = '203.0.113.10';
		$key        = QRLoginRateLimits::get_action_id( $bucket, $identifier );

		for ( $i = 0; $i < MobileAppQRLogin::MAX_INVALID_EXCHANGE_ATTEMPTS; $i++ ) {
			$this->assertTrue( QRLoginRateLimits::consume( $bucket, $identifier ) );
		}

		$this->assertFalse( QRLoginRateLimits::consume( $bucket, $identifier ) );

		$row = $this->get_qr_login_rate_limit_row( $bucket, $identifier );
		$this->assertNotNull( $row );
		$this->assertSame( $key, $row->rate_limit_key );
		$this->assertSame( 0, (int) $row->rate_limit_remaining );
		$this->assertSame( 0, $this->get_qr_login_rate_limit_transient_count() );

		$wpdb->update(
			$wpdb->prefix . 'wc_rate_limits',
			array( 'rate_limit_expiry' => time() - 1 ),
			array( 'rate_limit_key' => $key ),
			array( '%d' ),
			array( '%s' )
		);

		$this->assertTrue( QRLoginRateLimits::consume( $bucket, $identifier ) );

		$row = $this->get_qr_login_rate_limit_row( $bucket, $identifier );
		$this->assertNotNull( $row );
		$this->assertSame( MobileAppQRLogin::MAX_INVALID_EXCHANGE_ATTEMPTS - 1, (int) $row->rate_limit_remaining );
	}

	/**
	 * @testdox Random invalid exchange attempts do not exhaust a later valid-token exchange from the same IP.
	 */
	public function test_invalid_exchange_attempts_do_not_block_valid_token_from_same_ip(): void {
		wp_set_current_user( $this->admin_id );
		$plaintext = $this->token_from_qr_url( $this->dispatch_generate()->get_data()['qr_url'] );

		wp_set_current_user( 0 );
		for ( $i = 0; $i < MobileAppQRLogin::MAX_EXCHANGE_ATTEMPTS; $i++ ) {
			$this->assertSame( 401, $this->dispatch_exchange( 'random-invalid-' . $i )->get_status() );
		}
		$this->assertNull(
			$this->get_qr_login_rate_limit_row( QRLoginRateLimits::BUCKET_EXCHANGE_IP, '203.0.113.10' ),
			'Invalid-token traffic must not create a broad exchange-IP row.'
		);

		$response = $this->dispatch_exchange( $plaintext );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, WP_Application_Passwords::get_user_application_passwords( $this->admin_id ) );
		$this->assertNotNull(
			$this->get_qr_login_rate_limit_row( QRLoginRateLimits::BUCKET_EXCHANGE_IP, '203.0.113.10' ),
			'Valid-token exchange traffic should still consume the broad exchange-IP guard.'
		);
	}

	/**
	 * @testdox Valid-token exchange attempts are limited per token.
	 */
	public function test_valid_exchange_attempts_are_limited_per_token(): void {
		wp_set_current_user( $this->admin_id );
		$plaintext = $this->token_from_qr_url( $this->dispatch_generate()->get_data()['qr_url'] );

		add_filter( 'wp_is_application_passwords_available', '__return_false' );

		try {
			wp_set_current_user( 0 );
			for ( $i = 1; $i <= MobileAppQRLogin::MAX_EXCHANGE_ATTEMPTS; $i++ ) {
				$response = $this->dispatch_exchange( $plaintext );
				$this->assertSame(
					501,
					$response->get_status(),
					sprintf( 'Application Passwords unavailable response expected before the valid-token cap, got %d on attempt %d.', $response->get_status(), $i )
				);
			}

			$response = $this->dispatch_exchange( $plaintext );

			$this->assertSame( 429, $response->get_status() );
			$this->assertSame( 'rate_limit_exceeded', $response->get_data()['code'] );
			$this->assertIsArray( get_transient( $this->token_transient_key( $plaintext ) ) );
			$this->assertFalse( get_option( $this->token_claim_key( $plaintext ), false ) );
		} finally {
			remove_filter( 'wp_is_application_passwords_available', '__return_false' );
		}
	}

	// -----------------------------------------------------------------------
	// Schema / response shape.
	// -----------------------------------------------------------------------

	/**
	 * @testdox The generate-token response exposes exactly qr_url, expires_at, and ttl.
	 */
	public function test_generate_response_schema(): void {
		wp_set_current_user( $this->admin_id );

		$data = $this->dispatch_generate()->get_data();

		$this->assertEqualsCanonicalizing(
			array( 'qr_url', 'expires_at', 'ttl' ),
			array_keys( $data )
		);
		$this->assertIsString( $data['qr_url'] );
		$this->assertIsInt( $data['expires_at'] );
		$this->assertIsInt( $data['ttl'] );
	}

	/**
	 * @testdox The exchange-token response exposes the documented fields on success.
	 */
	public function test_exchange_response_schema(): void {
		wp_set_current_user( $this->admin_id );
		$plaintext = $this->token_from_qr_url( $this->dispatch_generate()->get_data()['qr_url'] );

		wp_set_current_user( 0 );
		$data = $this->dispatch_exchange( $plaintext )->get_data();

		$this->assertEqualsCanonicalizing(
			array( 'success', 'user_login', 'user_email', 'user_id', 'site_url', 'application_password', 'uuid' ),
			array_keys( $data )
		);
		$this->assertTrue( $data['success'] );
		$this->assertIsString( $data['user_login'] );
		$this->assertIsString( $data['user_email'] );
		$this->assertIsInt( $data['user_id'] );
		$this->assertIsString( $data['site_url'] );
		$this->assertIsString( $data['application_password'] );
		$this->assertIsString( $data['uuid'] );
	}

	/**
	 * @testdox The QR URL scheme is stable because the mobile apps depend on it exactly.
	 */
	public function test_qr_url_scheme_is_stable(): void {
		wp_set_current_user( $this->admin_id );

		$qr_url = $this->dispatch_generate()->get_data()['qr_url'];

		$this->assertMatchesRegularExpression(
			'#^woocommerce://qr-login\?token=[^&]+&siteUrl=[^&]+$#',
			$qr_url
		);
	}
}
