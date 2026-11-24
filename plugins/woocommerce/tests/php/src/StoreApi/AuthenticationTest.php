<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\StoreApi;

use Automattic\WooCommerce\StoreApi\Authentication;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use WC_Unit_Test_Case;

/**
 * Tests for Store API authentication.
 */
class AuthenticationTest extends WC_Unit_Test_Case {

	/**
	 * @testdox CORS credentials are limited to allowed origins even when a cart token is present.
	 * @dataProvider cors_origin_provider
	 * @param string $origin_type Origin to test.
	 * @param string $token_type Token to send.
	 * @param bool   $allow_origin Whether the origin should be reflected.
	 * @param bool   $allow_credentials Whether credentials should be allowed.
	 */
	public function test_cors_credentials( string $origin_type, string $token_type, bool $allow_origin, bool $allow_credentials ): void {
		$origin = 'site' === $origin_type ? get_allowed_http_origins()[0] : 'https://other.example';
		if ( '' === $origin_type || 'null' === $origin_type ) {
			$origin = $origin_type;
		}
		if ( 'allowed' === $origin_type ) {
			add_filter(
				'allowed_http_origins',
				static function ( $origins ) use ( $origin ) {
					$origins[] = $origin;
					return $origins;
				}
			);
		}

		$original_server = $_SERVER;
		try {
			$_SERVER['HTTP_ORIGIN']     = $origin;
			$_SERVER['REQUEST_METHOD']  = 'GET';
			$_SERVER['HTTP_CART_TOKEN'] = 'valid' === $token_type ? CartTokenUtils::get_cart_token( 'test-customer' ) : ( 'invalid' === $token_type ? 'invalid' : '' );
			$server                     = new \Spy_REST_Server();
			$sut                        = new Authentication();
			$result                     = $sut->send_cors_headers( false, new \WP_REST_Response(), new \WP_REST_Request( 'GET', '/wc/store/v1/cart' ), $server );

			$this->assertFalse( $result, 'The served status should be preserved.' );
			if ( $allow_origin ) {
				$this->assertSame( $origin, $server->sent_headers['Access-Control-Allow-Origin'] ?? null, 'The expected origin should be allowed.' );
			} else {
				$this->assertArrayNotHasKey( 'Access-Control-Allow-Origin', $server->sent_headers, 'An unknown origin without a valid token should not be allowed.' );
			}
			if ( $allow_credentials ) {
				$this->assertSame( 'true', $server->sent_headers['Access-Control-Allow-Credentials'] ?? null, 'Allowed origins should be able to use credentials.' );
			} else {
				$this->assertArrayNotHasKey( 'Access-Control-Allow-Credentials', $server->sent_headers, 'Unknown origins must not be able to use browser credentials.' );
			}
		} finally {
			$_SERVER = $original_server;
		}
	}

	/**
	 * @return array<string, array<mixed>>
	 */
	public function cors_origin_provider(): array {
		return array(
			'site origin'                  => array( 'site', 'none', true, true ),
			'allowed origin'               => array( 'allowed', 'none', true, true ),
			'allowed origin with token'    => array( 'allowed', 'valid', true, true ),
			'unknown origin'               => array( 'unknown', 'none', false, false ),
			'unknown origin invalid token' => array( 'unknown', 'invalid', false, false ),
			'unknown origin valid token'   => array( 'unknown', 'valid', true, false ),
			'empty origin valid token'     => array( '', 'valid', true, false ),
			'null origin valid token'      => array( 'null', 'valid', true, false ),
		);
	}
}
