<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\Helpers\ValidateSchema;

/**
 * Product Attributes Controller Tests.
 */
class ProductAttributeTerms extends ControllerTestCase {

	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();
		global $wpdb;

		$fixtures = new FixtureData();

		$this->attributes = array(
			$fixtures->get_product_attribute( 'color', array( 'red', 'green', 'blue' ) ),
			$fixtures->get_product_attribute( 'size', array( 'small', 'medium', 'large' ) ),
		);

		$wpdb->update(
			$wpdb->prefix . 'woocommerce_attribute_taxonomies',
			array( 'attribute_type' => 'wc-visual' ),
			array( 'attribute_id' => $this->attributes[0]['attribute_id'] ),
			array( '%s' ),
			array( '%d' )
		);

		delete_transient( 'wc_attribute_taxonomies' );
		\WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
	}

	/**
	 * Cleanup test product data. Called after every test.
	 */
	protected function tearDown(): void {
		global $wpdb;

		if ( isset( $this->attributes[0]['attribute_id'] ) ) {
			$wpdb->update(
				$wpdb->prefix . 'woocommerce_attribute_taxonomies',
				array( 'attribute_type' => 'select' ),
				array( 'attribute_id' => $this->attributes[0]['attribute_id'] ),
				array( '%s' ),
				array( '%d' )
			);

			$term = get_term_by( 'name', 'red', $this->attributes[0]['attribute_taxonomy'] );
			delete_term_meta( $term->term_id, 'color' );

			delete_transient( 'wc_attribute_taxonomies' );
			\WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
		}

		parent::tearDown();
	}

	/**
	 * Test getting items.
	 */
	public function test_get_items() {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/attributes/' . $this->attributes[0]['attribute_id'] . '/terms' );
		$request->set_param( 'hide_empty', false );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 3, count( $data ) );
		$this->assertArrayHasKey( 'id', $data[0] );
		$this->assertArrayHasKey( 'name', $data[0] );
		$this->assertArrayHasKey( 'slug', $data[0] );
		$this->assertArrayHasKey( 'description', $data[0] );
		$this->assertArrayHasKey( 'count', $data[0] );
		$this->assertArrayNotHasKey( '__experimentalVisual', $data[0] );
	}

	/**
	 * Test conversion of product to rest response.
	 */
	public function test_prepare_item() {
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'product-attribute-terms' );
		$response   = $controller->prepare_item_for_response( get_term_by( 'name', 'small', 'pa_size' ), new \WP_REST_Request() );
		$data       = $response->get_data();

		$this->assertArrayHasKey( 'id', $data );
		$this->assertEquals( 'small', $data['name'] );
		$this->assertEquals( 'small-slug', $data['slug'] );
		$this->assertEquals( 'Description of small', $data['description'] );
		$this->assertEquals( 0, $data['count'] );
		$this->assertArrayNotHasKey( '__experimentalVisual', $data );
	}

	/**
	 * Test collection params getter.
	 */
	public function test_get_collection_params() {
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'product-attribute-terms' );
		$params     = $controller->get_collection_params();

		$this->assertArrayHasKey( 'order', $params );
		$this->assertArrayHasKey( 'orderby', $params );
		$this->assertArrayHasKey( 'hide_empty', $params );
		$this->assertArrayHasKey( '__experimental_visual', $params );
	}

	/**
	 * Test schema matches responses.
	 */
	public function test_get_item_schema() {
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'product-attribute-terms' );
		$schema     = $controller->get_item_schema();
		$request    = new \WP_REST_Request();
		$request->set_param( '__experimental_visual', true );
		$response = $controller->prepare_item_for_response( get_term_by( 'name', 'red', 'pa_color' ), $request );
		$data     = $response->get_data();
		$validate = new ValidateSchema( $schema );

		$this->assertArrayHasKey( '__experimentalVisual', $data );
		$this->assertSame( 'none', $data['__experimentalVisual']['type'] );
		$this->assertSame( '', $data['__experimentalVisual']['value'] );

		$data['__experimentalVisual'] = (object) $data['__experimentalVisual'];

		$diff = $validate->get_diff_from_object( $data );
		$this->assertEmpty( $diff, print_r( $diff, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
	}

	/**
	 * Test visual attribute terms include experimental visual data.
	 */
	public function test_prepare_item_includes_visual_data_for_wc_visual_attributes() {
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'product-attribute-terms' );
		$schema     = $controller->get_item_schema();

		$term = get_term_by( 'name', 'red', 'pa_color' );
		update_term_meta( $term->term_id, 'color', '#00ff00' );

		$request = new \WP_REST_Request();
		$request->set_param( '__experimental_visual', true );

		$response = $controller->prepare_item_for_response( $term, $request );
		$data     = $response->get_data();

		$validate = new ValidateSchema( $schema );
		$this->assertArrayHasKey( '__experimentalVisual', $data );
		$this->assertSame( 'color', $data['__experimentalVisual']['type'] );
		$this->assertSame( '#00ff00', $data['__experimentalVisual']['value'] );

		$data['__experimentalVisual'] = (object) $data['__experimentalVisual'];

		$diff = $validate->get_diff_from_object( $data );
		$this->assertEmpty( $diff, print_r( $diff, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
	}
}
