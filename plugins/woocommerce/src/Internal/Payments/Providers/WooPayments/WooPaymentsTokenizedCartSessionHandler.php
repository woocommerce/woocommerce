<?php
/**
 * WooPaymentsTokenizedCartSessionHandler class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken;
use RuntimeException;

/**
 * WooPayments product-page tokenized cart session handler.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsTokenizedCartSessionHandler extends \WC_Session_Handler {

	private const SESSION_HEADER = 'HTTP_X_WOOPAYMENTS_TOKENIZED_CART_SESSION';

	private const TOKEN_ISSUER = 'woopayments/product-page';

	/**
	 * Init tokenized session data without binding the request to the shopper's normal WooCommerce session cookie.
	 *
	 * @internal
	 */
	final public function init(): void {
		$this->init_tokenized_session();

		if ( ! $this->is_ephemeral_request() ) {
			add_action( 'shutdown', array( $this, 'save_data' ), 20 );
		}
	}

	/**
	 * Always treat initialized tokenized sessions as active even though they do not use cookies.
	 *
	 * @return bool
	 */
	public function has_session() {
		return '' !== $this->get_customer_id();
	}

	/**
	 * Generate tokenized guest session IDs even when the shopper is logged in.
	 *
	 * @return string
	 */
	public function generate_customer_id() {
		return wc_rand_hash( 't_', 30 );
	}

	/**
	 * Prevent tokenized product-page requests from setting normal WooCommerce session cookies.
	 *
	 * @param bool $set Whether a cookie should be set.
	 */
	public function set_customer_session_cookie( $set ): void {}

	/**
	 * Forget tokenized session data without clearing the shopper's normal browser cookie.
	 */
	public function forget_session(): void {
		$this->_data        = array();
		$this->_dirty       = false;
		$this->_customer_id = $this->generate_customer_id();
		$this->_has_cookie  = false;
	}

	/**
	 * Return an empty cart for a newly-created isolated session.
	 *
	 * @param string $key           Session key.
	 * @param mixed  $default_value Default value.
	 * @return mixed
	 */
	public function get( $key, $default_value = null ) {
		if ( 'cart' === $key && ! array_key_exists( 'cart', $this->_data ) ) {
			return array();
		}

		return parent::get( $key, $default_value );
	}

	/**
	 * Initialize this request from the incoming tokenized cart session header or a new guest session.
	 *
	 * @throws RuntimeException When persisted tokenized session data belongs to a different tokenized customer.
	 */
	private function init_tokenized_session(): void {
		$session_id = $this->get_session_id_from_header();

		if ( '' === $session_id ) {
				$session_id = $this->generate_customer_id();
		}

		$this->_customer_id = $session_id;
		$this->_data        = (array) $this->get_session( $session_id, array() );

		if ( isset( $this->_data['token_customer_id'] ) && $this->_data['token_customer_id'] !== $session_id ) {
			throw new RuntimeException( 'Tokenized cart session customer mismatch.' );
		}

		$this->_data['token_customer_id'] = $session_id;
	}

	/**
	 * Get the tokenized cart session ID from the signed header.
	 *
	 * @return string
	 */
	private function get_session_id_from_header(): string {
		$token = $this->get_server_header( self::SESSION_HEADER );
		if ( '' === $token || ! JsonWebToken::validate( $token, self::get_token_secret() ) ) {
			return '';
		}

		$parts   = JsonWebToken::get_parts( $token );
		$payload = is_object( $parts ) && isset( $parts->payload ) ? $parts->payload : null;
		if ( ! is_object( $payload ) || ! isset( $payload->session_id, $payload->iss ) || self::TOKEN_ISSUER !== $payload->iss ) {
			return '';
		}

		$session_id = is_scalar( $payload->session_id ) ? sanitize_text_field( (string) $payload->session_id ) : '';

		return 0 === strpos( $session_id, 't_' ) ? $session_id : '';
	}

	/**
	 * Get a sanitized HTTP request header from the server environment.
	 *
	 * @param string $key Server header key.
	 * @return string
	 */
	private function get_server_header( string $key ): string {
		if ( ! isset( $_SERVER[ $key ] ) || ! is_scalar( $_SERVER[ $key ] ) ) {
			return '';
		}

		$value = wc_clean( wp_unslash( (string) $_SERVER[ $key ] ) );

		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Tell whether this request should be cleaned up after the response.
	 *
	 * @return bool
	 */
	private function is_ephemeral_request(): bool {
		return '1' === $this->get_server_header( 'HTTP_X_WOOPAYMENTS_TOKENIZED_CART_IS_EPHEMERAL_CART' );
	}

	/**
	 * Get the JWT signing secret shared by the session controller.
	 *
	 * @return string
	 */
	public static function get_token_secret(): string {
		return '@' . wp_salt();
	}
}
