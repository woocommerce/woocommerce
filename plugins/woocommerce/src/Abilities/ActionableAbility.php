<?php
/**
 * Actionable ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * An ability that changes a primary object, and optionally secondary objects,
 * and saves them in one call. Register it with a subclass as the
 * `ability_class`. The execute callback loads the primary object, checks the
 * `extensions` input against the field schemas, changes the objects in memory,
 * writes the fields with their `update_callback`, runs the validators that
 * ExtensibleAbilities::register_validator() adds, and then saves. A rejection
 * from any step saves nothing.
 *
 * The input schema gets the `extensions` property of the resource, so the
 * ability accepts the fields with no extra code.
 *
 * With the feature off, the input schema gets no `extensions` property and the
 * input is ignored. An ability whose own input schema sets
 * `additionalProperties` to false rejects it instead.
 *
 * The steps are protected, so only execute_change() runs them.
 *
 * The object that load() returns is the primary object. An ability that saves
 * more objects returns them from change(). These are the secondary objects. Each
 * object gets the field writes and the validators of its own resource before
 * the first save.
 *
 * Only save() saves, inside a database transaction. A WP_Error or an exception
 * from save() rolls back every row it wrote, so an ability that saves secondary
 * objects leaves nothing behind when a later save fails. After the last save,
 * still inside the transaction, the syncs that ExtensibleAbilities::register_sync()
 * adds write what the saved objects imply elsewhere, such as the price range a
 * variable parent derives from its variations, or a row in a table of the
 * extension. A sync also sees the objects the call deleted and the input of
 * the call. A refusal there rolls the call back too. A rollback drops the
 * object caches of the objects the call saved or deleted. It does not undo what a sync
 * did outside the database, such as an HTTP request, and it does nothing on a
 * table engine without transactions. The other steps must not save, send email
 * or make HTTP requests.
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
	abstract protected function load( array $input );

	/**
	 * Change the primary object in memory, and build the secondary objects that
	 * save() also writes. Return the secondary objects, each with its
	 * `extensions` input and, when it differs from get_resource(), the name of
	 * its resource. The field writes and the validators of that resource run on
	 * each one after the primary object, before save(). An ability that saves
	 * only the primary object returns null.
	 *
	 * @since 11.3.0
	 *
	 * @param \WC_Data $subject Primary object to change.
	 * @param array    $input   Ability input.
	 * @return null|\WP_Error|array<int, array{0: \WC_Data, 1: array, 2?: string}> A WP_Error rejects the change, and nothing is saved. An array lists the secondary objects.
	 */
	abstract protected function change( $subject, array $input );

	/**
	 * The ability output for the saved object. It must match the output schema
	 * of the ability.
	 *
	 * @since 11.3.0
	 *
	 * @param \WC_Data $subject Saved object.
	 * @return mixed
	 */
	abstract protected function prepare_response( $subject );

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
	protected function save( $subject ) {
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
	 * Whether the ability writes the extension fields of its resource. An
	 * ability that does not, such as a delete, gets no `extensions` input.
	 *
	 * @since 11.4.0
	 *
	 * @return bool
	 */
	protected function writes_extension_fields(): bool {
		return true;
	}

	/**
	 * The objects that save() deleted for good, for the syncs. An ability that
	 * deletes objects collects them in save() and returns them here.
	 *
	 * @since 11.4.0
	 *
	 * @return \WC_Data[]
	 */
	protected function get_deleted_objects(): array {
		return array();
	}

	/**
	 * The objects that save() moved to the trash, for the syncs. A trashed
	 * object can be restored, so a sync keeps what a restore needs.
	 *
	 * @since 11.4.0
	 *
	 * @return \WC_Data[]
	 */
	protected function get_trashed_objects(): array {
		return array();
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
		$writes  = $enabled && $this->writes_extension_fields();
		$updates = $writes ? $this->prepare_fields( $input['extensions'] ?? null, $this->get_resource() ) : array();
		if ( is_wp_error( $updates ) ) {
			return $updates;
		}

		$changed = $this->change( $subject, $input );
		if ( is_wp_error( $changed ) ) {
			return $changed;
		}

		$objects = array( array( $subject, $updates, $this->get_resource() ) );
		foreach ( is_array( $changed ) ? $changed : array() as $secondary ) {
			$resource_name = $secondary[2] ?? $this->get_resource();
			if ( $enabled && ! ExtensibleAbilities::is_resource_registered( $resource_name ) ) {
				wc_doing_it_wrong(
					get_class( $this ) . '::change',
					sprintf(
						/* translators: %s: resource name. */
						__( 'The "%s" resource of a secondary object is not registered, so no extension field or validator runs on it. Call ExtensibleAbilities::register_resource() first.', 'woocommerce' ),
						$resource_name
					),
					'11.4.0'
				);
			}
			$fields = $writes ? $this->prepare_fields( $secondary[1], $resource_name ) : array();
			if ( is_wp_error( $fields ) ) {
				return $fields;
			}
			$objects[] = array( $secondary[0], $fields, $resource_name );
		}

		foreach ( $objects as list( $object, $fields, $resource_name ) ) {
			$rejected = $this->apply_fields( $object, $fields );
			if ( null === $rejected && $enabled ) {
				$rejected = $this->validate( $object, $resource_name );
			}
			if ( null !== $rejected ) {
				return $rejected;
			}
		}

		wc_transaction_query( 'start' );
		try {
			$saved = $this->save( $subject );
			if ( ! is_wp_error( $saved ) && $enabled ) {
				$saved = $this->sync( $objects, $input );
			}
		} catch ( \Throwable $e ) {
			wc_transaction_query( 'rollback' );
			self::forget_rolled_back_objects( $objects, array_merge( $this->get_deleted_objects(), $this->get_trashed_objects() ) );
			throw $e;
		}
		if ( is_wp_error( $saved ) ) {
			wc_transaction_query( 'rollback' );
			self::forget_rolled_back_objects( $objects, array_merge( $this->get_deleted_objects(), $this->get_trashed_objects() ) );
			return $saved;
		}
		wc_transaction_query( 'commit' );

		return $this->prepare_response( $subject );
	}

	/**
	 * Run the syncs of each resource that the call saved or deleted, once,
	 * after the last save and before the commit. A deleted or trashed object
	 * is not in the saved list, even when it was the primary object.
	 *
	 * @param array<int, array{0: \WC_Data, 1: array, 2: string}> $objects The primary object and the secondary objects, with their resources.
	 * @param array                                               $input   Ability input.
	 * @return \WP_Error|null A WP_Error when a sync refused, and then the call rolls back.
	 */
	private function sync( array $objects, array $input ): ?\WP_Error {
		$deleted   = $this->get_deleted_objects();
		$trashed   = $this->get_trashed_objects();
		$gone      = array_map( static fn( $item ) => get_class( $item ) . ':' . $item->get_id(), array_merge( $deleted, $trashed ) );
		$saved     = array_values(
			array_filter(
				array_column( $objects, 0 ),
				static fn( $item ) => $item->get_id() > 0 && ! in_array( get_class( $item ) . ':' . $item->get_id(), $gone, true )
			)
		);
		$resources = array_column( $objects, 2 );
		if ( ! empty( $deleted ) || ! empty( $trashed ) ) {
			$resources[] = $this->get_resource();
		}
		$context = array(
			'deleted' => array_values( $deleted ),
			'trashed' => array_values( $trashed ),
			'input'   => $input,
		);
		foreach ( array_unique( $resources ) as $resource_name ) {
			foreach ( ExtensibleAbilities::get_syncs( $resource_name ) as $args ) {
				$rejected = self::run_callback( $args['callback'], array( $saved, $this, $context ), array( 'namespace' => $args['namespace'] ) );
				if ( null !== $rejected ) {
					return $rejected;
				}
			}
		}
		return null;
	}

	/**
	 * Drop the object caches of the objects a rolled back save() touched. The
	 * saves filled the caches with rows the rollback removed, so a read in the
	 * same request would still see them.
	 *
	 * @param array<int, array{0: \WC_Data, 1: array, 2: string}> $objects The primary object and the secondary objects.
	 * @param \WC_Data[]                                          $deleted The objects the call deleted.
	 */
	private static function forget_rolled_back_objects( array $objects, array $deleted = array() ): void {
		foreach ( array_merge( $objects, array_map( static fn( $item ) => array( $item ), $deleted ) ) as list( $object ) ) {
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
		if ( is_array( $args['input_schema'] ?? null ) && ExtensibleAbilities::is_enabled() && $this->writes_extension_fields() ) {
			$args['input_schema'] = $this->add_extensions_input_schema( $args['input_schema'] );
		}
		return parent::prepare_properties( $args );
	}

	/**
	 * Check `extensions.<namespace>.<field>` input values against the schema of
	 * each field, and cast them. No callback runs here. A null value is kept as
	 * is, to delete the value.
	 *
	 * @param mixed  $values        Values keyed by namespace, then field. Anything but an array is ignored.
	 * @param string $resource_name Resource of the object that gets the values.
	 * @return array|\WP_Error A list of field arguments and value pairs, or a WP_Error when a value is rejected.
	 */
	private function prepare_fields( $values, string $resource_name ) {
		$registered = ExtensibleAbilities::get_fields( $resource_name );
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
	 * The error for a field that does not apply to the type of an object.
	 *
	 * @param string   $param        Field, as `extensions.<namespace>.<field>`.
	 * @param string   $object_type  Type of the object.
	 * @param string[] $object_types Types that the field applies to.
	 * @return \WP_Error
	 */
	protected static function field_object_type_refusal( string $param, string $object_type, array $object_types ): \WP_Error {
		return new \WP_Error(
			'woocommerce_ability_field_object_type',
			sprintf(
				/* translators: 1: extension field, 2: object type, 3: comma-separated list of object types. */
				__( 'The %1$s field does not apply to the %2$s type. It applies to %3$s.', 'woocommerce' ),
				$param,
				$object_type,
				implode( ', ', $object_types )
			),
			array( 'status' => 400 )
		);
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
				return self::field_object_type_refusal( $param, ExtensibleAbilities::get_object_type( $subject ) ?? 'unknown', $args['object_types'] );
			}
			$rejected = self::run_callback( $args['update_callback'], array( $subject, $value ), array( 'field' => $param ) );
			if ( null !== $rejected ) {
				return $rejected;
			}
		}
		return null;
	}

	/**
	 * Run every validator of a resource on the changed object, also after one
	 * refuses. Each refusal gets the code `<namespace>:<code>`, so refusals with
	 * the same code keep their own data. When the code is already taken in the
	 * same namespace, by a refusal or by a crash, it gets a numeric suffix, as
	 * `<namespace>:<code>:2`. A
	 * validator that throws goes after the refusals, so the response status
	 * comes from a refusal when there is one.
	 *
	 * @param \WC_Data $subject       Changed object.
	 * @param string   $resource_name Resource of the object.
	 * @return \WP_Error|null One WP_Error with the refusals of all validators that refuse the change.
	 */
	private function validate( $subject, string $resource_name ): ?\WP_Error {
		$refused = new \WP_Error();
		$failed  = new \WP_Error();
		foreach ( ExtensibleAbilities::get_validators( $resource_name ) as $args ) {
			$rejected = self::run_callback( $args['callback'], array( $subject, $this ), array( 'namespace' => $args['namespace'] ) );
			if ( null === $rejected ) {
				continue;
			}
			foreach ( $rejected->get_error_codes() as $code ) {
				$data   = $rejected->get_error_data( $code );
				$target = 500 === ( $data['status'] ?? null ) ? $failed : $refused;
				$key    = $args['namespace'] . ':' . $code;
				$n      = 1;
				while ( in_array( $key, array_merge( $refused->get_error_codes(), $failed->get_error_codes() ), true ) ) {
					$key = $args['namespace'] . ':' . $code . ':' . ( ++$n );
				}
				foreach ( $rejected->get_error_messages( $code ) as $message ) {
					$target->add( $key, $message, $data );
				}
			}
		}
		$refused->merge_from( $failed );
		return $refused->has_errors() ? $refused : null;
	}

	/**
	 * Run an extension callback. A WP_Error that it returns, or an exception
	 * that it throws, becomes an error with the given data. An exception is
	 * logged and becomes a 500 with a fixed message, so its text never reaches
	 * the client.
	 *
	 * @param callable             $callback Field update_callback, validator or sync.
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
	 * Add data to every code of an error, and the 400 status to a code that has none.
	 *
	 * @param \WP_Error            $error Error.
	 * @param array<string, mixed> $data  Data to add.
	 * @return \WP_Error
	 */
	private static function with_data( \WP_Error $error, array $data ): \WP_Error {
		foreach ( $error->get_error_codes() as $code ) {
			$current = $error->get_error_data( $code );
			$error->add_data( array_merge( array( 'status' => 400 ), is_array( $current ) ? $current : array(), $data ), $code );
		}
		return $error;
	}
}
