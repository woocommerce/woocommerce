<?php
/**
 * Actionable ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

use Automattic\WooCommerce\Internal\AbilitiesApi\ChangeSummary;
use Automattic\WooCommerce\Internal\AbilitiesApi\SideEffectGuard;

defined( 'ABSPATH' ) || exit;

/**
 * An ability that can say what it will do before it does it, with dry_run().
 * Register it with a subclass as the `ability_class`.
 *
 * A field change registers no `execute_callback` and implements load() and
 * change(). The execute callback loads the object, changes it in memory,
 * applies the extension fields from the `extensions` input, runs the
 * validators of the object type and then saves it one time. A rejection from
 * any step saves nothing. Its dry run runs the same steps without the save.
 *
 * An action registers its own `execute_callback` and overrides do_dry_run().
 *
 * Only save() saves. The other steps must not save, send email or make HTTP
 * requests. In a dry run, Core drops these and lists them as side effects.
 *
 * @since 11.3.0
 */
abstract class ActionableAbility extends \WP_Ability {

	/**
	 * Object type that the ability changes. The fields and validators of this type run on the object.
	 *
	 * @since 11.3.0
	 *
	 * @return string
	 */
	abstract public function get_object_type(): string;

	/**
	 * Load the object to change.
	 *
	 * @since 11.3.0
	 *
	 * @param array $input Ability input.
	 * @return object|\WP_Error
	 */
	public function load( array $input ) {
		return self::unsupported( $this->get_name() );
	}

	/**
	 * Change the object in memory.
	 *
	 * @since 11.3.0
	 *
	 * @param object $subject Object to change.
	 * @param array  $input   Ability input.
	 * @return mixed A WP_Error rejects the change.
	 */
	public function change( $subject, array $input ) {
		return self::unsupported( $this->get_name() );
	}

	/**
	 * The ability output for the saved object.
	 *
	 * @since 11.3.0
	 *
	 * @param object $subject Saved object.
	 * @return mixed
	 */
	public function prepare_response( $subject ) {
		return null;
	}

	/**
	 * Save the changed object.
	 *
	 * @since 11.3.0
	 *
	 * @param object $subject Changed object.
	 * @return mixed A WP_Error when the object was not saved.
	 */
	public function save( $subject ) {
		$subject->save();
		return null;
	}

	/**
	 * The ability call that undoes the change, read from the object before change(), or null.
	 *
	 * @since 11.3.0
	 *
	 * @param object $subject Object before the change.
	 * @param array  $input   Ability input.
	 * @return array{ability: string, input: array}|null
	 */
	public function undo( $subject, array $input ): ?array {
		return null;
	}

	/**
	 * Sentences that describe what the save does beyond the object, such as an email.
	 *
	 * @since 11.3.0
	 *
	 * @param object $subject Object before the change.
	 * @param array  $input   Ability input.
	 * @return string[]
	 */
	public function side_effects( $subject, array $input ): array {
		return array();
	}

	/**
	 * The values of the object that a dry run compares, keyed by name.
	 *
	 * @since 11.3.0
	 *
	 * @param object $subject Object.
	 * @return array
	 */
	public function get_values( $subject ): array {
		if ( ! $subject instanceof \WC_Data ) {
			return array();
		}

		$values = array_replace_recursive( $subject->get_data(), $subject->get_changes() );
		unset( $values['meta_data'] );
		array_walk_recursive(
			$values,
			static function ( &$value ) {
				if ( is_object( $value ) && method_exists( $value, '__toString' ) ) {
					$value = (string) $value;
				}
			}
		);
		return $values;
	}

	/**
	 * What the ability will do, without doing it. It checks the input and the
	 * permissions as execute() does.
	 *
	 * @since 11.3.0
	 *
	 * @param mixed $input Ability input.
	 * @return array|\WP_Error The summary: ability, object_type, object_id, object_label, changes, expected, side_effects and undo.
	 */
	public function dry_run( $input = null ) {
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
				/* translators: %s: Ability name. */
				sprintf( __( 'Ability "%s" does not have necessary permission.', 'woocommerce' ), $this->get_name() )
			);
		}

		return $this->do_dry_run( is_array( $input ) ? $input : array() );
	}

	/**
	 * The dry run of a field change. An action overrides it.
	 *
	 * @since 11.3.0
	 *
	 * @param array $input Valid ability input.
	 * @return array|\WP_Error
	 */
	protected function do_dry_run( array $input ) {
		return $this->stage( $input, true );
	}

	/**
	 * Run the change. This is the execute callback of a field change.
	 *
	 * @internal
	 *
	 * @param mixed $input Ability input.
	 * @return mixed
	 */
	public function execute_change( $input = null ) {
		$staged = $this->stage( is_array( $input ) ? $input : array(), false );
		if ( is_wp_error( $staged ) ) {
			return $staged;
		}

		$saved = $this->save( $staged['subject'] );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return $this->prepare_response( $staged['subject'] );
	}

	/**
	 * Every step of a field change except the save.
	 *
	 * @param array $input   Ability input.
	 * @param bool  $dry_run Whether to drop side effects and return the summary.
	 * @return array|\WP_Error The summary in a dry run, else the changed object under `subject`.
	 */
	private function stage( array $input, bool $dry_run ) {
		$expected = is_array( $input['expected'] ?? null ) ? $input['expected'] : array();
		unset( $input['expected'] );

		$subject = $this->load( $input );
		if ( is_wp_error( $subject ) ) {
			return $subject;
		}

		$type   = $this->get_object_type();
		$before = $this->read( $subject );
		$stale  = ChangeSummary::stale( $before, $expected );
		if ( ! empty( $stale ) ) {
			return new \WP_Error(
				'woocommerce_ability_stale',
				/* translators: %s: Comma-separated field paths. */
				sprintf( __( 'The object changed since it was read: %s. Nothing was saved.', 'woocommerce' ), implode( ', ', $stale ) ),
				array(
					'status' => 409,
					'fields' => $stale,
				)
			);
		}

		if ( ! $dry_run ) {
			$rejected = $this->apply( $subject, $input );
			return is_wp_error( $rejected ) ? $rejected : array( 'subject' => $subject );
		}

		$label        = is_callable( array( $subject, 'get_name' ) ) ? $subject->get_name() : null;
		$undo         = $this->undo( $subject, $input );
		$side_effects = $this->side_effects( $subject, $input );

		list( $rejected, $dropped ) = SideEffectGuard::run(
			function () use ( $subject, $input ) {
				return $this->apply( $subject, $input );
			}
		);
		if ( is_wp_error( $rejected ) ) {
			return $rejected;
		}

		$changes = ChangeSummary::changes( $type, $before, $this->read( $subject ) );
		if ( null !== $undo && ! isset( $undo['input']['expected'] ) && wp_get_ability( (string) $undo['ability'] ) instanceof self ) {
			$undo['input']['expected'] = array_column( $changes, 'after', 'field' );
		}

		return array(
			'ability'      => $this->get_name(),
			'object_type'  => $type,
			'object_id'    => is_callable( array( $subject, 'get_id' ) ) ? $subject->get_id() : null,
			'object_label' => is_string( $label ) && '' !== $label ? $label : null,
			'changes'      => $changes,
			'expected'     => array_column( $changes, 'before', 'field' ),
			'side_effects' => array_merge( $side_effects, $dropped ),
			'undo'         => $undo,
		);
	}

	/**
	 * Change the object, write the extension fields and run the validators.
	 *
	 * @param object $subject Object to change.
	 * @param array  $input   Ability input.
	 * @return \WP_Error|null
	 */
	private function apply( $subject, array $input ): ?\WP_Error {
		$changed = $this->change( $subject, $input );
		if ( is_wp_error( $changed ) ) {
			return $changed;
		}

		$extensions = is_array( $input['extensions'] ?? null ) ? $input['extensions'] : array();
		return AbilityFields::update( $this->get_object_type(), $subject, $extensions )
			?? AbilityFields::validate( $this->get_object_type(), $subject );
	}

	/**
	 * The object's values and its extension field values.
	 *
	 * @param object $subject Object.
	 * @return array
	 */
	private function read( $subject ): array {
		$values = $this->get_values( $subject );
		$fields = AbilityFields::get_values( $this->get_object_type(), $subject );
		if ( ! empty( $fields ) ) {
			$values['extensions'] = $fields;
		}
		return $values;
	}

	/**
	 * The error for a step that an ability does not implement.
	 *
	 * @param string $name Ability name.
	 * @return \WP_Error
	 */
	private static function unsupported( string $name ): \WP_Error {
		return new \WP_Error(
			'woocommerce_ability_change_unsupported',
			/* translators: %s: Ability name. */
			sprintf( __( 'Ability "%s" does not change an object.', 'woocommerce' ), $name ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Use the change as the execute callback of a field change, accept
	 * `expected` in its input, and mark the dry run in the meta.
	 *
	 * @param array $args Ability arguments.
	 * @return array
	 */
	protected function prepare_properties( array $args ): array {
		if ( empty( $args['execute_callback'] ) ) {
			$args['execute_callback'] = array( $this, 'execute_change' );
			$args['input_schema']     = self::with_expected( $args['input_schema'] ?? array() );
		}
		$args['meta']['woocommerce']['dry_run'] = true;
		return parent::prepare_properties( $args );
	}

	/**
	 * Accept the optional `expected` values in an input schema, and in each of its `oneOf` branches.
	 *
	 * @param array $schema Input schema.
	 * @return array
	 */
	private static function with_expected( array $schema ): array {
		$expected = array(
			'type'        => 'object',
			'description' => __( 'Optional. Values the object must still have, keyed by the field paths that a dry run reports. When one differs, nothing is saved.', 'woocommerce' ),
		);
		if ( isset( $schema['properties'] ) ) {
			$schema['properties']['expected'] = $expected;
		}
		foreach ( $schema['oneOf'] ?? array() as $index => $branch ) {
			$schema['oneOf'][ $index ]['properties']['expected'] = $expected;
		}
		return $schema;
	}
}
