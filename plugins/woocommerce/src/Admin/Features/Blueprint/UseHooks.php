<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

trait UseHooks {

	/**
	 * Adds a filter to a specified tag.
	 *
	 * @param string   $tag             The name of the filter to hook the $function_to_add to.
	 * @param callable $function_to_add The callback to be run when the filter is applied.
	 * @param int      $priority        Optional. Used to specify the order in which the functions
	 *                                  associated with a particular action are executed. Default 10.
	 * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
	 */
	public function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
		add_filter($tag, $function_to_add, $priority, $accepted_args);
	}

	/**
	 * Adds an action to a specified tag.
	 *
	 * @param string   $tag             The name of the action to hook the $function_to_add to.
	 * @param callable $function_to_add The callback to be run when the action is triggered.
	 * @param int      $priority        Optional. Used to specify the order in which the functions
	 *                                  associated with a particular action are executed. Default 10.
	 * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
	 */
	public function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
		add_action($tag, $function_to_add, $priority, $accepted_args);
	}

	/**
	 * Calls the functions added to a filter hook.
	 *
	 * @param string $tag   The name of the filter hook.
	 * @param mixed  $value The value on which the filters hooked to $tag are applied on.
	 * @return mixed The filtered value after all hooked functions are applied to it.
	 */
	public function apply_filters($tag, $value) {
		$args = func_get_args();
		return call_user_func_array('apply_filters', $args);
	}

	/**
	 * Executes the functions hooked on a specific action hook.
	 *
	 * @param string $tag The name of the action to be executed.
	 * @param mixed  ...$arg Optional. Additional arguments which are passed on to the functions hooked to the action.
	 */
	public function do_action($tag, ...$args) {
		do_action($tag, ...$args);
	}
}
