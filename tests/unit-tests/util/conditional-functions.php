<?php

/**
 * Class Conditional_Functions.
 * @package WooCommerce\Tests\Util
 * @since 2.3.0
 */
class WC_Tests_Conditional_Functions extends WC_Unit_Test_Case {

	/**
	 * Test is_store_notice_showing().
	 *
	 * @since 2.3.0
	 */
	public function test_is_store_notice_showing() {

		$this->assertFalse( is_store_notice_showing() );
	}

	/**
	 * Test wc_tax_enabled().
	 *
	 * @since 2.3.0
	 */
	public function test_wc_tax_enabled() {

		$this->assertFalse( wc_tax_enabled() );
	}

	/**
	 * Test wc_prices_include_tax().
	 *
	 * @since 2.3.0
	 */
	public function test_wc_prices_include_tax() {

		$this->assertFalse( wc_prices_include_tax() );
	}

	/**
	 * Data provider for test_wc_is_valid_url.
	 *
	 * @since 2.4
	 */
	public function data_provider_test_wc_is_valid_url() {
		return array(
			// Test some invalid URLs
			array( false, wc_is_valid_url( 'google.com' ) ),
			array( false, wc_is_valid_url( 'ftp://google.com' ) ),
			array( false, wc_is_valid_url( 'sftp://google.com' ) ),
			array( false, wc_is_valid_url( 'https://google.com/test invalid' ) ),

			// Test some valid URLs
			array( true,  wc_is_valid_url( 'http://google.com' ) ),
			array( true,  wc_is_valid_url( 'https://google.com' ) ),
			array( true,  wc_is_valid_url( 'https://google.com/test%20valid' ) ),
			array( true,  wc_is_valid_url( 'https://google.com/test-valid/?query=test' ) ),
			array( true,  wc_is_valid_url( 'https://google.com/test-valid/#hash' ) ),
		);
	}

	/**
	 * Test wc_site_is_https().
	 */
	public function test_wc_site_is_https() {
		$this->assertFalse( wc_site_is_https() );

		add_filter( 'pre_option_home', array( $this, '_https_url' ) );

		$this->assertTrue( wc_site_is_https() );
	}

	/**
	 * Callback for chaning home url to https.
	 *
	 * @return string
	 */
	public function _https_url() {
		return 'https://example.org';
	}

	/**
	 * Test wc_is_valid_url().
	 *
	 * @dataProvider data_provider_test_wc_is_valid_url
	 * @since 2.3.0
	 */
	public function test_wc_is_valid_url( $assert, $values ) {
		$this->assertEquals( $assert,  $values );
	}
}
