<?php
/**
 * Class WC_Notes_Run_Db_Update file.
 *
 * @package WooCommerce\Tests\Admin\Notes
 */

use Automattic\WooCommerce\Admin\Notes\Note;

/**
 * Tests for the WC_Notes_Run_Db_Update class.
 */
class WC_Tests_Notes_Run_Db_Update extends WC_Unit_Test_Case {

	/**
	 * Load the necessary files, as they're not automatically loaded by WooCommerce.
	 *
	 */
	public static function setUpBeforeClass(): void {
		include_once WC_Unit_Tests_Bootstrap::instance()->plugin_dir . '/includes/admin/wc-admin-functions.php';
		include_once WC_Unit_Tests_Bootstrap::instance()->plugin_dir . '/includes/admin/notes/class-wc-notes-run-db-update.php';

	}

	/**
	 * Clean up before each test.
	 */
	public function setUp(): void {
		if ( ! WC()->is_wc_admin_active() ) {
			$this->markTestSkipped( 'WC Admin is not active on WP versions < 5.3' );
			return;
		}
		self::remove_db_update_notes();
	}

	/**
	 * Returns a list of note ids with name 'wc-update-db-reminder' from the database.
	 *
	 * @return array( int ) List of note ids with name 'wc-update-db-reminder'.
	 */
	private static function get_db_update_notes() {
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( WC_Notes_Run_Db_Update::NOTE_NAME );
		return $note_ids;
	}

	/**
	 * Removes all the notes with name 'wc-update-db-reminder' from the database.
	 */
	private static function remove_db_update_notes() {
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( WC_Notes_Run_Db_Update::NOTE_NAME );
		foreach ( $note_ids as $note_id ) {
			$note = new Note( $note_id );
			$data_store->delete( $note );
		}
	}

	/**
	 * Creates a sample note with name 'wc-update-db-reminder'.
	 *
	 * @return int Newly create note's id.
	 */
	private static function create_db_update_note() {
		$update_url = html_entity_decode(
			wp_nonce_url(
				add_query_arg( 'do_update_woocommerce', 'true', admin_url( 'admin.php?page=wc-settings' ) ),
				'wc_db_update',
				'wc_db_update_nonce'
			)
		);

		$note_actions = array(
			array(
				'name'    => 'update-db_run',
				'label'   => __( 'Update WooCommerce Database', 'woocommerce' ),
				'url'     => $update_url,
				'status'  => 'unactioned',
				'primary' => true,
			),
			array(
				'name'    => 'update-db_learn-more',
				'label'   => __( 'Learn more about updates', 'woocommerce' ),
				'url'     => 'https://woo.com/document/how-to-update-woocommerce/',
				'status'  => 'unactioned',
				'primary' => false,
			),
		);

		$note = new Note();

		$note->set_title( 'WooCommerce database update required' );
		$note->set_content( 'To keep things running smoothly, we have to update your database to the newest version.' );
		$note->set_type( Note::E_WC_ADMIN_NOTE_UPDATE );
		$note->set_name( WC_Notes_Run_Db_Update::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-core' );
		$note->set_status( Note::E_WC_ADMIN_NOTE_UNACTIONED );

		// Set new actions.
		$note->clear_actions();
		foreach ( $note_actions as $note_action ) {
			$note->add_action( ...array_values( $note_action ) );
		}

		return $note->save();
	}

	/**
	 * No note should be created/exist if db version is equal to WC code version.
	 */
	public function test_noop_db_update_note() {
		update_option( 'woocommerce_db_version', WC()->version );

		// No notes initially.
		$this->assertEquals( 0, count( self::get_db_update_notes() ), 'There should be no db update notes initially.' );

		WC_Notes_Run_Db_Update::show_reminder();

		// No notice should be created.
		$this->assertEquals( 0, count( self::get_db_update_notes() ), 'There should be no db update notes created if db is up to date.' );
	}


	/**
	 * Note should be created if there is none and WC is updated.
	 */
	public function test_create_db_update_note() {
		// No notes initially.
		$this->assertEquals( 0, count( self::get_db_update_notes() ), 'There should be no db update notes initially.' );

		// Make it appear as if db version is lower than WC version, i.e. db update is required.
		update_option( 'woocommerce_db_version', '3.9.0' );

		WC_Notes_Run_Db_Update::show_reminder();

		// A notice should be created.
		$this->assertEquals( 1, count( self::get_db_update_notes() ), 'A db update note should be created if db is NOT up to date.' );

		// Update the db option back.
		update_option( 'woocommerce_db_version', WC()->version );
	}

	/**
	 * Note should be created if there is none and WC is updated.
	 */
	public function test_clean_up_multiple_db_update_notes() {
		// No notes initially.
		$this->assertEquals( 0, count( self::get_db_update_notes() ), 'There should be no db update notes initially.' );

		$note_1 = self::create_db_update_note();
		$note_2 = self::create_db_update_note();

		$this->assertEquals( 2, count( self::get_db_update_notes() ), 'There should be 2 db update notes after I created 2.' );

		WC_Notes_Run_Db_Update::show_reminder();

		// Only one notice should remain, in case 2 were created under some weird circumstances.
		$this->assertEquals( 1, count( self::get_db_update_notes() ), 'A db update note should be created if db is NOT up to date.' );

	}

	/**
	 * Test switch from db update needed to thanks note.
	 */
	public function test_db_update_note_to_thanks_note() {
		// No notes initially.
		$this->assertEquals( 0, count( self::get_db_update_notes() ), 'There should be no db update notes initially.' );

		// Make it appear as if db version is lower than WC version, i.e. db update is required.
		update_option( 'woocommerce_db_version', '3.9.0' );

		// Magic 1: nothing to update-db note.
		WC_Notes_Run_Db_Update::show_reminder();

		$note_ids = self::get_db_update_notes();
		// An 'update required' notice should be created.
		$this->assertEquals( 1, count( $note_ids ), 'A db update note should be created if db is NOT up to date.' );

		$note = new Note( $note_ids[0] );
		$actions = $note->get_actions();
		$this->assertEquals( 'update-db_run', $actions[0]->name, 'A db update note to update the database should be displayed now.' );

		// Simulate database update has been performed.
		update_option( 'woocommerce_db_version', WC()->version );

		// Magic 2: update-db note to thank you note.
		WC_Notes_Run_Db_Update::show_reminder();

		$note = new Note( $note_ids[0] );
		$actions = $note->get_actions();
		$this->assertEquals( 'update-db_done', $actions[0]->name, 'A db update note--Thanks for the update--should be displayed now.' );
	}

}
