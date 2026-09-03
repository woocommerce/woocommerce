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
 * the resend and cancel actions for customer-owned notifications.
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
	 * Query argument naming the action a row link triggers.
	 */
	public const ACTION_FIELD = 'wc_bis_action';

	/**
	 * Action value: send the verification email again for a pending notification.
	 */
	public const ACTION_RESEND = 'resend';

	/**
	 * Action value: cancel a pending or active notification.
	 */
	public const ACTION_CANCEL = 'cancel';

	/**
	 * Build the nonce action name for an action on a given notification.
	 *
	 * Scoped per action and per notification (`wc_bis_cancel_<id>`), so a nonce minted for one
	 * row's Cancel link cannot be replayed on another row or on its Resend link.
	 *
	 * @param string $action          One of the `ACTION_*` values.
	 * @param int    $notification_id The notification id.
	 * @return string The nonce action name.
	 */
	public static function get_nonce_action( string $action, int $notification_id ): string {
		return 'wc_bis_' . $action . '_' . $notification_id;
	}

	/**
	 * Build the nonce-protected URL a row action link points at.
	 *
	 * Same shape as the other My Account action links (cancel order, resend set-password):
	 * a GET back to the endpoint carrying the action, the id, and a scoped nonce.
	 *
	 * @param string $action          One of the `ACTION_*` values.
	 * @param int    $notification_id The notification id.
	 * @return string The action URL.
	 */
	public static function get_action_url( string $action, int $notification_id ): string {
		$url = add_query_arg(
			array(
				self::ACTION_FIELD => $action,
				'notification_id'  => $notification_id,
			),
			self::get_endpoint_url()
		);

		return wp_nonce_url( $url, self::get_nonce_action( $action, $notification_id ) );
	}

	/**
	 * Statuses a customer can still cancel from My Account.
	 *
	 * Backs the Cancel button in the template and the cancel request handler,
	 * so the two can't drift apart. The listing itself splits into a pending
	 * table and an active table, each querying a single status.
	 *
	 * @return string[] List of {@see NotificationStatus} values.
	 */
	public static function get_cancellable_statuses(): array {
		return array( NotificationStatus::PENDING, NotificationStatus::ACTIVE );
	}

	/**
	 * Check whether a notification can still be cancelled by the customer.
	 *
	 * @param Notification $notification The notification to check.
	 * @return bool True when the notification is in a cancellable status.
	 */
	public static function is_cancellable( Notification $notification ): bool {
		return in_array( (string) $notification->get_status(), self::get_cancellable_statuses(), true );
	}

	/**
	 * Notification management service, owns the resend-verification domain logic.
	 *
	 * @var NotificationManagementService
	 */
	private NotificationManagementService $notification_management_service;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_get_query_vars', array( $this, 'register_query_var' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'register_menu_item' ), 10, 2 );
		add_filter( 'woocommerce_endpoint_' . self::ENDPOINT . '_title', array( $this, 'filter_endpoint_title' ) );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( $this, 'render_endpoint' ) );
		add_action( 'template_redirect', array( $this, 'maybe_handle_action' ) );
	}

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NotificationManagementService $notification_management_service The notification management service.
	 */
	final public function init( NotificationManagementService $notification_management_service ): void {
		$this->notification_management_service = $notification_management_service;
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
		// Avoid parameter not used PHPCS errors.
		unset( $endpoints );

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
		// Avoid parameter not used PHPCS errors.
		unset( $title );
		return __( 'Stock notifications', 'woocommerce' );
	}

	/**
	 * Default page size when none is provided.
	 *
	 * @var int
	 */
	public const DEFAULT_PER_PAGE = 10;

	/**
	 * Product name to show as the row label.
	 *
	 * {@see Notification::get_product_name()} returns the variation name, which
	 * already carries the attributes ("Hoodie - Blue, Large"). The row renders
	 * those attributes separately underneath, so use the parent title here and
	 * let the variation list own them.
	 *
	 * @param Notification $notification The notification to label.
	 * @return string Product name, or an empty string when the product is gone.
	 */
	public static function get_display_product_name( Notification $notification ): string {
		$product = $notification->get_product();
		if ( ! $product ) {
			return '';
		}

		return (string) $product->get_title();
	}

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
		 * @since 11.2.0
		 *
		 * @param int $per_page Number of notifications shown per page. Default {@see self::DEFAULT_PER_PAGE}.
		 */
		$per_page = (int) apply_filters( 'woocommerce_account_customer_stock_notifications_per_page', self::DEFAULT_PER_PAGE );
		$per_page = max( 1, $per_page );

		/**
		 * Filter how many pending (awaiting confirmation) notifications the My Account
		 * stock-notifications tab lists above the active table.
		 *
		 * The pending table is not paginated, so this is a hard cap.
		 *
		 * @since 11.2.0
		 *
		 * @param int $limit Maximum number of pending notifications shown. Default {@see self::DEFAULT_PER_PAGE}.
		 */
		$pending_limit = (int) apply_filters( 'woocommerce_account_customer_stock_notifications_pending_limit', self::DEFAULT_PER_PAGE );
		$pending_limit = max( 1, $pending_limit );

		$pending = $this->get_current_user_pending_notifications( $pending_limit );
		$page    = $this->get_current_user_notifications_page( $current_page, $per_page );

		// Both lists come from raw SQL selects, so nothing has primed the post cache
		// for the products the rows render. Prime it once here instead of letting
		// each row's wc_get_product() call issue its own query.
		$product_ids = array_filter( array_map( static fn( $notification ) => (int) $notification->get_product_id(), array_merge( $pending, $page['notifications'] ) ) );
		if ( $product_ids ) {
			_prime_post_caches( array_values( array_unique( $product_ids ) ) );
		}

		\wc_get_template(
			'myaccount/stock-notifications.php',
			array(
				'notifications'         => $page['notifications'],
				'pending_notifications' => $pending,
				'has_pending'           => ! empty( $pending ),
				'has_items'             => ! empty( $pending ) || ! empty( $page['notifications'] ),
				'current_page'          => $page['current_page'],
				'total_pages'           => $page['total_pages'],
				'total_items'           => $page['total_items'],
				'per_page'              => $per_page,
			)
		);
	}

	/**
	 * Return the current user's pending (awaiting confirmation) notifications, newest first.
	 *
	 * Always scopes to `get_current_user_id()` — the caller is never trusted.
	 *
	 * @param int $limit Maximum number of notifications to return.
	 * @return array<Notification>
	 */
	public function get_current_user_pending_notifications( int $limit ): array {
		$limit = max( 1, $limit );

		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return array();
		}

		return NotificationQuery::get_notifications(
			array(
				'user_id'  => $user_id,
				'status'   => NotificationStatus::PENDING,
				'order_by' => array( 'id' => 'DESC' ),
				'return'   => 'objects',
				'limit'    => $limit,
			)
		);
	}

	/**
	 * Return one page of the current user's active notifications, newest first.
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

		// Only confirmed sign-ups. PENDING rows render in their own table above this
		// one ({@see self::get_current_user_pending_notifications()}); SENT and
		// CANCELLED rows are noise here, and merchants who want a full history can
		// build their own view via {@see NotificationQuery::get_notifications()}.
		$statuses = array( NotificationStatus::ACTIVE );

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
	 * Intercept a row-action link (resend or cancel) from the stock notifications tab.
	 *
	 * Guards, shared by every action:
	 * - Must be on the My Account > stock-notifications endpoint.
	 * - Must be authenticated.
	 * - Nonce must be scoped to the action and the notification id ({@see ::get_nonce_action()}).
	 * - The notification must exist and belong to the current user.
	 *
	 * Requests that aren't an action link, or that arrive anonymously, are dropped
	 * silently. Everything else redirects back to the clean endpoint URL with a notice,
	 * so the link never looks dead and a refresh can't replay it. A missing notification
	 * and one owned by someone else share the same error, so the response doesn't
	 * confirm whether the id exists.
	 */
	public function maybe_handle_action(): void {
		global $wp;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET[ self::ACTION_FIELD ] ) ) {
			return;
		}

		if ( ! isset( $wp->query_vars[ self::ENDPOINT ] ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_GET[ self::ACTION_FIELD ] ) );
		if ( ! in_array( $action, array( self::ACTION_RESEND, self::ACTION_CANCEL ), true ) ) {
			return;
		}

		$notification_id = isset( $_GET['notification_id'] ) ? absint( wp_unslash( $_GET['notification_id'] ) ) : 0;
		if ( $notification_id <= 0 ) {
			return;
		}

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! wp_verify_nonce( $nonce, self::get_nonce_action( $action, $notification_id ) ) ) {
			$this->redirect_with_error( __( 'This link has expired. Please reload the page and try again.', 'woocommerce' ) );
		}

		$notification = Factory::get_notification( $notification_id );
		if ( ! $notification instanceof Notification || (int) $notification->get_user_id() !== get_current_user_id() ) {
			$this->redirect_with_error( __( 'That back in stock notification no longer exists.', 'woocommerce' ) );
		}

		$result = self::ACTION_RESEND === $action
			? $this->resend( $notification )
			: $this->cancel( $notification );

		if ( is_wp_error( $result ) ) {
			$this->redirect_with_error( $result->get_error_message() );
		}

		\wc_add_notice( esc_html( $result ) );
		wp_safe_redirect( self::get_endpoint_url() );
		exit;
	}

	/**
	 * Send the verification email again for a pending notification.
	 *
	 * @param Notification $notification The notification, already checked to belong to the current user.
	 * @return string|\WP_Error Success notice text, or the error to show instead.
	 */
	private function resend( Notification $notification ) {
		$result = $this->notification_management_service->resend_verification_email( $notification );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		/* translators: %s: email address the verification email was sent to. */
		return sprintf( __( 'Verification email sent to %s.', 'woocommerce' ), $notification->get_user_email() );
	}

	/**
	 * Flip a pending or active notification to `cancelled` on the customer's behalf.
	 *
	 * @param Notification $notification The notification, already checked to belong to the current user.
	 * @return string|\WP_Error Success notice text, or the error to show instead.
	 */
	private function cancel( Notification $notification ) {
		if ( ! self::is_cancellable( $notification ) ) {
			return new \WP_Error( 'wc_bis_cancel_not_cancellable', __( 'That back in stock notification has already been cancelled.', 'woocommerce' ) );
		}

		$notification->set_status( NotificationStatus::CANCELLED );
		$notification->set_cancellation_source( NotificationCancellationSource::USER );
		$notification->set_date_cancelled( time() );

		$notification->save();

		// `WC_Data_Store::update()` drops the data store's return value, so a failed write
		// reaches us as a successful save. Read the row back to confirm it really changed.
		$saved = Factory::get_notification( $notification->get_id() );
		if ( ! $saved instanceof Notification || NotificationStatus::CANCELLED !== $saved->get_status() ) {
			return new \WP_Error( 'wc_bis_cancel_failed', __( 'We could not cancel that back in stock notification. Please try again.', 'woocommerce' ) );
		}

		$product_name = $notification->get_product_name();
		if ( '' === $product_name ) {
			return __( 'Back in stock notification cancelled.', 'woocommerce' );
		}

		/* translators: %s: product name */
		return sprintf( __( 'Back in stock notification for "%s" cancelled.', 'woocommerce' ), $product_name );
	}

	/**
	 * Queue an error notice and redirect back to the stock notifications endpoint.
	 *
	 * @param string $message The error to show the customer.
	 * @return never
	 */
	private function redirect_with_error( string $message ) {
		\wc_add_notice( esc_html( $message ), 'error' );
		wp_safe_redirect( self::get_endpoint_url() );
		exit;
	}

	/**
	 * Get the URL of the My Account > stock notifications endpoint.
	 *
	 * @return string
	 */
	public static function get_endpoint_url(): string {
		return \wc_get_endpoint_url( self::ENDPOINT, '', \wc_get_page_permalink( 'myaccount' ) );
	}
}
