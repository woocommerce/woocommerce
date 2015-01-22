<?php
/**
 * Test WC conditional functions
 *
 * @since 2.3.0
 */
class WC_Tests_Conditional_Functions extends WC_Unit_Test_Case {

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
}
