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
	 * Results are memoized per hook for the lifetime of the request and are
	 * recomputed only when the set of registered callbacks changes, so that
	 * repeated calls do not repeat the reflection work that closures and
	 * invokable objects require.
	 *
	 * @param string $hook_name The name of the hook to inspect.
	 * @return array<int, array<string>> Array of priority => array( signatures ),  empty if hook has no callbacks.
	 *
	 * @since 10.5.0
	 */
	public static function get_hook_callback_signatures( string $hook_name ): array {
		global $wp_filter;

		static $cache = array();

		if ( ! isset( $wp_filter[ $hook_name ] ) ) {
			unset( $cache[ $hook_name ] );
			return array();
		}

		$callbacks_by_priority = $wp_filter[ $hook_name ]->callbacks;
		$fingerprint           = self::get_callbacks_fingerprint( $callbacks_by_priority );

		if ( isset( $cache[ $hook_name ] ) && $cache[ $hook_name ]['fingerprint'] === $fingerprint ) {
			return $cache[ $hook_name ]['signatures'];
		}

		$result = array();

		foreach ( $callbacks_by_priority as $priority => $priority_callbacks ) {
			$result[ $priority ] = array_map(
				fn( $callback_data ) => self::get_callback_signature( $callback_data['function'] ),
				array_values( $priority_callbacks )
			);
		}

		$cache[ $hook_name ] = array(
			'fingerprint' => $fingerprint,
			'signatures'  => $result,
			/*
			 * Retaining the callables keeps their object ids from being reused by a
			 * later object while this entry is live, which would otherwise let a
			 * removed-then-replaced callback produce an identical fingerprint.
			 */
			'callbacks'   => $callbacks_by_priority,
		);

		return $result;
	}

	/**
	 * Build a cheap identity fingerprint for a hook's registered callables.
	 *
	 * Deliberately avoids reflection: it captures only priority, class name and
	 * object id, which is enough to detect that the registered set has changed
	 * without paying the cost of resolving each signature.
	 *
	 * @param array $callbacks_by_priority The `callbacks` property of a WP_Hook instance.
	 * @return string Fingerprint of the registered callables.
	 */
	private static function get_callbacks_fingerprint( array $callbacks_by_priority ): string {
		$parts = array();

		foreach ( $callbacks_by_priority as $priority => $priority_callbacks ) {
			foreach ( $priority_callbacks as $callback_data ) {
				$function = $callback_data['function'];

				if ( is_string( $function ) ) {
					$parts[] = $priority . ':' . $function;
				} elseif ( is_array( $function ) && 2 === count( $function )
					&& ( is_object( $function[0] ) || is_string( $function[0] ) )
					&& is_string( $function[1] ) ) {
					$target  = $function[0];
					$parts[] = $priority . ':' .
						( is_object( $target ) ? get_class( $target ) . '#' . spl_object_id( $target ) : $target ) .
						'::' . $function[1];
				} elseif ( is_object( $function ) ) {
					$parts[] = $priority . ':' . get_class( $function ) . '#' . spl_object_id( $function );
				} else {
					/*
					 * Any other shape: defer to the signature itself, so that the
					 * fingerprint can never be coarser than the value it guards.
					 * These shapes are rare, and the branches above never reach here.
					 */
					$parts[] = $priority . ':' . self::get_callback_signature( $function );
				}
			}
		}

		return implode( '|', $parts );
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
