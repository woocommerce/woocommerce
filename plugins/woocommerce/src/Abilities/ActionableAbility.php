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
 * Only save() saves, inside a database transaction. A WP_Error or an exception
 * from save() rolls back every row it wrote. The other steps must not save,
 * send email or make HTTP requests.
 *
 * The API is experimental while the `ability_contracts` feature exists.
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
	 * @return mixed A WP_Error rejects the change.
	 */
	abstract public function change( $subject, array $input );

	/**
	 * The ability output for the saved object.
	 *
	 * @since 11.3.0
	 *
	 * @param \WC_Data $subject Saved object.
	 * @return mixed
	 */
	abstract public function prepare_response( $subject );

	/**
	 * The other objects that save() writes, each paired with its `extensions`
	 * input. The field writes and the validators of the resource run on each
	 * one after the subject, before save(). The default saves the subject only.
	 *
	 * @since 11.3.0
	 *
	 * @param \WC_Data $subject Changed subject.
	 * @param array    $input   Ability input.
	 * @return array<int, array{0: \WC_Data, 1: array}> Pairs of object and `extensions` input.
	 */
	public function pending_objects( $subject, array $input ): array {
		return array();
	}

	/**
	 * Save the changed object. The default saves a WC_Data object.
	 *
	 * @since 11.3.0
	 *
	 * @param \WC_Data $subject Changed object.
	 * @return mixed A WP_Error when the object was not saved.
	 */
	public function save( $subject ) {
		$subject->save();
		return null;
	}

	/**
	 * Run the change. This is the execute callback of the ability.
	 *
	 * @internal
	 *
	 * @param mixed $input Ability input.
	 * @return mixed
	 * @throws \Throwable When save() throws, after the rollback.
	 */
	public function execute_change( $input = null ) {
		$input   = is_array( $input ) ? $input : array();
		$subject = $this->load( $input );
		if ( is_wp_error( $subject ) ) {
			return $subject;
		}

		$enabled = AbilityExtensions::is_enabled();
		$updates = $enabled ? $this->prepare_fields( $input['extensions'] ?? null ) : array();
		if ( is_wp_error( $updates ) ) {
			return $updates;
		}

		$changed = $this->change( $subject, $input );
		if ( is_wp_error( $changed ) ) {
			return $changed;
		}

		$objects = array( array( $subject, $updates ) );
		foreach ( $this->pending_objects( $subject, $input ) as list( $object, $extensions ) ) {
			$pending = $enabled ? $this->prepare_fields( $extensions ) : array();
			if ( is_wp_error( $pending ) ) {
				return $pending;
			}
			$objects[] = array( $object, $pending );
		}

		foreach ( $objects as list( $object, $fields ) ) {
			$rejected = $this->apply_fields( $object, $fields );
			if ( null === $rejected && $enabled ) {
				$rejected = $this->validate( $object );
			}
			if ( null !== $rejected ) {
				return $rejected;
			}
		}

		wc_transaction_query( 'start' );
		try {
			$saved = $this->save( $subject );
		} catch ( \Throwable $e ) {
			wc_transaction_query( 'rollback' );
			throw $e;
		}
		if ( is_wp_error( $saved ) ) {
			wc_transaction_query( 'rollback' );
			return $saved;
		}
		wc_transaction_query( 'commit' );

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
		if ( is_array( $args['input_schema'] ?? null ) && AbilityExtensions::is_enabled() ) {
			$args['input_schema'] = $this->add_extensions_input_schema( $args['input_schema'] );
		}
		return parent::prepare_properties( $args );
	}

	/**
	 * Check `extensions.<namespace>.<field>` input values against the schema of
	 * each field, and cast them. No callback runs here. A null value is kept as
	 * is, to delete the value.
	 *
	 * @param mixed $values Values keyed by namespace, then field. Anything but an array is ignored.
	 * @return array|\WP_Error A list of update_callback and value pairs, or a WP_Error when a value is rejected.
	 */
	private function prepare_fields( $values ) {
		$registered = AbilityExtensions::get_fields( $this->get_resource() );
		$updates    = array();
		foreach ( is_array( $values ) ? $values : array() as $namespace => $fields ) {
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
	 * @return \WP_Error|null A WP_Error when a callback rejects a value.
	 */
	private function apply_fields( $subject, array $updates ): ?\WP_Error {
		foreach ( $updates as $param => list( $callback, $value ) ) {
			$rejected = self::run_callback( $callback, array( $subject, $value ), array( 'field' => $param ) );
			if ( null !== $rejected ) {
				return $rejected;
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
			$rejected = self::run_callback( $args['callback'], array( $subject, $this ), array( 'namespace' => $args['namespace'] ) );
			if ( null !== $rejected ) {
				return $rejected;
			}
		}
		return null;
	}

	/**
	 * Run an extension callback. A WP_Error that it returns, or an exception
	 * that it throws, becomes an error with the given data. An exception is
	 * logged and becomes a 500 with a fixed message, so its text never reaches
	 * the client.
	 *
	 * @param callable             $callback Field update_callback or validator.
	 * @param array                $args     Callback arguments.
	 * @param array<string, mixed> $data     Data that names the field or the namespace.
	 * @return \WP_Error|null
	 */
	private static function run_callback( callable $callback, array $args, array $data ): ?\WP_Error {
		try {
			$result = call_user_func_array( $callback, $args );
		} catch ( \Throwable $e ) {
			wc_get_logger()->error(
				sprintf( 'Ability extension "%s" failed: %s', implode( '', $data ), $e->getMessage() ),
				array( 'source' => 'ability-extensions' )
			);
			$result = new \WP_Error(
				'woocommerce_ability_extension_failed',
				__( 'An extension failed while checking this change.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}
		return is_wp_error( $result ) ? self::with_data( $result, $data ) : null;
	}

	/**
	 * Add the `extensions` property of the resource to the input schema. A
	 * schema with `oneOf` gets the property in each branch.
	 *
	 * @param array<string, mixed> $schema Input schema.
	 * @return array<string, mixed>
	 */
	private function add_extensions_input_schema( array $schema ): array {
		$extensions = AbilityExtensions::get_input_schema( $this->get_resource() );
		if ( isset( $schema['oneOf'] ) ) {
			foreach ( array_keys( $schema['oneOf'] ) as $index ) {
				$schema['oneOf'][ $index ]['properties']['extensions'] = $extensions;
			}
		} else {
			$schema['properties']['extensions'] = $extensions;
		}
		return $schema;
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
