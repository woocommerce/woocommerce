<?php

/**
 * class WC_REST_Product_Custom_Fields_Controller_Tests.
 * Product Custom Fields Controller tests for V3 REST API.
 */
class WC_REST_Product_Custom_Fields_Controller_Tests extends WC_REST_Unit_Test_Case {
	/**
	 * @var WC_Product_Simple[]
	 */
	protected static $products = array();

	/**
	 * Create products for tests.
	 *
	 * @return void
	 */
	public static function wpSetUpBeforeClass() {
		for ( $i = 1; $i <= 4; $i ++ ) {
			self::$products[] = WC_Helper_Product::create_simple_product();
		}

		foreach ( self::$products as $product ) {
			for ( $i = 0; $i < 20; $i++ ) {
				$product->add_meta_data( "Custom field $i", "Value $i", true );
			}
			$product->save();
		}
	}

	/**
	 * Clean up products after tests.
	 *
	 * @return void
	 */
	public static function wpTearDownAfterClass() {
		foreach ( self::$products as $product ) {
			WC_Helper_Product::delete_product( $product->get_id() );
		}
	}

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->endpoint = new WC_REST_Product_Custom_Fields_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
	}

	/**
	 * Test the default custom field names endpoint response.
	 *
	 * @return void
	 */
	public function test_custom_fields_without_params() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products/custom-fields/names' );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertEquals( 10, count( $data ) );
		$this->assertEquals( 'Custom field 0', $data[0] );
		$this->assertEquals( 'Custom field 1', $data[1] );
		$this->assertEquals( 'Custom field 10', $data[2] );
		$this->assertEquals( 'Custom field 11', $data[3] );
		$this->assertEquals( 'Custom field 12', $data[4] );
		$this->assertEquals( 'Custom field 13', $data[5] );
		$this->assertEquals( 'Custom field 14', $data[6] );
		$this->assertEquals( 'Custom field 15', $data[7] );
		$this->assertEquals( 'Custom field 16', $data[8] );
		$this->assertEquals( 'Custom field 17', $data[9] );
	}

	/**
	 * Test the custom field names searching.
	 *
	 * @return void
	 */
	public function test_custom_fields_searching() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products/custom-fields/names' );
		$request->set_query_params(
			array(
				'search' => '0',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertEquals( 2, count( $data ) );
		$this->assertEquals( 'Custom field 0', $data[0] );
		$this->assertEquals( 'Custom field 10', $data[1] );
	}

	/**
	 * Test the custom field names ordering.
	 *
	 * @return void
	 */
	public function test_custom_fields_ordering() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products/custom-fields/names' );
		$request->set_query_params(
			array(
				'order'  => 'desc',
				'search' => 'Custom',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertEquals( 10, count( $data ) );
		$this->assertEquals( 'Custom field 9', $data[0] );
		$this->assertEquals( 'Custom field 8', $data[1] );
		$this->assertEquals( 'Custom field 7', $data[2] );
		$this->assertEquals( 'Custom field 6', $data[3] );
		$this->assertEquals( 'Custom field 5', $data[4] );
		$this->assertEquals( 'Custom field 4', $data[5] );
		$this->assertEquals( 'Custom field 3', $data[6] );
		$this->assertEquals( 'Custom field 2', $data[7] );
		$this->assertEquals( 'Custom field 19', $data[8] );
		$this->assertEquals( 'Custom field 18', $data[9] );
	}

	/**
	 * Test the custom field names pagination.
	 *
	 * @return void
	 */
	public function test_custom_fields_pagination() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products/custom-fields/names' );
		$request->set_query_params(
			array(
				'per_page' => 3,
				'page'     => 2,
				'search'   => 'Custom',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertEquals( 3, count( $data ) );
		$this->assertEquals( 'Custom field 11', $data[0] );
		$this->assertEquals( 'Custom field 12', $data[1] );
		$this->assertEquals( 'Custom field 13', $data[2] );
	}
}
