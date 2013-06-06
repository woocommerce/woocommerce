<?php

return new WC_Messages_Helper();

class WC_Messages_Helper extends WC_Helper {
	public $errors = array();
	public $messages = array();

	/**
	 * Load Messages.
	 *
	 * @access public
	 * @return void
	 */
	public function load_messages() {
		global $woocommerce;
		$this->errors = $woocommerce->session->errors;
		$this->messages = $woocommerce->session->messages;
		unset( $woocommerce->session->errors, $woocommerce->session->messages );

		// Load errors from querystring
		if ( isset( $_GET['wc_error'] ) )
			$this->add_error( esc_attr( $_GET['wc_error'] ) );
	}

	/**
	 * Add an error.
	 *
	 * @access public
	 * @param string $error
	 * @return void
	 */
	public function add_error( $error ) {
		$this->errors[] = apply_filters( 'woocommerce_add_error', $error );
	}

	/**
	 * Add a message.
	 *
	 * @access public
	 * @param string $message
	 * @return void
	 */
	public function add_message( $message ) {
		$this->messages[] = apply_filters( 'woocommerce_add_message', $message );
	}

	/**
	 * Clear messages and errors from the session data.
	 *
	 * @access public
	 * @return void
	 */
	public function clear_messages() {
		global $woocommerce;
		$this->errors = $this->messages = array();
		unset( $woocommerce->session->errors, $woocommerce->session->messages );
	}

	/**
	 * error_count function.
	 *
	 * @access public
	 * @return int
	 */
	public function error_count() {
		return sizeof( $this->errors );
	}

	/**
	 * Get message count.
	 *
	 * @access public
	 * @return int
	 */
	public function message_count() {
		return sizeof( $this->messages );
	}

	/**
	 * Get errors.
	 *
	 * @access public
	 * @return array
	 */
	public function get_errors() {
		return (array) $this->errors;
	}

	/**
	 * Get messages.
	 *
	 * @access public
	 * @return array
	 */
	public function get_messages() {
		return (array) $this->messages;
	}

	/**
	 * Output the errors and messages.
	 *
	 * @access public
	 * @return void
	 */
	public function show_messages() {
		woocommerce_show_messages();
	}

	/**
	 * Set session data for messages.
	 *
	 * @access public
	 * @return void
	 */
	public function set_messages() {
		global $woocommerce;
		$woocommerce->session->errors = $this->errors;
		$woocommerce->session->messages = $this->messages;
	}
}