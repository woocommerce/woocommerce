<?php
/**
 * Session interface.
 *
 * Defines the transport-agnostic contract that all session handlers must fulfill
 * to be compatible with WooCommerce. Any class extending WC_Session should
 * implement this interface to ensure it provides the full session API.
 *
 * This interface intentionally excludes transport-specific methods such as
 * cookie handling. Those belong on the concrete handler class (e.g.,
 * WC_Session_Handler). Callers that need transport-specific behavior should
 * check `instanceof` before calling those methods.
 *
 * @package WooCommerce\Interfaces
 * @since   10.7.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Session_Interface
 *
 * @since 10.7.0
 */
interface WC_Session_Interface {

	/**
	 * Init hooks and session data.
	 *
	 * @return void
	 */
	public function init();

	/**
	 * Return true if the current user has an active session.
	 *
	 * @return bool
	 */
	public function has_session();

	/**
	 * Generate a unique customer ID for guests, or return user ID if logged in.
	 *
	 * @return string
	 */
	public function generate_customer_id();

	/**
	 * Get session unique ID for requests if session is initialized or user ID if logged in.
	 *
	 * @return string
	 */
	public function get_customer_unique_id();

	/**
	 * Get session data fresh from storage.
	 *
	 * This re-reads session data from the database rather than returning
	 * in-memory data, ensuring the latest persisted state is returned.
	 *
	 * @return array
	 */
	public function get_session_data();

	/**
	 * Returns the session for a given customer.
	 *
	 * @param string $customer_id Customer ID.
	 * @param mixed  $default_value Default session value.
	 * @return mixed
	 */
	public function get_session( $customer_id, $default_value = false );

	/**
	 * Save session data to storage.
	 *
	 * @return void
	 */
	public function save_data();

	/**
	 * Destroy all session data including persisted storage.
	 *
	 * @return void
	 */
	public function destroy_session();

	/**
	 * Forget all session data without destroying persisted storage.
	 *
	 * @return void
	 */
	public function forget_session();

	/**
	 * Delete the session from storage.
	 *
	 * @param string $customer_id Customer session ID.
	 * @return void
	 */
	public function delete_session( $customer_id );

	/**
	 * Cleanup expired sessions.
	 *
	 * @return void
	 */
	public function cleanup_sessions();

	/**
	 * Get a session variable.
	 *
	 * @param string $key Key to get.
	 * @param mixed  $default_value Default value if not set.
	 * @return mixed
	 */
	public function get( $key, $default_value = null );

	/**
	 * Set a session variable.
	 *
	 * @param string $key Key to set.
	 * @param mixed  $value Value to set.
	 * @return void
	 */
	public function set( $key, $value );

	/**
	 * Get customer ID.
	 *
	 * @return string
	 */
	public function get_customer_id();
}
