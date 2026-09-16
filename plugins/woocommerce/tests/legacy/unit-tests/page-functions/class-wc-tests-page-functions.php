<?php
/**
 * Tests for the functions in includes/wc-page-functions.php.
 *
 * @package WooCommerce\Tests\PageFunctions
 */

/**
 * Page functions tests.
 */
class WC_Tests_Page_Functions extends WC_Unit_Test_Case {

	/**
	 * Test wc_get_endpoint_url() when the option permalink_structure is not set.
	 */
	public function test_wc_get_endpoint_url_should_add_endpoint_to_query_string() {
		$url = wc_get_endpoint_url( 'customer-logout', 'yes', 'https://' . WP_TESTS_DOMAIN . '/' );
		$this->assertEquals( 'https://' . WP_TESTS_DOMAIN . '/?customer-logout=yes', $url );
	}

	/**
	 * Test wc_get_endpoint_url() when the option permalink_structure is set.
	 */
	public function test_wc_get_endpoint_url_should_add_endpoint_to_query_path() {
		global $wp_rewrite;

		update_option( 'permalink_structure', '/%postname%/' );
		$wp_rewrite->use_trailing_slashes = true;

		$url = wc_get_endpoint_url( 'customer-logout', '', 'https://' . WP_TESTS_DOMAIN . '/' );
		$this->assertEquals( 'https://' . WP_TESTS_DOMAIN . '/customer-logout/', $url );

		$url = wc_get_endpoint_url( 'customer-logout', 'yes', 'https://' . WP_TESTS_DOMAIN . '/' );
		$this->assertEquals( 'https://' . WP_TESTS_DOMAIN . '/customer-logout/yes/', $url );

		$url = wc_get_endpoint_url( 'customer-logout', 'yes', 'https://' . WP_TESTS_DOMAIN . '/?foo=bar' );
		$this->assertEquals( 'https://' . WP_TESTS_DOMAIN . '/customer-logout/yes/?foo=bar', $url );

		// test added after issue https://github.com/woocommerce/woocommerce/issues/24240.
		update_option( 'permalink_structure', '/%postname%' );
		$wp_rewrite->use_trailing_slashes = false;

		$url = wc_get_endpoint_url( 'customer-logout', '', 'https://' . WP_TESTS_DOMAIN . '/' );
		$this->assertEquals( 'https://' . WP_TESTS_DOMAIN . '/customer-logout', $url );
	}

	/**
	 * Reset the query globals mutated by the endpoint-title tests and restore the filter
	 * that wc_page_endpoint_title() removes from `the_title` once it matches.
	 */
	public function tearDown(): void {
		global $wp, $wp_query, $wp_the_query, $post;
		$wp           = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query     = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_the_query = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post         = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		delete_option( 'woocommerce_myaccount_page_id' );
		add_filter( 'the_title', 'wc_page_endpoint_title', 10, 2 );

		parent::tearDown();
	}

	/**
	 * Set up the globals for a My Account "orders" endpoint request rendered inside the main
	 * loop, so the conditional tags wc_page_endpoint_title() relies on are all genuinely true.
	 *
	 * @return int The My Account page ID, which is also the queried object.
	 */
	private function set_up_orders_endpoint_in_loop() {
		$page_id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'My account',
				'post_name'   => 'my-account',
			)
		);
		update_option( 'woocommerce_myaccount_page_id', $page_id );

		global $post, $wp, $wp_query, $wp_the_query;
		$post = get_post( $page_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$wp             = new stdClass(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp->query_vars = array( 'orders' => '' );

		$wp_query                    = new WP_Query(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query->is_page           = true;
		$wp_query->in_the_loop       = true;
		$wp_query->queried_object    = $post;
		$wp_query->queried_object_id = $page_id;
		$wp_the_query                = $wp_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- is_main_query() compares against this.

		return $page_id;
	}

	/**
	 * @testdox wc_page_endpoint_title() replaces the queried page's title with the endpoint title.
	 */
	public function test_wc_page_endpoint_title_replaces_queried_page_title() {
		$page_id = $this->set_up_orders_endpoint_in_loop();

		$this->assertSame( 'Orders', wc_page_endpoint_title( 'My account', $page_id ) );
	}

	/**
	 * @testdox wc_page_endpoint_title() leaves titles of other posts untouched so an earlier title does not consume the one-shot the_title filter before the page heading renders.
	 */
	public function test_wc_page_endpoint_title_ignores_non_queried_titles() {
		$this->set_up_orders_endpoint_in_loop();
		$product = WC_Helper_Product::create_simple_product();

		$this->assertSame(
			$product->get_name(),
			wc_page_endpoint_title( $product->get_name(), $product->get_id() ),
			'A title belonging to another post (e.g. a product in a server-rendered mini-cart) must not be replaced with the endpoint title.'
		);
	}
}
