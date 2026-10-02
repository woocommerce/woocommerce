<?php
/**
 * Object change ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AbilitiesApi;

defined( 'ABSPATH' ) || exit;

/**
 * An ability that changes one object, registered with this class or a
 * subclass as its `ability_class`. Execute changes the object in memory, runs
 * the extension fields and the object validators, and saves once. dry_run()
 * runs the same steps without the save. No method saves.
 *
 * Fixed facts about the ability, such as whether it is destructive, belong in
 * `meta.annotations`.
 *
 * @since 11.3.0
 */
abstract class ObjectChangeAbility extends PolyfilledAbility {

	/**
	 * Object type the ability changes. The fields and validators registered for it run on the changed object.
	 */
	abstract public static function object_type(): string;

	/**
	 * Load the object to change. Never saves.
	 *
	 * @param array $input Ability input.
	 * @return object|\WP_Error|null
	 */
	abstract public function load( array $input );

	/**
	 * Change the object in memory. It must not save, send email or make HTTP
	 * requests. Nothing is saved until every step passes.
	 *
	 * @param object $subject Object.
	 * @param array  $input   Ability input.
	 * @return mixed A WP_Error to reject the change. Nothing is saved.
	 */
	abstract public function change( $subject, array $input );

	/**
	 * The output for the saved object, or null for the object's data.
	 *
	 * @param object $subject Saved object.
	 * @return mixed
	 */
	public function prepare_response( $subject ) {
		return null;
	}

	/**
	 * The ability call that undoes this change, read from the object before
	 * change(), or null when it cannot be undone. It is not run.
	 *
	 * @param object $subject Object, before the change.
	 * @param array  $input   Ability input.
	 * @return array{ability: string, input: array}|null
	 */
	public function undo( $subject, array $input ): ?array {
		return null;
	}

	/**
	 * Short sentences that describe the effects of this call, such as "Emails the customer".
	 *
	 * @param object $subject Object, before the change.
	 * @param array  $input   Ability input.
	 * @return string[]
	 */
	public function side_effects( $subject, array $input ): array {
		return array();
	}

	/**
	 * What the change would do, without saving: the values that differ, the
	 * side effects and the undo call. It runs every step of execute except the
	 * save, and checks the input and permissions the same way.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error The change summary, or the error execute would return.
	 */
	public function dry_run( array $input ) {
		$input = $this->normalize_input( $input );
		if ( is_wp_error( $input ) ) {
			return $input;
		}
		$valid = $this->validate_input( $input );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		if ( true !== $this->check_permissions( $input ) ) {
			return new \WP_Error(
				'ability_invalid_permissions',
				/* translators: %s ability name. */
				sprintf( __( 'Ability "%s" does not have necessary permission.', 'woocommerce' ), $this->get_name() )
			);
		}

		$staged = $this->stage( is_array( $input ) ? $input : array() );
		return is_wp_error( $staged ) ? $staged : $staged->summary();
	}

	/**
	 * Run the change and save it once.
	 *
	 * @param mixed $input Ability input.
	 * @return mixed
	 */
	public function run_change( $input = null ) {
		$staged = $this->stage( is_array( $input ) ? $input : array() );
		if ( is_wp_error( $staged ) ) {
			return $staged;
		}

		try {
			$saved = $staged->save();
		} catch ( \Exception $exception ) {
			return $this->exception_error( $exception );
		}
		if ( is_wp_error( $saved ) ) {
			return self::with_status( $saved );
		}

		return $this->prepare_response( $staged->target() ) ?? $staged->snapshot();
	}

	/**
	 * Every step except the save: load, change, the extension fields and the object validators.
	 *
	 * @param array $input Ability input.
	 * @return StagedChange|\WP_Error
	 */
	protected function stage( array $input ) {
		$target = $this->load( $input );
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
		 * @param object               $target  Object returned by load().
		 */
		$subject = $target instanceof InMemorySubject ? $target : apply_filters( 'woocommerce_ability_in_memory_subject', null, $target );
		if ( ! $subject instanceof InMemorySubject ) {
			return new \WP_Error(
				'woocommerce_in_memory_write_unsupported',
				__( 'The object to change cannot be saved.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		$object_type = static::object_type();
		$fields      = AbilityFields::get( $object_type );
		try {
			$before       = self::read( $subject, $target, $fields );
			$undo         = $this->undo( $target, $input );
			$side_effects = $this->side_effects( $target, $input );

			$changed = $this->change( $target, $input );
			if ( is_wp_error( $changed ) ) {
				return self::with_status( $changed );
			}

			$rejection = isset( $input['extensions'] ) ? AbilityFields::update( $fields, $target, $input['extensions'] ) : null;
			$rejection = $rejection ?? AbilityObjectValidators::validate( $target, $object_type );
			if ( null !== $rejection ) {
				return new \WP_Error( 'woocommerce_in_memory_write_rejected', $rejection->get_error_message(), array( 'status' => 400 ) );
			}

			return new StagedChange( $this->get_name(), $object_type, $target, $subject, $before, self::read( $subject, $target, $fields ), $side_effects, $undo );
		} catch ( \Exception $exception ) {
			return $this->exception_error( $exception );
		}
	}

	/**
	 * Use the change as the execute callback.
	 *
	 * @param array $args Ability arguments.
	 * @return array
	 */
	protected function prepare_properties( array $args ): array {
		$args['execute_callback'] = array( $this, 'run_change' );
		return parent::prepare_properties( $args );
	}

	/**
	 * The object's data, with objects that print as text as text, and its extension field values.
	 *
	 * @param InMemorySubject                     $subject Subject.
	 * @param object                              $target  Object.
	 * @param array<string, array<string, mixed>> $fields  Fields keyed by attribute.
	 */
	private static function read( InMemorySubject $subject, $target, array $fields ): array {
		$values = $subject->snapshot();
		array_walk_recursive(
			$values,
			static function ( &$value ) {
				if ( is_object( $value ) && method_exists( $value, '__toString' ) ) {
					$value = (string) $value;
				}
			}
		);
		$values['extensions'] = AbilityFields::read( $fields, $target );
		return $values;
	}

	/**
	 * The error for an exception a step or the save threw.
	 *
	 * @param \Exception $exception Exception.
	 */
	private function exception_error( \Exception $exception ): \WP_Error {
		/**
		 * Filters the error an object change returns when a step or the save throws.
		 *
		 * @since 11.3.0
		 *
		 * @param \WP_Error|null $error        Error, or null for a generic 500.
		 * @param \Exception     $exception    Exception.
		 * @param string         $ability_name Ability name.
		 */
		$error = apply_filters( 'woocommerce_in_memory_write_exception_error', null, $exception, $this->get_name() );
		return $error instanceof \WP_Error ? $error : new \WP_Error(
			'woocommerce_in_memory_write_save_failed',
			__( 'The change could not be saved.', 'woocommerce' ),
			array( 'status' => 500 )
		);
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
