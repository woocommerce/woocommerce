<?php
/**
 * Test for the discounts utility class.
 * @package WooCommerce\Tests\DiscountsUtil
 */

use Automattic\WooCommerce\Utilities\DiscountsUtil;

/**
 * WC_Tests_DiscountsUtil.
 */
class WC_Tests_DiscountsUtil extends WC_Unit_Test_Case {
	/**
	 * Test is_coupon_emails_allowed function, specifically test wildcard emails.
	 *
	 * @return void
	 */
	public function test_is_coupon_emails_allowed() {
		$this->assertEquals( true, DiscountsUtil::is_coupon_emails_allowed( array( 'customer@wc.local' ), array( '*.local' ) ) );
		$this->assertEquals( false, DiscountsUtil::is_coupon_emails_allowed( array( 'customer@wc.local' ), array( '*.test' ) ) );
		$this->assertEquals( true, DiscountsUtil::is_coupon_emails_allowed( array( 'customer@wc.local' ), array( 'customer@wc.local' ) ) );
		$this->assertEquals( false, DiscountsUtil::is_coupon_emails_allowed( array( 'customer@wc.local' ), array( 'customer2@wc.local' ) ) );
	}
}
