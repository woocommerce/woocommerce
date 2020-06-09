<?php
/**
 * CustomerProvider class file.
 *
 * @package Automattic\WooCommerce\Providers
 */

namespace Automattic\WooCommerce\Providers;

use \WC_Customer;

/**
 * Provides methods to retrieve customer objects.
 */
class CustomerProvider {

	/**
	 * Get a customer object for the currently logged in user.
	 *
	 * @return WC_Customer Customer object for the currently logged in user.
	 */
	public function get_logged_in_customer() {
		return new WC_Customer( get_current_user_id(), true );
	}

	/**
	 * Get a customer object by id.
	 *
	 * @param int $id The id of the customer to retrieve.
	 *
	 * @return WC_Customer Customer object for the specified id.
	 */
	public function get_customer_by_id( int $id ) {
		return new WC_Customer( $id );
	}
}
