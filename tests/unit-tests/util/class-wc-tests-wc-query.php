<?php
/**
 *
 */

/**
 *
 */
class WC_Tests_WC_Query extends WC_Unit_Test_Case {

	public function test_instance() {
		$this->assertInstanceOf( 'WC_Query', WC()->query );
	}

	public function test_get_errors() {
		$_GET['wc_error'] = 'test';

		WC()->query->get_errors();
		$this->assertTrue( wc_has_notice( 'test', 'error' ) );

		// Clean up.
		unset( $_GET['wc_error'] );
		wc_clear_notices();

		WC()->query->get_errors();
		$this->assertFalse( wc_has_notice( 'test', 'error' ) );

	}

	public function test_init_query_vars_get_query_vars() {
		// Test the default options are present.
		WC()->query->init_query_vars();
		$default_vars = WC()->query->get_query_vars();
		$expected = array(
			'product-page'               => '',
			'order-pay'                  => 'order-pay',
			'order-received'             => 'order-received',
			'orders'                     => 'orders',
			'view-order'                 => 'view-order',
			'downloads'                  => 'downloads',
			'edit-account'               => 'edit-account',
			'edit-address'               => 'edit-address',
			'payment-methods'            => 'payment-methods',
			'lost-password'              => 'lost-password',
			'customer-logout'            => 'customer-logout',
			'add-payment-method'         => 'add-payment-method',
			'delete-payment-method'      => 'delete-payment-method',
			'set-default-payment-method' => 'set-default-payment-method',
		);
		$this->assertEquals( $expected, $default_vars );

		// Test updating a setting works.
		update_option( 'woocommerce_checkout_pay_endpoint', 'order-pay-new' );
		WC()->query->init_query_vars();
		$updated_vars = WC()->query->get_query_vars();
		$this->assertEquals( 'order-pay-new', $updated_vars['order-pay'] );

		// Clean up.
		update_option( 'woocommerce_checkout_pay_endpoint', 'order-pay' );
	}

	public function test_get_endpoint_title() {
		$endpoints = array(
			'order-pay',
			'order-received',
			'orders',
			'downloads',
			'edit-account',
			'edit-address',
			'payment-methods',
			'add-payment-method',
			'lost-password'
		);

		foreach ( $endpoints as $endpoint ) {
			$this->assertNotEquals( '', WC()->query->get_endpoint_title( $endpoint ) );
		}

		$this->assertEquals( '', WC()->query->get_endpoint_title( 'not-real-endpoint' ) );
	}

	public function test_get_endpoint_mask() {
		$this->assertEquals( EP_PAGES, WC()->query->get_endpoints_mask() );

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', 999 );
		update_option( 'woocommerce_checkout_page_id', 999 );
		$this->assertEquals( EP_ROOT | EP_PAGES, WC()->query->get_endpoints_mask() );
	}

	public function test_add_query_vars() {
		WC()->query->init_query_vars();

		$vars = array(
			'test1',
			'test2',
		);
		$added = WC()->query->add_query_vars( $vars );

		$this->assertContains( 'test1', $added );
		$this->assertContains( 'test2', $added );
		$this->assertContains( 'order-pay', $added );
		$this->assertContains( 'customer-logout', $added );
	}

	public function test_get_current_endpoint() {
		global $wp;

		$this->assertEquals( '', WC()->query->get_current_endpoint() );
		$wp->query_vars['order-pay'] = 'order-pay';
		$this->assertEquals( 'order-pay', WC()->query->get_current_endpoint() );
	}

	public function test_parse_request() {
		global $wp;

		// Test with $_GET.
		WC()->query->init_query_vars();
		$_GET['order-pay'] = 'order-pay';
		WC()->query->parse_request();
		$this->assertEquals( 'order-pay', $wp->query_vars['order-pay'] );
		unset( $_GET['order-pay'] );

		// Test with query var.
		update_option( 'woocommerce_checkout_pay_endpoint', 'order-pay-new' );
		WC()->query->init_query_vars();
		$wp->query_vars['order-pay-new'] = 'order-pay-new';
		WC()->query->parse_request();
		$this->assertEquals( 'order-pay-new', $wp->query_vars['order-pay'] );
	}

	public function test_pre_get_posts() {

	}

}
