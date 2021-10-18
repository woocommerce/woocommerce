<?php

/**
 * class WC_REST_Taxes_Controller_Tests.
 * Taxes Controller tests for V3 REST API.
 */
class WC_REST_Taxes_Controller_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * Runs before any test.
	 */
	public function setUp() {
		parent::setUp();
		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
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
			function( $item ) {
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
			function( $item ) {
				return $item['id'];
			},
			$data
		);

		$this->assertEquals( array( $tax_ids_by_class[ $class ] ), $ids );
	}
}
