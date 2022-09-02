<?php

/**
 * Class Payment_Token_eCheck.
 * @package WooCommerce\Tests\Payment_Tokens
 */
class WC_Tests_Payment_Token_eCheck extends WC_Unit_Test_Case {

	/**
	 * Test validation for empty/unset values.
	 * @since 2.6.0
	 */
	function test_wc_payment_token_echeck_validate_empty() {
		$token = new WC_Payment_Token_ECheck();
		$token->set_token( time() . ' ' . __FUNCTION__ );
		$this->assertFalse( $token->validate() );
		$token->set_last4( '1111' );
		$this->assertTrue( $token->validate() );
	}

	/**
	 * Test get/set last4.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_echeck_last4() {
		$token = new WC_Payment_Token_ECheck();
		$token->set_last4( '1111' );
		$this->assertEquals( '1111', $token->get_last4() );
	}

	/**
	 * Test reading/getting a token from DB correctly sets meta.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_echeck_read_pulls_meta() {
		$token    = WC_Helper_Payment_Token::create_eCheck_token();
		$token_id = $token->get_id();

		$token_read = new WC_Payment_Token_ECheck( $token_id );
		$this->assertEquals( '1234', $token_read->get_last4() );
	}
}
