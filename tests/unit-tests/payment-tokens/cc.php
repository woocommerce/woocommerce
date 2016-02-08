<?php
namespace WooCommerce\Tests\Payment_Tokens;

/**
 * Class Payment_Token_CC.
 * @package WooCommerce\Tests\Payment_Tokens
 */
class Payment_Token_CC extends \WC_Unit_Test_Case {

	/**
	 * Test validation for empty/unset values
	 *
	 * @since 2.6
	 */
	function test_wc_payment_token_cc_validate_empty() {
		$token = new \WC_Payment_Token_CC( 1 );
		$token->set_token( time() . ' ' . __FUNCTION__ );
		$this->assertFalse( $token->validate() );
		$token->set_last4( '1111' );
		$token->set_expiry_year( '2016' );
		$token->set_expiry_month( '08' );
		$token->set_card_type( 'visa' );
		$this->assertTrue( $token->validate() );
	}

	/**
	 * Test validation for expiry length
	 *
	 * @since 2.6
	 */
	function test_wc_payment_token_cc_validate_expiry_length() {
		$token = new \WC_Payment_Token_CC( 1 );
		$token->set_token( time() . ' ' . __FUNCTION__ );
		$this->assertFalse( $token->validate() );

		$token->set_last4( '1111' );
		$token->set_expiry_year( '16' );
		$token->set_expiry_month( '08' );
		$token->set_card_type( 'visa' );

		$this->assertFalse( $token->validate() );

		$token->set_expiry_year( '2016' );
		$this->assertTrue( $token->validate() );

		$token->set_expiry_month( '8' );
		$this->assertFalse( $token->validate() );
	}

	/**
	 * Test getting a card type
	 *
	 * @since 2.6
	 */
	public function test_wc_payment_token_cc_get_card_type() {
		$token = new \WC_Payment_Token_CC( 1, array(), array( 'card_type' => 'mastercard' ) );
		$this->assertEquals( 'mastercard', $token->get_card_type() );
	}

	/**
	 * Test setting a token's card type
	 *
	 * @since 2.6
	 */
	public function test_wc_payment_token_cc_set_card_type() {
		$token = new \WC_Payment_Token_CC( 1 );
		$token->set_card_type( 'visa' );
		$this->assertEquals( 'visa', $token->get_card_type() );
	}

	/**
	 * Test getting expiry year
	 *
	 * @since 2.6
	 */
	public function test_wc_payment_token_cc_get_expiry_year() {
		$token = new \WC_Payment_Token_CC( 1, array(), array( 'expiry_year' => '2016' ) );
		$this->assertEquals( '2016', $token->get_expiry_year() );
	}

	/**
	 * Test setting a token's expiry year
	 *
	 * @since 2.6
	 */
	public function test_wc_payment_token_cc_set_expiry_year() {
		$token = new \WC_Payment_Token_CC( 1 );
		$token->set_expiry_year( '2016' );
		$this->assertEquals( '2016', $token->get_expiry_year() );
	}

	/**
	 * Test getting expiry month
	 *
	 * @since 2.6
	 */
	public function test_wc_payment_token_cc_get_expiry_month() {
		$token = new \WC_Payment_Token_CC( 1, array(), array( 'expiry_month' => '08' ) );
		$this->assertEquals( '08', $token->get_expiry_month() );
	}

	/**
	 * Test setting a token's expiry month
	 *
	 * @since 2.6
	 */
	public function test_wc_payment_token_cc_set_expiry_month() {
		$token = new \WC_Payment_Token_CC( 1 );
		$token->set_expiry_month( '08' );
		$this->assertEquals( '08', $token->get_expiry_month() );
	}

	/**
	 * Test getting last4
	 *
	 * @since 2.6
	 */
	public function test_wc_payment_token_cc_get_last4() {
		$token = new \WC_Payment_Token_CC( 1, array(), array( 'last4' => '1111' ) );
		$this->assertEquals( '1111', $token->get_last4() );
	}

	/**
	 * Test setting a token's last4
	 *
	 * @since 2.6
	 */
	public function test_wc_payment_token_cc_set_last4() {
		$token = new \WC_Payment_Token_CC( 1 );
		$token->set_last4( '2222' );
		$this->assertEquals( '2222', $token->get_last4() );
	}

	/**
	 * Test reading/getting a token from DB correctly sets meta
	 *
	 * @since 2.6
	 */
	public function test_wc_payment_token_cc_read_pulls_meta() {
		$token = \WC_Helper_Payment_Token::create_cc_token();
		$token_id = $token->get_id();

		$token_read = new \WC_Payment_Token_CC();
		$token_read->read( $token_id );

		$this->assertEquals( '1234', $token_read->get_last4() );
	}

}
