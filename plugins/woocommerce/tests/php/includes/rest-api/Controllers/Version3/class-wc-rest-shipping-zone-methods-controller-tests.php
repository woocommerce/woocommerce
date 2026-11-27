<?php
declare( strict_types = 1 );

/**
 * Shipping zone methods controller tests for the V3 REST API.
 */
class WC_REST_Shipping_Zone_Methods_Controller_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * Runs before each test.
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
	 * @testdox Updating method settings uses the method's own sanitizer for locale-formatted amounts.
	 */
	public function test_update_method_settings_uses_sanitize_callback(): void {
		wp_set_current_user( $this->user );
		update_option( 'woocommerce_price_decimal_sep', ',' );
		update_option( 'woocommerce_price_thousand_sep', '.' );

		$zone = new WC_Shipping_Zone( null );
		$zone->set_zone_name( 'Zone 1' );
		$zone->save();
		$instance_id = $zone->add_shipping_method( 'free_shipping' );
		$route       = 'shipping/zones/' . $zone->get_id() . '/methods/' . $instance_id;
		$option      = 'woocommerce_free_shipping_' . $instance_id . '_settings';

		$response = $this->do_rest_post_request( $route, array( 'settings' => array( 'min_amount' => '1.000,50' ) ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertEquals( '1000.50', $response->get_data()['settings']['min_amount']['value'] );
		$this->assertSame( '1000.50', get_option( $option )['min_amount'], 'The minimum should be stored with a dot decimal.' );

		$response = $this->do_rest_post_request( $route, array( 'settings' => array( 'min_amount' => 'abc' ) ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( '1000.50', get_option( $option )['min_amount'], 'A rejected value should leave the stored minimum unchanged.' );
	}

	/**
	 * @testdox Updating method settings returns a 400 error when the method's sanitizer throws an Error.
	 */
	public function test_update_method_settings_sanitizer_error_returns_400(): void {
		wp_set_current_user( $this->user );

		$zone = new WC_Shipping_Zone( null );
		$zone->set_zone_name( 'Zone 1' );
		$zone->save();
		$instance_id = $zone->add_shipping_method( 'free_shipping' );
		$route       = 'shipping/zones/' . $zone->get_id() . '/methods/' . $instance_id;

		$add_sanitizer = function ( $fields ) {
			$fields['min_amount']['sanitize_callback'] = function () {
				throw new TypeError( 'Unexpected value.' );
			};
			return $fields;
		};
		add_filter( 'woocommerce_shipping_instance_form_fields_free_shipping', $add_sanitizer );

		$response = $this->do_rest_post_request( $route, array( 'settings' => array( 'min_amount' => '10' ) ) );

		remove_filter( 'woocommerce_shipping_instance_form_fields_free_shipping', $add_sanitizer );
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_setting_value_invalid', $response->get_data()['code'] );
	}
}
