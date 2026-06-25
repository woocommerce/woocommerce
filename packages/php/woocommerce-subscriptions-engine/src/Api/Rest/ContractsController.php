<?php
/**
 * ContractsController - the authenticated `wc/v3` REST surface for the customer
 * portal's contract reads and lifecycle actions.
 *
 * Routes (namespace `wc/v3`, base `subscriptions-engine/contracts`):
 *
 *   GET  /                       Customer-scoped list of the requester's contracts.
 *                                Query: `page`, `per_page`. Returns list-row view-models.
 *   GET  /{id}                   One contract's detail view-model (with related orders).
 *   POST /{id}/hold              Put an active contract on hold.
 *   POST /{id}/reactivate        Resume a held contract (next date recomputed forward).
 *   POST /{id}/cancel            Cancel. Body `{ at_period_end: bool }` - true winds the
 *                                contract down at the current period end, false cancels now.
 *
 * Every route requires a logged-in user (cookie auth) and the REST nonce (`wp_rest`),
 * enforced by core's cookie auth + this controller's `permission_callback`. Per-route,
 * the `{id}` routes additionally enforce per-contract OWNERSHIP with the asymmetric
 * not-found rule: a contract owned by another user returns 404 - IDENTICAL to an unknown
 * id - so the portal never confirms the existence of a contract the requester does not
 * own (anti-IDOR). The list route is inherently owner-scoped (it only ever reads the
 * requester's own customer id).
 *
 * The controller is a thin transport shell: it delegates every read and action to the
 * public {@see Subscriptions} facade (never the repository or services directly) and maps
 * the returned {@see Contract} entities to the response shape through {@see ContractPresenter}.
 * The view-model is ADDITIVE: new fields may appear; consumers tolerate unknown fields and
 * must not assume the set is closed.
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

defined( 'ABSPATH' ) || exit;

/**
 * REST controller for customer-portal contract reads + lifecycle actions.
 */
final class ContractsController extends WP_REST_Controller {

	/**
	 * Default page size when none is given.
	 */
	private const DEFAULT_LIMIT = 50;

	/**
	 * Largest page a single read returns, so a hostile or buggy caller cannot ask for an
	 * unbounded result set.
	 */
	private const MAX_LIMIT = 200;

	/**
	 * REST namespace - the WooCommerce v3 namespace, so the routes sit alongside the rest
	 * of the store's authenticated API.
	 */
	private const REST_NAMESPACE = 'wc/v3';

	/**
	 * Route base under the namespace.
	 */
	private const REST_BASE = 'subscriptions-engine/contracts';

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	protected $namespace = self::REST_NAMESPACE;

	/**
	 * Route base under the namespace.
	 *
	 * @var string
	 */
	protected $rest_base = self::REST_BASE;

	/**
	 * Presenter mapping contracts to the response view-model.
	 *
	 * @var ContractPresenter
	 */
	private $presenter;

	/**
	 * Build the controller.
	 *
	 * @param ContractPresenter|null $presenter Presenter; default instance when omitted.
	 */
	public function __construct( ?ContractPresenter $presenter = null ) {
		$this->presenter = $presenter ?? new ContractPresenter();
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
					'permission_callback' => array( $this, 'logged_in_permission' ),
					'args'                => $this->list_args(),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'logged_in_permission' ),
					'args'                => $this->id_arg(),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE . '/(?P<id>[\d]+)/hold',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'hold_item' ),
					'permission_callback' => array( $this, 'logged_in_permission' ),
					'args'                => $this->id_arg(),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE . '/(?P<id>[\d]+)/reactivate',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'reactivate_item' ),
					'permission_callback' => array( $this, 'logged_in_permission' ),
					'args'                => $this->id_arg(),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE . '/(?P<id>[\d]+)/cancel',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cancel_item' ),
					'permission_callback' => array( $this, 'logged_in_permission' ),
					'args'                => array_merge(
						$this->id_arg(),
						array(
							'at_period_end' => array(
								'description'       => __( 'Whether to cancel at the end of the current billing period (true) or immediately (false).', 'woocommerce-subscriptions-engine' ),
								'type'              => 'boolean',
								'required'          => false,
								'default'           => true,
								'sanitize_callback' => 'rest_sanitize_boolean',
							),
						)
					),
				),
			)
		);
	}

	/**
	 * Permission callback: a logged-in user. Core's cookie auth has already verified the
	 * REST nonce (`wp_rest`) for a cookie-authenticated request by the time this runs, so
	 * a missing/invalid nonce surfaces as a 401/403 before here; this guard rejects the
	 * anonymous case with a 401.
	 *
	 * @return true|WP_Error True when logged in, else a 401 error.
	 */
	public function logged_in_permission() {
		if ( 0 === get_current_user_id() ) {
			return new WP_Error(
				'woocommerce_subscriptions_engine_not_logged_in',
				__( 'You must be logged in to access subscriptions.', 'woocommerce-subscriptions-engine' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * GET / - the requester's contracts as list-row view-models.
	 *
	 * Owner-scoped by construction: the customer id is the current user's, never a
	 * request parameter, so there is nothing to IDOR.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ): WP_REST_Response {
		$customer_id = get_current_user_id();

		$per_page = $this->clamp_limit( ScalarCoercion::coerce_int( $request['per_page'] ) );
		$page     = max( 1, ScalarCoercion::coerce_int( $request['page'] ) );
		$offset   = ( $page - 1 ) * $per_page;

		$rows = array();
		foreach ( Subscriptions::list_for_customer( $customer_id, $per_page, $offset ) as $contract ) {
			$rows[] = $this->presenter->build_row( $contract );
		}

		return rest_ensure_response( $rows );
	}

	/**
	 * GET /{id} - one contract's detail view-model, ownership-checked.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error The detail view-model, or a 404 (asymmetric).
	 */
	public function get_item( $request ) {
		$contract = $this->owned_contract( ScalarCoercion::coerce_int( $request['id'] ) );
		if ( $contract instanceof WP_Error ) {
			return $contract;
		}

		return rest_ensure_response( $this->presenter->build_detail( $contract ) );
	}

	/**
	 * POST /{id}/hold.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error The refreshed detail view-model, or an error.
	 */
	public function hold_item( $request ) {
		return $this->run_action(
			ScalarCoercion::coerce_int( $request['id'] ),
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
			ScalarCoercion::coerce_int( $request['id'] ),
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
		// The `at_period_end` arg is boolean-typed + `rest_sanitize_boolean`-sanitized in
		// the route schema, so it arrives as a real bool; coerce defensively through the
		// scalar-safe string helper for the case it is supplied raw.
		$at_period_end = rest_sanitize_boolean( ScalarCoercion::coerce_string( $request['at_period_end'], 'true' ) );

		return $this->run_action(
			ScalarCoercion::coerce_int( $request['id'] ),
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
	 * Run a lifecycle action behind the ownership guard, then return the refreshed detail
	 * view-model so the client can re-render from one response.
	 *
	 * A `DomainException` (an illegal transition for the contract's current state) maps to
	 * a 409 Conflict; any other failure maps to a 500. The ownership guard keeps the
	 * asymmetric 404 for not-owned / unknown.
	 *
	 * @param int      $contract_id The contract id.
	 * @param callable $action      Runs the lifecycle action; receives the contract id.
	 * @return WP_REST_Response|WP_Error
	 */
	private function run_action( int $contract_id, callable $action ) {
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
			return $this->not_found();
		}

		return rest_ensure_response( $this->presenter->build_detail( $refreshed ) );
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
			return $this->not_found();
		}

		return $contract;
	}

	/**
	 * The shared 404, identical for unknown and not-owned contracts.
	 */
	private function not_found(): WP_Error {
		return new WP_Error(
			'woocommerce_subscriptions_engine_contract_not_found',
			__( 'Subscription not found.', 'woocommerce-subscriptions-engine' ),
			array( 'status' => 404 )
		);
	}

	/**
	 * Clamp a requested page size into the allowed 1..MAX_LIMIT band.
	 *
	 * @param int $limit Requested limit.
	 */
	private function clamp_limit( int $limit ): int {
		if ( $limit < 1 ) {
			return self::DEFAULT_LIMIT;
		}

		return min( $limit, self::MAX_LIMIT );
	}

	/**
	 * Arg schema for the list route.
	 *
	 * @return array<string, mixed>
	 */
	private function list_args(): array {
		return array(
			'page'     => array(
				'description'       => __( 'Current page of the collection.', 'woocommerce-subscriptions-engine' ),
				'type'              => 'integer',
				'required'          => false,
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'description'       => __( 'Maximum number of subscriptions to return per page.', 'woocommerce-subscriptions-engine' ),
				'type'              => 'integer',
				'required'          => false,
				'default'           => self::DEFAULT_LIMIT,
				'minimum'           => 1,
				'maximum'           => self::MAX_LIMIT,
				'sanitize_callback' => 'absint',
			),
		);
	}

	/**
	 * Arg schema for the `{id}` path parameter.
	 *
	 * @return array<string, mixed>
	 */
	private function id_arg(): array {
		return array(
			'id' => array(
				'description'       => __( 'Unique identifier for the subscription contract.', 'woocommerce-subscriptions-engine' ),
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
		);
	}
}
