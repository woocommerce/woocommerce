<?php

/**
 * Class WC_Importer_Tracking_Test.
 */
class WC_Importer_Tracking_Test extends \WC_Unit_Test_Case {
	/**
	 * Set up test
	 *
	 * @return void
	 */
	public function setUp(): void {
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-importer-tracking.php';
		update_option( 'woocommerce_allow_tracking', 'yes' );
		$importer_tracking = new WC_Importer_Tracking();
		$importer_tracking->init();
		parent::setUp();
	}

	/**
	 * Teardown test
	 *
	 * @return void
	 */
	public function tearDown(): void {
		update_option( 'woocommerce_allow_tracking', 'no' );
		parent::tearDown();
	}

	/**
	 * Test wcadmin_product_import_complete Tracks event
	 */
	public function test_import_complete() {
		$_REQUEST['step']  = 'done';
		$_REQUEST['nonce'] = 'nonce';
		/* phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment */
		do_action( 'product_page_product_importer' );
		$this->assertRecordedTracksEvent( 'wcadmin_product_import_complete' );
	}
}
