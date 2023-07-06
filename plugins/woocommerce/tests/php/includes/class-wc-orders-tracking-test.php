<?php

/**
 * Class WC_Tracks_Test.
 */
class WC_Orders_Tracking_Test extends \WC_Unit_Test_Case {

	/**
	 * @var object Backup object of $GLOBALS['current_screen'];
	 */
	private $current_screen_backup;

	/**
	 * Set up test
	 *
	 * @return void
	 */
	public function setUp(): void {
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-orders-tracking.php';
		update_option( 'woocommerce_allow_tracking', 'yes' );
		if ( isset( $GLOBALS['current_screen'] ) ) {
			$this->current_screen_backup = $GLOBALS['current_screen'];
		}
		$GLOBALS['current_screen'] = new CurrentScreenMock();
		$orders_tracking           = new WC_Orders_Tracking();
		$orders_tracking->init();
		parent::setUp();
	}

	public function tearDown(): void {
		update_option( 'woocommerce_allow_tracking', 'no' );
		if ( $this->current_screen_backup ) {
			$GLOBALS['current_screen'] = $this->current_screen_backup;
		}
		parent::tearDown();
	}

	/**
	 * Test that
	 */
	public function test_orders_view() {
		$_GET['post_type'] = 'shop_order';
		/* phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment */
		do_action( 'load-edit.php' );
		$this->assertRecordedTracksEvent( 'wcadmin_orders_view' );
	}

	public function test_orders_search() {
		$GLOBALS['current_screen']->id = 'edit-shop_order';
		/* phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment */
		apply_filters( 'woocommerce_shop_order_search_results', array( 'order_id1' ), 'term', array() );

		$this->assertRecordedTracksEvent( 'wcadmin_orders_view_search' );
	}

}
