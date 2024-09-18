<?php
/**
 * Admin note test
 *
 * @package WooCommerce\Admin\Tests\Notes
 */

use Automattic\WooCommerce\Admin\Notes\Note;

/**
 * Class WC_Admin_Tests_Notes_Note
 */
class WC_Admin_Tests_Notes_Note extends WC_Unit_Test_Case {

	/**
	 * Tests a note can be created with timestamp.
	 */
	public function test_note_correctly_sets_created_date_with_timestamp() {
		$timestamp     = time();
		$datetime      = new WC_DateTime( "@{$timestamp}", new DateTimeZone( 'UTC' ) );
		$expected_date = $datetime->format( 'Y-m-d H:i:s' );

		$note = new Note();
		$note->set_title( 'test1' );
		$note->set_date_created( $timestamp );
		$note->save();

		$note         = new Note( $note->get_id() );
		$date_created = $note->get_date_created()->format( 'Y-m-d H:i:s' );
		$this->assertEquals( $expected_date, $date_created );
	}

	/**
	 * Tests note correctly saves/loads date_created value
	 * when it gets saved multiple times.
	 */
	public function test_note_correctly_sets_created_date_when_saved() {
		update_option( 'timezone_string', 'America/Los_Angeles' );
		// Create a new note.
		$note = new Note();
		$note->set_title( 'title2' );
		$note->save();

		// Load it via the data store.
		$note = new Note( $note->get_id() );
		$note->set_content( 'test' );
		$date_created_from_first_save = $note->get_date_created()->format( 'Y-m-d H:i:s' );
		// Save it again.
		$note->save();
		// Then load it again.
		$date_created_from_second_save = $note->get_date_created()->format( 'Y-m-d H:i:s' );

		$this->assertEquals( $date_created_from_first_save, $date_created_from_second_save );
	}

	/**
	 * Tests setting date_reminder with various input types.
	 *
	 * @dataProvider date_reminder_provider
	 * @param mixed $input Input date value.
	 * @param int   $expected_timestamp Expected timestamp.
	 */
	public function test_set_date_reminder_with_various_inputs( $input, $expected_timestamp ) {
		$note = new Note();
		$note->set_date_reminder( $input );
		$date_reminder = $note->get_date_reminder();
		$this->assertEquals( $expected_timestamp, $date_reminder );
	}

	/**
	 * Data provider for test_set_date_reminder_with_various_inputs.
	 *
	 * @return array
	 */
	public function date_reminder_provider() {
		return array(
			'timestamp'          => array( 1609459200, '2021-01-01T00:00:00+00:00' ),
			'timestamp string'   => array( '1609459200', '2021-01-01T00:00:00+00:00' ),
			'date string'        => array( '2021-01-01', '2021-01-01T00:00:00+00:00' ),
			'WC_DateTime object' => array( new WC_DateTime( '2021-01-01' ), '2021-01-01T00:00:00+00:00' ),
		);
	}
}
