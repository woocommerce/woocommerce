<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiLocationInventory;

use Automattic\WooCommerce\Internal\Locations\Location;
use Automattic\WooCommerce\Internal\Locations\LocationsController;
use Automattic\WooCommerce\Internal\MultiLocationInventory\PosLocationSeeder;
use WC_Unit_Test_Case;

/**
 * Tests for PosLocationSeeder.
 */
class PosLocationSeederTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var PosLocationSeeder
	 */
	private $sut;

	/**
	 * Locations controller.
	 *
	 * @var LocationsController
	 */
	private $locations_controller;

	/**
	 * Set up fixtures shared by the whole test class.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		$controller = wc_get_container()->get( LocationsController::class );
		// Registers the 'location' data store filter.
		$controller->register();
		// DDL outside the per-test transaction.
		$controller->create_tables();
	}

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->locations_controller = wc_get_container()->get( LocationsController::class );
		$this->sut                  = wc_get_container()->get( PosLocationSeeder::class );
		// Isolate the SUT: detach the bootstrap-registered listener so toggling the feature option
		// below does not auto-seed. These tests drive maybe_seed() explicitly.
		remove_all_actions( LocationsController::LOCATIONS_READY_ACTION );
		update_option( 'woocommerce_feature_multi_location_inventory_enabled', 'yes' );
	}

	/**
	 * @testdox maybe_seed() creates a single pos location from store settings.
	 */
	public function test_seed_creates_single_pos_location_from_store_settings(): void {
		update_option( 'woocommerce_store_address', '123 Test St' );
		update_option( 'woocommerce_store_city', 'Testville' );
		update_option( 'woocommerce_store_postcode', 'TS1 1AA' );
		update_option( 'woocommerce_default_country', 'GB' );
		update_option( 'woocommerce_pos_store_name', 'My POS' );

		$this->sut->maybe_seed();

		$id = $this->locations_controller->get_default_location_id( 'pos' );
		$this->assertGreaterThan( 0, $id );

		$location = new Location( $id );
		$this->assertEquals( 'pos', $location->get_type() );
		$this->assertEquals( 'My POS', $location->get_name() );
		$this->assertEquals( '123 Test St', $location->get_address_1() );
		$this->assertEquals( 'Testville', $location->get_city() );
		$this->assertEquals( 'GB', $location->get_country() );
	}

	/**
	 * @testdox maybe_seed() is idempotent.
	 */
	public function test_seed_is_idempotent(): void {
		global $wpdb;

		$this->sut->maybe_seed();
		$this->sut->maybe_seed();

		$count = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE type = %s', $this->locations_controller->get_table_name(), 'pos' )
		);
		$this->assertEquals( 1, $count );
	}

	/**
	 * @testdox maybe_seed() does nothing when the inventory feature is disabled.
	 */
	public function test_seed_skipped_when_feature_disabled(): void {
		update_option( 'woocommerce_feature_multi_location_inventory_enabled', 'no' );

		$this->sut->maybe_seed();

		$this->assertSame( 0, $this->locations_controller->get_default_location_id( 'pos' ) );
	}

	/**
	 * @testdox The locations-ready action triggers seeding.
	 */
	public function test_locations_ready_action_triggers_seed(): void {
		$this->sut->register();

		/**
		 * Fires when the wc_locations table is ready. Defined in LocationsController.
		 *
		 * @since 11.0.0
		 */
		do_action( LocationsController::LOCATIONS_READY_ACTION );

		$this->assertGreaterThan( 0, $this->locations_controller->get_default_location_id( 'pos' ) );
	}
}
