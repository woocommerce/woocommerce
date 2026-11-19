<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Notes;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Internal\Admin\Notes\TrackingOptIn;
use WC_Unit_Test_Case;

/**
 * Tests for TrackingOptIn.
 */
class TrackingOptInTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should opt in via Action Scheduler only, without scheduling a WP-Cron first send.
	 */
	public function test_opt_in_schedules_action_scheduler_not_wp_cron(): void {
		update_option( 'woocommerce_allow_tracking', 'no' );
		wp_clear_scheduled_hook( 'woocommerce_tracker_send_event' );
		as_unschedule_all_actions( 'woocommerce_tracker_send_event_wrapper', array(), 'woocommerce' );

		$note = new Note();
		$note->set_name( TrackingOptIn::NOTE_NAME );

		( new TrackingOptIn() )->opt_in_to_tracking( $note );

		$this->assertSame( 'yes', get_option( 'woocommerce_allow_tracking' ) );
		$this->assertTrue(
			as_has_scheduled_action( 'woocommerce_tracker_send_event_wrapper' ),
			'Opting in via the inbox note should schedule the Action Scheduler wrapper.'
		);
		$this->assertFalse(
			wp_next_scheduled( 'woocommerce_tracker_send_event', array( true ) ),
			'Opting in via the inbox note must not schedule a legacy WP-Cron first send.'
		);
		$this->assertFalse(
			wp_next_scheduled( 'woocommerce_tracker_send_event' ),
			'Opting in via the inbox note must not schedule any WP-Cron tracker send.'
		);
	}
}
