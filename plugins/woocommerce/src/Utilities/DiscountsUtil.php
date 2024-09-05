<?php
/**
 * DiscountsUtil class file.
 */

namespace Automattic\WooCommerce\Utilities;

/**
 * The DiscountsUtil class provides utilities to assist discounts calculation and validation.
 */
class DiscountsUtil {

	/**
	 * Checks if the given email address(es) matches the ones specified on the coupon.
	 *
	 * @param array $check_emails Array of customer email addresses.
	 * @param array $restrictions Array of allowed email addresses.
	 *
	 * @return bool
	 */
	public static function is_coupon_emails_allowed( $check_emails, $restrictions ) {

		foreach ( $check_emails as $check_email ) {
			// With a direct match we return true.
			if ( in_array( $check_email, $restrictions, true ) ) {
				return true;
			}

			// Go through the allowed emails and return true if the email matches a wildcard.
			foreach ( $restrictions as $restriction ) {
				// Convert to PHP-regex syntax.
				$regex = '/^' . str_replace( '*', '(.+)?', $restriction ) . '$/';
				preg_match( $regex, $check_email, $match );
				if ( ! empty( $match ) ) {
					return true;
				}
			}
		}

		// No matches, this one isn't allowed.
		return false;
	}
}
