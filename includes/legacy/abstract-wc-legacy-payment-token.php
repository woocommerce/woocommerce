<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy Payment Tokens.
 * Payment Tokens were introduced in 2.6.0 with create and update as methods.
 * Major CRUD changes occurred in 2.7, so these were deprecated (save and delete still work).
 * This legacy class is for backwards compatibility in case any code called ->read, ->update or ->create
 * directly on the object.
 *
 * @version  2.7.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   WooCommerce
 */
abstract class WC_Legacy_Payment_Token extends WC_Data {

	/**
	 * Read a token by ID.
	 * @deprecated 2.7.0 - Init a token class with an ID.
	 */
	public function read( $token_id ) {
		wc_deprecated_function( 'WC_Payment_Token::read', '2.7', 'a new token class initialized with an ID.' );
		$this->set_id( $token_id );
		$data_store = WC_Data_Store::load( 'payment-token' );
		$data_store->read( $this );
	}

	/**
	 * Update a token.
	 * @deprecated 2.7.0 - Use ::save instead.
	 */
	public function update() {
		wc_deprecated_function( 'WC_Payment_Token::update', '2.7', '::save instead.' );
		$data_store = WC_Data_Store::load( 'payment-token' );
		try {
			$data_store->update( $this );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Create a token.
	 * @deprecated 2.7.0 - Use ::save instead.
	 */
	public function create() {
		wc_deprecated_function( 'WC_Payment_Token::create', '2.7', '::save instead.' );
		$data_store = WC_Data_Store::load( 'payment-token' );
		try {
			$data_store->create( $this );
		} catch ( Exception $e ) {
			return false;
		}
	}

}
