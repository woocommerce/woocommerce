<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\StoreApi\Authentication;
use Automattic\WooCommerce\StoreApi\SessionHandler;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use WC_Unit_Test_Case;

/**
 * Tests that Store API authentication recognises routes the way the REST server matches them.
 */
class AuthenticationRouteMatchingTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Authentication
	 */
	private $sut;

	/**
	 * The $_SERVER values from before the test.
	 *
	 * @var array<string, mixed>
	 */
	private $original_server;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut             = new Authentication();
		$this->original_server = $_SERVER;
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			unset( $GLOBALS['wp']->query_vars['rest_route'] );
			$_SERVER = $this->original_server;
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should apply Store API CORS handling to Store API routes in any letter case.
	 * @testWith ["/wc/store/v1/cart", true]
	 *           ["/wc/Store/v1/cart", true]
	 *           ["/WC/STORE/v1/cart", true]
	 *           ["/wc-admin/options", false]
	 *
	 * @param string $route    Requested REST route.
	 * @param bool   $expected Whether Store API CORS handling should be hooked.
	 */
	public function test_init_hooks_cors_handling_for_store_api_routes( string $route, bool $expected ): void {
		$GLOBALS['wp']->query_vars['rest_route'] = $route;

		$this->sut->init();

		$this->assertSame( $expected, is_int( has_filter( 'rest_pre_serve_request', array( $this->sut, 'send_cors_headers' ) ) ) );
	}

	/**
	 * @testdox Should rate limit checkout requests in any letter case when checkout rate limiting is enabled.
	 * @testWith ["/wc/store/v1/checkout"]
	 *           ["/wc/Store/v1/checkout"]
	 *           ["/wc/store/v1/Checkout"]
	 *
	 * @param string $route Requested REST route.
	 */
	public function test_opt_in_checkout_endpoint_ignores_letter_case( string $route ): void {
		wc_get_container()->get( FeaturesController::class )->change_feature_enable( 'rate_limit_checkout', true );
		$GLOBALS['wp']->query_vars['rest_route'] = $route;
		$_SERVER['REQUEST_METHOD']               = 'POST';

		$this->sut->opt_in_checkout_endpoint( null );

		$this->assertNotFalse( has_filter( 'woocommerce_store_api_rate_limit_options' ) );
	}

	/**
	 * @testdox Should use the Cart-Token session when the request path is percent-encoded.
	 */
	public function test_session_handler_uses_cart_token_for_encoded_path(): void {
		$_SERVER['REQUEST_URI']     = '/wp%2Djson/wc/store/v1/cart';
		$_SERVER['HTTP_CART_TOKEN'] = CartTokenUtils::get_cart_token( 't_test_customer' );

		$this->assertSame( SessionHandler::class, $this->sut->maybe_use_store_api_session_handler( \WC_Session_Handler::class ) );
	}
}
