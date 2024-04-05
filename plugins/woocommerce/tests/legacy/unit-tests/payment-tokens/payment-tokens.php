<?php
/**
 * Payment_Tokens class file.
 *
 * @package WooCommerce\Tests\Payment_Tokens
 */

/**
 * Class Payment_Tokens
 */
class WC_Tests_Payment_Tokens extends WC_Unit_Test_Case {

	/**
	 * Setup the test case.
	 *
	 * @see WC_Unit_Test_Case::setUp()
	 */
	public function setUp(): void {
		parent::setUp();
		$this->user_id = $this->login_as_role( 'shop_manager' );
	}

	/**
	 * Test getting tokens associated with an order.
	 * @since 2.6.0
	 */
	public function test_wc_payment_tokens_get_order_tokens() {
		$order = WC_Helper_Order::create_order();
		$this->assertEmpty( WC_Payment_Tokens::get_order_tokens( $order->get_id() ) );

		$token = WC_Helper_Payment_Token::create_cc_token();
		$order->add_payment_token( $token );

		$this->assertCount( 1, WC_Payment_Tokens::get_order_tokens( $order->get_id() ) );

	}

	/**
	 * Test getting tokens associated with a user and no gateway ID.
	 * @since 2.6.0
	 */
	public function test_wc_payment_tokens_get_customer_tokens_no_gateway() {
		$this->assertEmpty( WC_Payment_Tokens::get_customer_tokens( $this->user_id ) );

		$token = WC_Helper_Payment_Token::create_cc_token();
		$token->set_user_id( $this->user_id );
		$token->save();

		$token = WC_Helper_Payment_Token::create_cc_token();
		$token->set_user_id( $this->user_id );
		$token->save();

		$this->assertCount( 2, WC_Payment_Tokens::get_customer_tokens( $this->user_id ) );
	}

	/**
	 * Test getting tokens associated with a user and for a specific gateway.
	 * @since 2.6.0
	 */
	public function test_wc_payment_tokens_get_customer_tokens_with_gateway() {
		$this->assertEmpty( WC_Payment_Tokens::get_customer_tokens( $this->user_id ) );

		$token = WC_Helper_Payment_Token::create_cc_token();
		$token->set_user_id( $this->user_id );
		$token->set_gateway_id( 'bacs' );
		$token->save();

		$token = WC_Helper_Payment_Token::create_cc_token();
		$token->set_user_id( $this->user_id );
		$token->set_gateway_id( 'paypal' );
		$token->save();

		$this->assertCount( 2, WC_Payment_Tokens::get_customer_tokens( $this->user_id ) );
		$this->assertCount( 1, WC_Payment_Tokens::get_customer_tokens( $this->user_id, 'bacs' ) );

		foreach ( WC_Payment_Tokens::get_customer_tokens( $this->user_id, 'bacs' ) as $gateway_token ) {
			$this->assertEquals( 'bacs', $gateway_token->get_gateway_id() );
		}
	}

	/**
	 * Test getting a customers default token.
	 * @since 2.6.0
	 */
	public function test_wc_get_customer_default_token() {
		$token = WC_Helper_Payment_Token::create_cc_token();
		$token->set_user_id( $this->user_id );
		$token->set_gateway_id( 'bacs' );
		$token->save();

		$token = WC_Helper_Payment_Token::create_cc_token();
		$token->set_user_id( $this->user_id );
		$token->set_default( true );
		$token->set_gateway_id( 'paypal' );
		$token->save();

		$this->assertCount( 2, WC_Payment_Tokens::get_customer_tokens( $this->user_id ) );

		$default_token = WC_Payment_Tokens::get_customer_default_token( $this->user_id );
		$this->assertEquals( 'paypal', $default_token->get_gateway_id() );
	}

	/**
	 * Test getting a customers default token, when there no token is expictly set.
	 * This should be the "first created".
	 * @see WC_Payment_Token::create()
	 * @since 2.6.0
	 */
	public function test_wc_get_customer_default_token_returns_first_created_when_no_default_token_set() {
		$token = WC_Helper_Payment_Token::create_cc_token( $this->user_id );
		$token->set_gateway_id( 'bacs' );
		$token->save();

		$token = WC_Helper_Payment_Token::create_cc_token( $this->user_id );
		$token->set_gateway_id( 'paypal' );
		$token->save();

		$this->assertCount( 2, WC_Payment_Tokens::get_customer_tokens( $this->user_id ) );

		$default_token = WC_Payment_Tokens::get_customer_default_token( $this->user_id );
		$this->assertEquals( 'bacs', $default_token->get_gateway_id() );
	}

	/**
	 * Test getting a token by ID.
	 * @since 2.6.0
	 */
	public function test_wc_payment_tokens_get() {
		$token     = WC_Helper_Payment_Token::create_cc_token();
		$token_id  = $token->get_id();
		$get_token = WC_Payment_Tokens::get( $token_id );
		$this->assertEquals( $token->get_token(), $get_token->get_token() );
	}

	/**
	 * Test deleting a token by ID.
	 * @since 2.6.0
	 */
	public function test_wc_payment_tokens_delete() {
		$token    = WC_Helper_Payment_Token::create_cc_token();
		$token_id = $token->get_id();

		WC_Payment_Tokens::delete( $token_id );

		$get_token = WC_Payment_Tokens::get( $token_id );
		$this->assertNull( $get_token );
	}

	/**
	 * Test getting a token's type by ID.
	 * @since 2.6.0
	 */
	public function test_wc_payment_tokens_get_type_by_id() {
		$token    = WC_Helper_Payment_Token::create_cc_token();
		$token_id = $token->get_id();
		$this->assertEquals( 'CC', WC_Payment_Tokens::get_token_type_by_id( $token_id ) );
	}

	/**
	 * Test setting a users default token.
	 * @since 2.6.0
	 */
	public function test_wc_payment_tokens_set_users_default() {
		$token    = WC_Helper_Payment_Token::create_cc_token( $this->user_id );
		$token_id = $token->get_id();
		$token->save();

		$token2     = WC_Helper_Payment_Token::create_cc_token( $this->user_id );
		$token_id_2 = $token2->get_id();
		$token2->save();

		$this->assertTrue( $token->is_default() ); // first created is default.
		$this->assertFalse( $token2->is_default() );

		WC_Payment_Tokens::set_users_default( $this->user_id, $token_id_2 );
		$token  = new WC_Payment_Token_CC( $token_id );
		$token2 = new WC_Payment_Token_CC( $token_id_2 );
		$this->assertFalse( $token->is_default() );
		$this->assertTrue( $token2->is_default() );

		WC_Payment_Tokens::set_users_default( $this->user_id, $token_id );
		$token  = new WC_Payment_Token_CC( $token_id );
		$token2 = new WC_Payment_Token_CC( $token_id_2 );
		$this->assertTrue( $token->is_default() );
		$this->assertFalse( $token2->is_default() );
	}
}
