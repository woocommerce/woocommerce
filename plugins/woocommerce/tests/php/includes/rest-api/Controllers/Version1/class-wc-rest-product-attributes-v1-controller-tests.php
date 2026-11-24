<?php

/**
 * class WC_REST_Product_Attributes_V1_Controller_Tests.
 * Product Attributes Controller tests for V1 REST API.
 */
class WC_REST_Product_Attributes_V1_Controller_Tests extends WC_REST_Unit_Test_Case {
	/**
	 * @var int Admin user id.
	 */
	private $admin_id;

	/**
	 * Runs before any test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );

		// phpcs:disable Generic.CodeAnalysis, Squiz.Commenting
		$this->sut = new class() extends WC_REST_Product_Attributes_V1_Controller {
			public function get_taxonomy( $request ) {
				return parent::get_taxonomy( $request );
			}
		};
		// phpcs:enable Generic.CodeAnalysis, Squiz.Commenting
	}

	/**
	 * testdox 'get_taxonomy' returns the proper values when called for different requests.
	 */
	public function test_get_taxonomy_returns_the_proper_values_for_different_requests() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_attribute_taxonomy_name_by_id' => function( $attribute_id ) {
					return 'taxonomy_' . $attribute_id;
				},
			)
		);

		$request = array( 'id' => 1 );
		$value1  = $this->sut->get_taxonomy( $request );

		$request = array( 'id' => 2 );
		$value2  = $this->sut->get_taxonomy( $request );

		$this->assertEquals( 'taxonomy_1', $value1 );
		$this->assertEquals( 'taxonomy_2', $value2 );
	}

	/**
	 * @testdox Product attributes item schema contains expected properties.
	 */
	public function test_get_item_schema() {
		wp_set_current_user( $this->admin_id );

		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v1/products/attributes' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 200, $response->get_status() );

		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'slug', $properties );
		$this->assertArrayHasKey( 'type', $properties );
		$this->assertArrayHasKey( 'order_by', $properties );
		$this->assertArrayHasKey( 'has_archives', $properties );
	}

	/**
	 * @testdox Creating a product attribute with an empty slug succeeds.
	 */
	public function test_create_with_empty_slug() {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/wc/v1/products/attributes' );
		$request->set_body_params(
			array(
				'name' => 'Test attribute',
				'slug' => '',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
	}

	/**
	 * @testdox Reading a single attribute whose stored label contains markup returns a plain-text name.
	 */
	public function test_get_item_normalizes_stored_label_with_markup() {
		wp_set_current_user( $this->admin_id );

		$attribute_id = $this->insert_raw_attribute_row( 'Colour<b>bold</b>', 'restrawcolour' );

		try {
			$request  = new WP_REST_Request( 'GET', '/wc/v1/products/attributes/' . $attribute_id );
			$response = $this->server->dispatch( $request );

			$this->assertEquals( 200, $response->get_status() );
			$this->assertSame( 'Colourbold', $response->get_data()['name'] );
		} finally {
			wc_delete_attribute( $attribute_id );
		}
	}

	/**
	 * @testdox Updating an attribute without a slug keeps the stored slug even when the normalized name would slugify differently.
	 */
	public function test_update_item_without_slug_keeps_existing_slug() {
		global $wpdb;

		wp_set_current_user( $this->admin_id );

		$attribute_id = $this->insert_raw_attribute_row( 'Width <5cm', 'width' );

		try {
			$request = new WP_REST_Request( 'PUT', '/wc/v1/products/attributes/' . $attribute_id );
			$request->set_body_params( array( 'order_by' => 'name' ) );
			$response = $this->server->dispatch( $request );

			$this->assertEquals( 200, $response->get_status() );
			$this->assertSame( 'pa_width', $response->get_data()['slug'] );
			$this->assertSame( 'name', $response->get_data()['order_by'] );
			$this->assertSame( 'width', $wpdb->get_var( $wpdb->prepare( "SELECT attribute_name FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_id = %d", $attribute_id ) ) );
		} finally {
			wc_delete_attribute( $attribute_id );
		}
	}

	/**
	 * Insert an attribute row directly, bypassing wc_create_attribute(), and reset the attribute caches.
	 *
	 * @param string $label Raw label to store.
	 * @param string $slug  Attribute slug (without the `pa_` prefix).
	 * @return int Attribute ID.
	 */
	private function insert_raw_attribute_row( string $label, string $slug ): int {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_attribute_taxonomies',
			array(
				'attribute_label'   => $label,
				'attribute_name'    => $slug,
				'attribute_type'    => 'select',
				'attribute_orderby' => 'menu_order',
				'attribute_public'  => 0,
			)
		);
		delete_transient( 'wc_attribute_taxonomies' );
		WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );

		return (int) $wpdb->insert_id;
	}
}
