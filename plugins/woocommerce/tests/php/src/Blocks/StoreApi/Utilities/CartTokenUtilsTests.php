<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit tests for CartTokenUtils.
 */
class CartTokenUtilsTests extends TestCase {
	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		add_filter( 'salt', [ $this, 'mock_salt' ] );
	}

	/**
	 * Tear down test environment.
	 */
	protected function tearDown(): void {
		remove_filter( 'salt', [ $this, 'mock_salt' ] );
		parent::tearDown();
	}

	/**
	 * Mock callback for the 'salt' filter.
	 *
	 * @return string
	 */
	public function mock_salt() {
		return 'test_salt';
	}

	/**
	 * Test that a generated cart token is valid and can be validated.
	 */
	public function test_get_cart_token_and_validate() {
		$customer_id = 't_12345';
		$token       = CartTokenUtils::get_cart_token( $customer_id );
		$this->assertIsString( $token );
		$this->assertTrue( CartTokenUtils::validate_cart_token( $token ) );
	}

	/**
	 * Test that the payload can be extracted from a cart token.
	 */
	public function test_get_cart_token_payload() {
		$customer_id = 't_abcde';
		$token       = CartTokenUtils::get_cart_token( $customer_id );
		$payload     = CartTokenUtils::get_cart_token_payload( $token );
		$this->assertIsArray( $payload );
		$this->assertEquals( $customer_id, $payload['user_id'] );
		$this->assertEquals( 'store-api', $payload['iss'] );
		$this->assertArrayHasKey( 'exp', $payload );
		$this->assertIsInt( $payload['exp'] );
	}

	/**
	 * Test that an invalid token fails validation.
	 */
	public function test_invalid_token_fails_validation() {
		$this->assertFalse( CartTokenUtils::validate_cart_token( 'invalid.token.value' ) );
	}

	/**
	 * Test that an expired token fails validation.
	 */
	public function test_expired_token_fails_validation() {
		// Create a token with a past expiration.
		$payload = [
			'user_id' => 't_expired',
			'exp'     => time() - 100,
			'iss'     => 'store-api',
		];
		$secret  = '@' . wp_salt();
		$token   = \Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken::create( $payload, $secret );
		$this->assertFalse( CartTokenUtils::validate_cart_token( $token ) );
	}

	/**
	 * @testdox A cart link token names the session but is not a valid Store API cart token.
	 */
	public function test_cart_link_token_is_not_a_cart_token() {
		$token = CartTokenUtils::get_cart_link_token( 't_link' );

		$this->assertSame( 't_link', CartTokenUtils::get_cart_link_customer_id( $token ), 'A link token should name its session.' );
		$this->assertFalse( CartTokenUtils::validate_cart_token( $token ), 'A link token should not validate as a cart token.' );
		$this->assertSame( '', CartTokenUtils::get_cart_token_payload( $token )['user_id'], 'A link token should not expose a cart token payload.' );
	}

	/**
	 * @testdox Cart tokens are still accepted where a cart link token is expected.
	 */
	public function test_cart_token_is_accepted_as_cart_link_token() {
		$token = CartTokenUtils::get_cart_token( 't_headless' );

		$this->assertSame( 't_headless', CartTokenUtils::get_cart_link_customer_id( $token ) );
	}

	/**
	 * @testdox Expired, unsigned, or unknown-issuer tokens are rejected where a cart link token is expected.
	 */
	public function test_invalid_tokens_are_rejected_as_cart_link_tokens() {
		$secret = '@' . wp_salt();

		$expired = \Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken::create(
			[
				'user_id' => 't_expired',
				'exp'     => time() - 100,
				'iss'     => 'cart-link',
			],
			$secret
		);
		$other   = \Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken::create(
			[
				'user_id' => 't_other',
				'exp'     => time() + 100,
				'iss'     => 'other',
			],
			$secret
		);

		$this->assertSame( '', CartTokenUtils::get_cart_link_customer_id( $expired ), 'Expired link tokens should be rejected.' );
		$this->assertSame( '', CartTokenUtils::get_cart_link_customer_id( $other ), 'Tokens from other issuers should be rejected.' );
		$this->assertSame( '', CartTokenUtils::get_cart_link_customer_id( 'invalid.token.value' ), 'Malformed tokens should be rejected.' );
		$this->assertFalse( CartTokenUtils::validate_cart_token( $other ), 'Tokens from other issuers should not validate as cart tokens.' );
	}
}
