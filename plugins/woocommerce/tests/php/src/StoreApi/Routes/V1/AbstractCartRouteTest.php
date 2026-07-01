<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the AbstractCartRoute nonce / cookie-authentication seam.
 */
class AbstractCartRouteTest extends WC_Unit_Test_Case {

	/**
	 * Build a dependency-free cart route that exposes the protected nonce seam.
	 *
	 * @param bool $cookie_authenticated Value the route reports for cookie auth.
	 * @return AbstractCartRoute
	 */
	private function make_route( bool $cookie_authenticated = true ): AbstractCartRoute {
		return new class( $cookie_authenticated ) extends AbstractCartRoute {
			/**
			 * Whether the stubbed route is cookie authenticated.
			 *
			 * @var bool
			 */
			private $cookie_authenticated;

			/**
			 * Constructor without the schema dependencies the seam does not need.
			 *
			 * @param bool $cookie_authenticated Value to report for cookie auth.
			 */
			public function __construct( bool $cookie_authenticated ) {
				$this->cookie_authenticated = $cookie_authenticated;
			}

			/**
			 * Get the path of this REST route.
			 *
			 * @return string
			 */
			public function get_path() {
				return '/test';
			}

			/**
			 * Get method arguments for this REST route.
			 *
			 * @return array
			 */
			public function get_args() {
				return array();
			}

			/**
			 * Expose is_cookie_authenticated for assertions.
			 *
			 * @param WP_REST_Request $request Request object.
			 * @return bool
			 */
			public function expose_is_cookie_authenticated( WP_REST_Request $request ): bool {
				return $this->is_cookie_authenticated( $request );
			}

			/**
			 * Expose requires_nonce for assertions.
			 *
			 * @param WP_REST_Request $request Request object.
			 * @return bool
			 */
			public function expose_requires_nonce( WP_REST_Request $request ): bool {
				return $this->requires_nonce( $request );
			}

			/**
			 * Honour the constructor-supplied cookie-auth value.
			 *
			 * @param WP_REST_Request $request Request object.
			 * @return bool
			 */
			protected function is_cookie_authenticated( WP_REST_Request $request ) {
				unset( $request );
				return $this->cookie_authenticated;
			}
		};
	}

	/**
	 * @testdox Should treat requests as cookie authenticated by default.
	 */
	public function test_is_cookie_authenticated_defaults_to_true(): void {
		$route = new class() extends AbstractCartRoute {
			/**
			 * Constructor without the schema dependencies the seam does not need.
			 */
			public function __construct() {}

			/**
			 * Get the path of this REST route.
			 *
			 * @return string
			 */
			public function get_path() {
				return '/test';
			}

			/**
			 * Get method arguments for this REST route.
			 *
			 * @return array
			 */
			public function get_args() {
				return array();
			}

			/**
			 * Expose is_cookie_authenticated for assertions.
			 *
			 * @param WP_REST_Request $request Request object.
			 * @return bool
			 */
			public function expose_is_cookie_authenticated( WP_REST_Request $request ): bool {
				return $this->is_cookie_authenticated( $request );
			}
		};

		$this->assertTrue(
			$route->expose_is_cookie_authenticated( new WP_REST_Request( 'POST', '/test' ) ),
			'Routes should default to cookie-authenticated so the nonce is required.'
		);
	}

	/**
	 * @testdox Should not require a nonce for read requests.
	 */
	public function test_read_request_does_not_require_nonce(): void {
		$route = $this->make_route();

		$this->assertFalse(
			$route->expose_requires_nonce( new WP_REST_Request( 'GET', '/test' ) ),
			'GET requests should never require a nonce.'
		);
	}

	/**
	 * @testdox Should require a nonce for cookie-authenticated update requests.
	 */
	public function test_update_request_requires_nonce_when_cookie_authenticated(): void {
		$route = $this->make_route( true );

		$this->assertTrue(
			$route->expose_requires_nonce( new WP_REST_Request( 'POST', '/test' ) ),
			'Cookie-authenticated update requests must require a nonce.'
		);
	}

	/**
	 * @testdox Should skip the nonce for update requests that are not cookie authenticated.
	 */
	public function test_update_request_skips_nonce_when_not_cookie_authenticated(): void {
		$route = $this->make_route( false );

		$this->assertFalse(
			$route->expose_requires_nonce( new WP_REST_Request( 'POST', '/test' ) ),
			'Non-cookie-authenticated routes are not a CSRF target and opt out of the nonce.'
		);
	}
}
