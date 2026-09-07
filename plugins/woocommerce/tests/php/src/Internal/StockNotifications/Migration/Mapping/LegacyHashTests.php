<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Mapping;

use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\LegacyHash;
use WC_Unit_Test_Case;

/**
 * Tests for LegacyHash.
 *
 * `compute()` must reproduce `WC_BIS_Notification_Data::get_hash()` from the legacy Back
 * In Stock Notifications extension byte for byte, since it is what lets an already-sent
 * unsubscribe link keep working after migration.
 */
class LegacyHashTests extends WC_Unit_Test_Case {

	/**
	 * @before
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_fast_hash' ) || ! function_exists( 'wp_verify_fast_hash' ) ) {
			$this->markTestSkipped( 'wp_fast_hash()/wp_verify_fast_hash() require WordPress 6.8 or newer.' );
		}
	}

	/**
	 * @testdox compute() should reproduce the legacy get_hash() token for a fixture row.
	 */
	public function test_compute_matches_legacy_get_hash_fixture(): void {
		// This fixture is reproduced rather than captured: there is no legacy install here
		// to take a real delivered token from. It reimplements
		// `WC_BIS_Notification_Data::get_hash()`'s exact inputs
		// (`{id}-{product_id}-{create_date}`, AES-256-CBC via openssl_encrypt(), then a
		// sha256 digest) independently here, so the test does not simply call
		// LegacyHash::compute() and compare it to itself.
		$legacy_id          = 42;
		$product_id         = 917;
		$legacy_create_date = 1700000000;
		$hash_key           = 'this-is-a-32-byte-legacy-hash-k';
		$hash_iv            = 'legacy-iv-16-byt';

		$expected_input     = "{$legacy_id}-{$product_id}-{$legacy_create_date}";
		$expected_encrypted = openssl_encrypt( $expected_input, 'AES-256-CBC', $hash_key, 0, $hash_iv );
		$expected_token     = hash( 'sha256', $expected_encrypted );

		$result = LegacyHash::compute( $legacy_id, $product_id, $legacy_create_date, $hash_key, $hash_iv );

		$this->assertSame( $expected_token, $result );
	}

	/**
	 * @testdox compute() should return null when the hash key secret is missing.
	 */
	public function test_compute_returns_null_when_key_missing(): void {
		$result = LegacyHash::compute( 42, 917, 1700000000, '', 'legacy-iv-16-byt' );

		$this->assertNull( $result );
	}

	/**
	 * @testdox compute() should return null when the hash iv secret is missing.
	 */
	public function test_compute_returns_null_when_iv_missing(): void {
		$result = LegacyHash::compute( 42, 917, 1700000000, 'this-is-a-32-byte-legacy-hash-k', '' );

		$this->assertNull( $result );
	}

	/**
	 * @testdox compute() should return null when both secrets are missing.
	 */
	public function test_compute_returns_null_when_both_secrets_missing(): void {
		$this->assertNull( LegacyHash::compute( 42, 917, 1700000000, '', '' ) );
	}

	/**
	 * @testdox compute_verification() should reproduce the legacy get_verification_hash() token for a fixture row.
	 */
	public function test_compute_verification_matches_legacy_fixture(): void {
		// Reproduced, not captured, for the same reason as the get_hash() fixture above:
		// `WC_BIS_Notification_Data::get_verification_hash()` encrypts the verification code
		// with AES-256-CBC under the row's own `_verification_key`/`_verification_iv` and
		// takes a sha256 digest of the ciphertext.
		$code = 'verification-code-fixture';
		$key  = 'this-is-a-32-byte-legacy-hash-k';
		$iv   = 'legacy-iv-16-byt';

		$expected_token = hash( 'sha256', openssl_encrypt( $code, 'AES-256-CBC', $key, 0, $iv ) );

		$this->assertSame( $expected_token, LegacyHash::compute_verification( $code, $key, $iv ) );
	}

	/**
	 * @testdox compute_verification() should return null when any of its inputs is empty.
	 * @dataProvider provider_incomplete_verification_secrets
	 *
	 * @param string $code Test case value.
	 * @param string $key  Test case value.
	 * @param string $iv   Test case value.
	 */
	public function test_compute_verification_returns_null_on_incomplete_input( string $code, string $key, string $iv ): void {
		$this->assertNull( LegacyHash::compute_verification( $code, $key, $iv ) );
	}

	/**
	 * Verification triples with at least one missing member.
	 *
	 * @return array
	 */
	public function provider_incomplete_verification_secrets(): array {
		$code = 'verification-code-fixture';
		$key  = 'this-is-a-32-byte-legacy-hash-k';
		$iv   = 'legacy-iv-16-byt';

		return array(
			'code missing' => array( '', $key, $iv ),
			'key missing'  => array( $code, '', $iv ),
			'iv missing'   => array( $code, $key, '' ),
			'all missing'  => array( '', '', '' ),
		);
	}

	/**
	 * @testdox to_meta_value() should store the hashed form of the token, never the raw token.
	 */
	public function test_to_meta_value_stores_hashed_form_not_raw_token(): void {
		$token      = hash( 'sha256', 'some-encrypted-legacy-payload' );
		$meta_value = LegacyHash::to_meta_value( $token );

		$this->assertNotSame( $token, $meta_value, 'The raw token must never be the stored value.' );
		$this->assertStringNotContainsString( $token, $meta_value, 'The stored meta value must not contain the raw token.' );
	}

	/**
	 * @testdox to_meta_value()/parse()/verify() should round-trip a token end to end.
	 */
	public function test_round_trips_through_parse_and_verify(): void {
		$token      = hash( 'sha256', 'another-encrypted-legacy-payload' );
		$meta_value = LegacyHash::to_meta_value( $token );

		$parsed = LegacyHash::parse( $meta_value );

		$this->assertNotNull( $parsed );
		$this->assertSame( $meta_value, $parsed[0] );

		$this->assertTrue( LegacyHash::verify( $meta_value, $token ) );
		$this->assertFalse( LegacyHash::verify( $meta_value, hash( 'sha256', 'a-different-token' ) ) );
	}

	/**
	 * @testdox parse() should reject malformed stored values.
	 * @dataProvider provider_malformed_meta_values
	 *
	 * @param string $meta_value Test case value.
	 */
	public function test_parse_rejects_malformed_values( string $meta_value ): void {
		$this->assertNull( LegacyHash::parse( $meta_value ) );
	}

	/**
	 * Malformed `_wc_bis_legacy_*_hash_*` values that parse() must reject.
	 *
	 * @return array
	 */
	public function provider_malformed_meta_values(): array {
		return array(
			'empty expiry'       => array( ':abcdef123456' ),
			'empty digest'       => array( '1700003600:' ),
			'non numeric expiry' => array( 'soon:abcdef123456' ),
			'completely empty'   => array( '' ),
		);
	}

	/**
	 * @testdox verify() should return false for a malformed stored value.
	 */
	public function test_verify_returns_false_for_malformed_meta_value(): void {
		$this->assertFalse( LegacyHash::verify( 'not-a-valid-value', 'some-token' ) );
	}

	/**
	 * @testdox to_meta_value()/parse()/verify() should round-trip an expiring token end to end.
	 */
	public function test_round_trips_an_expiring_token(): void {
		$expires_at = 1700003600;
		$token      = hash( 'sha256', 'an-encrypted-verification-payload' );
		$meta_value = LegacyHash::to_meta_value( $token, $expires_at );

		$this->assertStringStartsWith( "{$expires_at}:", $meta_value );
		$this->assertStringNotContainsString( $token, $meta_value, 'The stored meta value must not contain the raw token.' );

		$parsed = LegacyHash::parse( $meta_value );

		$this->assertNotNull( $parsed );
		$this->assertSame( substr( $meta_value, strlen( "{$expires_at}:" ) ), $parsed[0] );
		$this->assertSame( $expires_at, $parsed[1] );

		$this->assertTrue( LegacyHash::verify( $meta_value, $token ) );
		$this->assertFalse( LegacyHash::verify( $meta_value, hash( 'sha256', 'a-different-token' ) ) );
	}

	/**
	 * @testdox parse() should report a non-expiring token's expiry as null.
	 */
	public function test_parse_reports_a_null_expiry_for_the_bare_digest_shape(): void {
		$parsed = LegacyHash::parse( LegacyHash::to_meta_value( hash( 'sha256', 'payload' ) ) );

		$this->assertNotNull( $parsed );
		$this->assertNull( $parsed[1] );
	}

	/**
	 * @testdox verify() should not accept a token stored under the other shape's digest position.
	 */
	public function test_verify_does_not_confuse_the_two_shapes(): void {
		$token = hash( 'sha256', 'payload' );

		// Read as the expiring shape, this value's digest field is the timestamp and its
		// expiry field is the digest. Neither shape may verify as the other.
		$this->assertFalse( LegacyHash::verify( wp_fast_hash( $token ) . ':1700003600', $token ) );
		$this->assertFalse( LegacyHash::verify( '1700003600', $token ) );
	}
}
