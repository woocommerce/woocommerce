<?php
/**
 * Product attribute terms controller tests for V1 REST API.
 *
 * @package WooCommerce\Tests\RestApi
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Product attribute terms controller tests for V1 REST API.
 */
class WC_REST_Product_Attribute_Terms_V1_Controller_Tests extends WC_REST_Unit_Test_Case {
	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	private $admin_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Test that the item schema contains expected properties.
	 *
	 * @testdox Product attribute terms item schema contains expected properties.
	 */
	public function test_get_item_schema() {
		wp_set_current_user( $this->admin_id );

		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v1/products/attributes/1/terms' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 200, $response->get_status() );

		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'slug', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'menu_order', $properties );
		$this->assertArrayHasKey( 'count', $properties );
	}

	/**
	 * Test that creating a term with an empty slug succeeds.
	 *
	 * @testdox Creating a product attribute term with an empty slug succeeds.
	 */
	public function test_create_with_empty_slug() {
		wp_set_current_user( $this->admin_id );

		$attribute = FixtureData::get_product_attribute( 'color', array( 'red', 'blue' ) );

		$request = new WP_REST_Request( 'POST', '/wc/v1/products/attributes/' . $attribute['attribute_id'] . '/terms' );
		$request->set_body_params(
			array(
				'name' => 'Test term',
				'slug' => '',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
	}

	/**
	 * Test that creating a term stores menu_order under the correct meta key.
	 *
	 * @testdox Creating a term via REST API stores menu_order under the 'order' meta key.
	 */
	public function test_menu_order_writes_to_correct_meta_key() {
		wp_set_current_user( $this->admin_id );

		$attribute_id = wc_create_attribute(
			array(
				'name'     => 'Test Size',
				'slug'     => 'test-size',
				'order_by' => 'menu_order',
			)
		);
		$taxonomy     = wc_attribute_taxonomy_name( 'test-size' );
		register_taxonomy( $taxonomy, array( 'product' ) );

		$request = new WP_REST_Request( 'POST', '/wc/v1/products/attributes/' . $attribute_id . '/terms' );
		$request->set_body_params(
			array(
				'name'       => 'Large',
				'slug'       => 'large',
				'menu_order' => 5,
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( 5, $data['menu_order'] );
		$this->assertEquals( 5, (int) get_term_meta( $data['id'], 'order', true ) );
		$this->assertEmpty( get_term_meta( $data['id'], 'order_' . $taxonomy, true ), 'Old meta key should not be written' );

		wc_delete_attribute( $attribute_id );
	}

	/**
	 * Test that updating menu_order updates the correct meta key.
	 *
	 * @testdox Updating menu_order via REST API updates the 'order' meta key.
	 */
	public function test_menu_order_update_writes_to_correct_meta_key() {
		wp_set_current_user( $this->admin_id );

		$attribute_id = wc_create_attribute(
			array(
				'name'     => 'Test Weight',
				'slug'     => 'test-weight',
				'order_by' => 'menu_order',
			)
		);
		$taxonomy     = wc_attribute_taxonomy_name( 'test-weight' );
		register_taxonomy( $taxonomy, array( 'product' ) );

		$term = wp_insert_term( 'Medium', $taxonomy, array( 'slug' => 'medium' ) );
		update_term_meta( $term['term_id'], 'order', 0 );

		$request = new WP_REST_Request( 'PUT', '/wc/v1/products/attributes/' . $attribute_id . '/terms/' . $term['term_id'] );
		$request->set_body_params( array( 'menu_order' => 3 ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 3, $data['menu_order'] );
		$this->assertEquals( 3, (int) get_term_meta( $term['term_id'], 'order', true ) );

		wc_delete_attribute( $attribute_id );
	}

	/**
	 * Test that reading menu_order returns the value from the correct meta key.
	 *
	 * @testdox Reading menu_order via GET returns value from the 'order' meta key.
	 */
	public function test_menu_order_read_uses_correct_meta_key() {
		wp_set_current_user( $this->admin_id );

		$attribute_id = wc_create_attribute(
			array(
				'name'     => 'Test Material',
				'slug'     => 'test-material',
				'order_by' => 'menu_order',
			)
		);
		$taxonomy     = wc_attribute_taxonomy_name( 'test-material' );
		register_taxonomy( $taxonomy, array( 'product' ) );

		$term = wp_insert_term( 'Cotton', $taxonomy, array( 'slug' => 'cotton' ) );
		update_term_meta( $term['term_id'], 'order', 7 );

		$request  = new WP_REST_Request( 'GET', '/wc/v1/products/attributes/' . $attribute_id . '/terms/' . $term['term_id'] );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 7, $data['menu_order'] );

		wc_delete_attribute( $attribute_id );
	}

	/**
	 * Test that updating a term without menu_order preserves existing order.
	 *
	 * @testdox Updating a term without menu_order does not overwrite existing order.
	 */
	public function test_update_without_menu_order_preserves_existing_order() {
		wp_set_current_user( $this->admin_id );

		$attribute_id = wc_create_attribute(
			array(
				'name'     => 'Test Style',
				'slug'     => 'test-style',
				'order_by' => 'menu_order',
			)
		);
		$taxonomy     = wc_attribute_taxonomy_name( 'test-style' );
		register_taxonomy( $taxonomy, array( 'product' ) );

		$term = wp_insert_term( 'Casual', $taxonomy, array( 'slug' => 'casual' ) );
		update_term_meta( $term['term_id'], 'order', 5 );

		$request = new WP_REST_Request( 'PUT', '/wc/v1/products/attributes/' . $attribute_id . '/terms/' . $term['term_id'] );
		$request->set_body_params( array( 'description' => 'Updated description' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 5, (int) get_term_meta( $term['term_id'], 'order', true ), 'Order should be preserved when menu_order is not in the request' );

		wc_delete_attribute( $attribute_id );
	}
}
