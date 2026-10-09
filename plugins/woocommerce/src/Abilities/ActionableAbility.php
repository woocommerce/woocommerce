<?php
/**
 * Actionable ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * An ability that changes one object and saves it one time. Register it with
 * a subclass as the `ability_class`. The execute callback loads the object,
 * checks the `extensions` input against the field schemas, changes the object
 * in memory, writes the fields with their `update_callback`, runs the validators
 * that AbilityExtensions::register_validator() adds, and then saves it. A
 * rejection from any step saves nothing.
 *
 * The input schema gets the `extensions` property of the resource, so the
 * ability accepts the fields with no extra code.
 *
 * With the feature off, the input schema gets no `extensions` property and the
 * input is ignored. An ability whose own input schema sets
 * `additionalProperties` to false rejects it instead.
 *
 * Only save() saves. The other steps must not save, send email or make HTTP
 * requests.
 *
 * The API is experimental while the `extensible_abilities` feature exists.
 *
 * @since 11.3.0
 */
abstract class ActionableAbility extends \WP_Ability {

	/**
	 * Resource that the ability changes, for example `product` or `order`. The fields of this resource are written.
	 *
	 * @since 11.3.0
	 *
	 * @return string
	 */
	abstract public function get_resource(): string;

	/**
	 * Load the object to change.
	 *
	 * @since 11.3.0
	 *
	 * @param array $input Ability input.
	 * @return \WC_Data|\WP_Error
	 */
	abstract public function load( array $input );

	/**
	 * Change the object in memory.
	 *
	 * @since 11.3.0
	 *
	 * @param \WC_Data $subject Object to change.
	 * @param array    $input   Ability input.
	 * @return null|\WP_Error A WP_Error rejects the change, and nothing is saved.
	 */
	abstract public function change( $subject, array $input );

	/**
	 * The ability output for the saved object. It must match the output schema
	 * of the ability.
	 *
	 * @since 11.3.0
	 *
	 * @param \WC_Data $subject Saved object.
	 * @return mixed
	 */
	abstract public function prepare_response( $subject );

	/**
	 * Save the changed object. The default calls WC_Data::save(). When the save
	 * throws or leaves the object without an ID, it logs the error and returns
	 * a WP_Error with a fixed message, so the exception text stays out of the
	 * response.
	 *
	 * @since 11.3.0
	 *
	 * @param \WC_Data $subject Changed object.
	 * @return null|\WP_Error Null when the object was saved, a WP_Error when it was not.
	 */
	public function save( $subject ) {
		try {
			$subject->save();
		} catch ( \Exception $e ) {
			wc_get_logger()->error(
				sprintf( 'Ability "%s" failed to save: %s', $this->get_name(), $e->getMessage() ),
				array( 'source' => 'woocommerce-abilities' )
			);
			return self::save_failed();
		}

		return $subject->get_id() > 0 ? null : self::save_failed();
	}

	/**
	 * Run the change. This is the execute callback of the ability.
	 *
	 * @internal
	 *
	 * @param mixed $input Ability input.
	 * @return mixed|\WP_Error The output of prepare_response(), or the WP_Error of the step that refused the change.
	 */
	public function execute_change( $input = null ) {
		$input   = is_array( $input ) ? $input : array();
		$subject = $this->load( $input );
		if ( is_wp_error( $subject ) ) {
			return $subject;
		}

		$updates = array();
		if ( ExtensibleAbilities::is_enabled() ) {
			$extensions = is_array( $input['extensions'] ?? null ) ? $input['extensions'] : array();
			$updates    = $this->prepare_fields( $extensions );
			if ( is_wp_error( $updates ) ) {
				return $updates;
			}
		}

		$changed = $this->change( $subject, $input );
		if ( is_wp_error( $changed ) ) {
			return $changed;
		}

		$rejected = $this->apply_fields( $subject, $updates );
		if ( null === $rejected && AbilityContracts::is_enabled() ) {
			$rejected = $this->validate( $subject );
		}
		if ( null !== $rejected ) {
			return $rejected;
		}

		$saved = $this->save( $subject );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return $this->prepare_response( $subject );
	}

	/**
	 * Use the change as the execute callback, and add the `extensions` input
	 * when the feature is on.
	 *
	 * @param array $args Ability arguments.
	 * @return array
	 */
	protected function prepare_properties( array $args ): array {
		$args['execute_callback'] = array( $this, 'execute_change' );
		if ( is_array( $args['input_schema'] ?? null ) && ExtensibleAbilities::is_enabled() ) {
			$args['input_schema'] = $this->add_extensions_input_schema( $args['input_schema'] );
		}
		return parent::prepare_properties( $args );
	}

	/**
	 * Check `extensions.<namespace>.<field>` input values against the schema of
	 * each field, and cast them. No callback runs here. A null value is kept as
	 * is, to delete the value.
	 *
	 * @param array<string, mixed> $values Values keyed by namespace, then field.
	 * @return array|\WP_Error A list of update_callback and value pairs, or a WP_Error when a value is rejected.
	 */
	private function prepare_fields( array $values ) {
		$registered = ExtensibleAbilities::get_fields( $this->get_resource() );
		$updates    = array();
		foreach ( $values as $namespace => $fields ) {
			foreach ( (array) $fields as $field => $value ) {
				$args = $registered[ $namespace ][ $field ] ?? array();
				if ( ! isset( $args['update_callback'] ) ) {
					return new \WP_Error(
						'woocommerce_ability_field_not_writable',
						/* translators: 1: Extension namespace. 2: Field name. */
						sprintf( __( 'The extension field "%1$s.%2$s" cannot be written.', 'woocommerce' ), $namespace, $field ),
						array( 'status' => 400 )
					);
				}
				if ( null !== $value ) {
					$param = 'extensions.' . $namespace . '.' . $field;
					$valid = rest_validate_value_from_schema( $value, $args['schema'], $param );
					if ( is_wp_error( $valid ) ) {
						return self::with_data( $valid, array( 'field' => $param ) );
					}
					$value = rest_sanitize_value_from_schema( $value, $args['schema'], $param );
					if ( is_wp_error( $value ) ) {
						return self::with_status( $value );
					}
				}
				$updates[ 'extensions.' . $namespace . '.' . $field ] = array( $args['update_callback'], $value );
			}
		}
		return $updates;
	}

	/**
	 * Write the checked values to the object in memory with the update_callback
	 * of each field.
	 *
	 * @param \WC_Data $subject Object to change.
	 * @param array    $updates Pairs of update_callback and value from prepare_fields().
	 * @return \WP_Error|null A WP_Error when a callback rejects a value or throws.
	 */
	private function apply_fields( $subject, array $updates ): ?\WP_Error {
		foreach ( $updates as $param => list( $callback, $value ) ) {
			$updated = call_user_func( $callback, $subject, $value );
			if ( is_wp_error( $updated ) ) {
				return self::with_data( $updated, array( 'field' => $param ) );
			}
		}
		return null;
	}

	/**
	 * Run the validators of the resource on the changed object.
	 *
	 * @param \WC_Data $subject Changed object.
	 * @return \WP_Error|null A WP_Error when a validator refuses the change.
	 */
	private function validate( $subject ): ?\WP_Error {
		foreach ( AbilityExtensions::get_validators( $this->get_resource() ) as $args ) {
			$valid = call_user_func( $args['callback'], $subject, $this );
			if ( is_wp_error( $valid ) ) {
				return self::with_data( $valid, array( 'namespace' => $args['namespace'] ) );
			}
		}
		return null;
	}

	/**
	 * Add the `extensions` property one time, at the top level of the input
	 * schema. It lists the fields that have an update_callback, and allows null
	 * for each one. Each `oneOf`, `anyOf` and `allOf` branch only accepts the
	 * key, so a branch with `additionalProperties` set to false does not reject
	 * it, and the schema does not grow with the number of branches.
	 *
	 * @param array<string, mixed> $schema Input schema.
	 * @return array<string, mixed>
	 */
	private function add_extensions_input_schema( array $schema ): array {
		$extensions = array(
			'type'                 => 'object',
			'description'          => __( 'Values to write to the fields that extensions add, keyed by extension namespace, then field. A null value deletes the value.', 'woocommerce' ),
			'additionalProperties' => false,
		);
		foreach ( ExtensibleAbilities::get_fields( $this->get_resource() ) as $namespace => $fields ) {
			$writable = array_filter(
				$fields,
				static function ( array $args ): bool {
					return isset( $args['update_callback'] );
				}
			);
			if ( empty( $writable ) ) {
				continue;
			}
			$extensions['properties'][ $namespace ] = array(
				'type'                 => 'object',
				'properties'           => array_map(
					static function ( array $args ): array {
						$field_schema = $args['schema'];
						if ( isset( $field_schema['type'] ) ) {
							$field_schema['type'] = array_values( array_unique( array_merge( (array) $field_schema['type'], array( 'null' ) ) ) );
						}
						if ( isset( $field_schema['enum'] ) && ! in_array( null, $field_schema['enum'], true ) ) {
							$field_schema['enum'][] = null;
						}
						return $field_schema;
					},
					$writable
				),
				'additionalProperties' => false,
			);
		}

		$schema['properties']['extensions'] = $extensions;
		foreach ( array( 'oneOf', 'anyOf', 'allOf' ) as $keyword ) {
			foreach ( array_keys( $schema[ $keyword ] ?? array() ) as $index ) {
				$schema[ $keyword ][ $index ]['properties']['extensions'] = array(
					'type'        => 'object',
					'description' => __( 'The fields are listed in the top-level extensions property.', 'woocommerce' ),
				);
			}
		}
		return $schema;
	}

	/**
	 * The error for a save that failed.
	 *
	 * @return \WP_Error
	 */
	private static function save_failed(): \WP_Error {
		return new \WP_Error(
			'woocommerce_ability_save_failed',
			__( 'The change could not be saved.', 'woocommerce' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Add data to an error, and the 400 status when it has none.
	 *
	 * @param \WP_Error            $error Error.
	 * @param array<string, mixed> $data  Data to add.
	 * @return \WP_Error
	 */
	private static function with_data( \WP_Error $error, array $data ): \WP_Error {
		$current = $error->get_error_data();
		$error->add_data( array_merge( array( 'status' => 400 ), is_array( $current ) ? $current : array(), $data ) );
		return $error;
	}
}
