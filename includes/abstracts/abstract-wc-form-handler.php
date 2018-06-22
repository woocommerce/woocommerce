<?php
/**
 * Form handler abstract class.
 *
 * @package WooCommerce/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstract_WC_Form_Handler class.
 *
 * @since 3.5.0
 */
abstract class Abstract_WC_Form_Handler {

	/**
	 * Nonce key. Nonce name and key derived from this value.
	 *
	 * @var string
	 */
	protected $nonce_key = '';

	/**
	 * Posted data.
	 *
	 * @var array
	 */
	protected $post_data = '';

	/**
	 * Process the form. Subclasses must extend this.
	 *
	 * @return void
	 */
	abstract public function process();

	/**
	 * Set the raw post data to process.
	 *
	 * @param array $post_data Post data (raw).
	 */
	public function set_post_data( $post_data ) {
		$this->post_data = (array) $post_data;
	}

	/**
	 * Get an item of post data, and optionally provide a default value.
	 *
	 * @param boolean $key Key to get.
	 * @param string  $default Default value to fallback to.
	 * @return mixed
	 */
	public function get_post_data( $key = false, $default = '' ) {
		if ( false === $key ) {
			return $this->post_data;
		}
		return isset( $this->post_data[ $key ] ) ? $this->post_data[ $key ] : $default;
	}

	/**
	 * Get a global WP query var.
	 *
	 * @param boolean $key Key to get.
	 * @param string  $default Default value to fallback to.
	 * @return mixed
	 */
	public function get_wp_query_var( $key = false, $default = '' ) {
		global $wp;

		return isset( $wp->query_vars[ $key ] ) ? $wp->query_vars[ $key ] : $default;
	}

	/**
	 * Handle error.
	 *
	 * @param Exception $e Exception object.
	 */
	public function handle_error( $e ) {
		wc_add_notice( $e->getMessage(), 'error' );
	}

	/**
	 * Perform a redirect.
	 *
	 * @param string $url URL to redirect to.
	 */
	protected function redirect_to( $url ) {
		wp_redirect( $url );
		exit;
	}

	/**
	 * Check nonce is valid.
	 *
	 * @throws Exception On error.
	 */
	protected function check_nonce() {
		if ( ! $this->is_nonce_valid() ) {
			throw new Exception( __( 'Unable to process form. Please try again.', 'woocommerce' ) );
		}
	}

	/**
	 * Check posted nonce is valid.
	 *
	 * @return bool
	 */
	protected function is_nonce_valid() {
		$nonce_value = isset( $this->post_data[ $this->nonce_key . '-nonce' ] ) ? $this->post_data[ $this->nonce_key . '-nonce' ] : false;

		return wp_verify_nonce( $nonce_value, $this->nonce_key );
	}
}
