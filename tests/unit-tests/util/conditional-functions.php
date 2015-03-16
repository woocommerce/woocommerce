<?php
namespace WooCommerce\Tests\Util;

/**
 * Class Conditional_Functions
 * @package WooCommerce\Tests\Util
 * @since 2.3.0
 */
class Conditional_Functions extends \WC_Unit_Test_Case {

	/**
	 * Test is_store_notice_showing()
	 *
	 * @since 2.3.0
	 */
	public function test_is_store_notice_showing() {

		$this->assertEquals( false, is_store_notice_showing() );
	}

	/**
	 * Test wc_tax_enabled()
	 *
	 * @since 2.3.0
	 */
	public function test_wc_tax_enabled() {

		$this->assertEquals( false, wc_tax_enabled() );
	}

	/**
	 * Test wc_prices_include_tax()
	 *
	 * @since 2.3.0
	 */
	public function test_wc_prices_include_tax() {

		$this->assertEquals( false, wc_prices_include_tax() );
	}

	/**
	 * Test wc_is_valid_url()
	 *
	 * @since 2.3.0
	 */
	public function test_wc_is_valid_url() {

		// Test some invalid URLs
		$this->assertEquals( false, wc_is_valid_url( 'google.com' ) );
		$this->assertEquals( false, wc_is_valid_url( 'ftp://google.com' ) );
		$this->assertEquals( false, wc_is_valid_url( 'sftp://google.com' ) );
		$this->assertEquals( false, wc_is_valid_url( 'https://google.com/test invalid' ) );

		// Test some valid URLs
		$this->assertEquals( true,  wc_is_valid_url( 'http://google.com' ) );
		$this->assertEquals( true,  wc_is_valid_url( 'https://google.com' ) );
		$this->assertEquals( true,  wc_is_valid_url( 'https://google.com/test%20valid' ) );
		$this->assertEquals( true,  wc_is_valid_url( 'https://google.com/test-valid/?query=test' ) );
		$this->assertEquals( true,  wc_is_valid_url( 'https://google.com/test-valid/#hash' ) );
	}
}
