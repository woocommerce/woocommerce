<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Locations;

use Automattic\WooCommerce\Internal\Locations\Location;
use Automattic\WooCommerce\Internal\Locations\LocationDataStore;
use Automattic\WooCommerce\Internal\Locations\LocationsController;
use WC_Unit_Test_Case;

/**
 * Tests for the Location WC_Data object and its data store.
 */
class LocationTest extends WC_Unit_Test_Case {

	/**
	 * Set up fixtures shared by the whole test class.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		$controller = wc_get_container()->get( LocationsController::class );
		// Registers the 'location' data store filter.
		$controller->register();
		// DDL must run outside the per-test transaction.
		$controller->create_tables();
	}

	/**
	 * @testdox A Location round-trips through create, read-by-id, and update.
	 */
	public function test_location_create_read_update_round_trip(): void {
		$location = new Location();
		$location->set_name( 'Warehouse One' );
		$location->set_type( 'pos' );
		$location->set_city( 'Leeds' );
		$location->save();

		$id = $location->get_id();
		$this->assertGreaterThan( 0, $id );

		$read = new Location( $id );
		$this->assertEquals( 'Warehouse One', $read->get_name() );
		$this->assertEquals( 'pos', $read->get_type() );
		$this->assertEquals( 'Leeds', $read->get_city() );

		$read->set_name( 'Renamed' );
		$read->save();
		$this->assertEquals( 'Renamed', ( new Location( $id ) )->get_name() );
	}

	/**
	 * @testdox set_type() rejects a value outside the allowlist.
	 */
	public function test_set_type_rejects_invalid_value(): void {
		$location = new Location();
		$this->expectException( \WC_Data_Exception::class );
		$location->set_type( 'warehouse' );
	}

	/**
	 * @testdox delete() soft-deletes: the row keeps its data and sets date_deleted.
	 */
	public function test_delete_is_soft(): void {
		$location = new Location();
		$location->set_name( 'To Delete' );
		$location->set_type( 'pos' );
		$location->save();
		$id = $location->get_id();

		$location->delete();

		$reloaded = new Location( $id );
		$this->assertEquals( 'To Delete', $reloaded->get_name(), 'Row must still exist after soft delete.' );
		$this->assertNotEmpty( $reloaded->get_date_deleted(), 'date_deleted must be set.' );
	}

	/**
	 * @testdox Dates round-trip as WC_DateTime objects and date_modified is populated on save.
	 */
	public function test_dates_round_trip_as_wc_datetime(): void {
		$location = new Location();
		$location->set_name( 'Dated' );
		$location->set_type( 'pos' );
		$location->save();

		$read = new Location( $location->get_id() );
		$this->assertInstanceOf( \WC_DateTime::class, $read->get_date_created() );
		$this->assertInstanceOf( \WC_DateTime::class, $read->get_date_modified() );
		$this->assertNull( $read->get_date_deleted() );
	}

	/**
	 * @testdox get_location_ids() returns active ids and excludes soft-deleted ones.
	 */
	public function test_get_location_ids_excludes_soft_deleted(): void {
		$data_store = new LocationDataStore();

		$kept = new Location();
		$kept->set_name( 'Kept' );
		$kept->set_type( 'pos' );
		$kept->save();

		$removed = new Location();
		$removed->set_name( 'Removed' );
		$removed->set_type( 'pos' );
		$removed->save();
		$removed->delete();

		$ids = $data_store->get_location_ids();
		$this->assertContains( $kept->get_id(), $ids );
		$this->assertNotContains( $removed->get_id(), $ids );
	}
}
