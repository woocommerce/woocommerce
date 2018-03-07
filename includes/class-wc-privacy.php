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
		/**
		 * Order Data:
		 *
		 * Transaction ID
		 * Customer's IP Address and User Agent
		 * Billing Address First Name, Last Name, Company, Address Line 1, Address Line 2, City, Postcode/ZIP, Country, State/County, Phone, Email Address
		 * "Billing Fields" (_billing_address_index)
		 * Same as above for shipping
		 *
		 * Customer Data (meta):
		 *
		 * Billing and shipping addresses
		 *
		 * Misc:
		 *
		 * Downloadable Product User Email
		 * Download Log Entry User IP Address
		 * Carts for user ID/Sessions
		 * File based logs containing their email e.g. from webhooks
		 * Payment tokens?
		 */
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
