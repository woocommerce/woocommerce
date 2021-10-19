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
		$url = wc_get_endpoint_url( 'customer-logout', 'yes', 'https://example.org/' );
		$this->assertEquals( 'https://example.org/?customer-logout=yes', $url );
	}

	/**
	 * Test wc_get_endpoint_url() when the option permalink_structure is set.
	 */
	public function test_wc_get_endpoint_url_should_add_endpoint_to_query_path() {
		global $wp_rewrite;

		update_option( 'permalink_structure', '/%postname%/' );
		$wp_rewrite->use_trailing_slashes = true;

		$url = wc_get_endpoint_url( 'customer-logout', '', 'https://example.org/' );
		$this->assertEquals( 'https://example.org/customer-logout/', $url );

		$url = wc_get_endpoint_url( 'customer-logout', 'yes', 'https://example.org/' );
		$this->assertEquals( 'https://example.org/customer-logout/yes/', $url );

		$url = wc_get_endpoint_url( 'customer-logout', 'yes', 'https://example.org/?foo=bar' );
		$this->assertEquals( 'https://example.org/customer-logout/yes/?foo=bar', $url );

		// test added after issue https://github.com/woocommerce/woocommerce/issues/24240.
		update_option( 'permalink_structure', '/%postname%' );
		$wp_rewrite->use_trailing_slashes = false;

		$url = wc_get_endpoint_url( 'customer-logout', '', 'https://example.org/' );
		$this->assertEquals( 'https://example.org/customer-logout', $url );
	}
}
