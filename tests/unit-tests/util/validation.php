<?php

namespace WooCommerce\Tests\Util;

/**
 * Class Validation
 * @package WooCommerce\Tests\Util
 * @since 2.3
 */
class Validation extends \WC_Unit_Test_Case {

	/**
	 * Test is_email()
	 *
	 * @since 2.3
	 */
	public function test_is_email() {
		$this->assertEquals( 'email@domain.com', \WC_Validation::is_email( 'email@domain.com' ) );
		$this->assertFalse( \WC_Validation::is_email( 'not a mail' ) );
		$this->assertFalse( \WC_Validation::is_email( 'http://test.com' ) );
	}

	/**
	 * Test is_phone()
	 *
	 * @since 2.3
	 */
	public function test_is_phone() {
		$this->assertTrue( \WC_Validation::is_phone( '+00 000 00 00 000' ) );
		$this->assertTrue( \WC_Validation::is_phone( '+00-000-00-00-000' ) );
		$this->assertTrue( \WC_Validation::is_phone( '(000) 00 00 000' ) );
		$this->assertFalse( \WC_Validation::is_phone( '+00.000.00.00.000' ) );
		$this->assertFalse( \WC_Validation::is_phone( '+00 aaa dd ee fff' ) );
	}
}
