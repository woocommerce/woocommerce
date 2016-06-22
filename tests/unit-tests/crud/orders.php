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
		$set_to = 'Boulder';
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
		$set_to = 'Boulder';
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

	/**
	 * Test: get_payment_method
	 */
	function test_get_payment_method() {
		$object = new WC_Order();
		$set_to = 'paypal';
		$object->set_payment_method( $set_to );
		$this->assertEquals( $set_to, $object->get_payment_method() );
	}

	/**
	 * Test: get_payment_method_title
	 */
	function test_get_payment_method_title() {
		$object = new WC_Order();
		$set_to = 'PayPal';
		$object->set_payment_method_title( $set_to );
		$this->assertEquals( $set_to, $object->get_payment_method_title() );
	}

	/**
	 * Test: get_transaction_id
	 */
	function test_get_transaction_id() {
		$object = new WC_Order();
		$set_to = '12345';
		$object->set_transaction_id( $set_to );
		$this->assertEquals( $set_to, $object->get_transaction_id() );
	}

	/**
	 * Test: get_customer_ip_address
	 */
	function test_get_customer_ip_address() {
		$object = new WC_Order();
		$set_to = '192.168.1.1';
		$object->set_customer_ip_address( $set_to );
		$this->assertEquals( $set_to, $object->get_customer_ip_address() );
	}

	/**
	 * Test: get_customer_user_agent
	 */
	function test_get_customer_user_agent() {
		$object = new WC_Order();
		$set_to = 'UAstring';
		$object->set_customer_user_agent( $set_to );
		$this->assertEquals( $set_to, $object->get_customer_user_agent() );
	}

	/**
	 * Test: get_created_via
	 */
	function test_get_created_via() {
		$object = new WC_Order();
		$set_to = 'WooCommerce';
		$object->set_created_via( $set_to );
		$this->assertEquals( $set_to, $object->get_created_via() );
	}

	/**
	 * Test: get_customer_note
	 */
	function test_get_customer_note() {
		$object = new WC_Order();
		$set_to = 'Leave on porch.';
		$object->set_customer_note( $set_to );
		$this->assertEquals( $set_to, $object->get_customer_note() );
	}

	/**
	 * Test: get_date_completed
	 */
	function test_get_date_completed() {
		$object = new WC_Order();
		$object->set_date_completed( '2016-12-12' );
		$this->assertEquals( '1481500800', $object->get_date_completed() );

		$object->set_date_completed( '1481500800' );
		$this->assertEquals( 1481500800, $object->get_date_completed() );
	}

	/**
	 * Test: get_date_paid
	 */
	function test_get_date_paid() {
		$object = new WC_Order();
		$set_to = 'PayPal';
		$object->set_date_paid( '2016-12-12' );
		$this->assertEquals( 1481500800, $object->get_date_paid() );

		$object->set_date_paid( '1481500800' );
		$this->assertEquals( 1481500800, $object->get_date_paid() );
	}

	/**
	 * Test: get_cart_hash
	 */
	function test_get_cart_hash() {
		$object = new WC_Order();
		$set_to = '12345';
		$object->set_cart_hash( $set_to );
		$this->assertEquals( $set_to, $object->get_cart_hash() );
	}

	/**
	 * Test: get_address
	 */
	function test_get_address() {
		$object = new WC_Order();

		$billing = array(
			'first_name' => 'Fred',
			'last_name'  => 'Flintstone',
			'company'    => 'Bedrock Ltd.',
			'address_1'  => '34 Stonepants avenue',
			'address_2'  => 'Rockville',
			'city'       => 'Bedrock',
			'state'      => 'Boulder',
			'postcode'   => '00001',
			'country'    => 'US',
			'email'      => '',
			'phone'      => '',
		);

		$shipping = array(
			'first_name' => 'Barney',
			'last_name'  => 'Rubble',
			'company'    => 'Bedrock Ltd.',
			'address_1'  => '34 Stonepants avenue',
			'address_2'  => 'Rockville',
			'city'       => 'Bedrock',
			'state'      => 'Boulder',
			'postcode'   => '00001',
			'country'    => 'US',
		);

		$object->set_billing_first_name( 'Fred' );
		$object->set_billing_last_name( 'Flintstone' );
		$object->set_billing_company( 'Bedrock Ltd.' );
		$object->set_billing_address_1( '34 Stonepants avenue' );
		$object->set_billing_address_2( 'Rockville' );
		$object->set_billing_city( 'Bedrock' );
		$object->set_billing_state( 'Boulder' );
		$object->set_billing_postcode( '00001' );
		$object->set_billing_country( 'US' );

		$object->set_shipping_first_name( 'Barney' );
		$object->set_shipping_last_name( 'Rubble' );
		$object->set_shipping_company( 'Bedrock Ltd.' );
		$object->set_shipping_address_1( '34 Stonepants avenue' );
		$object->set_shipping_address_2( 'Rockville' );
		$object->set_shipping_city( 'Bedrock' );
		$object->set_shipping_state( 'Boulder' );
		$object->set_shipping_postcode( '00001' );
		$object->set_shipping_country( 'US' );

		$this->assertEquals( $billing, $object->get_address() );
		$this->assertEquals( $shipping, $object->get_address( 'shipping' ) );
	}

	/**
	 * Test: get_shipping_address_map_url
	 */
	function test_get_shipping_address_map_url() {
		$object = new WC_Order();
		$object->set_shipping_first_name( 'Barney' );
		$object->set_shipping_last_name( 'Rubble' );
		$object->set_shipping_company( 'Bedrock Ltd.' );
		$object->set_shipping_address_1( '34 Stonepants avenue' );
		$object->set_shipping_address_2( 'Rockville' );
		$object->set_shipping_city( 'Bedrock' );
		$object->set_shipping_state( 'Boulder' );
		$object->set_shipping_postcode( '00001' );
		$object->set_shipping_country( 'US' );
		$this->assertEquals( 'http://maps.google.com/maps?&q=Barney%2C+Rubble%2C+Bedrock+Ltd.%2C+34+Stonepants+avenue%2C+Rockville%2C+Bedrock%2C+Boulder%2C+00001%2C+US&z=16', $object->get_shipping_address_map_url() );
	}

	/**
	 * Test: get_formatted_billing_full_name
	 */
	function test_get_formatted_billing_full_name() {
		$object = new WC_Order();
		$object->set_billing_first_name( 'Fred' );
		$object->set_billing_last_name( 'Flintstone' );
		$this->assertEquals( 'Fred Flintstone', $object->get_formatted_billing_full_name() );
	}

	/**
	 * Test: get_formatted_shipping_full_name
	 */
	function test_get_formatted_shipping_full_name() {
		$object = new WC_Order();
		$object->set_shipping_first_name( 'Barney' );
		$object->set_shipping_last_name( 'Rubble' );
		$this->assertEquals( 'Barney Rubble', $object->get_formatted_shipping_full_name() );
	}

	/**
	 * Test: get_formatted_billing_address
	 */
	function test_get_formatted_billing_address() {
		$object = new WC_Order();
		$object->set_billing_first_name( 'Fred' );
		$object->set_billing_last_name( 'Flintstone' );
		$object->set_billing_company( 'Bedrock Ltd.' );
		$object->set_billing_address_1( '34 Stonepants avenue' );
		$object->set_billing_address_2( 'Rockville' );
		$object->set_billing_city( 'Bedrock' );
		$object->set_billing_state( 'Boulder' );
		$object->set_billing_postcode( '00001' );
		$object->set_billing_country( 'US' );
		$this->assertEquals( 'Fred Flintstone<br/>Bedrock Ltd.<br/>34 Stonepants avenue<br/>Rockville<br/>Bedrock, BOULDER 00001<br/>United States (US)', $object->get_formatted_billing_address() );
	}

	/**
	 * Test: get_formatted_shipping_address
	 */
	function test_get_formatted_shipping_address() {
		$object = new WC_Order();
		$object->set_shipping_first_name( 'Barney' );
		$object->set_shipping_last_name( 'Rubble' );
		$object->set_shipping_company( 'Bedrock Ltd.' );
		$object->set_shipping_address_1( '34 Stonepants avenue' );
		$object->set_shipping_address_2( 'Rockville' );
		$object->set_shipping_city( 'Bedrock' );
		$object->set_shipping_state( 'Boulder' );
		$object->set_shipping_postcode( '00001' );
		$object->set_shipping_country( 'US' );
		$this->assertEquals( 'Barney Rubble<br/>Bedrock Ltd.<br/>34 Stonepants avenue<br/>Rockville<br/>Bedrock, BOULDER 00001<br/>United States (US)', $object->get_formatted_shipping_address() );
	}

	/**
	 * Test: has_cart_hash
	 */
	function test_has_cart_hash() {
		$object = new WC_Order();
		$this->assertFalse( $object->has_cart_hash( '12345' ) );
		$set_to = '12345';
		$object->set_cart_hash( $set_to );
		$this->assertTrue( $object->has_cart_hash( '12345' ) );
	}

	/**
	 * Test: is_editable
	 */
	function test_is_editable() {
		$object = new WC_Order();
		$object->set_status( 'pending' );
		$this->assertTrue( $object->is_editable() );
		$object->set_status( 'processing' );
		$this->assertFalse( $object->is_editable() );
	}

	/**
	 * Test: is_paid
	 */
	function test_is_paid() {
		$object = new WC_Order();
		$object->set_status( 'pending' );
		$this->assertFalse( $object->is_paid() );
		$object->set_status( 'processing' );
		$this->assertTrue( $object->is_paid() );
	}

	/**
	 * Test: is_download_permitted
	 */
	function test_is_download_permitted() {
		$object = new WC_Order();
		$object->set_status( 'pending' );
		$this->assertFalse( $object->is_download_permitted() );
		$object->set_status( 'completed' );
		$this->assertTrue( $object->is_download_permitted() );
	}

	/**
	 * Test: needs_shipping_address
	 */
	function test_needs_shipping_address() {
		$object = new WC_Order();
		$this->assertFalse( $object->needs_shipping_address() );

		// @todo
	}

	/**
	 * Test: has_downloadable_item
	 */
	function test_has_downloadable_item() {
		$object = new WC_Order();
		$this->assertFalse( $object->has_downloadable_item() );

		// @todo
	}

	/**
	 * Test: needs_payment
	 */
	function test_needs_payment() {
		$object = new WC_Order();

		$object->set_status( 'pending' );
		$this->assertFalse( $object->needs_payment() );

		$object->set_total( 100 );
		$this->assertTrue( $object->needs_payment() );

		$object->set_status( 'processing' );
		$this->assertFalse( $object->needs_payment() );
	}

	/**
	 * Test: get_checkout_payment_url
	 */
	function test_get_checkout_payment_url() {
		$object = new WC_Order();
		$id     = $object->save();
		$this->assertEquals( '?order-pay=' . $id . '&pay_for_order=true&key=' . $object->get_order_key(), $object->get_checkout_payment_url() );
	}

	/**
	 * Test: get_checkout_order_received_url
	 */
	function test_get_checkout_order_received_url() {
		$object = new WC_Order();
		$object->set_order_key( 'xxx' );
		$id     = $object->save();
		$this->assertEquals( '?order-received=' . $id . '&key=' . $object->get_order_key(), $object->get_checkout_order_received_url() );
	}

	/**
	 * Test: get_cancel_order_url
	 */
	function test_get_cancel_order_url() {
		$object = new WC_Order();
		$this->assertInternalType( 'string', $object->get_cancel_order_url() );
	}

	/**
	 * Test: get_cancel_order_url_raw
	 */
	function test_get_cancel_order_url_raw() {
		$object = new WC_Order();
		$this->assertInternalType( 'string', $object->get_cancel_order_url_raw() );
	}

	/**
	 * Test: get_cancel_endpoint
	 */
	function test_get_cancel_endpoint() {
		$object = new WC_Order();
		$this->assertEquals( 'http://example.org/', $object->get_cancel_endpoint() );
	}

	/**
	 * Test: get_view_order_url
	 */
	function test_get_view_order_url() {
		$object = new WC_Order();
		$id     = $object->save();
		$this->assertEquals( '?view-order=' . $id, $object->get_view_order_url() );
	}
}
