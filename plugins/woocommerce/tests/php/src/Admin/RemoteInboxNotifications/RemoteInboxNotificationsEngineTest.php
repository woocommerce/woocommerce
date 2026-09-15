<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\RemoteInboxNotifications;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\RemoteInboxNotifications\RemoteInboxNotificationsEngine;
use WC_Unit_Test_Case;

/**
 * Tests for retiring store alerts whose end date has passed.
 *
 * @covers \Automattic\WooCommerce\Admin\RemoteInboxNotifications\RemoteInboxNotificationsEngine::retire_expired_alerts
 */
class RemoteInboxNotificationsEngineTest extends WC_Unit_Test_Case {

	private const PAST            = '2020-01-01 00:00:00';
	private const FUTURE          = '2999-01-01 00:00:00';
	private const SPECS_TRANSIENT = 'woocommerce_admin_remote_inbox_notifications_specs';

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			delete_transient( self::SPECS_TRANSIENT );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox An alert whose end date has passed stops showing.
	 * @dataProvider alert_type_provider
	 *
	 * @param string $type Note type.
	 */
	public function test_expired_alert_is_set_to_pending( string $type ): void {
		$note_id = $this->create_note( $type, Note::E_WC_ADMIN_NOTE_UNACTIONED, self::PAST );

		RemoteInboxNotificationsEngine::retire_expired_alerts();

		$this->assertSame(
			Note::E_WC_ADMIN_NOTE_PENDING,
			( new Note( $note_id ) )->get_status(),
			'An alert past its end date should be hidden.'
		);
	}

	/**
	 * Alert note types.
	 *
	 * @return array[]
	 */
	public function alert_type_provider(): array {
		return array(
			'error'  => array( Note::E_WC_ADMIN_NOTE_ERROR ),
			'update' => array( Note::E_WC_ADMIN_NOTE_UPDATE ),
		);
	}

	/**
	 * @testdox An alert is left alone until its end date passes.
	 */
	public function test_unexpired_alert_keeps_showing(): void {
		$note_id = $this->create_note( Note::E_WC_ADMIN_NOTE_UPDATE, Note::E_WC_ADMIN_NOTE_UNACTIONED, self::FUTURE );

		RemoteInboxNotificationsEngine::retire_expired_alerts();

		$this->assertSame(
			Note::E_WC_ADMIN_NOTE_UNACTIONED,
			( new Note( $note_id ) )->get_status(),
			'An alert that has not reached its end date should keep showing.'
		);
	}

	/**
	 * @testdox A note with no end date is left alone.
	 */
	public function test_note_without_an_end_date_keeps_showing(): void {
		$note_id = $this->create_note( Note::E_WC_ADMIN_NOTE_UPDATE, Note::E_WC_ADMIN_NOTE_UNACTIONED, null );

		RemoteInboxNotificationsEngine::retire_expired_alerts();

		$this->assertSame(
			Note::E_WC_ADMIN_NOTE_UNACTIONED,
			( new Note( $note_id ) )->get_status(),
			'A note with no end date has nothing to expire against.'
		);
	}

	/**
	 * @testdox An unreadable end date is ignored rather than hiding the alert.
	 */
	public function test_unreadable_end_date_keeps_showing(): void {
		$note_id = $this->create_note( Note::E_WC_ADMIN_NOTE_UPDATE, Note::E_WC_ADMIN_NOTE_UNACTIONED, 'whenever' );

		RemoteInboxNotificationsEngine::retire_expired_alerts();

		$this->assertSame(
			Note::E_WC_ADMIN_NOTE_UNACTIONED,
			( new Note( $note_id ) )->get_status(),
			'A date that cannot be read should not be treated as passed.'
		);
	}

	/**
	 * @testdox Notes that are not shown as an alert are left alone.
	 */
	public function test_other_note_types_are_left_alone(): void {
		$note_id = $this->create_note( Note::E_WC_ADMIN_NOTE_MARKETING, Note::E_WC_ADMIN_NOTE_UNACTIONED, self::PAST );

		RemoteInboxNotificationsEngine::retire_expired_alerts();

		$this->assertSame(
			Note::E_WC_ADMIN_NOTE_UNACTIONED,
			( new Note( $note_id ) )->get_status(),
			'Only error and update notes are shown as an alert, so only those expire.'
		);
	}

	/**
	 * @testdox An alert the merchant already acted on is not reopened or changed.
	 */
	public function test_actioned_alert_is_left_alone(): void {
		$note_id = $this->create_note( Note::E_WC_ADMIN_NOTE_UPDATE, Note::E_WC_ADMIN_NOTE_ACTIONED, self::PAST );

		RemoteInboxNotificationsEngine::retire_expired_alerts();

		$this->assertSame(
			Note::E_WC_ADMIN_NOTE_ACTIONED,
			( new Note( $note_id ) )->get_status(),
			'An actioned note is already hidden and should keep its status.'
		);
	}

	/**
	 * @testdox Notes from another source are left alone.
	 */
	public function test_notes_from_another_source_are_left_alone(): void {
		$note_id = $this->create_note( Note::E_WC_ADMIN_NOTE_UPDATE, Note::E_WC_ADMIN_NOTE_UNACTIONED, self::PAST, 'woocommerce-core' );

		RemoteInboxNotificationsEngine::retire_expired_alerts();

		$this->assertSame(
			Note::E_WC_ADMIN_NOTE_UNACTIONED,
			( new Note( $note_id ) )->get_status(),
			'Only notes from woocommerce.com carry a remote inbox end date.'
		);
	}

	/**
	 * @testdox The daily engine run retires expired alerts.
	 */
	public function test_run_retires_expired_alerts(): void {
		$note_id = $this->create_note( Note::E_WC_ADMIN_NOTE_UPDATE, Note::E_WC_ADMIN_NOTE_UNACTIONED, self::PAST );

		// An empty spec list for the current locale keeps run() from fetching the feed.
		set_transient( self::SPECS_TRANSIENT, array( get_user_locale() => array() ) );

		RemoteInboxNotificationsEngine::run();

		$this->assertSame(
			Note::E_WC_ADMIN_NOTE_PENDING,
			( new Note( $note_id ) )->get_status(),
			'run() should retire an expired alert even when no specs are served.'
		);
	}

	/**
	 * Create an admin note.
	 *
	 * @param string      $type           Note type.
	 * @param string      $status         Note status.
	 * @param string|null $publish_before End date to store on the note, or null to store none.
	 * @param string      $source         Note source.
	 * @return int Note ID.
	 */
	private function create_note( string $type, string $status, ?string $publish_before, string $source = 'woocommerce.com' ): int {
		$content_data = new \stdClass();

		if ( null !== $publish_before ) {
			$content_data->publish_before = $publish_before;
		}

		$note = new Note();
		$note->set_name( 'phpunit-remote-inbox-alert' );
		$note->set_title( 'Test note' );
		$note->set_content( 'Test content' );
		$note->set_content_data( $content_data );
		$note->set_type( $type );
		$note->set_source( $source );
		$note->set_status( $status );
		$note->save();

		return $note->get_id();
	}
}
