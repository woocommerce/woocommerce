<?php

/**
 * Class WC_Customer_Data_Store_CPT_Test.
 */
class WC_Customer_Data_Store_CPT_Test extends WC_Unit_Test_Case {

	/**
	 * Test that metadata cannot overwrite customer's column data.
	 *
	 * @link https://github.com/woocommerce/woocommerce/issues/28100
	 */
	public function test_meta_data_cannot_overwrite_column_data() {
		$customer    = WC_Helper_Customer::create_customer();
		$customer_id = $customer->get_id();
		$username    = $customer->get_username();
		$customer->add_meta_data( 'id', '99999' );
		$customer->add_meta_data( 'username', 'abcde' );
		$customer->save();

		$customer_datastore = new WC_Customer_Data_Store();
		$customer_datastore->read( $customer );
		$this->assertEquals( $customer_id, $customer->get_id() );
		$this->assertEquals( $username, $customer->get_username() );
	}
}
