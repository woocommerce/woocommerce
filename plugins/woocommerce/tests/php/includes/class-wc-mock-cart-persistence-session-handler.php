<?php
declare(strict_types=1);

/**
 * Mock Session Handler for testing cart persistence.
 * Extends the existing session handler and overrides cookie setting.
 */
class WC_Mock_Cart_Persistence_Session_Handler extends WC_Session_Handler {
	/**
	 * @var string
	 */
	private $cookie_value = '';

	/**
	 * Set the cookie value.
	 *
	 * @param string $cookie_value The cookie value to set.
	 */
	public function set_cookie_value( $cookie_value ) {
		$this->cookie_value = $cookie_value;
	}

	/**
	 * Get the cookie value.
	 *
	 * @return string The cookie value.
	 */
	public function get_cookie_value() {
		return $this->cookie_value;
	}

	/**
	 * Check if the cookie exists.
	 *
	 * @return bool Whether the cookie exists.
	 */
	public function cookie_exists() {
		return ! empty( $this->cookie_value );
	}
}
