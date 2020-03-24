<?php
/**
 * Admin notes tests
 *
 * @package WooCommerce\Tests\Notes
 */

use Automattic\WooCommerce\Admin\Notes\WC_Admin_Note;

/**
 * Class WC_Tests_Notes_Data_Store
 */
class WC_Tests_Notes_Data_Store extends WC_Unit_Test_Case {

	/**
	 * Tests that the read data store method works as expected.
	 */
	public function test_read() {
		$note = new WC_Admin_Note();
		$note->set_title( 'PHPUNIT_TEST_NOTE' );
		$note->set_content( 'PHPUNIT_TEST_NOTE_CONTENT' );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_icon( 'info' );
		$note->set_name( 'PHPUNIT_TEST_NOTE_NAME' );
		$note->set_source( 'PHPUNIT_TEST' );
		$note->set_is_snoozable( false );
		$note->add_action(
			'PHPUNIT_TEST_ACTION_SLUG',
			'PHPUNIT_TEST_ACTION_LABEL',
			'?s=PHPUNIT_TEST_ACTION_URL'
		);
		$note->save();

		// Load in a new instance of the note.
		$read_note = new WC_Admin_Note();
		$read_note->set_id( $note->get_id() );
		WC_Data_Store::load( 'admin-note' )->read( $read_note );

		$this->assertEquals( $note->get_title(), $read_note->get_title() );
		$this->assertEquals( $note->get_content(), $read_note->get_content() );
		$this->assertEquals( $note->get_type(), $read_note->get_type() );
		$this->assertEquals( $note->get_icon(), $read_note->get_icon() );
		$this->assertEquals( $note->get_name(), $read_note->get_name() );
		$this->assertEquals( $note->get_source(), $read_note->get_source() );
		$this->assertEquals( $note->get_is_snoozable(), '0' !== $read_note->get_is_snoozable() );
		$this->assertEquals( $note->get_actions(), $read_note->get_actions() );
	}

	/**
	 * Tests that the read data store method does not fail for invalid content data fields.
	 */
	public function test_read_with_invalid_content_data() {
		global $wpdb;
		$data_store = WC_Data_Store::load( 'admin-note' );

		$note = new WC_Admin_Note();
		$note->set_title( 'PHPUNIT_TEST_NOTE' );
		$note->set_content( 'PHPUNIT_TEST_NOTE_CONTENT' );
		$note->save();

		// Make sure that empty content_data does not break the note.
		$wpdb->update(
			$wpdb->prefix . 'wc_admin_notes',
			array(
				'content_data' => '',
			),
			array( 'note_id' => $note->get_id() )
		);
		$data_store->read( $note );

		$this->assertEquals( 'PHPUNIT_TEST_NOTE', $note->get_title() );
		$this->assertEquals( 'PHPUNIT_TEST_NOTE_CONTENT', $note->get_content() );

		// We also want to make sure an empty array does not break the content.
		$wpdb->update(
			$wpdb->prefix . 'wc_admin_notes',
			array(
				'content_data' => '[]',
			),
			array( 'note_id' => $note->get_id() )
		);
		$data_store->read( $note );

		$this->assertEquals( 'PHPUNIT_TEST_NOTE', $note->get_title() );
		$this->assertEquals( 'PHPUNIT_TEST_NOTE_CONTENT', $note->get_content() );
	}
}
