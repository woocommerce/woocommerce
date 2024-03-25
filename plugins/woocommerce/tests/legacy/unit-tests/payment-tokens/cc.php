<?php

/**
 * Class Payment_Token_CC.
 * @package WooCommerce\Tests\Payment_Tokens
 */
class WC_Tests_Payment_Token_CC extends WC_Unit_Test_Case {

	/**
	 * Test validation for empty/unset values.
	 * @since 2.6.0
	 */
	function test_wc_payment_token_cc_validate_empty() {
		$token = new WC_Payment_Token_CC();
		$token->set_token( time() . ' ' . __FUNCTION__ );
		$this->assertFalse( $token->validate() );
		$token->set_last4( '1111' );
		$token->set_expiry_year( '2016' );
		$token->set_expiry_month( '08' );
		$token->set_card_type( 'visa' );
		$this->assertTrue( $token->validate() );
	}

	/**
	 * Test validation for expiry length.
	 * @since 2.6.0
	 */
	function test_wc_payment_token_cc_validate_expiry_length() {
		$token = new WC_Payment_Token_CC();
		$token->set_token( time() . ' ' . __FUNCTION__ );
		$this->assertFalse( $token->validate() );

		$token->set_last4( '1111' );
		$token->set_expiry_year( '16' );
		$token->set_expiry_month( '08' );
		$token->set_card_type( 'visa' );

		$this->assertFalse( $token->validate() );

		$token->set_expiry_year( '2016' );

		$this->assertTrue( $token->validate() );

		$token->set_expiry_month( '888' );
		$this->assertFalse( $token->validate() );
	}

	/**
	 * Tes get/set card type.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_cc_card_type() {
		$token = new WC_Payment_Token_CC();
		$token->set_card_type( 'visa' );
		$this->assertEquals( 'visa', $token->get_card_type() );
	}

	/**
	 * Test get/set expiry year.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_cc_expiry_year() {
		$token = new WC_Payment_Token_CC();
		$token->set_expiry_year( '2016' );
		$this->assertEquals( '2016', $token->get_expiry_year() );
	}

	/**
	 * Test get/set expiry month.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_cc_expiry_month() {
		$token = new WC_Payment_Token_CC();
		$token->set_expiry_month( '08' );
		$this->assertEquals( '08', $token->get_expiry_month() );
	}

	/**
	 * Test get/set last4.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_cc_last4() {
		$token = new WC_Payment_Token_CC();
		$token->set_last4( '1111' );
		$this->assertEquals( '1111', $token->get_last4() );
	}

	/*
	 * Test reading/getting a token from DB correctly sets meta.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_cc_read_pulls_meta() {
		$token      = WC_Helper_Payment_Token::create_cc_token();
		$token_id   = $token->get_id();
		$token_read = new WC_Payment_Token_CC( $token_id );
		$this->assertEquals( '1234', $token_read->get_last4() );
	}

	/*
	 * Test saving a new value in a token after it has been created.
	 * @since 3.0.0
	 */
	public function test_wc_payment_token_cc_updates_after_create() {
		$token    = WC_Helper_Payment_Token::create_cc_token();
		$token_id = $token->get_id();
		$this->assertEquals( '1234', $token->get_last4() );

		$token->set_last4( '4321' );
		$token->set_user_id( 3 );
		$token->save();
		$this->assertEquals( '4321', $token->get_last4() );
		$this->assertEquals( 3, $token->get_user_id() );
	}

	/**
	 * Test get/set expiry year.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_cc_co_branded_fields() {
		$token = new WC_Payment_Token_CC();
		$token->set_available_networks( array( 'visa', 'cartes_bancaires' ) );
		$token->set_preferred_network( 'cartes_bancaires' );
		$this->assertEquals( array( 'visa', 'cartes_bancaires' ), $token->get_available_networks() );
		$this->assertEquals( 'cartes_bancaires', $token->get_preferred_network() );
		$this->assertTrue( $token->is_co_branded() );
	}
}
