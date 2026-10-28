<?php
/**
 * REST controller for subscription engine plans: opaque CRUD over the plan facade (the
 * paged, searchable collection reads the repository). Policies pass through as JSON
 * objects, never parsed or merged.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Rest
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api\Rest;

use Automattic\WooCommerce\SubscriptionsEngine\Api\Plans;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ValidationException;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\PlanView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\PlanStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\Coercion;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\InvalidInputException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ValidationErrorCode;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Support\RESTPermissions;
use RuntimeException;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Plans REST controller.
 */
final class PlansController extends WP_REST_Controller {

	private const REST_NAMESPACE = 'wc/v3';

	private const REST_BASE = 'subscriptions-engine/plans';

	private const MAX_PER_PAGE = 100;

	private const DEFAULT_PER_PAGE = 20;

	/**
	 * Writable plan fields (the owning `extension_slug` comes from the request).
	 *
	 * @var array<int, string>
	 */
	private const WRITE_FIELDS = array( 'name', 'status', 'billing_policy', 'pricing_policy', 'delivery_policy' );

	/**
	 * Arg options of a writable schema field: WordPress neither checks nor casts it, so
	 * the facade (or the `extension_slug` check) reports it with every other refusal.
	 *
	 * @var array<string, null>
	 */
	private const UNCHECKED_ARG_OPTIONS = array(
		'validate_callback' => null,
		'sanitize_callback' => null,
	);

	/**
	 * Logger source.
	 */
	private const LOG_SOURCE = 'woocommerce-subscriptions-engine';

	/**
	 * Columns the collection may be ordered by.
	 *
	 * @var array<int, string>
	 */
	private const ORDERBY = array( 'id', 'name', 'date_created_gmt', 'date_updated_gmt' );

	/**
	 * Plans repository.
	 *
	 * @var PlanRepository
	 */
	private $plan_repository;

	/**
	 * REST permissions.
	 *
	 * @var RESTPermissions
	 */
	private $rest_permissions;

	/**
	 * Construct the controller.
	 *
	 * @param PlanRepository|null  $plan_repository  Plans repository.
	 * @param RESTPermissions|null $rest_permissions REST permissions.
	 */
	public function __construct( ?PlanRepository $plan_repository = null, ?RESTPermissions $rest_permissions = null ) {
		$this->namespace        = self::REST_NAMESPACE;
		$this->rest_base        = self::REST_BASE;
		$this->plan_repository  = $plan_repository ?? new PlanRepository();
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
	 * Register routes.
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
					'args'                => array(
						'extension_slug' => array(
							'description' => __( 'Extension slug or comma-separated list of slugs for the plan query. Use "any" to query all slugs.', 'woocommerce' ),
							'type'        => 'string',
							'required'    => true,
						),
						'page'           => array(
							'description' => __( 'Page number for the plan query.', 'woocommerce' ),
							'type'        => 'integer',
							'required'    => false,
						),
						'per_page'       => array(
							'description' => __( 'Number of plans per page for the plan query.', 'woocommerce' ),
							'type'        => 'integer',
							'required'    => false,
							'default'     => self::DEFAULT_PER_PAGE,
						),
						'search'         => array(
							'description' => __( 'Search term for the plan query.', 'woocommerce' ),
							'type'        => 'string',
							'required'    => false,
						),
						'status'         => array(
							'description'       => __( 'Status of the plans to query (any registered plan status).', 'woocommerce' ),
							'type'              => 'string',
							'required'          => false,
							// get_items() checks it, so its refusal carries the `errors` map.
							'sanitize_callback' => null,
						),
						'orderby'        => array(
							'description' => __( 'Order by field for the plan query.', 'woocommerce' ),
							'type'        => 'string',
							'required'    => false,
							'enum'        => self::ORDERBY,
							'default'     => 'id',
						),
						'order'          => array(
							'description' => __( 'Order direction for the plan query.', 'woocommerce' ),
							'type'        => 'string',
							'required'    => false,
							'enum'        => array( 'asc', 'desc' ),
							'default'     => 'asc',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the plan.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => 'PATCH',
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Permission callback for all management routes.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public function permissions_check( $request ) {
		return $this->rest_permissions->require_admin_permission();
	}

	/**
	 * Get a paginated plan list.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$extension_slugs = $this->get_multiple_extension_slugs( $request );
		if ( $extension_slugs instanceof WP_Error ) {
			return $extension_slugs;
		}

		$status_error = $this->get_status_filter_error( $request->get_param( 'status' ) );
		if ( null !== $status_error ) {
			return $status_error;
		}

		$page     = max( 1, Coercion::coerce_int( $request->get_param( 'page' ), 1 ) );
		$per_page = $this->get_per_page( $request );
		$args     = array(
			'limit'           => $per_page,
			'offset'          => ( $page - 1 ) * $per_page,
			'extension_slugs' => $extension_slugs,
		);

		foreach ( array( 'search', 'status', 'orderby', 'order' ) as $key ) {
			$value = $request->get_param( $key );
			if ( null !== $value && '' !== $value ) {
				$args[ $key ] = $value;
			}
		}

		$plans = $this->plan_repository->query( $args );
		$total = $this->plan_repository->count( $args );

		$response = new WP_REST_Response(
			array_map(
				function ( Plan $plan ) use ( $request ): array {
					$prepared = $this->prepare_response_for_collection(
						$this->prepare_item_for_response( PlanView::from_plan( $plan ), $request )
					);

					return is_array( $prepared ) ? $prepared : array();
				},
				$plans
			)
		);
		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) ( 0 === $per_page ? 0 : (int) ceil( $total / $per_page ) ) );

		return $response;
	}

	/**
	 * Get one plan.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$extension_slug = $this->get_single_extension_slug( $request );
		if ( $extension_slug instanceof WP_Error ) {
			return $extension_slug;
		}

		$plan = Plans::get( Coercion::coerce_int( $request->get_param( 'id' ) ) );
		if ( null === $plan || $extension_slug !== $plan->get_extension_slug() ) {
			return $this->not_found_error();
		}

		return rest_ensure_response( $this->prepare_item_for_response( $plan, $request ) );
	}

	/**
	 * Create one plan owned by the request's extension slug.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$extension_slug = $this->get_single_extension_slug( $request );
		if ( $extension_slug instanceof WP_Error ) {
			return $extension_slug;
		}

		$args                   = $this->get_write_args( $request );
		$args['extension_slug'] = $extension_slug;

		try {
			$plan = Plans::create( $args );
		} catch ( ValidationException $e ) {
			return $this->get_validation_error( $e );
		} catch ( RuntimeException $e ) {
			return $this->write_failed_error( $e, 'woocommerce_subscriptions_engine_plan_create_failed' );
		}

		$response = rest_ensure_response( $this->prepare_item_for_response( $plan, $request ) );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Partially update a plan: only the present fields are written, and a present
	 * policy replaces the stored payload.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$extension_slug = $this->get_single_extension_slug( $request );
		if ( $extension_slug instanceof WP_Error ) {
			return $extension_slug;
		}

		$args                   = $this->get_write_args( $request );
		$args['extension_slug'] = $extension_slug;

		try {
			// A plan of another extension reads as missing: the facade scopes the update to the request's extension slug.
			$plan = Plans::update( Coercion::coerce_int( $request->get_param( 'id' ) ), $args );
		} catch ( ValidationException $e ) {
			return $this->get_validation_error( $e );
		} catch ( RuntimeException $e ) {
			return $this->write_failed_error( $e, 'woocommerce_subscriptions_engine_plan_update_failed' );
		}

		if ( null === $plan ) {
			return $this->not_found_error();
		}

		return rest_ensure_response( $this->prepare_item_for_response( $plan, $request ) );
	}

	/**
	 * Serialize a plan view.
	 *
	 * @param PlanView        $item    Plan view.
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = array(
			'id'               => $item->get_id(),
			'extension_slug'   => $item->get_extension_slug(),
			'status'           => $item->get_status(),
			'name'             => $item->get_name(),
			'billing_policy'   => self::as_json_object( $item->get_billing_policy() ),
			'pricing_policy'   => self::as_json_object( $item->get_pricing_policy() ),
			'delivery_policy'  => self::as_json_object( $item->get_delivery_policy() ),
			'date_created_gmt' => $item->get_date_created_gmt(),
			'date_updated_gmt' => $item->get_date_updated_gmt(),
		);

		$context = Coercion::coerce_string( $request->get_param( 'context' ), 'view' );
		$context = '' !== $context ? $context : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		return rest_ensure_response( $data );
	}

	/**
	 * Get collection params.
	 *
	 * @return array<string, mixed>
	 */
	public function get_collection_params(): array {
		return array(
			'page'     => array(
				'description'       => __( 'Current page of the collection.', 'woocommerce' ),
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'per_page' => array(
				'description'       => __( 'Maximum number of items to be returned in result set.', 'woocommerce' ),
				'type'              => 'integer',
				'default'           => self::DEFAULT_PER_PAGE,
				'minimum'           => 1,
				'maximum'           => self::MAX_PER_PAGE,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'search'   => array(
				'description'       => __( 'Search term.', 'woocommerce' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'status'   => array(
				'description'       => __( 'Limit result set to plans with a registered plan status.', 'woocommerce' ),
				'type'              => 'string',
				'sanitize_callback' => null,
			),
			'orderby'  => array(
				'description'       => __( 'Sort collection by object attribute.', 'woocommerce' ),
				'type'              => 'string',
				'default'           => 'id',
				'enum'              => self::ORDERBY,
				'sanitize_callback' => 'sanitize_key',
			),
			'order'    => array(
				'description'       => __( 'Order sort attribute ascending or descending.', 'woocommerce' ),
				'type'              => 'string',
				'default'           => 'asc',
				'enum'              => array( 'asc', 'desc' ),
				'sanitize_callback' => 'sanitize_key',
			),
			'context'  => $this->get_context_param( array( 'default' => 'view' ) ),
		);
	}

	/**
	 * Get item schema.
	 *
	 * @return array<string, mixed>
	 */
	public function get_item_schema(): array {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'subscription_engine_plan',
			'type'       => 'object',
			'properties' => array(
				'id'               => array(
					'description' => __( 'Unique identifier for the plan.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'extension_slug'   => array(
					'description' => __( 'Owning extension slug.', 'woocommerce' ),
					'type'        => array( 'string', 'null' ),
					'context'     => array( 'view', 'edit' ),
					'arg_options' => self::UNCHECKED_ARG_OPTIONS,
				),
				'status'           => array(
					'description' => __( 'Plan status (any registered plan status).', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => self::UNCHECKED_ARG_OPTIONS,
				),
				'name'             => array(
					'description' => __( 'Display name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => self::UNCHECKED_ARG_OPTIONS,
				),
				'billing_policy'   => array(
					'description' => __( 'Billing payload of the owning extension.', 'woocommerce' ),
					'type'        => array( 'object', 'null' ),
					'context'     => array( 'view', 'edit' ),
					'arg_options' => self::UNCHECKED_ARG_OPTIONS,
				),
				'pricing_policy'   => array(
					'description' => __( 'Pricing payload of the owning extension.', 'woocommerce' ),
					'type'        => array( 'object', 'null' ),
					'context'     => array( 'view', 'edit' ),
					'arg_options' => self::UNCHECKED_ARG_OPTIONS,
				),
				'delivery_policy'  => array(
					'description' => __( 'Delivery payload of the owning extension.', 'woocommerce' ),
					'type'        => array( 'object', 'null' ),
					'context'     => array( 'view', 'edit' ),
					'arg_options' => self::UNCHECKED_ARG_OPTIONS,
				),
				'date_created_gmt' => array(
					'description' => __( 'Creation time (GMT).', 'woocommerce' ),
					'type'        => array( 'string', 'null' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'date_updated_gmt' => array(
					'description' => __( 'Last update time (GMT).', 'woocommerce' ),
					'type'        => array( 'string', 'null' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * The requested page size: capped at the maximum, and the default when below 1 or not a number.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	private function get_per_page( WP_REST_Request $request ): int {
		$value = Coercion::coerce_int( $request->get_param( 'per_page' ), self::DEFAULT_PER_PAGE );
		if ( $value < 1 ) {
			return self::DEFAULT_PER_PAGE;
		}

		return min( $value, self::MAX_PER_PAGE );
	}

	/**
	 * Collect the present writable params as facade args, passed through as given
	 * (a string name is sanitized); the facade validates them.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array<string, mixed>
	 */
	private function get_write_args( WP_REST_Request $request ): array {
		$args = array();
		foreach ( self::WRITE_FIELDS as $field ) {
			if ( $request->has_param( $field ) ) {
				$value          = $request->get_param( $field );
				$args[ $field ] = 'name' === $field && is_string( $value ) ? sanitize_text_field( $value ) : $value;
			}
		}

		return $args;
	}

	/**
	 * Present a map (a stored policy, error params) as a JSON object: an empty one serializes as `{}`.
	 *
	 * @param array<array-key, mixed>|null $map Map.
	 * @return array<array-key, mixed>|object|null
	 */
	private static function as_json_object( ?array $map ) {
		if ( array() === $map ) {
			return (object) array();
		}

		return $map;
	}

	/**
	 * Map a failed write to a 500. The facade wraps a throwing validation callback
	 * (the cause is chained, and the facade logs it); a failed insert or update carries
	 * no cause and is logged here with the database error. `Api\Plans` documents this
	 * on its create and update `@throws`, and PlansTest pins both sides.
	 *
	 * @param RuntimeException $e    Failure.
	 * @param string           $code Error code for a failed insert or update.
	 */
	private function write_failed_error( RuntimeException $e, string $code ): WP_Error {
		if ( $e->getPrevious() instanceof \Throwable ) {
			return new WP_Error(
				'woocommerce_subscriptions_engine_plan_validation_failed',
				__( 'The plan could not be validated.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		wc_get_logger()->error(
			sprintf( 'PlansController: the plan write failed: %s', $e->getMessage() ),
			array( 'source' => self::LOG_SOURCE )
		);

		return new WP_Error(
			$code,
			__( 'The plan could not be saved.', 'woocommerce' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Report validation errors as WordPress reports invalid request params: errors with a
	 * `field` become one `rest_invalid_param` error (see {@see self::get_invalid_param_error()});
	 * errors without one are added to it. With no `field` on any error, the errors return
	 * as a plain 400.
	 *
	 * @param ValidationException $e The facade's refusal.
	 */
	private function get_validation_error( ValidationException $e ): WP_Error {
		$paired = $this->get_paired_errors( $e );
		if ( array() === $paired['fielded'] ) {
			return $this->as_bad_request( $paired['fieldless'] );
		}

		$error = $this->get_invalid_param_error( $paired['fielded'] );
		$this->merge_fieldless_errors( $error, $paired['fieldless'] );

		return $error;
	}

	/**
	 * Pair each error message with its own data, in `WP_Error` order. The engine's errors
	 * always pair. An extension's error pairs only when every extension message of its code
	 * carries data; otherwise that code's extension messages are fieldless, so a field never
	 * lands on the wrong message.
	 *
	 * @param ValidationException $e The facade's refusal.
	 * @return array{fielded: list<array{code: string, message: string, field: string, data: array<mixed>}>, fieldless: WP_Error}
	 */
	private function get_paired_errors( ValidationException $e ): array {
		$errors    = $e->get_errors();
		$fielded   = array();
		$fieldless = new WP_Error();

		// The chained Core refusal holds the engine's errors, which come first in their code and always carry data.
		$engine_counts = array();
		$refusal       = $e->getPrevious();
		if ( $refusal instanceof InvalidInputException ) {
			foreach ( $refusal->get_errors() as $error ) {
				$engine_counts[ $error->get_error_code() ] = ( $engine_counts[ $error->get_error_code() ] ?? 0 ) + 1;
			}
		}

		foreach ( $errors->get_error_codes() as $code ) {
			$code     = (string) $code;
			$messages = array_values( $errors->get_error_messages( $code ) );
			$all_data = array_values( $errors->get_all_error_data( $code ) );
			// `WP_Error` skips empty data, so when the counts differ the extension's data cannot be placed.
			$pairs = count( $messages ) === count( $all_data );

			foreach ( $messages as $index => $message ) {
				$data = ( $pairs || $index < ( $engine_counts[ $code ] ?? 0 ) ) ? $all_data[ $index ] : null;
				if ( is_array( $data ) && isset( $data['field'] ) && is_string( $data['field'] ) && '' !== $data['field'] ) {
					$fielded[] = array(
						'code'    => $code,
						'message' => $message,
						'field'   => $data['field'],
						'data'    => $data,
					);
					continue;
				}

				$fieldless->add( $code, $message, $data );
			}
		}

		return array(
			'fielded'   => $fielded,
			'fieldless' => $fieldless,
		);
	}

	/**
	 * The `rest_invalid_param` error for fielded errors. Keyed by top-level arg: `params`
	 * holds the arg's sentences naming their fields, and `details` the arg's first error
	 * (code, that sentence and its own data). `errors` maps each full dot path to its
	 * errors' code, field-free message and params.
	 *
	 * @param list<array{code: string, message: string, field: string, data: array<mixed>}> $fielded Paired, fielded errors.
	 */
	private function get_invalid_param_error( array $fielded ): WP_Error {
		$params       = array();
		$details      = array();
		$field_errors = array();
		foreach ( $fielded as $error ) {
			$arg     = $this->get_top_level_arg( $error['field'] );
			$message = $this->get_field_message( $error['field'], $error['message'] );

			$params[ $arg ][] = $message;
			if ( ! isset( $details[ $arg ] ) ) {
				$details[ $arg ] = array(
					'code'    => $error['code'],
					'message' => $message,
					'data'    => $error['data'],
				);
				if ( isset( $error['data']['params'] ) && is_array( $error['data']['params'] ) ) {
					$details[ $arg ]['data']['params'] = self::as_json_object( $error['data']['params'] );
				}
			}
			$field_errors[ $error['field'] ][] = array(
				'code'    => $error['code'],
				'message' => $error['message'],
				'params'  => self::as_json_object( isset( $error['data']['params'] ) && is_array( $error['data']['params'] ) ? $error['data']['params'] : array() ),
			);
		}

		return new WP_Error(
			'rest_invalid_param',
			/* translators: %s: List of invalid parameters. */
			sprintf( __( 'Invalid parameter(s): %s' ), implode( ', ', array_keys( $params ) ) ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- WordPress core's own message for invalid request params, in its default domain.
			array(
				'status'  => 400,
				'params'  => array_map(
					static function ( array $messages ): string {
						return implode( ' ', $messages );
					},
					$params
				),
				'details' => $details,
				'errors'  => $field_errors,
			)
		);
	}

	/**
	 * Add fieldless errors to the response error, each with a 400 status unless it has
	 * its own. A `rest_invalid_param` one is added without its data, so it can never
	 * replace the response's data.
	 *
	 * @param WP_Error $error     The `rest_invalid_param` response error.
	 * @param WP_Error $fieldless Errors without a field.
	 */
	private function merge_fieldless_errors( WP_Error $error, WP_Error $fieldless ): void {
		$fieldless = $this->as_bad_request( $fieldless );
		foreach ( $fieldless->get_error_codes() as $code ) {
			foreach ( $fieldless->get_error_messages( $code ) as $message ) {
				$error->add( $code, $message );
			}
			if ( 'rest_invalid_param' === $code ) {
				continue;
			}
			foreach ( $fieldless->get_all_error_data( $code ) as $data ) {
				$error->add_data( $data, $code );
			}
		}
	}

	/**
	 * The top-level request arg of a dot path: the path up to its first dot.
	 *
	 * @param string $field Field, in dot notation.
	 */
	private function get_top_level_arg( string $field ): string {
		$dot = strpos( $field, '.' );

		return false === $dot ? $field : substr( $field, 0, $dot );
	}

	/**
	 * A validation message that names its field, for REST `params` and `details`.
	 *
	 * @param string $field   Field, in dot notation.
	 * @param string $message Translated, field-free message.
	 */
	private function get_field_message( string $field, string $message ): string {
		/* translators: 1: field name, such as billing_policy.interval, 2: validation message. */
		return sprintf( __( '%1$s: %2$s', 'woocommerce' ), $field, $message );
	}

	/**
	 * Give each error code without a status a 400 status.
	 *
	 * @param WP_Error $errors Validation errors.
	 */
	private function as_bad_request( WP_Error $errors ): WP_Error {
		foreach ( $errors->get_error_codes() as $code ) {
			$data = $errors->get_error_data( $code );
			if ( ! is_array( $data ) || ! isset( $data['status'] ) ) {
				$errors->add_data( array_merge( is_array( $data ) ? $data : array(), array( 'status' => 400 ) ), $code );
			}
		}

		return $errors;
	}

	/**
	 * The list filter's `status` error, in the validation error shape: null when the
	 * filter is absent or a registered plan status.
	 *
	 * @param mixed $value Param value.
	 */
	private function get_status_filter_error( $value ): ?WP_Error {
		if ( null === $value ) {
			return null;
		}

		if ( ! is_string( $value ) ) {
			$error = new InvalidInputException( ValidationErrorCode::INVALID_TYPE, 'status', 'status must be a string.', array( 'type' => 'string' ) );
		} elseif ( ! PlanStatus::is_registered( $value ) ) {
			$error = new InvalidInputException( ValidationErrorCode::NOT_REGISTERED, 'status', 'status must be a registered plan status.', array( 'value' => $value ) );
		} else {
			return null;
		}

		$exception = ValidationException::from_core( $error );

		return $this->get_validation_error( $exception );
	}

	/**
	 * Get multiple, valid extension slugs from an incoming request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array<int, string>|null|WP_Error Slugs, null for wildcard, or validation error.
	 */
	private function get_multiple_extension_slugs( WP_REST_Request $request ) {
		$raw = $request->get_param( 'extension_slug' );
		if ( null === $raw ) {
			return $this->get_extension_slug_error( ValidationErrorCode::REQUIRED, 'extension_slug is required.' );
		}
		$raw_string = trim( Coercion::coerce_string( $raw ) );
		if ( '' === $raw_string ) {
			return $this->get_extension_slug_error( ValidationErrorCode::REQUIRED, 'extension_slug is required.' );
		}

		if ( 'any' === $raw_string ) {
			return null;
		}

		$slugs = array();
		foreach ( explode( ',', $raw_string ) as $possible_slug ) {
			$slug = trim( $possible_slug );
			if ( '' === $slug || 'any' === $slug || ! $this->is_valid_extension_slug( $slug ) ) {
				return $this->get_extension_slug_error( ValidationErrorCode::INVALID_VALUE, 'extension_slug must be "any" or a comma-separated list of extension slugs.' );
			}

			$slugs[ $slug ] = $slug;
		}

		return array_values( $slugs );
	}

	/**
	 * Get a single, valid extension slug from an incoming request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return string|WP_Error Slug or validation error.
	 */
	private function get_single_extension_slug( WP_REST_Request $request ) {
		$raw = $request->get_param( 'extension_slug' );
		if ( null === $raw ) {
			return $this->get_extension_slug_error( ValidationErrorCode::REQUIRED, 'extension_slug is required.' );
		}
		if ( ! is_string( $raw ) ) {
			return $this->get_extension_slug_error( ValidationErrorCode::INVALID_TYPE, 'extension_slug must be a string.', array( 'type' => 'string' ) );
		}
		$raw_string = trim( $raw );
		if ( '' === $raw_string ) {
			return $this->get_extension_slug_error( ValidationErrorCode::REQUIRED, 'extension_slug is required.' );
		}

		if ( 'any' === $raw_string || false !== strpos( $raw_string, ',' ) || ! $this->is_valid_extension_slug( $raw_string ) ) {
			return $this->get_extension_slug_error( ValidationErrorCode::INVALID_VALUE, 'extension_slug must be a concrete extension slug.' );
		}

		return $raw_string;
	}

	/**
	 * The `extension_slug` param error, in the validation error shape.
	 *
	 * @param string               $code    Error code.
	 * @param string               $message English developer message.
	 * @param array<string, mixed> $params  Error params.
	 */
	private function get_extension_slug_error( string $code, string $message, array $params = array() ): WP_Error {
		$exception = ValidationException::from_core( new InvalidInputException( $code, 'extension_slug', $message, $params ) );

		return $this->get_validation_error( $exception );
	}

	/**
	 * Whether a value is a valid extension slug.
	 *
	 * @param string $slug Possible extension slug.
	 */
	private function is_valid_extension_slug( string $slug ): bool {
		return '' !== $slug && sanitize_key( $slug ) === $slug;
	}

	/**
	 * Not-found error.
	 */
	private function not_found_error(): WP_Error {
		return new WP_Error(
			'woocommerce_subscriptions_engine_plan_not_found',
			__( 'Plan not found.', 'woocommerce' ),
			array( 'status' => 404 )
		);
	}
}
