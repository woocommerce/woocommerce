<?php

/**
 * Test that orders are synced to stats table.
 */
class OrdersSchedulerTest extends \WC_Admin_Tests_Reports_Orders_Stats {
	use \Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;

	/**
	 * Setup HPOS.
	 */
	public function setUp(): void {
		parent::setUp();
//		$this->legacy_order_scheduler_test = new WC_Admin_Tests_Reports_Orders_Stats();
//		$this->legacy_report_importer_test = new WC_Admin_Tests_API_Reports_Import();
		add_filter( 'woocommerce_analytics_report_should_use_cache', '__return_false' );
		$this->setup_cot();
		$this->disable_cot_sync();
		$this->toggle_cot( true );
//		$this->legacy_report_importer_test->setUp();
	}

	/**
	 * Teardown HPOS.
	 */
	public function tearDown(): void {
		$this->clean_up_cot_setup();
		$this->toggle_cot( false );
		remove_filter( 'woocommerce_analytics_report_should_use_cache', '__return_false' );
//		$this->legacy_report_importer_test->tearDown();
		parent::tearDown();
	}

//	/**
//	 * @testDox Test that orders are imported to stats table with HPOS enabled.
//	 */
//	public function test_import_params() {
//		$this->legacy_report_importer_test->test_import_params();
//	}

}
