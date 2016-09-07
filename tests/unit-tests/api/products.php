<?php
/**
 * Tests for Products API.
 *
 * @package WooCommerce\Tests\API
 * @since 2.7.0
 */

class Products_API extends WC_REST_Unit_Test_Case {

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() {
		parent::setUp();
		$this->endpoint = new WC_REST_Products_Controller();
		$this->user = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
	}

    /**
     * Tests to make sure you can filter products post statuses by both
     * the status query arg and WP_Query.
     *
     * @since 2.7.0
     */
    public function test_products_filter_post_status() {
        wp_set_current_user( $this->user );
        for ( $i = 0; $i < 8; $i++ ) {
            $product = WC_Helper_Product::create_simple_product();
            if ( 0 === $i % 2 ) {
                wp_update_post( array(
                    'ID'          => $product->get_id(),
                    'post_status' => 'draft',
                ) );
            }
        }

        // Test filtering with filter[post_status]=publish
        $request = new WP_REST_Request( 'GET', '/wc/v1/products' );
        $request->set_param( 'filter', array( 'post_status' => 'publish' ) );
        $response = $this->server->dispatch( $request );
        $products = $response->get_data();

        $this->assertEquals( 4, count( $products ) );
        foreach ( $products as $product ) {
            $this->assertEquals( 'publish', $product['status'] );
        }

        // Test filtering with filter[post_status]=draft
        $request = new WP_REST_Request( 'GET', '/wc/v1/products' );
        $request->set_param( 'filter', array( 'post_status' => 'draft' ) );
        $response = $this->server->dispatch( $request );
        $products = $response->get_data();

        $this->assertEquals( 4, count( $products ) );
        foreach ( $products as $product ) {
            $this->assertEquals( 'draft', $product['status'] );
        }

        // Test filtering with status=publish
        $request = new WP_REST_Request( 'GET', '/wc/v1/products' );
        $request->set_param( 'status', 'publish' );
        $response = $this->server->dispatch( $request );
        $products = $response->get_data();

        $this->assertEquals( 4, count( $products ) );
        foreach ( $products as $product ) {
            $this->assertEquals( 'publish', $product['status'] );
        }

        // Test filtering with status=draft
        $request = new WP_REST_Request( 'GET', '/wc/v1/products' );
        $request->set_param( 'status', 'draft' );
        $response = $this->server->dispatch( $request );
        $products = $response->get_data();

        $this->assertEquals( 4, count( $products ) );
        foreach ( $products as $product ) {
            $this->assertEquals( 'draft', $product['status'] );
        }

        // Test filtering with status=draft and filter[post_status]=publish
        // filter[post_status]=publish should win
        $request = new WP_REST_Request( 'GET', '/wc/v1/products' );
        $request->set_param( 'status', 'draft' );
        $request->set_param( 'filter', array( 'post_status' => 'publish' ) );
        $response = $this->server->dispatch( $request );
        $products = $response->get_data();

        $this->assertEquals( 4, count( $products ) );
        foreach ( $products as $product ) {
            $this->assertEquals( 'publish', $product['status'] );
        }

        // Test filtering with no filters - which should return 'any' (all 8)
        $request = new WP_REST_Request( 'GET', '/wc/v1/products' );
        $response = $this->server->dispatch( $request );
        $products = $response->get_data();

        $this->assertEquals( 8, count( $products ) );
    }

}
