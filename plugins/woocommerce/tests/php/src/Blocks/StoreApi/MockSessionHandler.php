<?php

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi;

use WC_Session_Handler;

defined( 'ABSPATH' ) || exit;

/**
 * MockSessionHandler class for PHPUnit tests.
 */
final class MockSessionHandler extends WC_Session_Handler {

	/**
	 * Setup cookie and customer ID.
	 *
	 * @param bool $set Should the session cookie be set.
	 */
	public function init_session_cookie( $set = true ) {
		$to_hash           = $this->_customer_id . '|' . $this->_session_expiration;
		$cookie_hash       = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );
		$cookie_value      = $this->_customer_id . '||' . $this->_session_expiration . '||' . $this->_session_expiring . '||' . $cookie_hash;
		$this->_has_cookie = true;

		if ( ! isset( $_COOKIE[ $this->_cookie ] ) || $_COOKIE[ $this->_cookie ] !== $cookie_value ) {

			// Manually set the cookie here because the response is already sent, so wc_setcookie() (as in the parent class) won't work.
			$_COOKIE[ $this->_cookie ] = $cookie_value;
		}
	}
}
