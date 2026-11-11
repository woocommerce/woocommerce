<?php
/**
 * Request context and Store API cart session hook tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

/**
 * Covers the woocommerce_request_context action, the request type getter and the
 * woocommerce_store_api_before_load_cart_session action.
 */
class RequestContextTest extends ControllerTestCase {

	/**
	 * Original $_SERVER REQUEST_URI.
	 *
	 * @var string|null
	 */
	private $original_request_uri;

	/**
	 * Original $_GET.
	 *
	 * @var array
	 */
	private $original_get;

	/**
	 * Original current screen global.
	 *
	 * @var mixed
	 */
	private $original_current_screen;

	/**
	 * Save globals touched by the tests.
	 */
	protected function setUp(): void {
		add_filter(
			'__experimental_woocommerce_store_api_batch_request_methods',
			function ( $methods ) {
				$methods[] = 'GET';
				return $methods;
			}
		);
		parent::setUp();
		$this->original_request_uri    = $_SERVER['REQUEST_URI'] ?? null;
		$this->original_get            = $_GET;
		$this->original_current_screen = $GLOBALS['current_screen'] ?? null;
	}

	/**
	 * Restore globals and filters touched by the tests.
	 */
	protected function tearDown(): void {
		if ( null === $this->original_request_uri ) {
			unset( $_SERVER['REQUEST_URI'] );
		} else {
			$_SERVER['REQUEST_URI'] = $this->original_request_uri;
		}
		$_GET                      = $this->original_get;
		$GLOBALS['current_screen'] = $this->original_current_screen;
		remove_all_filters( 'wp_doing_cron' );
		remove_all_filters( 'wp_doing_ajax' );
		remove_all_filters( 'woocommerce_is_rest_api_request' );
		remove_all_filters( '__experimental_woocommerce_store_api_batch_request_methods' );
		parent::tearDown();
	}

	/**
	 * Invoke the private request classifier on the WooCommerce instance.
	 *
	 * @return string
	 */
	private function classify_request(): string {
		$method = new \ReflectionMethod( WC(), 'determine_request_context' );
		return $method->invoke( WC() );
	}

	/**
	 * Data provider for request classification. Each case sets up the request, then expects a type.
	 *
	 * @return array
	 */
	public function request_type_data(): array {
		return array(
			'pretty store api route' => array( '/wp-json/wc/store/v1/cart', array(), 'store_api' ),
			'plain store api route'  => array( '/?rest_route=/wc/store/v1/cart', array( 'rest_route' => '/wc/store/v1/cart' ), 'store_api' ),
			'pretty rest route'      => array( '/wp-json/wc/v3/products', array(), 'rest' ),
			'plain rest route'       => array( '/?rest_route=/wc/v3/products', array( 'rest_route' => '/wc/v3/products' ), 'rest' ),
			'frontend page'          => array( '/shop/', array(), 'frontend' ),
		);
	}

	/**
	 * Each request is classified by its path or route.
	 *
	 * @dataProvider request_type_data
	 * @param string $request_uri Request URI.
	 * @param array  $get         Query parameters.
	 * @param string $expected    Expected request type.
	 */
	public function test_request_type_from_route( string $request_uri, array $get, string $expected ): void {
		$_SERVER['REQUEST_URI'] = $request_uri;
		$_GET                   = $get;

		$this->assertSame( $expected, $this->classify_request() );
	}

	/**
	 * Cron requests are classified as cron.
	 */
	public function test_request_type_cron(): void {
		$_SERVER['REQUEST_URI'] = '/wp-cron.php';
		add_filter( 'wp_doing_cron', '__return_true' );

		$this->assertSame( 'cron', $this->classify_request() );
	}

	/**
	 * AJAX requests are classified as ajax.
	 */
	public function test_request_type_ajax(): void {
		$_SERVER['REQUEST_URI'] = '/wp-admin/admin-ajax.php';
		add_filter( 'wp_doing_ajax', '__return_true' );

		$this->assertSame( 'ajax', $this->classify_request() );
	}

	/**
	 * Admin screens are classified as admin.
	 */
	public function test_request_type_admin(): void {
		$_SERVER['REQUEST_URI'] = '/wp-admin/index.php';
		set_current_screen( 'dashboard' );

		$this->assertTrue( is_admin() );
		$this->assertSame( 'admin', $this->classify_request() );
	}

	/**
	 * Store API takes priority over cron, AJAX, admin and REST.
	 */
	public function test_store_api_takes_priority(): void {
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/cart';
		add_filter( 'wp_doing_cron', '__return_true' );
		add_filter( 'wp_doing_ajax', '__return_true' );

		$this->assertSame( 'store_api', $this->classify_request() );
	}

	/**
	 * The woocommerce_is_rest_api_request filter is honoured by the REST branch.
	 */
	public function test_rest_filter_marks_request_as_rest(): void {
		$_SERVER['REQUEST_URI'] = '/shop/';
		add_filter( 'woocommerce_is_rest_api_request', '__return_true' );

		$this->assertSame( 'rest', $this->classify_request() );
	}

	/**
	 * The getter returns the stored type, and null before the action has fired.
	 */
	public function test_get_request_context_returns_stored_type(): void {
		$property = new \ReflectionProperty( WC(), 'request_context' );
		$original = $property->getValue( WC() );

		$property->setValue( WC(), null );
		$this->assertNull( WC()->get_request_context() );

		$property->setValue( WC(), 'store_api' );
		$this->assertSame( 'store_api', WC()->get_request_context() );

		$property->setValue( WC(), $original );
	}

	/**
	 * A Store API cart request fires the before-load action once, with the REST request.
	 */
	public function test_cart_route_fires_before_load_cart_session_with_request(): void {
		$received = array();
		$callback = function ( $request ) use ( &$received ) {
			$received[] = $request;
		};
		add_action( 'woocommerce_store_api_before_load_cart_session', $callback, 10, 1 );

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/cart' ) );

		remove_action( 'woocommerce_store_api_before_load_cart_session', $callback, 10 );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, $received );
		$this->assertInstanceOf( \WP_REST_Request::class, $received[0] );
		$this->assertSame( '/wc/store/v1/cart', $received[0]->get_route() );
	}

	/**
	 * Batch sub-requests to cart routes fire the before-load action for each sub-request.
	 */
	public function test_batch_cart_sub_requests_fire_before_load_cart_session(): void {
		$received = array();
		$callback = function ( $request ) use ( &$received ) {
			$received[] = $request->get_route();
		};
		add_action( 'woocommerce_store_api_before_load_cart_session', $callback, 10, 1 );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/batch' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'requests' => array(
					array(
						'method' => 'GET',
						'path'   => '/wc/store/v1/cart',
					),
					array(
						'method' => 'GET',
						'path'   => '/wc/store/v1/cart',
					),
				),
			)
		);

		rest_get_server()->dispatch( $request );

		remove_action( 'woocommerce_store_api_before_load_cart_session', $callback, 10 );
		$this->assertSame( array( '/wc/store/v1/cart', '/wc/store/v1/cart' ), $received );
	}
}
