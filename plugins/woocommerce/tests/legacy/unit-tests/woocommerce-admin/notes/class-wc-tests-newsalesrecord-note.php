<?php
/**
 * NewSalesRecord note tests
 *
 * @package WooCommerce\Admin\Tests\Notes
 */

use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\Internal\Admin\Notes\NewSalesRecord;

/**
 * Class WC_Admin_Tests_NewSalesRecord_Note
 */
class WC_Admin_Tests_NewSalesRecord_Note extends WC_Unit_Test_Case {

	/**
	 * @var string
	 */
	private $yesterday;

	/**
	 * Set up
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		Notes::delete_notes_with_name( NewSalesRecord::NOTE_NAME );
		$this->yesterday = gmdate( 'Y-m-d', time() - DAY_IN_SECONDS );

		$order = wc_create_order();
		$order->set_total( 10 );
		$order->set_date_created( $this->yesterday );
		$order->update_status( 'Completed' );

		update_option( NewSalesRecord::RECORD_DATE_OPTION_KEY, '2022-01-01' );
		update_option( NewSalesRecord::RECORD_AMOUNT_OPTION_KEY, 1 );
	}

	/**
	 * Test it uses f jS date format for English speaking countries.
	 * @return void
	 */
	public function test_it_uses_fjS_date_format_for_english_speaking_countries() {
		add_filter(
			'locale',
			function() {
				return 'en_US';
			}
		);
		NewSalesRecord::possibly_add_note();
		$expected_date = date_i18n( 'F jS', strtotime( $this->yesterday ) );
		$note          = Notes::get_note_by_name( NewSalesRecord::NOTE_NAME );
		$this->assertTrue( strpos( $note->get_content(), "Woohoo, $expected_date" ) === 0 );
	}

	/**
	 * Test it uses system date format for non-English speaking countries.
	 * @return void
	 */
	public function test_it_uses_system_date_format_for_non_english_speaking_countries() {
		add_filter(
			'locale',
			function() {
				return 'es_MX';
			}
		);
		NewSalesRecord::possibly_add_note();
		$expected_date = date_i18n( get_option( 'date_format' ), strtotime( $this->yesterday ) );
		$note          = Notes::get_note_by_name( NewSalesRecord::NOTE_NAME );
		$this->assertTrue( strpos( $note->get_content(), $expected_date ) !== false );
	}
}
