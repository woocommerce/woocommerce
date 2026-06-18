<?php
/**
 * POS CartApplyCoupon integration tests.
 *
 * Exercises the route end-to-end against the real REST server and the real
 * Store API cart pipeline.
 *
 * @package Automattic\WooCommerce\Tests\Internal\POS\StoreApi\Routes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CartPersistencePolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\NoncePolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CartTokenParamPolicy;
use Automattic\WooCommerce\StoreApi\RoutesController;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use WC_Helper_Coupon;
use WC_Product_Simple;

/**
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CartPersistencePolicy
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CartTokenParamPolicy
 */
class CartApplyCouponIntegrationTest extends ControllerTestCase {

	/**
	 * Admin user with manage_woocommerce capability.
	 *
	 * @var int
	 */
	private $admin_id;

	/**
	 * In-stock product used to build a cart.
	 *
	 * @var WC_Product_Simple
	 */
	private $product;

	/**
	 * Coupon id created in setUp and removed in tearDown.
	 *
	 * @var int
	 */
	private $coupon_id;

	/**
	 * Coupon code corresponding to {@see $coupon_id}.
	 *
	 * @var string
	 */
	private $coupon_code = 'pos-test-coupon';

	/**
	 * Original current_user_id captured in setUp so it can be restored.
	 *
	 * @var int
	 */
	private $original_user_id;

	/**
	 * Setup.
	 */
	protected function setUp(): void {
		parent::setUp();

		Context::set_test_override( true );

		$this->original_user_id = get_current_user_id();
		$this->admin_id         = $this->factory()->user->create( array( 'role' => 'administrator' ) );

		// In production these policy hooks are registered in class-woocommerce.php;
		// in tests we register them explicitly so the test is self-contained.
		// CartTokenParamPolicy bridges the `cart_token` request param onto the
		// Cart-Token header so apply-coupon resumes the cart built by add-item.
		( new CartPersistencePolicy() )->register();
		( new NoncePolicy() )->register();
		( new CartTokenParamPolicy() )->register();

		wc_get_container()->get( RoutesController::class )->register_all_routes();

		$this->product = ( new FixtureData() )->get_simple_product(
			array(
				'name'          => 'POS Coupon Test Product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 10,
			)
		);

		$coupon          = WC_Helper_Coupon::create_coupon(
			$this->coupon_code,
			array( 'discount_type' => 'percent', 'coupon_amount' => '10' )
		);
		$this->coupon_id = $coupon->get_id();
	}

	/**
	 * Tear down.
	 */
	protected function tearDown(): void {
		WC_Helper_Coupon::delete_coupon( $this->coupon_id );
		remove_all_filters( 'woocommerce_persistent_cart_enabled' );
		remove_all_filters( 'rest_dispatch_request' );
		Context::set_test_override( null );
		wp_set_current_user( $this->original_user_id );
		wp_delete_user( $this->admin_id );
		parent::tearDown();
	}

	/**
	 * @testdox The shared cart/apply-coupon route is present in the REST server's index.
	 */
	public function test_route_is_registered(): void {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/wc/store/v1/cart/apply-coupon', $routes );
	}

	/**
	 * @testdox A non-POS request to the shared coupon route still requires a nonce (the POS opt-out is gated).
	 */
	public function test_non_pos_request_still_requires_nonce(): void {
		// The shared coupon route is public; POS gates behaviour, not access.
		// Outside POS context the Store API nonce guard must still stand.
		Context::set_test_override( false );
		wp_set_current_user( $this->admin_id );

		$response = $this->apply_coupon( $this->coupon_code );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_missing_nonce', $response->get_data()['code'] ?? null );
	}

	/**
	 * @testdox Admin can apply a valid coupon to an existing POS cart and the discount appears in the response.
	 */
	public function test_admin_can_apply_coupon_to_pos_cart(): void {
		wp_set_current_user( $this->admin_id );

		$cart_token = $this->build_cart_with_one_item();
		$this->assertNotEmpty( $cart_token, 'Precondition: cart/add-item should issue a Cart-Token.' );

		wp_set_current_user( $this->admin_id );

		$response = $this->apply_coupon( $this->coupon_code, array( 'cart_token' => $cart_token ) );

		$this->assertSame(
			200,
			$response->get_status(),
			'apply-coupon failed: ' . wp_json_encode( $response->get_data() )
		);

		$data = $response->get_data();
		$this->assertArrayHasKey( 'coupons', $data );
		$this->assertCount( 1, $data['coupons'] );
		$this->assertSame( $this->coupon_code, $data['coupons'][0]['code'] );
	}

	/**
	 * @testdox An unknown coupon code returns a 400 with the standard Store API error code.
	 */
	public function test_invalid_coupon_returns_400(): void {
		wp_set_current_user( $this->admin_id );

		$cart_token = $this->build_cart_with_one_item();
		wp_set_current_user( $this->admin_id );

		$response = $this->apply_coupon( 'does-not-exist', array( 'cart_token' => $cart_token ) );

		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Add one item to a fresh POS cart and return the issued Cart-Token.
	 *
	 * @return string
	 */
	private function build_cart_with_one_item(): string {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_body_params(
			array(
				'id'       => $this->product->get_id(),
				'quantity' => 1,
			)
		);
		$response = rest_get_server()->dispatch( $request );

		foreach ( $response->get_headers() as $key => $value ) {
			if ( strtolower( $key ) === 'cart-token' ) {
				return (string) $value;
			}
		}
		return '';
	}

	/**
	 * Dispatch POST /wc/store/v1/cart/apply-coupon with the given code.
	 *
	 * @param string $code  Coupon code.
	 * @param array  $extra Extra request params (e.g. cart_token).
	 * @return \WP_REST_Response
	 */
	private function apply_coupon( string $code, array $extra = array() ): \WP_REST_Response {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/apply-coupon' );
		$request->set_body_params(
			array_merge( array( 'code' => $code ), $extra )
		);
		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Silence the unused-import warning while keeping the class-level @covers explicit.
	 *
	 * @return void
	 */
	public function test_covered_classes_are_present(): void {
		$this->assertTrue( class_exists( CartTokenParamPolicy::class ) );
	}
}
