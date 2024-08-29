<?php

use Automattic\WooCommerce\Internal\Admin\Orders\PageController;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Class WC_Orders_Tracking_Test.
 */
class WC_Orders_Tracking_Test extends \WC_Unit_Test_Case {

	use HPOSToggleTrait;

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

		// Mock screen.
		$this->current_screen_backup = $GLOBALS['current_screen'] ?? null;
		$GLOBALS['current_screen']   = $this->get_screen_mock(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		if ( ! did_action( 'current_screen' ) ) {
			do_action( 'current_screen', $GLOBALS['current_screen'] ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		}

		$orders_tracking = new WC_Orders_Tracking();
		$orders_tracking->init();
		parent::setUp();

		$this->setup_cot();
	}

	/**
	 * Teardown test
	 *
	 * @return void
	 */
	public function tearDown(): void {
		update_option( 'woocommerce_allow_tracking', 'no' );
		if ( $this->current_screen_backup ) {
			$GLOBALS['current_screen'] = $this->current_screen_backup; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
		parent::tearDown();
		$this->clean_up_cot_setup();
		remove_all_filters( 'wc_allow_changing_orders_storage_while_sync_is_pending' );
	}

	/**
	 * Test wcadmin_orders_edit_status_change Tracks event.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $hpos_enabled Whether to test with HPOS enabled or not.
	 */
	public function test_orders_status_change( $hpos_enabled ) {
		$this->toggle_cot_authoritative( $hpos_enabled );
		$order = wc_create_order();
		$order->save();

		/* phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment */
		do_action( 'woocommerce_order_status_changed', $order->get_id(), 'pending', 'finished' );
		$this->assertRecordedTracksEvent( 'wcadmin_orders_edit_status_change' );
	}

	/**
	 * Test wcadmin_orders_view Tracks event.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $hpos_enabled Whether to test with HPOS enabled or not.
	 */
	public function test_orders_view( $hpos_enabled ) {
		$this->toggle_cot_authoritative( $hpos_enabled );
		$this->setup_screen( 'list' );

		/* phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment */
		// phpcs:disable WordPress.NamingConventions.ValidHookName.UseUnderscores
		do_action( $hpos_enabled ? 'load-woocommerce_page_wc-orders' : 'load-edit.php' );

		$this->assertRecordedTracksEvent( 'wcadmin_orders_view' );
	}

	/**
	 * Test wcadmin_orders_view_search Tracks event.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $hpos_enabled Whether to test with HPOS enabled or not.
	 */
	public function test_orders_search( $hpos_enabled ) {
		$this->toggle_cot_authoritative( $hpos_enabled );

		$_REQUEST['s'] = 'term';
		$this->setup_screen( 'list' );

		do_action( 'load-edit.php' );

		$this->assertRecordedTracksEvent( 'wcadmin_orders_view_search' );
	}

	/**
	 * Configure the screen as if it were the "list" orders screen.
	 */
	private function setup_screen() {
		$GLOBALS['current_screen']->post_type = 'shop_order';
		$GLOBALS['current_screen']->base      = 'edit';
		$_GET['action']                       = '';

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$GLOBALS['pagenow']     = 'admin.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$GLOBALS['plugin_page'] = 'wc-orders'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			add_filter( 'map_meta_cap', array( $this, 'allow_edit_shop_orders' ), 10, 3 );
			wc_get_container()->get( PageController::class )->setup();
			remove_filter( 'map_meta_cap', array( $this, 'allow_edit_shop_orders' ), 10 );
		}
	}

	/**
	 * Returns an object mocking what we need from `\WP_Screen`.
	 *
	 * @return object
	 */
	private function get_screen_mock() {
		$screen_mock = $this->getMockBuilder( stdClass::class )->setMethods( array( 'in_admin', 'add_option' ) )->getMock();
		$screen_mock->method( 'in_admin' )->willReturn( true );
		foreach ( array( 'id', 'base', 'action', 'post_type' ) as $key ) {
			$screen_mock->{$key} = '';
		}

		return $screen_mock;
	}

	/**
	 * Used to temporarily grant the current user the 'edit_shop_orders' permission.
	 *
	 * @param string[] $caps     Primitive capabilities required for the user.
	 * @param string   $cap      Capability being checked.
	 * @param int      $user_id  The user ID.
	 * @return array
	 */
	public function allow_edit_shop_orders( $caps, $cap, $user_id ) {
		return ( 0 === $user_id && 'edit_shop_orders' === $cap ) ? array() : $caps;
	}

}
