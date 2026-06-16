<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\CustomerEmailVerification;

/**
 * Links a verified customer's matching guest orders to their account.
 *
 * Listens for the email-verified event and reuses wc_update_new_customer_past_orders().
 * For customers with many matching orders, the work is offloaded to Action Scheduler.
 *
 * @since 11.0.0
 */
class OrderLinker {

	/**
	 * Above this many matching orders, link asynchronously via Action Scheduler.
	 */
	private const ASYNC_THRESHOLD = 25;

	/**
	 * The Action Scheduler hook used to link orders in the background.
	 */
	private const ASYNC_HOOK = 'woocommerce_email_verification_link_orders';

	/**
	 * Constructor. Registers hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_customer_email_verified', array( $this, 'link_past_orders' ) );
		add_action( self::ASYNC_HOOK, array( $this, 'run_link' ) );
	}

	/**
	 * On verification, decide whether to link inline or via Action Scheduler.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id The verified user's ID.
	 */
	public function link_past_orders( int $user_id ): void {
		$matching = $this->get_matching_order_ids( $user_id, self::ASYNC_THRESHOLD + 1 );
		if ( null === $matching ) {
			return;
		}

		if ( count( $matching ) > self::ASYNC_THRESHOLD ) {
			WC()->queue()->add( self::ASYNC_HOOK, array( 'user_id' => $user_id ), 'woocommerce-email-verification' );
			return;
		}

		$this->run_link( $user_id );
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
	 * Perform the link. Also the Action Scheduler callback.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id The user ID whose guest orders should be linked.
	 */
	public function run_link( int $user_id ): void {
		wc_update_new_customer_past_orders( $user_id );
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
