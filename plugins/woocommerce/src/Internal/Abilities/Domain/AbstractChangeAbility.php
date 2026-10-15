<?php
/**
 * Change ability base class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

defined( 'ABSPATH' ) || exit;

/**
 * A domain ability that loads one object and writes a change to it the way
 * the object offers. With the feature on, CoreActionableAbility runs the same
 * steps and puts the extension fields on the object before the write.
 */
abstract class AbstractChangeAbility extends AbstractDomainAbility {

	/**
	 * Resource that the ability changes.
	 *
	 * @return string
	 */
	abstract public static function get_resource(): string;

	/**
	 * Load the object to change.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Data|\WP_Error
	 */
	abstract public static function load( array $input );

	/**
	 * Apply the change and save, with the method the object offers for it. The
	 * extension fields, when the feature is on, are already on the object.
	 *
	 * @param \WC_Data $subject Object to change.
	 * @param array    $input   Ability input.
	 * @return null|\WP_Error
	 */
	abstract public static function write( $subject, array $input );

	/**
	 * The ability output for the saved object.
	 *
	 * @param \WC_Data $subject Saved object.
	 * @return array
	 */
	abstract public static function prepare_response( $subject ): array;

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

		$written = static::write( $subject, $input );
		if ( is_wp_error( $written ) ) {
			return $written;
		}

		return static::prepare_response( $subject );
	}
}
