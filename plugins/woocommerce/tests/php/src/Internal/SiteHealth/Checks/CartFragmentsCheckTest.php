<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth\Checks;

use Automattic\WooCommerce\Internal\SiteHealth\Checks\CartFragmentsCheck;
use WC_Unit_Test_Case;

/**
 * Tests for the CartFragmentsCheck class.
 */
class CartFragmentsCheckTest extends WC_Unit_Test_Case {

	/**
	 * Remove the HTTP request stub between tests.
	 */
	public function tearDown(): void {
		remove_all_filters( 'pre_http_request' );
		parent::tearDown();
	}

	/**
	 * The check is recommended when the cart fragments script is present in the loopback response.
	 */
	public function test_recommended_when_fragments_in_response(): void {
		$this->stub_response( '<html><script id="wc-cart-fragments-js"></script></html>' );
		$this->assertSame( 'recommended', ( new CartFragmentsCheck() )->run()['status'] );
	}

	/**
	 * The check is good when the cart fragments script is absent from the loopback response.
	 */
	public function test_good_when_fragments_not_in_response(): void {
		$this->stub_response( '<html>no fragments here</html>' );
		$this->assertSame( 'good', ( new CartFragmentsCheck() )->run()['status'] );
	}

	/**
	 * The check is recommended when the loopback request fails entirely.
	 */
	public function test_recommended_when_loopback_fails(): void {
		add_filter( 'pre_http_request', fn() => new \WP_Error( 'http_request_failed', 'connection refused' ) );
		$this->assertSame( 'recommended', ( new CartFragmentsCheck() )->run()['status'] );
	}

	/**
	 * Stub the loopback HTTP request to return a 200 response with the given body.
	 *
	 * @param string $body The response body to return.
	 */
	private function stub_response( string $body ): void {
		add_filter(
			'pre_http_request',
			static function () use ( $body ) {
				return array(
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'body'     => $body,
					'headers'  => array(),
					'cookies'  => array(),
					'filename' => null,
				);
			}
		);
	}
}
