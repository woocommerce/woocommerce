<?php
/**
 * ReviewRequestUnsubscribeController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Email;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Order;

/**
 * Handles customer-initiated unsubscribes from the review-request email.
 *
 * Registered customers (orders with a `customer_id`) see a one-click
 * unsubscribe link in their review-request email; clicking it records the
 * opt-out as user meta, cancels any pending Action Scheduler send, and
 * redirects the customer to the shop with a success notice. Guest customers
 * are out of scope for this feature: the email template omits the link for
 * them, and the scheduler filter only considers registered-user flags.
 *
 * The token / handler / redirect mechanics mirror the Stock Notifications
 * unsubscribe flow (`src/Internal/StockNotifications/Emails/EmailActionController.php`).
 *
 * @internal Just for internal use.
 *
 * @since 10.8.0
 */
class ReviewRequestUnsubscribeController implements RegisterHooksInterface {

	/**
	 * Order meta key holding the secret token used to authenticate the
	 * unsubscribe link. Generated lazily by the email class.
	 */
	public const UNSUBSCRIBE_KEY_META = '_wc_review_request_unsubscribe_key';

	/**
	 * User meta key set to 'yes' once the customer unsubscribes. Blocks every
	 * future review-request email for that customer regardless of order.
	 */
	public const CUSTOMER_UNSUBSCRIBED_META = '_wc_review_requests_unsubscribed';

	/**
	 * Query arg name for the order id in the unsubscribe URL.
	 */
	public const QUERY_ARG_ORDER = 'review_request_unsubscribe';

	/**
	 * Query arg name for the unsubscribe token in the URL.
	 */
	public const QUERY_ARG_KEY = 'key';

	/**
	 * Register hooks and filters.
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'maybe_process_unsubscribe' ) );
		add_filter( 'woocommerce_should_send_review_request', array( $this, 'respect_unsubscribe_flags' ), 10, 2 );
	}

	/**
	 * Detect and process an unsubscribe request on the front end.
	 *
	 * @internal
	 */
	public function maybe_process_unsubscribe(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- token is the nonce.
		if ( ! isset( $_GET[ self::QUERY_ARG_ORDER ], $_GET[ self::QUERY_ARG_KEY ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order_id = absint( wp_unslash( $_GET[ self::QUERY_ARG_ORDER ] ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$submitted_key = sanitize_text_field( wp_unslash( $_GET[ self::QUERY_ARG_KEY ] ) );

		if ( ! $order_id || '' === $submitted_key ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		// Guests are out of scope for this feature.
		$customer_id = $order->get_customer_id();
		if ( ! $customer_id ) {
			return;
		}

		$stored_key = (string) $order->get_meta( self::UNSUBSCRIBE_KEY_META );
		if ( '' === $stored_key || ! hash_equals( $stored_key, $submitted_key ) ) {
			return;
		}

		$this->apply_unsubscribe( $order, $customer_id );
		$this->announce_and_redirect();
	}

	/**
	 * Block the scheduler when the order's customer has opted out.
	 *
	 * Hooked into `woocommerce_should_send_review_request` (defined by
	 * `ReviewRequestScheduler`). If an earlier callback already returned false
	 * we respect that and short-circuit; otherwise we check the customer's
	 * opt-out flag. Guest orders (no customer_id) are always allowed through.
	 *
	 * @internal
	 *
	 * @param bool     $should_send Whether the scheduler should enqueue the email.
	 * @param WC_Order $order       The order being evaluated.
	 * @return bool
	 */
	public function respect_unsubscribe_flags( $should_send, $order ): bool {
		if ( ! $should_send ) {
			return false;
		}

		if ( ! $order instanceof WC_Order ) {
			return (bool) $should_send;
		}

		$customer_id = $order->get_customer_id();
		if ( ! $customer_id ) {
			return true;
		}

		return 'yes' !== get_user_meta( $customer_id, self::CUSTOMER_UNSUBSCRIBED_META, true );
	}

	/**
	 * Persist the opt-out on the customer and cancel any pending scheduled send.
	 *
	 * @param WC_Order $order       The order whose unsubscribe link was clicked.
	 * @param int      $customer_id The order's customer user id (guaranteed > 0 by the caller).
	 */
	private function apply_unsubscribe( WC_Order $order, int $customer_id ): void {
		update_user_meta( $customer_id, self::CUSTOMER_UNSUBSCRIBED_META, 'yes' );

		as_unschedule_action( ReviewRequestScheduler::ACTION_HOOK, array( $order->get_id() ) );
		$order->delete_meta_data( ReviewRequestScheduler::SCHEDULED_META_KEY );
		$order->save();
	}

	/**
	 * Add a success notice and redirect the customer to the shop page.
	 *
	 * Matches the Stock Notifications flow: ensure a session cookie exists so
	 * the notice renders on the front-end landing page, then redirect through a
	 * filterable URL.
	 */
	private function announce_and_redirect(): void {
		if ( WC()->session instanceof \WC_Session_Handler && ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}

		wc_add_notice( __( 'You have been unsubscribed from review-request emails. You will no longer receive them.', 'woocommerce' ) );

		$shop_permalink = get_permalink( wc_get_page_id( 'shop' ) );
		$default_url    = false === $shop_permalink ? home_url( '/' ) : $shop_permalink;

		/**
		 * Filter the URL the customer is redirected to after unsubscribing.
		 *
		 * @param string $url The redirect URL.
		 *
		 * @since 10.8.0
		 */
		$url = (string) apply_filters( 'woocommerce_review_request_unsubscribe_redirect_url', $default_url );

		wp_safe_redirect( $url );
		exit;
	}
}
