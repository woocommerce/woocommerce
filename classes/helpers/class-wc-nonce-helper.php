<?php

return new WC_Nonce_Helper();

class WC_Nonce_Helper extends WC_Helper {
	/**
	 * Return a nonce field.
	 *
	 * @access public
	 * @param mixed $action
	 * @param bool $referer (default: true)
	 * @param bool $echo (default: true)
	 * @return void
	 */
	public function nonce_field( $action, $referer = true , $echo = true ) {
		return wp_nonce_field('woocommerce-' . $action, '_n', $referer, $echo );
	}


	/**
	 * Return a url with a nonce appended.
	 *
	 * @access public
	 * @param mixed $action
	 * @param string $url (default: '')
	 * @return string
	 */
	public function nonce_url( $action, $url = '' ) {
		return add_query_arg( '_n', wp_create_nonce( 'woocommerce-' . $action ), $url );
	}
}