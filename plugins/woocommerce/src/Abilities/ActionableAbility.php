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
 * changes it in memory, applies the extension fields from the `extensions`
 * input, runs the validators of the object type and then saves it. A
 * rejection from any step saves nothing.
 *
 * Only save() saves. The other steps must not save, send email or make HTTP
 * requests.
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
	abstract public function load( array $input );

	/**
	 * Change the object in memory.
	 *
	 * @since 11.3.0
	 *
	 * @param object $subject Object to change.
	 * @param array  $input   Ability input.
	 * @return mixed A WP_Error rejects the change.
	 */
	abstract public function change( $subject, array $input );

	/**
	 * The ability output for the saved object.
	 *
	 * @since 11.3.0
	 *
	 * @param object $subject Saved object.
	 * @return mixed
	 */
	abstract public function prepare_response( $subject );

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

		$extensions = is_array( $input['extensions'] ?? null ) ? $input['extensions'] : array();
		$rejected   = AbilityFields::update( $this->get_object_type(), $subject, $extensions )
			?? AbilityFields::validate( $this->get_object_type(), $subject );
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
	 * Use the change as the execute callback.
	 *
	 * @param array $args Ability arguments.
	 * @return array
	 */
	protected function prepare_properties( array $args ): array {
		$args['execute_callback'] = array( $this, 'execute_change' );
		return parent::prepare_properties( $args );
	}
}
