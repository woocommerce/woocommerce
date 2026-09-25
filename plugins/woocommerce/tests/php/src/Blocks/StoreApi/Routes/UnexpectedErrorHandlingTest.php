<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractRoute;
use Automattic\WooCommerce\StoreApi\Routes\V1\Batch;
use Automattic\WooCommerce\StoreApi\Routes\V1\Checkout;
use WC_Unit_Test_Case;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Tests for unexpected Store API route errors.
 */
class UnexpectedErrorHandlingTest extends WC_Unit_Test_Case {
	/**
	 * Set up the current user.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( 0 );
		Constants::set_constant( 'WP_DEBUG', false );
	}

	/**
	 * Restore the current user.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		Constants::clear_single_constant( 'WP_DEBUG' );
		parent::tearDown();
	}

	/**
	 * @testdox Should mask an engine failure from a public abstract route response.
	 */
	public function test_abstract_route_masks_engine_failure(): void {
		$route    = $this->create_abstract_route( new \TypeError( 'Fixture abstract route failure.' ) );
		$response = $route->get_response( new WP_REST_Request( 'GET', '/unexpected-error-fixture' ) );

		$this->assert_generic_error_response( $response );
	}

	/**
	 * @testdox Should mask an engine failure from a public cart route response.
	 */
	public function test_cart_route_masks_engine_failure(): void {
		$route    = $this->create_cart_route( new \TypeError( 'Fixture cart route failure.' ) );
		$response = $route->get_response( new WP_REST_Request( 'GET', '/unexpected-cart-error-fixture' ) );

		$this->assert_generic_error_response( $response );
	}

	/**
	 * @testdox Should show limited cart-session failure details to managers in debug mode.
	 */
	public function test_cart_session_failure_shows_limited_details_to_managers_in_debug_mode(): void {
		Constants::set_constant( 'WP_DEBUG', true );
		$this->login_as_administrator();
		$route = $this->create_cart_route( new \TypeError( 'Fixture cart session failure.' ), true );

		$response = $route->get_response( new WP_REST_Request( 'GET', '/unexpected-cart-error-fixture' ) );

		$this->assertSame( 500, $response->get_status(), 'Cart-session engine failures should keep status 500.' );
		$this->assertSame(
			array(
				'code'    => 'woocommerce_rest_unknown_server_error',
				'message' => 'Fixture cart session failure.',
				'data'    => array(
					'status'          => 500,
					'exception_class' => \TypeError::class,
				),
			),
			$response->get_data(),
			'Debug responses for managers should include only the cart-session failure message and class.'
		);
	}

	/**
	 * @testdox Should preserve the public cart-session failure message.
	 */
	public function test_cart_session_failure_preserves_public_message(): void {
		$route = $this->create_cart_route( new \TypeError( 'Fixture cart session failure.' ), true );

		$response = $route->get_response( new WP_REST_Request( 'GET', '/unexpected-cart-error-fixture' ) );

		$this->assertSame( 500, $response->get_status(), 'Cart-session engine failures should keep status 500.' );
		$this->assertSame(
			array(
				'code'    => 'woocommerce_rest_unknown_server_error',
				'message' => __( 'The cart could not be loaded. Please try again.', 'woocommerce' ),
				'data'    => array( 'status' => 500 ),
			),
			$response->get_data(),
			'Public cart-session errors should retain their established client-safe response.'
		);
	}

	/**
	 * @testdox Should not run cart update side effects after an engine failure.
	 */
	public function test_cart_route_skips_update_side_effects_after_engine_failure(): void {
		$route    = $this->create_cart_route( new \TypeError( 'Fixture cart update failure.' ) );
		$response = $route->get_response( new WP_REST_Request( 'POST', '/unexpected-cart-error-fixture' ) );

		$this->assert_generic_error_response( $response );
	}

	/**
	 * @testdox Should preserve cart update side effects after a successful update request.
	 */
	public function test_cart_route_preserves_update_side_effects_after_success(): void {
		$this->expectException( \LogicException::class );
		$this->expectExceptionMessage( 'Cart update side effects reached.' );

		$route = $this->create_cart_route( null );
		$route->get_response( new WP_REST_Request( 'POST', '/unexpected-cart-error-fixture' ) );
	}

	/**
	 * @testdox Should mask an engine failure from a public checkout route response.
	 */
	public function test_checkout_route_masks_engine_failure(): void {
		$route    = $this->create_checkout_route( new \TypeError( 'Fixture checkout route failure.' ) );
		$response = $route->get_response( new WP_REST_Request( 'GET', '/unexpected-checkout-error-fixture' ) );

		$this->assert_generic_error_response( $response );
	}

	/**
	 * @testdox Should mask an engine failure from a public batch route response.
	 */
	public function test_batch_route_masks_engine_failure(): void {
		$response = $this->dispatch_batch_route( new \TypeError( 'Fixture batch route failure.' ) );

		$this->assert_generic_error_response( $response );
	}

	/**
	 * @testdox Should preserve the existing ordinary exception response for the $route_kind route.
	 * @testWith ["abstract"]
	 *           ["cart"]
	 *           ["checkout"]
	 *           ["batch"]
	 *
	 * @param string $route_kind Which route fixture to dispatch through.
	 */
	public function test_ordinary_exception_response_is_unchanged( string $route_kind ): void {
		$failure = new \RuntimeException( 'Fixture ordinary exception.' );
		switch ( $route_kind ) {
			case 'cart':
				$route = $this->create_cart_route( $failure );
				break;
			case 'checkout':
				$route = $this->create_checkout_route( $failure );
				break;
			case 'batch':
				$route = null;
				break;
			default:
				$route = $this->create_abstract_route( $failure );
		}

		$response = $route
			? $route->get_response( new WP_REST_Request( 'GET', '/unexpected-error-fixture' ) )
			: $this->dispatch_batch_route( $failure );

		$this->assertSame( 500, $response->get_status(), 'Ordinary exception responses should keep status 500.' );
		$this->assertSame(
			array(
				'code'    => 'woocommerce_rest_unknown_server_error',
				'message' => 'Fixture ordinary exception.',
				'data'    => array( 'status' => 500 ),
			),
			$response->get_data(),
			'Ordinary exception responses should remain byte-for-byte compatible.'
		);
	}

	/**
	 * @testdox Should preserve expected route error details.
	 */
	public function test_expected_route_exception_response_is_unchanged(): void {
		$route = $this->create_abstract_route(
			new RouteException(
				'fixture_expected_error',
				'Fixture expected error.',
				409,
				array( 'fixture' => true )
			)
		);

		$response = $route->get_response( new WP_REST_Request( 'GET', '/unexpected-error-fixture' ) );

		$this->assertSame( 409, $response->get_status(), 'Expected route errors should keep their status.' );
		$this->assertSame(
			array(
				'code'    => 'fixture_expected_error',
				'message' => 'Fixture expected error.',
				'data'    => array(
					'fixture' => true,
					'status'  => 409,
				),
			),
			$response->get_data(),
			'Expected route errors should keep their public payload.'
		);
	}

	/**
	 * Dispatch a batch request whose inner REST server dispatch throws the given failure.
	 *
	 * @param \Throwable $failure Failure thrown by the REST server while serving the batch.
	 * @return WP_REST_Response
	 */
	private function dispatch_batch_route( \Throwable $failure ): WP_REST_Response {
		global $wp_rest_server;

		$original_server = $wp_rest_server;
		$wp_rest_server  = new class( $failure ) extends WP_REST_Server {
			/** @var \Throwable */
			private $failure;

			/**
			 * @param \Throwable $failure Failure thrown while serving the batch.
			 */
			public function __construct( \Throwable $failure ) {
				parent::__construct();
				$this->failure = $failure;
			}

			/**
			 * @param WP_REST_Request $batch_request Batch request.
			 * @throws \Throwable Always throws the configured fixture failure.
			 */
			public function serve_batch_request_v1( WP_REST_Request $batch_request ) {
				throw $this->failure;
			}
		};

		try {
			$request = new WP_REST_Request( 'POST', '/unexpected-batch-error-fixture' );
			$request->set_param(
				'requests',
				array(
					array( 'path' => '/wc/store/v1/products' ),
				)
			);

			$route = new class() extends Batch {
				/**
				 * Create the fixture without production constructor dependencies.
				 */
				public function __construct() {}
			};

			return $route->get_response( $request );
		} finally {
			$wp_rest_server = $original_server;
		}
	}

	/**
	 * Create a generic route with a configurable failure.
	 *
	 * @param \Throwable $failure Failure thrown during route dispatch.
	 * @return AbstractRoute
	 */
	private function create_abstract_route( \Throwable $failure ): AbstractRoute {
		return new class( $failure ) extends AbstractRoute {
			use ConfiguredFailureRouteTrait;
		};
	}

	/**
	 * Create a cart route with a configurable dispatch failure.
	 *
	 * @param \Throwable|null $failure            Configured route failure, or null for success.
	 * @param bool            $fail_while_loading Whether to fail while loading the cart session.
	 * @return AbstractCartRoute
	 */
	private function create_cart_route( ?\Throwable $failure, bool $fail_while_loading = false ): AbstractCartRoute {
		return new class( $failure, $fail_while_loading ) extends AbstractCartRoute {
			use ConfiguredFailureRouteTrait {
				__construct as private configure_failure;
			}

			/** @var bool */
			private $fail_while_loading;

			/**
			 * @param \Throwable|null $failure            Configured route failure, or null for success.
			 * @param bool            $fail_while_loading Whether to fail while loading the cart session.
			 */
			public function __construct( ?\Throwable $failure, bool $fail_while_loading ) {
				$this->configure_failure( $failure );
				$this->fail_while_loading = $fail_while_loading;
			}

			/**
			 * @param WP_REST_Request $request Request object.
			 * @throws \Throwable The configured fixture failure, when set to fail while loading.
			 */
			protected function load_cart_session( WP_REST_Request $request ) {
				if ( $this->fail_while_loading ) {
					throw $this->failure;
				}
			}

			/**
			 * @param WP_REST_Request $request Request object.
			 * @throws \LogicException Always throws if this side effect is reached.
			 */
			protected function cart_updated( WP_REST_Request $request ) {
				throw new \LogicException( 'Cart update side effects reached.' );
			}
		};
	}

	/**
	 * Create a checkout route with a configurable dispatch failure.
	 *
	 * @param \Throwable $failure Failure thrown during route dispatch.
	 * @return Checkout
	 */
	private function create_checkout_route( \Throwable $failure ): Checkout {
		return new class( $failure ) extends Checkout {
			use ConfiguredFailureRouteTrait;

			/**
			 * @param WP_REST_Request $request Request object.
			 */
			protected function load_cart_session( WP_REST_Request $request ) {}
		};
	}

	/**
	 * Assert a generic public Store API error response.
	 *
	 * @param WP_REST_Response $response Response object.
	 */
	private function assert_generic_error_response( WP_REST_Response $response ): void {
		$this->assertSame( 500, $response->get_status(), 'Engine failures should become status-500 Store API responses.' );
		$this->assertSame(
			array(
				'code'    => 'woocommerce_rest_unknown_server_error',
				'message' => __( 'Internal server error', 'woocommerce' ),
				'data'    => array( 'status' => 500 ),
			),
			$response->get_data(),
			'Public Store API responses must not disclose engine-error details.'
		);
	}
}
