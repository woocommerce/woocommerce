<?php
/**
 * Customer functions
 *
 * @package WooCommerce\Tests\Customer
 */

/**
 * WC_Tests_Customer_Functions class.
 */
class WC_Tests_Customer_Functions extends WC_Unit_Test_Case {

	/**
	 * Set illegal login
	 *
	 * @param array $logins Array of blocked logins.
	 * @return array
	 */
	public function setup_illegal_user_logins( $logins ) {
		return array( 'test' );
	}

	/**
	 * Test wc_create_new_customer.
	 *
	 * @since 3.1
	 */
	public function test_wc_create_new_customer() {

		// Basic.
		$id = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$this->assertTrue( is_numeric( $id ) && $id > 0 );

		// Existing email.
		$id = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$this->assertInstanceOf( 'WP_Error', $id );

		// Empty username.
		$id = wc_create_new_customer( '', 'testuser', 'testpassword' );
		$this->assertInstanceOf( 'WP_Error', $id );

		// Bad email.
		$id = wc_create_new_customer( 'bademail', 'testuser', 'testpassword' );
		$this->assertInstanceOf( 'WP_Error', $id );

		// Existing username.
		$id = wc_create_new_customer( 'test2@example.com', 'testuser', 'testpassword' );
		$this->assertInstanceOf( 'WP_Error', $id );

		// Username with auto-generation.
		update_option( 'woocommerce_registration_generate_username', 'yes' );
		$id       = wc_create_new_customer( 'fred@example.com', '', 'testpassword' );
		$userdata = get_userdata( $id );
		$this->assertEquals( 'fred', $userdata->user_login );
		$id       = wc_create_new_customer( 'fred@mail.com', '', 'testpassword' );
		$userdata = get_userdata( $id );
		$this->assertNotEquals( 'fred', $userdata->user_login );
		$this->assertContains( 'fred', $userdata->user_login );
		$id       = wc_create_new_customer( 'fred@test.com', '', 'testpassword' );
		$userdata = get_userdata( $id );
		$this->assertNotEquals( 'fred', $userdata->user_login );
		$this->assertContains( 'fred', $userdata->user_login );

		// Test extra arguments to generate display_name.
		$id       = wc_create_new_customer(
			'john.doe@example.com',
			'',
			'testpassword',
			array(
				'first_name' => 'John',
				'last_name'  => 'Doe',
			)
		);
		$userdata = get_userdata( $id );
		$this->assertEquals( 'John Doe', $userdata->display_name );

		// No password.
		update_option( 'woocommerce_registration_generate_password', 'no' );
		$id = wc_create_new_customer( 'joe@example.com', 'joecustomer', '' );
		$this->assertInstanceOf( 'WP_Error', $id );

		// Auto-generated password.
		update_option( 'woocommerce_registration_generate_password', 'yes' );
		$id = wc_create_new_customer( 'joe@example.com', 'joecustomer', '' );
		$this->assertTrue( is_numeric( $id ) && $id > 0 );
	}

	/**
	 * Test username generation.
	 */
	public function test_wc_create_new_customer_username() {
		// Test getting name from email.
		$this->assertEquals( 'mike', wc_create_new_customer_username( 'mike@fakemail.com', array() ) );

		// Test getting name if username exists.
		wc_create_new_customer( 'mike@fakemail.com', '', 'testpassword' );
		$username = wc_create_new_customer_username( 'mike@fakemail.com', array() );
		$this->assertNotEquals( 'mike', $username, $username );
		$this->assertContains( 'mike', $username, $username );

		// Test common email prefix avoidance.
		$this->assertEquals( 'somecompany.com', wc_create_new_customer_username( 'info@somecompany.com', array() ) );

		// Test first/last name generation.
		$this->assertEquals(
			'bob.bobson',
			wc_create_new_customer_username(
				'bob@bobbobson.com',
				array(
					'first_name' => 'Bob',
					'last_name'  => 'Bobson',
				)
			)
		);

		// Test unicode fallbacks.
		$this->assertEquals(
			'unicode',
			wc_create_new_customer_username(
				'unicode@unicode.com',
				array(
					'first_name' => 'こんにちは',
					'last_name'  => 'こんにちは',
				)
			)
		);

		// Test username generation triggered by illegal_user_logins filter.
		add_filter( 'illegal_user_logins', array( $this, 'setup_illegal_user_logins' ) );

		$this->assertStringStartsWith(
			'woo_user_',
			wc_create_new_customer_username( 'test@test.com' )
		);

		remove_filter( 'illegal_user_logins', array( $this, 'setup_illegal_user_logins' ) );
	}

	/**
	 * Test wc_update_new_customer_past_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_update_new_customer_past_orders() {
		$customer_id = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$order1      = new WC_Order();
		$order1->set_billing_email( 'test@example.com' );
		$order1->set_status( 'completed' );
		$order1->save();
		$order2 = new WC_Order();
		$order2->save();

		// Test download permissions.
		$prod_download = new WC_Product_Download();
		$prod_download->set_file( plugin_dir_url( __FILE__ ) . '/assets/images/help.png' );
		$prod_download->set_id( 'download' );

		$product = new WC_Product_Simple();
		$product->set_downloadable( 'yes' );
		$product->set_downloads( array( $prod_download ) );
		$product->save();

		$order3 = new WC_Order();
		$item   = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
			)
		);
		$order3->set_billing_email( 'test@example.com' );
		$order3->set_status( 'completed' );
		$order3->add_item( $item );
		$order3->save();

		$downloads = wc_get_customer_available_downloads( $customer_id );
		$this->assertEquals( 0, count( $downloads ) );

		// Link orders that haven't been linked.
		$linked = wc_update_new_customer_past_orders( $customer_id );
		$this->assertEquals( 2, $linked );
		$order1 = wc_get_order( $order1->get_id() );
		$this->assertEquals( $customer_id, $order1->get_customer_id() );
		$order3 = wc_get_order( $order3->get_id() );
		$this->assertEquals( $customer_id, $order3->get_customer_id() );

		// Test download permissions.
		$downloads = wc_get_customer_available_downloads( $customer_id );
		$this->assertEquals( 1, count( $downloads ) );

		// Don't link linked orders again.
		$linked = wc_update_new_customer_past_orders( $customer_id );
		$this->assertEquals( 0, $linked );
	}

	/**
	 * Test wc_update_new_customer_past_orders with invalid or changed email.
	 *
	 * @since 3.1
	 */
	public function test_wc_update_new_customer_past_orders_invalid_changed_email() {
		$customer_id = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$order1      = new WC_Order();
		$order1->set_billing_email( 'test@example.com' );
		$order1->set_status( 'completed' );
		$order1->save();

		wp_update_user(
			array(
				'ID'         => $customer_id,
				'user_email' => 'invalid',
			)
		);
		$linked = wc_update_new_customer_past_orders( $customer_id );
		$this->assertEquals( 0, $linked );

		wp_update_user(
			array(
				'ID'         => $customer_id,
				'user_email' => 'new@example.com',
			)
		);
		$linked = wc_update_new_customer_past_orders( $customer_id );
		$this->assertEquals( 0, $linked );
	}

	/**
	 * Test wc_paying_customer.
	 *
	 * @since 3.1
	 */
	public function test_wc_paying_customer() {
		$customer_id = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );

		$customer = new WC_Customer( $customer_id );
		$this->assertFalse( $customer->get_is_paying_customer() );

		// Test after new order created.
		$order1 = new WC_Order();
		$order1->set_customer_id( $customer_id );
		$order1->set_status( 'completed' );
		$order1->save();

		$customer = new WC_Customer( $customer_id );
		$this->assertTrue( $customer->get_is_paying_customer() );

		// Test after the order is deleted!
		$order1->delete( true );
		$customer = new WC_Customer( $customer_id );
		$this->assertFalse( $customer->get_is_paying_customer() );
	}

	/**
	 * Test wc_customer_bought_product.
	 *
	 * @since 3.1
	 */
	public function test_wc_customer_bought_product() {
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

	/**
	 * Test wc_customer_has_capability.
	 *
	 * @since 3.1
	 */
	public function test_wc_customer_has_capability() {
		$customer_id = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );

		$order = new WC_Order();
		$order->set_customer_id( $customer_id );
		$order->save();

		// Can view same user's order.
		$allcaps = wc_customer_has_capability( array(), array( 'view_order' ), array( 'view_order', $customer_id, $order->get_id() ) );
		$this->assertTrue( $allcaps['view_order'] );

		// Can't view other user's order.
		$allcaps = wc_customer_has_capability( array(), array( 'view_order' ), array( 'view_order', 99, $order->get_id() ) );
		$this->assertTrue( empty( $allcaps['view_order'] ) );

		// Can pay same user's order.
		$allcaps = wc_customer_has_capability( array(), array( 'pay_for_order' ), array( 'pay_for_order', $customer_id, $order->get_id() ) );
		$this->assertTrue( $allcaps['pay_for_order'] );

		// Can't pay other user's order.
		$allcaps = wc_customer_has_capability( array(), array( 'pay_for_order' ), array( 'pay_for_order', 99, $order->get_id() ) );
		$this->assertTrue( empty( $allcaps['pay_for_order'] ) );

		// Can pay new order.
		$allcaps = wc_customer_has_capability( array(), array( 'pay_for_order' ), array( 'pay_for_order', $customer_id, null ) );
		$this->assertTrue( $allcaps['pay_for_order'] );

		// Can order user's order again.
		$allcaps = wc_customer_has_capability( array(), array( 'order_again' ), array( 'order_again', $customer_id, $order->get_id() ) );
		$this->assertTrue( $allcaps['order_again'] );

		// Can't order other user's order again.
		$allcaps = wc_customer_has_capability( array(), array( 'order_again' ), array( 'order_again', 99, $order->get_id() ) );
		$this->assertTrue( empty( $allcaps['order_again'] ) );

		// Can cancel order.
		$allcaps = wc_customer_has_capability( array(), array( 'cancel_order' ), array( 'cancel_order', $customer_id, $order->get_id() ) );
		$this->assertTrue( $allcaps['cancel_order'] );

		// Can't cancel other user's order.
		$allcaps = wc_customer_has_capability( array(), array( 'cancel_order' ), array( 'cancel_order', 99, $order->get_id() ) );
		$this->assertTrue( empty( $allcaps['cancel_order'] ) );

		$download = new WC_Customer_Download();
		$download->set_user_id( $customer_id );
		$download->save();

		// Can download.
		$allcaps = wc_customer_has_capability( array(), array( 'download_file' ), array( 'download_file', $customer_id, $download ) );
		$this->assertTrue( $allcaps['download_file'] );

		// Can't download other user's download.
		$allcaps = wc_customer_has_capability( array(), array( 'download_file' ), array( 'download_file', 99, $download ) );
		$this->assertTrue( empty( $allcaps['download_file'] ) );
	}

	/**
	 * Test wc_get_customer_download_permissions.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_customer_download_permissions() {
		$customer_id_1 = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$customer_id_2 = wc_create_new_customer( 'test2@example.com', 'testuser2', 'testpassword2' );

		$download_1 = new WC_Customer_Download();
		$download_1->set_user_id( $customer_id_1 );
		$download_1->set_order_id( 1 );
		$download_1->save();
		$download_2 = new WC_Customer_Download();
		$download_2->set_user_id( $customer_id_2 );
		$download_2->set_order_id( 2 );
		$download_2->save();

		$permissions = wc_get_customer_download_permissions( $customer_id_1 );
		$this->assertEquals( 1, count( $permissions ) );
		$this->assertEquals( $download_1->get_id(), $permissions[0]->permission_id );

		$permissions = wc_get_customer_download_permissions( $customer_id_2 );
		$this->assertEquals( 1, count( $permissions ) );
		$this->assertEquals( $download_2->get_id(), $permissions[0]->permission_id );
	}

	/**
	 * Test wc_get_customer_available_downloads.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_customer_available_downloads() {
		$customer_id = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );

		$prod_download = new WC_Product_Download();
		$prod_download->set_file( plugin_dir_url( __FILE__ ) . '/assets/images/help.png' );
		$prod_download->set_id( 1 );

		$product = new WC_Product_Simple();
		$product->set_downloadable( 'yes' );
		$product->set_downloads( array( $prod_download ) );
		$product->save();

		$cust_download = new WC_Customer_Download();
		$cust_download->set_user_id( $customer_id );
		$cust_download->set_product_id( $product->get_id() );
		$cust_download->set_download_id( $prod_download->get_id() );
		$cust_download->save();

		$order = new WC_Order();
		$order->set_customer_id( $customer_id );
		$order->set_status( 'completed' );
		$order->save();

		$cust_download->set_order_id( $order->get_id() );
		$cust_download->save();

		$downloads = wc_get_customer_available_downloads( $customer_id );
		$this->assertEquals( 1, count( $downloads ) );

		$download = current( $downloads );
		$this->assertEquals( $prod_download->get_id(), $download['download_id'] );
		$this->assertEquals( $order->get_id(), $download['order_id'] );
		$this->assertEquals( $product->get_id(), $download['product_id'] );
	}

	/**
	 * Test wc_get_customer_total_spent.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_customer_total_spent() {
		$customer_id_1 = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$customer_id_2 = wc_create_new_customer( 'test2@example.com', 'testuser2', 'testpassword2' );

		$order_1 = new WC_Order();
		$order_1->set_status( 'completed' );
		$order_1->set_total( '100.00' );
		$order_1->set_customer_id( $customer_id_1 );
		$order_1->save();
		$order_2 = new WC_Order();
		$order_2->set_status( 'completed' );
		$order_2->set_total( '15.50' );
		$order_2->set_customer_id( $customer_id_1 );
		$order_2->save();
		$order_3 = new WC_Order();
		$order_3->set_status( 'completed' );
		$order_3->set_total( '50.01' );
		$order_3->set_customer_id( $customer_id_2 );
		$order_3->save();
		$order_4 = new WC_Order();
		$order_4->set_status( 'pending' );
		$order_4->set_total( '1.00' );
		$order_4->set_customer_id( $customer_id_2 );
		$order_4->save();

		$this->assertEquals( 115.5, wc_get_customer_total_spent( $customer_id_1 ) );
		$this->assertEquals( 50.01, wc_get_customer_total_spent( $customer_id_2 ) );
	}

	/**
	 * Test wc_get_customer_order_count.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_customer_order_count() {
		$customer_id_1 = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$customer_id_2 = wc_create_new_customer( 'test2@example.com', 'testuser2', 'testpassword2' );

		$order_1 = new WC_Order();
		$order_1->set_customer_id( $customer_id_1 );
		$order_1->save();
		$order_2 = new WC_Order();
		$order_2->set_customer_id( $customer_id_1 );
		$order_2->save();
		$order_3 = new WC_Order();
		$order_3->set_customer_id( $customer_id_2 );
		$order_3->save();

		$this->assertEquals( 2, wc_get_customer_order_count( $customer_id_1 ) );
		$this->assertEquals( 1, wc_get_customer_order_count( $customer_id_2 ) );
	}

	/**
	 * Test wc_reset_order_customer_id_on_deleted_user.
	 *
	 * @since 3.1
	 */
	public function test_wc_reset_order_customer_id_on_deleted_user() {
		$customer_id = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$customer    = new WC_Customer( $customer_id );

		$order = new WC_Order();
		$order->set_customer_id( $customer_id );
		$order->save();

		$customer->delete();

		$order = new WC_Order( $order->get_id() );
		$this->assertEquals( 0, $order->get_customer_id() );
	}

	/**
	 * Test wc_get_customer_last_order.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_customer_last_order() {
		$customer_id = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );

		$order_1 = new WC_Order();
		$order_1->set_customer_id( $customer_id );
		$order_1->save();
		$order_2 = new WC_Order();
		$order_2->set_customer_id( $customer_id );
		$order_2->save();

		$last_order = wc_get_customer_last_order( $customer_id );
		$this->assertEquals( $order_2->get_id(), $last_order->get_id() );
	}
}
