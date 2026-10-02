<?php
/**
 * In-memory write runner class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AbilitiesApi;

defined( 'ABSPATH' ) || exit;

/**
 * Runs a write's steps: subject, validate, apply, the extension fields, the
 * object validators, one save, respond. No step saves.
 *
 * @since 11.3.0
 */
final class InMemoryWriteRunner {

	/**
	 * Run the write.
	 *
	 * @param string               $ability_name Ability name.
	 * @param array<string, mixed> $steps        `object_type` and the `subject`, `validate`, `apply` and optional `respond` callables.
	 * @param array                $input        Ability input.
	 * @return mixed The respond step's output, or a WP_Error when nothing was saved.
	 */
	public static function run( string $ability_name, array $steps, array $input ) {
		$target = call_user_func( $steps['subject'], $input );
		if ( is_wp_error( $target ) ) {
			return self::with_status( $target );
		}
		if ( ! is_object( $target ) ) {
			return new \WP_Error(
				'woocommerce_in_memory_write_not_found',
				__( 'The object to change was not found.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		/**
		 * Filters the InMemorySubject that saves an object which does not implement it.
		 *
		 * @since 11.3.0
		 *
		 * @param InMemorySubject|null $subject Subject, or null when nothing can save the object.
		 * @param object               $target  Object returned by the subject step.
		 */
		$subject = $target instanceof InMemorySubject ? $target : apply_filters( 'woocommerce_ability_in_memory_subject', null, $target );
		if ( ! $subject instanceof InMemorySubject ) {
			return new \WP_Error(
				'woocommerce_in_memory_write_unsupported',
				__( 'The object to change cannot be saved.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		try {
			$valid = call_user_func( $steps['validate'], $target, $input );
			if ( is_wp_error( $valid ) ) {
				return self::with_status( $valid );
			}

			$applied = call_user_func( $steps['apply'], $target, $input );
			if ( is_wp_error( $applied ) ) {
				return self::with_status( $applied );
			}

			$object_type = (string) ( $steps['object_type'] ?? '' );
			$rejection   = isset( $input['extensions'] ) ? AbilityFields::update( AbilityFields::get( $object_type ), $target, $input['extensions'] ) : null;
			$rejection   = $rejection ?? AbilityObjectValidators::validate( $target, $object_type );
			if ( null !== $rejection ) {
				return new \WP_Error( 'woocommerce_in_memory_write_rejected', $rejection->get_error_message(), array( 'status' => 400 ) );
			}

			$saved = $subject->save();
			if ( is_wp_error( $saved ) ) {
				return self::with_status( $saved );
			}
		} catch ( \Exception $exception ) {
			/**
			 * Filters the error an in-memory write returns when a step or the save throws.
			 *
			 * @since 11.3.0
			 *
			 * @param \WP_Error|null $error        Error, or null for a generic 500.
			 * @param \Exception     $exception    Exception.
			 * @param string         $ability_name Ability name.
			 */
			$error = apply_filters( 'woocommerce_in_memory_write_exception_error', null, $exception, $ability_name );
			return $error instanceof \WP_Error ? $error : new \WP_Error(
				'woocommerce_in_memory_write_save_failed',
				__( 'The change could not be saved.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		$response = isset( $steps['respond'] ) ? call_user_func( $steps['respond'], $target ) : null;
		return $response ?? $subject->snapshot();
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
