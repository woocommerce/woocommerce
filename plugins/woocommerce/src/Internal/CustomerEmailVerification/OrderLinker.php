<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\CustomerEmailVerification;

/**
 * Links a verified customer's matching guest orders to their account.
 *
 * Listens for the email-verified event and reuses wc_update_new_customer_past_orders().
 *
 * @since 11.0.0
 */
class OrderLinker {

	/**
	 * Constructor. Registers hooks.
	 */
	public function __construct() {
		// wc_update_new_customer_past_orders() already no-ops on an invalid/guest user ID.
		// Wrapped in a void closure because the function returns a count that an action callback must not
		// return. The argument is left untyped and cast here: under strict_types a third party firing this
		// hook with a numeric string would otherwise throw a TypeError.
		add_action(
			'woocommerce_customer_email_verified',
			static function ( $user_id ): void {
				wc_update_new_customer_past_orders( absint( $user_id ) );
			}
		);
	}

	/**
	 * Whether the user has at least one guest order that could be linked on verification.
	 *
	 * Deliberately checks existence only (a single row, no details) so it is cheap to call
	 * on page load and discloses nothing beyond "there is something to link".
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id The user ID to check.
	 * @return bool
	 */
	public function has_linkable_orders( int $user_id ): bool {
		$user = get_user_by( 'id', $user_id );

		return $user && ! empty(
			wc_get_orders(
				array(
					'limit'    => 1,
					'customer' => array( array( 0, $user->user_email ) ),
					'return'   => 'ids',
				)
			)
		);
	}
}
