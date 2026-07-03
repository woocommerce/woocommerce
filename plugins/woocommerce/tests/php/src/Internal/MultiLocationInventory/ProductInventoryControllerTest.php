<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiLocationInventory;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\MultiLocationInventory\ProductInventoryController;
use WC_Unit_Test_Case;

/**
 * Tests for multi-location inventory feature registration and controller wiring.
 */
class ProductInventoryControllerTest extends WC_Unit_Test_Case {

	/**
	 * Set up fixtures shared by all tests in this class.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		wc_get_container()->get( ProductInventoryController::class )->create_tables();
	}

	/**
	 * @testdox multi_location_inventory feature is registered, non-experimental, and off by default.
	 */
	public function test_feature_is_registered_non_experimental_and_off_by_default(): void {
		$controller = wc_get_container()->get( FeaturesController::class );
		$definition = $controller->get_feature_definition( 'multi_location_inventory' );

		$this->assertIsArray( $definition, 'Feature definition should be registered.' );
		$this->assertFalse( (bool) $definition['is_experimental'], 'Feature must not be experimental.' );
		$this->assertTrue( (bool) $definition['disable_ui'], 'Feature must hide its Features-screen UI.' );
		$this->assertFalse( $controller->feature_is_enabled( 'multi_location_inventory' ), 'Feature must be off by default.' );
	}

	/**
	 * @testdox create_tables() creates wc_product_inventory as InnoDB with the unique triple key.
	 */
	public function test_create_tables_creates_innodb_table_with_unique_triple(): void {
		global $wpdb;

		$controller = wc_get_container()->get( ProductInventoryController::class );
		$controller->create_tables();

		$this->assertTrue( $controller->tables_exist() );

		$engine = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s',
				$controller->get_table_name()
			)
		);
		$this->assertEquals( 'InnoDB', $engine );

		$create_sql = $wpdb->get_var( $wpdb->prepare( 'SHOW CREATE TABLE %i', $controller->get_table_name() ), 1 );
		$this->assertStringContainsString( 'UNIQUE KEY', $create_sql );
		$this->assertStringContainsString( 'product_variation_location', $create_sql );
	}

	/**
	 * @testdox It registers multi_location_inventory as a location consumer.
	 */
	public function test_registers_as_location_consumer(): void {
		$controller = wc_get_container()->get( ProductInventoryController::class );
		$ids        = $controller->register_as_location_consumer( array() );
		$this->assertContains( 'multi_location_inventory', $ids );
	}

	/**
	 * @testdox Enabling this feature installs the inventory table (latch set).
	 */
	public function test_on_feature_enabled_changed_installs_for_this_feature(): void {
		$controller = wc_get_container()->get( ProductInventoryController::class );
		delete_option( ProductInventoryController::TABLES_CREATED_OPTION );
		update_option( 'woocommerce_feature_multi_location_inventory_enabled', 'yes' );

		// Idempotent explicit call (post-bootstrap the option write above also auto-fires this).
		$controller->on_feature_enabled_changed( 'multi_location_inventory', true );

		$this->assertEquals( 'yes', get_option( ProductInventoryController::TABLES_CREATED_OPTION ) );
	}

	/**
	 * @testdox on_feature_enabled_changed ignores unrelated features.
	 */
	public function test_on_feature_enabled_changed_ignores_other_features(): void {
		$controller = wc_get_container()->get( ProductInventoryController::class );
		delete_option( ProductInventoryController::TABLES_CREATED_OPTION );
		update_option( 'woocommerce_feature_multi_location_inventory_enabled', 'no' );

		$controller->on_feature_enabled_changed( 'some_other_feature', true );

		$this->assertFalse( (bool) get_option( ProductInventoryController::TABLES_CREATED_OPTION ) );
	}
}
