<?php
/**
 * Tests for WC_Shortcode_My_Account.
 *
 * @package WooCommerce\Tests\Shortcodes
 */

declare( strict_types = 1 );

/**
 * Class WC_Test_Shortcode_My_Account.
 */
class WC_Test_Shortcode_My_Account extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures: clear request-scope superglobals to avoid leaking state
	 * between tests in this file.
	 */
	public function tearDown(): void {
		unset( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] );
		unset( $_GET['wc-resetpass-token'] );
		parent::tearDown();
	}

	/**
	 * @testdox set_password_reset_token returns a non-empty opaque token and stores the value.
	 */
	public function test_set_password_reset_token_stores_value(): void {
		$value = '42:somekey';

		$token = WC_Shortcode_My_Account::set_password_reset_token( $value );

		$this->assertIsString( $token, 'Token should be a string on success.' );
		$this->assertNotEmpty( $token, 'Token should not be empty.' );
		$this->assertSame( $value, get_transient( '_wc_resetpass_token_' . $token ), 'Transient should hold the original value.' );
	}

	/**
	 * @testdox set_password_reset_token rejects empty or non-string values.
	 */
	public function test_set_password_reset_token_rejects_invalid_input(): void {
		$this->assertFalse( WC_Shortcode_My_Account::set_password_reset_token( '' ), 'Empty string should be rejected.' );
		$this->assertFalse( WC_Shortcode_My_Account::set_password_reset_token( null ), 'Null should be rejected.' );
		$this->assertFalse( WC_Shortcode_My_Account::set_password_reset_token( 123 ), 'Non-string should be rejected.' );
	}

	/**
	 * @testdox get_password_reset_token_value returns the stored value once and then invalidates it.
	 */
	public function test_get_password_reset_token_value_is_single_use(): void {
		$value = '7:abc123';
		$token = WC_Shortcode_My_Account::set_password_reset_token( $value );
		$this->assertIsString( $token );

		$first  = WC_Shortcode_My_Account::get_password_reset_token_value( $token );
		$second = WC_Shortcode_My_Account::get_password_reset_token_value( $token );

		$this->assertSame( $value, $first, 'First read should return stored value.' );
		$this->assertFalse( $second, 'Second read should fail; token is single-use.' );
	}

	/**
	 * @testdox get_password_reset_token_value returns false for unknown or empty tokens.
	 */
	public function test_get_password_reset_token_value_unknown(): void {
		$this->assertFalse( WC_Shortcode_My_Account::get_password_reset_token_value( '' ) );
		$this->assertFalse( WC_Shortcode_My_Account::get_password_reset_token_value( 'no-such-token' ) );
	}

	/**
	 * @testdox get_password_reset_credentials prefers the wp-resetpass cookie when set.
	 */
	public function test_get_password_reset_credentials_uses_cookie(): void {
		$_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] = '13:cookie-key';

		$creds = WC_Shortcode_My_Account::get_password_reset_credentials();

		$this->assertIsArray( $creds, 'Credentials should be returned as an array.' );
		$this->assertSame( '13', $creds['id'] );
		$this->assertSame( 'cookie-key', $creds['key'] );
	}

	/**
	 * @testdox get_password_reset_credentials falls back to the query token when the cookie is missing.
	 */
	public function test_get_password_reset_credentials_falls_back_to_token(): void {
		$value = '21:fallback-key';
		$token = WC_Shortcode_My_Account::set_password_reset_token( $value );
		$this->assertIsString( $token );

		// No cookie, only the token query param (simulating SameSite=Strict drop).
		$_GET['wc-resetpass-token'] = $token;

		$creds = WC_Shortcode_My_Account::get_password_reset_credentials();

		$this->assertIsArray( $creds, 'Fallback path should produce credentials.' );
		$this->assertSame( '21', $creds['id'] );
		$this->assertSame( 'fallback-key', $creds['key'] );
	}

	/**
	 * @testdox get_password_reset_credentials returns false when neither cookie nor token is present.
	 */
	public function test_get_password_reset_credentials_returns_false_when_nothing_present(): void {
		$this->assertFalse( WC_Shortcode_My_Account::get_password_reset_credentials() );
	}

	/**
	 * @testdox get_password_reset_credentials ignores a malformed cookie without a colon separator.
	 */
	public function test_get_password_reset_credentials_ignores_malformed_cookie(): void {
		$_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] = 'no-colon-value';

		$this->assertFalse( WC_Shortcode_My_Account::get_password_reset_credentials() );
	}

	/**
	 * @testdox Fallback token cannot be replayed after the credentials have been consumed once.
	 */
	public function test_token_cannot_be_replayed(): void {
		$value = '99:one-shot';
		$token = WC_Shortcode_My_Account::set_password_reset_token( $value );
		$this->assertIsString( $token );

		$_GET['wc-resetpass-token'] = $token;

		$first  = WC_Shortcode_My_Account::get_password_reset_credentials();
		$second = WC_Shortcode_My_Account::get_password_reset_credentials();

		$this->assertIsArray( $first );
		$this->assertFalse( $second, 'Token should be invalidated after first credential lookup.' );
	}
}
