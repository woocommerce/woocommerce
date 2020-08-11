<?php
/**
 * Marketing notes tests
 *
 * @package WooCommerce\Admin\Tests\Notes
 */

use \Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes;
use \Automattic\WooCommerce\Admin\Notes\WC_Admin_Note;
use \Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes_WooCommerce_Payments;

/**
 * Class WC_Tests_Marketing_Notes
 */
class WC_Tests_Marketing_Notes extends WC_Unit_Test_Case {

	/**
	 * Tests that a marketing note can be added.
	 */
	public function test_add_remove_marketing_note() {
		$data_store = \WC_Data_Store::load( 'admin-note' );

		$note = new WC_Admin_Note();
		$note->set_title( 'PHPUNIT_TEST_MARKETING_NOTE' );
		$note->set_content( 'PHPUNIT_TEST_MARKETING_NOTE_CONTENT' );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_MARKETING );
		$note->set_name( 'PHPUNIT_TEST_MARKETING_NOTE_NAME' );
		$note->set_source( 'PHPUNIT_TEST' );
		$note->set_is_snoozable( false );
		$note->save();

		// Load all marketing notes and check that the note was successfully saved.
		$notes = $data_store->get_notes(
			array(
				'type' => array( WC_Admin_Note::E_WC_ADMIN_NOTE_MARKETING ),
			)
		);

		$this->assertEquals( 1, count( $notes ) );

		// Opt out of WooCommerce marketing.
		update_option( 'woocommerce_show_marketplace_suggestions', 'no' );

		// Reload all marketing notes to verify they have been removed.
		$notes = $data_store->get_notes(
			array(
				'type' => array( WC_Admin_Note::E_WC_ADMIN_NOTE_MARKETING ),
			)
		);

		$this->assertEquals( 0, count( $notes ) );
	}

	/**
	 * Tests to see if marketing notes are prevented when marketing is opted out.
	 */
	public function test_prevent_add_marketing_note() {
		// Update settings so that note should be added.
		update_option( 'woocommerce_default_country', 'US:GA' );
		update_option( 'woocommerce_admin_install_timestamp', time() - WEEK_IN_SECONDS );
		// Set user preferences to disallow marketing suggestions.
		update_option( 'woocommerce_show_marketplace_suggestions', 'no' );

		WC_Admin_Notes_WooCommerce_Payments::possibly_add_note();

		// Load all marketing notes and check that the note was not added.
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$notes      = $data_store->get_notes(
			array(
				'type' => array( WC_Admin_Note::E_WC_ADMIN_NOTE_MARKETING ),
			)
		);

		$this->assertEquals( 0, count( $notes ) );
	}
}
