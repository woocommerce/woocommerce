<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Utilities;

/**
 * Utility class for working with WordPress hooks and callbacks.
 *
 * @since 10.5.0
 */
final class CallbackUtil {

	/**
	 * Get a stable signature for a callback that can be used for hashing.
	 *
	 * This method normalizes callbacks into consistent string representations,
	 * regardless of changes in dynamic properties in callback instances.
	 *
	 * @param callable|mixed $callback A PHP callback.
	 * @return string Normalized callback signature.
	 *
	 * @since 10.5.0
	 */
	public static function get_callback_signature( $callback ): string {
		if ( is_string( $callback ) ) {
			// Standalone function.
			return $callback;
		}

		if ( is_array( $callback ) && 2 === count( $callback ) ) {
			$target = $callback[0];
			$method = $callback[1];

			if ( ( is_object( $target ) || is_string( $target ) ) && is_string( $method ) ) {
				// Array callback (class method).
				$class = is_object( $target ) ? get_class( $target ) : $target;
				return "{$class}::{$method}";
			}
		}

		if ( $callback instanceof \Closure ) {
			// Closure.
			return 'Closure@' . spl_object_hash( $callback );
		}

		if ( is_object( $callback ) ) {
			// Invokable object.
			return get_class( $callback ) . '::__invoke';
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Fallback for unknown callback types.
		return serialize( $callback );
	}

	/**
	 * Get signatures for all callbacks attached to a specific hook.
	 *
	 * Returns an array of callback signatures for all callbacks registered
	 * with the specified hook name, organized by priority. This is useful
	 * for generating cache keys or comparing hook state.
	 *
	 * @param string $hook_name The name of the hook to inspect.
	 * @return array<int, array<string>> Array of priority => array( signatures ),  empty if hook has no callbacks.
	 *
	 * @since 10.5.0
	 */
	public static function get_hook_callback_signatures( string $hook_name ): array {
		global $wp_filter;

		if ( ! isset( $wp_filter[ $hook_name ] ) ) {
			return array();
		}

		// Note that $wp_filter is already keyed by priority and array_map preserves associative keys.
		return array_map(
			fn( $priority_callbacks ) => array_map(
				fn( $callback_data ) => self::get_callback_signature( $callback_data['function'] ),
				array_values( $priority_callbacks )
			),
			$wp_filter[ $hook_name ]->callbacks
		);
	}
}
