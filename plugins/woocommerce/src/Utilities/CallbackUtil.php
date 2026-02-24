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
			try {
				return self::get_closure_signature( $callback );
			} catch ( \Exception $e ) {
				return 'Closure@' . spl_object_hash( $callback );
			}
		}

		if ( is_object( $callback ) ) {
			// Invokable object.
			try {
				return self::get_invokable_signature( $callback );
			} catch ( \Exception $e ) {
				return get_class( $callback ) . '::__invoke';
			}
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
	 * Closure signatures are based on their file location and line numbers,
	 * providing consistent hashes across requests for the same closure code.
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

		$result = array();

		foreach ( $wp_filter[ $hook_name ]->callbacks as $priority => $priority_callbacks ) {
			$result[ $priority ] = array_map(
				fn( $callback_data ) => self::get_callback_signature( $callback_data['function'] ),
				array_values( $priority_callbacks )
			);
		}

		return $result;
	}

	/**
	 * Get a stable signature for a closure based on its file path and line numbers.
	 *
	 * @param \Closure $closure The closure to generate a signature for.
	 * @return string Signature in the format 'Closure@filename:startLine-endLine'.
	 * @throws \ReflectionException If reflection fails.
	 */
	private static function get_closure_signature( \Closure $closure ): string {
		$reflection = new \ReflectionFunction( $closure );
		$file       = $reflection->getFileName();
		$start      = $reflection->getStartLine();
		$end        = $reflection->getEndLine();

		if ( false === $file || false === $start || false === $end ) {
			throw new \ReflectionException( 'Unable to get closure location information' );
		}

		return sprintf( 'Closure@%s:%d-%d', $file, $start, $end );
	}

	/**
	 * Get a stable signature for an invokable object based on its class and __invoke method location.
	 *
	 * For regular classes, returns 'ClassName::__invoke' since the class name is stable.
	 * For anonymous classes, includes file location since the class name varies between requests.
	 *
	 * @param object $invokable The invokable object to generate a signature for.
	 * @return string Signature in format 'ClassName::__invoke' or 'class@anonymous[hash]::__invoke@filename:startLine-endLine'.
	 */
	private static function get_invokable_signature( object $invokable ): string {
		$method = new \ReflectionMethod( $invokable, '__invoke' );
		$class  = $method->getDeclaringClass();

		if ( ! $class->isAnonymous() ) {
			return $class->getName() . '::__invoke';
		}

		return sprintf(
			'class@anonymous[%s]::__invoke@%s:%d-%d',
			md5( $class->getName() ),
			$method->getFileName(),
			$method->getStartLine(),
			$method->getEndLine()
		);
	}
}
