<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal;

use Automattic\WooCommerce\Internal\POS\Service\POSApprovalService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Enforces WooCommerce capabilities on REST API endpoints.
 *
 * Hooks into REST API permission filters to check granular capabilities
 * such as refund_shop_orders for any user, regardless of their role.
 *
 * @internal
 * @since 10.8.0
 */
class CapabilityEnforcement implements RegisterHooksInterface {

	/**
	 * Capabilities that support approval-token overrides.
	 *
	 * @var string[]
	 */
	private const APPROVABLE_CAPABILITIES = array(
		'refund_shop_orders',
		'publish_shop_coupons',
	);

	/**
	 * Approval service instance.
	 *
	 * @var POSApprovalService
	 */
	private POSApprovalService $approval_service;

	/**
	 * Approval token extracted from the current request's POST body.
	 * Set before capability checks so the filter can use it without touching $_REQUEST.
	 *
	 * @var string
	 */
	private string $current_approval_token = '';

	/**
	 * The order ID from the current request, for approval scope validation.
	 *
	 * @var int
	 */
	private int $current_order_id = 0;

	/**
	 * The current REST route being evaluated.
	 *
	 * @var string
	 */
	private string $current_rest_route = '';

	/**
	 * The current REST request, captured before permission callbacks run.
	 *
	 * @var WP_REST_Request<array<string, mixed>>|null
	 */
	private ?WP_REST_Request $current_rest_request = null;

	/**
	 * The approval-token error (if any) recorded during the current permission check.
	 *
	 * Set when a token was present in the request but failed validation. Surfaced
	 * through the rest_request_before_callbacks filter so mobile clients can
	 * distinguish "manager-approved token expired" from "user lacks capability".
	 *
	 * @var WP_Error|null
	 */
	private ?WP_Error $approval_error = null;

	/**
	 * Initialize dependencies via the DI container.
	 *
	 * @internal
	 * @since 10.8.0
	 * @param POSApprovalService $approval_service Approval service instance.
	 */
	final public function init( POSApprovalService $approval_service ): void {
		$this->approval_service = $approval_service;
	}

	/**
	 * Register hooks and filters.
	 *
	 * @since 10.8.0
	 */
	public function register(): void {
		add_filter( 'woocommerce_rest_check_permissions', array( $this, 'enforce_capabilities' ), 10, 4 );
		add_filter( 'woocommerce_pos_capability_check', array( $this, 'check_approval_token' ), 10, 3 );
		add_filter( 'rest_pre_dispatch', array( $this, 'capture_current_rest_route' ), 10, 3 );
		add_filter( 'rest_request_before_callbacks', array( $this, 'enforce_route_access' ), 10, 3 );
		add_filter( 'rest_request_after_callbacks', array( $this, 'enforce_route_access' ), 10, 3 );
		add_filter( 'rest_post_dispatch', array( $this, 'filter_sensitive_report_data' ), 10, 3 );
	}

	/**
	 * Enforce capabilities on REST API permission checks.
	 *
	 * Hooks into the woocommerce_rest_check_permissions filter to deny access
	 * when users attempt actions they lack capabilities for.
	 *
	 * @since 10.8.0
	 *
	 * @param bool   $permission Whether the user has permission.
	 * @param string $context    Request context (read, create, edit, delete, batch).
	 * @param int    $object_id  Object ID.
	 * @param string $post_type  Post type or object type.
	 * @return bool
	 */
	public function enforce_capabilities(
		bool $permission,
		string $context,
		int $object_id,
		string $post_type
	): bool {
		$route = $this->get_current_rest_route();

		if ( $this->is_customer_route( $route ) ) {
			return $this->current_user_can_access_customer_route( $context );
		}

		// POS users need read-only access to payment gateways to determine
		// automatic refund support. The default check requires manage_woocommerce
		// which POS roles intentionally lack.
		if ( 'payment_gateways' === $post_type && 'read' === $context && current_user_can( 'view_pos' ) ) {
			return true;
		}

		// POS settings group: allow read with view_pos_settings and write with
		// edit_pos_settings. The default settings check requires manage_woocommerce
		// which pos_manager lacks by design. The carve-out is scoped to the
		// point-of-sale settings route so other settings groups stay gated.
		if ( 'settings' === $post_type && $this->is_pos_settings_route( $route ) ) {
			if ( 'read' === $context && current_user_can( 'view_pos_settings' ) ) {
				return true;
			}
			if ( in_array( $context, array( 'edit', 'create', 'delete', 'batch' ), true ) && current_user_can( 'edit_pos_settings' ) ) {
				return true;
			}
		}

		if ( 'shop_order_refund' === $post_type && 'create' === $context ) {
			$this->extract_approval_context_from_rest_request();
			return $this->user_has_capability( 'refund_shop_orders' );
		}

		if ( 'shop_coupon' === $post_type && 'create' === $context ) {
			$this->extract_approval_context_from_rest_request();
			return $this->user_has_capability( 'publish_shop_coupons' );
		}

		// HPOS uses a shop_order_placehold post type whose map_meta_cap resolves
		// to generic edit_posts instead of edit_shop_orders. POS roles have
		// edit_shop_orders but not edit_posts, so the base permission check
		// fails. Re-check against the WC-specific capability here.
		if ( ! $permission && 'shop_order' === $post_type && 'edit' === $context ) {
			return current_user_can( 'edit_shop_orders' );
		}

		if (
			! $permission
			&& 'edit' === $context
			&& in_array( $post_type, array( 'product', 'product_variation' ), true )
			&& $this->is_stock_adjustment_request()
		) {
			return current_user_can( 'edit_products' );
		}

		return $permission;
	}

	/**
	 * Restrict non-POS routes and report access for POS-only users.
	 *
	 * @since 10.8.0
	 *
	 * @param mixed           $response The pre-dispatch response.
	 * @param array           $handler  Route handler data.
	 * @param WP_REST_Request $request  Current request.
	 * @phpstan-param array<string, mixed> $handler
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return mixed
	 */
	public function enforce_route_access( $response, array $handler, WP_REST_Request $request ) {
		unset( $handler );

		// If the permission callback already denied the request and we recorded a
		// specific approval-token failure during the check, surface the structured
		// error so mobile clients can distinguish "token expired/invalid" from
		// "user lacks capability".
		if ( is_wp_error( $response ) && $this->approval_error instanceof WP_Error ) {
			$approval_error       = $this->approval_error;
			$this->approval_error = null;
			return $approval_error;
		}

		$route = $request->get_route();

		if ( ! $this->is_blocked_user_route( $route ) && ! $this->is_report_route( $route ) ) {
			return $response;
		}

		if ( ! is_user_logged_in() || ! current_user_can( 'view_pos' ) || current_user_can( 'manage_woocommerce' ) ) {
			return $response;
		}

		if ( $this->is_blocked_user_route( $route ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_view',
				__( 'Sorry, you cannot view this resource.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! current_user_can( 'view_pos' ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_view',
				__( 'Sorry, you cannot list resources.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return $response;
	}

	/**
	 * Remove financial-only report fields for users without financial report access.
	 *
	 * @since 10.8.0
	 *
	 * @param mixed           $response The REST response.
	 * @param WP_REST_Server  $server   REST server instance.
	 * @param WP_REST_Request $request  Current request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return mixed
	 */
	public function filter_sensitive_report_data( $response, WP_REST_Server $server, WP_REST_Request $request ) {
		unset( $server );

		if ( ! $response instanceof WP_REST_Response || ! $this->is_report_route( $request->get_route() ) ) {
			return $response;
		}

		if ( current_user_can( 'manage_woocommerce' ) ) {
			return $response;
		}

		$response->set_data( $this->strip_financial_fields( $response->get_data() ) );

		return $response;
	}

	/**
	 * Capture the current route before endpoint permission callbacks run.
	 *
	 * @since 10.8.0
	 *
	 * @param mixed           $result  The pre-dispatch response.
	 * @param WP_REST_Server  $server  REST server instance.
	 * @param WP_REST_Request $request Current request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return mixed
	 */
	public function capture_current_rest_route( $result, WP_REST_Server $server, WP_REST_Request $request ) {
		unset( $server );

		$this->current_rest_request = $request;
		$this->current_rest_route = $request->get_route();

		return $result;
	}

	/**
	 * Check if a valid approval token is present when a user lacks a capability.
	 *
	 * Hooks into the woocommerce_pos_capability_check filter. When the user does not
	 * have the required capability, reads `_pos_approval` from the request and validates
	 * the token via POSApprovalService. Adds an order note when the token is consumed
	 * on an order-related action.
	 *
	 * @since 10.8.0
	 *
	 * @param bool   $has_cap    Whether the user has the capability.
	 * @param string $capability The capability being checked.
	 * @param int    $user_id    The user ID.
	 * @return bool
	 */
	public function check_approval_token( bool $has_cap, string $capability, int $user_id ): bool {
		if ( $has_cap ) {
			return true;
		}

		if ( ! in_array( $capability, self::APPROVABLE_CAPABILITIES, true ) ) {
			return false;
		}

		if ( '' === $this->current_approval_token ) {
			return false;
		}

		$approval_data = $this->approval_service->validate_and_consume(
			$this->current_approval_token,
			$capability
		);
		if ( false === $approval_data ) {
			$reason               = $this->approval_service->get_last_failure_reason();
			$this->approval_error = $this->build_approval_error(
				POSApprovalService::FAILURE_ACTION_MISMATCH === $reason
					? 'woocommerce_pos_approval_action_mismatch'
					: 'woocommerce_pos_approval_invalid_or_expired'
			);
			return false;
		}

		// Validate the approval is scoped to the correct order if applicable.
		// Non-order-scoped approvals (e.g. coupon creation) skip this check.
		$approved_order_id = (int) ( $approval_data['context']['order_id'] ?? 0 );
		if ( $approved_order_id > 0 || $this->current_order_id > 0 ) {
			if ( $approved_order_id !== $this->current_order_id ) {
				$this->approval_error = $this->build_approval_error( 'woocommerce_pos_approval_order_mismatch' );
				return false;
			}
		}

		$this->log_approval_consumed( $approval_data, $capability, $user_id );
		$this->maybe_add_order_note( $approval_data, $capability, $user_id );

		// Clear token after consumption to prevent reuse within the same request.
		$this->current_approval_token = '';
		$this->approval_error         = null;

		return true;
	}

	/**
	 * Build a structured WP_Error describing an approval-token failure.
	 *
	 * @since 10.8.0
	 *
	 * @param string $code Error code to emit.
	 * @return WP_Error
	 */
	private function build_approval_error( string $code ): WP_Error {
		$messages = array(
			'woocommerce_pos_approval_invalid_or_expired' => __( 'The manager approval is invalid or has expired. Please request a new approval.', 'woocommerce' ),
			'woocommerce_pos_approval_action_mismatch'    => __( 'The manager approval does not cover this action.', 'woocommerce' ),
			'woocommerce_pos_approval_order_mismatch'     => __( 'The manager approval was not issued for this order.', 'woocommerce' ),
		);

		$message = $messages[ $code ] ?? $messages['woocommerce_pos_approval_invalid_or_expired'];

		return new WP_Error(
			$code,
			$message,
			array( 'status' => 401 )
		);
	}

	/**
	 * Log when an approval token is consumed.
	 *
	 * This is the universal audit trail for all POS overrides. Any capability
	 * added to APPROVABLE_CAPABILITIES is automatically logged here when the
	 * token is consumed, regardless of whether the action is order-scoped.
	 *
	 * Logs are visible in WooCommerce > Status > Logs under the
	 * woocommerce-pos source.
	 *
	 * @since 10.8.0
	 *
	 * @param array  $approval_data The approval data from POSApprovalService.
	 * @param string $capability    The capability that was granted.
	 * @param int    $user_id       The user who performed the action.
	 */
	private function log_approval_consumed( array $approval_data, string $capability, int $user_id ): void {
		$approver       = get_userdata( $approval_data['approver_id'] );
		$approver_label = $approver
			? sprintf( '%s (%s, ID %d)', $approver->display_name, $approver->user_login, $approval_data['approver_id'] )
			: sprintf( 'ID %d', $approval_data['approver_id'] );
		$actor          = get_userdata( $user_id );
		$actor_label    = $actor
			? sprintf( '%s (%s, ID %d)', $actor->display_name, $actor->user_login, $user_id )
			: sprintf( 'ID %d', $user_id );

		$message = sprintf(
			'POS override consumed: %s granted to %s, approved by %s.',
			$capability,
			$actor_label,
			$approver_label
		);

		$order_id = (int) ( $approval_data['context']['order_id'] ?? 0 );
		if ( $order_id > 0 ) {
			$message .= sprintf( ' Order #%d.', $order_id );
		}

		wc_get_logger()->info( $message, array( 'source' => 'woocommerce-pos' ) );
	}

	/**
	 * Add an order note when an approval token is consumed for an order-related action.
	 *
	 * @since 10.8.0
	 *
	 * @param array  $approval_data The approval data returned by POSApprovalService.
	 * @param string $capability    The capability that was checked.
	 * @param int    $user_id       The user performing the action.
	 */
	private function maybe_add_order_note( array $approval_data, string $capability, int $user_id ): void {
		$order_id = $approval_data['context']['order_id'] ?? 0;
		if ( empty( $order_id ) ) {
			return;
		}

		$order = wc_get_order( (int) $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$approver      = get_userdata( $approval_data['approver_id'] );
		$approver_name = $approver ? $approver->display_name : (string) $approval_data['approver_id'];
		$actor         = get_userdata( $user_id );
		$actor_name    = $actor ? $actor->display_name : (string) $user_id;

		$order->add_order_note(
			sprintf(
				/* translators: 1: capability name, 2: actor display name, 3: approver display name */
				__( 'POS override: %1$s granted to %2$s, approved by %3$s.', 'woocommerce' ),
				$capability,
				$actor_name,
				$approver_name
			)
		);
	}

	/**
	 * Extract approval token and order context from the current REST request.
	 *
	 * Used by enforce_capabilities() which receives the permission filter
	 * but not the WP_REST_Request directly. Prefers the captured REST request
	 * when available, falling back to POST body to prevent token leakage.
	 *
	 * @since 10.8.0
	 */
	private function extract_approval_context_from_rest_request(): void {
		$this->approval_error = null;

		// Prefer the request captured by capture_current_rest_route, but fall
		// back to rest_get_server()->get_current_request() so the token is still
		// recoverable if the rest_pre_dispatch capture did not fire or was
		// overridden. Mirrors the fallback chain in get_current_rest_route().
		$request = $this->current_rest_request instanceof WP_REST_Request
			? $this->current_rest_request
			: null;

		if ( null === $request ) {
			$server = rest_get_server();
			if ( $server && method_exists( $server, 'get_current_request' ) ) {
				$current = $server->get_current_request();
				if ( $current instanceof WP_REST_Request ) {
					$request = $current;
				}
			}
		}

		if ( $request instanceof WP_REST_Request ) {
			$this->current_approval_token = (string) $request->get_param( '_pos_approval' );
			$route                        = $request->get_route();
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$this->current_approval_token = isset( $_POST['_pos_approval'] )
				? sanitize_text_field( wp_unslash( $_POST['_pos_approval'] ) )
				: '';
			$route = $this->get_rest_route_from_request_uri();
		}

		// Extract order ID from the route (e.g., /orders/123/refunds).
		if ( preg_match( '#/orders/(\d+)/#', $route, $matches ) ) {
			$this->current_order_id = (int) $matches[1];
		}
	}

	/**
	 * Check whether the current user has a specific capability.
	 *
	 * Applies the woocommerce_pos_capability_check filter to allow
	 * temporary capability overrides.
	 *
	 * @since 10.8.0
	 *
	 * @param string $capability The capability to check.
	 * @return bool
	 */
	private function user_has_capability( string $capability ): bool {
		$user_id = get_current_user_id();
		$has_cap = current_user_can( $capability );

		/**
		 * Filters whether a user has a specific capability.
		 *
		 * This filter allows overriding capability checks, for example
		 * to grant temporary elevated permissions via approval tokens.
		 *
		 * @since 10.8.0
		 *
		 * @param bool   $has_cap    Whether the user has the capability.
		 * @param string $capability The capability being checked.
		 * @param int    $user_id    The user ID.
		 */
		return (bool) apply_filters( 'woocommerce_pos_capability_check', $has_cap, $capability, $user_id );
	}

	/**
	 * Return the current REST route, if available.
	 *
	 * @since 10.8.0
	 *
	 * @return string
	 */
	private function get_current_rest_route(): string {
		if ( '' !== $this->current_rest_route ) {
			return $this->current_rest_route;
		}

		if ( $this->current_rest_request instanceof WP_REST_Request ) {
			return $this->current_rest_request->get_route();
		}

		$server = rest_get_server();
		if ( ! $server || ! method_exists( $server, 'get_current_request' ) ) {
			return $this->get_rest_route_from_request_uri();
		}

		$request = $server->get_current_request();

		if ( $request instanceof WP_REST_Request ) {
			return $request->get_route();
		}

		return $this->get_rest_route_from_request_uri();
	}

	/**
	 * Check whether the current route is a customer REST API route.
	 *
	 * @since 10.8.0
	 *
	 * @param string $route REST route.
	 * @return bool
	 */
	private function is_customer_route( string $route ): bool {
		return 1 === preg_match( '#^/wc/v\d+/customers(?:/|$)#', $route );
	}

	/**
	 * Check whether the current route targets the point-of-sale settings group.
	 *
	 * Matches `/wc/v{n}/settings/point-of-sale` and its sub-routes so POS roles
	 * with view_pos_settings / edit_pos_settings can access their settings
	 * without needing the broader manage_woocommerce capability.
	 *
	 * @since 10.8.0
	 *
	 * @param string $route REST route.
	 * @return bool
	 */
	private function is_pos_settings_route( string $route ): bool {
		return 1 === preg_match( '#^/wc/v\d+/settings/point-of-sale(?:/|$)#', $route );
	}

	/**
	 * Check whether the current route is a report or analytics route.
	 *
	 * @since 10.8.0
	 *
	 * @param string $route REST route.
	 * @return bool
	 */
	private function is_report_route( string $route ): bool {
		return 1 === preg_match( '#^/(wc-analytics|wc-admin|wc/v\d+/reports)(?:/|$)#', $route );
	}

	/**
	 * Check whether the route should be blocked for POS-only users.
	 *
	 * @since 10.8.0
	 *
	 * @param string $route REST route.
	 * @return bool
	 */
	private function is_blocked_user_route( string $route ): bool {
		if ( '/wp/v2/users/me' === $route ) {
			return false;
		}

		// Block the users collection, individual users, and user sub-resources
		// (e.g. /wp/v2/users/{id}/application-passwords) so POS-only roles
		// cannot list or mint credentials for other users.
		return 1 === preg_match( '#^/wp/v2/users(?:/\\d+(?:/.+)?)?$#', $route );
	}

	/**
	 * Check whether the current user can access a customer route.
	 *
	 * POS users need customer access to look up returning customers at the
	 * register and attach them to orders. The `/wc/v3/customers` controller
	 * already filters results to the `customer` role only, so there is no
	 * risk of exposing admin or staff users.
	 *
	 * @since 10.8.0
	 *
	 * @param string $context Permission context (read, create, edit, delete, batch).
	 * @return bool
	 */
	private function current_user_can_access_customer_route( string $context ): bool {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		if ( 'read' === $context ) {
			return current_user_can( 'view_pos' );
		}

		if ( 'create' === $context ) {
			return current_user_can( 'create_customers' );
		}

		return false;
	}

	/**
	 * Check whether the current request is a stock-only product update.
	 *
	 * @since 10.8.0
	 *
	 * @return bool
	 */
	private function is_stock_adjustment_request(): bool {
		$request = $this->current_rest_request;
		if ( ! $request instanceof WP_REST_Request ) {
			$server = rest_get_server();
			if ( ! $server || ! method_exists( $server, 'get_current_request' ) ) {
				return false;
			}

			$request = $server->get_current_request();
		}

		if ( ! $request instanceof WP_REST_Request ) {
			return false;
		}

		if ( ! in_array( $request->get_method(), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			return false;
		}

		$route = $request->get_route();
		if ( 1 !== preg_match( '#^/wc/v[34]/products(?:/\\d+)?(?:/variations/\\d+)?$#', $route ) ) {
			return false;
		}

		$params = $request->get_json_params();
		if ( ! is_array( $params ) || empty( $params ) ) {
			$params = $request->get_body_params();
		}
		if ( ! is_array( $params ) ) {
			return false;
		}

		$allowed_keys = array(
			'id',
			'manage_stock',
			'stock_quantity',
			'stock_status',
			'backorders',
			'low_stock_amount',
		);

		$stock_keys_found = false;

		foreach ( array_keys( $params ) as $key ) {
			if ( in_array( $key, array( 'manage_stock', 'stock_quantity', 'stock_status', 'backorders', 'low_stock_amount' ), true ) ) {
				$stock_keys_found = true;
			}

			if ( ! in_array( $key, $allowed_keys, true ) ) {
				return false;
			}
		}

		return $stock_keys_found;
	}

	/**
	 * Recursively remove financial-only fields from response data.
	 *
	 * @since 10.8.0
	 *
	 * @param mixed $data Response data.
	 * @return mixed
	 */
	private function strip_financial_fields( $data ) {
		$blocked_keys = array(
			'cost_of_goods_sold',
			'cogs_total_value',
			'total_cogs',
			'gross_profit',
			'gross_margin',
			'net_profit',
			'profit_margin',
		);

		if ( ! is_array( $data ) ) {
			return $data;
		}

		foreach ( $blocked_keys as $key ) {
			unset( $data[ $key ] );
		}

		foreach ( $data as $key => $value ) {
			$data[ $key ] = $this->strip_financial_fields( $value );
		}

		return $data;
	}

	/**
	 * Parse the current REST route from REQUEST_URI when the REST server has not exposed it yet.
	 *
	 * @since 10.8.0
	 *
	 * @return string
	 */
	private function get_rest_route_from_request_uri(): string {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		if ( ! is_string( $request_uri ) || '' === $request_uri ) {
			return '';
		}

		$parsed_request_uri = wp_parse_url( $request_uri );
		if ( ! is_array( $parsed_request_uri ) ) {
			return '';
		}

		if ( isset( $parsed_request_uri['query'] ) && is_string( $parsed_request_uri['query'] ) ) {
			parse_str( $parsed_request_uri['query'], $query_args );
			if ( isset( $query_args['rest_route'] ) && is_string( $query_args['rest_route'] ) ) {
				return wp_unslash( $query_args['rest_route'] );
			}
		}

		$path = isset( $parsed_request_uri['path'] ) && is_string( $parsed_request_uri['path'] ) ? rawurldecode( $parsed_request_uri['path'] ) : '';
		if ( '' === $path ) {
			return '';
		}

		$rest_prefix = '/' . rest_get_url_prefix() . '/';
		$prefix_pos  = strpos( $path, $rest_prefix );
		if ( false === $prefix_pos ) {
			return '';
		}

		return '/' . ltrim( substr( $path, $prefix_pos + strlen( $rest_prefix ) ), '/' );
	}
}
