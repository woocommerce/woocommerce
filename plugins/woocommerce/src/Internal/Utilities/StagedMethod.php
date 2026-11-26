<?php
/**
 * StagedMethod class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Utilities;

defined( 'ABSPATH' ) || exit;

/**
 * A WC_Data method that sets and saves in one call, paired with the method that only stages the change in memory.
 * The first method runs the stage method through WC_Data::write_through(), then saves. For example: update_status()
 * and stage_status().
 *
 * A class that overrides the first method with rules of its own must override the stage method too, at the same
 * level. Otherwise a direct call of the stage method skips those rules, so the stage method refuses the class.
 * The class keeps working through the first method. A stage method starts with
 * WC_Data::refuse_unstaged_override(), and PHPStan reports one that does not. An override of a stage method must
 * call the parent's stage method, because that call runs the refusal.
 *
 * @internal
 * @since 11.4.0
 */
final class StagedMethod {

	/**
	 * Name of the first class, from the object's class upward, that declares the method that sets and saves without
	 * declaring the stage method. Null when the object's class can stage the change.
	 *
	 * @param object $subject      Object to change, for example an order.
	 * @param string $method       Method that sets and saves, for example update_status.
	 * @param string $stage_method Method that only stages the change, for example stage_status.
	 * @return string|null
	 */
	public static function unstaged_class( object $subject, string $method, string $stage_method ): ?string {
		$class_name = get_class( $subject );
		while ( $class_name ) {
			if ( self::declares( $class_name, $method ) && ! self::declares( $class_name, $stage_method ) ) {
				return $class_name;
			}
			$class_name = get_parent_class( $class_name );
		}

		return null;
	}

	/**
	 * Refuse a direct call of the stage method when the object's class overrides the saving method without
	 * overriding the stage method too.
	 *
	 * @param object $subject      Object to change, for example an order.
	 * @param string $method       Method that sets and saves, for example update_status.
	 * @param string $stage_method Method that only stages the change, for example stage_status.
	 * @throws \WC_Data_Exception When the class has not adopted the stage method.
	 */
	public static function refuse_unstaged( object $subject, string $method, string $stage_method ): void {
		$class_name = self::unstaged_class( $subject, $method, $stage_method );
		if ( null !== $class_name ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- A plain text message for a client, built from PHP names.
			throw new \WC_Data_Exception(
				'woocommerce_data_staging_unsupported',
				sprintf(
					/* translators: 1: PHP class name of the object, 2: method that the class overrides, 3: method that the class needs to override. */
					__( 'This object does not support this change yet. The class %1$s overrides %2$s() and needs to override %3$s() too.', 'woocommerce' ),
					$class_name,
					$method,
					$stage_method
				),
				400
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
	}

	/**
	 * Whether a hook may fire on a direct stage call: refuse the change when a callback of the hook accepts fewer
	 * arguments than the hook passes, $staged included. Such a callback was written for the saving path, where the
	 * object and its items are saved.
	 *
	 * @param string $hook      Hook name.
	 * @param int    $arg_count Number of arguments the hook passes, $staged included.
	 * @throws \WC_Data_Exception When a callback does not accept $staged.
	 */
	public static function refuse_unadapted_callbacks( string $hook, int $arg_count ): void {
		global $wp_filter;

		$names = array();
		foreach ( isset( $wp_filter[ $hook ] ) ? $wp_filter[ $hook ]->callbacks : array() as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( (int) $callback['accepted_args'] < $arg_count ) {
					$names[] = self::callback_name( $callback['function'] );
				}
			}
		}

		if ( empty( $names ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- A plain text message for a client, built from PHP names.
		throw new \WC_Data_Exception(
			'woocommerce_data_staging_unsupported',
			sprintf(
				/* translators: 1: hook name, 2: comma-separated list of PHP callbacks, 3: number of arguments. */
				__( 'This change cannot be staged yet, because code on the %1$s hook does not support staged changes: %2$s. Each of these callbacks needs to accept $staged as its last argument, registered with %3$d accepted arguments. When $staged is true, the object and its items are not saved yet: they can have no IDs, they are not in the database, and the change can still be refused. The callback must read them from the object it gets and must not write to the database.', 'woocommerce' ),
				$hook,
				implode( ', ', $names ),
				$arg_count
			),
			400
		);
		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
	}

	/**
	 * Readable name of a hook callback: a function, a class method, or the file and line of a closure.
	 *
	 * @param mixed $callback Hook callback.
	 * @return string
	 */
	private static function callback_name( $callback ): string {
		if ( is_string( $callback ) ) {
			return $callback;
		}

		if ( is_array( $callback ) && isset( $callback[0], $callback[1] ) ) {
			return ( is_object( $callback[0] ) ? get_class( $callback[0] ) : (string) $callback[0] ) . '::' . $callback[1];
		}

		if ( $callback instanceof \Closure ) {
			$function = new \ReflectionFunction( $callback );
			$file     = str_replace( wp_normalize_path( WP_CONTENT_DIR ) . '/', '', wp_normalize_path( (string) $function->getFileName() ) );
			/* translators: 1: PHP file path, 2: line number. */
			return sprintf( __( 'a closure in %1$s on line %2$d', 'woocommerce' ), $file, $function->getStartLine() );
		}

		return is_object( $callback ) ? get_class( $callback ) . '::__invoke' : '';
	}

	/**
	 * Whether the class itself declares the method, rather than inherits it.
	 *
	 * @param string $class_name Class name.
	 * @param string $method     Method name.
	 * @return bool
	 */
	private static function declares( string $class_name, string $method ): bool {
		return method_exists( $class_name, $method ) && ( new \ReflectionMethod( $class_name, $method ) )->class === $class_name;
	}
}
