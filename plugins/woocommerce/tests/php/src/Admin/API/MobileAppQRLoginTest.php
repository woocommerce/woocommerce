<?php
/**
 * Tests for the MobileAppQRLogin REST controller.
 *
 * @package WooCommerce\Admin\Tests\Admin\API
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\API;

use Automattic\WooCommerce\Admin\API\MobileAppQRLogin;
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
	 * Original value of $_SERVER['REMOTE_ADDR'] (if any) before each test.
	 *
	 * @var string|null
	 */
	private $original_remote_addr;

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
		$this->original_https       = isset( $_SERVER['HTTPS'] ) ? (string) $_SERVER['HTTPS'] : null;
		$this->original_remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : null;
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Default to HTTPS on for most tests; disable explicitly where needed.
		$this->force_https( true );

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

		// Clear any transients the tests may have written.
		$this->delete_all_qr_login_transients();

		// Restore $_SERVER state.
		if ( null === $this->original_https ) {
			unset( $_SERVER['HTTPS'] );
		} else {
			$_SERVER['HTTPS'] = $this->original_https;
		}

		if ( null === $this->original_remote_addr ) {
			unset( $_SERVER['REMOTE_ADDR'] );
		} else {
			$_SERVER['REMOTE_ADDR'] = $this->original_remote_addr;
		}

		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );

		parent::tearDown();
	}

	/**
	 * Toggle HTTPS state for `is_ssl()` checks.
	 *
	 * @param bool $on Whether HTTPS should appear enabled.
	 */
	private function force_https( bool $on ): void {
		if ( $on ) {
			$_SERVER['HTTPS'] = 'on';
		} else {
			unset( $_SERVER['HTTPS'] );
		}
	}

	/**
	 * Delete every transient created by the controller (token + rate limit).
	 */
	private function delete_all_qr_login_transients(): void {
		global $wpdb;

		// Remove token transients keyed by sha256 hash (and their _timeout siblings).
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_\\_wc\\_qr\\_login\\_token\\_%' ESCAPE '\\\\' OR option_name LIKE '\\_transient\\_timeout\\_\\_wc\\_qr\\_login\\_token\\_%' ESCAPE '\\\\'"
		);

		// Remove rate-limit transients.
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_\\_wc\\_qr\\_login\\_rate\\_%' ESCAPE '\\\\' OR option_name LIKE '\\_transient\\_timeout\\_\\_wc\\_qr\\_login\\_rate\\_%' ESCAPE '\\\\'"
		);

		wp_cache_flush();
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
	 * An administrator should receive a token + qr_url on the happy path.
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
	 * A shop manager should also succeed because they have `manage_woocommerce`.
	 */
	public function test_generate_token_happy_path_for_shop_manager(): void {
		wp_set_current_user( $this->shop_manager_id );

		$response = $this->dispatch_generate();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'qr_url', $response->get_data() );
	}

	/**
	 * An unauthenticated request should be rejected with a 401.
	 */
	public function test_generate_token_rejects_unauthenticated(): void {
		wp_set_current_user( 0 );

		$response = $this->dispatch_generate();

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
		$this->assertSame( 'woocommerce_rest_cannot_view', $response->get_data()['code'] );
	}

	/**
	 * A subscriber should be rejected by the capability check.
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
	 * Non-HTTPS requests from an authorized user return `ssl_required`.
	 */
	public function test_generate_token_requires_https(): void {
		wp_set_current_user( $this->admin_id );
		$this->force_https( false );

		$response = $this->dispatch_generate();

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'ssl_required', $response->get_data()['code'] );
	}

	/**
	 * When Application Passwords are disabled site-wide, the endpoint returns 501.
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
	 * Successful generation persists the token hash (not the plaintext) in a transient.
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
		$token_data = get_transient( MobileAppQRLogin::TOKEN_TRANSIENT_PREFIX . hash( 'sha256', $plaintext ) );
		$this->assertIsArray( $token_data );
		$this->assertSame( $this->admin_id, $token_data['user_id'] );
		$this->assertSame( get_site_url(), $token_data['site_url'] );
		$this->assertGreaterThan( time(), $token_data['expires_at'] );
		$this->assertLessThanOrEqual( time() + MobileAppQRLogin::TOKEN_TTL, $token_data['expires_at'] );
	}

	/**
	 * Rate limit: 5 tokens per user per window succeed, the 6th returns 429.
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
	}

	/**
	 * Generation rate limit is bucketed per-user: one user hitting the limit
	 * does not affect another user.
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
	 * Happy path for exchange: valid token → 200, returns AP credentials.
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
	 * An unknown / tampered token returns `invalid_token`.
	 */
	public function test_exchange_token_rejects_invalid_token(): void {
		$response = $this->dispatch_exchange( 'definitely-not-a-real-token' );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'invalid_token', $response->get_data()['code'] );
	}

	/**
	 * A token whose stored `expires_at` is in the past returns `token_expired`.
	 *
	 * We manually overwrite the transient so we can simulate expiry without
	 * waiting 5 real minutes.
	 */
	public function test_exchange_token_rejects_expired_token(): void {
		wp_set_current_user( $this->admin_id );
		$plaintext = $this->token_from_qr_url( $this->dispatch_generate()->get_data()['qr_url'] );

		$transient_key            = MobileAppQRLogin::TOKEN_TRANSIENT_PREFIX . hash( 'sha256', $plaintext );
		$token_data               = get_transient( $transient_key );
		$token_data['expires_at'] = time() - 60;
		set_transient( $transient_key, $token_data, MobileAppQRLogin::TOKEN_TTL );

		wp_set_current_user( 0 );
		$response = $this->dispatch_exchange( $plaintext );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'token_expired', $response->get_data()['code'] );
	}

	/**
	 * A token can only be redeemed once — the second exchange fails with invalid_token.
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
	 * If the token's user was deleted between generation and exchange, return 404.
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
	 * If Application Password creation fails, the endpoint surfaces
	 * `application_password_failed` with a 500 status.
	 *
	 * We force the failure by short-circuiting `update_user_metadata` for the
	 * `_application_passwords` key. `WP_Application_Passwords::set_user_application_passwords()`
	 * calls `update_user_meta()` under the hood; when the short-circuit filter returns
	 * `false` the meta update is reported as failed, which causes
	 * `create_new_application_password()` to return `WP_Error( 'db_error', ... )`.
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
		} finally {
			remove_filter( 'update_user_metadata', $deny_meta, 10 );
		}
	}

	/**
	 * Exchange rate limit: 10 attempts per IP per window succeed, the 11th returns 429.
	 */
	public function test_exchange_token_rate_limit_boundary(): void {
		// Burn the quota with invalid tokens (each still counts toward the limit).
		for ( $i = 1; $i <= MobileAppQRLogin::MAX_EXCHANGE_ATTEMPTS; $i++ ) {
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
	}

	/**
	 * Exchange rate limit is bucketed per-IP: attackers on different IPs each get their
	 * own bucket.
	 */
	public function test_exchange_token_rate_limit_is_per_ip(): void {
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
		for ( $i = 0; $i < MobileAppQRLogin::MAX_EXCHANGE_ATTEMPTS; $i++ ) {
			$this->dispatch_exchange( 'bad-' . $i );
		}
		$this->assertSame( 429, $this->dispatch_exchange( 'bad-final' )->get_status() );

		// Different IP → fresh bucket.
		$_SERVER['REMOTE_ADDR'] = '198.51.100.25';
		$this->assertSame( 401, $this->dispatch_exchange( 'new-ip' )->get_status() );
	}

	// -----------------------------------------------------------------------
	// Schema / response shape.
	// -----------------------------------------------------------------------

	/**
	 * The generate response exposes exactly `qr_url`, `expires_at`, `ttl`.
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
	 * The exchange response exposes the documented fields on success.
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
	 * The QR URL scheme is stable — the mobile apps depend on it exactly.
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
