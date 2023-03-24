<?php

use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;

/**
 * Tests for the WC_User class.
 */
class WC_User_Functions_Tests extends WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * Setup COT.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->setup_cot();
		$this->toggle_cot_feature_and_usage( false );
	}

	/**
	 * Clean COT specific things.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->clean_up_cot_setup();
	}

	/**
	 * Test wc_get_customer_order_count. Borrowed from `WC_Tests_Customer_Functions` class for COT.
	 */
	public function test_hpos_wc_customer_bought_product() {
		$this->toggle_cot_feature_and_usage( true );
		$customer_id_1 = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$customer_id_2 = wc_create_new_customer( 'test2@example.com', 'testuser2', 'testpassword2' );
		$product_1     = new WC_Product_Simple();
		$product_1->save();
		$product_id_1 = $product_1->get_id();
		$product_2    = new WC_Product_Simple();
		$product_2->save();
		$product_id_2 = $product_2->get_id();

		$order_1 = WC_Helper_Order::create_order( $customer_id_1, $product_1 );
		$order_1->set_billing_email( 'test@example.com' );
		$order_1->set_status( 'completed' );
		$order_1->save();
		$order_2 = WC_Helper_Order::create_order( $customer_id_2, $product_2 );
		$order_2->set_billing_email( 'test2@example.com' );
		$order_2->set_status( 'completed' );
		$order_2->save();
		$order_3 = WC_Helper_Order::create_order( $customer_id_1, $product_2 );
		$order_3->set_billing_email( 'test@example.com' );
		$order_3->set_status( 'pending' );
		$order_3->save();

		$this->assertTrue( wc_customer_bought_product( 'test@example.com', $customer_id_1, $product_id_1 ) );
		$this->assertTrue( wc_customer_bought_product( '', $customer_id_1, $product_id_1 ) );
		$this->assertTrue( wc_customer_bought_product( 'test@example.com', 0, $product_id_1 ) );
		$this->assertFalse( wc_customer_bought_product( 'test@example.com', $customer_id_1, $product_id_2 ) );
		$this->assertFalse( wc_customer_bought_product( 'test2@example.com', $customer_id_2, $product_id_1 ) );
	}
}
