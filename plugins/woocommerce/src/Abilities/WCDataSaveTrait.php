<?php
/**
 * WCDataSaveTrait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * The save() of an ActionableAbility whose object is a `WC_Data` object.
 *
 * @since 11.5.0
 */
trait WCDataSaveTrait {

	/**
	 * Save the changed object with WC_Data::save(). An order's save() catches a
	 * failed write, so this reads that error from
	 * WC_Abstract_Order::get_last_save_error(). When the save fails or leaves
	 * the object without an ID, it logs the error and returns a WP_Error with a
	 * fixed message, so the exception text stays out of the response.
	 *
	 * @since 11.5.0
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
}
