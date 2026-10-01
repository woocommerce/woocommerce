<?php
/**
 * InMemoryWriteExecutor class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

use Automattic\WooCommerce\Abilities\CommitsAfterSave;
use Automattic\WooCommerce\Abilities\InMemorySubject;
use Automattic\WooCommerce\Abilities\InMemoryWrite;
use Automattic\WooCommerce\Abilities\ObjectValidatorRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Runs an in-memory write for direct callers: subject, validate, apply,
 * object validators, one save. The write never saves by itself.
 *
 * @since 11.3.0
 */
final class InMemoryWriteExecutor {

	/**
	 * Run the write.
	 *
	 * @param string $class_name Name of a class implementing InMemoryWrite.
	 * @param array  $input      Ability input.
	 * @return array|\WP_Error The write's respond() output, or an error when nothing was saved.
	 *
	 * @since 11.3.0
	 */
	public static function run( string $class_name, array $input ) {
		$subject = $class_name::subject( $input );
		if ( ! $subject instanceof \WC_Data && ! $subject instanceof InMemorySubject ) {
			return new \WP_Error(
				'woocommerce_in_memory_write_not_found',
				__( 'The object to change was not found.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		try {
			$valid = $class_name::validate( $subject, $input );
			if ( is_wp_error( $valid ) ) {
				return self::with_status( $valid );
			}

			$class_name::apply( $subject, $input );

			$rejection = ObjectValidatorRegistry::instance()->validate( $subject, $class_name::subject_type() );
			if ( null !== $rejection ) {
				return new \WP_Error( 'woocommerce_in_memory_write_rejected', $rejection, array( 'status' => 400 ) );
			}

			$saved = $subject->save();
		} catch ( \WC_Data_Exception $exception ) {
			return new \WP_Error( 'woocommerce_in_memory_write_save_failed', $exception->getMessage(), array( 'status' => 400 ) );
		} catch ( \Exception $exception ) {
			wc_get_logger()->error(
				'In-memory write failed.',
				array(
					'source'    => 'woocommerce-abilities',
					'ability'   => $class_name::get_name(),
					'exception' => get_class( $exception ),
					'message'   => $exception->getMessage(),
				)
			);
			return new \WP_Error( 'woocommerce_in_memory_write_save_failed', __( 'The change could not be saved.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		if ( is_wp_error( $saved ) ) {
			return self::with_status( $saved );
		}

		if ( is_a( $class_name, CommitsAfterSave::class, true ) ) {
			$committed = $class_name::commit( $subject, $input );
			if ( is_wp_error( $committed ) ) {
				return self::with_status( $committed );
			}
		}

		return $class_name::respond( $subject );
	}

	/**
	 * Give an error a 400 status unless it carries one.
	 *
	 * @param \WP_Error $error Error.
	 */
	private static function with_status( \WP_Error $error ): \WP_Error {
		$data = $error->get_error_data();
		if ( ! isset( $data['status'] ) ) {
			$error->add_data( array_merge( is_array( $data ) ? $data : array(), array( 'status' => 400 ) ) );
		}
		return $error;
	}
}
