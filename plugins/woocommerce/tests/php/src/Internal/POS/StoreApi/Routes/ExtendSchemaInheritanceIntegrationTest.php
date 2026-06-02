<?php
/**
 * POS ↔ ExtendSchema inheritance integration test.
 *
 * Answers the load-bearing open question for the spike: do `ExtendSchema`
 * registrations made against the Store API (`wc/store/v1`) automatically apply
 * to responses produced under the separate POS namespace (`wc/pos/v1`)?
 *
 * If they do, the wrapper-delegation / subclassing approach holds: extensions
 * (Gift Cards, Subscriptions, Bookings, …) keep working through POS without any
 * POS-specific re-registration, and the `wc/pos/v1` namespace split is safe.
 *
 * If they did NOT, the whole premise of routing POS through the Store API
 * pipeline would be undermined and the routes would need to live under the
 * `wc/store/v1` namespace instead.
 *
 * Mechanism under test: `ExtendSchema` keys registrations on the schema
 * IDENTIFIER (`cart`, `cart-item`), not on the REST namespace. It is a shared
 * singleton in the Store API container, and POS routes resolve their schemas
 * from that same `SchemaController` (see {@see Controller::register_routes}).
 * So an extension registered via `woocommerce_store_api_register_endpoint_data()`
 * is read by the very same schema instances that serialize POS responses.
 *
 * @package Automattic\WooCommerce\Tests\Internal\POS\StoreApi\Routes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CartPersistencePolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\Routes\Controller;
use Automattic\WooCommerce\StoreApi\RoutesController;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use WC_Product_Simple;

/**
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\Routes\CartAddItem
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\Routes\Controller
 */
class ExtendSchemaInheritanceIntegrationTest extends ControllerTestCase {

	/**
	 * Unique namespace for the probe extension so it can't collide with real
	 * registrations and is trivially identifiable in the response.
	 */
	private const PROBE_NAMESPACE = 'pos-extendschema-probe';

	/**
	 * Marker value the cart-level data callback emits.
	 */
	private const CART_MARKER = 'cart-extension-applied';

	/**
	 * Marker value the cart-item-level data callback emits.
	 */
	private const ITEM_MARKER = 'cart-item-extension-applied';

	/**
	 * Admin user with manage_woocommerce capability.
	 *
	 * @var int
	 */
	private $admin_id;

	/**
	 * In-stock product added to the cart.
	 *
	 * @var WC_Product_Simple
	 */
	private $product;

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

		// rest_get_server()->dispatch() bypasses the URL routing that populates
		// $_SERVER['REQUEST_URI'], so force POS context explicitly.
		Context::set_test_override( true );

		$this->original_user_id = get_current_user_id();
		$this->admin_id         = $this->factory()->user->create( array( 'role' => 'administrator' ) );

		( new CartPersistencePolicy() )->register();

		wc_get_container()->get( Controller::class )->register_routes();
		wc_get_container()->get( RoutesController::class )->register_all_routes();

		$this->product = ( new FixtureData() )->get_simple_product(
			array(
				'name'          => 'POS ExtendSchema Probe Product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 10,
			)
		);

		$this->register_probe_extension();
	}

	/**
	 * Tear down.
	 */
	protected function tearDown(): void {
		$this->reset_extend_schema_registrations();
		remove_all_filters( 'woocommerce_persistent_cart_enabled' );
		Context::set_test_override( null );
		wp_set_current_user( $this->original_user_id );
		wp_delete_user( $this->admin_id );
		parent::tearDown();
	}

	/**
	 * @testdox A cart-level ExtendSchema registration surfaces in a wc/pos/v1 add-item response.
	 */
	public function test_cart_level_extension_applies_to_pos_response(): void {
		wp_set_current_user( $this->admin_id );

		$response = $this->pos_add_item( $this->product->get_id() );
		$this->assertSame( 201, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'extensions', $data, 'POS cart response should expose the extensions container.' );

		$extensions = (array) $data['extensions'];
		$this->assertArrayHasKey(
			self::PROBE_NAMESPACE,
			$extensions,
			'Extension data registered against the wc/store/v1 cart schema should appear under wc/pos/v1.'
		);
		$this->assertSame(
			self::CART_MARKER,
			( (array) $extensions[ self::PROBE_NAMESPACE ] )['marker'] ?? null
		);
	}

	/**
	 * @testdox A cart-item-level ExtendSchema registration surfaces on each item in a wc/pos/v1 response.
	 */
	public function test_cart_item_level_extension_applies_to_pos_response(): void {
		wp_set_current_user( $this->admin_id );

		$response = $this->pos_add_item( $this->product->get_id() );
		$this->assertSame( 201, $response->get_status() );

		$data = $response->get_data();
		$this->assertCount( 1, $data['items'] );

		$item = $data['items'][0];
		$this->assertArrayHasKey( 'extensions', $item, 'Each POS cart item should expose the extensions container.' );

		$item_extensions = (array) $item['extensions'];
		$this->assertArrayHasKey(
			self::PROBE_NAMESPACE,
			$item_extensions,
			'cart-item ExtendSchema data should be inherited by wc/pos/v1 line items.'
		);
		$this->assertSame(
			self::ITEM_MARKER,
			( (array) $item_extensions[ self::PROBE_NAMESPACE ] )['marker'] ?? null
		);
	}

	/**
	 * Baseline: the very same registration surfaces on the canonical Store API
	 * surface too. If this assertion passes but the POS ones above fail, the
	 * registration is sound and the namespace split is what broke inheritance —
	 * making the failure unambiguous.
	 *
	 * @testdox Control: the probe extension also surfaces on the wc/store/v1 cart so the registration itself is proven sound.
	 */
	public function test_control_extension_applies_to_store_api_cart(): void {
		wp_set_current_user( $this->admin_id );

		// Seed the cart through the POS route (no nonce needed), then read it
		// back over the public Store API GET (no nonce needed for reads).
		$this->assertSame( 201, $this->pos_add_item( $this->product->get_id() )->get_status() );

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/cart' ) );
		$this->assertSame( 200, $response->get_status() );

		$extensions = (array) $response->get_data()['extensions'];
		$this->assertArrayHasKey(
			self::PROBE_NAMESPACE,
			$extensions,
			'Sanity: the probe extension must appear on wc/store/v1 for the POS comparison to be meaningful.'
		);
	}

	/**
	 * Register a probe extension against both the cart and cart-item schemas via
	 * the public helper, exactly as a real extension (e.g. Gift Cards) would.
	 */
	private function register_probe_extension(): void {
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CartSchema::IDENTIFIER,
				'namespace'       => self::PROBE_NAMESPACE,
				'data_callback'   => function () {
					return array( 'marker' => self::CART_MARKER );
				},
				'schema_callback' => function () {
					return array(
						'marker' => array(
							'description' => 'POS ExtendSchema probe marker.',
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					);
				},
			)
		);

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CartItemSchema::IDENTIFIER,
				'namespace'       => self::PROBE_NAMESPACE,
				'data_callback'   => function ( $cart_item ) {
					// Marker is item-independent for this probe.
					unset( $cart_item );
					return array( 'marker' => self::ITEM_MARKER );
				},
				'schema_callback' => function () {
					return array(
						'marker' => array(
							'description' => 'POS ExtendSchema probe marker.',
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					);
				},
			)
		);
	}

	/**
	 * The container's ExtendSchema is a shared singleton, so probe registrations
	 * would leak into sibling tests. Reset its private registry in teardown.
	 */
	private function reset_extend_schema_registrations(): void {
		$extend     = StoreApi::container()->get( ExtendSchema::class );
		$reflection = new \ReflectionClass( $extend );
		$property   = $reflection->getProperty( 'extend_data' );
		$property->setAccessible( true );
		$property->setValue( $extend, array() );
	}

	/**
	 * Dispatch POST /wc/pos/v1/cart/add-item.
	 *
	 * @param int $product_id Product ID to add.
	 * @param int $quantity   Quantity to add.
	 * @return \WP_REST_Response
	 */
	private function pos_add_item( int $product_id, int $quantity = 1 ): \WP_REST_Response {
		$request = new \WP_REST_Request( 'POST', '/' . Controller::REST_NAMESPACE . '/cart/add-item' );
		$request->set_body_params(
			array(
				'id'       => $product_id,
				'quantity' => $quantity,
			)
		);

		return rest_get_server()->dispatch( $request );
	}
}
