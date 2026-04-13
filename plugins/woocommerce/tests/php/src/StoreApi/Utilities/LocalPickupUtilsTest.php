<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils;
use Automattic\WooCommerce\Tests\StoreApi\Mocks\FakeLocalPickupShippingMethod;
use Automattic\WooCommerce\Tests\StoreApi\Mocks\FakeRegularShippingMethod;
use WC_Shipping_Method;

/**
 * Tests for LocalPickupUtils class.
 */
class LocalPickupUtilsTest extends \WC_Unit_Test_Case {

	/**
	 * Original shipping methods backup.
	 *
	 * @var array
	 */
	private $original_shipping_methods;

	/**
	 * Mocked pickup locations value.
	 *
	 * @var array|null
	 */
	private $mocked_pickup_locations = null;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// Store original shipping methods to restore later.
		$this->original_shipping_methods = WC()->shipping()->shipping_methods;

		// Add filter to intercept option retrieval.
		add_filter( 'pre_option_pickup_location_pickup_locations', array( $this, 'filter_pickup_locations' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		// Restore original shipping methods.
		WC()->shipping()->shipping_methods = $this->original_shipping_methods;

		// Remove the filter.
		remove_filter( 'pre_option_pickup_location_pickup_locations', array( $this, 'filter_pickup_locations' ) );

		// Reset mocked value.
		$this->mocked_pickup_locations = null;

		parent::tearDown();
	}

	/**
	 * Filter callback to mock pickup locations option.
	 *
	 * @return array|false The mocked pickup locations or false to use real value.
	 */
	public function filter_pickup_locations() {
		if ( null !== $this->mocked_pickup_locations ) {
			return $this->mocked_pickup_locations;
		}
		return false;
	}

	/**
	 * Helper to set mocked pickup locations.
	 *
	 * @param array $locations The locations to mock.
	 */
	private function set_pickup_locations( array $locations ): void {
		$this->mocked_pickup_locations = $locations;
	}

	/**
	 * @testdox Should return empty array when no locations and no custom methods exist.
	 */
	public function test_returns_empty_array_when_no_locations_and_no_custom_methods(): void {
		$this->set_pickup_locations( array() );

		WC()->shipping()->shipping_methods = array();

		$result = LocalPickupUtils::get_local_pickup_method_locations();

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * @testdox Should return built-in locations with method_id added.
	 */
	public function test_returns_builtin_locations_with_method_id(): void {
		$this->set_pickup_locations(
			array(
				array(
					'name'    => 'Store Location',
					'enabled' => true,
					'address' => array(
						'address_1' => '123 Main St',
						'city'      => 'Anytown',
						'state'     => 'CA',
						'postcode'  => '12345',
						'country'   => 'US',
					),
					'details' => 'Open 9-5',
				),
			)
		);

		WC()->shipping()->shipping_methods = array();

		$result = LocalPickupUtils::get_local_pickup_method_locations();

		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'method_id', $result[0] );
		$this->assertSame( 'pickup_location', $result[0]['method_id'] );
		$this->assertSame( 'Store Location', $result[0]['name'] );
	}

	/**
	 * @testdox Should create placeholder location for custom shipping method with local-pickup support.
	 */
	public function test_creates_placeholder_for_custom_local_pickup_method(): void {
		$this->set_pickup_locations( array() );

		WC()->shipping()->shipping_methods = array(
			'test_local_pickup' => new FakeLocalPickupShippingMethod(),
		);

		$result = LocalPickupUtils::get_local_pickup_method_locations();

		$this->assertCount( 1, $result );
		$this->assertSame( 'test_local_pickup', $result[0]['method_id'] );
		$this->assertSame( 'Test Local Pickup', $result[0]['name'] );
		$this->assertTrue( $result[0]['enabled'] );
		$this->assertArrayHasKey( 'address', $result[0] );
		$this->assertSame( '123 Main Street', $result[0]['address']['address_1'] );
		$this->assertSame( 'Sample City', $result[0]['address']['city'] );
		$this->assertSame( '12345', $result[0]['address']['postcode'] );
	}

	/**
	 * @testdox Should not create placeholder for shipping method without local-pickup support.
	 */
	public function test_does_not_create_placeholder_for_regular_shipping_method(): void {
		$this->set_pickup_locations( array() );

		WC()->shipping()->shipping_methods = array(
			'test_regular' => new FakeRegularShippingMethod(),
		);

		$result = LocalPickupUtils::get_local_pickup_method_locations();

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * @testdox Should combine built-in locations with custom method placeholders.
	 */
	public function test_combines_builtin_locations_with_custom_method_placeholders(): void {
		$this->set_pickup_locations(
			array(
				array(
					'name'    => 'Store Location',
					'enabled' => true,
					'address' => array(
						'address_1' => '123 Main St',
						'city'      => 'Anytown',
						'state'     => 'CA',
						'postcode'  => '12345',
						'country'   => 'US',
					),
					'details' => 'Open 9-5',
				),
			)
		);

		WC()->shipping()->shipping_methods = array(
			'test_local_pickup' => new FakeLocalPickupShippingMethod(),
		);

		$result = LocalPickupUtils::get_local_pickup_method_locations();

		$this->assertCount( 2, $result );

		$this->assertSame( 'pickup_location', $result[0]['method_id'] );
		$this->assertSame( 'Store Location', $result[0]['name'] );

		$this->assertSame( 'test_local_pickup', $result[1]['method_id'] );
		$this->assertSame( 'Test Local Pickup', $result[1]['name'] );
	}

	/**
	 * @testdox Should not duplicate pickup_location method in results.
	 */
	public function test_does_not_duplicate_builtin_pickup_location_method(): void {
		$this->set_pickup_locations(
			array(
				array(
					'name'    => 'Store Location',
					'enabled' => true,
					'address' => array(
						'address_1' => '123 Main St',
						'city'      => 'Anytown',
						'state'     => 'CA',
						'postcode'  => '12345',
						'country'   => 'US',
					),
					'details' => 'Open 9-5',
				),
			)
		);

		$pickup_location_mock     = $this->createMock( WC_Shipping_Method::class );
		$pickup_location_mock->id = 'pickup_location';
		$pickup_location_mock->method( 'supports' )->willReturn( true );
		$pickup_location_mock->method( 'get_method_title' )->willReturn( 'Local Pickup' );

		WC()->shipping()->shipping_methods = array(
			'pickup_location' => $pickup_location_mock,
		);

		$result = LocalPickupUtils::get_local_pickup_method_locations();

		$this->assertCount( 1, $result );
		$this->assertSame( 'pickup_location', $result[0]['method_id'] );
		$this->assertSame( 'Store Location', $result[0]['name'] );
	}

	/**
	 * @testdox Should use store base country and state for placeholder address.
	 */
	public function test_uses_store_base_location_for_placeholder(): void {
		add_filter(
			'pre_option_woocommerce_default_country',
			function () {
				return 'GB:LND';
			}
		);

		$this->set_pickup_locations( array() );

		WC()->shipping()->shipping_methods = array(
			'test_local_pickup' => new FakeLocalPickupShippingMethod(),
		);

		$result = LocalPickupUtils::get_local_pickup_method_locations();

		$this->assertCount( 1, $result );
		$this->assertSame( 'GB', $result[0]['address']['country'] );
		$this->assertSame( 'LND', $result[0]['address']['state'] );

		remove_all_filters( 'pre_option_woocommerce_default_country' );
	}

	/**
	 * @testdox Should include details with method title in placeholder.
	 */
	public function test_includes_details_with_method_title(): void {
		$this->set_pickup_locations( array() );

		WC()->shipping()->shipping_methods = array(
			'test_local_pickup' => new FakeLocalPickupShippingMethod(),
		);

		$result = LocalPickupUtils::get_local_pickup_method_locations();

		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'details', $result[0] );
		$this->assertStringContainsString( 'Test Local Pickup', $result[0]['details'] );
	}
}
