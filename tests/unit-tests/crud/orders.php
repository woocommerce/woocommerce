<?php

/**
 * Meta
 * @package WooCommerce\Tests\CRUD
 */
class WC_Tests_CRUD_Orders extends WC_Unit_Test_Case {

	/**
	 * Test: get_type
	 */
	function test_get_type() {
		$object = new WC_Order();
		$this->assertEquals( 'shop_order', $object->get_type() );
	}

	/**
	 * Test: CRUD
	 */
	function test_CRUD() {
		$object = new WC_Order();

		// Save + create
		$save_id = $object->save();
		$post    = get_post( $save_id );
		$this->assertEquals( 'shop_order', $post->post_type );
		$this->assertEquals( 'shop_order', $post->post_type );

		// Update
		$update_id = $object->save();
		$this->assertEquals( $update_id, $save_id );

		// Delete
		$object->delete();
		$post = get_post( $save_id );
		$this->assertNull( $post );
	}

	/**
	 * Test: payment_complete
	 */
	function test_payment_complete() {
		$object = new WC_Order();

		$this->assertFalse( $object->payment_complete() );
		$object->save();
		$this->assertTrue( $object->payment_complete( '12345' ) );
		$this->assertEquals( 'completed', $object->get_status() );
		$this->assertEquals( '12345', $object->get_transaction_id() );
	}

	/**
	 * Test: get_formatted_order_total
	 */
	function test_get_formatted_order_total() {
		$object = new WC_Order();
		$object->set_total( 100 );
		$object->set_currency( 'USD' );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&#36;</span>100.00</span>', $object->get_formatted_order_total() );
	}

	/**
	 * Test: set_status
	 */
	function test_set_status() {
		$object = new WC_Order();
		$object->set_status( 'on-hold' );
		$this->assertEquals( 'on-hold', $object->get_status() );
	}

	/**
	 * Test: update_status
	 */
	function test_update_status() {
		$object = new WC_Order();
		$this->assertFalse( $object->update_status( 'on-hold' ) );
		$object->save();
		$this->assertTrue( $object->update_status( 'on-hold' ) );
		$this->assertEquals( 'on-hold', $object->get_status() );
	}

	/**
	 * Test: get_billing_first_name
	 */
	function test_get_billing_first_name() {
		$object = new WC_Order();
		$set_to = 'Fred';
		$object->set_billing_first_name( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_first_name() );
	}

	/**
	 * Test: get_billing_last_name
	 */
	function test_get_billing_last_name() {
		$object = new WC_Order();
		$set_to = 'Flintstone';
		$object->set_billing_last_name( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_last_name() );
	}

	/**
	 * Test: get_billing_company
	 */
	function test_get_billing_company() {
		$object = new WC_Order();
		$set_to = 'Bedrock Ltd.';
		$object->set_billing_company( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_company() );
	}

	/**
	 * Test: get_billing_address_1
	 */
	function test_get_billing_address_1() {
		$object = new WC_Order();
		$set_to = '34 Stonepants avenue';
		$object->set_billing_address_1( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_address_1() );
	}

	/**
	 * Test: get_billing_address_2
	 */
	function test_get_billing_address_2() {
		$object = new WC_Order();
		$set_to = 'Rockville';
		$object->set_billing_address_2( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_address_2() );
	}

	/**
	 * Test: get_billing_city
	 */
	function test_get_billing_city() {
		$object = new WC_Order();
		$set_to = 'Bedrock';
		$object->set_billing_city( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_city() );
	}

	/**
	 * Test: get_billing_state
	 */
	function test_get_billing_state() {
		$object = new WC_Order();
		$set_to = 'Jurassic America';
		$object->set_billing_state( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_state() );
	}

	/**
	 * Test: get_billing_postcode
	 */
	function test_get_billing_postcode() {
		$object = new WC_Order();
		$set_to = '00001';
		$object->set_billing_postcode( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_postcode() );
	}

	/**
	 * Test: get_billing_country
	 */
	function test_get_billing_country() {
		$object = new WC_Order();
		$set_to = 'US';
		$object->set_billing_country( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_country() );
	}

	/**
	 * Test: get_billing_email
	 */
	function test_get_billing_email() {
		$object = new WC_Order();
		$set_to = 'test@test.com';
		$object->set_billing_email( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_email() );

		$set_to = 'not an email';
		$object->set_billing_email( $set_to );
		$this->assertEquals( '', $object->get_billing_email() );
	}

	/**
	 * Test: get_billing_phone
	 */
	function test_get_billing_phone() {
		$object = new WC_Order();
		$set_to = '123456678';
		$object->set_billing_phone( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_phone() );
	}

	/**
	 * Test: get_shipping_first_name
	 */
	function test_get_shipping_first_name() {
		$object = new WC_Order();
		$set_to = 'Fred';
		$object->set_shipping_first_name( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_first_name() );
	}

	/**
	 * Test: get_shipping_last_name
	 */
	function test_get_shipping_last_name() {
		$object = new WC_Order();
		$set_to = 'Flintstone';
		$object->set_shipping_last_name( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_last_name() );
	}

	/**
	 * Test: get_shipping_company
	 */
	function test_get_shipping_company() {
		$object = new WC_Order();
		$set_to = 'Bedrock Ltd.';
		$object->set_shipping_company( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_company() );
	}

	/**
	 * Test: get_shipping_address_1
	 */
	function test_get_shipping_address_1() {
		$object = new WC_Order();
		$set_to = '34 Stonepants avenue';
		$object->set_shipping_address_1( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_address_1() );
	}

	/**
	 * Test: get_shipping_address_2
	 */
	function test_get_shipping_address_2() {
		$object = new WC_Order();
		$set_to = 'Rockville';
		$object->set_shipping_address_2( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_address_2() );
	}

	/**
	 * Test: get_shipping_city
	 */
	function test_get_shipping_city() {
		$object = new WC_Order();
		$set_to = 'Bedrock';
		$object->set_shipping_city( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_city() );
	}

	/**
	 * Test: get_shipping_state
	 */
	function test_get_shipping_state() {
		$object = new WC_Order();
		$set_to = 'Jurassic America';
		$object->set_shipping_state( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_state() );
	}

	/**
	 * Test: get_shipping_postcode
	 */
	function test_get_shipping_postcode() {
		$object = new WC_Order();
		$set_to = '00001';
		$object->set_shipping_postcode( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_postcode() );
	}

	/**
	 * Test: get_shipping_country
	 */
	function test_get_shipping_country() {
		$object = new WC_Order();
		$set_to = 'US';
		$object->set_shipping_country( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_country() );
	}
}
