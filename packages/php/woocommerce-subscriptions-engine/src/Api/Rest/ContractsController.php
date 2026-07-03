<?php
/**
 * ContractsController - the authenticated `wc/v3` REST surface for the customer
 * portal's contract reads and lifecycle actions.
 *
 * Routes (namespace `wc/v3`, base `subscriptions-engine/contracts`):
 *
 *   GET  /                       Customer-scoped list of the requester's contracts.
 *                                Query: `page`, `per_page`, `context`. Returns items in
 *                                the `embed` (list-row) representation by default, with
 *                                `X-WP-Total` / `X-WP-TotalPages` collection headers.
 *   GET  /{id}                   One contract in the `view` (detail) representation,
 *                                with related orders.
 *   POST /{id}/hold              Put an active contract on hold.
 *   POST /{id}/reactivate        Resume a held contract (next date recomputed forward).
 *   POST /{id}/cancel            Cancel. Body `{ at_period_end: bool }` - true winds the
 *                                contract down at the current period end, false cancels now.
 *
 * Every route requires a logged-in user, enforced through the shared
 * {@see RESTPermissions} floor (core's cookie auth has already verified the REST nonce
 * `wp_rest` by then). Per-route, the `{id}` routes additionally enforce per-contract
 * OWNERSHIP with the asymmetric not-found rule: a contract owned by another user returns
 * 404 - IDENTICAL to an unknown id - so the portal never confirms the existence of a
 * contract the requester does not own (anti-IDOR). The list route is inherently
 * owner-scoped (it only ever reads the requester's own customer id).
 *
 * The controller is a thin transport shell: it delegates every read and action to the
 * public {@see Subscriptions} facade (never the repository or services directly) and maps
 * the returned {@see Contract} entities to the response shape through {@see ContractPresenter},
 * behind the standard {@see WP_REST_Controller} machinery (item schema, context filtering,
 * schema-driven arg validation). The view-model is ADDITIVE: new fields may appear;
 * consumers tolerate unknown fields and must not assume the set is closed.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api\Rest
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api\Rest;

use DomainException;
use Throwable;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Subscriptions;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ScalarCoercion;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Support\RESTPermissions;

defined( 'ABSPATH' ) || exit;

/**
 * REST controller for customer-portal contract reads + lifecycle actions.
 */
final class ContractsController extends WP_REST_Controller {

	private const REST_NAMESPACE = 'wc/v3';

	private const REST_BASE = 'subscriptions-engine/contracts';

	private const DEFAULT_PER_PAGE = 50;

	private const MAX_PER_PAGE = 200;

	/**
	 * Presenter mapping contracts to the response view-model.
	 *
	 * @var ContractPresenter
	 */
	private $presenter;

	/**
	 * REST permissions.
	 *
	 * @var RESTPermissions
	 */
	private $rest_permissions;

	/**
	 * Build the controller.
	 *
	 * @param ContractPresenter|null $presenter        Presenter; default instance when omitted.
	 * @param RESTPermissions|null   $rest_permissions REST permissions; default instance when omitted.
	 */
	public function __construct( ?ContractPresenter $presenter = null, ?RESTPermissions $rest_permissions = null ) {
		$this->namespace        = self::REST_NAMESPACE;
		$this->rest_base        = self::REST_BASE;
		$this->presenter        = $presenter ?? new ContractPresenter();
		$this->rest_permissions = $rest_permissions ?? new RESTPermissions();
	}

	/**
	 * Wire route registration.
	 */
	public static function register_hooks(): void {
		add_action(
			'rest_api_init',
			static function (): void {
				( new self() )->register_routes();
			}
		);
	}

	/**
	 * Register the routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE . '/(?P<id>[\d]+)',
			array(
				'args'   => $this->id_arg(),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE . '/(?P<id>[\d]+)/hold',
			array(
				'args'   => $this->id_arg(),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'hold_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE . '/(?P<id>[\d]+)/reactivate',
			array(
				'args'   => $this->id_arg(),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'reactivate_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE . '/(?P<id>[\d]+)/cancel',
			array(
				'args'   => $this->id_arg(),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cancel_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'at_period_end' => array(
							'description'       => __( 'Whether to cancel at the end of the current billing period (true) or immediately (false).', 'woocommerce-subscriptions-engine' ),
							'type'              => 'boolean',
							'required'          => false,
							'default'           => true,
							'sanitize_callback' => 'rest_sanitize_boolean',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Permission callback for all routes: the shared logged-in floor.
	 *
	 * Any logged-in user passes; per-contract ownership is enforced by the route
	 * handlers (the asymmetric 404), and the list is owner-scoped by construction.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error True when logged in, else a 401 error.
	 */
	public function permissions_check( $request ) {
		return $this->rest_permissions->require_logged_in_permission();
	}

	/**
	 * GET / - the requester's contracts, in the `embed` (list-row) representation by
	 * default, with collection totals headers.
	 *
	 * Owner-scoped by construction: the customer id is the current user's, never a
	 * request parameter, so there is nothing to IDOR.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ): WP_REST_Response {
		$customer_id = get_current_user_id();

		$page     = max( 1, ScalarCoercion::coerce_int( $request->get_param( 'page' ), 1 ) );
		$per_page = ScalarCoercion::coerce_int( $request->get_param( 'per_page' ), self::DEFAULT_PER_PAGE );
		$per_page = min( max( 1, $per_page ), self::MAX_PER_PAGE );
		$offset   = ( $page - 1 ) * $per_page;

		$rows = array();
		foreach ( Subscriptions::list_for_customer( $customer_id, $per_page, $offset ) as $contract ) {
			$rows[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( $contract, $request ) );
		}

		$total = Subscriptions::count_for_customer( $customer_id );

		$response = new WP_REST_Response( $rows );
		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) (int) ceil( $total / $per_page ) );

		return $response;
	}

	/**
	 * GET /{id} - one contract in the `view` (detail) representation, ownership-checked.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error The detail view-model, or a 404 (asymmetric).
	 */
	public function get_item( $request ) {
		$contract = $this->owned_contract( ScalarCoercion::coerce_int( $request->get_param( 'id' ) ) );
		if ( $contract instanceof WP_Error ) {
			return $contract;
		}

		return $this->prepare_item_for_response( $contract, $request );
	}

	/**
	 * POST /{id}/hold.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error The refreshed detail view-model, or an error.
	 */
	public function hold_item( $request ) {
		return $this->run_action(
			$request,
			static function ( int $id ): void {
				Subscriptions::hold( $id );
			}
		);
	}

	/**
	 * POST /{id}/reactivate.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error The refreshed detail view-model, or an error.
	 */
	public function reactivate_item( $request ) {
		return $this->run_action(
			$request,
			static function ( int $id ): void {
				Subscriptions::reactivate( $id );
			}
		);
	}

	/**
	 * POST /{id}/cancel - body `{ at_period_end: bool }` (default true).
	 *
	 * `at_period_end` true winds the contract down at the current period end (graceful);
	 * false cancels immediately, reusing the shared {@see Subscriptions::cancel()}.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error The refreshed detail view-model, or an error.
	 */
	public function cancel_item( $request ) {
		// Boolean-typed, defaulted, and `rest_sanitize_boolean`-sanitized by the route
		// schema, so it arrives as a real bool; the coercion path covers a caller
		// invoking the method directly with a raw value.
		$param         = $request->get_param( 'at_period_end' );
		$at_period_end = is_bool( $param ) ? $param : rest_sanitize_boolean( ScalarCoercion::coerce_string( $param, 'true' ) );

		return $this->run_action(
			$request,
			static function ( int $id ) use ( $at_period_end ): void {
				if ( $at_period_end ) {
					Subscriptions::cancel_at_period_end( $id );
				} else {
					Subscriptions::cancel( $id );
				}
			}
		);
	}

	/**
	 * Serialize a contract through the presenter, honouring the requested context.
	 *
	 * `embed` (the list default) maps to the lightweight list-row view-model; `view`
	 * (the detail default) maps to the full detail view-model, whose related-orders
	 * read is deliberately kept off the list path. The result then flows through the
	 * standard additional-fields + context filter, so registered REST fields and the
	 * schema's per-property contexts apply.
	 *
	 * @param Contract        $item    Contract.
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$context = ScalarCoercion::coerce_string( $request->get_param( 'context' ), 'view' );
		$context = '' !== $context ? $context : 'view';

		$data = 'embed' === $context
			? $this->presenter->build_row( $item )
			: $this->presenter->build_detail( $item );

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		return rest_ensure_response( $data );
	}

	/**
	 * Get collection params.
	 *
	 * The `context` default is `embed`: the list intentionally serves the lightweight
	 * row representation, keeping the detail-only reads (related orders) off the
	 * collection path.
	 *
	 * @return array<string, mixed>
	 */
	public function get_collection_params(): array {
		return array(
			'page'     => array(
				'description'       => __( 'Current page of the collection.', 'woocommerce-subscriptions-engine' ),
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'per_page' => array(
				'description'       => __( 'Maximum number of items to be returned in result set.', 'woocommerce-subscriptions-engine' ),
				'type'              => 'integer',
				'default'           => self::DEFAULT_PER_PAGE,
				'minimum'           => 1,
				'maximum'           => self::MAX_PER_PAGE,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'context'  => $this->get_context_param( array( 'default' => 'embed' ) ),
		);
	}

	/**
	 * Get item schema.
	 *
	 * One resource, two representations: `embed` is the list row, `view` is the detail.
	 * Properties carry their contexts accordingly, so the context filter documents (and
	 * enforces) which fields each representation serves.
	 *
	 * @return array<string, mixed>
	 */
	public function get_item_schema(): array {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'subscription_engine_contract',
			'type'       => 'object',
			'properties' => array(
				'id'                     => array(
					'description' => __( 'Unique identifier for the subscription contract.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'status'                 => array(
					'description' => __( 'Contract status.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'status_label'           => array(
					'description' => __( 'Localized status label.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'payment_method_title'   => array(
					'description' => __( 'Payment method display title.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'next_payment'           => array(
					'description' => __( 'Formatted next payment date for the list row.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'string',
					'context'     => array( 'embed' ),
					'readonly'    => true,
				),
				'total'                  => array(
					'description' => __( 'Formatted recurring total for the list row.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'string',
					'context'     => array( 'embed' ),
					'readonly'    => true,
				),
				'recurring_summary'      => array(
					'description' => __( 'Formatted recurring price and cadence summary.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'start_date'             => array(
					'description' => __( 'Formatted contract start date.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'last_order_date'        => array(
					'description' => __( 'Formatted date of the most recent related order.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'date_row_label'         => array(
					'description' => __( 'Label for the status-dependent date row.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'date_row_value'         => array(
					'description' => __( 'Value for the status-dependent date row.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'payment_method_expires' => array(
					'description' => __( 'Payment method expiry note, when known.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'cancel_visible'         => array(
					'description' => __( 'Whether the cancel action is available.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'hold_visible'           => array(
					'description' => __( 'Whether the hold action is available.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'reactivate_visible'     => array(
					'description' => __( 'Whether the reactivate action is available.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'needs_payment_notice'   => array(
					'description' => __( 'Whether the payment method needs updating before the contract can resume.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'at_period_end'          => array(
					'description' => __( 'Whether a cancel request defaults to winding down at the period end.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'cancel_modal_copy'      => array(
					'description' => __( 'Status-dependent copy for the cancel confirmation.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'related_orders'         => array(
					'description' => __( 'Orders related to the contract, newest first.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Run a lifecycle action behind the ownership guard, then return the refreshed detail
	 * view-model so the client can re-render from one response.
	 *
	 * A `DomainException` (an illegal transition for the contract's current state) maps to
	 * a 409 Conflict; any other failure maps to a 500. The ownership guard keeps the
	 * asymmetric 404 for not-owned / unknown.
	 *
	 * @param WP_REST_Request $request The request (carries the id; context resolves to `view`).
	 * @param callable        $action  Runs the lifecycle action; receives the contract id.
	 * @return WP_REST_Response|WP_Error
	 */
	private function run_action( WP_REST_Request $request, callable $action ) {
		$contract_id = ScalarCoercion::coerce_int( $request->get_param( 'id' ) );
		$customer_id = get_current_user_id();

		// Guard ownership before acting: unknown and foreign are both 404.
		$contract = $this->owned_contract( $contract_id );
		if ( $contract instanceof WP_Error ) {
			return $contract;
		}

		try {
			$action( $contract_id );
		} catch ( DomainException $e ) {
			return new WP_Error(
				'woocommerce_subscriptions_engine_illegal_transition',
				__( 'That action is not available for this subscription right now.', 'woocommerce-subscriptions-engine' ),
				array( 'status' => 409 )
			);
		} catch ( Throwable $e ) {
			return new WP_Error(
				'woocommerce_subscriptions_engine_action_failed',
				__( 'The subscription could not be updated. Please try again.', 'woocommerce-subscriptions-engine' ),
				array( 'status' => 500 )
			);
		}

		$refreshed = Subscriptions::get_for_customer( $contract_id, $customer_id );
		if ( null === $refreshed ) {
			// Raced away between the action and the re-read - treat as not found.
			return $this->not_found_error();
		}

		return $this->prepare_item_for_response( $refreshed, $request );
	}

	/**
	 * Load the contract `$contract_id` when the current user owns it, else a 404.
	 *
	 * Returns the asymmetric not-found (unknown id and foreign-owned are indistinguishable)
	 * via the facade's {@see Subscriptions::get_for_customer()}, so a logged-in user cannot
	 * probe for the existence of contracts they do not own.
	 *
	 * @param int $contract_id Contract id.
	 * @return Contract|WP_Error The owned contract, or a 404.
	 */
	private function owned_contract( int $contract_id ) {
		$contract = Subscriptions::get_for_customer( $contract_id, get_current_user_id() );
		if ( null === $contract ) {
			return $this->not_found_error();
		}

		return $contract;
	}

	/**
	 * The shared 404, identical for unknown and not-owned contracts.
	 */
	private function not_found_error(): WP_Error {
		return new WP_Error(
			'woocommerce_subscriptions_engine_contract_not_found',
			__( 'Subscription not found.', 'woocommerce-subscriptions-engine' ),
			array( 'status' => 404 )
		);
	}

	/**
	 * Route-level arg schema for the `{id}` path parameter.
	 *
	 * @return array<string, mixed>
	 */
	private function id_arg(): array {
		return array(
			'id' => array(
				'description'       => __( 'Unique identifier for the subscription contract.', 'woocommerce-subscriptions-engine' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}
}
