<?php
/**
 * REST controller for subscription engine plans: opaque CRUD over the plan read and
 * write facades. Policies pass through as JSON objects, never parsed or merged.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Rest
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api\Rest;

use Automattic\WooCommerce\SubscriptionsEngine\Api\Plans;
use Automattic\WooCommerce\SubscriptionsEngine\Api\PlanValidationException;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\PlanView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\PlanStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\Coercion;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Support\RESTPermissions;
use InvalidArgumentException;
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
	 * Writable plan fields (besides the owner, which is the request's extension slug).
	 *
	 * @var array<int, string>
	 */
	private const WRITE_FIELDS = array( 'name', 'status', 'billing_policy', 'pricing_policy', 'delivery_policy' );

	/**
	 * Policy fields: JSON objects or null.
	 *
	 * @var array<int, string>
	 */
	private const POLICY_FIELDS = array( 'billing_policy', 'pricing_policy', 'delivery_policy' );

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
							'description' => __( 'Extension slug or comma-separated list of slugs for the plan query. Use "any" to query all slugs.', 'woocommerce-subscriptions-engine' ),
							'type'        => 'string',
							'required'    => true,
						),
						'page'           => array(
							'description' => __( 'Page number for the plan query.', 'woocommerce-subscriptions-engine' ),
							'type'        => 'integer',
							'required'    => false,
						),
						'per_page'       => array(
							'description' => __( 'Number of plans per page for the plan query.', 'woocommerce-subscriptions-engine' ),
							'type'        => 'integer',
							'required'    => false,
							'default'     => self::DEFAULT_PER_PAGE,
						),
						'search'         => array(
							'description' => __( 'Search term for the plan query.', 'woocommerce-subscriptions-engine' ),
							'type'        => 'string',
							'required'    => false,
						),
						'status'         => array(
							'description'       => __( 'Status of the plans to query (any registered plan status).', 'woocommerce-subscriptions-engine' ),
							'type'              => 'string',
							'required'          => false,
							'validate_callback' => array( $this, 'validate_status_param' ),
						),
						'orderby'        => array(
							'description' => __( 'Order by field for the plan query.', 'woocommerce-subscriptions-engine' ),
							'type'        => 'string',
							'required'    => false,
							'enum'        => self::ORDERBY,
							'default'     => 'id',
						),
						'order'          => array(
							'description' => __( 'Order direction for the plan query.', 'woocommerce-subscriptions-engine' ),
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
						'description' => __( 'Unique identifier for the plan.', 'woocommerce-subscriptions-engine' ),
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
	 * Validate a status param: any registered plan status.
	 *
	 * @param mixed $value Param value.
	 * @return true|WP_Error
	 */
	public function validate_status_param( $value ) {
		if ( is_string( $value ) && PlanStatus::is_registered( $value ) ) {
			return true;
		}

		return new WP_Error(
			'rest_invalid_param',
			__( 'status must be a registered plan status.', 'woocommerce-subscriptions-engine' ),
			array( 'status' => 400 )
		);
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

		$page     = max( 1, Coercion::coerce_int( $request->get_param( 'page' ), 1 ) );
		$per_page = $this->resolve_per_page( $request );
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

		$plan = $this->plan_repository->find( Coercion::coerce_int( $request->get_param( 'id' ) ), $extension_slug );
		if ( ! $plan instanceof Plan ) {
			return $this->not_found_error();
		}

		return rest_ensure_response( $this->prepare_item_for_response( PlanView::from_plan( $plan ), $request ) );
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

		$args = $this->write_args( $request );
		if ( $args instanceof WP_Error ) {
			return $args;
		}
		$args['owner'] = $extension_slug;

		try {
			$id = Plans::create( $args );
		} catch ( PlanValidationException $e ) {
			return $this->as_bad_request( $e->get_errors() );
		} catch ( InvalidArgumentException $e ) {
			return $this->invalid_error( $e->getMessage() );
		} catch ( RuntimeException $e ) {
			return $this->write_failed_error( $e );
		}

		$plan = $this->plan_repository->find( $id );
		if ( ! $plan instanceof Plan ) {
			return $this->not_found_error();
		}

		$response = rest_ensure_response( $this->prepare_item_for_response( PlanView::from_plan( $plan ), $request ) );
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

		$id = Coercion::coerce_int( $request->get_param( 'id' ) );
		if ( ! $this->plan_repository->find( $id, $extension_slug ) instanceof Plan ) {
			return $this->not_found_error();
		}

		$args = $this->write_args( $request );
		if ( $args instanceof WP_Error ) {
			return $args;
		}

		try {
			$updated = Plans::update( $id, $args );
		} catch ( PlanValidationException $e ) {
			return $this->as_bad_request( $e->get_errors() );
		} catch ( InvalidArgumentException $e ) {
			return $this->invalid_error( $e->getMessage() );
		} catch ( RuntimeException $e ) {
			return $this->write_failed_error( $e );
		}

		$plan = $updated ? $this->plan_repository->find( $id, $extension_slug ) : null;
		if ( ! $plan instanceof Plan ) {
			return $this->not_found_error();
		}

		return rest_ensure_response( $this->prepare_item_for_response( PlanView::from_plan( $plan ), $request ) );
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
			'extension_slug'   => $item->get_owner(),
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
			'search'   => array(
				'description'       => __( 'Search term.', 'woocommerce-subscriptions-engine' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'status'   => array(
				'description'       => __( 'Limit result set to plans with a registered plan status.', 'woocommerce-subscriptions-engine' ),
				'type'              => 'string',
				'validate_callback' => array( $this, 'validate_status_param' ),
			),
			'orderby'  => array(
				'description'       => __( 'Sort collection by object attribute.', 'woocommerce-subscriptions-engine' ),
				'type'              => 'string',
				'default'           => 'id',
				'enum'              => self::ORDERBY,
				'sanitize_callback' => 'sanitize_key',
			),
			'order'    => array(
				'description'       => __( 'Order sort attribute ascending or descending.', 'woocommerce-subscriptions-engine' ),
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
					'description' => __( 'Unique identifier for the plan.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'extension_slug'   => array(
					'description' => __( 'Owning extension slug.', 'woocommerce-subscriptions-engine' ),
					'type'        => array( 'string', 'null' ),
					'context'     => array( 'view', 'edit' ),
				),
				'status'           => array(
					'description'       => __( 'Plan status (any registered plan status).', 'woocommerce-subscriptions-engine' ),
					'type'              => 'string',
					'context'           => array( 'view', 'edit' ),
					'validate_callback' => array( $this, 'validate_status_param' ),
				),
				'name'             => array(
					'description' => __( 'Display name.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'billing_policy'   => array(
					'description' => __( 'Billing payload of the owning extension.', 'woocommerce-subscriptions-engine' ),
					'type'        => array( 'object', 'null' ),
					'context'     => array( 'view', 'edit' ),
				),
				'pricing_policy'   => array(
					'description' => __( 'Pricing payload of the owning extension.', 'woocommerce-subscriptions-engine' ),
					'type'        => array( 'object', 'null' ),
					'context'     => array( 'view', 'edit' ),
				),
				'delivery_policy'  => array(
					'description' => __( 'Delivery payload of the owning extension.', 'woocommerce-subscriptions-engine' ),
					'type'        => array( 'object', 'null' ),
					'context'     => array( 'view', 'edit' ),
				),
				'date_created_gmt' => array(
					'description' => __( 'Creation time (GMT).', 'woocommerce-subscriptions-engine' ),
					'type'        => array( 'string', 'null' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'date_updated_gmt' => array(
					'description' => __( 'Last update time (GMT).', 'woocommerce-subscriptions-engine' ),
					'type'        => array( 'string', 'null' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Resolve per_page.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	private function resolve_per_page( WP_REST_Request $request ): int {
		$value = Coercion::coerce_int( $request->get_param( 'per_page' ), self::DEFAULT_PER_PAGE );
		if ( $value < 1 ) {
			return self::DEFAULT_PER_PAGE;
		}

		return min( $value, self::MAX_PER_PAGE );
	}

	/**
	 * Collect the present writable params as facade args. Policies must be JSON
	 * objects or null; they pass through as given.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array<string, mixed>|WP_Error
	 */
	private function write_args( WP_REST_Request $request ) {
		$args = array();
		foreach ( self::WRITE_FIELDS as $field ) {
			if ( ! $request->has_param( $field ) ) {
				continue;
			}

			$value = $request->get_param( $field );
			if ( in_array( $field, self::POLICY_FIELDS, true ) ) {
				$message = sprintf( '%s must be an object or null.', $field );
				if ( null !== $value && ! is_array( $value ) ) {
					return $this->invalid_error( $message );
				}
				try {
					$value = null === $value ? null : $this->associative_array( $value, $message );
				} catch ( InvalidArgumentException $e ) {
					return $this->invalid_error( $e->getMessage() );
				}
			} elseif ( 'name' === $field ) {
				$value = $this->string_param( $request, 'name' );
			}

			$args[ $field ] = $value;
		}

		return $args;
	}

	/**
	 * Present a stored policy as a JSON object: an empty payload serializes as `{}`.
	 *
	 * @param array<string, mixed>|null $policy Policy payload.
	 * @return array<string, mixed>|object|null
	 */
	private static function as_json_object( ?array $policy ) {
		if ( array() === $policy ) {
			return (object) array();
		}

		return $policy;
	}

	/**
	 * Map a failed write to a 500. The facade wraps a throwing validation callback
	 * (the cause is chained); a failed insert or update carries no cause.
	 *
	 * @param RuntimeException $e Failure.
	 */
	private function write_failed_error( RuntimeException $e ): WP_Error {
		if ( $e->getPrevious() instanceof \Throwable ) {
			return new WP_Error(
				'woocommerce_subscriptions_engine_plan_validation_failed',
				__( 'The plan could not be validated.', 'woocommerce-subscriptions-engine' ),
				array( 'status' => 500 )
			);
		}

		return new WP_Error(
			'woocommerce_subscriptions_engine_plan_update_failed',
			__( 'The plan could not be saved.', 'woocommerce-subscriptions-engine' ),
			array( 'status' => 500 )
		);
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
	 * Get multiple, valid extension slugs from an incoming request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array<int, string>|null|WP_Error Slugs, null for wildcard, or validation error.
	 */
	private function get_multiple_extension_slugs( WP_REST_Request $request ) {
		$raw = $request->get_param( 'extension_slug' );
		if ( null === $raw ) {
			return $this->invalid_error( __( 'extension_slug is required.', 'woocommerce-subscriptions-engine' ) );
		}
		$raw_string = trim( Coercion::coerce_string( $raw ) );
		if ( '' === $raw_string ) {
			return $this->invalid_error( __( 'extension_slug is required.', 'woocommerce-subscriptions-engine' ) );
		}

		if ( 'any' === $raw_string ) {
			return null;
		}

		$slugs = array();
		foreach ( explode( ',', $raw_string ) as $possible_slug ) {
			$slug = trim( $possible_slug );
			if ( '' === $slug || 'any' === $slug || ! $this->is_valid_extension_slug( $slug ) ) {
				return $this->invalid_error( __( 'extension_slug must be "any" or a comma-separated list of extension slugs.', 'woocommerce-subscriptions-engine' ) );
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
			return $this->invalid_error( __( 'extension_slug is required.', 'woocommerce-subscriptions-engine' ) );
		}
		$raw_string = trim( Coercion::coerce_string( $raw ) );
		if ( '' === $raw_string ) {
			return $this->invalid_error( __( 'extension_slug is required.', 'woocommerce-subscriptions-engine' ) );
		}

		if ( 'any' === $raw_string || false !== strpos( $raw_string, ',' ) || ! $this->is_valid_extension_slug( $raw_string ) ) {
			return $this->invalid_error( __( 'extension_slug must be a concrete extension slug.', 'woocommerce-subscriptions-engine' ) );
		}

		return $raw_string;
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
	 * Read a string param.
	 *
	 * @param WP_REST_Request $request  Request.
	 * @param string          $key      Param key.
	 * @param string          $fallback Fallback.
	 */
	private function string_param( WP_REST_Request $request, string $key, string $fallback = '' ): string {
		return sanitize_text_field( Coercion::coerce_string( $request->get_param( $key ), $fallback ) );
	}

	/**
	 * Normalize a REST object payload to a string-keyed array.
	 *
	 * @param array<array-key, mixed> $value   Request value.
	 * @param string                  $message Error message.
	 * @return array<string, mixed>
	 * @throws InvalidArgumentException If the array is not object-shaped.
	 */
	private function associative_array( array $value, string $message ): array {
		$data = array();
		foreach ( $value as $key => $item ) {
			if ( ! is_string( $key ) ) {
				throw new InvalidArgumentException( esc_html( $message ) );
			}
			$data[ $key ] = $item;
		}

		return $data;
	}

	/**
	 * Not-found error.
	 */
	private function not_found_error(): WP_Error {
		return new WP_Error(
			'woocommerce_subscriptions_engine_plan_not_found',
			__( 'Plan not found.', 'woocommerce-subscriptions-engine' ),
			array( 'status' => 404 )
		);
	}

	/**
	 * Invalid request error.
	 *
	 * @param string $message Message.
	 */
	private function invalid_error( string $message ): WP_Error {
		return new WP_Error(
			'woocommerce_subscriptions_engine_invalid_plan',
			$message,
			array( 'status' => 400 )
		);
	}
}
