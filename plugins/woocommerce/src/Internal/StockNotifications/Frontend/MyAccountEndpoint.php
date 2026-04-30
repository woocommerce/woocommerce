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
	public const ENDPOINT = 'stock-notifications';

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
	 * Register the `stock-notifications` rewrite endpoint / query var.
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
	 * Default page size when none is provided.
	 *
	 * @var int
	 */
	public const DEFAULT_PER_PAGE = 10;

	/**
	 * Render the endpoint template.
	 *
	 * Hooked to `woocommerce_account_stock-notifications_endpoint`, mirroring
	 * how `woocommerce_account_downloads` and `woocommerce_account_orders` hook up.
	 *
	 * @param string|int $current_page The current page number passed by WC (the value
	 *                                 captured from the rewrite endpoint, e.g. `2` for
	 *                                 `/my-account/stock-notifications/2/`). Empty
	 *                                 string when no page is in the URL.
	 */
	public function render_endpoint( $current_page = 1 ): void {
		$current_page = max( 1, (int) $current_page );

		/**
		 * Filter the per-page count for the My Account stock-notifications table.
		 *
		 * @since 10.9.0
		 *
		 * @param int $per_page Number of notifications shown per page. Default {@see self::DEFAULT_PER_PAGE}.
		 */
		$per_page = (int) apply_filters( 'woocommerce_account_back_in_stock_notifications_per_page', self::DEFAULT_PER_PAGE );
		$per_page = max( 1, $per_page );

		$page = $this->get_current_user_notifications_page( $current_page, $per_page );

		\wc_get_template(
			'myaccount/stock-notifications.php',
			array(
				'notifications' => $page['notifications'],
				'has_items'     => ! empty( $page['notifications'] ),
				'current_page'  => $page['current_page'],
				'total_pages'   => $page['total_pages'],
				'total_items'   => $page['total_items'],
				'per_page'      => $per_page,
			)
		);
	}

	/**
	 * Return one page of the current user's notifications, newest first.
	 *
	 * Always scopes to `get_current_user_id()` — the caller is never trusted.
	 *
	 * @param int $current_page 1-indexed page number.
	 * @param int $per_page     Page size.
	 * @return array{notifications:array<Notification>, current_page:int, total_pages:int, total_items:int}
	 */
	public function get_current_user_notifications_page( int $current_page, int $per_page ): array {
		$current_page = max( 1, $current_page );
		$per_page     = max( 1, $per_page );

		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return array(
				'notifications' => array(),
				'current_page'  => 1,
				'total_pages'   => 0,
				'total_items'   => 0,
			);
		}

		/**
		 * Filter the notification statuses shown on the My Account stock-notifications screen.
		 *
		 * Defaults to PENDING + ACTIVE — the statuses the customer can act on. SENT
		 * notifications are deliberately hidden because the email has already been
		 * dispatched and the row is just noise; CANCELLED is hidden for the same
		 * reason. Merchants who want a full history can build their own view via
		 * {@see NotificationQuery::get_notifications()}.
		 *
		 * @since 10.9.0
		 *
		 * @param string[] $statuses List of {@see NotificationStatus} values to include.
		 */
		$statuses = (array) apply_filters(
			'woocommerce_account_back_in_stock_notifications_statuses',
			array( NotificationStatus::PENDING, NotificationStatus::ACTIVE )
		);

		$total_items = NotificationQuery::count_notifications(
			array(
				'user_id' => $user_id,
				'status'  => $statuses,
			)
		);

		$total_pages = (int) ceil( $total_items / $per_page );

		// Clamp out-of-range pages to the last available page so a stale link
		// doesn't render an empty table.
		if ( $total_items > 0 && $current_page > $total_pages ) {
			$current_page = $total_pages;
		}

		$notifications = $total_items > 0 ? NotificationQuery::get_notifications(
			array(
				'user_id'  => $user_id,
				'status'   => $statuses,
				'order_by' => array( 'id' => 'DESC' ),
				'return'   => 'objects',
				'limit'    => $per_page,
				'offset'   => ( $current_page - 1 ) * $per_page,
			)
		) : array();

		return array(
			'notifications' => $notifications,
			'current_page'  => $current_page,
			'total_pages'   => $total_pages,
			'total_items'   => $total_items,
		);
	}

	/**
	 * Intercept a cancel POST to flip a notification to `cancelled`.
	 *
	 * Guards:
	 * - Must be on the My Account > stock-notifications endpoint.
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
		if ( ! $notification instanceof Notification ) {
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
