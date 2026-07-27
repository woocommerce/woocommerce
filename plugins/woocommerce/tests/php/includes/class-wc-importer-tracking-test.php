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
		parent::setUp();
		$this->clear_tracks_events();

		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-importer-tracking.php';
		update_option( 'woocommerce_allow_tracking', 'yes' );
		$importer_tracking = new WC_Importer_Tracking();
		$importer_tracking->init();
	}

	/**
	 * Teardown test
	 *
	 * @return void
	 */
	public function tearDown(): void {
		update_option( 'woocommerce_allow_tracking', 'no' );
		unset(
			$_REQUEST['step'],
			$_REQUEST['_wpnonce'],
			$_REQUEST['nonce'],
			$_GET['products-imported'],
			$_GET['products-imported-variations'],
			$_GET['products-updated'],
			$_GET['products-failed'],
			$_GET['products-skipped']
		);
		parent::tearDown();
	}

	/**
	 * The completion Tracks event should fire on the done step, which the
	 * product importer reaches via a URL carrying the `_wpnonce` query arg.
	 */
	public function test_import_complete_is_recorded_with_wpnonce() {
		$_REQUEST['step']     = 'done';
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'woocommerce-csv-importer' );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'product_page_product_importer' );

		$this->assertRecordedTracksEvent( 'wcadmin_product_import_complete' );
	}

	/**
	 * The completion Tracks event should record the import result counts taken
	 * from the query args appended to the done URL.
	 */
	public function test_import_complete_records_result_counts() {
		$_REQUEST['step']                     = 'done';
		$_REQUEST['_wpnonce']                 = wp_create_nonce( 'woocommerce-csv-importer' );
		$_GET['products-imported']            = '5';
		$_GET['products-imported-variations'] = '3';
		$_GET['products-updated']             = '2';
		$_GET['products-failed']              = '1';
		$_GET['products-skipped']             = '4';

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'product_page_product_importer' );

		$events = $this->get_tracks_events( 'wcadmin_product_import_complete' );
		$this->assertNotEmpty( $events );
		$event = end( $events );
		$this->assertEquals( 5, $event->imported );
		$this->assertEquals( 3, $event->imported_variations );
		$this->assertEquals( 2, $event->updated );
		$this->assertEquals( 1, $event->failed );
		$this->assertEquals( 4, $event->skipped );
	}

	/**
	 * Without a nonce on the request the completion event must not fire.
	 */
	public function test_import_complete_not_recorded_without_nonce() {
		$_REQUEST['step'] = 'done';

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'product_page_product_importer' );

		$this->assertNotRecordedTracksEvent( 'wcadmin_product_import_complete' );
	}
}
