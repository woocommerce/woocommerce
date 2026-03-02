<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Product attribute terms controller tests for V1 REST API.
 */
class WC_REST_Product_Attribute_Terms_V1_Controller_Tests extends WC_REST_Unit_Test_Case {
	/**
	 * @var int Admin user id.
	 */
	private $admin_id;

	/**
	 * Test setup.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
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
}
