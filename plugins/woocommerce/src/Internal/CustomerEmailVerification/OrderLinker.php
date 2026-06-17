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
		add_action( 'woocommerce_customer_email_verified', array( $this, 'link_past_orders' ) );
	}

	/**
	 * Link the verified customer's matching guest orders to their account.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id The verified user's ID.
	 */
	public function link_past_orders( int $user_id ): void {
		// ponytail: link inline; re-add an Action Scheduler job if a store with thousands of guest orders times out here.
		if ( get_user_by( 'id', $user_id ) ) {
			wc_update_new_customer_past_orders( $user_id );
		}
	}

	/**
	 * Return the IDs of guest orders whose billing email matches the user's account email.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id The user ID to match guest orders for.
	 * @param int $limit   Maximum number of order IDs to return.
	 * @return int[]|null Matching guest order IDs, or null when the user does not exist.
	 */
	private function get_matching_order_ids( int $user_id, int $limit ): ?array {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return null;
		}

		return (array) wc_get_orders(
			array(
				'limit'    => $limit,
				'customer' => array( array( 0, $user->user_email ) ),
				'return'   => 'ids',
			)
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
		return ! empty( $this->get_matching_order_ids( $user_id, 1 ) );
	}
}
