<?php

/**
 * Class WC_Product_CSV_Importer_Controller_Test
 *
 * Tests to ensure that the CSV product importer works as expected.
 */
class WC_Product_CSV_Importer_Controller_Test extends WC_Unit_Test_Case {

	/**
	 * Load up the importer classes since they aren't loaded by default.
	 */
	public function setUp() {
		parent::setUp();

		$bootstrap = WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/import/class-wc-product-csv-importer.php';
		require_once $bootstrap->plugin_dir . '/includes/admin/importers/class-wc-product-csv-importer-controller.php';
	}

	/**
	 * Tests that the automatic mapping is case insensitive so that columns can be matched more easily.
	 */
	public function test_that_auto_mapping_is_case_insensitive() {
		// Allow us to call the protected method.
		$class  = new ReflectionClass( WC_Product_CSV_Importer_Controller::class );
		$method = $class->getMethod( 'auto_map_columns' );
		$method->setAccessible( true );

		$controller = new WC_Product_CSV_Importer_Controller();

		// Test a few different casing formats first.
		$columns = $method->invoke( $controller, array( 'Name', 'Type' ) );
		$this->assertEquals(
			array(
				0 => 'name',
				1 => 'type',
			),
			$columns
		);
		$columns = $method->invoke( $controller, array( 'NAME', 'tYpE' ) );
		$this->assertEquals(
			array(
				0 => 'name',
				1 => 'type',
			),
			$columns
		);

		// Make sure that the case sensitivity doesn't squash the meta keys.
		$columns = $method->invoke( $controller, array( 'Meta: _TESTING', 'Meta: _testing' ) );
		$this->assertEquals(
			array(
				0 => 'meta:_TESTING',
				1 => 'meta:_testing',
			),
			$columns
		);
	}
}
