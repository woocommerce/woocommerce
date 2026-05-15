<?php
/**
 * Customer Effort Score Survey Tests.
 *
 * @package Automattic\WooCommerce\Admin\Features
 */

use Automattic\WooCommerce\Internal\Admin\CustomerEffortScoreTracks;

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound
if ( ! class_exists( 'CurrentScreenMock' ) ) {
	/**
	 * Class CurrentScreenMock
	 */
	class CurrentScreenMock {
		/**
		 * CustomerEffortScoreTracks only works in wp-admin, so let's fake it.
		 */
		public function in_admin() {
			return true;
		}
	}
}

/**
 * Class WC_Admin_Tests_CES_Tracks
 */
class WC_Admin_Tests_CES_Tracks extends WC_Unit_Test_Case {

	/**
	 * @var CustomerEffortScoreTracks
	 */
	private $ces;

	/**
	 * @var object Backup object of $GLOBALS['current_screen'];
	 */
	private $current_screen_backup;

	/**
	 * Overridden setUp method from PHPUnit
	 */
	public function setUp(): void {
		parent::setUp();
		update_option( 'woocommerce_allow_tracking', 'yes' );
		if ( isset( $GLOBALS['current_screen'] ) ) {
			$this->current_screen_backup = $GLOBALS['current_screen'];
		}
		$GLOBALS['current_screen'] = new CurrentScreenMock();
	}

	public function tearDown(): void {
		parent::tearDown();
		if ( $this->current_screen_backup ) {
			$GLOBALS['current_screen'] = $this->current_screen_backup;
		}
		update_option( 'woocommerce_allow_tracking', 'no' );
	}

	/**
	 * Verify that it adds correct action to the queue on woocommerce_update_options action.
	 */
	public function test_updating_options_triggers_ces() {
		$ces = new CustomerEffortScoreTracks();

		do_action( 'woocommerce_update_options' );

		$queue_items = get_option( $ces::CES_TRACKS_QUEUE_OPTION_NAME, array() );
		$this->assertNotEmpty( $queue_items );

		$expected_queue_item = array_filter(
			$queue_items,
			function ( $item ) use ( $ces ) {
				return $ces::SETTINGS_CHANGE_ACTION_NAME === $item['action'];
			}
		);

		$this->assertCount( 1, $expected_queue_item );
	}

	/**
	 * Verify that the queue does not add duplicate item by checking
	 * action and label values.
	 */
	public function test_the_queue_does_not_allow_duplicate() {
		$ces = new CustomerEffortScoreTracks();

		// Fire the action twice to trigger the queueing process twice.
		do_action( 'woocommerce_update_options' );
		do_action( 'woocommerce_update_options' );

		$queue_items = get_option( $ces::CES_TRACKS_QUEUE_OPTION_NAME, array() );
		$this->assertNotEmpty( $queue_items );

		$expected_queue_item = array_filter(
			$queue_items,
			function ( $item ) use ( $ces ) {
				return $ces::SETTINGS_CHANGE_ACTION_NAME === $item['action'];
			}
		);

		$this->assertCount( 1, $expected_queue_item );
	}

	/**
	 * Verify that tasks performed using a mobile device are ignored.
	 */
	public function test_disabled_for_mobile() {
		add_filter( 'wp_is_mobile', '__return_true' );

		$ces = new CustomerEffortScoreTracks();

		do_action( 'woocommerce_update_options' );

		$queue_items = get_option( $ces::CES_TRACKS_QUEUE_OPTION_NAME, array() );

		$this->assertEmpty( $queue_items );
	}

	/**
	 * Helper to set the HPOS order edit page context for a test.
	 *
	 * @param string|null $page Value to set for $_GET['page'], or null to
	 *                          unset it.
	 * @return array{pagenow:string|null,page:string|null} Previous values for
	 *                          restoration in tearDown_hpos_context().
	 */
	private function setUp_hpos_context( $page = 'wc-orders' ) {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test helper: temporarily faking $pagenow to simulate the HPOS order edit page.
		$GLOBALS['pagenow'] = 'admin.php';
		$previous           = array(
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading $_GET in test helper to back up state.
			'page' => isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : null,
		);
		if ( null === $page ) {
			unset( $_GET['page'] );
		} else {
			$_GET['page'] = $page;
		}
		return $previous;
	}

	/**
	 * Restore $_GET['page'] to its pre-test value.
	 *
	 * @param array $previous The map returned by setUp_hpos_context().
	 */
	private function tearDown_hpos_context( $previous ) {
		if ( null === $previous['page'] ) {
			unset( $_GET['page'] );
		} else {
			$_GET['page'] = $previous['page'];
		}
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test helper: restoring $pagenow that this test temporarily modified.
		unset( $GLOBALS['pagenow'] );
	}

	/**
	 * Verify that the HPOS order save action enqueues the shop_order_update
	 * survey. This guards against the regression where switching to HPOS
	 * stopped firing the survey because it relied on transition_post_status,
	 * which is never invoked on the HPOS order edit screen.
	 */
	public function test_shop_order_update_triggers_ces_on_hpos_save() {
		$previous = $this->setUp_hpos_context( 'wc-orders' );
		delete_option( CustomerEffortScoreTracks::CES_TRACKS_QUEUE_OPTION_NAME );

		new CustomerEffortScoreTracks();

		/**
		 * Simulate the order save fired by HPOS Edit::handle_order_update.
		 *
		 * @since 10.9.0
		 */
		do_action( 'woocommerce_process_shop_order_meta', 123, null );

		$queue_items = get_option( CustomerEffortScoreTracks::CES_TRACKS_QUEUE_OPTION_NAME, array() );

		$this->tearDown_hpos_context( $previous );

		$shop_order_items = array_values(
			array_filter(
				$queue_items,
				function ( $item ) {
					return CustomerEffortScoreTracks::SHOP_ORDER_UPDATE_ACTION_NAME === $item['action'];
				}
			)
		);

		$this->assertCount( 1, $shop_order_items );
		$this->assertSame( 'woocommerce_page_wc-orders', $shop_order_items[0]['pagenow'] );
		$this->assertSame( 'woocommerce_page_wc-orders', $shop_order_items[0]['adminpage'] );
	}

	/**
	 * Verify that the HPOS-specific hook is only registered on the wc-orders
	 * admin page, so saving an order via some other code path does not
	 * accidentally enqueue the survey.
	 */
	public function test_shop_order_update_does_not_trigger_ces_outside_hpos_edit_page() {
		$previous = $this->setUp_hpos_context( null );
		delete_option( CustomerEffortScoreTracks::CES_TRACKS_QUEUE_OPTION_NAME );

		new CustomerEffortScoreTracks();

		/**
		 * Simulate the order save fired outside the HPOS edit page.
		 *
		 * @since 10.9.0
		 */
		do_action( 'woocommerce_process_shop_order_meta', 123, null );

		$queue_items = get_option( CustomerEffortScoreTracks::CES_TRACKS_QUEUE_OPTION_NAME, array() );

		$this->tearDown_hpos_context( $previous );

		$shop_order_items = array_filter(
			$queue_items,
			function ( $item ) {
				return CustomerEffortScoreTracks::SHOP_ORDER_UPDATE_ACTION_NAME === $item['action'];
			}
		);

		$this->assertCount( 0, $shop_order_items );
	}

	/**
	 * Verify that it adds `settings_area` prop.
	 */
	public function test_settings_area_included_in_event_props() {
		// Global assignment to mimic what's done in WC_Admin_Settings::save_settings.
		global $current_tab;
		$current_tab = 'test_tab';
		$ces         = new CustomerEffortScoreTracks();

		do_action( 'woocommerce_update_options' );

		$queue_items = get_option( $ces::CES_TRACKS_QUEUE_OPTION_NAME, array() );
		$this->assertNotEmpty( $queue_items );

		$expected_queue_item = array_filter(
			$queue_items,
			function ( $item ) use ( $ces ) {
				return $ces::SETTINGS_CHANGE_ACTION_NAME === $item['action'];
			}
		);

		// Remove global assignment.
		unset( $GLOBALS['current_tab'] );

		$this->assertCount( 1, $expected_queue_item );
		$this->assertEquals( 'test_tab', $expected_queue_item[0]['props']->settings_area );
	}
}
