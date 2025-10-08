<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * REST API Customers controller
 *
 * Handles route registration, permissions, CRUD operations, and schema definition.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Customers;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractController;
use Automattic\WooCommerce\StoreApi\Utilities\Pagination;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Customers\CustomerSchema;
use WP_Http;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Customers Controller.
 */
class Controller extends AbstractController {
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'customers';

	/**
	 * Post type used for customers.
	 *
	 * @var string
	 */
	protected $post_type = 'customer';

	/**
	 * Schema class for this route.
	 *
	 * @var CustomerSchema
	 */
	protected $item_schema;

	/**
	 * Initialize the controller.
	 *
	 * @param CustomerSchema $item_schema Customer schema class.
	 * @internal
	 */
	final public function init( CustomerSchema $item_schema ) {
		$this->item_schema = $item_schema;
	}

	/**
	 * Get the schema for the current resource. This use consumed by the AbstractController to generate the item schema
	 * after running various hooks on the response.
	 */
	protected function get_schema(): array {
		return $this->item_schema->get_item_schema();
	}

	/**
	 * Register the routes for customers.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'schema' => array( $this, 'get_public_item_schema' ),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array_merge(
						$this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
						array(
							'email'    => array(
								'required'    => true,
								'type'        => 'string',
								'description' => __( 'New user email address.', 'woocommerce' ),
							),
							'username' => array(
								'required'    => 'no' === get_option( 'woocommerce_registration_generate_username', 'yes' ),
								'description' => __( 'New user username.', 'woocommerce' ),
								'type'        => 'string',
							),
							'password' => array(
								'required'    => 'no' === get_option( 'woocommerce_registration_generate_password', 'no' ),
								'description' => __( 'New user password.', 'woocommerce' ),
								'type'        => 'string',
							),
						)
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'schema' => array( $this, 'get_public_item_schema' ),
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
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
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force'    => array(
							'default'     => false,
							'type'        => 'boolean',
							'description' => __( 'Required to be true, as resource does not support trashing.', 'woocommerce' ),
						),
						'reassign' => array(
							'default'     => 0,
							'type'        => 'integer',
							'description' => __( 'ID to reassign posts to.', 'woocommerce' ),
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/batch',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'batch_items' ),
					'permission_callback' => array( $this, 'batch_items_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_batch_schema' ),
			)
		);
	}

	/**
	 * Get a single customer.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$user = \Automattic\WooCommerce\Internal\Utilities\Users::get_user_in_current_site( $request['id'] );
		if ( is_wp_error( $user ) ) {
			$user->add_data( array( 'status' => 404 ) );
			return $user;
		}

		$customer = $this->prepare_item_for_response( $user, $request );
		$response = rest_ensure_response( $customer );

		return $response;
	}

	/**
	 * Prepare a single customer object for response.
	 *
	 * @param WC_Customer     $customer Customer object.
	 * @param WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function get_item_response( $customer, WP_REST_Request $request ): array {
		return $this->item_schema->get_item_response( $customer, $request, $this->get_fields_for_response( $request ) );
	}
}
