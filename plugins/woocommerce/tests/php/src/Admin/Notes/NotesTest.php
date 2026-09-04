<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\Notes;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;
use WC_Unit_Test_Case;

/**
 * Tests for admin note cleanup.
 */
class NotesTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Survey cleanup runs daily instead of during admin initialization.
	 */
	public function test_survey_cleanup_runs_daily(): void {
		$callback = array( Notes::class, 'possibly_delete_survey_notes' );

		$this->assertFalse( has_action( 'admin_init', $callback ), 'Survey cleanup should not run during admin initialization.' );

		// Priority 9 so the cleanup runs before Events::do_wc_admin_daily, which adds notes and
		// refreshes the remote data source pollers at the default priority.
		$this->assertSame( 9, has_action( 'wc_admin_daily', $callback ), 'Survey cleanup should run from the daily admin event, ahead of the fetch.' );
	}

	/**
	 * @testdox Survey cleanup deletes only actioned survey notes.
	 */
	public function test_survey_cleanup_deletes_only_actioned_survey_notes(): void {
		$actioned_note_id   = $this->create_note( 'phpunit-actioned-survey-note', Note::E_WC_ADMIN_NOTE_SURVEY, Note::E_WC_ADMIN_NOTE_ACTIONED );
		$unactioned_note_id = $this->create_note( 'phpunit-unactioned-survey-note', Note::E_WC_ADMIN_NOTE_SURVEY, Note::E_WC_ADMIN_NOTE_UNACTIONED );
		$other_note_id      = $this->create_note( 'phpunit-actioned-info-note', Note::E_WC_ADMIN_NOTE_INFORMATIONAL, Note::E_WC_ADMIN_NOTE_ACTIONED );

		Notes::possibly_delete_survey_notes();

		$this->assertTrue( ( new Note( $actioned_note_id ) )->get_is_deleted(), 'The actioned survey note should be deleted.' );
		$this->assertFalse( ( new Note( $unactioned_note_id ) )->get_is_deleted(), 'The unactioned survey note should remain visible.' );
		$this->assertFalse( ( new Note( $other_note_id ) )->get_is_deleted(), 'Actioned notes of other types should remain visible.' );
	}

	/**
	 * Create an admin note.
	 *
	 * @param string $name   Note name.
	 * @param string $type   Note type.
	 * @param string $status Note status.
	 * @return int Note ID.
	 */
	private function create_note( string $name, string $type, string $status ): int {
		$note = new Note();
		$note->set_name( $name );
		$note->set_title( 'Test note' );
		$note->set_content( 'Test content' );
		$note->set_type( $type );
		$note->set_source( 'PHPUNIT_TEST' );
		$note->set_status( $status );
		$note->save();

		return $note->get_id();
	}
}
