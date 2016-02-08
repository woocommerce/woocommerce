<?php
namespace WooCommerce\Tests\Payment_Tokens;

/**
 * Class Payment_Tokens
 * @package WooCommerce\Tests\Payment_Tokens
 */
class Payment_Tokens extends \WC_Unit_Test_Case {

	/**
	 * Test getting tokens associated with an order
	 *
	 * @since 2.6
	 */
	function test_wc_payment_tokens_get_order_tokens() {
		$order = \WC_Helper_Order::create_order();
		$this->assertEmpty( \WC_Payment_Tokens::get_order_tokens( $order->id ) );

		$token = \WC_Helper_Payment_Token::create_cc_token();
		update_post_meta( $order->id, '_payment_tokens', array( $token->get_id() ) );

		$this->assertCount( 1, \WC_Payment_Tokens::get_order_tokens( $order->id ) );

	}

	/**
	 * Test getting tokens associated with a user
	 *
	 * @since 2.6
	 */
	function test_wc_payment_tokens_get_customer_token() {
		$this->assertEmpty( \WC_Payment_Tokens::get_customer_tokens( 1 ) );

		$token = \WC_Helper_Payment_Token::create_cc_token();
		$token->set_user_id( 1 );
		$token->save();

		$this->assertCount( 1, \WC_Payment_Tokens::get_customer_tokens( 1 ) );
	}

	/**
	 * Test getting a token by ID
	 *
	 * @since 2.6
	 */
	function test_wc_payment_tokens_get() {
		$token = \WC_Helper_Payment_Token::create_cc_token();
		$token_id = $token->get_id();
		$get_token = \WC_Payment_Tokens::get( $token_id );
		$this->assertEquals( $token->get_token(), $get_token->get_token() );
	}

	/**
	 * Test deleting a token by ID
	 *
	 * @since 2.6
	 */
	function test_wc_payment_tokens_delete() {
		$token = \WC_Helper_Payment_Token::create_cc_token();
		$token_id = $token->get_id();

		\WC_Payment_Tokens::delete( $token_id );

		$get_token = \WC_Payment_Tokens::get( $token_id );
		$this->assertNull( $get_token );
	}

	/**
	 * Test getting a token's type by ID
	 *
	 * @since 2.6
	 */
	function test_wc_payment_tokens_get_type_by_id() {
		$token = \WC_Helper_Payment_Token::create_cc_token();
		$token_id = $token->get_id();
		$this->assertEquals( 'CC', \WC_Payment_Tokens::get_token_type_by_id( $token_id ) );
	}

}
