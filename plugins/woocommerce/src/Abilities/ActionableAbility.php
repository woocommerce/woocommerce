<?php
/**
 * Actionable ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

use Automattic\WooCommerce\Internal\AbilitiesApi\AbilityContracts;

defined( 'ABSPATH' ) || exit;

/**
 * An ability that changes one object and saves it one time. Register it with
 * a subclass as the `ability_class`. The execute callback loads the object,
 * changes it in memory, writes the `extensions` input with the fields that
 * AbilityExtensions::register_field() adds, runs the validators that
 * AbilityExtensions::register_validator() adds, and then saves it. A rejection
 * from any step saves nothing.
 *
 * The input schema gets the `extensions` property of the resource, so the
 * ability accepts the fields with no extra code.
 *
 * Only save() saves. The other steps must not save, send email or make HTTP
 * requests.
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
	 */
	public function execute_change( $input = null ) {
		$input   = is_array( $input ) ? $input : array();
		$subject = $this->load( $input );
		if ( is_wp_error( $subject ) ) {
			return $subject;
		}

		$changed = $this->change( $subject, $input );
		if ( is_wp_error( $changed ) ) {
			return $changed;
		}

		if ( AbilityContracts::is_enabled() ) {
			$extensions = is_array( $input['extensions'] ?? null ) ? $input['extensions'] : array();
			$rejected   = $this->update_fields( $subject, $extensions ) ?? $this->validate( $subject );
			if ( null !== $rejected ) {
				return $rejected;
			}
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
		if ( is_array( $args['input_schema'] ?? null ) && AbilityContracts::is_enabled() ) {
			$args['input_schema'] = $this->add_extensions_input_schema( $args['input_schema'] );
		}
		return parent::prepare_properties( $args );
	}

	/**
	 * Write `extensions.<namespace>.<field>` input values to the object in
	 * memory with the update_callback of each field. Every value is checked and
	 * cast to its field schema before the first callback runs. A null value is
	 * passed as is, to delete the value.
	 *
	 * @param \WC_Data             $subject Object to change.
	 * @param array<string, mixed> $values  Values keyed by namespace, then field.
	 * @return \WP_Error|null A WP_Error when a value is rejected.
	 */
	private function update_fields( $subject, array $values ): ?\WP_Error {
		$registered = AbilityExtensions::get_fields( $this->get_resource() );
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
				}
				$updates[ 'extensions.' . $namespace . '.' . $field ] = array( $args['update_callback'], $value );
			}
		}

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
	 * that it throws, becomes an error with the given data. An exception keeps
	 * its message and gets the 500 status.
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
			$result = new \WP_Error( 'woocommerce_ability_extension_failed', $e->getMessage(), array( 'status' => 500 ) );
		}
		return is_wp_error( $result ) ? self::with_data( $result, $data ) : null;
	}

	/**
	 * Add the `extensions` property one time, at the top level of the input
	 * schema. It lists the fields that have an update_callback, and allows null
	 * for each one. Each `oneOf` branch only accepts the key, so the schema
	 * does not grow with the number of branches.
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
		foreach ( AbilityExtensions::get_fields( $this->get_resource() ) as $namespace => $fields ) {
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
						return $field_schema;
					},
					$writable
				),
				'additionalProperties' => false,
			);
		}

		$schema['properties']['extensions'] = $extensions;
		foreach ( array_keys( $schema['oneOf'] ?? array() ) as $index ) {
			$schema['oneOf'][ $index ]['properties']['extensions'] = array( 'type' => 'object' );
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
