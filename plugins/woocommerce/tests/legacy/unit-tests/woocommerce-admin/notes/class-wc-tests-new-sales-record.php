<?php
/**
 * NewSalesRecord note tests
 *
 * @package WooCommerce\Admin\Tests\Notes
 */

use \Automattic\WooCommerce\Internal\Admin\Notes\NewSalesRecord;

/**
 * Class WC_Admin_New_Sales_Record
 */
class WC_Admin_Tests_New_Sales_Record extends WC_Unit_Test_Case {
	/**
	 * Tests get_note method return false when note does not exist in db.
	 */
	public function test_get_note_when_note_not_exist_in_db() {
		$this->assertFalse( NewSalesRecord::get_note() );
	}

	/**
	 * Tests get_note method return note when note exists in db.
	 */
	public function test_get_note_when_note_exists_in_db() {
		$record_date   = '2022-08-15';
		$record_amount = 100;
		$yesterday     = '2022-08-16';
		$total         = 200;
		$note          = NewSalesRecord::get_note_with_record_data( $record_date, $record_amount, $yesterday, $total );
		$note->save();

		$note = NewSalesRecord::get_note();
		$this->assertEquals( $note->get_content(), 'Woohoo, August 16th was your record day for sales! Net sales was $200.00 beating the previous record of $100.00 set on August 15th.' );
	}
}
