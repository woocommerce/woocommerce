<?php
/**
 * Tests for TrackingOptIn.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Notes;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Internal\Admin\Notes\TrackingOptIn;
use WC_Unit_Test_Case;

/**
 * Tests for the tracking opt-in note action.
 */
class TrackingOptInTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var TrackingOptIn
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new TrackingOptIn();
		update_option( 'woocommerce_allow_tracking', 'no' );
		as_unschedule_all_actions( 'woocommerce_tracker_send_event_wrapper' );
		wp_clear_scheduled_hook( 'woocommerce_tracker_send_event' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		as_unschedule_all_actions( 'woocommerce_tracker_send_event_wrapper' );
		wp_clear_scheduled_hook( 'woocommerce_tracker_send_event' );
		delete_option( 'woocommerce_allow_tracking' );

		parent::tearDown();
	}

	/**
	 * @testdox The note action opts in through the option hook and leaves no raw tracker cron event.
	 */
	public function test_note_action_schedules_callback_safe_recurrence_without_raw_cron(): void {
		$note = new Note();
		$note->set_name( TrackingOptIn::NOTE_NAME );

		$this->sut->opt_in_to_tracking( $note );

		$this->assertSame( 'yes', get_option( 'woocommerce_allow_tracking' ) );
		$this->assertTrue(
			as_has_scheduled_action( 'woocommerce_tracker_send_event_wrapper', array(), 'woocommerce' ),
			'The option update should schedule the callback-safe recurrence.'
		);
		$this->assertFalse(
			wp_next_scheduled( 'woocommerce_tracker_send_event' ),
			'The note action should not schedule the raw tracker hook in WP-Cron.'
		);
	}
}
