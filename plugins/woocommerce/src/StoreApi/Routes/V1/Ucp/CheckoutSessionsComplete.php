<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

use Automattic\WooCommerce\Checkout\Helpers\ReserveStockException;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\CheckoutMapper;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils;

/**
 * UCP `POST /checkout-sessions/{id}/complete` route.
 */
class CheckoutSessionsComplete extends AbstractCheckoutRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ucp-checkout-sessions-complete';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return self::get_path_regex();
	}

	/**
	 * Get the path of this rest route.
	 *
	 * @return string
	 */
	public static function get_path_regex() {
		return '/checkout-sessions/(?P<id>[\w\-\.]+)/complete';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return array(
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => '__return_true',
			),
			'schema' => array( $this->schema, 'get_public_item_schema' ),
		);
	}

	/**
	 * Hand a ready checkout over to the buyer to pay in the browser.
	 *
	 * No payment is taken through the API, so `payment` is ignored. The cart becomes a pending
	 * order, the Store API draft order of this session, and `continue_url` is its payment page.
	 * Stock is held for the order first, as the Store API checkout does, because the payment
	 * page trusts a pending order's stock to be held and checks nothing itself.
	 * A cart with nothing to pay is refused: these routes complete no order, and the payment page takes no such order.
	 * A checkout that is not ready yet is returned as it is, with messages naming what is missing.
	 * So is one already handed off and unchanged since, with a warning: its order is not written to again.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$release = $this->acquire_checkout_lock( $this->route_cart_id( $request ) );

		if ( $release instanceof \WP_REST_Response ) {
			return $release;
		}

		try {
			$this->reload_persisted_checkout();

			return $this->hand_off_checkout( $request );
		} finally {
			// The row must exist before the lock is released. Shutdown is too late: the next completion can read the gap.
			$session = WC()->session;
			if ( $session instanceof \WC_Session && is_callable( array( $session, 'save_data' ) ) ) {
				$session->save_data();
			}
			$release();
		}
	}

	/**
	 * Hand off the checkout named by the request. The caller holds the checkout lock.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	private function hand_off_checkout( \WP_REST_Request $request ) {
		$state = $this->open_checkout( true );

		if ( $state instanceof \WP_REST_Response ) {
			return $state;
		}

		$checkout_id = $this->route_cart_id( $request );
		$cart        = $this->cart_item_response();
		$order       = $this->get_draft_order();
		unset( $state['status'], $state['continue_url'] );

		// Taken before the hooks below run, so a cart one of them changes no longer matches the order.
		$fingerprint = $this->hand_off_fingerprint();

		// The cart or its addresses can change behind the checkout, through the Store API or a price edit, and
		// a new order is then made for it: the one already handed off must not stay payable too.
		if ( ! empty( $state['order_id'] ) && ( ! $order || $order->get_id() !== (int) $state['order_id'] || ( $state['fingerprint'] ?? '' ) !== $fingerprint ) ) {
			$state = $this->cancel_handed_off_order( $state, __( 'UCP checkout session cart changed after payment was handed off.', 'woocommerce' ) );

			if ( ! empty( $state['order_id'] ) ) {
				return $this->refuse_kept_order();
			}

			$order = $this->get_draft_order();
		}

		if ( CheckoutMapper::STATUS_READY_FOR_COMPLETE !== ( new CheckoutMapper() )->build_checkout( $cart, $checkout_id, $state )['status'] ) {
			// An order handed off while the checkout was ready, for stock since taken say, must not stay payable.
			$state = $this->cancel_handed_off_order( $state, __( 'UCP checkout session is no longer ready to complete.', 'woocommerce' ) );

			if ( $this->hand_off_is_payable( $state ) ) {
				return $this->refuse_kept_order();
			}

			return $this->checkout_response( $checkout_id, $this->checkout_state() ?? $state, 200, $cart );
		}

		if ( ! $this->cart_controller->get_cart_instance()->needs_payment() ) {
			return $this->ucp_error( 'payment_not_required', __( 'This checkout has nothing to pay, so it cannot be handed off for payment. It must be made on the store itself.', 'woocommerce' ), 'unrecoverable', 200 );
		}

		// Handed off already and unchanged. The buyer may be paying this order now, so it is not written to again.
		if ( ! empty( $state['order_id'] ) ) {
			return $this->already_handed_off_response( $checkout_id, $cart );
		}

		if ( $order ) {
			$this->order_controller->update_order_from_cart( $order );
		} else {
			$order = $this->order_controller->create_order_from_cart();
		}

		if ( ! $order->get_id() ) {
			return $this->ucp_error( 'TRANSPORT_ERROR', __( 'The order could not be created. Please try again.', 'woocommerce' ), 'recoverable', 500 );
		}

		// Remembered before the hold so a refused hold leaves a draft the next attempt reuses.
		$this->set_draft_order_id( $order->get_id() );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Store API hook, documented in Routes\V1\Checkout.
		do_action( 'woocommerce_store_api_checkout_update_order_meta', $order );

		try {
			$this->order_controller->validate_order_before_payment( $order );
		} catch ( RouteException $e ) {
			// The order stays a draft, which the payment page refuses.
			return $this->ucp_error( 'VALIDATION_ERROR', wp_specialchars_decode( $e->getMessage(), ENT_QUOTES ), 'recoverable', 200 );
		}

		try {
			wc_reserve_stock_for_order( $order );
		} catch ( ReserveStockException $e ) {
			// The hold failed on stock another checkout took since the cart was last checked. The order
			// stays a draft, which the payment page refuses.
			return $this->ucp_error( Utils::cart_error_code( (string) $e->getErrorCode() ), wp_specialchars_decode( $e->getMessage(), ENT_QUOTES ), 'recoverable', 200, '$.line_items' );
		}

		// The payment page only takes an order awaiting payment, which a checkout draft is not.
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		// Stored before the processed callbacks, which can pay the order. A callback that then
		// fails must leave this association in place so a retry finds the order and does not
		// create another. The hook is not run again on that retry: a callback that already
		// ran must not run twice, which is also how the Store API checkout behaves.
		$state = array_merge(
			$state,
			array(
				'status'       => CheckoutMapper::STATUS_REQUIRES_ESCALATION,
				'continue_url' => $order->get_checkout_payment_url(),
				'order_id'     => $order->get_id(),
				'fingerprint'  => $fingerprint,
			)
		);
		$this->save_checkout_state( $state );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Store API hook, documented in Routes\V1\Checkout.
		do_action( 'woocommerce_store_api_checkout_order_processed', $order );

		return $this->checkout_response( $checkout_id, $this->checkout_state() ?? $state, 200, $cart );
	}

	/**
	 * Hold the checkout until this completion has stored its order, so a second one waits and then sees it.
	 *
	 * The lock follows the session row, not the cart token. Two tokens for one session share a customer id
	 * and must share the lock. The name is limited to 64 characters, so a longer identity is shortened.
	 *
	 * @param string $checkout_id Checkout identifier, used when the request has no session yet.
	 * @return \Closure|\WP_REST_Response A release callback, or the error response when the lock was not acquired.
	 */
	private function acquire_checkout_lock( string $checkout_id ) {
		global $wpdb;

		$session     = WC()->session;
		$customer_id = $session instanceof \WC_Session ? (string) $session->get_customer_id() : '';
		$identity    = '' !== $customer_id ? get_current_blog_id() . ':' . $customer_id : $checkout_id;
		$name        = 'wc_ucp_' . $identity;

		if ( strlen( $name ) > 64 ) {
			$name = 'wc_ucp_' . md5( $identity );
		}

		$acquired = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $name, 15 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Named lock; it must not be served from a cache.

		if ( '1' !== (string) $acquired ) {
			return $this->ucp_error( 'TRANSPORT_ERROR', __( 'This checkout is being completed. Please try again.', 'woocommerce' ), 'recoverable', 500 );
		}

		return static function () use ( $wpdb, $name ): void {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Releases the named lock acquired above.
		};
	}

	/**
	 * Read the checkout and its draft order from the session row, which another completion may have just written.
	 */
	private function reload_persisted_checkout(): void {
		$session = WC()->session;

		if ( ! $session instanceof \WC_Session || ! is_callable( array( $session, 'get_session' ) ) || '' === (string) $session->get_customer_id() ) {
			return;
		}

		$customer_id = (string) $session->get_customer_id();

		// The cookie session caches its row. The Store API handler reads the table directly.
		if ( $session instanceof \WC_Session_Handler && defined( 'WC_SESSION_CACHE_GROUP' ) ) {
			wp_cache_delete( \WC_Cache_Helper::get_cache_prefix( WC_SESSION_CACHE_GROUP ) . $customer_id, WC_SESSION_CACHE_GROUP );
		}

		$stored = $session->get_session( $customer_id, false );

		if ( ! is_array( $stored ) ) {
			return;
		}

		// A key this request loaded, and has not changed, is gone when the row no longer has it.
		// Setting the stored keys alone would leave a removed coupon in memory.
		foreach ( $this->loaded_session_snapshot( $session ) as $key => $loaded ) {
			if ( ! is_string( $key ) || array_key_exists( $key, $stored ) || ! isset( $session->$key ) ) {
				continue;
			}

			$current = $session->get( $key );
			if ( $loaded === $current || maybe_unserialize( $loaded ) === $current ) {
				$session->set( $key, null );
			}
		}

		foreach ( $stored as $key => $value ) {
			if ( is_string( $key ) ) {
				$session->set( $key, maybe_unserialize( $value ) );
			}
		}

		// The cart and customer objects were filled before this lock. They have to match the row just read,
		// or a fingerprint from the older cart cancels the order the row points at.
		if ( isset( $stored['cart'] ) && WC()->cart instanceof \WC_Cart ) {
			WC()->cart->get_cart_from_session();
		}

		$customer = WC()->customer;
		if ( isset( $stored['customer'] ) && $customer instanceof \WC_Customer ) {
			$store = $customer->get_data_store();
			// get_data_store() returns the WC_Data_Store wrapper, not the session store it holds.
			if ( $store instanceof \WC_Data_Store && is_a( $store->get_current_class_name(), \WC_Customer_Data_Store_Session::class, true ) ) {
				$store->read( $customer );
			}
		}
	}

	/**
	 * Session data this request loaded, so a reload can tell a local change from a stale key.
	 *
	 * @param \WC_Session $session The bound session.
	 * @return array<string, mixed>
	 */
	private function loaded_session_snapshot( \WC_Session $session ): array {
		$method = new \ReflectionMethod( \WC_Session::class, 'get_loaded_session_snapshot' );
		$method->setAccessible( true );
		$snapshot = $method->invoke( $session );

		return is_array( $snapshot ) ? $snapshot : array();
	}

	/**
	 * Return a checkout whose payment was already handed off, as it stands, with a warning that nothing was done.
	 *
	 * @param string               $checkout_id Checkout identifier.
	 * @param array<string, mixed> $cart Cart response.
	 * @return \WP_REST_Response
	 */
	private function already_handed_off_response( string $checkout_id, array $cart ): \WP_REST_Response {
		// Read again, so an order paid while this request ran is reported as it now stands.
		$response = $this->checkout_response( $checkout_id, (array) $this->checkout_state(), 200, $cart );
		$data     = (array) $response->get_data();

		if ( ! empty( $data['continue_url'] ) ) {
			$data['messages'][] = array(
				'type'         => 'warning',
				'code'         => 'ALREADY_HANDED_OFF',
				'content'      => __( 'Payment for this checkout session was already handed off, so it was returned unchanged. Update the checkout session to change it, or send the buyer to the payment page.', 'woocommerce' ),
				'path'         => '$.status',
				'content_type' => 'plain',
			);
			$response->set_data( $data );
		}

		return $response;
	}
}
