<?php
/**
 * REST controller for subscription engine contracts.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api\Rest
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api\Rest;

use SplObjectStorage;
use Throwable;
use UnexpectedValueException;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\ContractView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\Coercion;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Rest\ContractActionRegistry;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Support\RESTPermissions;

defined( 'ABSPATH' ) || exit;

/**
 * Contracts REST controller under `wc/v3/subscriptions-engine/contracts`: `GET /{id}` returns
 * the stored contract to store managers; `GET|POST /{id}/action` lists and runs the actions
 * the contract's owning extension registered through {@see \Automattic\WooCommerce\SubscriptionsEngine\Api\ContractActions}.
 *
 * @phpstan-import-type ContractActionDefinition from ContractActionRegistry
 */
final class ContractsController extends WP_REST_Controller {

	private const REST_NAMESPACE = 'wc/v3';

	private const REST_BASE = 'subscriptions-engine/contracts';

	private const LOG_SOURCE = 'woocommerce-subscriptions-engine';

	/**
	 * REST permissions.
	 *
	 * @var RESTPermissions
	 */
	private $rest_permissions;

	/**
	 * Action resolutions per request, so the callback reuses what the permission check read.
	 *
	 * @var SplObjectStorage<WP_REST_Request, array{contract: ContractView, actions: array<int, ContractActionDefinition>}|WP_Error>
	 */
	private $resolved_actions;

	/**
	 * Build the controller.
	 */
	public function __construct() {
		$this->namespace        = self::REST_NAMESPACE;
		$this->rest_base        = self::REST_BASE;
		$this->rest_permissions = new RESTPermissions();
		$this->resolved_actions = new SplObjectStorage();
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
			'/' . self::REST_BASE . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the contract.', 'woocommerce-subscriptions-engine' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE . '/(?P<id>[\d]+)/action',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the contract.', 'woocommerce-subscriptions-engine' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_actions' ),
					'permission_callback' => array( $this, 'actions_permissions_check' ),
					'args'                => array(
						'action' => array(
							'description' => __( 'Only list this action.', 'woocommerce-subscriptions-engine' ),
							'type'        => 'string',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'run_action' ),
					'permission_callback' => array( $this, 'actions_permissions_check' ),
					'args'                => array(
						'action'         => array(
							'description' => __( 'Action to run.', 'woocommerce-subscriptions-engine' ),
							'type'        => 'string',
							'required'    => true,
						),
						'extension_slug' => array(
							'description' => __( 'Slug of the extension that owns the contract and registered the action.', 'woocommerce-subscriptions-engine' ),
							'type'        => 'string',
							'required'    => true,
						),
						'action_args'    => array(
							'description' => __( 'Arguments for the action, as its schema describes them.', 'woocommerce-subscriptions-engine' ),
							'type'        => 'object',
							'default'     => array(),
						),
					),
				),
			)
		);
	}

	/**
	 * Store managers only.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		return $this->rest_permissions->require_admin_permission();
	}

	/**
	 * Logged-in users with at least one permitted action on the contract. Anything else that is
	 * not a 401 is the same 404, so a caller cannot probe for contracts it may not act on.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public function actions_permissions_check( $request ) {
		$logged_in = $this->rest_permissions->require_logged_in_permission();
		if ( true !== $logged_in ) {
			return $logged_in;
		}

		$resolved = $this->resolve_permitted_actions( $request );

		return $resolved instanceof WP_Error ? $resolved : true;
	}

	/**
	 * List the permitted actions that are available for the contract now, with their resolved args.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_actions( $request ) {
		$resolved = $this->resolve_permitted_actions( $request );
		if ( $resolved instanceof WP_Error ) {
			return $resolved;
		}

		$contract = $resolved['contract'];
		$actions  = array();
		try {
			foreach ( $resolved['actions'] as $definition ) {
				if ( ! ContractActionRegistry::is_available( $definition, $contract ) ) {
					continue;
				}

				$actions[] = array(
					'action'         => $definition['action'],
					'extension_slug' => $definition['extension_slug'],
					'description'    => $definition['description'],
					'args'           => $this->get_args_for_response( ContractActionRegistry::get_args_schema( $definition, $contract ) ),
				);
			}
		} catch ( Throwable $e ) {
			return $this->get_action_failed_error( $e, $request );
		}

		return rest_ensure_response( array( 'actions' => $actions ) );
	}

	/**
	 * Run the action: 409 when it is not available, 400 for invalid `action_args`, then the
	 * callback's result. A `WP_Error` passes through (status 400 unless it has one).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function run_action( $request ) {
		$resolved = $this->resolve_permitted_actions( $request );
		if ( $resolved instanceof WP_Error ) {
			return $resolved;
		}

		$contract   = $resolved['contract'];
		$definition = $resolved['actions'][0];
		try {
			if ( ! ContractActionRegistry::is_available( $definition, $contract ) ) {
				return new WP_Error(
					'woocommerce_subscriptions_engine_action_not_available',
					__( 'This action is not available for the contract.', 'woocommerce-subscriptions-engine' ),
					array( 'status' => 409 )
				);
			}

			$action_args = $this->get_validated_action_args( $request, ContractActionRegistry::get_args_schema( $definition, $contract ) );
			if ( $action_args instanceof WP_Error ) {
				return $action_args;
			}

			$result = ( $definition['callback'] )( $contract, $action_args );
		} catch ( Throwable $e ) {
			return $this->get_action_failed_error( $e, $request );
		}

		if ( $result instanceof ContractView ) {
			return rest_ensure_response(
				array(
					'id'     => $result->get_id(),
					'status' => $result->get_status(),
				)
			);
		}

		if ( $result instanceof WP_Error ) {
			$error_data = $result->get_error_data();
			if ( ! is_array( $error_data ) || ! isset( $error_data['status'] ) ) {
				$result->add_data( array_merge( is_array( $error_data ) ? $error_data : array(), array( 'status' => 400 ) ) );
			}

			return $result;
		}

		return $this->get_action_failed_error( new UnexpectedValueException( 'The action callback must return a ContractView or a WP_Error.' ), $request );
	}

	/**
	 * Get one contract with its items and addresses.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$contract = Contracts::get( Coercion::coerce_int( $request->get_param( 'id' ) ) );
		if ( null === $contract ) {
			return new WP_Error(
				'woocommerce_subscriptions_engine_contract_not_found',
				__( 'Contract not found.', 'woocommerce-subscriptions-engine' ),
				array( 'status' => 404 )
			);
		}

		return $this->prepare_item_for_response( $contract, $request );
	}

	/**
	 * The contract as response data. Dates are GMT, formatted like other WordPress REST dates.
	 *
	 * @param ContractView    $item    Contract.
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = array(
			'id'                   => $item->get_id(),
			'extension_slug'       => $item->get_extension_slug(),
			'status'               => $item->get_status(),
			'customer_id'          => $item->get_customer_id(),
			'currency'             => $item->get_currency(),
			'selling_plan_id'      => $item->get_selling_plan_id(),
			'origin_order_id'      => $item->get_origin_order_id(),
			'payment_method'       => $item->get_payment_method(),
			'payment_method_title' => $item->get_payment_method_title(),
			'payment_token_id'     => $item->get_payment_token_id(),
			'start_gmt'            => $this->format_date( $item->get_start_gmt() ),
			'next_payment_gmt'     => $this->format_date( $item->get_next_payment_gmt() ),
			'last_payment_gmt'     => $this->format_date( $item->get_last_payment_gmt() ),
			'last_attempt_gmt'     => $this->format_date( $item->get_last_attempt_gmt() ),
			'trial_end_gmt'        => $this->format_date( $item->get_trial_end_gmt() ),
			'end_gmt'              => $this->format_date( $item->get_end_gmt() ),
			'schedule_source'      => $item->get_schedule_source(),
			'billing_total'        => $item->get_billing_total(),
			'discount_total'       => $item->get_discount_total(),
			'shipping_total'       => $item->get_shipping_total(),
			'tax_total'            => $item->get_tax_total(),
			'items'                => $item->get_items() ?? array(),
			'addresses'            => $item->get_addresses() ?? array(),
		);

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, Coercion::coerce_string( $request->get_param( 'context' ), 'view' ) );

		return rest_ensure_response( $data );
	}

	/**
	 * Get the contract schema.
	 *
	 * @return array<string, mixed>
	 */
	public function get_item_schema(): array {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$properties = array(
			'id'                   => array( 'integer', __( 'Unique identifier for the contract.', 'woocommerce-subscriptions-engine' ) ),
			'extension_slug'       => array( array( 'string', 'null' ), __( 'Slug of the extension that owns the contract.', 'woocommerce-subscriptions-engine' ) ),
			'status'               => array( 'string', __( 'Contract status slug.', 'woocommerce-subscriptions-engine' ) ),
			'customer_id'          => array( array( 'integer', 'null' ), __( 'Customer user ID.', 'woocommerce-subscriptions-engine' ) ),
			'currency'             => array( array( 'string', 'null' ), __( 'Currency code.', 'woocommerce-subscriptions-engine' ) ),
			'selling_plan_id'      => array( array( 'integer', 'null' ), __( 'Plan ID.', 'woocommerce-subscriptions-engine' ) ),
			'origin_order_id'      => array( array( 'integer', 'null' ), __( 'ID of the order the contract started from.', 'woocommerce-subscriptions-engine' ) ),
			'payment_method'       => array( array( 'string', 'null' ), __( 'Payment gateway ID.', 'woocommerce-subscriptions-engine' ) ),
			'payment_method_title' => array( array( 'string', 'null' ), __( 'Payment method title.', 'woocommerce-subscriptions-engine' ) ),
			'payment_token_id'     => array( array( 'integer', 'null' ), __( 'Payment token ID.', 'woocommerce-subscriptions-engine' ) ),
			'start_gmt'            => array( array( 'string', 'null' ), __( 'Start date, as GMT.', 'woocommerce-subscriptions-engine' ) ),
			'next_payment_gmt'     => array( array( 'string', 'null' ), __( 'Next-due moment, as GMT.', 'woocommerce-subscriptions-engine' ) ),
			'last_payment_gmt'     => array( array( 'string', 'null' ), __( 'Last payment date, as GMT.', 'woocommerce-subscriptions-engine' ) ),
			'last_attempt_gmt'     => array( array( 'string', 'null' ), __( 'Last payment attempt date, as GMT.', 'woocommerce-subscriptions-engine' ) ),
			'trial_end_gmt'        => array( array( 'string', 'null' ), __( 'Trial end date, as GMT.', 'woocommerce-subscriptions-engine' ) ),
			'end_gmt'              => array( array( 'string', 'null' ), __( 'End date, as GMT.', 'woocommerce-subscriptions-engine' ) ),
			'schedule_source'      => array( 'string', __( 'Who keeps the payment schedule.', 'woocommerce-subscriptions-engine' ) ),
			'billing_total'        => array( 'string', __( 'Recurring total.', 'woocommerce-subscriptions-engine' ) ),
			'discount_total'       => array( 'string', __( 'Recurring discount total.', 'woocommerce-subscriptions-engine' ) ),
			'shipping_total'       => array( 'string', __( 'Recurring shipping total.', 'woocommerce-subscriptions-engine' ) ),
			'tax_total'            => array( 'string', __( 'Recurring tax total.', 'woocommerce-subscriptions-engine' ) ),
			'items'                => array( 'array', __( 'Line items.', 'woocommerce-subscriptions-engine' ) ),
			'addresses'            => array( 'object', __( 'Billing and shipping addresses.', 'woocommerce-subscriptions-engine' ) ),
		);

		$schema_properties = array();
		foreach ( $properties as $key => $property ) {
			$schema_properties[ $key ] = array(
				'description' => $property[1],
				'type'        => $property[0],
				'context'     => array( 'view' ),
				'readonly'    => true,
			);
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'subscription_engine_contract',
			'type'       => 'object',
			'properties' => $schema_properties,
		);

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * The contract and the actions the current user may run on it (all, or the requested one),
	 * resolved once per request. Unknown contract, wrong `extension_slug`, unknown action and
	 * no permission are the same 404.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array{contract: ContractView, actions: array<int, ContractActionDefinition>}|WP_Error
	 */
	private function resolve_permitted_actions( WP_REST_Request $request ) {
		if ( ! $this->resolved_actions->contains( $request ) ) {
			try {
				$this->resolved_actions[ $request ] = $this->get_permitted_actions( $request );
			} catch ( Throwable $e ) {
				$this->resolved_actions[ $request ] = $this->get_action_failed_error( $e, $request );
			}
		}

		return $this->resolved_actions[ $request ];
	}

	/**
	 * Read the contract and filter its owner's actions down to the permitted ones.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array{contract: ContractView, actions: array<int, ContractActionDefinition>}|WP_Error
	 */
	private function get_permitted_actions( WP_REST_Request $request ) {
		$not_found = new WP_Error(
			'woocommerce_subscriptions_engine_contract_not_found',
			__( 'Contract not found.', 'woocommerce-subscriptions-engine' ),
			array( 'status' => 404 )
		);

		$contract       = Contracts::get( Coercion::coerce_int( $request->get_param( 'id' ) ) );
		$extension_slug = null === $contract ? null : $contract->get_extension_slug();
		if ( null === $contract || null === $extension_slug ) {
			return $not_found;
		}

		if ( 'POST' === $request->get_method() && $request->get_param( 'extension_slug' ) !== $extension_slug ) {
			return $not_found;
		}

		$action = $request->get_param( 'action' );
		if ( null === $action ) {
			$definitions = ContractActionRegistry::get_for_extension( $extension_slug );
		} else {
			$definition  = is_string( $action ) ? ContractActionRegistry::get( $extension_slug, $action ) : null;
			$definitions = null === $definition ? array() : array( $definition );
		}

		$permitted = array();
		foreach ( $definitions as $candidate ) {
			if ( ContractActionRegistry::is_permitted( $candidate, $contract, $request ) ) {
				$permitted[] = $candidate;
			}
		}

		if ( array() === $permitted ) {
			return $not_found;
		}

		return array(
			'contract' => $contract,
			'actions'  => $permitted,
		);
	}

	/**
	 * Validate `action_args` against the action's property schemas: defaults filled in, unknown
	 * keys dropped, a 400 when a value does not match.
	 *
	 * @param WP_REST_Request                     $request    Request.
	 * @param array<string, array<string, mixed>> $properties Property schemas.
	 * @return array<string, mixed>|WP_Error
	 */
	private function get_validated_action_args( WP_REST_Request $request, array $properties ) {
		$defaults = array();
		foreach ( $properties as $name => $property ) {
			if ( array_key_exists( 'default', $property ) ) {
				$defaults[ $name ] = $property['default'];
			}
		}

		$request_args = $request->get_param( 'action_args' );
		$action_args  = ( is_array( $request_args ) ? $request_args : array() ) + $defaults;
		$schema       = array(
			'type'       => 'object',
			'properties' => $properties,
		);

		$valid = rest_validate_value_from_schema( $action_args, $schema, 'action_args' );
		if ( $valid instanceof WP_Error ) {
			return $this->get_invalid_action_args_error( $valid );
		}

		$sanitized = rest_sanitize_value_from_schema( $action_args, $schema + array( 'additionalProperties' => false ), 'action_args' );
		if ( $sanitized instanceof WP_Error ) {
			return $this->get_invalid_action_args_error( $sanitized );
		}

		return is_array( $sanitized ) ? Coercion::coerce_string_keyed( $sanitized ) : array();
	}

	/**
	 * The 400 for `action_args` that do not match the schema, carrying the schema error's message.
	 *
	 * @param WP_Error $error Schema validation or sanitization error.
	 */
	private function get_invalid_action_args_error( WP_Error $error ): WP_Error {
		return new WP_Error(
			'woocommerce_subscriptions_engine_invalid_action_args',
			$error->get_error_message(),
			array(
				'status' => 400,
				'reason' => $error->get_error_code(),
			)
		);
	}

	/**
	 * The resolved args schemas as discovery shows them: schema keywords and `required` only,
	 * the way the WordPress REST index describes route args. An object, so no args encodes as `{}`.
	 *
	 * @param array<string, array<string, mixed>> $properties Property schemas.
	 */
	private function get_args_for_response( array $properties ): object {
		$keywords = array_flip( rest_get_allowed_schema_keywords() );
		$args     = array();
		foreach ( $properties as $name => $property ) {
			$args[ $name ]             = array_intersect_key( $property, $keywords );
			$args[ $name ]['required'] = ! empty( $property['required'] );
		}

		return (object) $args;
	}

	/**
	 * Log an extension callback failure and return a generic 500.
	 *
	 * @param Throwable       $e       Failure.
	 * @param WP_REST_Request $request Request.
	 */
	private function get_action_failed_error( Throwable $e, WP_REST_Request $request ): WP_Error {
		$contract_id = Coercion::coerce_int( $request->get_param( 'id' ) );
		wc_get_logger()->error(
			sprintf( 'ContractsController: contract action "%s" on contract %d failed: %s', Coercion::coerce_string( $request->get_param( 'action' ) ), $contract_id, $e->getMessage() ),
			array(
				'source'      => self::LOG_SOURCE,
				'contract_id' => $contract_id,
			)
		);

		return new WP_Error(
			'woocommerce_subscriptions_engine_action_failed',
			__( 'The action could not be completed.', 'woocommerce-subscriptions-engine' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * A stored GMT datetime in the WordPress REST date format, or null.
	 *
	 * @param string|null $date_gmt Stored `Y-m-d H:i:s` GMT datetime.
	 */
	private function format_date( ?string $date_gmt ): ?string {
		return null === $date_gmt ? null : mysql_to_rfc3339( $date_gmt );
	}
}
