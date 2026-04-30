<?php
/**
 * MyAccountEndpoint class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;

/**
 * Registers the "Stock notifications" My Account endpoint and handles
 * the cancel action for customer-owned notifications.
 *
 * @internal
 */
class MyAccountEndpoint {

	/**
	 * Query var / endpoint slug.
	 *
	 * Matches the slug used in the menu filter, rewrite endpoint, and template hook.
	 */
	public const ENDPOINT = 'back-in-stock-notifications';

	/**
	 * Query argument triggered by the cancel form post.
	 */
	public const CANCEL_ACTION = 'wc_bis_cancel_notification';

	/**
	 * Build the nonce action name for a given notification id.
	 *
	 * Same shape as the admin-side scoping from #64348: `wc_bis_cancel_<id>`.
	 *
	 * @param int $notification_id The notification id.
	 * @return string The nonce action name.
	 */
	public static function get_cancel_nonce_action( int $notification_id ): string {
		return 'wc_bis_cancel_' . $notification_id;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_get_query_vars', array( $this, 'register_query_var' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'register_menu_item' ), 10, 2 );
		add_filter( 'woocommerce_endpoint_' . self::ENDPOINT . '_title', array( $this, 'filter_endpoint_title' ) );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( $this, 'render_endpoint' ) );
		add_action( 'template_redirect', array( $this, 'maybe_handle_cancel' ) );
	}

	/**
	 * Register the `back-in-stock-notifications` rewrite endpoint / query var.
	 *
	 * Hooking `woocommerce_get_query_vars` wires us into {@see \WC_Query::add_endpoints()}
	 * so WordPress registers the rewrite rule and our slug lands in `$wp->query_vars`.
	 *
	 * @param array<string, string> $vars Existing query vars keyed by endpoint slug.
	 * @return array<string, string>
	 */
	public function register_query_var( $vars ) {
		if ( ! is_array( $vars ) ) {
			return $vars;
		}

		$vars[ self::ENDPOINT ] = self::ENDPOINT;
		return $vars;
	}

	/**
	 * Inject the menu entry between "Downloads" and "Addresses" if present, otherwise append.
	 *
	 * @param array<string, string> $items     The current menu items.
	 * @param array<string, string> $endpoints The resolved endpoint slugs (unused, kept for filter signature).
	 * @return array<string, string>
	 */
	public function register_menu_item( $items, $endpoints ) {
		unset( $endpoints ); // Avoid parameter not used PHPCS errors.

		if ( ! is_array( $items ) ) {
			return $items;
		}

		$new_item = array(
			self::ENDPOINT => __( 'Stock notifications', 'woocommerce' ),
		);

		// Try to slot it in right after Downloads so it sits with the other lists.
		if ( isset( $items['downloads'] ) ) {
			$position = array_search( 'downloads', array_keys( $items ), true );
			if ( false !== $position ) {
				return array_slice( $items, 0, $position + 1, true )
					+ $new_item
					+ array_slice( $items, $position + 1, null, true );
			}
		}

		// Otherwise insert before customer-logout so "Log out" stays last.
		if ( isset( $items['customer-logout'] ) ) {
			$position = array_search( 'customer-logout', array_keys( $items ), true );
			if ( false !== $position ) {
				return array_slice( $items, 0, $position, true )
					+ $new_item
					+ array_slice( $items, $position, null, true );
			}
		}

		return $items + $new_item;
	}

	/**
	 * Override the endpoint page title.
	 *
	 * @param string $title The default title.
	 * @return string
	 */
	public function filter_endpoint_title( $title ) {
		unset( $title ); // Avoid parameter not used PHPCS errors.
		return __( 'Stock notifications', 'woocommerce' );
	}

	/**
	 * Render the endpoint template.
	 *
	 * Hooked to `woocommerce_account_back-in-stock-notifications_endpoint`, mirroring
	 * how `woocommerce_account_downloads` and `woocommerce_account_orders` hook up.
	 */
	public function render_endpoint(): void {
		$notifications = $this->get_current_user_notifications();

		\wc_get_template(
			'myaccount/back-in-stock-notifications.php',
			array(
				'notifications' => $notifications,
				'has_items'     => ! empty( $notifications ),
			)
		);
	}

	/**
	 * Return the current user's notifications, newest first.
	 *
	 * Always scopes to `get_current_user_id()` — the caller is never trusted.
	 *
	 * @return array<Notification>
	 */
	public function get_current_user_notifications(): array {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return array();
		}

		$notifications = NotificationQuery::get_notifications(
			array(
				'user_id'  => $user_id,
				'order_by' => array( 'id' => 'DESC' ),
				'return'   => 'objects',
				'limit'    => -1,
			)
		);

		if ( ! is_array( $notifications ) ) {
			return array();
		}

		return $notifications;
	}

	/**
	 * Intercept a cancel POST to flip a notification to `cancelled`.
	 *
	 * Guards:
	 * - Must be on the My Account > back-in-stock-notifications endpoint.
	 * - Must be authenticated.
	 * - Nonce must be scoped to the specific notification id ({@see ::get_cancel_nonce_action()}).
	 * - The notification must belong to the current user.
	 *
	 * On any guard failure the request is silently dropped (no destructive side effects).
	 */
	public function maybe_handle_cancel(): void {
		global $wp;

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST[ self::CANCEL_ACTION ] ) ) {
			return;
		}

		if ( ! isset( $wp->query_vars[ self::ENDPOINT ] ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		$notification_id = isset( $_POST['notification_id'] ) ? absint( wp_unslash( $_POST['notification_id'] ) ) : 0;
		if ( $notification_id <= 0 ) {
			return;
		}

		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( ! wp_verify_nonce( $nonce, self::get_cancel_nonce_action( $notification_id ) ) ) {
			return;
		}

		$notification = Factory::get_notification( $notification_id );
		if ( ! $notification ) {
			return;
		}

		if ( (int) $notification->get_user_id() !== get_current_user_id() ) {
			return;
		}

		if ( NotificationStatus::CANCELLED === $notification->get_status() ) {
			return;
		}

		$notification->set_status( NotificationStatus::CANCELLED );
		$notification->set_cancellation_source( NotificationCancellationSource::USER );
		$notification->set_date_cancelled( time() );
		$notification->save();

		$product_name = $notification->get_product_name();
		if ( '' !== $product_name ) {
			\wc_add_notice(
				sprintf(
					/* translators: %s: product name */
					esc_html__( 'Back in stock notification for "%s" cancelled.', 'woocommerce' ),
					esc_html( $product_name )
				)
			);
		} else {
			\wc_add_notice( esc_html__( 'Back in stock notification cancelled.', 'woocommerce' ) );
		}

		wp_safe_redirect( \wc_get_endpoint_url( self::ENDPOINT, '', \wc_get_page_permalink( 'myaccount' ) ) );
	}
}
