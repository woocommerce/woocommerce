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
 * that ExtensibleAbilities::register_validator() adds, and then saves it. A
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
 * from save() rolls back every row it wrote, so an ability that saves more than
 * one object, with pending_objects(), leaves nothing behind when a later save
 * fails. After the last save, still inside the transaction, the
 * `woocommerce_ability_after_save` action gives extensions one moment to write
 * what the saved objects imply elsewhere, such as the price range a variable
 * parent derives from its variations, or a row in a table of the extension. A
 * refusal there rolls the call back too. The rollback does not undo what a
 * listener did outside the database, such as an HTTP request, and it does
 * nothing on a table engine without transactions. The other steps must not
 * save, send email or make HTTP requests.
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
	 * The other objects that save() writes, each paired with its `extensions`
	 * input. The field writes and the validators of the resource run on each
	 * one after the subject, before save(). The default saves the subject only.
	 *
	 * @since 11.4.0
	 *
	 * @param \WC_Data $subject Changed subject.
	 * @param array    $input   Ability input.
	 * @return array<int, array{0: \WC_Data, 1: array}> Pairs of object and `extensions` input.
	 */
	public function pending_objects( $subject, array $input ): array {
		return array();
	}

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
	 * @throws \Throwable When save() throws, after the rollback.
	 */
	public function execute_change( $input = null ) {
		$input   = is_array( $input ) ? $input : array();
		$subject = $this->load( $input );
		if ( is_wp_error( $subject ) ) {
			return $subject;
		}

		$enabled = ExtensibleAbilities::is_enabled();
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
			if ( ! is_wp_error( $saved ) && $enabled ) {
				$saved = $this->after_save( array_column( $objects, 0 ) );
			}
		} catch ( \Throwable $e ) {
			wc_transaction_query( 'rollback' );
			self::forget_rolled_back_objects( $objects );
			throw $e;
		}
		if ( is_wp_error( $saved ) ) {
			wc_transaction_query( 'rollback' );
			self::forget_rolled_back_objects( $objects );
			return $saved;
		}
		wc_transaction_query( 'commit' );

		return $this->prepare_response( $subject );
	}

	/**
	 * Give extensions one moment, after the last save and before the commit,
	 * to write what the saved objects imply elsewhere.
	 *
	 * @param \WC_Data[] $saved The saved objects, the subject first.
	 * @return \WP_Error|null A WP_Error when a listener refused, and then the call rolls back.
	 */
	private function after_save( array $saved ): ?\WP_Error {
		$ability = $this;
		return self::run_callback(
			static function () use ( $saved, $ability ): void {
				/**
				 * Fires once per ability call, after every object of the call is
				 * saved and before the transaction commits. Listeners write what
				 * the saved objects imply on other objects or tables, such as the
				 * price range a variable parent derives from its variations, or a
				 * row in a table of the extension. A WC_Data_Exception refuses the
				 * call with its code and message, any other exception refuses it
				 * with a fixed message, and either way every save of the call is
				 * rolled back. Listeners must not send email or make HTTP requests.
				 *
				 * @since 11.4.0
				 *
				 * @param \WC_Data[]  $saved   The saved objects, the subject of the ability first.
				 * @param \WP_Ability $ability The ability that saved them.
				 */
				do_action( 'woocommerce_ability_after_save', $saved, $ability );
			},
			array(),
			array( 'hook' => 'woocommerce_ability_after_save' )
		);
	}

	/**
	 * Drop the object caches of the objects a rolled back save() touched. The
	 * saves filled the caches with rows the rollback removed, so a read in the
	 * same request would still see them.
	 *
	 * @param array<int, array{0: \WC_Data, 1: array}> $objects The subject and the pending objects.
	 */
	private static function forget_rolled_back_objects( array $objects ): void {
		foreach ( $objects as list( $object ) ) {
			$id = $object instanceof \WC_Data ? $object->get_id() : 0;
			if ( $id <= 0 ) {
				continue;
			}
			if ( $object instanceof \WC_Product ) {
				clean_post_cache( $id );
				wc_delete_product_transients( $id );
				if ( $object->get_parent_id() > 0 ) {
					clean_post_cache( $object->get_parent_id() );
					wc_delete_product_transients( $object->get_parent_id() );
				}
			} elseif ( $object instanceof \WC_Abstract_Order ) {
				wc_get_container()->get( \Automattic\WooCommerce\Caches\OrderCache::class )->remove( $id );
				clean_post_cache( $id );
			}
		}
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
	 * @param mixed $values Values keyed by namespace, then field. Anything but an array is ignored.
	 * @return array|\WP_Error A list of field arguments and value pairs, or a WP_Error when a value is rejected.
	 */
	private function prepare_fields( $values ) {
		$registered = ExtensibleAbilities::get_fields( $this->get_resource() );
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
					if ( is_wp_error( $value ) ) {
						return self::with_data( $value, array( 'field' => $param ) );
					}
				}
				$updates[ 'extensions.' . $namespace . '.' . $field ] = array( $args, $value );
			}
		}
		return $updates;
	}

	/**
	 * Write the checked values to the object in memory with the update_callback
	 * of each field.
	 *
	 * @param \WC_Data $subject Object to change.
	 * @param array    $updates Pairs of field arguments and value from prepare_fields().
	 * @return \WP_Error|null A WP_Error when the field does not apply to the object, or a callback rejects a value or throws.
	 */
	private function apply_fields( $subject, array $updates ): ?\WP_Error {
		foreach ( $updates as $param => list( $args, $value ) ) {
			if ( ! ExtensibleAbilities::field_applies_to( $args, $subject ) ) {
				return new \WP_Error(
					'woocommerce_ability_field_object_type',
					sprintf(
						/* translators: 1: extension field, 2: object type, 3: comma-separated list of object types. */
						__( 'The %1$s field does not apply to the %2$s type. It applies to %3$s.', 'woocommerce' ),
						$param,
						ExtensibleAbilities::get_object_type( $subject ) ?? 'unknown',
						implode( ', ', $args['object_types'] )
					),
					array( 'status' => 400 )
				);
			}
			$rejected = self::run_callback( $args['update_callback'], array( $subject, $value ), array( 'field' => $param ) );
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
		foreach ( ExtensibleAbilities::get_validators( $this->get_resource() ) as $args ) {
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
		} catch ( \WC_Data_Exception $e ) {
			$result = new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( \Throwable $e ) {
			wc_get_logger()->error(
				sprintf( 'Extension callback "%s" failed: %s', implode( '', $data ), $e->getMessage() ),
				array( 'source' => 'extensible-abilities' )
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
	 * Add the `extensions` property one time, at the top level of the input
	 * schema. Each `oneOf`, `anyOf` and `allOf` branch only accepts the key, so
	 * a branch with `additionalProperties` set to false does not reject it, and
	 * the schema does not grow with the number of branches.
	 *
	 * @param array<string, mixed> $schema Input schema.
	 * @return array<string, mixed>
	 */
	private function add_extensions_input_schema( array $schema ): array {
		$schema['properties']['extensions'] = ExtensibleAbilities::get_input_schema( $this->get_resource() );
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
