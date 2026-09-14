<?php
/**
 * ShippingZoneMethodService tests.
 *
 * @package WooCommerce\Tests\RestApi\Controllers\Version4\ShippingZoneMethod
 */

declare( strict_types=1 );

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZoneMethod\ShippingZoneMethodService;

/**
 * ShippingZoneMethodService test class.
 */
class WC_REST_Shipping_Zone_Method_V4_Service_Tests extends WC_Unit_Test_Case {

	/**
	 * ShippingZoneMethodService instance.
	 *
	 * @var ShippingZoneMethodService
	 */
	private $service;

	/**
	 * Created shipping zones for cleanup.
	 *
	 * @var array
	 */
	private $created_zones = array();

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->service = new ShippingZoneMethodService();

		// Ensure shipping is enabled for tests.
		update_option( 'woocommerce_ship_to_countries', '' );
		update_option( 'woocommerce_shipping_cost_requires_address', 'no' );
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		// Clean up created zones.
		foreach ( $this->created_zones as $zone ) {
			if ( $zone instanceof WC_Shipping_Zone && $zone->get_id() > 0 ) {
				$zone->delete();
			}
		}
		$this->created_zones = array();

		parent::tearDown();
	}

	/**
	 * Helper method to create a shipping zone.
	 *
	 * @param string $name Zone name.
	 * @param int    $order Zone order.
	 * @param array  $locations Zone locations.
	 * @return WC_Shipping_Zone
	 */
	private function create_zone( $name = 'Test Zone', $order = 0, $locations = array() ) {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( $name );
		$zone->set_zone_order( $order );
		$zone->set_locations( $locations );
		$zone->save();

		$this->created_zones[] = $zone;

		return $zone;
	}

	// ========================================
	// Tests for update_shipping_method_settings()
	// ========================================

	/**
	 * Test update_shipping_method_settings with valid settings.
	 */
	public function test_update_shipping_method_settings_success() {
		$zone        = $this->create_zone( 'Test Zone' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$method      = WC_Shipping_Zones::get_shipping_method( $instance_id );

		$settings = array(
			'title' => 'Custom Flat Rate',
			'cost'  => '15.50',
		);

		$result = $this->service->update_shipping_method_settings( $method, $settings );

		$this->assertInstanceOf( WC_Shipping_Method::class, $result );
		$this->assertEquals( 'Custom Flat Rate', $result->get_option( 'title' ) );
		$this->assertEquals( '15.50', $result->get_option( 'cost' ) );
	}

	/**
	 * Test update_shipping_method_settings validates settings.
	 */
	public function test_update_shipping_method_settings_validates() {
		$zone        = $this->create_zone( 'Test Zone' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$method      = WC_Shipping_Zones::get_shipping_method( $instance_id );

		// Settings array must be an array.
		$result = $this->service->update_shipping_method_settings( $method, 'not an array' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'woocommerce_rest_shipping_method_invalid_settings', $result->get_error_code() );
	}

	/**
	 * Test update_shipping_method_settings sanitizes values.
	 */
	public function test_update_shipping_method_settings_sanitizes() {
		$zone        = $this->create_zone( 'Test Zone' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$method      = WC_Shipping_Zones::get_shipping_method( $instance_id );

		$settings = array(
			'title' => 'Test <script>alert("xss")</script>',
		);

		$result = $this->service->update_shipping_method_settings( $method, $settings );

		$this->assertInstanceOf( WC_Shipping_Method::class, $result );
		$title = $result->get_option( 'title' );
		$this->assertStringNotContainsString( '<script>', $title );
	}

	// ========================================
	// Tests for update_shipping_zone_method()
	// ========================================

	/**
	 * Test update_shipping_zone_method updates enabled status.
	 */
	public function test_update_shipping_zone_method_enabled() {
		$zone        = $this->create_zone( 'Test Zone' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$method      = WC_Shipping_Zones::get_shipping_method( $instance_id );

		$data = array( 'enabled' => false );

		$result = $this->service->update_shipping_zone_method( $method, $instance_id, $data, $zone->get_id() );

		$this->assertInstanceOf( WC_Shipping_Method::class, $result );
		$this->assertFalse( wc_string_to_bool( $result->enabled ) );
	}

	/**
	 * Test update_shipping_zone_method updates order.
	 */
	public function test_update_shipping_zone_method_order() {
		$zone        = $this->create_zone( 'Test Zone' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$method      = WC_Shipping_Zones::get_shipping_method( $instance_id );

		$data = array( 'order' => 5 );

		$result = $this->service->update_shipping_zone_method( $method, $instance_id, $data );

		$this->assertInstanceOf( WC_Shipping_Method::class, $result );
		$this->assertEquals( 5, $result->method_order );
	}

	/**
	 * Test update_shipping_zone_method updates both enabled and order in single query.
	 */
	public function test_update_shipping_zone_method_enabled_and_order() {
		$zone        = $this->create_zone( 'Test Zone' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$method      = WC_Shipping_Zones::get_shipping_method( $instance_id );

		$data = array(
			'enabled' => false,
			'order'   => 10,
		);

		$result = $this->service->update_shipping_zone_method( $method, $instance_id, $data, $zone->get_id() );

		$this->assertInstanceOf( WC_Shipping_Method::class, $result );
		$this->assertFalse( wc_string_to_bool( $result->enabled ) );
		$this->assertEquals( 10, $result->method_order );
	}

	/**
	 * Test update_shipping_zone_method updates settings via update_shipping_method_settings.
	 */
	public function test_update_shipping_zone_method_updates_settings() {
		$zone        = $this->create_zone( 'Test Zone' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$method      = WC_Shipping_Zones::get_shipping_method( $instance_id );

		$data = array(
			'settings' => array(
				'title' => 'Updated Title',
				'cost'  => '20.00',
			),
		);

		$result = $this->service->update_shipping_zone_method( $method, $instance_id, $data );

		$this->assertInstanceOf( WC_Shipping_Method::class, $result );
		$this->assertEquals( 'Updated Title', $result->get_option( 'title' ) );
		$this->assertEquals( '20.00', $result->get_option( 'cost' ) );
	}

	/**
	 * Test update_shipping_zone_method with empty data returns method object.
	 */
	public function test_update_shipping_zone_method_empty_data() {
		$zone        = $this->create_zone( 'Test Zone' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$method      = WC_Shipping_Zones::get_shipping_method( $instance_id );

		$data = array();

		$result = $this->service->update_shipping_zone_method( $method, $instance_id, $data );

		// Should return method object without doing anything.
		$this->assertInstanceOf( WC_Shipping_Method::class, $result );
	}

	/**
	 * Test update_shipping_zone_method returns error on settings validation failure.
	 */
	public function test_update_shipping_zone_method_settings_validation_error() {
		$zone        = $this->create_zone( 'Test Zone' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$method      = WC_Shipping_Zones::get_shipping_method( $instance_id );

		$data = array(
			'settings' => 'not an array',
		);

		$result = $this->service->update_shipping_zone_method( $method, $instance_id, $data );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'woocommerce_rest_shipping_method_invalid_settings', $result->get_error_code() );
	}
}
