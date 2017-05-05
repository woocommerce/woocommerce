<?php
/**
 * Class WC_Helper_Payment_Token
 *
 * This helper class should ONLY be used for unit tests!
 */
class WC_Helper_Payment_Token {

	/**
	 * Create a new credit card payment token
	 *
	 * @since 2.6
	 * @return WC_Payment_Token_CC object
	 */
	public static function create_cc_token( $user_id = '' ) {
		$token = new WC_Payment_Token_CC();
		$token->set_last4( 1234 );
		$token->set_expiry_month( '08' );
		$token->set_expiry_year( '2016' );
		$token->set_card_type( 'visa' );
		$token->set_token( time() );
		if ( ! empty( $user_id ) ) {
			$token->set_user_id( $user_id );
		}
		$token->save();
		return $token;
	}

	/**
	 * Create a new eCheck payment token
	 *
	 * @since 2.6
	 * @return WC_Payment_Token_eCheck object
	 */
	public static function create_eCheck_token() {
		$token = new WC_Payment_Token_eCheck();
		$token->set_last4( 1234 );
		$token->set_token( time() );
		$token->save();
		return $token;
	}

	/**
	 * Create a new 'stub' payment token
	 *
	 * @since 2.6
	 * @param  string $extra A string to insert and get to test the metadata functionality of a token
	 * @return WC_Payment_Token_Stub object
	 */
	public static function create_stub_token( $extra ) {
		$token = new WC_Payment_Token_Stub();
		$token->set_extra( $extra );
		$token->set_token( time() );
		$token->save();
		return $token;
	}
}
