<?php
/**
 * Marketing notes tests
 *
 * @package WooCommerce\Admin\Tests\Notes
 */

use \Automattic\WooCommerce\Admin\Notes\Notes;
use \Automattic\WooCommerce\Admin\Notes\Note;
use \Automattic\WooCommerce\Internal\Admin\Notes\WooCommercePayments;

/**
 * Class WC_Admin_Tests_Marketing_Notes
 */
class WC_Admin_Tests_Marketing_Notes extends WC_Unit_Test_Case {

	/**
	 * Tests that a marketing note can be added.
	 */
	public function test_add_remove_marketing_note() {
		$data_store = Notes::load_data_store();

		$note = new Note();
		$note->set_title( 'PHPUNIT_TEST_MARKETING_NOTE' );
		$note->set_content( 'PHPUNIT_TEST_MARKETING_NOTE_CONTENT' );
		$note->set_type( Note::E_WC_ADMIN_NOTE_MARKETING );
		$note->set_name( 'PHPUNIT_TEST_MARKETING_NOTE_NAME' );
		$note->set_source( 'PHPUNIT_TEST' );
		$note->set_is_snoozable( false );
		$note->save();

		// Load all marketing notes and check that the note was successfully saved.
		$notes = $data_store->get_notes(
			array(
				'type' => array( Note::E_WC_ADMIN_NOTE_MARKETING ),
			)
		);

		$this->assertEquals( 1, count( $notes ) );

		// Opt out of WooCommerce marketing.
		update_option( 'woocommerce_show_marketplace_suggestions', 'no' );

		// Reload all marketing notes to verify they have been removed.
		$notes = $data_store->get_notes(
			array(
				'type' => array( Note::E_WC_ADMIN_NOTE_MARKETING ),
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

		WooCommercePayments::possibly_add_note();

		// Load all marketing notes and check that the note was not added.
		$data_store = Notes::load_data_store();
		$notes      = $data_store->get_notes(
			array(
				'type' => array( Note::E_WC_ADMIN_NOTE_MARKETING ),
			)
		);

		$this->assertEquals( 0, count( $notes ) );
	}
}
