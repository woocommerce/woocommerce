<?php
/**
 * Class Payment_Token
 * @package WooCommerce\Tests\Payment_Tokens
 */
class WC_Tests_Payment_Token extends WC_Unit_Test_Case {

	/**
	 * Test get type returns the class name/type.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_get_type() {
		$token = new WC_Payment_Token_Stub();
		$this->assertEquals( 'stub', $token->get_type() );
	}

	/**
	 * Test set/get token to make sure it returns the passed token.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_token() {
		$raw_token = time() . ' ' . __FUNCTION__;
		$token     = new WC_Payment_Token_Stub();
		$token->set_token( $raw_token );
		$this->assertEquals( $raw_token, $token->get_token() );
	}

	/**
	 * Test set/get user ID to make sure it passes the correct ID.
	 * @since 2.6.0
	 */
	public function test_wc_payment_user_id() {
		$token = new WC_Payment_Token_Stub();
		$token->set_user_id( 1 );
		$this->assertEquals( 1, $token->get_user_id() );
	}

	/**
	 * Test get user ID to make sure it returns 0 if there is no user ID.
	 * @since 2.6.0
	 */
	public function test_wc_payment_get_user_id_defaults_to_0() {
		$token = new WC_Payment_Token_Stub();
		$this->assertEquals( 0, $token->get_user_id() );
	}

	/**
	 * Test get/set the gateway ID.
	 * @since 2.6.0
	 */
	public function test_wc_payment_gateway_id() {
		$token = new WC_Payment_Token_Stub();
		$token->set_gateway_id( 'paypal' );
		$this->assertEquals( 'paypal', $token->get_gateway_id() );
	}

	/**
	 * Test set/is a token as default.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_is_default() {
		$token = new WC_Payment_Token_Stub();
		$token->set_default( true );
		$this->assertTrue( $token->is_default() );
		$token->set_default( false );
		$this->assertFalse( $token->is_default() );
	}


	/**
	 * Test that get_data returns the correct internal representation for a token.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_get_data() {
		$raw_token = time() . ' ' . __FUNCTION__;
		$token     = new WC_Payment_Token_Stub();
		$token->set_token( $raw_token );
		$token->set_gateway_id( 'paypal' );
		$token->set_extra( 'woocommerce' );

		$this->assertEquals( $raw_token, $token->get_token() );
		$this->assertEquals( 'paypal', $token->get_gateway_id() );
		$this->assertEquals( 'stub', $token->get_type() );

		$data = $token->get_data();
		$this->assertEquals( 'extra', $data['meta_data'][0]->key );
		$this->assertEquals( 'woocommerce', $data['meta_data'][0]->value );
	}

	/**
	 * Test token validation.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_validation() {
		$token = new WC_Payment_Token_Stub();
		$token->set_token( time() . ' ' . __FUNCTION__ );
		$this->assertTrue( $token->validate() );

		$token = new WC_Payment_Token_Stub();
		$this->assertFalse( $token->validate() );
	}

	/**
	 * Test reading a token from the database.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_read() {
		$token    = WC_Helper_Payment_Token::create_stub_token( __FUNCTION__ );
		$token_id = $token->get_id();

		$token_read = new WC_Payment_Token_Stub( $token_id );

		$this->assertEquals( $token->get_token(), $token_read->get_token() );
		$this->assertEquals( $token->get_extra(), $token_read->get_extra() );
	}

	/**
	 * Test updating a token.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_update() {
		$token = WC_Helper_Payment_Token::create_stub_token( __FUNCTION__ );
		$this->assertEquals( __FUNCTION__, $token->get_extra() );
		$token->set_extra( ':)' );
		$token->set_user_id( 2 );
		$token->save();

		$token = new WC_Payment_Token_Stub( $token->get_id() );
		$this->assertEquals( ':)', $token->get_extra() );
		$this->assertEquals( 2, $token->get_user_id() );
	}

	/**
	 * Test creating a new token.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_create() {
		$token = new WC_Payment_Token_Stub();
		$token->set_extra( __FUNCTION__ );
		$token->set_token( time() );
		$token->save();
		$this->assertNotEmpty( $token->get_id() );

		$token = new WC_Payment_Token_Stub( $token->get_id() );
		$this->assertEquals( __FUNCTION__, $token->get_extra() );
	}

	/**
	 * Test deleting a token.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_delete() {
		$token    = WC_Helper_Payment_Token::create_stub_token( __FUNCTION__ );
		$token_id = $token->get_id();
		$token->delete();
		$get_token = WC_Payment_Tokens::get( $token_id );
		$this->assertNull( $get_token );
	}

	/**
	 * Test a meta function (like CC's last4) doesn't work on the core abstract class.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_last4_doesnt_work() {
		$token = new WC_Payment_Token_Stub();
		$this->assertFalse( is_callable( array( $token, 'get_last4' ) ) );
	}

	/**
	 * Test legacy token functions.
	 *
	 * @since 3.0.0
	 *
	 * @expectedDeprecated WC_Payment_Token::read
	 * @expectedDeprecated WC_Payment_Token::create
	 * @expectedDeprecated WC_Payment_Token::update
	 */
	public function test_wc_payment_token_legacy() {
		$token    = WC_Helper_Payment_Token::create_stub_token( __FUNCTION__ );
		$token_id = $token->get_id();

		$token_read = new WC_Payment_Token_Stub();
		$token_read->read( $token_id );
		$this->assertEquals( $token_id, $token_read->get_id() );

		$token = new WC_Payment_Token_Stub();
		$token->set_token( 'blah' );
		$token->create();

		$this->assertEquals( 'blah', $token->get_token() );
		$this->assertNotEmpty( $token->get_id() );

		$token->set_token( 'blah2' );
		$token->update();

		$this->assertEquals( 'blah2', $token->get_token() );
	}
}
