<?php
/**
 * Provide basic rate limiting functionality via WP Options API.
 *
 * Currently only provides a simple limit by delaying action by X seconds.
 *
 * Example usage:
 *
 * When an action runs, call set_rate_limit, e.g.:
 *
 * WC_Rate_Limiter::set_rate_limit( "{$my_action_name}_{$user_id}", $delay );
 *
 * This sets a timestamp for future timestamp after which action can run again.
 *
 *
 * Then before running the action again, check if the action is allowed to run, e.g.:
 *
 * if ( WC_Rate_Limiter::retried_too_soon( "{$my_action_name}_{$user_id}" ) ) {
 *     add_notice( 'Sorry, too soon!' );
 * }
 *
 * @package WooCommerce/Classes
 * @version 3.9.0
 * @since   3.9.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rate limit class.
 */
class WC_Rate_Limiter {

	/**
	 * Constructs Option name from action identifier.
	 *
	 * @param string $action_id Identifier of the action.
	 * @return string
	 */
	public static function storage_id( $action_id ) {
		return 'woocommerce_rate_limit_' . $action_id;
	}

	/**
	 * Returns true if the action is not allowed to be run by the rate limiter yet, false otherwise.
	 *
	 * @param string $action_id Identifier of the action.
	 * @return bool
	 */
	public static function retried_too_soon( $action_id ) {
		$next_try_allowed_at = get_option( self::storage_id( $action_id ) );

		// No record of action running, so action is allowed to run.
		if ( false === $next_try_allowed_at ) {
			return false;
		}

		// Before the next run is allowed, retry forbidden.
		if ( time() <= $next_try_allowed_at ) {
			return true;
		}

		// After the next run is allowed, retry allowed.
		return false;
	}

	/**
	 * Sets the rate limit delay in seconds for action with identifier $id.
	 *
	 * @param string $action_id Identifier of the action.
	 * @param int    $delay Delay in seconds.
	 * @return bool True if the option setting was successful, false otherwise.
	 */
	public static function set_rate_limit( $action_id, $delay ) {
		$option_name = self::storage_id( $action_id );
		$next_try_allowed_at = time() + $delay;
		return update_option( $option_name, $next_try_allowed_at );
	}
}
