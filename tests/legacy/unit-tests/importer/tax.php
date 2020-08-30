<?php

/**
 * Meta
 * @package WooCommerce\Tests\Importer
 */
class WC_Tests_Tax_CSV_Importer extends WC_Unit_Test_Case {

	/**
	 * Test CSV file path.
	 *
	 * @var string
	 */
	protected $csv_file = '';

	/**
	 * Load up the importer classes since they aren't loaded by default.
	 */
	public function setUp() {
		parent::setUp();

		$this->csv_file = dirname( __FILE__ ) . '/sample_tax_rates.csv';

		$bootstrap = WC_Unit_Tests_Bootstrap::instance();
		require_once ABSPATH . '/wp-admin/includes/class-wp-importer.php';
		require_once $bootstrap->plugin_dir . '/includes/admin/importers/class-wc-tax-rate-importer.php';
	}

	/**
	 * Test import.
	 * @since 3.1.0
	 */
	public function test_import() {
		global $wpdb;
		$importer = new WC_Tax_Rate_Importer();
		ob_start();
		$importer->import( $this->csv_file );
		ob_end_clean();
		$rate_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$this->assertEquals( 5, $rate_count );
	}
}
