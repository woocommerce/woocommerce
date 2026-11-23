<?php

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\Utilities\DiscountsUtil;

/**
 * Tests for the discounts utility class.
 */
class DiscountsUtilTest extends \WC_Unit_Test_Case {
	/**
	 * @testdox `is_coupon_emails_allowed` should return true/false based on direct match, wildcard match, case sensitivity, empty allowed list, and special characters.
	 */
	public function test_is_coupon_emails_allowed() {
		// Direct match.
		$this->assertEquals( true, DiscountsUtil::is_coupon_emails_allowed( array( 'customer@wc.local' ), array( 'customer@wc.local' ) ) );
		$this->assertEquals( false, DiscountsUtil::is_coupon_emails_allowed( array( 'customer@wc.local' ), array( 'customer2@wc.local' ) ) );

		// Wildcard match.
		$this->assertEquals( true, DiscountsUtil::is_coupon_emails_allowed( array( 'customer@wc.local' ), array( '*.local' ) ) );
		$this->assertEquals( false, DiscountsUtil::is_coupon_emails_allowed( array( 'customer@wc.local' ), array( '*.test' ) ) );

		// Case sensitivity.
		$this->assertEquals( false, DiscountsUtil::is_coupon_emails_allowed( array( 'Customer@WC.local' ), array( 'customer@wc.local' ) ) );

		// Empty allowed list.
		$this->assertEquals( false, DiscountsUtil::is_coupon_emails_allowed( array( 'customer@wc.local' ), array() ) );
		$this->assertEquals( false, DiscountsUtil::is_coupon_emails_allowed( array(), array( 'customer@wc.local' ) ) );

		// Special characters in email.
		$this->assertEquals( false, DiscountsUtil::is_coupon_emails_allowed( array( 'special@example.com' ), array( 'special+char@example.com' ) ) );
	}

	/**
	 * @testdox `is_coupon_emails_allowed` should treat only the asterisk as a wildcard and match every other character literally.
	 */
	public function test_is_coupon_emails_allowed_matches_characters_literally() {
		$this->assertFalse( DiscountsUtil::is_coupon_emails_allowed( array( 'janexdoe@wc.local' ), array( 'jane.doe@wc.local' ) ) );
		$this->assertFalse( DiscountsUtil::is_coupon_emails_allowed( array( 'jane-doe@wc.local' ), array( 'jane.doe@wc.local' ) ) );
		$this->assertFalse( DiscountsUtil::is_coupon_emails_allowed( array( 'customer@staffxwc.local' ), array( '*@staff.wc.local' ) ) );
		$this->assertFalse( DiscountsUtil::is_coupon_emails_allowed( array( 'janevip@wc.local' ), array( 'jane+vip@wc.local' ) ) );
		$this->assertFalse( DiscountsUtil::is_coupon_emails_allowed( array( 'another@wc.local' ), array( 'a|b@wc.local' ) ) );
		$this->assertFalse( DiscountsUtil::is_coupon_emails_allowed( array( 'x@wc.local' ), array( 'a/b@wc.local' ) ) );

		$this->assertTrue( DiscountsUtil::is_coupon_emails_allowed( array( 'jane+vip@wc.local' ), array( '*+vip@wc.local' ) ) );
		$this->assertTrue( DiscountsUtil::is_coupon_emails_allowed( array( 'a|b@wc.local' ), array( 'a|*@wc.local' ) ) );
		$this->assertTrue( DiscountsUtil::is_coupon_emails_allowed( array( 'a/b@wc.local' ), array( 'a/*@wc.local' ) ) );
		$this->assertTrue( DiscountsUtil::is_coupon_emails_allowed( array( 'customer@staff.wc.local' ), array( '*@staff.wc.local' ) ) );
		$this->assertTrue( DiscountsUtil::is_coupon_emails_allowed( array( 'jane+vip@wc.local' ), array( 'jane+*@wc.local' ) ) );
		$this->assertFalse( DiscountsUtil::is_coupon_emails_allowed( array( 'janexvip@wc.local' ), array( 'jane+*@wc.local' ) ) );
	}
}
