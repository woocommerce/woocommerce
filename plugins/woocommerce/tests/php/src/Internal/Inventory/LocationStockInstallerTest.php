<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Inventory;

use Automattic\WooCommerce\Internal\Inventory\InventoryController;
use Automattic\WooCommerce\Internal\Inventory\LocationStockInstaller;
use Automattic\WooCommerce\Internal\Inventory\LocationStockService;

require_once __DIR__ . '/LocationStockTestCase.php';

/**
 * Tests for POS location stock.
 *
 * @covers \Automattic\WooCommerce\Internal\Inventory\LocationStockInstaller
 * @covers \Automattic\WooCommerce\Internal\Inventory\LocationStockService
 */
class LocationStockInstallerTest extends LocationStockTestCase {

	/**
	 * @testdox Should not initialize POS locations when the feature is disabled.
	 */
	public function test_locations_are_not_initialized_when_feature_is_disabled(): void {
		update_option( InventoryController::FEATURE_OPTION, 'no' );
		delete_option( LocationStockInstaller::POS_LOCATION_CREATED_OPTION );
		$this->configure_pos_locations( array() );

		$this->installer->maybe_initialize_locations();

		$this->assertSame( array(), $this->service->get_locations() );
		$this->assertSame( 'no', get_option( LocationStockInstaller::POS_LOCATION_CREATED_OPTION, 'no' ) );
	}

	/**
	 * @testdox Should initialize the default POS location when the feature is enabled.
	 */
	public function test_default_pos_location_is_initialized_when_feature_is_enabled(): void {
		delete_option( LocationStockInstaller::POS_LOCATION_CREATED_OPTION );
		$this->configure_pos_locations( array() );

		$this->installer->maybe_initialize_locations();

		$this->assertSame( 'POS', $this->service->get_location( LocationStockService::LOCATION_POS )['name'] );
		$this->assertSame( 'yes', get_option( LocationStockInstaller::POS_LOCATION_CREATED_OPTION ) );
	}

	/**
	 * @testdox Should seed the default POS location from the store address.
	 */
	public function test_default_pos_location_uses_store_address(): void {
		$previous_address         = get_option( 'woocommerce_store_address', null );
		$previous_address_2       = get_option( 'woocommerce_store_address_2', null );
		$previous_city            = get_option( 'woocommerce_store_city', null );
		$previous_postcode        = get_option( 'woocommerce_store_postcode', null );
		$previous_default_country = get_option( 'woocommerce_default_country', null );

		try {
			update_option( 'woocommerce_store_address', '60 29th Street' );
			update_option( 'woocommerce_store_address_2', 'Suite 200' );
			update_option( 'woocommerce_store_city', 'San Francisco' );
			update_option( 'woocommerce_store_postcode', '94110' );
			update_option( 'woocommerce_default_country', 'US:CA' );

			delete_option( LocationStockInstaller::POS_LOCATION_CREATED_OPTION );
			$this->configure_pos_locations( array() );

			$this->installer->maybe_initialize_locations();

			$location = $this->service->get_location( LocationStockService::LOCATION_POS );

			$this->assertSame( '60 29th Street', $location['address_1'] );
			$this->assertSame( 'Suite 200', $location['address_2'] );
			$this->assertSame( 'San Francisco', $location['city'] );
			$this->assertSame( 'CA', $location['state'] );
			$this->assertSame( '94110', $location['postcode'] );
			$this->assertSame( 'US', $location['country'] );
		} finally {
			$this->restore_option( 'woocommerce_store_address', $previous_address );
			$this->restore_option( 'woocommerce_store_address_2', $previous_address_2 );
			$this->restore_option( 'woocommerce_store_city', $previous_city );
			$this->restore_option( 'woocommerce_store_postcode', $previous_postcode );
			$this->restore_option( 'woocommerce_default_country', $previous_default_country );
		}
	}

	/**
	 * @testdox Should not overwrite existing configured POS locations.
	 */
	public function test_existing_locations_are_not_overwritten(): void {
		update_option( LocationStockInstaller::POS_LOCATION_CREATED_OPTION, 'yes' );
		$this->configure_pos_locations(
			array(
				array(
					'slug' => 'front-counter',
					'name' => 'Front counter',
				),
			)
		);

		$this->installer->maybe_initialize_locations();

		$this->assertNull( $this->service->get_location( LocationStockService::LOCATION_POS ) );
		$this->assertSame( 'Front counter', $this->service->get_location( 'front-counter' )['name'] );
	}
}
