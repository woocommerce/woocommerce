<?php
/**
 * Change ability base class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

defined( 'ABSPATH' ) || exit;

/**
 * A domain ability that changes one object in steps and saves it one time.
 * With ability contracts on, CoreActionableAbility runs the same steps and
 * adds the extension fields and validators before the save.
 */
abstract class AbstractChangeAbility extends AbstractDomainAbility {

	/**
	 * Object type that the ability changes.
	 *
	 * @return string
	 */
	abstract public static function get_object_type(): string;

	/**
	 * Load the object to change.
	 *
	 * @param array $input Ability input.
	 * @return object|\WP_Error
	 */
	abstract public static function load( array $input );

	/**
	 * Change the object in memory.
	 *
	 * @param object $subject Object to change.
	 * @param array  $input   Ability input.
	 * @return null|\WP_Error
	 */
	abstract public static function change( $subject, array $input );

	/**
	 * Save the changed object.
	 *
	 * @param object $subject Changed object.
	 * @return null|\WP_Error
	 */
	abstract public static function save( $subject );

	/**
	 * The ability output for the saved object.
	 *
	 * @param object $subject Saved object.
	 * @return array
	 */
	abstract public static function prepare_response( $subject ): array;

	/**
	 * The ability call that undoes the change, read from the object before the change, or null.
	 *
	 * @param object $subject Object before the change.
	 * @param array  $input   Ability input.
	 * @return array{ability: string, input: array}|null
	 */
	public static function undo( $subject, array $input ): ?array {
		return null;
	}

	/**
	 * What the save does beyond the object.
	 *
	 * @param object $subject Object before the change.
	 * @param array  $input   Ability input.
	 * @return array<int, array{code: string, value: mixed, description: string}>
	 */
	public static function side_effects( $subject, array $input ): array {
		return array();
	}

	/**
	 * Run the change.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 10.9.0
	 */
	public static function execute( array $input ) {
		$subject = static::load( $input );
		if ( is_wp_error( $subject ) ) {
			return $subject;
		}

		$changed = static::change( $subject, $input );
		if ( is_wp_error( $changed ) ) {
			return $changed;
		}

		$saved = static::save( $subject );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return static::prepare_response( $subject );
	}
}
