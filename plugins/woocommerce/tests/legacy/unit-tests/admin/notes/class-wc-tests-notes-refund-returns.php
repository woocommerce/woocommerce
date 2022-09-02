<?php
/**
 * Class WC_Notes_Refund_Returns file.
 *
 * @package WooCommerce\Tests\Admin\Notes
 */

use Automattic\WooCommerce\Admin\Notes\Note;

/**
 * Tests for the WC_Notes_Refund_Returns class.
 */
class WC_Tests_Notes_Refund_Returns extends WC_Unit_Test_Case {
	/**
	 * Test get_note_from_db method with a invalid db note.
	 */
	public function test_get_note_from_db_with_invalid_note() {
		$note_from_db = array();
		$this->assertEquals( WC_Notes_Refund_Returns::get_note_from_db( $note_from_db ), $note_from_db );
	}

	/**
	 * Test get_note_from_db method when the note locale is the same as user's locale.
	 */
	public function test_get_note_from_db_when_note_locale_is_the_same() {
		$note_from_db = new Note();
		$locale       = get_user_locale();
		$note_from_db->set_locale( $locale );
		$this->assertEquals( WC_Notes_Refund_Returns::get_note_from_db( $note_from_db ), $note_from_db );
	}

	/**
	 * Test get_note_from_db method when the note name is not wc-refund-returns-page.
	 */
	public function test_get_note_from_db_when_note_name_not_match() {
		$note_from_db = new Note();
		$note_from_db->set_name( 'test' );
		$note_from_db->set_locale( 'zh_TW' );
		$this->assertEquals( WC_Notes_Refund_Returns::get_note_from_db( $note_from_db ), $note_from_db );
	}

	/**
	 * Test get_note_from_db method should return a updated note.
	 */
	public function test_get_note_from_db_return_updated_note() {
		$note_from_db = new Note();
		$note_from_db->set_name( WC_Notes_Refund_Returns::NOTE_NAME );
		$note_from_db->set_locale( 'zh_TW' );
		$note = WC_Notes_Refund_Returns::get_note_from_db( $note_from_db );
		$this->assertEquals( $note->get_title(), 'Setup a Refund and Returns Policy page to boost your store\'s credibility.' );
	}
}
