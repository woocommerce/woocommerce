<?php
/**
 * Privacy/GDPR related functionality.
 *
 * @package WooCommerce\Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Privacy Class.
 */
class WC_Privacy {

	/**
	 * Init - hook into events.
	 */
	public static function init() {
		// Add hooks here.
	}

	/**
	 * Anonymize/remove personal data for a given email address.
	 *
	 * @param string $email Email address.
	 */
	public static function remove_personal_data( $email ) {
		// Do something.
	}

	/**
	 * Get personal data for a given email address. This can be used for exports.
	 *
	 * @param string $email Email address.
	 * @return array Array of personal data.
	 */
	public static function get_personal_data( $email ) {
		// Do something.
		return array();
	}

}

WC_Privacy::init();
