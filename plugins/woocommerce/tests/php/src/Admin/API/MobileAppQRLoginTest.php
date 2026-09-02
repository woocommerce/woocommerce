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
use WC_Unit_Test_Case;
use WP_Application_Passwords;
use WP_REST_Request;
use WP_REST_Server;
use WP_UnitTest_Factory;

/**
 * MobileAppQRLogin API controller test.
 *
 * @class MobileAppQRLoginTest.
 */
class MobileAppQRLoginTest extends WC_Unit_Test_Case {

	/**
	 * REST server used to dispatch QR login requests.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	/**
	 * QR login controller registered on the test server.
	 *
	 * @var MobileAppQRLogin
	 */
	private $controller;

	/**
	 * Administrator fixture user ID.
	 *
	 * @var int
	 */
	private static $fixture_admin_id;

	/**
	 * Shop manager fixture user ID.
	 *
	 * @var int
	 */
	private static $fixture_shop_manager_id;

	/**
	 * Subscriber fixture user ID.
	 *
	 * @var int
	 */
	private static $fixture_subscriber_id;

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
	 * Token status endpoint (polled by wc-admin while the QR is on screen).
	 *
	 * @var string
	 */
	const STATUS_ENDPOINT = '/wc-admin/mobile-app/qr-login-status';

	/**
	 * Application Password revoke endpoint.
	 *
	 * @var string
	 */
	const REVOKE_ENDPOINT = '/wc-admin/mobile-app/qr-login-revoke';

	/**
	 * Up-front capability probe endpoint (`/qr-login-availability`).
	 *
	 * @var string
	 */
	const AVAILABILITY_ENDPOINT = '/wc-admin/mobile-app/qr-login-availability';

	/**
	 * Number-match scan endpoint (Task 7).
	 *
	 * @var string
	 */
	const SCAN_ENDPOINT = '/wc-admin/mobile-app/qr-login-scan';

	/**
	 * Number-match approve endpoint (Task 7).
	 *
	 * @var string
	 */
	const APPROVE_ENDPOINT = '/wc-admin/mobile-app/qr-login-approve';

	/**
	 * Mobile-side session-status polling endpoint (Task 7).
	 *
	 * @var string
	 */
	const SESSION_STATUS_ENDPOINT = '/wc-admin/mobile-app/qr-login-session-status';

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
	 * Create immutable users shared by the test class.
	 *
	 * @param WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$fixture_admin_id        = $factory->user->create( array( 'role' => 'administrator' ) );
		self::$fixture_shop_manager_id = $factory->user->create( array( 'role' => 'shop_manager' ) );
		self::$fixture_subscriber_id   = $factory->user->create( array( 'role' => 'subscriber' ) );
	}

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->controller = new MobileAppQRLogin();
		$this->server     = $this->create_rest_server_with_routes(
			array( array( $this->controller, 'register_routes' ) ),
			true
		);

		$this->admin_id        = self::$fixture_admin_id;
		$this->shop_manager_id = self::$fixture_shop_manager_id;
		$this->subscriber_id   = self::$fixture_subscriber_id;

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
		$this->clear_rest_server();
		unset( $this->server, $this->controller );

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

		// Remove "consumed" transients written by exchange_token() so the
		// status endpoint can surface them to the wc-admin polling client.
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_\\_wc\\_qr\\_login\\_consumed\\_%' ESCAPE '\\\\' OR option_name LIKE '\\_transient\\_timeout\\_\\_wc\\_qr\\_login\\_consumed\\_%' ESCAPE '\\\\'"
		);

		// Remove rate-limit rows.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}wc_rate_limits WHERE rate_limit_key LIKE %s",
				$wpdb->esc_like( QRLoginRateLimits::KEY_PREFIX ) . '%'
			)
		);

		// Remove Task 7 session-id to token-hash mapping transients written by /qr-login-scan.
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_\\_wc\\_qr\\_login\\_session\\_%' ESCAPE '\\\\' OR option_name LIKE '\\_transient\\_timeout\\_\\_wc\\_qr\\_login\\_session\\_%' ESCAPE '\\\\'"
		);

		// Remove database-backed token exchange claims.
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_wc\\_qr\\_login\\_claim\\_%' ESCAPE '\\\\'"
		);

		// Remove database-backed token scan claims.
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_wc\\_qr\\_login\\_scan\\_claim\\_%' ESCAPE '\\\\'"
		);

		// Remove database-backed token approval claims.
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_wc\\_qr\\_login\\_approve\\_claim\\_%' ESCAPE '\\\\'"
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
	 * Build the database scan-claim option key for a plaintext token.
	 *
	 * @param string $token Plaintext token.
	 * @return string
	 */
	private function token_scan_claim_key( string $token ): string {
		return MobileAppQRLogin::SCAN_CLAIM_OPTION_PREFIX . $this->token_hash( $token );
	}

	/**
	 * Build the database approval-claim option key for a plaintext token.
	 *
	 * @param string $token Plaintext token.
	 * @return string
	 */
	private function token_approve_claim_key( string $token ): string {
		return MobileAppQRLogin::APPROVE_CLAIM_OPTION_PREFIX . $this->token_hash( $token );
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
	 * @param string|null $grant Optional `exchange_grant` nonce returned by /qr-login-approve.
	 * @return \WP_REST_Response
	 */
	private function dispatch_exchange( ?string $token, ?string $grant = null ): \WP_REST_Response {
		$request = new WP_REST_Request( 'POST', self::EXCHANGE_ENDPOINT );
		if ( null !== $token ) {
			$request->set_param( 'token', $token );
		}
		if ( null !== $grant ) {
			$request->set_param( 'exchange_grant', $grant );
		}
		return $this->server->dispatch( $request );
	}

	/**
	 * Issue a POST to the /qr-login-scan endpoint (Task 7).
	 *
	 * @param string|null                $token                     Token to scan.
	 * @param array<string, string>|null $device                    Optional device payload.
	 * @param bool                       $supports_number_matching  Capability flag (defaults true so happy-path tests don't have to set it).
	 * @return \WP_REST_Response
	 */
	private function dispatch_scan( ?string $token, ?array $device = null, bool $supports_number_matching = true ): \WP_REST_Response {
		$request = new WP_REST_Request( 'POST', self::SCAN_ENDPOINT );
		if ( null !== $token ) {
			$request->set_param( 'token', $token );
		}
		// /qr-login-scan now requires `device` at the schema level. Default to
		// a generic Android-like payload when callers don't supply one — most
		// existing tests don't care about the device contents, just that the
		// scan/approve flow advances the state machine.
		if ( null === $device ) {
			$device = array(
				'os'          => 'Android',
				'os_version'  => '16',
				'model'       => 'Test Device',
				'app_version' => '24.7.0',
			);
		}
		$request->set_param( 'device', $device );
		$request->set_param( 'supports_number_matching', $supports_number_matching );
		return $this->server->dispatch( $request );
	}

	/**
	 * Issue a POST to the /qr-login-approve endpoint (Task 7).
	 *
	 * @param string|null $token  Token to approve.
	 * @param string|null $choice Number the merchant tapped on wc-admin.
	 * @return \WP_REST_Response
	 */
	private function dispatch_approve( ?string $token, ?string $choice ): \WP_REST_Response {
		$request = new WP_REST_Request( 'POST', self::APPROVE_ENDPOINT );
		if ( null !== $token ) {
			$request->set_param( 'token', $token );
		}
		if ( null !== $choice ) {
			$request->set_param( 'choice', $choice );
		}
		return $this->server->dispatch( $request );
	}

	/**
	 * Issue a GET to the /qr-login-session-status endpoint (Task 7).
	 *
	 * The endpoint binds grant delivery to proof of token knowledge — every
	 * call must send the SHA-256 hash of the plaintext token alongside the
	 * session id. Tests that drive the happy path supply both; tests that
	 * exercise the mismatch / missing-hash branches pass `$token_plaintext`
	 * explicitly (or `null` to omit the hash entirely).
	 *
	 * @param string|null $session_id      The session id from /qr-login-scan.
	 * @param string|null $token_plaintext Plaintext token. SHA-256'd before sending. `null` omits the parameter.
	 * @return \WP_REST_Response
	 */
	private function dispatch_session_status( ?string $session_id, ?string $token_plaintext = null ): \WP_REST_Response {
		$request = new WP_REST_Request( 'GET', self::SESSION_STATUS_ENDPOINT );
		if ( null !== $session_id ) {
			$request->set_param( 'session_id', $session_id );
		}
		if ( null !== $token_plaintext ) {
			$request->set_param( 'token_hash', hash( 'sha256', $token_plaintext ) );
		}
		return $this->server->dispatch( $request );
	}

	/**
	 * Helper: do the full pre-exchange flow (scan + approve) and return the
	 * data needed to call /qr-login-exchange. Existing happy-path tests that
	 * predate the number-matching step (Task 5/6) call this so they don't
	 * have to be rewritten end-to-end.
	 *
	 * Falls back to a generic device payload when none is supplied — the
	 * /qr-login-scan endpoint requires `device` and `supports_number_matching`,
	 * so tests that don't care about the device contents still need *something*
	 * to thread through the scan call.
	 *
	 * @param string                     $plaintext Token from /qr-login-token.
	 * @param array<string, string>|null $device    Optional device payload.
	 * @return array{session_id: string, exchange_grant: string}
	 */
	private function complete_pre_exchange_flow( string $plaintext, ?array $device = null ): array {
		if ( null === $device ) {
			$device = array(
				'os'          => 'Android',
				'os_version'  => '16',
				'model'       => 'Test Device',
				'app_version' => '24.7.0',
			);
		}

		// Mobile-side scan (unauthenticated).
		wp_set_current_user( 0 );
		$scan_response = $this->dispatch_scan( $plaintext, $device );
		$this->assertSame( 200, $scan_response->get_status(), 'Pre-exchange helper expected /scan to succeed.' );
		$scan_data = $scan_response->get_data();

		// Merchant-side approve (auth required).
		wp_set_current_user( $this->admin_id );
		$approve_response = $this->dispatch_approve( $plaintext, $scan_data['real_number'] );
		$this->assertSame( 200, $approve_response->get_status(), 'Pre-exchange helper expected /approve to succeed.' );

		// Mobile-side session-status to retrieve the grant.
		wp_set_current_user( 0 );
		$session_response = $this->dispatch_session_status( $scan_data['session_id'], $plaintext );
		$this->assertSame( 200, $session_response->get_status(), 'Pre-exchange helper expected /session-status to succeed.' );
		$session_data = $session_response->get_data();
		$this->assertArrayHasKey( 'exchange_grant', $session_data, 'session-status should return the grant once approved.' );

		return array(
			'session_id'     => $scan_data['session_id'],
			'exchange_grant' => $session_data['exchange_grant'],
		);
	}

	/**
	 * Issue a POST to the status endpoint.
	 *
	 * @param string|null $token Token to query. Null omits the parameter.
	 * @return \WP_REST_Response
	 */
	private function dispatch_status( ?string $token ): \WP_REST_Response {
		$request = new WP_REST_Request( 'POST', self::STATUS_ENDPOINT );
		if ( null !== $token ) {
			$request->set_param( 'token', $token );
		}
		return $this->server->dispatch( $request );
	}

	/**
	 * Issue a DELETE to the revoke endpoint.
	 *
	 * @param string|null $uuid The Application Password UUID to revoke. Null omits the parameter.
	 * @return \WP_REST_Response
	 */
	private function dispatch_revoke( ?string $uuid ): \WP_REST_Response {
		$request = new WP_REST_Request( 'DELETE', self::REVOKE_ENDPOINT );
		if ( null !== $uuid ) {
			$request->set_param( 'uuid', $uuid );
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
	 * @testdox Token generation rejects a site_url filter that downgrades the final URL to HTTP.
	 */
	public function test_generate_token_rejects_filtered_http_site_url(): void {
		wp_set_current_user( $this->admin_id );

		$downgrade_site_url = static function () {
			return 'http://filtered.example.com';
		};
		add_filter( 'site_url', $downgrade_site_url );

		try {
			$response = $this->dispatch_generate();

			$this->assertSame( 500, $response->get_status() );
			$this->assertSame( 'insecure_site_url', $response->get_data()['code'] );
		} finally {
			remove_filter( 'site_url', $downgrade_site_url );
		}
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
		// Pre-exchange flow runs while AP is still available.
		$prep = $this->prepare_exchange_token();

		// Disable APs only for the exchange itself.
		add_filter( 'wp_is_application_passwords_available', '__return_false' );

		try {
			$response = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );

			$this->assertSame( 501, $response->get_status() );
			$this->assertSame( 'application_passwords_unavailable', $response->get_data()['code'] );
			$this->assertCount( 0, WP_Application_Passwords::get_user_application_passwords( $this->admin_id ) );
			$this->assertSame(
				MobileAppQRLogin::STATE_APPROVED,
				get_transient( $this->token_transient_key( $prep['plaintext'] ) )['state']
			);
			$this->assertFalse( get_option( $this->token_claim_key( $prep['plaintext'] ), false ) );
		} finally {
			remove_filter( 'wp_is_application_passwords_available', '__return_false' );
		}
	}

	/**
	 * @testdox Token exchange fails when the target user lacks the create_app_password capability.
	 */
	public function test_exchange_token_requires_create_app_password_capability(): void {
		$prep = $this->prepare_exchange_token();

		$deny_create_app_password = function ( $caps, $cap ) {
			if ( 'create_app_password' === $cap ) {
				return array( 'do_not_allow' );
			}
			return $caps;
		};
		add_filter( 'map_meta_cap', $deny_create_app_password, 10, 2 );

		try {
			wp_set_current_user( 0 );
			$response = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );

			$this->assertSame( rest_authorization_required_code(), $response->get_status() );
			$this->assertSame( 'rest_cannot_create_application_passwords', $response->get_data()['code'] );
			$this->assertCount( 0, WP_Application_Passwords::get_user_application_passwords( $this->admin_id ) );
			$this->assertIsArray( get_transient( $this->token_transient_key( $prep['plaintext'] ) ) );
			$this->assertFalse( get_option( $this->token_claim_key( $prep['plaintext'] ), false ) );
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
		$prep = $this->prepare_exchange_token();

		// Unauthenticated exchange (as the mobile app would perform it).
		$response = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );

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
		$prep = $this->prepare_exchange_token();

		// Force the stored expires_at into the past, then try to exchange.
		$transient_key            = $this->token_transient_key( $prep['plaintext'] );
		$token_data               = get_transient( $transient_key );
		$token_data['expires_at'] = time() - 60;
		set_transient( $transient_key, $token_data, MobileAppQRLogin::TOKEN_TTL );

		$response = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'token_expired', $response->get_data()['code'] );
	}

	/**
	 * @testdox Tokens are single-use and the second exchange attempt fails with invalid_token.
	 */
	public function test_exchange_token_is_single_use(): void {
		$prep = $this->prepare_exchange_token();

		$first  = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );
		$second = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );

		$this->assertSame( 200, $first->get_status() );
		$this->assertSame( 401, $second->get_status() );
		$this->assertSame( 'invalid_token', $second->get_data()['code'] );
	}

	/**
	 * @testdox An active database claim blocks a duplicate exchange before an Application Password is created.
	 */
	public function test_exchange_token_active_claim_blocks_duplicate_exchange(): void {
		$prep      = $this->prepare_exchange_token();
		$claim_key = $this->token_claim_key( $prep['plaintext'] );

		$this->assertTrue(
			add_option( $claim_key, (string) ( time() + MobileAppQRLogin::TOKEN_TTL ), '', false )
		);

		$response = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'invalid_token', $response->get_data()['code'] );
		$this->assertCount( 0, WP_Application_Passwords::get_user_application_passwords( $this->admin_id ) );
		$this->assertSame(
			MobileAppQRLogin::STATE_APPROVED,
			get_transient( $this->token_transient_key( $prep['plaintext'] ) )['state']
		);
	}

	/**
	 * @testdox A stale database claim is cleaned up and the token can be exchanged.
	 */
	public function test_exchange_token_reclaims_stale_claim(): void {
		$prep      = $this->prepare_exchange_token();
		$claim_key = $this->token_claim_key( $prep['plaintext'] );

		$this->assertTrue(
			add_option( $claim_key, (string) ( time() - 60 ), '', false )
		);

		$response = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, WP_Application_Passwords::get_user_application_passwords( $this->admin_id ) );
		$this->assertFalse( get_option( $claim_key, false ) );
	}

	/**
	 * @testdox Token exchange returns 404 user_not_found when the associated user was deleted between generation and exchange.
	 */
	public function test_exchange_token_rejects_missing_user(): void {
		wp_set_current_user( $this->admin_id );
		$prep = $this->prepare_exchange_token();

		wp_delete_user( $this->admin_id );
		// Avoid double-delete in tearDown().
		$this->admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );

		$response = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'user_not_found', $response->get_data()['code'] );
	}

	/**
	 * @testdox Token exchange surfaces application_password_failed with status 500 when Application Password creation fails.
	 */
	public function test_exchange_token_handles_application_password_creation_failure(): void {
		$prep = $this->prepare_exchange_token();

		$deny_meta = function ( $check, $object_id, $meta_key ) {
			unset( $object_id );
			if ( '_application_passwords' === $meta_key ) {
				return false;
			}
			return $check;
		};
		add_filter( 'update_user_metadata', $deny_meta, 10, 3 );

		try {
			$response = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );

			$this->assertSame( 500, $response->get_status() );
			$this->assertSame( 'application_password_failed', $response->get_data()['code'] );
			$this->assertSame(
				MobileAppQRLogin::STATE_APPROVED,
				get_transient( $this->token_transient_key( $prep['plaintext'] ) )['state']
			);
			$this->assertFalse( get_option( $this->token_claim_key( $prep['plaintext'] ), false ) );
		} finally {
			remove_filter( 'update_user_metadata', $deny_meta, 10 );
		}

		$retry = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );

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
		$prep = $this->prepare_exchange_token();

		for ( $i = 0; $i < MobileAppQRLogin::MAX_EXCHANGE_ATTEMPTS; $i++ ) {
			$this->assertSame( 401, $this->dispatch_exchange( 'random-invalid-' . $i )->get_status() );
		}
		$this->assertNull(
			$this->get_qr_login_rate_limit_row( QRLoginRateLimits::BUCKET_EXCHANGE_IP, '203.0.113.10' ),
			'Invalid-token traffic must not create a broad exchange-IP row.'
		);

		$response = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );

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
		$prep = $this->prepare_exchange_token();

		add_filter( 'wp_is_application_passwords_available', '__return_false' );

		try {
			for ( $i = 1; $i <= MobileAppQRLogin::MAX_EXCHANGE_ATTEMPTS; $i++ ) {
				$response = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );
				$this->assertSame(
					501,
					$response->get_status(),
					sprintf( 'Application Passwords unavailable response expected before the valid-token cap, got %d on attempt %d.', $response->get_status(), $i )
				);
			}

			$response = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );

			$this->assertSame( 429, $response->get_status() );
			$this->assertSame( 'rate_limit_exceeded', $response->get_data()['code'] );
			$this->assertSame(
				MobileAppQRLogin::STATE_APPROVED,
				get_transient( $this->token_transient_key( $prep['plaintext'] ) )['state']
			);
			$this->assertFalse( get_option( $this->token_claim_key( $prep['plaintext'] ), false ) );
		} finally {
			remove_filter( 'wp_is_application_passwords_available', '__return_false' );
		}//end try
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
		$prep = $this->prepare_exchange_token();
		$data = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] )->get_data();

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

	// -----------------------------------------------------------------------
	// Consumed-status persistence on exchange success.
	// -----------------------------------------------------------------------

	/**
	 * Convenience: generate a token as $admin_id and return the plaintext.
	 *
	 * @return string Plaintext token.
	 */
	private function generate_token_as_admin(): string {
		wp_set_current_user( $this->admin_id );
		$plaintext = $this->token_from_qr_url( $this->dispatch_generate()->get_data()['qr_url'] );
		wp_set_current_user( 0 );
		return $plaintext;
	}

	/**
	 * Generate a token AND complete the scan + approve handshake so the
	 * token is ready for /qr-login-exchange. Returns the plaintext token
	 * plus the `exchange_grant` nonce required by the exchange call.
	 *
	 * Use this in tests that exercise the exchange-side behaviour
	 * (consumed transient, AP creation, email dispatch, etc.) without
	 * caring about the number-matching details.
	 *
	 * @param array<string, string>|null $device Optional device payload to thread through scan.
	 * @return array{plaintext: string, session_id: string, exchange_grant: string}
	 */
	private function prepare_exchange_token( ?array $device = null ): array {
		$plaintext = $this->generate_token_as_admin();
		$flow      = $this->complete_pre_exchange_flow( $plaintext, $device );

		// Leave the test in unauthenticated state — the mobile app calls
		// /qr-login-exchange without a logged-in cookie.
		wp_set_current_user( 0 );

		return array(
			'plaintext'      => $plaintext,
			'session_id'     => $flow['session_id'],
			'exchange_grant' => $flow['exchange_grant'],
		);
	}

	/**
	 * @testdox Successful exchange persists a consumed-status transient keyed by the same hash as the token.
	 */
	public function test_exchange_token_persists_consumed_status(): void {
		$device = array(
			'os'          => 'Android',
			'os_version'  => '14',
			'model'       => 'Pixel 8 Pro',
			'brand'       => 'google',
			'app_version' => '24.7.0',
		);
		$prep   = $this->prepare_exchange_token( $device );

		$response = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );
		$this->assertSame( 200, $response->get_status() );

		$consumed = get_transient( MobileAppQRLogin::CONSUMED_TRANSIENT_PREFIX . hash( 'sha256', $prep['plaintext'] ) );
		$this->assertIsArray( $consumed, 'A consumed-status transient should be written on successful exchange.' );
		$this->assertArrayHasKey( 'consumed_at', $consumed );
		$this->assertArrayHasKey( 'user_id', $consumed );
		$this->assertArrayHasKey( 'ap_uuid', $consumed );
		$this->assertArrayHasKey( 'ap_name', $consumed );
		$this->assertArrayHasKey( 'device', $consumed );
		$this->assertSame( $this->admin_id, (int) $consumed['user_id'] );
		$this->assertSame( $response->get_data()['uuid'], $consumed['ap_uuid'] );
		$this->assertSame(
			array(
				'os'          => 'Android',
				'os_version'  => '14',
				'model'       => 'Pixel 8 Pro',
				'brand'       => 'google',
				'app_version' => '24.7.0',
			),
			$consumed['device']
		);
	}

	/**
	 * @testdox Exchange after scan with a device payload uses the model and date in the Application Password name.
	 */
	public function test_exchange_token_with_device_payload_sets_descriptive_ap_name(): void {
		$prep = $this->prepare_exchange_token(
			array(
				'os'          => 'iOS',
				'os_version'  => '17.5',
				'model'       => 'iPhone 15',
				'app_version' => '24.7.0',
			)
		);

		$response = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );
		$this->assertSame( 200, $response->get_status() );

		$aps = WP_Application_Passwords::get_user_application_passwords( $this->admin_id );
		$this->assertCount( 1, $aps );
		$this->assertMatchesRegularExpression(
			'#^Woo Mobile · iPhone 15 · \d{4}-\d{2}-\d{2}$#u',
			$aps[0]['name'],
			'AP name should be "Woo Mobile · {model} · {YYYY-MM-DD}".'
		);
	}

	/**
	 * @testdox Exchange whitelists device-payload keys, drops anything outside the whitelist, and caps each field at 64 characters.
	 */
	public function test_exchange_token_sanitizes_device_fields(): void {
		$long = str_repeat( 'A', 100 );

		// Each value asserts a different sanitization invariant: tags stripped
		// by sanitize_text_field, length capped at 64, and unknown keys
		// dropped (whitelist enforcement).
		$prep = $this->prepare_exchange_token(
			array(
				'os'          => 'iOS<script>',
				'os_version'  => '17.5',
				'model'       => $long,
				'app_version' => '24.7.0',
				'rogue_field' => 'should-be-dropped',
			)
		);

		$this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );

		$consumed = get_transient( MobileAppQRLogin::CONSUMED_TRANSIENT_PREFIX . hash( 'sha256', $prep['plaintext'] ) );
		$this->assertIsArray( $consumed );
		$this->assertSame( 'iOS', $consumed['device']['os'], 'sanitize_text_field should strip tags.' );
		$this->assertSame( str_repeat( 'A', 64 ), $consumed['device']['model'], 'Each field is capped at 64 chars.' );
		$this->assertArrayNotHasKey(
			'rogue_field',
			$consumed['device'],
			'Anything outside the whitelist must be dropped server-side.'
		);
	}

	// -----------------------------------------------------------------------
	// Status endpoint.
	// -----------------------------------------------------------------------

	/**
	 * @testdox Status endpoint returns pending when a token has been generated but not yet exchanged.
	 */
	public function test_get_status_returns_pending_when_token_active(): void {
		$plaintext = $this->generate_token_as_admin();

		wp_set_current_user( $this->admin_id );
		$response = $this->dispatch_status( $plaintext );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'pending', $data['status'] );
		$this->assertArrayHasKey( 'expires_at', $data );
		$this->assertIsInt( $data['expires_at'] );
	}

	/**
	 * @testdox Status endpoint returns consumed with the device payload after a successful exchange.
	 */
	public function test_get_status_returns_consumed_with_device_info(): void {
		$prep = $this->prepare_exchange_token(
			array(
				'os'          => 'Android',
				'os_version'  => '14',
				'model'       => 'Pixel 8',
				'app_version' => '24.7.0',
			)
		);

		$exchange = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );
		$this->assertSame( 200, $exchange->get_status() );

		wp_set_current_user( $this->admin_id );
		$response = $this->dispatch_status( $prep['plaintext'] );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'consumed', $data['status'] );
		$this->assertSame( $exchange->get_data()['uuid'], $data['ap_uuid'] );
		$this->assertSame( 'Pixel 8', $data['device']['model'] );
		$this->assertSame( 'Android', $data['device']['os'] );
		$this->assertIsInt( $data['consumed_at'] );
		$this->assertNotEmpty( $data['ap_name'] );
	}

	/**
	 * @testdox Status endpoint returns expired when neither token nor consumed transient exists.
	 */
	public function test_get_status_returns_expired_when_neither_transient_exists(): void {
		wp_set_current_user( $this->admin_id );

		$response = $this->dispatch_status( 'never-minted-this-token' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'expired', $response->get_data()['status'] );
	}

	/**
	 * @testdox Status endpoint returns expired immediately when the token is empty.
	 */
	public function test_get_status_returns_expired_when_token_empty(): void {
		wp_set_current_user( $this->admin_id );

		$response = $this->dispatch_status( '' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'expired', $response->get_data()['status'] );
	}

	/**
	 * @testdox Status endpoint hides another user's token state and reports it as expired (defense in depth).
	 */
	public function test_get_status_rejects_other_users(): void {
		// Admin mints + exchanges the token.
		$prep = $this->prepare_exchange_token(
			array(
				'os'    => 'iOS',
				'model' => 'iPhone 15',
			)
		);
		$this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );

		// A different shop manager polls the same token. Token guess is
		// astronomical, but cross-user reads must still be opaque.
		wp_set_current_user( $this->shop_manager_id );
		$response = $this->dispatch_status( $prep['plaintext'] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'expired', $response->get_data()['status'] );
	}

	/**
	 * @testdox Status endpoint rejects subscribers who lack the manage_woocommerce capability.
	 */
	public function test_get_status_rejects_subscriber(): void {
		wp_set_current_user( $this->subscriber_id );

		$response = $this->dispatch_status( 'whatever' );

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
	}

	// -----------------------------------------------------------------------
	// Revoke endpoint.
	// -----------------------------------------------------------------------

	/**
	 * @testdox Revoke endpoint deletes the Application Password issued by a successful exchange.
	 */
	public function test_revoke_password_happy_path(): void {
		$prep     = $this->prepare_exchange_token(
			array(
				'os'    => 'iOS',
				'model' => 'iPhone 15',
			)
		);
		$exchange = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );
		$this->assertSame( 200, $exchange->get_status() );
		$uuid = $exchange->get_data()['uuid'];

		$this->assertCount( 1, WP_Application_Passwords::get_user_application_passwords( $this->admin_id ) );

		wp_set_current_user( $this->admin_id );
		$response = $this->dispatch_revoke( $uuid );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
		$this->assertSame( $uuid, $response->get_data()['uuid'] );
		$this->assertCount(
			0,
			WP_Application_Passwords::get_user_application_passwords( $this->admin_id ),
			'The Application Password must be gone after a successful revoke.'
		);
	}

	/**
	 * @testdox Revoke endpoint returns 404 when the UUID belongs to a different user.
	 */
	public function test_revoke_password_rejects_when_uuid_belongs_to_another_user(): void {
		// Admin mints + exchanges. AP belongs to admin.
		$prep     = $this->prepare_exchange_token(
			array(
				'os'    => 'iOS',
				'model' => 'iPhone 15',
			)
		);
		$exchange = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );
		$uuid     = $exchange->get_data()['uuid'];

		// Shop manager tries to revoke admin's AP. This must fail with 404 —
		// we don't even leak that the AP exists.
		wp_set_current_user( $this->shop_manager_id );
		$response = $this->dispatch_revoke( $uuid );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'application_password_not_found', $response->get_data()['code'] );

		// And the admin's AP is untouched.
		$this->assertCount( 1, WP_Application_Passwords::get_user_application_passwords( $this->admin_id ) );
	}

	/**
	 * @testdox Revoke endpoint rejects subscribers who lack the manage_woocommerce capability.
	 */
	public function test_revoke_password_rejects_subscriber(): void {
		wp_set_current_user( $this->subscriber_id );

		$response = $this->dispatch_revoke( 'whatever-uuid' );

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
	}

	// -----------------------------------------------------------------------
	// Sign-in notification email (Task 6).
	// -----------------------------------------------------------------------

	/**
	 * Capture wp_mail() calls into a local array via the `pre_wp_mail` filter.
	 *
	 * The filter must return a non-null value to short-circuit the actual
	 * mail send. The captured atts include `to`,
	 * `subject`, `message`, and `headers`. Caller must remove the filter via
	 * the returned remover closure.
	 *
	 * @param bool $send_result Value the filter should return from wp_mail().
	 * @return array{captures: array<int, array<string, mixed>>, remove: callable}
	 */
	private function capture_wp_mail( bool $send_result = true ): array {
		$captures = array();

		$capture = static function ( $short_circuit, $atts ) use ( &$captures, $send_result ) {
			unset( $short_circuit );
			$captures[] = is_array( $atts ) ? $atts : array();
			return $send_result;
		};

		add_filter( 'pre_wp_mail', $capture, 10, 2 );

		$remove = static function () use ( $capture ) {
			remove_filter( 'pre_wp_mail', $capture, 10 );
		};

		return array(
			'captures' => &$captures,
			'remove'   => $remove,
		);
	}

	/**
	 * @testdox Successful exchange dispatches a sign-in notification email to the user that minted the token.
	 */
	public function test_exchange_token_dispatches_sign_in_notification_email(): void {
		$capture = $this->capture_wp_mail();

		try {
			$prep = $this->prepare_exchange_token(
				array(
					'os'          => 'iOS',
					'os_version'  => '17.5',
					'model'       => 'iPhone 15',
					'app_version' => '24.7.0',
				)
			);

			$response = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );
			$this->assertSame( 200, $response->get_status() );

			$this->assertCount( 1, $capture['captures'], 'Exactly one email should be sent on a successful exchange.' );
			$mail = $capture['captures'][0];

			$admin_user = get_userdata( $this->admin_id );
			$this->assertSame( $admin_user->user_email, $mail['to'] );

			$this->assertStringContainsString(
				get_bloginfo( 'name' ),
				(string) $mail['subject'],
				'Subject should reference the site name so a merchant managing multiple stores can disambiguate.'
			);

			$body = (string) $mail['message'];
			$this->assertStringContainsString( 'iPhone 15', $body, 'Email body should surface the device model.' );
			$this->assertStringContainsString( 'iOS 17.5', $body, 'Email body should surface the OS version.' );
			$this->assertStringContainsString( '24.7.0', $body, 'Email body should surface the app version.' );
			$this->assertStringContainsString( 'application-passwords', $body, 'Email body should link to the AP management screen.' );
		} finally {
			$capture['remove']();
		}//end try
	}

	/**
	 * @testdox The sign-in notification email can be suppressed via the woocommerce_qr_login_should_send_signin_email filter.
	 */
	public function test_sign_in_notification_email_can_be_suppressed_via_filter(): void {
		$capture  = $this->capture_wp_mail();
		$suppress = static fn () => false;
		add_filter( 'woocommerce_qr_login_should_send_signin_email', $suppress );

		try {
			$prep     = $this->prepare_exchange_token(
				array(
					'os'    => 'iOS',
					'model' => 'iPhone 15',
				)
			);
			$response = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );
			$this->assertSame( 200, $response->get_status() );

			$this->assertCount(
				0,
				$capture['captures'],
				'Filter returning false must suppress the email send entirely.'
			);
		} finally {
			remove_filter( 'woocommerce_qr_login_should_send_signin_email', $suppress );
			$capture['remove']();
		}//end try
	}

	/**
	 * @testdox A wp_mail false return is logged without failing a successful exchange.
	 */
	public function test_sign_in_notification_email_false_return_is_logged_without_blocking_exchange(): void {
		$capture = $this->capture_wp_mail( false );
		$logger  = $this->getMockBuilder( \WC_Logger_Interface::class )->getMock();
		$logger->expects( $this->once() )
			->method( 'warning' )
			->with(
				$this->stringContains( 'QR sign-in notification email failed' ),
				$this->callback(
					static function ( $context ) {
						return is_array( $context )
							&& isset( $context['source'] )
							&& 'mobile-app-qr-login' === $context['source'];
					}
				)
			);

		$logger_filter = static fn () => $logger;
		add_filter( 'woocommerce_logging_class', $logger_filter );

		try {
			$prep     = $this->prepare_exchange_token(
				array(
					'os'    => 'Android',
					'model' => 'Pixel 10',
				)
			);
			$response = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );

			$this->assertSame( 200, $response->get_status() );
			$this->assertCount( 1, $capture['captures'], 'A failed mailer return should still prove the send was attempted.' );
		} finally {
			remove_filter( 'woocommerce_logging_class', $logger_filter );
			$capture['remove']();
		}//end try
	}

	// ---------------------------------------------------------------------
	// Task 7 — number-matching state machine.
	// ---------------------------------------------------------------------

	/**
	 * @testdox Scan endpoint transitions a pending token to scanned and returns a session_id + real_number.
	 */
	public function test_scan_transitions_pending_to_scanned(): void {
		$plaintext = $this->generate_token_as_admin();
		wp_set_current_user( 0 );

		$response = $this->dispatch_scan(
			$plaintext,
			array(
				'os'          => 'Android',
				'model'       => 'Pixel 10',
				'app_version' => '24.7.0',
			)
		);

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertArrayHasKey( 'session_id', $data );
		$this->assertNotEmpty( $data['session_id'] );
		$this->assertArrayHasKey( 'real_number', $data );
		$this->assertMatchesRegularExpression( '/^\d{3}$/', (string) $data['real_number'], 'Real number must be 3 digits, zero-padded.' );
		$this->assertArrayHasKey( 'expires_in', $data );
		$this->assertSame( MobileAppQRLogin::CHALLENGE_TTL_SECONDS, $data['expires_in'] );

		// Underlying transient is now in the scanned state with the device payload threaded through.
		$record = get_transient( MobileAppQRLogin::TOKEN_TRANSIENT_PREFIX . hash( 'sha256', $plaintext ) );
		$this->assertIsArray( $record );
		$this->assertSame( MobileAppQRLogin::STATE_SCANNED, $record['state'] );
		$this->assertSame( 'Pixel 10', $record['challenge']['device']['model'] ?? null );
		$this->assertNotNull(
			$this->get_qr_login_rate_limit_row( QRLoginRateLimits::BUCKET_SCAN, '203.0.113.10' )
		);
		$this->assertFalse( get_option( $this->token_scan_claim_key( $plaintext ), false ) );
	}

	/**
	 * @testdox Scan endpoint caps the challenge window to the original token lifetime.
	 */
	public function test_scan_caps_challenge_window_to_remaining_token_lifetime(): void {
		$plaintext     = $this->generate_token_as_admin();
		$transient_key = $this->token_transient_key( $plaintext );
		$record        = get_transient( $transient_key );
		$expires_at    = time() + 20;

		$this->assertIsArray( $record );
		$record['expires_at'] = $expires_at;
		set_transient( $transient_key, $record, 20 );

		wp_set_current_user( 0 );
		$response = $this->dispatch_scan( $plaintext );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertGreaterThan( 0, $data['expires_in'] );
		$this->assertLessThanOrEqual( 20, $data['expires_in'] );

		$record = get_transient( $transient_key );
		$this->assertIsArray( $record );
		$this->assertSame( $expires_at, $record['challenge']['expires_at'] );
	}

	/**
	 * @testdox Scan endpoint returns 426 Upgrade Required when the mobile app omits the supports_number_matching capability flag.
	 */
	public function test_scan_rejects_when_capability_flag_missing(): void {
		$plaintext = $this->generate_token_as_admin();
		wp_set_current_user( 0 );

		// Simulate a pre-Task-7 mobile app that doesn't send the flag.
		$response = $this->dispatch_scan( $plaintext, null, false );

		$this->assertSame( 426, $response->get_status() );
		$this->assertSame( 'mobile_app_update_required', $response->get_data()['code'] );
		$this->assertNull(
			$this->get_qr_login_rate_limit_row( QRLoginRateLimits::BUCKET_SCAN, '203.0.113.10' )
		);

		// Token state must remain pending — a legacy scan must not mutate the record.
		$record = get_transient( MobileAppQRLogin::TOKEN_TRANSIENT_PREFIX . hash( 'sha256', $plaintext ) );
		$this->assertSame( MobileAppQRLogin::STATE_PENDING, $record['state'] );
	}

	/**
	 * @testdox Scan endpoint rejects device payloads that do not include a model or OS identity.
	 */
	public function test_scan_requires_device_identity(): void {
		$plaintext = $this->generate_token_as_admin();
		wp_set_current_user( 0 );

		$response = $this->dispatch_scan(
			$plaintext,
			array( 'app_version' => '24.7.0' )
		);

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'invalid_device', $response->get_data()['code'] );

		$record = get_transient( $this->token_transient_key( $plaintext ) );
		$this->assertIsArray( $record );
		$this->assertSame( MobileAppQRLogin::STATE_PENDING, $record['state'] );
		$this->assertFalse( get_option( $this->token_scan_claim_key( $plaintext ), false ) );
	}

	/**
	 * @testdox Scan endpoint rate-limits invalid random tokens without consuming the valid scan bucket.
	 */
	public function test_scan_invalid_token_does_not_consume_scan_rate_limit(): void {
		wp_set_current_user( 0 );

		$response = $this->dispatch_scan( 'not-a-real-token' );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'invalid_token', $response->get_data()['code'] );
		$this->assertNull(
			$this->get_qr_login_rate_limit_row( QRLoginRateLimits::BUCKET_SCAN, '203.0.113.10' )
		);
		$row = $this->get_qr_login_rate_limit_row( QRLoginRateLimits::BUCKET_INVALID_SCAN, '203.0.113.10' );
		$this->assertNotNull( $row );
		$this->assertSame( MobileAppQRLogin::MAX_INVALID_SCAN_ATTEMPTS - 1, (int) $row->rate_limit_remaining );
	}

	/**
	 * @testdox Scan endpoint returns 409 when called on a token that was already scanned.
	 */
	public function test_scan_rejects_already_scanned_token(): void {
		$plaintext = $this->generate_token_as_admin();
		wp_set_current_user( 0 );
		$this->assertSame( 200, $this->dispatch_scan( $plaintext )->get_status() );

		// A second scan must fail — once scanned, only /approve can move the state.
		$response = $this->dispatch_scan( $plaintext );
		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'qr_login_already_scanned', $response->get_data()['code'] );
	}

	/**
	 * @testdox Approve endpoint mints an exchange_grant when the merchant taps the correct number.
	 */
	public function test_approve_correct_choice_marks_approved_and_emits_grant(): void {
		$plaintext = $this->generate_token_as_admin();

		wp_set_current_user( 0 );
		$scan_data = $this->dispatch_scan( $plaintext )->get_data();

		wp_set_current_user( $this->admin_id );
		$response = $this->dispatch_approve( $plaintext, $scan_data['real_number'] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( MobileAppQRLogin::STATE_APPROVED, $response->get_data()['state'] );

		$record = get_transient( MobileAppQRLogin::TOKEN_TRANSIENT_PREFIX . hash( 'sha256', $plaintext ) );
		$this->assertSame( MobileAppQRLogin::STATE_APPROVED, $record['state'] );
		$this->assertNotEmpty( $record['exchange_grant'] );
		$this->assertSame( MobileAppQRLogin::EXCHANGE_GRANT_BYTES * 2, strlen( (string) $record['exchange_grant'] ), 'Grant should be hex-encoded random_bytes — 2 chars per byte.' );
	}

	/**
	 * @testdox Approve returns 410 with no grant when the token expires between scan and tap.
	 */
	public function test_approve_returns_410_when_token_expired_after_scan(): void {
		$plaintext     = $this->generate_token_as_admin();
		$transient_key = $this->token_transient_key( $plaintext );

		wp_set_current_user( 0 );
		$scan_data = $this->dispatch_scan( $plaintext )->get_data();

		// Simulate the underlying token lapsing after the scan but before the
		// merchant taps: push expires_at into the past while keeping the
		// transient itself readable so approve re-reads the lapsed record.
		$record = get_transient( $transient_key );
		$this->assertIsArray( $record );
		$record['expires_at'] = time() - 1;
		set_transient( $transient_key, $record, 60 );

		wp_set_current_user( $this->admin_id );
		$response = $this->dispatch_approve( $plaintext, (string) $scan_data['real_number'] );

		$this->assertSame( 410, $response->get_status() );
		$this->assertSame( 'qr_login_expired', $response->get_data()['code'] );

		// Terminal expired state, and — even though the tapped number was
		// correct — no exchange_grant may ever be minted.
		$record = get_transient( $transient_key );
		$this->assertIsArray( $record );
		$this->assertSame( MobileAppQRLogin::STATE_EXPIRED, $record['state'] );
		$this->assertArrayNotHasKey( 'exchange_grant', $record, 'An expired challenge must never mint an exchange grant.' );
	}

	/**
	 * @testdox Approve endpoint terminates the session with rejected when the merchant taps a wrong number.
	 */
	public function test_approve_wrong_choice_marks_rejected(): void {
		$plaintext = $this->generate_token_as_admin();

		wp_set_current_user( 0 );
		$scan_data = $this->dispatch_scan( $plaintext )->get_data();

		// Pick something that definitely isn't the real number — distractors
		// are guaranteed to differ from the real one by ≥ 100, so subtracting
		// 100 from the real (mod 1000) gives us a guaranteed-wrong value.
		$wrong = str_pad( (string) ( ( ( (int) $scan_data['real_number'] ) + 500 ) % 1000 ), 3, '0', STR_PAD_LEFT );

		wp_set_current_user( $this->admin_id );
		$response = $this->dispatch_approve( $plaintext, $wrong );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( MobileAppQRLogin::STATE_REJECTED, $response->get_data()['state'] );

		// Token transient is now in terminal rejected state — no exchange_grant ever issued.
		$record = get_transient( MobileAppQRLogin::TOKEN_TRANSIENT_PREFIX . hash( 'sha256', $plaintext ) );
		$this->assertSame( MobileAppQRLogin::STATE_REJECTED, $record['state'] );
		$this->assertArrayNotHasKey( 'exchange_grant', $record, 'Wrong-pick must never mint a grant — that is the entire point of the security guarantee.' );
	}

	/**
	 * @testdox Approve endpoint rejects another user trying to approve someone else's scanned token.
	 */
	public function test_approve_rejects_other_user(): void {
		$plaintext = $this->generate_token_as_admin();

		wp_set_current_user( 0 );
		$scan_data = $this->dispatch_scan( $plaintext )->get_data();

		// A second administrator who is also `manage_woocommerce`-capable
		// must still be rejected — this is not "permission denied", it's
		// "this token doesn't belong to you, full stop".
		$other_admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		try {
			wp_set_current_user( $other_admin_id );
			$response = $this->dispatch_approve( $plaintext, $scan_data['real_number'] );

			$this->assertSame( 401, $response->get_status() );
			$this->assertSame( 'invalid_token', $response->get_data()['code'] );

			// Even with the right number, a stranger must not move the token to approved.
			$record = get_transient( MobileAppQRLogin::TOKEN_TRANSIENT_PREFIX . hash( 'sha256', $plaintext ) );
			$this->assertSame( MobileAppQRLogin::STATE_SCANNED, $record['state'] );
		} finally {
			wp_delete_user( $other_admin_id );
		}
	}

	/**
	 * @testdox Exchange endpoint returns 412 when the QR session has not been approved (no number-match step completed).
	 */
	public function test_exchange_requires_approved_state(): void {
		$plaintext = $this->generate_token_as_admin();

		wp_set_current_user( 0 );
		$this->dispatch_scan( $plaintext );
		// Note: deliberately skipping /approve — token is still in scanned, not approved.

		$response = $this->dispatch_exchange( $plaintext, 'any-grant-here-doesnt-matter' );

		$this->assertSame( 412, $response->get_status() );
		$this->assertSame( 'qr_login_not_approved', $response->get_data()['code'] );
		$this->assertCount( 0, WP_Application_Passwords::get_user_application_passwords( $this->admin_id ), 'No AP must be issued without approval.' );

		$record = get_transient( $this->token_transient_key( $plaintext ) );
		$this->assertIsArray( $record );
		$this->assertSame( MobileAppQRLogin::STATE_SCANNED, $record['state'] );
	}

	/**
	 * @testdox Exchange endpoint returns 412 when the merchant skipped /qr-login-scan entirely (token still in pending state).
	 */
	public function test_exchange_rejects_token_that_skipped_scan(): void {
		$plaintext = $this->generate_token_as_admin();

		// Skip /scan and /approve entirely — token stays in pending. The
		// state-machine guard collapses both this and the "scanned but not
		// approved" case into a single 412 qr_login_not_approved response.
		wp_set_current_user( 0 );
		$response = $this->dispatch_exchange( $plaintext, 'irrelevant-grant' );

		$this->assertSame( 412, $response->get_status() );
		$this->assertSame( 'qr_login_not_approved', $response->get_data()['code'] );
		$this->assertCount( 0, WP_Application_Passwords::get_user_application_passwords( $this->admin_id ), 'Skipping scan must not be a path to mint an AP.' );

		$record = get_transient( $this->token_transient_key( $plaintext ) );
		$this->assertIsArray( $record );
		$this->assertSame( MobileAppQRLogin::STATE_PENDING, $record['state'] );
	}

	/**
	 * @testdox Exchange endpoint returns 412 when the exchange_grant nonce does not match the one minted at approve time.
	 */
	public function test_exchange_requires_valid_grant_nonce(): void {
		$prep = $this->prepare_exchange_token();

		$response = $this->dispatch_exchange( $prep['plaintext'], str_repeat( 'a', strlen( $prep['exchange_grant'] ) ) );

		$this->assertSame( 412, $response->get_status() );
		$this->assertSame( 'invalid_exchange_grant', $response->get_data()['code'] );
		$this->assertCount( 0, WP_Application_Passwords::get_user_application_passwords( $this->admin_id ), 'Grant mismatch must not produce an AP.' );

		$record = get_transient( $this->token_transient_key( $prep['plaintext'] ) );
		$this->assertIsArray( $record );
		$this->assertSame( MobileAppQRLogin::STATE_APPROVED, $record['state'] );
		$this->assertSame( 1, $record['invalid_grant_attempts'] );

		$retry = $this->dispatch_exchange( $prep['plaintext'], $prep['exchange_grant'] );
		$this->assertSame( 200, $retry->get_status() );
		$this->assertCount( 1, WP_Application_Passwords::get_user_application_passwords( $this->admin_id ) );
	}

	/**
	 * @testdox Invalid exchange grants do not extend approved records beyond the original token lifetime.
	 */
	public function test_invalid_exchange_grant_does_not_extend_approved_record_past_token_lifetime(): void {
		$prep          = $this->prepare_exchange_token();
		$transient_key = $this->token_transient_key( $prep['plaintext'] );
		$record        = get_transient( $transient_key );
		$expires_at    = time() + 20;

		$this->assertIsArray( $record );
		$record['expires_at'] = $expires_at;
		set_transient( $transient_key, $record, 20 );

		$response = $this->dispatch_exchange( $prep['plaintext'], str_repeat( 'b', strlen( $prep['exchange_grant'] ) ) );

		$this->assertSame( 412, $response->get_status() );
		$this->assertSame( 'invalid_exchange_grant', $response->get_data()['code'] );

		$timeout = get_option( '_transient_timeout_' . $transient_key );
		$this->assertNotFalse( $timeout, 'Token transient should keep a timeout after the failed exchange.' );
		$this->assertLessThanOrEqual(
			$expires_at,
			(int) $timeout,
			'Failed exchange must not extend the approved record beyond token expiry.'
		);
	}

	/**
	 * @testdox Repeated invalid exchange grants terminally reject the token after the threshold.
	 */
	public function test_exchange_rejects_after_invalid_grant_threshold(): void {
		$prep = $this->prepare_exchange_token();

		for ( $i = 1; $i <= MobileAppQRLogin::MAX_INVALID_GRANT_ATTEMPTS; $i++ ) {
			$response = $this->dispatch_exchange(
				$prep['plaintext'],
				str_repeat( (string) $i, strlen( $prep['exchange_grant'] ) )
			);
			$this->assertSame( 412, $response->get_status() );
			$this->assertSame( 'invalid_exchange_grant', $response->get_data()['code'] );
		}

		$record = get_transient( $this->token_transient_key( $prep['plaintext'] ) );
		$this->assertIsArray( $record );
		$this->assertSame( MobileAppQRLogin::STATE_REJECTED, $record['state'] );
		$this->assertSame( MobileAppQRLogin::MAX_INVALID_GRANT_ATTEMPTS, $record['invalid_grant_attempts'] );
		$this->assertCount( 0, WP_Application_Passwords::get_user_application_passwords( $this->admin_id ) );

		$status = $this->dispatch_session_status( $prep['session_id'], $prep['plaintext'] );
		$this->assertSame( 200, $status->get_status() );
		$this->assertSame( MobileAppQRLogin::STATE_REJECTED, $status->get_data()['state'] );
	}

	/**
	 * @testdox Status endpoint returns a 3-element shuffled candidate triple (real + 2 distractors) while in scanned state and never reveals which one is real.
	 */
	public function test_status_endpoint_returns_shuffled_triple_in_scanned(): void {
		$plaintext = $this->generate_token_as_admin();

		wp_set_current_user( 0 );
		$scan_data   = $this->dispatch_scan( $plaintext )->get_data();
		$real_number = (string) $scan_data['real_number'];

		wp_set_current_user( $this->admin_id );
		$response = $this->dispatch_status( $plaintext );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertSame( MobileAppQRLogin::STATE_SCANNED, $data['status'] );
		$this->assertCount( 3, $data['numbers'] );
		foreach ( $data['numbers'] as $candidate ) {
			$this->assertMatchesRegularExpression( '/^\d{3}$/', (string) $candidate );
		}
		$this->assertContains( $real_number, $data['numbers'], 'The shuffled triple must include the real number.' );

		// Status response must NOT expose any field that flags which one is real.
		$flat = wp_json_encode( $data );
		$this->assertStringNotContainsString( '"real"', (string) $flat, 'Status payload must not contain a `real` field — that would defeat the entire shoulder-surf protection.' );
	}

	/**
	 * @testdox Concurrent /qr-login-scan requests on the same token produce exactly one winner thanks to the atomic claim — the loser is rejected with `qr_login_already_scanned`, never silently overwriting the canonical record.
	 */
	public function test_scan_atomic_claim_rejects_concurrent_second_scan(): void {
		$plaintext  = $this->generate_token_as_admin();
		$token_hash = hash( 'sha256', $plaintext );

		// Pre-register the scan claim option to simulate a concurrent worker
		// that has already grabbed the mutex but not yet completed its
		// state-mutation. add_option() is atomic; the second worker (this
		// test) must observe the existing key and bail out.
		$claim_key        = MobileAppQRLogin::SCAN_CLAIM_OPTION_PREFIX . $token_hash;
		$claim_expires_at = (string) ( time() + 60 );
		add_option( $claim_key, $claim_expires_at, '', false );

		wp_set_current_user( 0 );
		$response = $this->dispatch_scan( $plaintext );

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'qr_login_already_scanned', $response->get_data()['code'] );

		// The token record must remain in pending — the rejected scan should
		// not have mutated it. Without the claim, this assertion would fail
		// because the second worker would have written its own challenge.
		$record = get_transient( $this->token_transient_key( $plaintext ) );
		$this->assertIsArray( $record );
		$this->assertSame( MobileAppQRLogin::STATE_PENDING, $record['state'] );
		$this->assertSame(
			$claim_expires_at,
			get_option( $claim_key ),
			'A rejected second scan must not release another request\'s claim.'
		);

		delete_option( $claim_key );
	}

	/**
	 * @testdox Stale claim cleanup does not delete a fresh claim written after the stale value was observed.
	 */
	public function test_stale_claim_cleanup_does_not_delete_fresh_replacement_claim(): void {
		$claim_key         = $this->token_claim_key( 'race-token' );
		$stale_claim_value = (string) ( time() - 60 );
		$fresh_claim_value = (string) ( time() + 60 );

		$this->assertTrue( add_option( $claim_key, $stale_claim_value, '', false ) );
		$this->assertTrue( update_option( $claim_key, $fresh_claim_value, false ) );

		$controller = new MobileAppQRLogin();
		$reflection = new \ReflectionClass( $controller );
		$method     = $reflection->getMethod( 'delete_claim_if_value_matches' );
		$method->setAccessible( true );

		$this->assertFalse( $method->invoke( $controller, $claim_key, $stale_claim_value ) );
		$this->assertSame( $fresh_claim_value, get_option( $claim_key ) );

		delete_option( $claim_key );
	}

	/**
	 * @testdox Concurrent /qr-login-approve requests on the same scanned token produce exactly one winner thanks to the atomic claim.
	 */
	public function test_approve_atomic_claim_rejects_concurrent_second_approval(): void {
		$plaintext = $this->generate_token_as_admin();

		wp_set_current_user( 0 );
		$scan_data = $this->dispatch_scan( $plaintext )->get_data();

		wp_set_current_user( $this->admin_id );

		// Pre-register the approval claim option to simulate a concurrent
		// worker that has already grabbed the mutex but not yet completed the
		// scanned -> approved/rejected transition. The second worker must
		// observe the existing key and bail out without mutating the record.
		$claim_key        = $this->token_approve_claim_key( $plaintext );
		$claim_expires_at = (string) ( time() + 60 );
		add_option( $claim_key, $claim_expires_at, '', false );

		$response = $this->dispatch_approve( $plaintext, (string) $scan_data['real_number'] );

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'qr_login_approval_in_progress', $response->get_data()['code'] );

		$record = get_transient( $this->token_transient_key( $plaintext ) );
		$this->assertIsArray( $record );
		$this->assertSame( MobileAppQRLogin::STATE_SCANNED, $record['state'] );
		$this->assertArrayNotHasKey( 'exchange_grant', $record );
		$this->assertSame(
			$claim_expires_at,
			get_option( $claim_key ),
			'A rejected concurrent approval must not release another request\'s claim.'
		);

		delete_option( $claim_key );
	}

	/**
	 * @testdox QR login endpoints introduced after the base PR use wc_rate_limits buckets instead of transient rate rows.
	 */
	public function test_later_qr_endpoint_rate_limits_use_wc_rate_limits(): void {
		$plaintext = $this->generate_token_as_admin();

		wp_set_current_user( $this->admin_id );
		$this->assertSame( 200, $this->dispatch_status( $plaintext )->get_status() );
		$this->assertNotNull(
			$this->get_qr_login_rate_limit_row( QRLoginRateLimits::BUCKET_STATUS, (string) $this->admin_id )
		);

		$this->assertSame( 404, $this->dispatch_revoke( 'missing-uuid' )->get_status() );
		$this->assertNotNull(
			$this->get_qr_login_rate_limit_row( QRLoginRateLimits::BUCKET_REVOKE, (string) $this->admin_id )
		);

		wp_set_current_user( 0 );
		$scan_response = $this->dispatch_scan( $plaintext );
		$this->assertSame( 200, $scan_response->get_status() );
		$this->assertNotNull(
			$this->get_qr_login_rate_limit_row( QRLoginRateLimits::BUCKET_SCAN, '203.0.113.10' )
		);

		wp_set_current_user( $this->admin_id );
		$this->assertSame(
			200,
			$this->dispatch_approve( $plaintext, $scan_response->get_data()['real_number'] )->get_status()
		);
		$this->assertNotNull(
			$this->get_qr_login_rate_limit_row( QRLoginRateLimits::BUCKET_APPROVE, (string) $this->admin_id )
		);

		wp_set_current_user( 0 );
		$this->assertSame(
			200,
			$this->dispatch_session_status( $scan_response->get_data()['session_id'], $plaintext )->get_status()
		);
		$this->assertNotNull(
			$this->get_qr_login_rate_limit_row(
				QRLoginRateLimits::BUCKET_SESSION_STATUS,
				$scan_response->get_data()['session_id']
			)
		);

		$this->assertSame( 0, $this->get_qr_login_rate_limit_transient_count() );
	}

	/**
	 * @testdox Session-status endpoint refuses to return any state without proof of token knowledge — a session_id alone yields opaque `expired`.
	 */
	public function test_session_status_requires_token_hash_proof(): void {
		$plaintext = $this->generate_token_as_admin();

		wp_set_current_user( 0 );
		$scan_data = $this->dispatch_scan( $plaintext )->get_data();

		// Approve the match server-side so a successful poll *would* otherwise
		// return the grant. The point of this test is that without the
		// token_hash, even an approved session can't be polled.
		wp_set_current_user( $this->admin_id );
		$this->dispatch_approve( $plaintext, $scan_data['real_number'] );
		wp_set_current_user( 0 );

		// No token_hash → opaque expired (we never confirm the session_id is real).
		$response = $this->dispatch_session_status( $scan_data['session_id'] );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			MobileAppQRLogin::STATE_EXPIRED,
			$response->get_data()['state']
		);
		$this->assertArrayNotHasKey( 'exchange_grant', $response->get_data() );

		// Wrong token_hash → same opaque expired.
		$response = $this->dispatch_session_status( $scan_data['session_id'], 'a-different-token' );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( MobileAppQRLogin::STATE_EXPIRED, $response->get_data()['state'] );
		$this->assertArrayNotHasKey( 'exchange_grant', $response->get_data() );
		$this->assertNull(
			$this->get_qr_login_rate_limit_row( QRLoginRateLimits::BUCKET_SESSION_STATUS, $scan_data['session_id'] )
		);

		// Correct token_hash → grant delivered.
		$response = $this->dispatch_session_status( $scan_data['session_id'], $plaintext );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( MobileAppQRLogin::STATE_APPROVED, $response->get_data()['state'] );
		$this->assertNotEmpty( $response->get_data()['exchange_grant'] );
		$this->assertNotNull(
			$this->get_qr_login_rate_limit_row( QRLoginRateLimits::BUCKET_SESSION_STATUS, $scan_data['session_id'] )
		);
	}

	/**
	 * @testdox Session-status endpoint requires HTTPS before it can return an exchange grant.
	 */
	public function test_session_status_requires_https(): void {
		$plaintext = $this->generate_token_as_admin();

		wp_set_current_user( 0 );
		$scan_data = $this->dispatch_scan( $plaintext )->get_data();

		wp_set_current_user( $this->admin_id );
		$this->dispatch_approve( $plaintext, $scan_data['real_number'] );

		wp_set_current_user( 0 );
		$this->force_https( false );
		$response = $this->dispatch_session_status( $scan_data['session_id'], $plaintext );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'ssl_required', $response->get_data()['code'] );
		$this->assertArrayNotHasKey( 'exchange_grant', $response->get_data() );
	}

	/**
	 * @testdox Session-status endpoint does not create rate-limit rows for random missing sessions.
	 */
	public function test_session_status_missing_session_does_not_consume_rate_limit(): void {
		wp_set_current_user( 0 );

		$response = $this->dispatch_session_status( 'missing-session-id', 'not-a-real-token' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( MobileAppQRLogin::STATE_EXPIRED, $response->get_data()['state'] );
		$this->assertNull(
			$this->get_qr_login_rate_limit_row( QRLoginRateLimits::BUCKET_SESSION_STATUS, 'missing-session-id' )
		);
	}

	/**
	 * @testdox Session-status endpoint returns the exchange_grant once the merchant has approved the number-match.
	 */
	public function test_session_status_returns_grant_when_approved(): void {
		$plaintext = $this->generate_token_as_admin();

		wp_set_current_user( 0 );
		$scan_data = $this->dispatch_scan( $plaintext )->get_data();

		// Before approval — session-status returns scanned, no grant.
		$pre = $this->dispatch_session_status( $scan_data['session_id'], $plaintext );
		$this->assertSame( 200, $pre->get_status() );
		$this->assertSame( MobileAppQRLogin::STATE_SCANNED, $pre->get_data()['state'] );
		$this->assertArrayNotHasKey( 'exchange_grant', $pre->get_data() );

		// Approve.
		wp_set_current_user( $this->admin_id );
		$this->dispatch_approve( $plaintext, $scan_data['real_number'] );

		// After approval — session-status returns approved + exchange_grant.
		wp_set_current_user( 0 );
		$post = $this->dispatch_session_status( $scan_data['session_id'], $plaintext );
		$this->assertSame( 200, $post->get_status() );
		$this->assertSame( MobileAppQRLogin::STATE_APPROVED, $post->get_data()['state'] );
		$this->assertNotEmpty( $post->get_data()['exchange_grant'] );
	}

	// -----------------------------------------------------------------------
	// Availability endpoint (`/qr-login-availability`).
	// -----------------------------------------------------------------------

	/**
	 * Issue a GET to the availability endpoint.
	 *
	 * @return \WP_REST_Response
	 */
	private function dispatch_availability(): \WP_REST_Response {
		$request = new WP_REST_Request( 'GET', self::AVAILABILITY_ENDPOINT );
		return $this->server->dispatch( $request );
	}

	/**
	 * @testdox Availability endpoint reports `available: true` when HTTPS + application passwords are both ready.
	 */
	public function test_availability_reports_available_when_https_and_application_passwords_are_ready(): void {
		wp_set_current_user( $this->admin_id );

		$response = $this->dispatch_availability();

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['available'] );
		$this->assertNull( $data['reason'] );
	}

	/**
	 * @testdox Availability endpoint reports `https_required` when the site is served over plain HTTP.
	 */
	public function test_availability_reports_https_required_when_site_is_not_secure(): void {
		wp_set_current_user( $this->admin_id );
		$this->force_https( false );
		$this->force_site_url( 'http://example.org' );

		$response = $this->dispatch_availability();

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertFalse( $data['available'] );
		$this->assertSame(
			MobileAppQRLogin::AVAILABILITY_REASON_HTTPS_REQUIRED,
			$data['reason']
		);
	}

	/**
	 * @testdox Availability endpoint mirrors the token endpoint when the request is HTTP but siteurl is HTTPS.
	 */
	public function test_availability_reports_https_required_when_request_is_not_secure(): void {
		wp_set_current_user( $this->admin_id );
		$this->force_https( false );
		$this->force_site_url( 'https://example.org' );

		$response = $this->dispatch_availability();

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertFalse( $data['available'] );
		$this->assertSame(
			MobileAppQRLogin::AVAILABILITY_REASON_HTTPS_REQUIRED,
			$data['reason']
		);
	}

	/**
	 * @testdox Availability endpoint reports `application_passwords_disabled_by_filter` when the WP filter returns false.
	 */
	public function test_availability_reports_filter_when_application_passwords_filter_disables_them(): void {
		wp_set_current_user( $this->admin_id );

		add_filter( 'wp_is_application_passwords_available', '__return_false' );
		try {
			$response = $this->dispatch_availability();
		} finally {
			remove_filter( 'wp_is_application_passwords_available', '__return_false' );
		}

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertFalse( $data['available'] );
		$this->assertSame(
			MobileAppQRLogin::AVAILABILITY_REASON_AP_DISABLED_BY_FILTER,
			$data['reason']
		);
	}

	/**
	 * @testdox Availability endpoint rejects subscribers with the same capability gate as the token endpoint.
	 */
	public function test_availability_rejects_subscriber(): void {
		wp_set_current_user( $this->subscriber_id );

		$response = $this->dispatch_availability();

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
	}

	/**
	 * @testdox Availability endpoint emits no-cache headers so a stale unavailable response cannot be pinned.
	 */
	public function test_availability_emits_nocache_headers(): void {
		wp_set_current_user( $this->admin_id );

		$response = $this->dispatch_availability();

		$this->assertSame( 200, $response->get_status() );
		$headers = array_change_key_case( $response->get_headers(), CASE_LOWER );
		$this->assertArrayHasKey( 'cache-control', $headers );
		$this->assertStringContainsStringIgnoringCase(
			'no-cache',
			(string) $headers['cache-control']
		);
	}

	/**
	 * @testdox Distractor distinctness invariant — across many challenge generations, every triple has all values ≥ 100 apart.
	 */
	public function test_distractor_distinctness_invariant(): void {
		// Direct invocation via reflection so we don't have to fire 200
		// /qr-login-scan requests (which would hit the rate limiter at 10/IP).
		$controller = new MobileAppQRLogin();
		$reflection = new \ReflectionClass( $controller );
		$method     = $reflection->getMethod( 'generate_challenge_numbers' );
		$method->setAccessible( true );

		for ( $i = 0; $i < 200; $i++ ) {
			$challenge = $method->invoke( $controller );
			$values    = array_map( 'intval', array_merge( array( $challenge['real'] ), $challenge['distractors'] ) );
			$this->assertCount( 3, $values, 'Challenge generator must always return 1 real + 2 distractors.' );

			$count = count( $values );
			for ( $a = 0; $a < $count; $a++ ) {
				for ( $b = $a + 1; $b < $count; $b++ ) {
					$this->assertGreaterThanOrEqual(
						100,
						abs( $values[ $a ] - $values[ $b ] ),
						sprintf( 'Iteration #%d: %d vs %d are < 100 apart — would let a partial-read leak fingerprint the real number.', $i, $values[ $a ], $values[ $b ] )
					);
				}
			}
		}
	}
}
