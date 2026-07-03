<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Locations;

use Automattic\WooCommerce\Internal\Locations\Location;
use Automattic\WooCommerce\Internal\Locations\LocationsController;
use WC_Unit_Test_Case;

/**
 * Tests for LocationsController.
 */
class LocationsControllerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var LocationsController
	 */
	private $sut;

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
		$this->sut = wc_get_container()->get( LocationsController::class );
	}

	/**
	 * @testdox create_tables() creates wc_locations (without is_default) with a key on type.
	 */
	public function test_create_tables_creates_table_with_type_key(): void {
		global $wpdb;

		$this->assertTrue( $this->sut->tables_exist(), 'wc_locations should exist after creation.' );

		$create_sql = $wpdb->get_var( $wpdb->prepare( 'SHOW CREATE TABLE %i', $this->sut->get_table_name() ), 1 );
		$this->assertStringNotContainsString( '`is_default`', $create_sql, 'is_default column must not exist.' );
		$this->assertStringContainsString( 'KEY `type`', $create_sql, 'There must be a key on type.' );
		$this->assertStringContainsString( '`date_deleted_gmt`', $create_sql, 'Soft-delete column must exist.' );
		$this->assertStringContainsString( '`date_modified_gmt`', $create_sql, 'Modified column must exist.' );
	}

	/**
	 * @testdox maybe_seed_default_location() creates a single pos location from store settings.
	 */
	public function test_seed_creates_single_pos_location_from_store_settings(): void {
		update_option( 'woocommerce_store_address', '123 Test St' );
		update_option( 'woocommerce_store_city', 'Testville' );
		update_option( 'woocommerce_store_postcode', 'TS1 1AA' );
		update_option( 'woocommerce_default_country', 'GB' );
		update_option( 'woocommerce_pos_store_name', 'My POS' );

		$this->sut->maybe_seed_default_location();

		$id = $this->sut->get_default_location_id( 'pos' );
		$this->assertGreaterThan( 0, $id );

		$location = new Location( $id );
		$this->assertEquals( 'pos', $location->get_type() );
		$this->assertEquals( 'My POS', $location->get_name() );
		$this->assertEquals( '123 Test St', $location->get_address_1() );
		$this->assertEquals( 'Testville', $location->get_city() );
		$this->assertEquals( 'GB', $location->get_country() );
	}

	/**
	 * @testdox maybe_seed_default_location() is idempotent.
	 */
	public function test_seed_is_idempotent(): void {
		global $wpdb;

		$this->sut->maybe_seed_default_location();
		$this->sut->maybe_seed_default_location();

		$count = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE type = %s', $this->sut->get_table_name(), 'pos' )
		);
		$this->assertEquals( 1, $count );
	}

	/**
	 * @testdox set_default_location() then get_default_location_id() returns the location that was set.
	 */
	public function test_set_default_location_then_get_returns_it(): void {
		$location = new Location();
		$location->set_name( 'Manually Set Default' );
		$location->set_type( 'pos' );
		$location->save();

		$this->sut->set_default_location( 'pos', $location->get_id() );

		$this->assertSame( $location->get_id(), $this->sut->get_default_location_id( 'pos' ) );
	}

	/**
	 * @testdox get_default_location_id() returns 0 and self-heals when the mapped location is soft-deleted.
	 */
	public function test_get_default_location_id_self_heals_dangling_entry(): void {
		$location = new Location();
		$location->set_name( 'Dangling Default' );
		$location->set_type( 'pos' );
		$location->save();

		$this->sut->set_default_location( 'pos', $location->get_id() );
		$location->delete();

		$this->assertSame( 0, $this->sut->get_default_location_id( 'pos' ), 'A soft-deleted default must not be discoverable.' );
	}

	/**
	 * @testdox is_enabled() is true only when a registered consumer feature is on.
	 */
	public function test_is_enabled_tracks_consumer_features(): void {
		add_filter(
			'woocommerce_location_feature_consumers',
			static function ( $ids ) {
				$ids[] = 'multi_location_inventory';
				return $ids;
			}
		);

		update_option( 'woocommerce_feature_multi_location_inventory_enabled', 'no' );
		$this->assertFalse( $this->sut->is_enabled() );

		update_option( 'woocommerce_feature_multi_location_inventory_enabled', 'yes' );
		$this->assertTrue( $this->sut->is_enabled() );
	}

	/**
	 * @testdox Enabling a consumer feature installs and seeds the default location.
	 */
	public function test_on_feature_enabled_changed_installs_when_enabled(): void {
		add_filter(
			'woocommerce_location_feature_consumers',
			static function ( $ids ) {
				$ids[] = 'multi_location_inventory';
				return $ids;
			}
		);
		delete_option( LocationsController::TABLES_CREATED_OPTION );
		update_option( 'woocommerce_feature_multi_location_inventory_enabled', 'yes' );

		// Idempotent explicit call: once the bootstrap wires the listener, the option
		// write above also auto-fires this handler via FeaturesController.
		$this->sut->on_feature_enabled_changed( 'multi_location_inventory', true );

		$this->assertEquals( 'yes', get_option( LocationsController::TABLES_CREATED_OPTION ) );
		$this->assertGreaterThan( 0, $this->sut->get_default_location_id( 'pos' ) );
	}

	/**
	 * @testdox on_feature_enabled_changed is a no-op when disabled or for a non-consumer feature.
	 */
	public function test_on_feature_enabled_changed_is_noop_when_disabled_or_other_feature(): void {
		add_filter(
			'woocommerce_location_feature_consumers',
			static function ( $ids ) {
				$ids[] = 'multi_location_inventory';
				return $ids;
			}
		);
		delete_option( LocationsController::TABLES_CREATED_OPTION );
		update_option( 'woocommerce_feature_multi_location_inventory_enabled', 'no' );

		// Disabled transition is a no-op.
		$this->sut->on_feature_enabled_changed( 'multi_location_inventory', false );
		// Non-consumer feature is a no-op.
		$this->sut->on_feature_enabled_changed( 'some_other_feature', true );

		$this->assertFalse( (bool) get_option( LocationsController::TABLES_CREATED_OPTION ) );
	}

	/**
	 * @testdox add_table_to_install_list() registers wc_locations for uninstall.
	 */
	public function test_add_table_to_install_list(): void {
		$tables = $this->sut->add_table_to_install_list( array() );
		$this->assertContains( $this->sut->get_table_name(), $tables );
	}

	/**
	 * @testdox The bootstrap registers the location data store and the inventory consumer.
	 */
	public function test_bootstrap_wires_controllers(): void {
		/**
		 * Filters the feature ids that consume the locations domain. Defined in
		 * LocationsController::get_consumer_feature_ids().
		 *
		 * @since 11.0.0
		 */
		$consumers = apply_filters( 'woocommerce_location_feature_consumers', array() );
		$this->assertContains( 'multi_location_inventory', $consumers, 'Inventory must self-register as a consumer.' );

		/**
		 * Filters the registered WC_Data_Store classes. Defined in WC_Data_Store::__construct().
		 *
		 * @since 11.0.0
		 */
		$stores = apply_filters( 'woocommerce_data_stores', array() );
		$this->assertArrayHasKey( 'location', $stores, 'Location data store must be registered.' );
	}
}
