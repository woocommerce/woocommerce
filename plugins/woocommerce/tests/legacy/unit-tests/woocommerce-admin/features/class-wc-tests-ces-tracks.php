<?php
/**
 * Customer Effort Score Survey Tests.
 *
 * @package Automattic\WooCommerce\Admin\Features
 */

use Automattic\WooCommerce\Internal\Admin\CustomerEffortScoreTracks;

// CustomerEffortScoreTracks only works in wp-admin, so let's fake it.
class CurrentScreenMock {
	public function in_admin() {
	    return true;
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
