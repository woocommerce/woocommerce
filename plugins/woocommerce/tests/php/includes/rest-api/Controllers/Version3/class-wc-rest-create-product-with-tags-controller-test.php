<?php
/**
 * This tests when creating a product with tags, it will properly assign
 * the tags when they exist or will create them if they don't.
 *
 * Class WC_REST_Create_Product_With_Tags_Controller_Test.
 */
class WC_REST_Create_Product_With_Tags_Controller_Test extends WC_REST_Unit_Test_Case {
	/**
	 * Tests creating products and assigning existing tags.
	 *
	 */
	public function test_create_product_with_existing_tags() {
		wp_set_current_user( 1 );
		$term = wp_insert_term( 'clothes', 'product_tag' );

		$this->assertNotWPError( $term );

		$json_data = array(
			'name' => 'Product with existing tags',
			'type' => 'simple',
			'tags' => array(
				array(
					'id' => $term['term_id'],
				),
			),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v3/products' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $json_data ) );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );

		$response_data = $response->get_data();

		$ids = wp_list_pluck( $response_data['tags'], 'id' );

		$this->assertContains( $term['term_id'], $ids );
	}

	/**
	 * Tests creating products and assigning new tags.
	 *
	 */
	public function test_create_product_with_new_tags() {
		wp_set_current_user( 1 );

		$json_data = array(
			'name' => 'Product with new tags',
			'type' => 'simple',
			'tags' => array(
				array(
					'name' => 'clothes',
				),
				array(
					'name' => 'shirts',
				),
			),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v3/products' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $json_data ) );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );

		$response_data = $response->get_data();

		$names = wp_list_pluck( $response_data['tags'], 'name' );

		$this->assertContains( 'clothes', $names );
		$this->assertContains( 'shirts', $names );
	}

	/**
	 * Tests creating products and assigning existing and new tags.
	 *
	 */
	public function test_create_product_with_existing_and_new_tags() {
		wp_set_current_user( 1 );
		$term = wp_insert_term( 'clothes', 'product_tag' );

		$this->assertNotWPError( $term );

		$json_data = array(
			'name' => 'Product with existing tags',
			'type' => 'simple',
			'tags' => array(
				array(
					'id' => $term['term_id'],
				),
				array(
					'name' => 'shirts',
				),
			),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v3/products' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $json_data ) );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );

		$response_data = $response->get_data();

		$ids   = wp_list_pluck( $response_data['tags'], 'id' );
		$names = wp_list_pluck( $response_data['tags'], 'name' );

		$this->assertContains( $term['term_id'], $ids );
		$this->assertContains( 'shirts', $names );
	}
}
