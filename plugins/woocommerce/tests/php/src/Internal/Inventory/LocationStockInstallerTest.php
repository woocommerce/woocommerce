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
 */
class LocationStockInstallerTest extends LocationStockTestCase {

	/**
	 * @testdox Should not create inventory tables when the feature is disabled.
	 */
	public function test_tables_are_not_created_when_feature_is_disabled(): void {
		update_option( InventoryController::FEATURE_OPTION, 'no' );
		delete_option( LocationStockInstaller::TABLES_CREATED_OPTION );
		$this->drop_inventory_tables();

		$this->installer->maybe_create_db_tables();

		$this->assertFalse( $this->service->tables_exist(), 'Inventory tables should not exist when the feature is disabled.' );
		$this->assertSame( 'no', get_option( LocationStockInstaller::TABLES_CREATED_OPTION, 'no' ) );
	}

	/**
	 * @testdox Should verify dbDelta results and configure the POS location when the feature is enabled.
	 */
	public function test_tables_are_created_and_verified_when_feature_is_enabled(): void {
		delete_option( LocationStockInstaller::TABLES_CREATED_OPTION );
		$this->drop_inventory_tables();

		$this->installer->maybe_create_db_tables();

		$this->assertTrue( $this->service->tables_exist(), 'Inventory tables should exist after dbDelta runs.' );
		$this->assertSame( 'yes', get_option( LocationStockInstaller::TABLES_CREATED_OPTION ) );
		$this->assertSame( 'POS', $this->service->get_location( LocationStockService::LOCATION_POS )['name'] );
		$this->assertNull( $this->service->get_location( 'web' ), 'The POS-only milestone should not seed a web location row.' );
	}

	/**
	 * @testdox Should configure the POS location when the inventory tables already exist.
	 */
	public function test_existing_tables_configure_pos_location_when_feature_is_enabled(): void {
		update_option( LocationStockInstaller::TABLES_CREATED_OPTION, 'yes' );
		delete_option( LocationStockInstaller::POS_LOCATION_CREATED_OPTION );
		$this->remove_pos_location();

		$this->installer->maybe_create_db_tables();

		$this->assertSame( 'POS', $this->service->get_location( LocationStockService::LOCATION_POS )['name'] );
	}
}
