<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Deprecated_Hooks class maps old actions and filters to new ones. This is the base class for handling those deprecated hooks.
 *
 * Based on the WCS_Hook_Deprecator class by Prospress.
 *
 * @since 3.0.0
 */
abstract class WC_Deprecated_Hooks {

	/**
	 * Array of deprecated hooks we need to handle.
	 *
	 * @var array
	 */
	protected $deprecated_hooks = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$new_hooks = array_keys( $this->deprecated_hooks );
		array_walk( $new_hooks, array( $this, 'hook_in' ) );
	}

	/**
	 * Hook into the new hook so we can handle deprecated hooks once fired.
	 *
	 * @param  string $hook_name
	 */
	abstract function hook_in( $hook_name );

	/**
	 * Get old hooks to map to new hook.
	 *
	 * @param  string $new_hook
	 * @return array
	 */
	public function get_old_hooks( $new_hook ) {
		$old_hooks = isset( $this->deprecated_hooks[ $new_hook ] ) ? $this->deprecated_hooks[ $new_hook ] : array();
		$old_hooks = is_array( $old_hooks ) ? $old_hooks : array( $old_hooks );

		return $old_hooks;
	}

	/**
	 * If the hook is Deprecated, call the old hooks here.
	 */
	public function maybe_handle_deprecated_hook() {
		$new_hook          = current_filter();
		$old_hooks         = $this->get_old_hooks( $new_hook );
		$new_callback_args = func_get_args();
		$return_value      = $new_callback_args[0];

		foreach ( $old_hooks as $old_hook ) {
			$return_value = $this->handle_deprecated_hook( $new_hook, $old_hook, $new_callback_args, $return_value );
		}

		return $return_value;
	}

	/**
	 * If the old hook is in-use, trigger it.
	 *
	 * @param string $new_hook
	 * @param string $old_hook
	 * @param array $new_callback_args
	 * @param mixed $return_value
	 * @return mixed
	 */
	abstract function handle_deprecated_hook( $new_hook, $old_hook, $new_callback_args, $return_value );

	/**
	 * Display a deprecated notice for old hooks.
	 *
	 * @param string $old_hook
	 * @param string $new_hook
	 */
	protected function display_notice( $old_hook, $new_hook ) {
		wc_deprecated_function( sprintf( 'The "%s" hook uses out of date data structures and', esc_html( $old_hook ) ), WC_VERSION, esc_html( $new_hook ) );
	}

	/**
	 * Fire off a legacy hook with it's args.
	 *
	 * @param  string $old_hook
	 * @param  array $new_callback_args
	 * @return mixed
	 */
	abstract protected function trigger_hook( $old_hook, $new_callback_args );
}
