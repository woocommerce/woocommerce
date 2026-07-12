<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\Helpers\ValidateSchema;
use WC_Logger;
use WC_Logger_Interface;
use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * Cart Controller Tests.
 */
class CartItems extends ControllerTestCase {
	/**
	 * Product IDs shared by the class.
	 *
	 * @var int[]
	 */
	private static $product_ids = array();

	/**
	 * Variation ID shared by the class.
	 *
	 * @var int
	 */
	private static $variation_id;

	/**
	 * All product IDs owned by the class.
	 *
	 * @var int[]
	 */
	private static $owned_product_ids = array();

	/**
	 * Attribute taxonomy IDs owned by the class.
	 *
	 * @var int[]
	 */
	private static $attribute_ids = array();

	/**
	 * Whether an attribute deletion rewrite flush was already scheduled.
	 *
	 * @var bool
	 */
	private static $had_scheduled_rewrite_flush;

	/**
	 * Create the immutable cart catalog shared by all test methods.
	 */
	public static function wpSetUpBeforeClass(): void {
		$existing_attribute_ids            = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_id' );
		self::$had_scheduled_rewrite_flush = false !== wp_next_scheduled( 'woocommerce_flush_rewrite_rules' );

		$fixtures = self::with_direct_product_attribute_lookup_updates(
			static function () {
				$fixture_data = new FixtureData();
				$color        = $fixture_data->get_product_attribute( 'color', array( 'red', 'green', 'blue' ) );
				$size         = $fixture_data->get_product_attribute( 'size', array( 'small', 'medium', 'large' ) );

				foreach ( array( $color, $size ) as $attribute ) {
					foreach ( $attribute['term_ids'] as $term_id ) {
						$term = get_term( $term_id, $attribute['attribute_taxonomy'] );
						wp_update_term( $term_id, $attribute['attribute_taxonomy'], array( 'slug' => sanitize_title( $term->name ) ) );
					}
				}

				$simple    = $fixture_data->get_simple_product(
					array(
						'name'          => 'Test Product 1',
						'stock_status'  => ProductStockStatus::IN_STOCK,
						'regular_price' => 10,
						'weight'        => 10,
					)
				);
				$variable  = $fixture_data->get_variable_product(
					array(
						'name'          => 'Test Product 2',
						'stock_status'  => ProductStockStatus::IN_STOCK,
						'regular_price' => 10,
						'weight'        => 10,
					),
					array(
						$color,
						$size,
					)
				);
				$variation = $fixture_data->get_variation_product(
					$variable->get_id(),
					array(
						'pa_color' => 'red',
						'pa_size'  => 'small',
					)
				);

				return array( $simple, $variable, $variation );
			}
		);

		self::$product_ids       = array( $fixtures[0]->get_id(), $fixtures[1]->get_id() );
		self::$variation_id      = $fixtures[2]->get_id();
		self::$owned_product_ids = array( $fixtures[0]->get_id(), $fixtures[2]->get_id(), $fixtures[1]->get_id() );
		self::$attribute_ids     = array_values( array_diff( wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_id' ), $existing_attribute_ids ) );
	}

	/**
	 * Delete the class-owned catalog and global attributes.
	 */
	public static function wpTearDownAfterClass(): void {
		global $wc_product_attributes;

		self::delete_class_fixture_products( self::$owned_product_ids );

		foreach ( self::$attribute_ids as $attribute_id ) {
			$attribute = wc_get_attribute( $attribute_id );
			$taxonomy  = $attribute ? $attribute->slug : '';

			wc_delete_attribute( $attribute_id );

			if ( $taxonomy && taxonomy_exists( $taxonomy ) ) {
				unregister_taxonomy( $taxonomy );
			}
			unset( $wc_product_attributes[ $taxonomy ] );
		}

		if ( ! self::$had_scheduled_rewrite_flush ) {
			wp_clear_scheduled_hook( 'woocommerce_flush_rewrite_rules' );
		}
	}

	/**
	 * The mock logger.
	 *
	 * @var WC_Logger_Interface|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_logger;

	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->products = array_map( 'wc_get_product', self::$product_ids );
		$variation      = wc_get_product( self::$variation_id );

		wc_empty_cart();
		$this->keys   = array();
		$this->keys[] = wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );
		$this->keys[] = wc()->cart->add_to_cart(
			$this->products[1]->get_id(),
			1,
			$variation->get_id(),
			array(
				'attribute_pa_color' => 'red',
				'attribute_pa_size'  => 'small',
			)
		);

		// Have a mock logger used by the suggestions rule evaluator.
		$this->mock_logger = $this->getMockBuilder( 'WC_Logger_Interface' )->getMock();
		add_filter( 'woocommerce_logging_class', array( $this, 'override_wc_logger' ) );
	}

	/**
	 * Tear down.
	 */
	protected function tearDown(): void {
		parent::tearDown();
		remove_filter( 'woocommerce_logging_class', array( $this, 'override_wc_logger' ) );
	}

	/**
	 * Test getting cart.
	 */
	public function test_get_items() {
		$this->assertAPIResponse(
			'/wc/store/v1/cart/items',
			200,
			array(
				0 => array(
					'key'       => $this->keys[0],
					'id'        => $this->products[0]->get_id(),
					'type'      => $this->products[0]->get_type(),
					'name'      => $this->products[0]->get_name(),
					'sku'       => $this->products[0]->get_sku(),
					'permalink' => $this->products[0]->get_permalink(),
					'quantity'  => 2,
					'totals'    => array(
						'line_subtotal' => '2000',
						'line_total'    => '2000',
					),
				),
				1 => array(
					'key'      => $this->keys[1],
					'quantity' => 1,
					'totals'   => array(
						'line_subtotal' => '1000',
						'line_total'    => '1000',
					),
				),
			)
		);
	}

	/**
	 * Test getting cart item by key.
	 */
	public function test_get_item() {
		$this->assertAPIResponse(
			'/wc/store/v1/cart/items/' . $this->keys[0],
			200,
			array(
				'key'       => $this->keys[0],
				'id'        => $this->products[0]->get_id(),
				'type'      => $this->products[0]->get_type(),
				'name'      => $this->products[0]->get_name(),
				'sku'       => $this->products[0]->get_sku(),
				'permalink' => $this->products[0]->get_permalink(),
				'quantity'  => 2,
				'totals'    => array(
					'line_subtotal' => '2000',
					'line_total'    => '2000',
				),
			)
		);
	}

	/**
	 * Test add to cart.
	 */
	public function test_create_item() {
		wc_empty_cart();

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/items' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id'       => $this->products[0]->get_id(),
				'quantity' => '10',
			)
		);

		$this->assertAPIResponse(
			$request,
			201,
			array(
				'id'       => $this->products[0]->get_id(),
				'quantity' => 10,
			)
		);

		$this->assertAPIResponse(
			$request,
			201,
			array(
				'id'       => $this->products[0]->get_id(),
				'quantity' => 20,
			)
		);
	}

	/**
	 * Test add to cart does not allow invalid items.
	 */
	public function test_invalid_create_item() {
		wc_empty_cart();

		$fixtures        = new FixtureData();
		$invalid_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Invalid Product',
				'regular_price' => '',
			)
		);

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/items' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id'       => $invalid_product->get_id(),
				'quantity' => '10',
			)
		);
		$this->assertAPIResponse(
			$request,
			400
		);
	}

	/**
	 * Test updating an item.
	 */
	public function test_update_item() {
		$request = new \WP_REST_Request( 'PUT', '/wc/store/v1/cart/items/' . $this->keys[0] );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'quantity' => '10',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 10, $data['quantity'] );
	}

	/**
	 * Test delete item.
	 */
	public function test_delete_item() {
		$request = new \WP_REST_Request( 'DELETE', '/wc/store/v1/cart/items/' . $this->keys[0] );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$this->assertAPIResponse(
			$request,
			204,
			array()
		);

		$request = new \WP_REST_Request( 'DELETE', '/wc/store/v1/cart/items/' . $this->keys[0] );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$this->assertAPIResponse(
			$request,
			409
		);
	}

	/**
	 * Test delete all items.
	 */
	public function test_delete_items() {
		$request = new \WP_REST_Request( 'DELETE', '/wc/store/v1/cart/items' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( array(), $data );

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/cart/items' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 0, count( $data ) );
	}

	/**
	 * Test conversion of cart item to rest response.
	 */
	public function test_prepare_item() {
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'cart-items', 'v1' );
		$cart       = wc()->cart->get_cart();
		$response   = $controller->prepare_item_for_response( current( $cart ), new \WP_REST_Request() );
		$data       = $response->get_data();

		$this->assertArrayHasKey( 'key', $data );
		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'type', $data );
		$this->assertArrayHasKey( 'quantity', $data );
		$this->assertArrayHasKey( 'name', $data );
		$this->assertArrayHasKey( 'sku', $data );
		$this->assertArrayHasKey( 'permalink', $data );
		$this->assertArrayHasKey( 'images', $data );
		$this->assertArrayHasKey( 'totals', $data );
		$this->assertArrayHasKey( 'variation', $data );
		$this->assertArrayHasKey( 'item_data', $data );
		$this->assertArrayHasKey( 'low_stock_remaining', $data );
		$this->assertArrayHasKey( 'backorders_allowed', $data );
		$this->assertArrayHasKey( 'show_backorder_badge', $data );
		$this->assertArrayHasKey( 'short_description', $data );
		$this->assertArrayHasKey( 'catalog_visibility', $data );
	}

	/**
	 * Test schema matches responses.
	 *
	 * Tests schema of both products in cart to cover as much schema as possible.
	 */
	public function test_get_item_schema() {
		// Give the simple product an image so the nested image schema is validated too.
		$image_id = $this->add_image_to_product();

		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'cart-items', 'v1' );
		$schema     = $controller->get_item_schema();
		$cart       = wc()->cart->get_cart();
		$validate   = new ValidateSchema( $schema );

		// Simple product.
		$response = $controller->prepare_item_for_response( current( $cart ), new \WP_REST_Request() );
		$this->assertNotEmpty( $response->get_data()['images'], 'The simple product response must include an image so its schema is exercised.' );
		$diff = $validate->get_diff_from_object( $response->get_data() );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$this->assertEmpty( $diff, print_r( $diff, true ) );

		// Variable product.
		$response = $controller->prepare_item_for_response( end( $cart ), new \WP_REST_Request() );
		$diff     = $validate->get_diff_from_object( $response->get_data() );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$this->assertEmpty( $diff, print_r( $diff, true ) );

		wp_delete_attachment( $image_id, true );
	}

	/**
	 * Test filtering cart item images.
	 *
	 * @return void
	 * @throws \Exception When the images are not filtered correctly.
	 */
	public function test_cart_item_image_filtering() {
		$image_id   = $this->add_image_to_product();
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'cart-items', 'v1' );
		$cart       = WC()->cart->get_cart();

		add_filter(
			'woocommerce_store_api_cart_item_images',
			function ( $images ) {
				foreach ( $images as $image ) {
					$image->src       = 'https://example.com/image-1.jpg';
					$image->thumbnail = 'https://example.com/image-1-thumbnail.jpg';
				}

				return $images;
			},
			10,
			1
		);

		$response = $controller->prepare_item_for_response( current( $cart ), new \WP_REST_Request() );
		$image    = $response->get_data()['images'][0];
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$this->assertEquals( $image->src, 'https://example.com/image-1.jpg' );
		$this->assertEquals( $image->thumbnail, 'https://example.com/image-1-thumbnail.jpg' );
		remove_all_filters( 'woocommerce_store_api_cart_item_images' );
		wp_delete_attachment( $image_id, true );
	}

	/**
	 * Test error logging while filtering cart item images.
	 *
	 * @return void
	 * @throws \Exception When the errors are not logged correctly.
	 */
	public function test_cart_item_image_filtering_logging() {
		$image_id   = $this->add_image_to_product();
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'cart-items', 'v1' );
		$cart       = WC()->cart->get_cart();
		$image_id   = $this->products[0]->get_image_id();

		// Capture warnings in the order they are logged so we can assert on them
		// without relying on the deprecated at() matcher.
		$logged_warnings = array();
		$this->mock_logger
			->method( 'warning' )
			->willReturnCallback(
				function ( $message ) use ( &$logged_warnings ) {
					$logged_warnings[] = $message;
				}
			);

		// Ensure warning is logged when image has invalid src.
		add_filter(
			'woocommerce_store_api_cart_item_images',
			function ( $images ) {
				foreach ( $images as $image ) {
					$image->src = 'invalid';
				}
				return $images;
			},
			10,
			1
		);

		$controller->prepare_item_for_response( current( $cart ), new \WP_REST_Request() );
		remove_all_filters( 'woocommerce_store_api_cart_item_images' );

		$this->assertContains(
			sprintf( 'After passing through woocommerce_cart_item_images filter, image with id %s did not have a valid src property.', $image_id ),
			$logged_warnings,
			'Expected a warning to be logged when the filtered image has an invalid src.'
		);

		// Ensure warning is logged when image has invalid thumbnail.
		add_filter(
			'woocommerce_store_api_cart_item_images',
			function ( $images ) {
				foreach ( $images as $image ) {
					$image->thumbnail = 'invalid';
				}
				return $images;
			},
			10,
			1
		);

		$controller->prepare_item_for_response( current( $cart ), new \WP_REST_Request() );
		remove_all_filters( 'woocommerce_store_api_cart_item_images' );

		$this->assertContains(
			sprintf( 'After passing through woocommerce_cart_item_images filter, image with id %s did not have a valid thumbnail property.', $image_id ),
			$logged_warnings,
			'Expected a warning to be logged when the filtered image has an invalid thumbnail.'
		);

		// Ensure original images are returned if filter returns a non-array.
		add_filter(
			'woocommerce_store_api_cart_item_images',
			function () {
				return null;
			},
			10,
			0
		);
		$response       = $controller->prepare_item_for_response( current( $cart ), new \WP_REST_Request() );
		$image          = $response->get_data()['images'][0];
		$expected_image = wp_get_attachment_image_url( $this->products[0]->get_image_id(), 'full' );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$this->assertEquals( $image->src, $expected_image );
		$this->assertEquals( $image->thumbnail, $expected_image );
		wp_delete_attachment( $image_id, true );
	}

	/**
	 * Add an image to the simple product used by image-filter tests.
	 *
	 * @return int Attachment ID.
	 */
	private function add_image_to_product(): int {
		$fixtures = new FixtureData();
		$image_id = $fixtures->create_image_attachment( $this->products[0]->get_id() );
		$this->products[0]->set_image_id( $image_id );
		$this->products[0]->save();
		$cart_item = WC()->cart->get_cart_item( $this->keys[0] );
		$cart_item['data']->set_image_id( $image_id );

		return $image_id;
	}

	/**
	 * Overrides the WC logger.
	 *
	 * @return mixed
	 */
	public function override_wc_logger() {
		return $this->mock_logger;
	}
}
