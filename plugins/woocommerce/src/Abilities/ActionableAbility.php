<?php
/**
 * Actionable ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

use Automattic\WooCommerce\Internal\Abilities\Ability;
use Automattic\WooCommerce\Internal\Caches\ProductCache;
use Automattic\WooCommerce\Internal\Utilities\DatabaseTransaction;

defined( 'ABSPATH' ) || exit;

/**
 * An ability that changes a primary object, and optionally secondary objects,
 * and saves them in one call. Register it with a subclass as the
 * `ability_class`. The execute callback loads the primary object, checks the
 * `extensions` input against the field input schemas, changes the objects in memory,
 * writes the fields with their `set_callback`, runs the validators that
 * ExtensibleAbilities::register_validator() adds, saves, and then prepares the
 * response. A rejection before the save saves nothing. When prepare_response()
 * returns a WP_Error, the change is already saved, so the error message names
 * the saved object, and the error data gets `saved` set to true and the `id`
 * of the object.
 *
 * The input schema gets the `extensions` property of the resource, so the
 * ability accepts the fields with no extra code.
 *
 * With the feature off, the input schema gets no `extensions` property and the
 * input is ignored. An ability whose own input schema sets
 * `additionalProperties` to false rejects it instead.
 *
 * The object that load() returns is the primary object. An ability that saves
 * more objects returns them from change(). These are the secondary objects. The field
 * writes of every object run first, then the validators of each object's own
 * resource, before the first save. The objects that get_objects_to_delete() lists get the
 * validators too.
 *
 * Only save() saves, inside a database transaction. A WP_Error or an exception
 * from save() rolls back every row it wrote, so an ability that saves secondary
 * objects leaves nothing behind when a later save fails. After the last save,
 * still inside the transaction, the syncs that ExtensibleAbilities::register_sync()
 * adds write what the saved objects imply elsewhere, such as the price range a
 * variable parent derives from its variations, or a row in a table of the
 * extension. A sync also sees the objects the call deleted and the input of
 * the call. A refusal there rolls the call back too. When a query hits a
 * deadlock, a lock wait timeout or a snapshot conflict with a parallel call, the call rolls
 * back and fails with a WP_Error instead of reporting the write. A rollback drops the
 * object caches of the objects the call saved or deleted. It does not undo what a sync
 * did outside the database, such as an HTTP request, and it does nothing on a
 * table engine without transactions. The other steps must not save, send email
 * or make HTTP requests, except after_commit(), which runs after the commit and
 * before prepare_response().
 *
 * When is_dry_run() is true for the input, the call stops after the validators,
 * saves nothing and returns prepare_preview().
 *
 * The API is experimental while the `extensible_abilities` feature exists.
 *
 * @since 11.3.0
 */
abstract class ActionableAbility extends Ability {

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
	 * only the primary object returns null. The resource must be registered,
	 * otherwise the call is refused with `woocommerce_ability_resource_unknown`
	 * and nothing is saved.
	 *
	 * @since 11.3.0
	 *
	 * @param \WC_Data $subject Primary object to change.
	 * @param array    $input   Ability input.
	 * @return null|\WP_Error|array<int, array{0: \WC_Data, 1: array, 2?: string}> A WP_Error rejects the change, and nothing is saved. The field writes and the validators of the primary object still run, so the client gets their refusals in the same error. An array lists the secondary objects.
	 */
	abstract protected function change( $subject, array $input );

	/**
	 * The ability output for the saved object. It must match the output schema
	 * of the ability.
	 *
	 * @since 11.3.0
	 *
	 * @param \WC_Data $subject Saved object.
	 * @return mixed|\WP_Error A WP_Error fails the call, but the change stays saved.
	 */
	abstract protected function prepare_response( $subject );

	/**
	 * Save the changed object. The default calls WC_Data::save(). An order's
	 * save() catches a failed write, so the default reads that error from
	 * WC_Abstract_Order::get_last_save_error(). When the save fails or leaves
	 * the object without an ID, it logs the error and returns a WP_Error with a
	 * fixed message, so the exception text stays out of the response.
	 *
	 * @since 11.3.0
	 *
	 * @param \WC_Data $subject Changed object.
	 * @return null|\WP_Error Null when the object was saved, a WP_Error when it was not.
	 */
	protected function save( $subject ) {
		try {
			$subject->save();
			$error = $subject instanceof \WC_Abstract_Order ? $subject->get_last_save_error() : null;
		} catch ( \Exception $e ) {
			$error = $e;
		}

		if ( null !== $error ) {
			wc_get_logger()->error(
				sprintf( 'Ability "%s" failed to save: %s', $this->get_name(), $error->getMessage() ),
				array( 'source' => 'woocommerce-abilities' )
			);
			return self::save_failed();
		}

		return $subject->get_id() > 0 ? null : self::save_failed();
	}

	/**
	 * Whether the call only checks the change. A dry run runs every step up to
	 * the validators, saves nothing, and returns prepare_preview().
	 *
	 * @since 11.4.0
	 *
	 * @param array $input Ability input.
	 * @return bool
	 */
	protected function is_dry_run( array $input ): bool {
		return false;
	}

	/**
	 * The ability output for a dry run. The object is changed in memory and was
	 * never saved, so it can have no ID.
	 *
	 * @since 11.4.0
	 *
	 * @param \WC_Data $subject Changed object, not saved.
	 * @param array    $input   Ability input.
	 * @return mixed|\WP_Error
	 */
	protected function prepare_preview( $subject, array $input ) {
		return $this->prepare_response( $subject );
	}

	/**
	 * Work that a rollback could not undo, such as a request to a payment
	 * gateway. It runs after the commit, so a failure here leaves the change
	 * saved unless this method undoes it. Inside a transaction that a caller
	 * opened, the caller's commit still decides whether the rows stay.
	 *
	 * @since 11.4.0
	 *
	 * @param \WC_Data $subject Saved object.
	 * @param array    $input   Ability input.
	 * @return null|\WP_Error A WP_Error fails the call as it is.
	 */
	protected function after_commit( $subject, array $input ): ?\WP_Error {
		return null;
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
	 * The objects that save() will delete or trash, for the validators. Each one
	 * goes through the validators of the resource before the first save, with
	 * `deleting` in the context, also when no other object of the call has it.
	 * Its value is read after change().
	 *
	 * @since 11.4.0
	 *
	 * @return \WC_Data[]
	 */
	protected function get_objects_to_delete(): array {
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
		$updates = $writes ? $this->prepare_fields( $input['extensions'] ?? array(), $this->get_resource() ) : array();
		if ( is_wp_error( $updates ) ) {
			return $updates;
		}

		$refused = new \WP_Error();
		try {
			$changed = $this->change( $subject, $input );
		} catch ( \WC_Data_Exception $e ) {
			$changed = new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		}
		$core_refused = is_wp_error( $changed );
		if ( $core_refused ) {
			$refused->merge_from( $changed );
			$changed = null;
		}

		$objects = array( array( $subject, $updates, $this->get_resource() ) );
		foreach ( (array) $changed as $secondary ) {
			$resource_name = $secondary[2] ?? $this->get_resource();
			if ( $enabled && ! ExtensibleAbilities::is_resource_registered( $resource_name ) ) {
				wc_doing_it_wrong(
					get_class( $this ) . '::change',
					sprintf(
						'The "%s" resource of a secondary object is not registered, so the call is refused. Call ExtensibleAbilities::register_resource() first.',
						$resource_name
					),
					'11.4.0'
				);
				return new \WP_Error(
					'woocommerce_ability_resource_unknown',
					sprintf(
						/* translators: 1: resource name, 2: ability name. */
						__( 'The "%1$s" resource is not registered, so the "%2$s" ability cannot save the change.', 'woocommerce' ),
						$resource_name,
						$this->get_name()
					),
					array(
						'status'   => 500,
						'resource' => $resource_name,
						'ability'  => $this->get_name(),
					)
				);
			}
			$fields = $writes ? $this->prepare_fields( $secondary[1], $resource_name ) : array();
			if ( is_wp_error( $fields ) ) {
				return $fields;
			}
			$objects[] = array( $secondary[0], $fields, $resource_name );
		}

		foreach ( $objects as list( $object, $fields ) ) {
			$this->apply_fields( $object, $fields, $refused );
		}

		$deleting = $core_refused ? array() : $this->get_objects_to_delete();
		$staged   = array_column( $objects, 0 );
		$to_check = $objects;
		foreach ( $deleting as $object ) {
			if ( ! in_array( $object, $staged, true ) ) {
				$to_check[] = array( $object, array(), $this->get_resource() );
			}
		}
		foreach ( $enabled ? $to_check : array() as list( $object, , $resource_name ) ) {
			$this->validate(
				$object,
				$resource_name,
				array(
					'primary'           => $subject,
					'deleting'          => in_array( $object, $deleting, true ),
					'objects'           => $staged,
					'objects_to_delete' => $deleting,
				),
				$refused
			);
		}
		if ( $core_refused || $refused->has_errors() ) {
			return self::join_refusals( $refused );
		}

		if ( $this->is_dry_run( $input ) ) {
			return $this->prepare_preview( $subject, $input );
		}

		try {
			$saved = DatabaseTransaction::run(
				function () use ( $subject, $objects, $input, $enabled ) {
					$saved = $this->save( $subject );
					return ! is_wp_error( $saved ) && $enabled ? $this->sync( $objects, $input ) : $saved;
				}
			);
		} catch ( \Throwable $e ) {
			self::forget_rolled_back_objects( $objects, array_merge( $this->get_deleted_objects(), $this->get_trashed_objects() ) );
			throw $e;
		}
		if ( is_wp_error( $saved ) ) {
			self::forget_rolled_back_objects( $objects, array_merge( $this->get_deleted_objects(), $this->get_trashed_objects() ) );
			return $saved;
		}

		$finished = $this->after_commit( $subject, $input );
		if ( is_wp_error( $finished ) ) {
			return $finished;
		}

		$response = $this->prepare_response( $subject );
		if ( is_wp_error( $response ) ) {
			return ExtensibleAbilities::saved_write_error( $response, $this->get_resource(), $subject->get_id() );
		}

		return $response;
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
	 * same request would still see them. The `WC_Data` meta cache and the
	 * product instance cache are separate from the post cache.
	 *
	 * @param array<int, array{0: \WC_Data, 1: array, 2: string}> $objects The primary object and the secondary objects.
	 * @param \WC_Data[]                                          $deleted The objects the call deleted.
	 */
	private static function forget_rolled_back_objects( array $objects, array $deleted = array() ): void {
		$product_cache = wc_get_container()->get( ProductCache::class );
		foreach ( array_merge( $objects, array_map( static fn( $item ) => array( $item ), $deleted ) ) as list( $object ) ) {
			$id = $object->get_id();
			if ( $id > 0 ) {
				$object->delete_meta_cache();
			}
			if ( $id > 0 && $object instanceof \WC_Product ) {
				clean_post_cache( $id );
				wc_delete_product_transients( $id );
				$product_cache->remove( $id );
				if ( $object->get_parent_id() > 0 ) {
					clean_post_cache( $object->get_parent_id() );
					wc_delete_product_transients( $object->get_parent_id() );
					$product_cache->remove( $object->get_parent_id() );
				}
			} elseif ( $id > 0 && $object instanceof \WC_Abstract_Order ) {
				wc_get_container()->get( \Automattic\WooCommerce\Caches\OrderCache::class )->remove( $id );
				clean_post_cache( $id );
			}
		}
	}

	/**
	 * Check the input schema. When it refuses the input, name every bad extension
	 * field in one message, because an MCP client gets only the first message of an
	 * error. A failure with no bad extension field keeps the error of the schema.
	 *
	 * @since 11.4.0
	 *
	 * @param mixed $input Ability input.
	 * @return true|\WP_Error
	 */
	public function validate_input( $input = null ) {
		$result = parent::validate_input( $input );
		if ( ! is_wp_error( $result ) || ! is_array( $input ) ) {
			return $result;
		}
		$refusals = $this->get_field_refusals( $input );
		if ( empty( $refusals ) ) {
			return $result;
		}
		return new \WP_Error( 'woocommerce_ability_invalid_fields', implode( ' ', $refusals ), array( 'status' => 400 ) );
	}

	/**
	 * Say what is wrong with each bad extension field of the input. An ability
	 * whose input has more `extensions`, such as one per item, adds those here.
	 *
	 * @since 11.4.0
	 *
	 * @param array $input Ability input that the schema refused.
	 * @return string[]
	 */
	protected function get_field_refusals( array $input ): array {
		if ( ! isset( $this->get_input_schema()['properties']['extensions'] ) ) {
			return array();
		}
		return self::describe_bad_fields( $input['extensions'] ?? null, $this->get_resource() );
	}

	/**
	 * Say what is wrong with each unknown namespace, unknown field, read-only field
	 * and bad value. A value of the wrong shape is left to the input schema.
	 *
	 * @since 11.4.0
	 *
	 * @param mixed         $values        Values keyed by namespace, then field.
	 * @param string        $resource_name Resource of the object that gets the values.
	 * @param callable|null $field_filter  Optional. Gets the arguments of a field and returns whether the object can take it. A field it refuses counts as unknown.
	 * @return string[]
	 */
	protected static function describe_bad_fields( $values, string $resource_name, ?callable $field_filter = null ): array {
		$registered = ExtensibleAbilities::get_fields( $resource_name );
		$refusals   = array();
		foreach ( is_array( $values ) ? $values : array() as $namespace => $fields ) {
			if ( ! isset( $registered[ $namespace ] ) ) {
				/* translators: %s: Extension namespace. */
				$refusals[] = sprintf( __( 'No extension registered the namespace "%s" for this ability. Remove it from the input, or use a namespace from the input schema.', 'woocommerce' ), $namespace );
				continue;
			}
			$candidates = null === $field_filter ? $registered[ $namespace ] : array_filter( $registered[ $namespace ], $field_filter );
			foreach ( is_array( $fields ) ? $fields : array() as $field => $value ) {
				$args = $candidates[ $field ] ?? null;
				if ( null === $args ) {
					$refusals[] = self::describe_unknown_field( $candidates, (string) $namespace, (string) $field );
				} elseif ( ! isset( $args['set_callback'] ) ) {
					/* translators: 1: Extension namespace. 2: Field name. */
					$refusals[] = sprintf( __( 'The extension field "%1$s.%2$s" is read-only. Remove it from the input.', 'woocommerce' ), $namespace, $field );
				} else {
					$param = 'extensions.' . $namespace . '.' . $field;
					$valid = rest_validate_value_from_schema( $value, $args['input_schema'], $param );
					if ( is_wp_error( $valid ) ) {
						$refusals[] = ( isset( $args['input_schema']['anyOf'] ) ? self::describe_any_of_refusal( $value, $args['input_schema']['anyOf'], $param ) : null ) ?? $valid->get_error_message();
					}
				}
			}
		}
		return $refusals;
	}

	/**
	 * Name the types an `anyOf` field takes when the value matches none of them, because the
	 * WordPress message then names no type. Other refusals keep the WordPress message.
	 *
	 * @param mixed                            $value    Value from the input.
	 * @param array<int, array<string, mixed>> $branches The `anyOf` branches.
	 * @param string                           $param    Path of the field in the input.
	 * @return string|null The refusal, or null to keep the WordPress message.
	 */
	private static function describe_any_of_refusal( $value, array $branches, string $param ): ?string {
		$types = array_column( $branches, 'type' );
		if ( count( array_filter( $types, 'is_string' ) ) !== count( $branches ) || '' !== rest_get_best_type_for_value( $value, $types ) ) {
			return null;
		}
		/* translators: 1: Path of the field in the input. 2: Comma-separated list of JSON types. */
		return sprintf( __( '%1$s is not of type %2$s.', 'woocommerce' ), $param, implode( ',', array_unique( $types ) ) );
	}

	/**
	 * Say that a namespace has no such field, and list the fields that the agent can write.
	 *
	 * @param array<string, array> $fields              Fields of the namespace that the object can take.
	 * @param string               $extension_namespace Extension namespace from the input.
	 * @param string               $field               Field name from the input.
	 * @return string
	 */
	private static function describe_unknown_field( array $fields, string $extension_namespace, string $field ): string {
		$writable = array_keys( array_filter( $fields, static fn( array $args ): bool => isset( $args['set_callback'] ) ) );
		if ( empty( $writable ) ) {
			/* translators: 1: Extension namespace. 2: Field name. */
			return sprintf( __( 'The extension namespace "%1$s" has no field "%2$s", and it has no fields you can write. Remove "%1$s.%2$s" from the input.', 'woocommerce' ), $extension_namespace, $field );
		}
		/* translators: 1: Extension namespace. 2: Field name. 3: Comma-separated list of field names. */
		return sprintf( __( 'The extension namespace "%1$s" has no field "%2$s". The fields you can write in this namespace are: %3$s. Use one of them, or remove "%1$s.%2$s" from the input.', 'woocommerce' ), $extension_namespace, $field, implode( ', ', $writable ) );
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
	 * Check `extensions.<namespace>.<field>` input values against the input
	 * schema of each field, and cast them. No callback runs here. A null value
	 * passes only where the input schema declares null.
	 *
	 * @param array  $values        Values keyed by namespace, then field.
	 * @param string $resource_name Resource of the object that gets the values.
	 * @return array|\WP_Error A list of field arguments and value pairs, or a WP_Error when a value is rejected.
	 */
	private function prepare_fields( array $values, string $resource_name ) {
		$registered = ExtensibleAbilities::get_fields( $resource_name );
		$updates    = array();
		foreach ( $values as $namespace => $fields ) {
			foreach ( (array) $fields as $field => $value ) {
				$args = $registered[ $namespace ][ $field ] ?? array();
				if ( ! isset( $args['set_callback'] ) ) {
					return new \WP_Error(
						'woocommerce_ability_field_not_writable',
						/* translators: 1: Extension namespace. 2: Field name. */
						sprintf( __( 'The extension field "%1$s.%2$s" cannot be written.', 'woocommerce' ), $namespace, $field ),
						array( 'status' => 400 )
					);
				}
				$param = 'extensions.' . $namespace . '.' . $field;
				$valid = rest_validate_value_from_schema( $value, $args['input_schema'], $param );
				if ( is_wp_error( $valid ) ) {
					return self::with_data( $valid, array( 'field' => $param ) );
				}
				$value = rest_sanitize_value_from_schema( $value, $args['input_schema'], $param );
				if ( is_wp_error( $value ) ) {
					return self::with_data( $value, array( 'field' => $param ) );
				}
				$updates[ $param ] = array( $args, $value );
			}
		}
		return $updates;
	}

	/**
	 * Write the checked values to the object in memory with the set_callback
	 * of each field, also after one rejects its value. Each rejection goes into
	 * the given errors, as add_refusal() describes.
	 *
	 * @param \WC_Data  $subject Object to change.
	 * @param array     $updates Pairs of field arguments and value from prepare_fields().
	 * @param \WP_Error $refused Collects the refusals, including a field that does not apply to the object.
	 */
	private function apply_fields( $subject, array $updates, \WP_Error $refused ): void {
		foreach ( $updates as $param => list( $args, $value ) ) {
			if ( ! ExtensibleAbilities::field_applies_to( $args, $subject ) ) {
				$rejected = new \WP_Error(
					'woocommerce_ability_field_object_type',
					sprintf(
						/* translators: 1: extension field, 2: object type, 3: comma-separated list of object types. */
						__( 'The %1$s field does not apply to the %2$s type. It applies to %3$s.', 'woocommerce' ),
						$param,
						ExtensibleAbilities::get_object_type( $subject ),
						implode( ', ', $args['object_types'] )
					),
					array( 'status' => 400 )
				);
			} else {
				$rejected = self::run_callback( $args['set_callback'], array( $subject, $value ), array( 'field' => $param ) );
			}
			if ( null !== $rejected ) {
				self::add_refusal( $rejected, explode( '.', $param )[1], $refused );
			}
		}
	}

	/**
	 * Run every validator of a resource on the changed object, also after a
	 * field or another validator refuses.
	 *
	 * @param \WC_Data  $subject       Changed object.
	 * @param string    $resource_name Resource of the object.
	 * @param array     $context       The primary object of the call, whether the call deletes or trashes the object, the objects the call saves, and the objects it deletes or trashes.
	 * @param \WP_Error $refused       Collects the refusals.
	 */
	private function validate( $subject, string $resource_name, array $context, \WP_Error $refused ): void {
		foreach ( ExtensibleAbilities::get_validators( $resource_name ) as $args ) {
			$rejected = self::run_callback( $args['callback'], array( $subject, $this, $context ), array( 'namespace' => $args['namespace'] ) );
			if ( null !== $rejected ) {
				self::add_refusal( $rejected, $args['namespace'], $refused );
			}
		}
	}

	/**
	 * Add the codes of a rejection to the errors of the call. Each code becomes
	 * `<namespace>:<code>`, so refusals with the same code keep their own data.
	 * When the code is already taken in the same namespace, it gets a numeric
	 * suffix, as `<namespace>:<code>:2`. The errors keep the run order, so the
	 * first rejection gives the response its code and status, and
	 * join_refusals() puts every message into the first one.
	 *
	 * @param \WP_Error $rejected  Rejection from run_callback().
	 * @param string    $extension Extension that owns the callback.
	 * @param \WP_Error $refused   Collects the refusals.
	 */
	private static function add_refusal( \WP_Error $rejected, string $extension, \WP_Error $refused ): void {
		foreach ( $rejected->get_error_codes() as $code ) {
			$key = $extension . ':' . $code;
			$n   = 1;
			while ( in_array( $key, $refused->get_error_codes(), true ) ) {
				$key = $extension . ':' . $code . ':' . ( ++$n );
			}
			foreach ( $rejected->get_error_messages( $code ) as $message ) {
				$refused->add( $key, $message );
			}
			$refused->add_data( array_merge( $rejected->get_error_data( $code ), array( 'namespace' => $extension ) ), $key );
		}
	}

	/**
	 * Put every refusal message into the first message, once each, because an
	 * MCP client shows only the first message of an error. The other codes and
	 * messages stay as they are for the clients that read all of them.
	 *
	 * @param \WP_Error $refused Refusals in run order.
	 * @return \WP_Error
	 */
	private static function join_refusals( \WP_Error $refused ): \WP_Error {
		$messages = $refused->get_error_messages();
		if ( count( $messages ) > 1 ) {
			$refused->errors[ $refused->get_error_code() ][0] = implode( ' ', array_unique( $messages ) );
		}

		return $refused;
	}

	/**
	 * Run an extension callback. A WP_Error that it returns, or an exception
	 * that it throws, becomes an error with the given data. An exception is
	 * logged and becomes a 500 with a fixed message, so its text never reaches
	 * the client.
	 *
	 * @param callable             $callback Field set_callback, validator or sync.
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
	 * Add data to every code of an error, and the 400 status to a code that has
	 * none. It replaces the data of the code, because WP_Error::add_data() would
	 * also keep the old data, and the client would see it in `additional_data`.
	 *
	 * @param \WP_Error            $error Error.
	 * @param array<string, mixed> $data  Data to add.
	 * @return \WP_Error
	 */
	private static function with_data( \WP_Error $error, array $data ): \WP_Error {
		foreach ( $error->get_error_codes() as $code ) {
			$current                    = $error->get_error_data( $code );
			$error->error_data[ $code ] = array_merge( array( 'status' => 400 ), is_array( $current ) ? $current : array(), $data );
		}
		return $error;
	}
}
