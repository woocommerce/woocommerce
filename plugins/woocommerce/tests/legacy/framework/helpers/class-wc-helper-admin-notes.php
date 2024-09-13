<?php
/**
 * Admin notes helper for wc-admin unit tests.
 *
 * @package WooCommerce\Admin\Tests\Framework\Helpers
 */

use Automattic\WooCommerce\Admin\Notes\Note;

/**
 * Class WC_Helper_Admin_Notes.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class WC_Helper_Admin_Notes {

	/**
	 * Delete everything in the notes tables.
	 */
	public static function reset_notes_dbs() {
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_admin_notes" ); // @codingStandardsIgnoreLine.
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_admin_note_actions" ); // @codingStandardsIgnoreLine.
	}

	/**
	 * Create four notes that we can use for notes REST API tests
	 */
	public static function add_notes_for_tests() {
		$data_store = WC_Data_Store::load( 'admin-note' );

		$note_1 = new Note();
		$note_1->set_title( 'PHPUNIT_TEST_NOTE_1_TITLE' );
		$note_1->set_content( 'PHPUNIT_TEST_NOTE_1_CONTENT' );
		$note_1->set_content_data( (object) array( 'amount' => 1.23 ) );
		$note_1->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note_1->set_name( 'PHPUNIT_TEST_NOTE_NAME' );
		$note_1->set_source( 'PHPUNIT_TEST' );
		$note_1->set_is_snoozable( false );
		$note_1->set_layout( 'plain' );
		$note_1->set_image( '' );
		$note_1->add_action(
			'PHPUNIT_TEST_NOTE_1_ACTION_1_SLUG',
			'PHPUNIT_TEST_NOTE_1_ACTION_1_LABEL',
			'?s=PHPUNIT_TEST_NOTE_1_ACTION_1_URL'
		);
		$note_1->add_action(
			'PHPUNIT_TEST_NOTE_1_ACTION_2_SLUG',
			'PHPUNIT_TEST_NOTE_1_ACTION_2_LABEL',
			'?s=PHPUNIT_TEST_NOTE_1_ACTION_2_URL'
		);
		$note_1->save();

		$note_2 = new Note();
		$note_2->set_title( 'PHPUNIT_TEST_NOTE_2_TITLE' );
		$note_2->set_content( 'PHPUNIT_TEST_NOTE_2_CONTENT' );
		$note_2->set_content_data( (object) array( 'amount' => 4.56 ) );
		$note_2->set_type( Note::E_WC_ADMIN_NOTE_WARNING );
		$note_2->set_name( 'PHPUNIT_TEST_NOTE_NAME' );
		$note_2->set_source( 'PHPUNIT_TEST' );
		$note_2->set_status( Note::E_WC_ADMIN_NOTE_ACTIONED );
		$note_2->set_is_snoozable( true );
		$note_2->set_layout( 'thumbnail' );
		$note_2->set_image( 'https://an-image.jpg' );
		// This note has no actions.
		$note_2->save();

		$note_3 = new Note();
		$note_3->set_title( 'PHPUNIT_TEST_NOTE_3_TITLE' );
		$note_3->set_content( 'PHPUNIT_TEST_NOTE_3_CONTENT' );
		$note_3->set_content_data( (object) array( 'amount' => 7.89 ) );
		$note_3->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note_3->set_name( 'PHPUNIT_TEST_NOTE_NAME' );
		$note_3->set_source( 'PHPUNIT_TEST' );
		$note_3->set_status( Note::E_WC_ADMIN_NOTE_SNOOZED );
		$note_3->set_date_reminder( time() - HOUR_IN_SECONDS );
		$note_3->set_layout( 'thumbnail' );
		$note_3->set_image( 'https://an-image.jpg' );
		// This note has no actions.
		$note_3->save();

		$note_4 = new Note();
		$note_4->set_title( 'PHPUNIT_TEST_NOTE_4_TITLE' );
		$note_4->set_content( 'PHPUNIT_TEST_NOTE_4_CONTENT' );
		$note_4->set_content_data( (object) array( 'amount' => 1.23 ) );
		$note_4->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note_4->set_name( 'PHPUNIT_TEST_NOTE_NAME' );
		$note_4->set_source( 'PHPUNIT_TEST' );
		$note_4->set_is_snoozable( false );
		$note_4->set_layout( 'plain' );
		$note_4->set_image( '' );
		$note_4->add_action(
			'PHPUNIT_TEST_NOTE_4_ACTION_1_SLUG',
			'PHPUNIT_TEST_NOTE_4_ACTION_1_LABEL',
			'?s=PHPUNIT_TEST_NOTE_4_ACTION_1_URL'
		);
		$note_4->add_action(
			'PHPUNIT_TEST_NOTE_4_ACTION_2_SLUG',
			'PHPUNIT_TEST_NOTE_4_ACTION_2_LABEL',
			'?s=PHPUNIT_TEST_NOTE_4_ACTION_2_URL'
		);
		$note_4->save();
	}

	/**
	 * Create a note that we can use for email notes tests
	 */
	public static function add_email_notes_for_test() {
		$data_store = WC_Data_Store::load( 'admin-note' );

		$note_5 = new Note();
		$note_5->set_title( 'PHPUNIT_TEST_NOTE_5_TITLE' );
		$note_5->set_content( 'PHPUNIT_TEST_NOTE_5_CONTENT' );
		$additional_data = array(
			'heading'        => 'PHPUNIT_TEST_EMAIL_HEADING',
			'role'           => 'administrator',
			'template_html'  => 'PHPUNIT_TEST_EMAIL_HTML_TEMPLATE',
			'template_plain' => 'PHPUNIT_TEST_EMAIL_HTML_PLAIN',
		);
		$note_5->set_content_data( (object) $additional_data );
		$note_5->set_type( Note::E_WC_ADMIN_NOTE_EMAIL );
		$note_5->set_name( 'PHPUNIT_TEST_NOTE_NAME' );
		$note_5->set_source( 'PHPUNIT_TEST' );
		$note_5->set_is_snoozable( false );
		$note_5->set_layout( 'plain' );
		$note_5->set_image( '' );
		$note_5->add_action(
			'PHPUNIT_TEST_NOTE_5_ACTION_SLUG',
			'PHPUNIT_TEST_NOTE_5_ACTION_LABEL',
			'?s=PHPUNIT_TEST_NOTE_5_ACTION_URL'
		);
		$note_5->save();
	}

	/**
	 * Create a note that we can use for tests on `name` related filters
	 *
	 * @param string $name The name of the new note.
	 */
	public static function add_note_for_test( string $name = 'default_name' ) {
		$data_store = WC_Data_Store::load( 'admin-note' );

		$note = new Note();
		$note->set_title( 'PHPUNIT_TEST_NOTE_TITLE' );
		$note->set_content( 'PHPUNIT_TEST_NOTE_CONTENT' );

		$note->set_type( Note::E_WC_ADMIN_NOTE_MARKETING );
		$note->set_name( $name );
		$note->set_source( 'PHPUNIT_TEST' );
		$note->set_is_snoozable( false );
		$note->set_layout( 'plain' );
		$note->set_image( '' );
		$note->save();
	}
}
