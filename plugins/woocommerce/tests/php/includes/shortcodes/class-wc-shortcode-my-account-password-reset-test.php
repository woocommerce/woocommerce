<?php
declare( strict_types = 1 );

/**
 * Tests for the My Account password-reset flow.
 *
 * @package WooCommerce\Tests\Shortcodes
 */

/**
 * Class WC_Shortcode_My_Account_Password_Reset_Test.
 */
class WC_Shortcode_My_Account_Password_Reset_Test extends WC_Unit_Test_Case {

	/**
	 * Test customer.
	 *
	 * @var WP_User
	 */
	private WP_User $user;

	/**
	 * Test customer's WordPress password-reset key.
	 *
	 * @var string
	 */
	private string $reset_key;

	/**
	 * Set up a customer with an active WordPress password-reset key.
	 */
	public function setUp(): void {
		parent::setUp();

		$user_id = self::factory()->user->create(
			array(
				'role'       => 'customer',
				'user_login' => 'reset-bridge-customer',
			)
		);

		$this->user = new WP_User( $user_id );
		$reset_key  = get_password_reset_key( $this->user );
		$this->assertIsString( $reset_key );
		$this->reset_key = $reset_key;
		$this->user      = new WP_User( $user_id );

		wp_set_current_user( 0 );
		wc_clear_notices();
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
		$_COOKIE  = array();
	}

	/**
	 * Clean up request state.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		wc_clear_notices();
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
		$_COOKIE  = array();

		parent::tearDown();
	}

	/**
	 * @testdox A URL handle is consumed and exchanged for a signed form token.
	 */
	public function test_url_handle_exchanges_for_signed_form_token(): void {
		$handle   = WC_Shortcode_My_Account::create_password_reset_bridge_token( $this->user );
		$template = $this->render_bridge_handle( $handle );
		$token    = $template['args']['key'];

		$this->assertMatchesRegularExpression( '/^[A-Za-z0-9]{32}$/', $handle );
		$this->assertMatchesRegularExpression( '/^wc1\.[1-9][0-9]*\.[0-9]+\.[A-Za-z0-9]{32}\.[a-f0-9]{64}$/', $token );
		$this->assertNotSame( $handle, $token );
		$this->assertSame( $this->user->ID, WC_Shortcode_My_Account::check_password_reset_key( $token, $this->user->user_login )->ID );
	}

	/**
	 * @testdox The bridge renders the reset form when a Strict cookie is unavailable.
	 */
	public function test_bridge_renders_reset_form_without_cookie(): void {
		$handle   = WC_Shortcode_My_Account::create_password_reset_bridge_token( $this->user );
		$template = $this->render_bridge_handle( $handle );

		$this->assertSame( 'myaccount/form-reset-password.php', $template['name'] );
		$this->assertNotSame( $handle, $template['args']['key'] );
		$this->assertSame( $this->user->user_login, $template['args']['login'] );
	}

	/**
	 * @testdox A stale well-formed cookie does not suppress a valid bridge.
	 */
	public function test_stale_cookie_falls_back_to_valid_bridge(): void {
		$handle = WC_Shortcode_My_Account::create_password_reset_bridge_token( $this->user );

		$_GET = array(
			'show-reset-form' => 'true',
			'reset-token'     => $handle,
		);

		$_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] = $this->user->ID . ':stale-reset-key';

		$template = $this->render_lost_password_page();

		$this->assertSame( 'myaccount/form-reset-password.php', $template['name'] );
		$this->assertNotSame( $handle, $template['args']['key'] );
		$this->assertSame( $this->user->ID, WC_Shortcode_My_Account::check_password_reset_key( $template['args']['key'], $this->user->user_login )->ID );
		$this->assertSame( 0, wc_notice_count( 'error' ) );
	}

	/**
	 * @testdox A valid WordPress reset cookie keeps precedence over a valid bridge.
	 */
	public function test_valid_cookie_takes_precedence_over_valid_bridge(): void {
		$other_user_id = self::factory()->user->create(
			array(
				'role'       => 'customer',
				'user_login' => 'cookie-reset-customer',
			)
		);
		$other_user    = get_userdata( $other_user_id );
		$other_key     = get_password_reset_key( $other_user );
		$handle        = WC_Shortcode_My_Account::create_password_reset_bridge_token( $this->user );

		$_GET = array(
			'show-reset-form' => 'true',
			'reset-token'     => $handle,
		);

		$_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] = $other_user_id . ':' . $other_key;

		$template = $this->render_lost_password_page();

		$this->assertSame( $other_key, $template['args']['key'] );
		$this->assertSame( $other_user->user_login, $template['args']['login'] );

		$_COOKIE = array();
		wc_clear_notices();
		$replay = $this->render_lost_password_page();
		$this->assertSame( 'myaccount/form-lost-password.php', $replay['name'] );
	}

	/**
	 * @testdox Tampered, expired, and cross-user bridge tokens fail closed.
	 */
	public function test_invalid_bridge_tokens_fail_closed(): void {
		$handle         = WC_Shortcode_My_Account::create_password_reset_bridge_token( $this->user );
		$template       = $this->render_bridge_handle( $handle );
		$token          = $template['args']['key'];
		$tampered_token = substr( $token, 0, -1 ) . ( 'a' === substr( $token, -1 ) ? 'b' : 'a' );
		$expired_token  = $this->create_signed_token( $this->user, time() - 1 );

		$this->assertFalse( WC_Shortcode_My_Account::check_password_reset_key( $tampered_token, $this->user->user_login ) );
		wc_clear_notices();
		$this->assertFalse( WC_Shortcode_My_Account::check_password_reset_key( $expired_token, $this->user->user_login ) );
		wc_clear_notices();
		$this->assertFalse( WC_Shortcode_My_Account::check_password_reset_key( $token, 'another-customer' ) );
		wc_clear_notices();

		add_filter( 'password_reset_expiration', '__return_zero' );
		try {
			$this->assertFalse( WC_Shortcode_My_Account::check_password_reset_key( $token, $this->user->user_login ) );
		} finally {
			remove_filter( 'password_reset_expiration', '__return_zero' );
		}
	}

	/**
	 * @testdox Changing the password invalidates the bridge with WordPress reset state.
	 */
	public function test_password_change_invalidates_bridge(): void {
		$handle   = WC_Shortcode_My_Account::create_password_reset_bridge_token( $this->user );
		$template = $this->render_bridge_handle( $handle );
		$token    = $template['args']['key'];

		wp_set_password( 'new-secure-password', $this->user->ID );

		$this->assertFalse( WC_Shortcode_My_Account::check_password_reset_key( $token, $this->user->user_login ) );
	}

	/**
	 * @testdox A reset-key rotation between validation and storage invalidates the bridge.
	 */
	public function test_reset_key_rotation_invalidates_validated_snapshot_bridge(): void {
		$validated_user = new WP_User( $this->user->ID );
		$new_key        = get_password_reset_key( new WP_User( $this->user->ID ) );
		$this->assertIsString( $new_key );

		$handle   = WC_Shortcode_My_Account::create_password_reset_bridge_token( $validated_user );
		$template = $this->render_bridge_handle( $handle );

		$this->assertSame( 'myaccount/form-lost-password.php', $template['name'] );
	}

	/**
	 * @testdox Password validation errors preserve the signed reset form after handle consumption.
	 */
	public function test_password_validation_error_preserves_reset_form(): void {
		$handle   = WC_Shortcode_My_Account::create_password_reset_bridge_token( $this->user );
		$template = $this->render_bridge_handle( $handle );
		$token    = $template['args']['key'];
		$nonce    = wp_create_nonce( 'reset_password' );

		$_POST = array(
			'woocommerce-reset-password-nonce' => $nonce,
			'_wpnonce'                         => 'extension-nonce-that-must-not-win',
			'wc_reset_password'                => 'true',
			'password_1'                       => 'first-password',
			'password_2'                       => 'different-password',
			'reset_key'                        => $token,
			'reset_login'                      => $this->user->user_login,
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Build the request superglobal consumed by the form handler.
		$_REQUEST = $_POST;

		WC_Form_Handler::process_reset_password();
		$this->assertSame( 1, wc_notice_count( 'error' ) );

		$rerendered = $this->render_lost_password_page();
		$this->assertSame( 'myaccount/form-reset-password.php', $rerendered['name'] );
		$this->assertSame( $token, $rerendered['args']['key'] );
		$this->assertSame( $this->user->user_login, $rerendered['args']['login'] );
	}

	/**
	 * @testdox Creating a bridge does not persist the URL token or plaintext reset key.
	 */
	public function test_bridge_credentials_are_not_persisted(): void {
		global $wpdb;

		$token  = WC_Shortcode_My_Account::create_password_reset_bridge_token( $this->user );
		$user   = new WP_User( $this->user->ID );
		$tables = array(
			$wpdb->options  => 'option_value',
			$wpdb->usermeta => 'meta_value',
		);

		foreach ( $tables as $table => $value_column ) {
			$token_count = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$value_column} LIKE %s", '%' . $wpdb->esc_like( $token ) . '%' ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			);
			$key_count   = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$value_column} LIKE %s", '%' . $wpdb->esc_like( $this->reset_key ) . '%' ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			);
			$state_count = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$value_column} LIKE %s", '%' . $wpdb->esc_like( $user->user_activation_key ) . '%' ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			);

			$this->assertSame( 0, $token_count, "The bridge token must not be stored in {$table}." );
			$this->assertSame( 0, $key_count, "The plaintext reset key must not be stored in {$table}." );
			$this->assertSame( 0, $state_count, "The hashed reset state must not be duplicated in {$table}." );
		}
	}

	/**
	 * @testdox The legacy reset link redirects with a bridge and without its original credentials.
	 */
	public function test_legacy_reset_link_redirects_with_bridge(): void {
		add_filter( 'woocommerce_is_account_page', '__return_true' );
		try {
			$identifiers = array(
				'id'    => (string) $this->user->ID,
				'login' => $this->user->user_login,
			);

			foreach ( $identifiers as $name => $identifier ) {
				$_GET = array(
					'key'    => $this->reset_key,
					$name    => $identifier,
					'action' => 'rp',
				);

				$location = $this->intercept_reset_link_redirect();
				$query    = wp_parse_url( $location, PHP_URL_QUERY );
				parse_str( is_string( $query ) ? $query : '', $args );

				$this->assertSame( 'true', $args['show-reset-form'] );
				$this->assertSame( 'rp', $args['action'] );
				$this->assertArrayHasKey( 'reset-token', $args );
				$this->assertArrayNotHasKey( 'key', $args );
				$this->assertArrayNotHasKey( 'id', $args );
				$this->assertArrayNotHasKey( 'login', $args );
				$this->assertMatchesRegularExpression( '/^[A-Za-z0-9]{32}$/', $args['reset-token'] );
				$template = $this->render_bridge_handle( $args['reset-token'] );
				$this->assertSame( $this->user->ID, WC_Shortcode_My_Account::check_password_reset_key( $template['args']['key'], $this->user->user_login )->ID );
			}
		} finally {
			remove_filter( 'woocommerce_is_account_page', '__return_true' );
		}
	}

	/**
	 * @testdox Invalid legacy credentials do not receive a signed bridge.
	 */
	public function test_invalid_legacy_reset_link_does_not_receive_bridge(): void {
		$_GET = array(
			'key' => 'invalid-reset-key',
			'id'  => (string) $this->user->ID,
		);

		add_filter( 'woocommerce_is_account_page', '__return_true' );
		try {
			$location = $this->intercept_reset_link_redirect();
		} finally {
			remove_filter( 'woocommerce_is_account_page', '__return_true' );
		}

		$query = wp_parse_url( $location, PHP_URL_QUERY );
		parse_str( is_string( $query ) ? $query : '', $args );

		$this->assertArrayNotHasKey( 'reset-token', $args );
	}

	/**
	 * @testdox A logged-in user cannot create a bridge for another account.
	 */
	public function test_logged_in_user_mismatch_still_stops_redirect(): void {
		$other_user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $other_user_id );

		$_GET = array(
			'key' => $this->reset_key,
			'id'  => (string) $this->user->ID,
		);

		$redirected = false;
		$capture    = static function () use ( &$redirected ): void {
			$redirected = true;
		};

		add_filter( 'woocommerce_is_account_page', '__return_true' );
		add_filter( 'wp_redirect', $capture );
		try {
			WC_Form_Handler::redirect_reset_password_link();
		} finally {
			remove_filter( 'wp_redirect', $capture );
			remove_filter( 'woocommerce_is_account_page', '__return_true' );
		}

		$this->assertFalse( $redirected );
		$this->assertSame( 1, wc_notice_count( 'error' ) );
	}

	/**
	 * @testdox Bridge responses prevent storage and referrer disclosure.
	 */
	public function test_bridge_response_headers_are_private(): void {
		$original_headers = array( 'Cache-Control' => 'public, max-age=600' );
		$_GET             = array(
			'show-reset-form' => 'true',
			'reset-token'     => str_repeat( 'a', 32 ),
		);

		$this->assertSame( $original_headers, WC_Form_Handler::set_reset_password_bridge_headers( $original_headers ) );

		add_filter( 'woocommerce_is_account_page', '__return_true' );
		try {
			$headers = WC_Form_Handler::set_reset_password_bridge_headers( $original_headers );
		} finally {
			remove_filter( 'woocommerce_is_account_page', '__return_true' );
		}

		$this->assertStringContainsString( 'no-store', $headers['Cache-Control'] );
		$this->assertStringContainsString( 'private', $headers['Cache-Control'] );
		$this->assertSame( 'no-referrer', $headers['Referrer-Policy'] );
	}

	/**
	 * Render the lost-password page and capture the selected template arguments.
	 *
	 * @return array{name: string, args: array<string, mixed>}
	 */
	private function render_lost_password_page(): array {
		$rendered = array(
			'name' => '',
			'args' => array(),
		);
		$capture  = static function ( string $template_name, string $template_path, string $located, array $args ) use ( &$rendered ): void {
			unset( $template_path, $located );
			if ( 0 !== strpos( $template_name, 'myaccount/' ) ) {
				return;
			}

			$rendered = array(
				'name' => $template_name,
				'args' => $args,
			);
		};

		add_action( 'woocommerce_before_template_part', $capture, 10, 4 );
		ob_start();
		WC_Shortcode_My_Account::lost_password();
		ob_end_clean();
		remove_action( 'woocommerce_before_template_part', $capture, 10 );

		return $rendered;
	}

	/**
	 * Consume a URL bridge handle and capture the resulting form.
	 *
	 * @param string $handle URL bridge handle.
	 * @return array{name: string, args: array<string, mixed>}
	 */
	private function render_bridge_handle( string $handle ): array {
		$_GET = array(
			'show-reset-form' => 'true',
			'reset-token'     => $handle,
		);

		return $this->render_lost_password_page();
	}

	/**
	 * Create a bridge token with a chosen expiry for boundary tests.
	 *
	 * @param WP_User $user       Token owner.
	 * @param int     $expiration Unix expiry timestamp.
	 * @return string
	 */
	private function create_signed_token( WP_User $user, int $expiration ): string {
		$user      = new WP_User( $user->ID );
		$nonce     = str_repeat( 'a', 32 );
		$claims    = implode( '|', array( 'wc1', $user->ID, $expiration, $nonce, $user->user_activation_key ) );
		$signature = hash_hmac( 'sha256', $claims, wp_salt( 'nonce' ) );

		return implode( '.', array( 'wc1', $user->ID, $expiration, $nonce, $signature ) );
	}

	/**
	 * Invoke the reset-link handler without allowing its trailing exit to stop PHPUnit.
	 *
	 * @return string Redirect location.
	 */
	private function intercept_reset_link_redirect(): string {
		$location = '';
		$abort    = static function ( string $redirect ) use ( &$location ): void {
			$location = $redirect;
			throw new RuntimeException( 'Password-reset redirect intercepted.' );
		};

		add_filter( 'wp_redirect', $abort );
		// PHPUnit has already emitted output; suppress only the expected setcookie() header warning from the existing handler.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
		set_error_handler(
			static function ( int $level, string $message ): bool {
				return E_WARNING === $level && false !== strpos( $message, 'Cannot modify header information' );
			}
		);
		try {
			WC_Form_Handler::redirect_reset_password_link();
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'Password-reset redirect intercepted.', $e->getMessage() );
		} finally {
			restore_error_handler();
			remove_filter( 'wp_redirect', $abort );
		}

		return $location;
	}
}
