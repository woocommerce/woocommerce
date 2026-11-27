<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Shipping;

use Automattic\WooCommerce\Internal\Shipping\ShippingZoneMethodService;
use WC_Shipping_Method;
use WC_Shipping_Zone;
use WC_Shipping_Zones;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for the ShippingZoneMethodService class.
 */
class ShippingZoneMethodServiceTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ShippingZoneMethodService
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new ShippingZoneMethodService();

		update_option( 'woocommerce_ship_to_countries', '' );
		update_option( 'woocommerce_shipping_cost_requires_address', 'no' );
	}

	/**
	 * Create a shipping zone.
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

		return $zone;
	}

	/**
	 * @testdox Should update a shipping method's settings.
	 */
	public function test_update_shipping_method_settings_success() {
		$zone        = $this->create_zone( 'Test Zone' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$method      = WC_Shipping_Zones::get_shipping_method( $instance_id );

		$settings = array(
			'title' => 'Custom Flat Rate',
			'cost'  => '15.50',
		);

		$result = $this->sut->update_shipping_method_settings( $method, $settings );

		$this->assertInstanceOf( WC_Shipping_Method::class, $result );
		$this->assertEquals( 'Custom Flat Rate', $result->get_option( 'title' ) );
		$this->assertEquals( '15.50', $result->get_option( 'cost' ) );
	}

	/**
	 * @testdox Should reject settings that are not an array.
	 */
	public function test_update_shipping_method_settings_validates() {
		$zone        = $this->create_zone( 'Test Zone' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$method      = WC_Shipping_Zones::get_shipping_method( $instance_id );

		$result = $this->sut->update_shipping_method_settings( $method, 'not an array' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'woocommerce_rest_shipping_method_invalid_settings', $result->get_error_code() );
	}

	/**
	 * @testdox Should sanitize setting values.
	 */
	public function test_update_shipping_method_settings_sanitizes() {
		$zone        = $this->create_zone( 'Test Zone' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$method      = WC_Shipping_Zones::get_shipping_method( $instance_id );

		$settings = array(
			'title' => 'Test <script>alert("xss")</script>',
		);

		$result = $this->sut->update_shipping_method_settings( $method, $settings );

		$this->assertInstanceOf( WC_Shipping_Method::class, $result );
		$this->assertStringNotContainsString( '<script>', $result->get_option( 'title' ) );
	}

	/**
	 * @testdox Should update the enabled status.
	 */
	public function test_update_shipping_zone_method_enabled() {
		$zone        = $this->create_zone( 'Test Zone' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$method      = WC_Shipping_Zones::get_shipping_method( $instance_id );

		$data = array( 'enabled' => false );

		$result = $this->sut->update_shipping_zone_method( $method, $instance_id, $data, $zone->get_id() );

		$this->assertInstanceOf( WC_Shipping_Method::class, $result );
		$this->assertFalse( wc_string_to_bool( $result->enabled ) );
	}

	/**
	 * @testdox Should update the sort order.
	 */
	public function test_update_shipping_zone_method_order() {
		$zone        = $this->create_zone( 'Test Zone' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$method      = WC_Shipping_Zones::get_shipping_method( $instance_id );

		$data = array( 'order' => 5 );

		$result = $this->sut->update_shipping_zone_method( $method, $instance_id, $data );

		$this->assertInstanceOf( WC_Shipping_Method::class, $result );
		$this->assertEquals( 5, $result->method_order );
	}

	/**
	 * @testdox Should update the enabled status and sort order in one call.
	 */
	public function test_update_shipping_zone_method_enabled_and_order() {
		$zone        = $this->create_zone( 'Test Zone' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$method      = WC_Shipping_Zones::get_shipping_method( $instance_id );

		$data = array(
			'enabled' => false,
			'order'   => 10,
		);

		$result = $this->sut->update_shipping_zone_method( $method, $instance_id, $data, $zone->get_id() );

		$this->assertInstanceOf( WC_Shipping_Method::class, $result );
		$this->assertFalse( wc_string_to_bool( $result->enabled ) );
		$this->assertEquals( 10, $result->method_order );
	}

	/**
	 * @testdox Should update settings through update_shipping_method_settings.
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

		$result = $this->sut->update_shipping_zone_method( $method, $instance_id, $data );

		$this->assertInstanceOf( WC_Shipping_Method::class, $result );
		$this->assertEquals( 'Updated Title', $result->get_option( 'title' ) );
		$this->assertEquals( '20.00', $result->get_option( 'cost' ) );
	}

	/**
	 * @testdox Should return the method unchanged when no data is given.
	 */
	public function test_update_shipping_zone_method_empty_data() {
		$zone        = $this->create_zone( 'Test Zone' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$method      = WC_Shipping_Zones::get_shipping_method( $instance_id );

		$result = $this->sut->update_shipping_zone_method( $method, $instance_id, array() );

		$this->assertInstanceOf( WC_Shipping_Method::class, $result );
	}

	/**
	 * @testdox Should return an error when settings validation fails.
	 */
	public function test_update_shipping_zone_method_settings_validation_error() {
		$zone        = $this->create_zone( 'Test Zone' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$method      = WC_Shipping_Zones::get_shipping_method( $instance_id );

		$data = array(
			'settings' => 'not an array',
		);

		$result = $this->sut->update_shipping_zone_method( $method, $instance_id, $data );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'woocommerce_rest_shipping_method_invalid_settings', $result->get_error_code() );
	}
}
