<?php
/**
 * ContractsController - the authenticated `wc/v3` REST surface for the generic
 * contract lifecycle actions.
 *
 * Routes (namespace `wc/v3`, base `subscriptions-engine/contracts`):
 *
 *   POST /{id}/hold              Put an active contract on hold.
 *   POST /{id}/reactivate        Resume a held contract (next date recomputed forward).
 *   POST /{id}/cancel            Cancel. Body `{ at_period_end: bool }` - true winds the
 *                                contract down at the current period end, false cancels now.
 *
 * Actions only, deliberately: the engine exposes ONE implementation of the lifecycle
 * transitions (guards, ownership, conflict semantics) that every consumer calls rather
 * than re-implements - while READS for UI stay server-side, where each consumer shapes
 * its own view from the {@see Subscriptions} facade. There are no read routes here and
 * no view-model in the responses: an action responds with a minimal domain summary
 * (`id` + resulting `status` slug, e.g. cancel lands on `pending-cancellation` or
 * `cancelled` depending on the mode), so no consumer-specific presentation leaks into
 * the engine. The summary is ADDITIVE: fields may appear; consumers tolerate unknown
 * fields and must not assume the set is closed. A generic resource read API is a
 * planned follow-up alongside the read-model views, when a consumer needs it.
 *
 * Every route requires a logged-in user, enforced through the shared
 * {@see RESTPermissions} floor (core's cookie auth has already verified the REST nonce
 * `wp_rest` by then). Per-route, ownership is enforced with the asymmetric not-found
 * rule: a contract owned by another user returns 404 - IDENTICAL to an unknown id - so
 * a caller never confirms the existence of a contract the requester does not own
 * (anti-IDOR).
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
 * REST controller for the generic contract lifecycle actions.
 */
final class ContractsController extends WP_REST_Controller {

	private const REST_NAMESPACE = 'wc/v3';

	private const REST_BASE = 'subscriptions-engine/contracts';

	/**
	 * REST permissions.
	 *
	 * @var RESTPermissions
	 */
	private $rest_permissions;

	/**
	 * Build the controller.
	 *
	 * @param RESTPermissions|null $rest_permissions REST permissions; default instance when omitted.
	 */
	public function __construct( ?RESTPermissions $rest_permissions = null ) {
		$this->namespace        = self::REST_NAMESPACE;
		$this->rest_base        = self::REST_BASE;
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
	 * handlers (the asymmetric 404).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error True when logged in, else a 401 error.
	 */
	public function permissions_check( $request ) {
		return $this->rest_permissions->require_logged_in_permission();
	}

	/**
	 * POST /{id}/hold.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error The domain summary, or an error.
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
	 * @return WP_REST_Response|WP_Error The domain summary, or an error.
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
	 * @return WP_REST_Response|WP_Error The domain summary, or an error.
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
	 * Serialize a contract as the action-response domain summary.
	 *
	 * Domain values only - the id and the resulting status slug - never labels,
	 * formatted values, or other presentation: consumers own their view shaping.
	 *
	 * @param Contract        $item    Contract.
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = array(
			'id'     => (int) $item->get_id(),
			'status' => $item->get_status(),
		);

		$data = $this->add_additional_fields_to_object( $data, $request );

		return rest_ensure_response( $data );
	}

	/**
	 * Get item schema: the action-response domain summary.
	 *
	 * @return array<string, mixed>
	 */
	public function get_item_schema(): array {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'subscription_engine_contract_action',
			'type'       => 'object',
			'properties' => array(
				'id'     => array(
					'description' => __( 'Unique identifier for the subscription contract.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'status' => array(
					'description' => __( 'Contract status after the action.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Run a lifecycle action behind the ownership guard, then return the domain
	 * summary with the resulting status.
	 *
	 * A `DomainException` (an illegal transition for the contract's current state) maps to
	 * a 409 Conflict; any other failure maps to a 500. The ownership guard keeps the
	 * asymmetric 404 for not-owned / unknown.
	 *
	 * @param WP_REST_Request $request The request (carries the id).
	 * @param callable        $action  Runs the lifecycle action; receives the contract id.
	 * @return WP_REST_Response|WP_Error
	 */
	private function run_action( WP_REST_Request $request, callable $action ) {
		$contract_id = ScalarCoercion::coerce_int( $request->get_param( 'id' ) );
		$customer_id = get_current_user_id();

		// Guard ownership before acting: the facade's ownership-checked read returns
		// null for an unknown id and a foreign-owned contract alike, so both map to
		// the same 404 (anti-IDOR).
		if ( null === Subscriptions::get_for_customer( $contract_id, $customer_id ) ) {
			return $this->not_found_error();
		}

		try {
			$action( $contract_id );
		} catch ( DomainException $e ) {
			return new WP_Error(
				'woocommerce_subscriptions_engine_illegal_action',
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

		// Re-read for the resulting status. The action already succeeded, so a row
		// vanishing here is a server-side inconsistency - a 500, not a not-found.
		$refreshed = Subscriptions::get_for_customer( $contract_id, $customer_id );
		if ( null === $refreshed ) {
			return new WP_Error(
				'woocommerce_subscriptions_engine_refresh_failed',
				__( 'The subscription was updated, but its refreshed state could not be loaded.', 'woocommerce-subscriptions-engine' ),
				array( 'status' => 500 )
			);
		}

		return $this->prepare_item_for_response( $refreshed, $request );
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
