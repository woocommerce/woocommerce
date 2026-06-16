<?php
/**
 * POS custom-fee integration tests.
 *
 * Exercises cart/add-fee end-to-end against the real REST server, the real
 * Store API cart pipeline, and the session-backed fee store.
 * The persistence test is the important one: it proves a fee added in one
 * request survives into a later request via the session, which is the whole
 * reason the fee store + woocommerce_cart_calculate_fees re-apply exist.
 *
 * @package Automattic\WooCommerce\Tests\Internal\POS\StoreApi\Routes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CartPersistencePolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CustomFeesPolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\Routes\Controller;
use Automattic\WooCommerce\StoreApi\RoutesController;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use WC_Product_Simple;

/**
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\Routes\CartAddFee
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CustomFeesPolicy
 * @covers \Automattic\WooCommerce\StoreApi\Utilities\CustomFeesStore
 */
class CartFeesIntegrationTest extends ControllerTestCase {

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
	 * POS fees policy instance, kept so its hook can be removed in tearDown.
	 *
	 * @var CustomFeesPolicy
	 */
	private $fees_policy;

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

		( new CartPersistencePolicy() )->register();

		// Registers the woocommerce_cart_calculate_fees re-apply that persistence relies on.
		$this->fees_policy = new CustomFeesPolicy();
		$this->fees_policy->register();

		wc_get_container()->get( Controller::class )->register_routes();
		wc_get_container()->get( RoutesController::class )->register_all_routes();

		$this->product = ( new FixtureData() )->get_simple_product(
			array(
				'name'          => 'POS Fee Test Product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 10,
			)
		);
	}

	/**
	 * Tear down.
	 */
	protected function tearDown(): void {
		remove_action( 'woocommerce_cart_calculate_fees', array( $this->fees_policy, 'apply_custom_fees' ) );
		if ( WC()->session ) {
			WC()->session->set( 'store_api_custom_fees', null );
		}
		remove_all_filters( 'woocommerce_persistent_cart_enabled' );
		Context::set_test_override( null );
		wp_set_current_user( $this->original_user_id );
		wp_delete_user( $this->admin_id );
		parent::tearDown();
	}

	/**
	 * @testdox The POS cart/add-fee route is present in the REST server's index.
	 */
	public function test_routes_are_registered(): void {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/' . Controller::REST_NAMESPACE . '/cart/add-fee', $routes );
	}

	/**
	 * @testdox Anonymous request to cart/add-fee is forbidden.
	 */
	public function test_anonymous_request_is_forbidden(): void {
		wp_set_current_user( 0 );

		$response = $this->add_fee( 'Gift wrap', 5 );

		$this->assertSame( 401, $response->get_status() );
	}

	/**
	 * @testdox Admin can add a custom fee and it appears in the cart response.
	 */
	public function test_admin_can_add_fee(): void {
		wp_set_current_user( $this->admin_id );

		$cart_token = $this->build_cart_with_one_item();
		wp_set_current_user( $this->admin_id );

		$response = $this->add_fee( 'Gift wrap', 5, array( 'cart_token' => $cart_token ) );

		$this->assertSame( 200, $response->get_status(), 'add-fee failed: ' . wp_json_encode( $response->get_data() ) );

		$fees = $response->get_data()['fees'] ?? array();
		$this->assertCount( 1, $fees );
		$this->assertSame( 'Gift wrap', $fees[0]['name'] );
	}

	/**
	 * @testdox A custom fee persists across requests via the session.
	 */
	public function test_fee_persists_across_requests(): void {
		wp_set_current_user( $this->admin_id );

		$cart_token = $this->build_cart_with_one_item();
		wp_set_current_user( $this->admin_id );
		$this->add_fee( 'Gift wrap', 5, array( 'cart_token' => $cart_token ) );

		// A separate later request on the same cart must still see the fee — it
		// only survives if it was persisted to the session and re-applied.
		wp_set_current_user( $this->admin_id );
		$second = $this->add_item( $cart_token );

		$fees = $second->get_data()['fees'] ?? array();
		$this->assertCount( 1, $fees, 'The fee should still be present on a later request.' );
		$this->assertSame( 'Gift wrap', $fees[0]['name'] );
	}

	/**
	 * @testdox Re-adding an identical fee is idempotent (no duplicate).
	 */
	public function test_adding_identical_fee_is_idempotent(): void {
		wp_set_current_user( $this->admin_id );

		$cart_token = $this->build_cart_with_one_item();
		wp_set_current_user( $this->admin_id );
		$this->add_fee( 'Gift wrap', 5, array( 'cart_token' => $cart_token ) );
		wp_set_current_user( $this->admin_id );
		$response = $this->add_fee( 'Gift wrap', 5, array( 'cart_token' => $cart_token ) );

		$this->assertCount( 1, $response->get_data()['fees'] ?? array(), 'An identical fee must not be duplicated.' );
	}

	/**
	 * @testdox A non-positive fee amount is rejected with 400.
	 * @dataProvider non_positive_amounts
	 *
	 * @param float $amount Invalid amount.
	 */
	public function test_non_positive_amount_is_rejected( float $amount ): void {
		wp_set_current_user( $this->admin_id );

		$cart_token = $this->build_cart_with_one_item();
		wp_set_current_user( $this->admin_id );
		$response = $this->add_fee( 'Bad fee', $amount, array( 'cart_token' => $cart_token ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_cart_fee_invalid_amount', $response->get_data()['code'] ?? null );
	}

	/**
	 * Non-positive amounts that must be rejected (no negative or zero fees).
	 *
	 * @return array<string, array<float>>
	 */
	public function non_positive_amounts(): array {
		return array(
			'negative' => array( -5.0 ),
			'zero'     => array( 0.0 ),
		);
	}

	/**
	 * Add one item to a fresh POS cart and return the issued Cart-Token.
	 *
	 * @return string
	 */
	private function build_cart_with_one_item(): string {
		$response = $this->add_item();

		foreach ( $response->get_headers() as $key => $value ) {
			if ( strtolower( $key ) === 'cart-token' ) {
				return (string) $value;
			}
		}
		return '';
	}

	/**
	 * Dispatch POST /cart/add-item, optionally scoped to an existing cart token.
	 *
	 * @param string $cart_token Optional cart token to scope the request to.
	 * @return \WP_REST_Response
	 */
	private function add_item( string $cart_token = '' ): \WP_REST_Response {
		$request = new \WP_REST_Request( 'POST', '/' . Controller::REST_NAMESPACE . '/cart/add-item' );
		$params  = array(
			'id'       => $this->product->get_id(),
			'quantity' => 1,
		);
		if ( '' !== $cart_token ) {
			$params['cart_token'] = $cart_token;
		}
		$request->set_body_params( $params );
		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Dispatch POST /cart/add-fee.
	 *
	 * @param string $name   Fee name.
	 * @param float  $amount Fee amount.
	 * @param array  $extra  Extra request params (e.g. cart_token).
	 * @return \WP_REST_Response
	 */
	private function add_fee( string $name, float $amount, array $extra = array() ): \WP_REST_Response {
		$request = new \WP_REST_Request( 'POST', '/' . Controller::REST_NAMESPACE . '/cart/add-fee' );
		$request->set_body_params(
			array_merge(
				array(
					'name'   => $name,
					'amount' => $amount,
				),
				$extra
			)
		);
		return rest_get_server()->dispatch( $request );
	}
}
