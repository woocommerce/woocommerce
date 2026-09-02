<?php

/**
 * class WC_REST_Taxes_Controller_Tests.
 * Taxes Controller tests for V3 REST API.
 */
class WC_REST_Taxes_Controller_Tests extends WC_REST_Unit_Test_Case {

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
	}

	/**
	 * @testdox A tax class completes its registered V3 create, read, list, unsupported update, and delete lifecycle.
	 */
	public function test_tax_class_crud_lifecycle() {
		wp_set_current_user( $this->user );

		$class_name = 'Slice 42 Class ' . wp_generate_uuid4();
		$class_slug = sanitize_title( $class_name );

		try {
			$response = $this->do_rest_request(
				'taxes/classes',
				'POST',
				array( 'name' => $class_name )
			);
			$this->assertSame( 201, $response->get_status() );
			$this->assertSame(
				array(
					'name' => $class_name,
					'slug' => $class_slug,
				),
				$response->get_data()
			);
			$this->assertSame(
				array(
					'name' => $class_name,
					'slug' => $class_slug,
				),
				WC_Tax::get_tax_class_by( 'slug', $class_slug )
			);

			$response = $this->do_rest_get_request( 'taxes/classes/' . $class_slug );
			$this->assertSame( 200, $response->get_status() );
			$this->assertCount( 1, $response->get_data() );
			$this->assertSame( $class_name, $response->get_data()[0]['name'] );
			$this->assertSame( $class_slug, $response->get_data()[0]['slug'] );

			$response = $this->do_rest_get_request( 'taxes/classes' );
			$this->assertSame( 200, $response->get_status() );
			$this->assertSame(
				array( $class_slug ),
				array_values( wp_list_pluck( wp_list_filter( $response->get_data(), array( 'slug' => $class_slug ) ), 'slug' ) )
			);

			$response = $this->do_rest_request(
				'taxes/classes/' . $class_slug,
				'PUT',
				array( 'name' => 'Unsupported update' )
			);
			$this->assertSame( 404, $response->get_status() );
			$this->assertSame( 'rest_no_route', $response->get_data()['code'] );

			$response = $this->do_rest_request(
				'taxes/classes/' . $class_slug,
				'DELETE',
				array( 'force' => true )
			);
			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( $class_slug, $response->get_data()['slug'] );
			$this->assertFalse( WC_Tax::get_tax_class_by( 'slug', $class_slug ) );

			$response = $this->do_rest_get_request( 'taxes/classes/' . $class_slug );
			$this->assertSame( 404, $response->get_status() );
			$this->assertSame( 'woocommerce_rest_tax_class_invalid_slug', $response->get_data()['code'] );
		} finally {
			if ( WC_Tax::get_tax_class_by( 'slug', $class_slug ) ) {
				WC_Tax::delete_tax_class_by( 'slug', $class_slug );
			}
		}
	}

	/**
	 * @testdox A tax rate completes its registered V3 CRUD lifecycle with fresh persisted state.
	 */
	public function test_tax_rate_crud_lifecycle() {
		wp_set_current_user( $this->user );

		$class_name = 'Slice 42 Rate Class ' . wp_generate_uuid4();
		$class_slug = WC_Tax::create_tax_class( $class_name )['slug'];
		$rate_id    = 0;

		try {
			$response = $this->do_rest_request(
				'taxes',
				'POST',
				array(
					'country'   => 'US',
					'state'     => 'CA',
					'cities'    => array( 'Los Angeles' ),
					'postcodes' => array( '90001' ),
					'rate'      => '4.25',
					'name'      => 'Slice 42 State Tax',
					'shipping'  => false,
					'order'     => 3,
					'class'     => $class_slug,
				)
			);
			$data     = $response->get_data();
			$rate_id  = $data['id'];
			$expected = array(
				'id'        => $rate_id,
				'country'   => 'US',
				'state'     => 'CA',
				'postcode'  => '90001',
				'city'      => 'LOS ANGELES',
				'rate'      => '4.2500',
				'name'      => 'Slice 42 State Tax',
				'priority'  => 1,
				'compound'  => false,
				'shipping'  => false,
				'order'     => 3,
				'class'     => $class_slug,
				'postcodes' => array( '90001' ),
				'cities'    => array( 'LOS ANGELES' ),
			);
			$this->assertSame( 201, $response->get_status() );
			$this->assertIsInt( $rate_id );
			$this->assertGreaterThan( 0, $rate_id );
			$this->assertSame( $expected, array_intersect_key( $data, $expected ) );

			$persisted = WC_Tax::_get_tax_rate( $rate_id );
			$this->assertSame( $class_slug, $persisted['tax_rate_class'] );
			$this->assertSame( '4.2500', $persisted['tax_rate'] );
			$this->assertSame( 'Slice 42 State Tax', $persisted['tax_rate_name'] );

			$response = $this->do_rest_get_request( 'taxes/' . $rate_id );
			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( $expected, array_intersect_key( $response->get_data(), $expected ) );

			$response = $this->do_rest_get_request( 'taxes' );
			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( array( $rate_id ), array_values( array_intersect( wp_list_pluck( $response->get_data(), 'id' ), array( $rate_id ) ) ) );

			$response = $this->do_rest_request(
				'taxes/' . $rate_id,
				'PUT',
				array(
					'rate'     => '5.5',
					'name'     => 'Slice 42 Updated Tax',
					'priority' => 2,
					'compound' => true,
					'shipping' => true,
					'order'    => 8,
				)
			);
			$data     = $response->get_data();
			$expected = array(
				'id'       => $rate_id,
				'rate'     => '5.5000',
				'name'     => 'Slice 42 Updated Tax',
				'priority' => 2,
				'compound' => true,
				'shipping' => true,
				'order'    => 8,
				'class'    => $class_slug,
			);
			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( $expected, array_intersect_key( $data, $expected ) );

			$persisted = WC_Tax::_get_tax_rate( $rate_id );
			$this->assertSame( '5.5000', $persisted['tax_rate'] );
			$this->assertSame( 'Slice 42 Updated Tax', $persisted['tax_rate_name'] );
			$this->assertSame( '2', $persisted['tax_rate_priority'] );
			$this->assertSame( '1', $persisted['tax_rate_compound'] );
			$this->assertSame( '1', $persisted['tax_rate_shipping'] );
			$this->assertSame( '8', $persisted['tax_rate_order'] );
			$this->assertSame( $class_slug, $persisted['tax_rate_class'] );

			$response = $this->do_rest_get_request( 'taxes/' . $rate_id );
			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( $expected, array_intersect_key( $response->get_data(), $expected ) );

			$response = $this->do_rest_request( 'taxes/' . $rate_id, 'DELETE', array( 'force' => true ) );
			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( $rate_id, $response->get_data()['id'] );
			$this->assertNull( WC_Tax::_get_tax_rate( $rate_id ) );
			$rate_id = 0;

			$response = $this->do_rest_get_request( 'taxes/' . $data['id'] );
			$this->assertSame( 404, $response->get_status() );
			$this->assertSame( 'woocommerce_rest_invalid_id', $response->get_data()['code'] );
		} finally {
			if ( $rate_id && WC_Tax::_get_tax_rate( $rate_id ) ) {
				WC_Tax::_delete_tax_rate( $rate_id );
			}
			if ( WC_Tax::get_tax_class_by( 'slug', $class_slug ) ) {
				WC_Tax::delete_tax_class_by( 'slug', $class_slug );
			}
		}
	}

	/**
	 * @testdox Two tax rates complete registered V3 batch create, update, and delete lifecycles in order.
	 */
	public function test_tax_rate_batch_lifecycle() {
		wp_set_current_user( $this->user );

		$class_name = 'Slice 42 Batch Class ' . wp_generate_uuid4();
		$class_slug = WC_Tax::create_tax_class( $class_name )['slug'];
		$rate_ids   = array();

		try {
			$response = $this->do_rest_request(
				'taxes/batch',
				'POST',
				array(
					'create' => array(
						array(
							'country' => 'US',
							'state'   => 'NY',
							'rate'    => '4.1',
							'name'    => 'Slice 42 Batch One',
							'order'   => 1,
							'class'   => $class_slug,
						),
						array(
							'country' => 'CA',
							'state'   => 'BC',
							'rate'    => '6.2',
							'name'    => 'Slice 42 Batch Two',
							'order'   => 2,
							'class'   => $class_slug,
						),
					),
				)
			);
			$created  = $response->get_data()['create'];
			$rate_ids = wp_list_pluck( $created, 'id' );
			$this->assertSame( 200, $response->get_status() );
			$this->assertCount( 2, $created );
			$this->assertCount( 2, array_unique( $rate_ids ) );
			$this->assertSame( array( 'Slice 42 Batch One', 'Slice 42 Batch Two' ), wp_list_pluck( $created, 'name' ) );
			$this->assertSame( array( '4.1000', '6.2000' ), wp_list_pluck( $created, 'rate' ) );
			$this->assertSame( array( $class_slug, $class_slug ), wp_list_pluck( $created, 'class' ) );

			foreach ( $rate_ids as $rate_id ) {
				$this->assertIsInt( $rate_id );
				$this->assertGreaterThan( 0, $rate_id );
				$this->assertSame( $class_slug, WC_Tax::_get_tax_rate( $rate_id )['tax_rate_class'] );
			}

			$response = $this->do_rest_request(
				'taxes/batch',
				'POST',
				array(
					'update' => array(
						array(
							'id'    => $rate_ids[0],
							'rate'  => '4.1111',
							'name'  => 'Slice 42 Batch One Updated',
							'order' => 7,
						),
						array(
							'id'       => $rate_ids[1],
							'rate'     => '6.2222',
							'name'     => 'Slice 42 Batch Two Updated',
							'priority' => 3,
						),
					),
				)
			);
			$updated  = $response->get_data()['update'];
			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( $rate_ids, wp_list_pluck( $updated, 'id' ) );
			$this->assertSame( array( '4.1111', '6.2222' ), wp_list_pluck( $updated, 'rate' ) );
			$this->assertSame( array( 'Slice 42 Batch One Updated', 'Slice 42 Batch Two Updated' ), wp_list_pluck( $updated, 'name' ) );
			$this->assertSame( 7, $updated[0]['order'] );
			$this->assertSame( 3, $updated[1]['priority'] );

			$first_persisted  = WC_Tax::_get_tax_rate( $rate_ids[0] );
			$second_persisted = WC_Tax::_get_tax_rate( $rate_ids[1] );
			$this->assertSame( '4.1111', $first_persisted['tax_rate'] );
			$this->assertSame( 'Slice 42 Batch One Updated', $first_persisted['tax_rate_name'] );
			$this->assertSame( '7', $first_persisted['tax_rate_order'] );
			$this->assertSame( '6.2222', $second_persisted['tax_rate'] );
			$this->assertSame( 'Slice 42 Batch Two Updated', $second_persisted['tax_rate_name'] );
			$this->assertSame( '3', $second_persisted['tax_rate_priority'] );

			$response = $this->do_rest_request(
				'taxes/batch',
				'POST',
				array( 'delete' => $rate_ids )
			);
			$deleted  = $response->get_data()['delete'];
			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( $rate_ids, wp_list_pluck( $deleted, 'id' ) );

			foreach ( $rate_ids as $rate_id ) {
				$this->assertNull( WC_Tax::_get_tax_rate( $rate_id ) );
				$get_response = $this->do_rest_get_request( 'taxes/' . $rate_id );
				$this->assertSame( 404, $get_response->get_status() );
				$this->assertSame( 'woocommerce_rest_invalid_id', $get_response->get_data()['code'] );
			}
			$rate_ids = array();
		} finally {
			foreach ( $rate_ids as $rate_id ) {
				if ( WC_Tax::_get_tax_rate( $rate_id ) ) {
					WC_Tax::_delete_tax_rate( $rate_id );
				}
			}
			if ( WC_Tax::get_tax_class_by( 'slug', $class_slug ) ) {
				WC_Tax::delete_tax_class_by( 'slug', $class_slug );
			}
		}
	}

	/**
	 * Data provider for test_can_create_and_update_tax_rates_with_multiple_cities_and_postcodes.
	 *
	 * @return array
	 */
	public function data_provider_for_test_can_create_and_update_tax_rates_with_multiple_cities_and_postcodes() {
		return array(
			array(
				array(
					'city'     => 'Osaka;Kyoto;Kobe',
					'postcode' => '5555;7777;8888',
				),
				'create',
			),
			array(
				array(
					'cities'    => array(
						'Osaka',
						'Kyoto',
						'Kobe',
					),
					'postcodes' => array(
						'5555',
						'7777',
						'8888',
					),
				),
				'create',
			),
			array(
				array(
					'city'     => 'Osaka;Kyoto;Kobe',
					'postcode' => '5555;7777;8888',
				),
				'update',
			),
			array(
				array(
					'cities'    => array(
						'Osaka',
						'Kyoto',
						'Kobe',
					),
					'postcodes' => array(
						'5555',
						'7777',
						'8888',
					),
				),
				'update',
			),
		);
	}

	/**
	 * @testdox It is possible to create or update a tax rate passing either "city"/"postcode" (strings) or "cities"/"postcodes" (arrays) fields.
	 *
	 * @dataProvider data_provider_for_test_can_create_and_update_tax_rates_with_multiple_cities_and_postcodes
	 *
	 * @param array  $request_body The body for the REST request.
	 * @param string $action The action to perform, 'create' or 'update'.
	 */
	public function test_can_create_and_update_tax_rates_with_multiple_cities_and_postcodes( $request_body, $action ) {
		global $wpdb;

		wp_set_current_user( $this->user );

		if ( 'create' === $action ) {
			$tax_rate_id = null;

			$request_body = array_merge(
				$request_body,
				array(
					'country' => 'JP',
					'rate'    => '1',
					'name'    => 'Fake Tax',
				)
			);

			$verb           = 'POST';
			$url            = 'taxes';
			$success_status = 201;
		} else {
			$tax_rate_id = WC_Tax::_insert_tax_rate(
				array(
					'tax_rate_country' => 'JP',
					'tax_rate'         => '1',
					'tax_rate_name'    => 'Fake Tax',
				)
			);

			WC_Tax::_update_tax_rate_cities( $tax_rate_id, 'Tokyo' );
			WC_Tax::_update_tax_rate_postcodes( $tax_rate_id, '0000' );

			$verb           = 'PUT';
			$url            = 'taxes/' . $tax_rate_id;
			$success_status = 200;
		}

		$response = $this->do_rest_request( $url, $verb, $request_body );
		$this->assertEquals( $success_status, $response->get_status() );
		if ( ! $tax_rate_id ) {
			$tax_rate_id = $response->get_data()['id'];
		}

		$data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT location_type, GROUP_CONCAT(location_code SEPARATOR ';') as items
						FROM {$wpdb->prefix}woocommerce_tax_rate_locations
						WHERE tax_rate_id=%d
						GROUP BY location_type",
				$tax_rate_id
			),
			OBJECT_K
		);

		$this->assertEquals( 'OSAKA;KYOTO;KOBE', $data['city']->items );
		$this->assertEquals( '5555;7777;8888', $data['postcode']->items );
	}

	/**
	 * @testdox The response for tax rate(s) includes the "city"/"postcode" (strings) and "cities"/"postcodes" (arrays) fields.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $request_one True to request only one tax, false to request all the taxes.
	 */
	public function test_get_tax_response_includes_cities_and_postcodes_as_arrays( $request_one ) {
		wp_set_current_user( $this->user );

		$tax_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country' => 'JP',
				'tax_rate'         => '1',
				'tax_rate_name'    => 'Fake Tax',
			)
		);

		WC_Tax::_update_tax_rate_cities( $tax_id, 'Osaka;Kyoto;Kobe' );
		WC_Tax::_update_tax_rate_postcodes( $tax_id, '5555;7777;8888' );

		if ( $request_one ) {
			$response = $this->do_rest_get_request( 'taxes/' . $tax_id );
		} else {
			$response = $this->do_rest_get_request( 'taxes' );
		}

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		if ( ! $request_one ) {
			$data = current( $data );
		}

		$this->assertEquals( 'KOBE', $data['city'] );
		$this->assertEquals( '8888', $data['postcode'] );
		$this->assertEquals( array( 'OSAKA', 'KYOTO', 'KOBE' ), $data['cities'] );
		$this->assertEquals( array( '5555', '7777', '8888' ), $data['postcodes'] );
	}

	/**
	 * @testdox The response of a REST API request for taxes can be sorted by priority.
	 *
	 * @testWith ["asc"]
	 *           ["desc"]
	 *
	 * @param string $order_type Sort type, 'asc' or 'desc'.
	 */
	public function test_get_tax_response_can_be_sorted_by_priority( $order_type ) {
		wp_set_current_user( $this->user );

		$tax_id_1 = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'JP',
				'tax_rate'          => '1',
				'tax_rate_priority' => 1,
				'tax_rate_name'     => 'Fake Tax 1',
			)
		);
		$tax_id_3 = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'JP',
				'tax_rate'          => '1',
				'tax_rate_priority' => 3,
				'tax_rate_name'     => 'Fake Tax 3',
			)
		);
		$tax_id_2 = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'JP',
				'tax_rate'          => '1',
				'tax_rate_priority' => 2,
				'tax_rate_name'     => 'Fake Tax 2',
			)
		);

		$response = $this->do_rest_get_request(
			'taxes',
			array(
				'orderby' => 'priority',
				'order'   => $order_type,
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$data = array_values( $response->get_data() );
		$ids  = array_map(
			function ( $item ) {
				return $item['id'];
			},
			$data
		);

		if ( 'asc' === $order_type ) {
			$expected = array( $tax_id_1, $tax_id_2, $tax_id_3 );
		} else {
			$expected = array( $tax_id_3, $tax_id_2, $tax_id_1 );
		}
		$this->assertEquals( $expected, $ids );
	}

	/**
	 * @testdox Tax rates can be queries filtering by tax class.
	 *
	 * @testWith ["standard"]
	 *           ["reduced-rate"]
	 *           ["zero-rate"]
	 *
	 * @param string $class The tax class name to try getting the taxes for.
	 */
	public function test_can_get_taxes_filtering_by_class( $class ) {
		wp_set_current_user( $this->user );

		$classes = array( 'standard', 'reduced-rate', 'zero-rate' );

		$tax_ids_by_class = array();
		foreach ( $classes as $class ) {
			$tax_id                     = WC_Tax::_insert_tax_rate(
				array(
					'tax_rate_country'  => 'JP',
					'tax_rate'          => '1',
					'tax_rate_priority' => 1,
					'tax_rate_name'     => 'Fake Tax',
					'tax_rate_class'    => $class,
				)
			);
			$tax_ids_by_class[ $class ] = $tax_id;
		}

		$response = $this->do_rest_get_request(
			'taxes',
			array(
				'class' => $class,
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$data = array_values( $response->get_data() );
		$ids  = array_map(
			function ( $item ) {
				return $item['id'];
			},
			$data
		);

		$this->assertEquals( array( $tax_ids_by_class[ $class ] ), $ids );
	}

	/**
	 * @testdox Tax rates with non-Latin characters in tax class names are properly created and associated with the correct class.
	 */
	public function test_can_create_tax_rate_with_non_latin_tax_class() {
		wp_set_current_user( $this->user );

		$tax_class_name = '∑';
		$tax_class_slug = WC_Tax::create_tax_class( $tax_class_name )['slug'];

		$controller = new WC_REST_Taxes_V1_Controller();
		$request    = new WP_REST_Request( 'POST', '/wc/v1/taxes' );
		$request->set_body_params(
			array(
				'class' => $tax_class_slug,
			)
		);

		$response    = $controller->create_item( $request );
		$tax_rate_id = $response->get_data()['id'];

		$tax_rate = WC_Tax::_get_tax_rate( $tax_rate_id );
		$this->assertEquals( $tax_class_slug, $tax_rate['tax_rate_class'] );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
		WC_Tax::delete_tax_class_by( 'slug', $tax_class_slug );
	}
}
