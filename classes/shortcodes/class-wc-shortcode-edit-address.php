<?php
/**
 * Edit Address Shortcode
 *
 * @todo Address fields should be loaded using the array defined in
 * the checkout class, and the fields should be built off of that.
 *
 * Adapted from spencerfinnell's pull request
 * @author 		WooThemes
 * @category 	Shortcodes
 * @package 	WooCommerce/Shortcodes/Edit_Address
 * @version     2.0.0
 */

class WC_Shortcode_Edit_Address {

	/**
	 * Get the shortcode content.
	 *
	 * @access public
	 * @param array $atts
	 * @return string
	 */
	public static function get( $atts ) {
		global $woocommerce;
		return $woocommerce->shortcode_wrapper( array( __CLASS__, 'output' ), $atts );
	}

	/**
	 * Output the shortcode.
	 *
	 * @access public
	 * @param array $atts
	 * @return void
	 */
	public static function output( $atts ) {
		global $woocommerce;

		if ( ! is_user_logged_in() ) return;

		$load_address = self::get_address_to_edit();

		$address = $woocommerce->countries->get_address_fields( get_user_meta( get_current_user_id(), $load_address . '_country', true ), $load_address . '_' );

		woocommerce_get_template( 'myaccount/form-edit-address.php', array(
			'load_address' 	=> $load_address,
			'address'		=> $address
		) );
	}

	/**
	 * Figure out which address is being viewed/edited.
	 *
	 * @access public
	 */
	public static function get_address_to_edit() {

		$load_address = ( isset( $_GET[ 'address' ] ) ) ? esc_attr( $_GET[ 'address' ] ) : '';

		$load_address = ( $load_address == 'billing' || $load_address == 'shipping' ) ? $load_address : '';

		return $load_address;
	}

}