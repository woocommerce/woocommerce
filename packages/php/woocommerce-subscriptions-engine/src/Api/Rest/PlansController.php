<?php
/**
 * REST controller for subscription engine plans.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Rest
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api\Rest;

use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ScalarCoercion;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PricingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Support\RESTPermissions;
use InvalidArgumentException;
use Throwable;
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
							'description' => __( 'Status of the plans to query.', 'woocommerce-subscriptions-engine' ),
							'type'        => 'string',
							'required'    => false,
							'enum'        => array( Plan::STATUS_ACTIVE, Plan::STATUS_ARCHIVED ),
						),
						'orderby'        => array(
							'description' => __( 'Order by field for the plan query.', 'woocommerce-subscriptions-engine' ),
							'type'        => 'string',
							'required'    => false,
							'enum'        => array( 'id', 'name', 'status', 'sort_order' ),
							'default'     => 'sort_order',
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
			'/' . self::REST_BASE . '/reorder',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'reorder_items' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
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

		$page     = max( 1, ScalarCoercion::coerce_int( $request->get_param( 'page' ), 1 ) );
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
						$this->prepare_item_for_response( $plan, $request )
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

		$plan = $this->plan_repository->find( ScalarCoercion::coerce_int( $request->get_param( 'id' ) ), $extension_slug );
		if ( ! $plan instanceof Plan ) {
			return $this->not_found_error();
		}

		return rest_ensure_response( $this->prepare_item_for_response( $plan, $request ) );
	}

	/**
	 * Create one global plan.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$extension_slug = $this->get_single_extension_slug( $request );
		if ( $extension_slug instanceof WP_Error ) {
			return $extension_slug;
		}

		$name = $this->string_param( $request, 'name' );
		if ( '' === $name ) {
			return $this->invalid_error( __( 'Plan name is required.', 'woocommerce-subscriptions-engine' ) );
		}

		$billing_policy = $request->get_param( 'billing_policy' );
		if ( ! is_array( $billing_policy ) ) {
			return $this->invalid_error( __( 'billing_policy is required.', 'woocommerce-subscriptions-engine' ) );
		}

		try {
			$billing_policy = $this->associative_array( $billing_policy, 'billing_policy must be an object.' );

			$plan = Plan::create(
				array(
					'name'           => $name,
					'description'    => $this->nullable_string_param( $request, 'description' ),
					'billing_policy' => BillingPolicy::from_array( $billing_policy ),
					'pricing_policy' => $this->pricing_policy_from_param( $request->get_param( 'pricing_policy' ), null ),
					'category'       => $this->string_param( $request, 'category', Plan::DEFAULT_CATEGORY ),
					'status'         => $this->string_param( $request, 'status', Plan::STATUS_ACTIVE ),
					'sort_order'     => ScalarCoercion::coerce_int( $request->get_param( 'sort_order' ) ),
					'extension_slug' => $extension_slug,
				)
			);
			$this->plan_repository->insert( $plan );
		} catch ( Throwable $e ) {
			return $this->invalid_error( $e->getMessage() );
		}

		$response = rest_ensure_response( $this->prepare_item_for_response( $plan, $request ) );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Partially update a plan.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$extension_slug = $this->get_single_extension_slug( $request );
		if ( $extension_slug instanceof WP_Error ) {
			return $extension_slug;
		}

		$plan = $this->plan_repository->find( ScalarCoercion::coerce_int( $request->get_param( 'id' ) ), $extension_slug );
		if ( ! $plan instanceof Plan ) {
			return $this->not_found_error();
		}

		try {
			if ( $request->has_param( 'name' ) ) {
				$name = $this->string_param( $request, 'name' );
				if ( '' === $name ) {
					return $this->invalid_error( __( 'Plan name is required.', 'woocommerce-subscriptions-engine' ) );
				}
				$plan->set_name( $name );
			}

			if ( $request->has_param( 'description' ) ) {
				$plan->set_description( $this->nullable_string_param( $request, 'description' ) );
			}

			if ( $request->has_param( 'billing_policy' ) ) {
				$billing_policy = $request->get_param( 'billing_policy' );
				if ( ! is_array( $billing_policy ) ) {
					return $this->invalid_error( __( 'billing_policy must be an object.', 'woocommerce-subscriptions-engine' ) );
				}
				$billing_policy = $this->associative_array( $billing_policy, 'billing_policy must be an object.' );
				$plan->set_billing_policy(
					BillingPolicy::from_array(
						array_merge( $plan->get_billing_policy()->to_array(), $billing_policy )
					)
				);
			}

			if ( $request->has_param( 'pricing_policy' ) ) {
				$plan->set_pricing_policy(
					$this->pricing_policy_from_param( $request->get_param( 'pricing_policy' ), $plan->get_pricing_policy() )
				);
			}

			if ( $request->has_param( 'status' ) ) {
				$plan->set_status( $this->string_param( $request, 'status', Plan::STATUS_ACTIVE ) );
			}

			if ( $request->has_param( 'sort_order' ) ) {
				$plan->set_sort_order( ScalarCoercion::coerce_int( $request->get_param( 'sort_order' ) ) );
			}

			if ( ! $this->plan_repository->update( $plan ) ) {
				return new WP_Error(
					'woocommerce_subscriptions_engine_plan_update_failed',
					__( 'The plan could not be saved.', 'woocommerce-subscriptions-engine' ),
					array( 'status' => 500 )
				);
			}
		} catch ( Throwable $e ) {
			return $this->invalid_error( $e->getMessage() );
		}

		return rest_ensure_response( $this->prepare_item_for_response( $plan, $request ) );
	}

	/**
	 * Reorder plans.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function reorder_items( $request ) {
		$extension_slug = $this->get_single_extension_slug( $request );
		if ( $extension_slug instanceof WP_Error ) {
			return $extension_slug;
		}

		$ids = $request->get_param( 'ids' );
		if ( ! is_array( $ids ) ) {
			return $this->invalid_error( __( 'ids must be an array of plan ids.', 'woocommerce-subscriptions-engine' ) );
		}

		$sort_order_by_id = array();
		$response_ids     = array();
		foreach ( array_values( $ids ) as $index => $raw_id ) {
			$id = ScalarCoercion::coerce_nullable_int( $raw_id );
			if ( null === $id || $id <= 0 ) {
				return $this->invalid_error( __( 'ids must contain only positive integers.', 'woocommerce-subscriptions-engine' ) );
			}
			if ( isset( $sort_order_by_id[ $id ] ) ) {
				return $this->invalid_error( __( 'ids must not contain duplicate plan ids.', 'woocommerce-subscriptions-engine' ) );
			}
			$sort_order_by_id[ $id ] = $index;
			$response_ids[]          = $id;
		}

		if ( ! $this->plan_repository->reorder( $extension_slug, $sort_order_by_id ) ) {
			return new WP_Error(
				'woocommerce_subscriptions_engine_reorder_failed',
				__( 'Plan reorder failed.', 'woocommerce-subscriptions-engine' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response( array( 'ids' => $response_ids ) );
	}

	/**
	 * Serialize a plan.
	 *
	 * @param Plan            $item    Plan.
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$pricing = $item->get_pricing_policy();

		$data = array(
			'id'             => $item->get_id(),
			'name'           => $item->get_name(),
			'description'    => $item->get_description(),
			'scope'          => 'global',
			'status'         => $item->get_status(),
			'sort_order'     => $item->get_sort_order(),
			'extension_slug' => $item->get_extension_slug(),
			'billing_policy' => $item->get_billing_policy()->to_array(),
			'pricing_policy' => null !== $pricing ? $pricing->to_array() : null,
		);

		$context = ScalarCoercion::coerce_string( $request->get_param( 'context' ), 'view' );
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
				'description'       => __( 'Limit result set to plans with a status.', 'woocommerce-subscriptions-engine' ),
				'type'              => 'string',
				'enum'              => Plan::ALLOWED_STATUSES,
				'sanitize_callback' => 'sanitize_key',
			),
			'orderby'  => array(
				'description'       => __( 'Sort collection by object attribute.', 'woocommerce-subscriptions-engine' ),
				'type'              => 'string',
				'default'           => 'sort_order',
				'enum'              => array( 'id', 'name', 'sort_order', 'date_created_gmt', 'date_updated_gmt' ),
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
				'id'             => array(
					'description' => __( 'Unique identifier for the plan.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'name'           => array(
					'description' => __( 'Display name.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'description'    => array(
					'description' => __( 'Optional description.', 'woocommerce-subscriptions-engine' ),
					'type'        => array( 'string', 'null' ),
					'context'     => array( 'view', 'edit' ),
				),
				'scope'          => array(
					'description' => __( 'Plan scope.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'status'         => array(
					'description' => __( 'Plan status.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'string',
					'enum'        => Plan::ALLOWED_STATUSES,
					'context'     => array( 'view', 'edit' ),
				),
				'sort_order'     => array(
					'description' => __( 'Manual sort order.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'extension_slug' => array(
					'description' => __( 'Owning extension slug.', 'woocommerce-subscriptions-engine' ),
					'type'        => array( 'string', 'null' ),
					'context'     => array( 'view', 'edit' ),
				),
				'billing_policy' => array(
					'description' => __( 'Billing policy.', 'woocommerce-subscriptions-engine' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
				),
				'pricing_policy' => array(
					'description' => __( 'Pricing policy.', 'woocommerce-subscriptions-engine' ),
					'type'        => array( 'object', 'null' ),
					'context'     => array( 'view', 'edit' ),
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
		$value = ScalarCoercion::coerce_int( $request->get_param( 'per_page' ), self::DEFAULT_PER_PAGE );
		if ( $value < 1 ) {
			return self::DEFAULT_PER_PAGE;
		}

		return min( $value, self::MAX_PER_PAGE );
	}

	/**
	 * Build a pricing policy from a request param, preserving omitted existing keys.
	 *
	 * @param mixed              $value    Request value.
	 * @param PricingPolicy|null $existing Existing policy.
	 * @return PricingPolicy|null
	 * @throws InvalidArgumentException If the param shape is invalid.
	 */
	private function pricing_policy_from_param( $value, ?PricingPolicy $existing ): ?PricingPolicy {
		if ( null === $value ) {
			return null;
		}

		if ( ! is_array( $value ) ) {
			throw new InvalidArgumentException( 'pricing_policy must be an object or null.' );
		}

		$value = $this->associative_array( $value, 'pricing_policy must be an object or null.' );
		$data  = null !== $existing ? $existing->to_array() : array();
		if ( array_key_exists( 'policies', $value ) ) {
			$data['policies'] = $value['policies'];
		}
		if ( array_key_exists( 'one_time_fees', $value ) ) {
			$data['one_time_fees'] = $value['one_time_fees'];
		}

		return PricingPolicy::from_array( $data );
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
		$raw_string = trim( ScalarCoercion::coerce_string( $raw ) );
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
		$raw_string = trim( ScalarCoercion::coerce_string( $raw ) );
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
		return sanitize_text_field( ScalarCoercion::coerce_string( $request->get_param( $key ), $fallback ) );
	}

	/**
	 * Read a nullable string param.
	 *
	 * @param WP_REST_Request $request Request.
	 * @param string          $key     Param key.
	 */
	private function nullable_string_param( WP_REST_Request $request, string $key ): ?string {
		$value = ScalarCoercion::coerce_nullable_string( $request->get_param( $key ) );
		if ( null === $value || '' === $value ) {
			return null;
		}

		return sanitize_text_field( $value );
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
