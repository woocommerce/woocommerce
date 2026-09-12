<?php

/**
 * class WC_REST_Terms_Controller_Tests.
 * Terms Controller tests for V3 REST API.
 */
class WC_REST_Terms_Controller_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * The system under test for the existing taxonomy lookup test.
	 *
	 * @var WC_REST_Terms_Controller
	 */
	private $sut;

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	private $user;

	/**
	 * Runs before any test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );

		// phpcs:disable Generic.CodeAnalysis, Squiz.Commenting
		$this->sut = new class() extends WC_REST_Terms_Controller {
			public function get_taxonomy( $request ) {
				return parent::get_taxonomy( $request );
			}
		};
		// phpcs:enable Generic.CodeAnalysis, Squiz.Commenting
	}

	/**
	 * @testdox 'get_taxonomy' returns the proper values when called for different requests.
	 */
	public function test_get_taxonomy_returns_the_proper_values_for_different_requests() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_attribute_taxonomy_name_by_id' => function ( $attribute_id ) {
					return 'taxonomy_' . $attribute_id;
				},
			)
		);

		$request = array( 'attribute_id' => 1 );
		$value1  = $this->sut->get_taxonomy( $request );

		$request = array( 'attribute_id' => 2 );
		$value2  = $this->sut->get_taxonomy( $request );

		$this->assertEquals( 'taxonomy_1', $value1 );
		$this->assertEquals( 'taxonomy_2', $value2 );
	}

	/**
	 * Product term REST routes.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function product_term_routes() {
		return array(
			'product tag'            => array( 'product_tag', '/wc/v3/products/tags' ),
			'product shipping class' => array( 'product_shipping_class', '/wc/v3/products/shipping_classes' ),
		);
	}

	/**
	 * @testdox A product term can complete its CRUD lifecycle through the registered V3 route.
	 *
	 * @dataProvider product_term_routes
	 *
	 * @param string $taxonomy Product term taxonomy.
	 * @param string $route Registered V3 route.
	 */
	public function test_product_term_crud_lifecycle( $taxonomy, $route ): void {
		$name        = str_replace( '_', ' ', $taxonomy ) . ' lifecycle';
		$slug        = sanitize_title( $name );
		$description = 'Updated through the registered V3 product term route.';

		$response = $this->do_rest_request(
			$route,
			'POST',
			array(
				'name' => $name,
			)
		);
		$data     = $response->get_data();

		$this->assertSame( 201, $response->get_status(), 'Creating the product term should return HTTP 201.' );
		$this->assertIsInt( $data['id'], 'The created product term should have an integer ID.' );
		$term_id = $data['id'];
		$this->assertSame( $name, $data['name'], 'The create response should preserve the term name.' );
		$this->assertSame( $slug, $data['slug'], 'The create response should contain the deterministic term slug.' );
		$this->assertSame( '', $data['description'], 'A new product term should have an empty description.' );
		$this->assertSame( 0, $data['count'], 'A new product term should have no assigned products.' );
		$this->assertSame( rest_url( $route . '/' . $term_id ), $response->get_headers()['Location'], 'The create response should identify the registered item route.' );

		$response = $this->do_rest_request( $route . '/' . $term_id );
		$data     = $response->get_data();
		$this->assertSame( 200, $response->get_status(), 'Retrieving the created product term should return HTTP 200.' );
		$this->assertSame( $term_id, $data['id'], 'The item route should return the created product term.' );
		$this->assertSame( $name, $data['name'], 'The item route should return the created term name.' );
		$this->assertSame( $slug, $data['slug'], 'The item route should return the created term slug.' );
		$this->assertSame( '', $data['description'], 'The item route should return the empty initial description.' );
		$this->assertSame( 0, $data['count'], 'The item route should return the initial product count.' );

		$response       = $this->do_rest_request( $route, 'GET', null, array( 'include' => array( $term_id ) ) );
		$collection_ids = wp_list_pluck( $response->get_data(), 'id' );
		$this->assertSame( 200, $response->get_status(), 'Retrieving the product-term collection should return HTTP 200.' );
		$this->assertSame( 1, count( array_keys( $collection_ids, $term_id, true ) ), 'The product-term collection should contain the created ID exactly once.' );

		$response = $this->do_rest_request(
			$route . '/' . $term_id,
			'PUT',
			array(
				'description' => $description,
			)
		);
		$data     = $response->get_data();
		$term     = get_term( $term_id, $taxonomy );
		$this->assertSame( 200, $response->get_status(), 'Updating the product term should return HTTP 200.' );
		$this->assertInstanceOf( WP_Term::class, $term, 'The updated term should remain persisted.' );
		/** @var WP_Term $term */
		$this->assertSame(
			array( $description, $description ),
			array( $data['description'], $term->description ),
			'The update description should be returned and persisted.'
		);
		$this->assertSame( $term_id, $data['id'], 'The update response should retain the product term ID.' );
		$this->assertSame( $name, $data['name'], 'The update response should retain the product term name.' );
		$this->assertSame( $slug, $data['slug'], 'The update response should retain the product term slug.' );
		$this->assertSame( 0, $data['count'], 'Updating the product term should not change its product count.' );

		$response = $this->do_rest_request( $route . '/' . $term_id, 'DELETE', null, array( 'force' => true ) );
		$this->assertSame( 200, $response->get_status(), 'Force-deleting the product term should return HTTP 200.' );

		$response = $this->do_rest_request( $route . '/' . $term_id );
		$this->assertSame( 404, $response->get_status(), 'The deleted product term should no longer be retrievable.' );
	}

	/**
	 * @testdox Product terms can be created, updated, and deleted through the registered V3 batch route.
	 *
	 * @dataProvider product_term_routes
	 *
	 * @param string $taxonomy Product term taxonomy.
	 * @param string $route Registered V3 route.
	 */
	public function test_product_term_batch_lifecycle( $taxonomy, $route ): void {
		$name_prefix        = str_replace( '_', ' ', $taxonomy ) . ' batch';
		$first_description  = 'First update through the registered V3 product term batch route.';
		$second_description = 'Second update through the registered V3 product term batch route.';

		$response = $this->do_rest_request(
			$route . '/batch',
			'POST',
			array(
				'create' => array(
					array( 'name' => $name_prefix . ' one' ),
					array( 'name' => $name_prefix . ' two' ),
					array( 'name' => $name_prefix . ' three' ),
				),
			)
		);
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status(), 'The initial batch create should return HTTP 200.' );
		$this->assertCount( 3, $data['create'], 'The initial batch should create exactly three product terms.' );
		$this->assertSame( array( $name_prefix . ' one', $name_prefix . ' two', $name_prefix . ' three' ), wp_list_pluck( $data['create'], 'name' ), 'The initial batch should return all three created terms.' );
		$this->assertContainsOnly( 'int', wp_list_pluck( $data['create'], 'id' ), 'Each batch-created product term should have an integer ID.' );
		$first_id  = $data['create'][0]['id'];
		$second_id = $data['create'][1]['id'];
		$third_id  = $data['create'][2]['id'];

		$response = $this->do_rest_request(
			$route . '/batch',
			'POST',
			array(
				'create' => array(
					array( 'name' => $name_prefix . ' four' ),
				),
				'update' => array(
					array(
						'id'          => $first_id,
						'description' => $first_description,
					),
					array(
						'id'          => $second_id,
						'description' => $second_description,
					),
				),
				'delete' => array( $third_id ),
			)
		);
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status(), 'The mixed batch should return HTTP 200.' );
		$this->assertCount( 1, $data['create'], 'The mixed batch should create exactly one product term.' );
		$this->assertCount( 2, $data['update'], 'The mixed batch should update exactly two product terms.' );
		$this->assertCount( 1, $data['delete'], 'The mixed batch should delete exactly one product term.' );
		$fourth_id = $data['create'][0]['id'];
		$this->assertSame( $name_prefix . ' four', $data['create'][0]['name'], 'The mixed batch should return the created product term.' );
		$this->assertSame( array( $first_id, $second_id ), wp_list_pluck( $data['update'], 'id' ), 'The mixed batch should return both updated product term IDs.' );
		$this->assertSame( array( $first_description, $second_description ), wp_list_pluck( $data['update'], 'description' ), 'The mixed batch should return both updated descriptions.' );
		$this->assertSame( $third_id, $data['delete'][0]['id'], 'The mixed batch should return the deleted product term ID.' );

		$updated_terms = array(
			$first_id  => $first_description,
			$second_id => $second_description,
		);
		foreach ( $updated_terms as $updated_id => $expected_description ) {
			$response = $this->do_rest_request( $route . '/' . $updated_id );
			$term     = get_term( $updated_id, $taxonomy );
			$this->assertSame( 200, $response->get_status(), 'Each batch-updated product term should remain retrievable.' );
			$this->assertInstanceOf( WP_Term::class, $term, 'Each batch-updated product term should remain persisted.' );
			/** @var WP_Term $term */
			$this->assertSame(
				array( $updated_id, $expected_description, $expected_description ),
				array( $response->get_data()['id'], $response->get_data()['description'], $term->description ),
				'Each batch update should be returned by a fresh GET and persisted in the taxonomy.'
			);
		}

		$response = $this->do_rest_request( $route . '/' . $third_id );
		$this->assertSame( 404, $response->get_status(), 'The batch-deleted product term should no longer be retrievable.' );
	}
}
