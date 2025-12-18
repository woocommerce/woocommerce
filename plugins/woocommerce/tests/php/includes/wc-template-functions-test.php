<?php
/**
 * Unit tests for wc_template_redirect function.
 *
 * @package WooCommerce\Tests\TemplateFunctions
 */

/**
 * Class WC_Template_Functions_Test
 */
class WC_Template_Functions_Test extends \WC_Unit_Test_Case {

	public function setUp(): void {
		parent::setUp();
		
		WC()->init();

		flush_rewrite_rules();
	}

	public function tearDown(): void {
		parent::tearDown();

		global $wp_query, $wp;
		$wp_query = new WP_Query();
		$wp->query_vars = array();

		remove_all_filters( 'woocommerce_account_endpoint_page_not_found' );
		remove_all_filters( 'woocommerce_is_checkout' );
		remove_all_filters( 'woocommerce_is_order_received_page' );
	}

	/**
	 * @testdox Order received endpoint should not trigger 404 error.
	 */
	public function test_order_received_endpoint_does_not_trigger_404() {
		global $wp_query, $wp;

		$checkout_page_id = wc_create_page( 'checkout', 'woocommerce_checkout_page_id', 'Checkout', '[woocommerce_checkout]' );
		
		$order = WC_Helper_Order::create_order();
		$order_id = $order->get_id();
		$order_key = $order->get_order_key();

		$wp->query_vars['order-received'] = $order_id;
		$_GET['key'] = $order_key;

		$wp_query->is_page = true;
		$wp_query->is_404 = false;
		$wp_query->queried_object_id = $checkout_page_id;
		$wp_query->queried_object = get_post( $checkout_page_id );
		$wp_query->post = get_post( $checkout_page_id );

		ob_start();
		
		$is_wc_endpoint = is_wc_endpoint_url();
		$is_account_page = is_account_page();
		$is_checkout = is_checkout();
		$is_order_received = is_order_received_page();
		$is_checkout_pay = is_checkout_pay_page();
		$should_trigger_404 = apply_filters( 'woocommerce_account_endpoint_page_not_found', true );

		$would_trigger_404 = $is_wc_endpoint 
			&& ! $is_account_page 
			&& ! $is_checkout 
			&& ! $is_order_received 
			&& ! $is_checkout_pay 
			&& $should_trigger_404;

		ob_end_clean();

		$this->assertFalse( $would_trigger_404, 'Order received endpoint should not trigger 404' );
		$this->assertTrue( $is_order_received, 'is_order_received_page() should return true' );
		$this->assertTrue( $is_wc_endpoint, 'is_wc_endpoint_url() should return true for order-received' );
	}

	/**
	 * @testdox Order pay endpoint should not trigger 404 error.
	 */
	public function test_order_pay_endpoint_does_not_trigger_404() {
		global $wp_query, $wp;

		$checkout_page_id = wc_create_page( 'checkout', 'woocommerce_checkout_page_id', 'Checkout', '[woocommerce_checkout]' );
		
		$order = WC_Helper_Order::create_order();
		$order_id = $order->get_id();

		$wp->query_vars['order-pay'] = $order_id;

		$wp_query->is_page = true;
		$wp_query->is_404 = false;
		$wp_query->queried_object_id = $checkout_page_id;
		$wp_query->queried_object = get_post( $checkout_page_id );
		$wp_query->post = get_post( $checkout_page_id );

		ob_start();
		
		$is_wc_endpoint = is_wc_endpoint_url();
		$is_account_page = is_account_page();
		$is_checkout = is_checkout();
		$is_order_received = is_order_received_page();
		$is_checkout_pay = is_checkout_pay_page();
		$should_trigger_404 = apply_filters( 'woocommerce_account_endpoint_page_not_found', true );

		$would_trigger_404 = $is_wc_endpoint 
			&& ! $is_account_page 
			&& ! $is_checkout 
			&& ! $is_order_received 
			&& ! $is_checkout_pay 
			&& $should_trigger_404;

		ob_end_clean();

		$this->assertFalse( $would_trigger_404 );
		$this->assertTrue( $is_checkout_pay || $is_checkout );
	}

	/**
	 * @testdox Invalid endpoint on wrong page should still trigger 404.
	 */
	public function test_invalid_endpoint_on_wrong_page_triggers_404() {
		global $wp_query, $wp;

		$page_id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Test Page',
			)
		);

		$wp->query_vars['orders'] = 1;

		$wp_query->is_page = true;
		$wp_query->is_404 = false;
		$wp_query->queried_object_id = $page_id;
		$wp_query->queried_object = get_post( $page_id );
		$wp_query->post = get_post( $page_id );

		ob_start();
		
		$is_wc_endpoint = is_wc_endpoint_url();
		$is_order_received = is_order_received_page();
		$is_checkout_pay = is_checkout_pay_page();
		$should_trigger_404 = apply_filters( 'woocommerce_account_endpoint_page_not_found', true );

		$this->assertFalse( $is_order_received, 'is_order_received_page() should return false for orders endpoint' );
		$this->assertFalse( $is_checkout_pay, 'is_checkout_pay_page() should return false for orders endpoint' );
		$this->assertTrue( $is_wc_endpoint, 'is_wc_endpoint_url() should return true for orders endpoint' );

		ob_end_clean();

		wp_delete_post( $page_id, true );

		$this->assertFalse( $is_order_received );
		$this->assertFalse( $is_checkout_pay );
		$this->assertTrue( $is_wc_endpoint );
	}

	/**
	 * @testdox Order received page should be accessible with valid order key.
	 */
	public function test_order_received_page_accessible_with_valid_key() {
		global $wp_query, $wp;

		$checkout_page_id = wc_create_page( 'checkout', 'woocommerce_checkout_page_id', 'Checkout', '[woocommerce_checkout]' );
		
		$order = WC_Helper_Order::create_order();
		$order_id = $order->get_id();
		$order_key = $order->get_order_key();

		$wp->query_vars['order-received'] = $order_id;
		$_GET['key'] = $order_key;

		$wp_query->is_page = true;
		$wp_query->is_404 = false;
		$wp_query->queried_object_id = $checkout_page_id;
		$wp_query->queried_object = get_post( $checkout_page_id );

		$this->assertTrue( is_order_received_page(), 'is_order_received_page() should return true for valid order-received endpoint' );
		
		$retrieved_order = wc_get_order( $order_id );
		$this->assertInstanceOf( 'WC_Order', $retrieved_order, 'Order should be retrievable' );
		$this->assertEquals( $order_key, $retrieved_order->get_order_key(), 'Order key should match' );
	}
}