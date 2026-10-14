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
 * writes the extension fields before the save.
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
	 * Change the object in memory.
	 *
	 * @param \WC_Data $subject Object to change.
	 * @param array    $input   Ability input.
	 * @return null|\WP_Error
	 */
	abstract public static function change( $subject, array $input );

	/**
	 * Save the changed object.
	 *
	 * @param \WC_Data $subject Changed object.
	 * @return null|\WP_Error
	 */
	abstract public static function save( $subject );

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
	 * @throws \Throwable When save() throws, after the rollback.
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

		wc_transaction_query( 'start' );
		try {
			$saved = static::save( $subject );
		} catch ( \Throwable $e ) {
			wc_transaction_query( 'rollback' );
			throw $e;
		}
		if ( is_wp_error( $saved ) ) {
			wc_transaction_query( 'rollback' );
			return $saved;
		}
		wc_transaction_query( 'commit' );

		return static::prepare_response( $subject );
	}
}
