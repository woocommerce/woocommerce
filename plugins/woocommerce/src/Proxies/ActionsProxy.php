<?php
/**
 * ActionsProxy class file.
 */

namespace Automattic\WooCommerce\Proxies;

/**
 * Proxy for interacting with WordPress actions and filters.
 *
 * This class should be used instead of directly accessing the WordPress functions, to ease unit testing.
 */
class ActionsProxy {

	/**
	 * Retrieve the number of times an action is fired.
	 *
	 * @param string $tag The name of the action hook.
	 *
	 * @return int The number of times action hook $tag is fired.
	 */
	public function did_action( $tag ) {
		return did_action( $tag );
	}

	/**
	 * Calls the callback functions that have been added to a filter hook.
	 *
	 * @param string $tag     The name of the filter hook.
	 * @param mixed  $value   The value to filter.
	 * @param mixed  ...$parameters Additional parameters to pass to the callback functions.
	 *
	 * @return mixed The filtered value after all hooked functions are applied to it.
	 */
	public function apply_filters( $tag, $value, ...$parameters ) {
		return apply_filters( $tag, $value, ...$parameters );
	}

	/**
	 * Calls the callback functions that have been added to a filter hook, specifying arguments in an array.
	 *
	 * @since 10.9.0
	 *
	 * @param string $tag  The name of the filter hook.
	 * @param array  $args The arguments supplied to the functions hooked to $tag.
	 *
	 * @return mixed The filtered value after all hooked functions are applied to it.
	 */
	public function apply_filters_ref_array( $tag, $args ) {
		return apply_filters_ref_array( $tag, $args );
	}

	/**
	 * Execute functions hooked on a specific action hook.
	 *
	 * @since 10.9.0
	 *
	 * @param string $tag     The name of the action to be executed.
	 * @param mixed  ...$args Additional arguments which are passed on to the functions hooked to the action.
	 *
	 * @return void
	 */
	public function do_action( $tag, ...$args ) {
		do_action( $tag, ...$args );
	}

	/**
	 * Execute functions hooked on a specific action hook, specifying arguments in an array.
	 *
	 * @since 10.9.0
	 *
	 * @param string $tag  The name of the action to be executed.
	 * @param array  $args The arguments supplied to the functions hooked to $tag.
	 *
	 * @return void
	 */
	public function do_action_ref_array( $tag, $args ) {
		do_action_ref_array( $tag, $args );
	}

	/**
	 * Adds a callback function to an action hook.
	 *
	 * @since 10.9.0
	 *
	 * @param string   $tag           The name of the action to add the callback to.
	 * @param callable $callback      The callback to be run when the action is called.
	 * @param int      $priority      Optional. Order in which the callback is executed. Default 10.
	 * @param int      $accepted_args Optional. Number of arguments the callback accepts. Default 1.
	 *
	 * @return true Always returns true.
	 */
	public function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
		return add_action( $tag, $callback, $priority, $accepted_args );
	}

	/**
	 * Removes a callback function from an action hook.
	 *
	 * @since 10.9.0
	 *
	 * @param string   $tag      The action hook to which the function to be removed is hooked.
	 * @param callable $callback The callback to be removed.
	 * @param int      $priority Optional. Priority of the callback. Default 10.
	 *
	 * @return bool Whether the callback was successfully removed.
	 */
	public function remove_action( $tag, $callback, $priority = 10 ) {
		return remove_action( $tag, $callback, $priority );
	}

	/**
	 * Check if any callback has been registered for an action hook.
	 *
	 * @since 10.9.0
	 *
	 * @param string         $tag      The name of the action hook.
	 * @param callable|false $callback Optional. The callback to check for. Default false.
	 *
	 * @return bool|int If $callback is false, returns true if any callbacks are registered for $tag,
	 *                  or false if not. If $callback is provided, returns the priority of that callback
	 *                  or false if it is not registered.
	 */
	public function has_action( $tag, $callback = false ) {
		return has_action( $tag, $callback );
	}

	/**
	 * Adds a callback function to a filter hook.
	 *
	 * @since 10.9.0
	 *
	 * @param string   $tag           The name of the filter to add the callback to.
	 * @param callable $callback      The callback to be run when the filter is applied.
	 * @param int      $priority      Optional. Order in which the callback is executed. Default 10.
	 * @param int      $accepted_args Optional. Number of arguments the callback accepts. Default 1.
	 *
	 * @return true Always returns true.
	 */
	public function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
		return add_filter( $tag, $callback, $priority, $accepted_args );
	}

	/**
	 * Removes a callback function from a filter hook.
	 *
	 * @since 10.9.0
	 *
	 * @param string   $tag      The filter hook to which the function to be removed is hooked.
	 * @param callable $callback The callback to be removed.
	 * @param int      $priority Optional. Priority of the callback. Default 10.
	 *
	 * @return bool Whether the callback was successfully removed.
	 */
	public function remove_filter( $tag, $callback, $priority = 10 ) {
		return remove_filter( $tag, $callback, $priority );
	}

	/**
	 * Check if any callback has been registered for a filter hook.
	 *
	 * @since 10.9.0
	 *
	 * @param string         $tag      The name of the filter hook.
	 * @param callable|false $callback Optional. The callback to check for. Default false.
	 *
	 * @return bool|int If $callback is false, returns true if any callbacks are registered for $tag,
	 *                  or false if not. If $callback is provided, returns the priority of that callback
	 *                  or false if it is not registered.
	 */
	public function has_filter( $tag, $callback = false ) {
		return has_filter( $tag, $callback );
	}

	/**
	 * Returns the name of the current filter or action hook.
	 *
	 * @since 10.9.0
	 *
	 * @return string Hook name of the current filter or action.
	 */
	public function current_filter() {
		return current_filter();
	}

	/**
	 * Returns whether or not an action hook is currently being executed.
	 *
	 * @since 10.9.0
	 *
	 * @param string|null $action Optional. Action hook to check. Defaults to null, which checks if any action is currently being run.
	 *
	 * @return bool Whether the action is currently in the call stack.
	 */
	public function doing_action( $action = null ) {
		return doing_action( $action );
	}
}
