<?php
/**
 * Tests for the WC_Query class.
 *
 * @package WooCommerce\Tests\Util
 * @since 3.3.0
 */

/**
 * WC_Query tests.
 */
class WC_Tests_WC_Query extends WC_Unit_Test_Case {

	/**
	 * Test WC_Query gets initialized properly.
	 */
	public function test_instance() {
		$this->assertInstanceOf( 'WC_Query', WC()->query );
	}

	/**
	 * Test the get_errors method.
	 */
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

	/**
	 * Test the init_query_vars and get_query_vars methods.
	 */
	public function test_init_query_vars_get_query_vars() {
		// Test the default options are present.
		WC()->query->init_query_vars();
		$default_vars = WC()->query->get_query_vars();
		$expected     = array(
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

	/**
	 * Test the get_endpoint_title method.
	 */
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
			'lost-password',
		);

		foreach ( $endpoints as $endpoint ) {
			$this->assertNotEquals( '', WC()->query->get_endpoint_title( $endpoint ) );
		}

		$this->assertEquals( '', WC()->query->get_endpoint_title( 'not-real-endpoint' ) );
	}

	/**
	 * Test the get_endpoint_mask method.
	 */
	public function test_get_endpoint_mask() {
		$this->assertEquals( EP_PAGES, WC()->query->get_endpoints_mask() );

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', 999 );
		update_option( 'woocommerce_checkout_page_id', 999 );
		$this->assertEquals( EP_ROOT | EP_PAGES, WC()->query->get_endpoints_mask() );
	}

	/**
	 * Test the add_query_vars method.
	 */
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

	/**
	 * Test the get_current_endpoint method.
	 */
	public function test_get_current_endpoint() {
		global $wp;

		$this->assertEquals( '', WC()->query->get_current_endpoint() );
		$wp->query_vars['order-pay'] = 'order-pay';
		$this->assertEquals( 'order-pay', WC()->query->get_current_endpoint() );
	}

	/**
	 * Test the parse_request method.
	 */
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

	/**
	 * Test the remove_product_query method.
	 */
	public function test_remove_product_query() {
		$this->assertTrue( (bool) has_filter( 'pre_get_posts', array( WC()->query, 'pre_get_posts' ) ) );

		WC()->query->remove_product_query();

		$this->assertFalse( (bool) has_filter( 'pre_get_posts', array( WC()->query, 'pre_get_posts' ) ) );
	}

	/**
	 * Test the remove_ordering_args method.
	 */
	public function test_remove_ordering_args() {
		WC()->query->get_catalog_ordering_args( 'price', 'DESC' );
		$this->assertTrue( (bool) has_filter( 'posts_clauses', array( WC()->query, 'order_by_price_desc_post_clauses' ) ) );

		WC()->query->remove_ordering_args();
		$this->assertFalse( (bool) has_filter( 'posts_clauses', array( WC()->query, 'order_by_price_desc_post_clauses' ) ) );
	}

	/**
	 * Test the get_catalog_ordering_args method.
	 */
	public function test_get_catalog_ordering_args() {
		$data = array(
			array(
				'orderby'  => 'menu_order',
				'order'    => 'ASC',
				'expected' => array(
					'orderby'  => 'menu_order title',
					'order'    => 'ASC',
					'meta_key' => '',
				),
			),
			array(
				'orderby'  => 'title',
				'order'    => 'DESC',
				'expected' => array(
					'orderby'  => 'title',
					'order'    => 'DESC',
					'meta_key' => '',
				),
			),
			array(
				'orderby'  => 'relevance',
				'order'    => 'ASC',
				'expected' => array(
					'orderby'  => 'relevance',
					'order'    => 'DESC', // Relevance is always DESC order.
					'meta_key' => '',
				),
			),
			array(
				'orderby'  => 'rand',
				'order'    => '',
				'expected' => array(
					'orderby'  => 'rand',
					'order'    => 'ASC',
					'meta_key' => '',
				),
			),
			array(
				'orderby'  => 'date',
				'order'    => 'DESC',
				'expected' => array(
					'orderby'  => 'date ID',
					'order'    => 'DESC',
					'meta_key' => '',
				),
			),
			array(
				'orderby'  => 'price',
				'order'    => 'ASC',
				'expected' => array(
					'orderby'  => 'price',
					'order'    => 'ASC',
					'meta_key' => '',
				),
			),
			array(
				'orderby'  => 'price',
				'order'    => 'DESC',
				'expected' => array(
					'orderby'  => 'price',
					'order'    => 'DESC',
					'meta_key' => '',
				),
			),
			array(
				'orderby'  => 'popularity',
				'order'    => 'DESC',
				'expected' => array(
					'orderby'  => 'popularity',
					'order'    => 'DESC',
					'meta_key' => 'total_sales',
				),
			),
			array(
				'orderby'  => 'rating',
				'order'    => 'ASC',
				'expected' => array(
					'orderby'  => array(
						'meta_value_num' => 'DESC',
						'ID'             => 'ASC',
					),
					'order'    => 'ASC',
					'meta_key' => '_wc_average_rating',
				),
			),
			array(
				'orderby'  => 'unknownkey',
				'order'    => 'ASC',
				'expected' => array(
					'orderby'  => 'unknownkey',
					'order'    => 'ASC',
					'meta_key' => '',
				),
			),
			array(
				'orderby'  => 'date',
				'order'    => 'INVALIDORDER',
				'expected' => array(
					'orderby'  => 'date ID',
					'order'    => 'DESC',
					'meta_key' => '',
				),
			),
		);

		foreach ( $data as $test ) {
			$result = WC()->query->get_catalog_ordering_args( $test['orderby'], $test['order'] );
			$this->assertEquals( $test['expected'], $result );
		}
	}

	/**
	 * Test the get_catalog_ordering_args method with $_GET param.
	 */
	public function test_get_catalog_ordering_args_GET() {
		$_GET['orderby'] = 'price-desc';

		$expected = array(
			'orderby'  => 'price',
			'order'    => 'DESC',
			'meta_key' => '',
		);

		$this->assertEquals( $expected, WC()->query->get_catalog_ordering_args() );

		unset( $_GET['orderby'] );
	}

	/**
	 * Test the get_main_query method.
	 */
	public function test_get_main_query() {
		WC()->query->product_query( new WP_Query() );
		$this->assertInstanceOf( 'WP_Query', WC_Query::get_main_query() );
	}

	/**
	 * Test the get_main_tax_query method.
	 */
	public function test_get_main_tax_query() {
		$tax_query = array(
			'taxonomy'         => 'product_tag',
			'field'            => 'slug',
			'terms'            => array( 'test' ),
			'operator'         => 'IN',
			'include_children' => true,
		);

		$query_args = array(
			'tax_query' => array( $tax_query ),
		);

		WC()->query->product_query( new WP_Query( $query_args ) );
		$tax_queries = WC_Query::get_main_tax_query();
		$this->assertContains( $tax_query, $tax_queries );
	}

	/**
	 * Test the get_main_meta_query method.
	 */
	public function test_get_main_meta_query() {
		$meta_query = array(
			'key'     => '_stock',
			'value'   => 10,
			'compare' => '=',
		);

		$query_args = array(
			'meta_query' => array( $meta_query ),
		);

		WC()->query->product_query( new WP_Query( $query_args ) );
		$meta_queries = WC_Query::get_main_meta_query();
		$this->assertContains( $meta_query, $meta_queries );
	}

	/**
	 * Test the remove_add_to_cart_pagination method.
	 */
	public function test_remove_add_to_cart_pagination() {
		$url = 'http://example.com/shop/page/2/?add-to-cart=1';
		$this->assertEquals( 'http://example.com/shop/page/2/', WC()->query->remove_add_to_cart_pagination( $url ) );
	}
}
