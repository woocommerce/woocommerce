<?php
namespace WooCommerce\Tests\Util;

/**
 * Class Conditional_Functions.
 * @package WooCommerce\Tests\Util
 * @since 2.3.0
 */
class Conditional_Functions extends \WC_Unit_Test_Case {

	/**
	 * Test is_store_notice_showing().
	 *
	 * @since 2.3.0
	 */
	public function test_is_store_notice_showing() {

		$this->assertEquals( false, is_store_notice_showing() );
	}

	/**
	 * Test wc_tax_enabled().
	 *
	 * @since 2.3.0
	 */
	public function test_wc_tax_enabled() {

		$this->assertEquals( false, wc_tax_enabled() );
	}

	/**
	 * Test wc_prices_include_tax().
	 *
	 * @since 2.3.0
	 */
	public function test_wc_prices_include_tax() {

		$this->assertEquals( false, wc_prices_include_tax() );
	}

	/**
	 * Data provider for test_wc_is_webhook_valid_topic.
	 *
	 * @since 2.4
	 */
	public function data_provider_test_wc_is_webhook_valid_topic() {
		return array(
			array( true, wc_is_webhook_valid_topic( 'action.woocommerce_add_to_cart' ) ),
			array( true, wc_is_webhook_valid_topic( 'action.wc_add_to_cart' ) ),
			array( true, wc_is_webhook_valid_topic( 'product.created' ) ),
			array( true, wc_is_webhook_valid_topic( 'product.updated' ) ),
			array( true, wc_is_webhook_valid_topic( 'product.deleted' ) ),
			array( true, wc_is_webhook_valid_topic( 'order.created' ) ),
			array( true, wc_is_webhook_valid_topic( 'order.updated' ) ),
			array( true, wc_is_webhook_valid_topic( 'order.deleted' ) ),
			array( true, wc_is_webhook_valid_topic( 'customer.created' ) ),
			array( true, wc_is_webhook_valid_topic( 'customer.updated' ) ),
			array( true, wc_is_webhook_valid_topic( 'customer.deleted' ) ),
			array( true, wc_is_webhook_valid_topic( 'coupon.created' ) ),
			array( true, wc_is_webhook_valid_topic( 'coupon.updated' ) ),
			array( true, wc_is_webhook_valid_topic( 'coupon.deleted' ) ),
			array( false, wc_is_webhook_valid_topic( 'coupon.upgraded' ) ),
			array( false, wc_is_webhook_valid_topic( 'wc.product.updated' ) ),
			array( false, wc_is_webhook_valid_topic( 'missingdot' ) ),
			array( false, wc_is_webhook_valid_topic( 'with space' ) )
		);
	}

	/**
	 * Test wc_is_webhook_valid_topic().
	 *
	 * @dataProvider data_provider_test_wc_is_webhook_valid_topic
	 * @since 2.4
	 */
	public function test_wc_is_webhook_valid_topic( $assert, $values ) {
		$this->assertEquals( $assert, $values );
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
			array( true,  wc_is_valid_url( 'https://google.com/test-valid/#hash' ) )
		);
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
