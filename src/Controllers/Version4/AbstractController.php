<?php
/**
 * REST Controller
 *
 * It's required to follow "Controller Classes" guide before extending this class:
 * <https://developer.wordpress.org/rest-api/extending-the-rest-api/controller-classes/>
 *
 * @class   \WC_REST_Controller
 * @see     https://developer.wordpress.org/rest-api/extending-the-rest-api/controller-classes/
 * @package WooCommerce/RestApi
 */

namespace WooCommerce\RestApi\Controllers\Version4;

defined( 'ABSPATH' ) || exit;

use \WP_REST_Controller;

/**
 * Abstract Rest Controller Class
 *
 * @package WooCommerce/RestApi
 * @extends  WP_REST_Controller
 * @version  2.6.0
 */
abstract class AbstractController extends WP_REST_Controller {
	use BatchTrait;

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '';

	/**
	 * Register route for items requests.
	 *
	 * @param array $methods Supported methods. read, create.
	 */
	protected function register_items_route( $methods = [ 'read', 'create' ] ) {
		$routes           = [];
		$routes['schema'] = [ $this, 'get_public_item_schema' ];

		if ( in_array( 'read', $methods, true ) ) {
			$routes[] = array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			);
		}

		if ( in_array( 'create', $methods, true ) ) {
			$routes[] = array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
			);
		}

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			$routes,
			true
		);
	}

	/**
	 * Register route for item create/get/delete/update requests.
	 *
	 * @param array $methods Supported methods. read, create.
	 */
	protected function register_item_route( $methods = [ 'read', 'edit', 'delete' ] ) {
		$routes           = [];
		$routes['schema'] = [ $this, 'get_public_item_schema' ];
		$routes['args']   = [
			'id' => [
				'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
				'type'        => 'integer',
			],
		];

		if ( in_array( 'read', $methods, true ) ) {
			$routes[] = array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param(
						array(
							'default' => 'view',
						)
					),
				),
			);
		}

		if ( in_array( 'edit', $methods, true ) ) {
			$routes[] = array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
			);
		}

		if ( in_array( 'delete', $methods, true ) ) {
			$routes[] = array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'                => array(
					'force' => array(
						'default'     => false,
						'description' => __( 'Whether to bypass trash and force deletion.', 'woocommerce' ),
						'type'        => 'boolean',
					),
				),
			);
		}

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			$routes,
			true
		);
	}

	/**
	 * Add the schema from additional fields to an schema array.
	 *
	 * @param array $schema Schema array.
	 * @return array
	 */
	protected function add_additional_fields_schema( $schema ) {
		$schema               = parent::add_additional_fields_schema( $schema );
		$object_type          = $schema['title'];
		$schema['properties'] = apply_filters( 'woocommerce_rest_' . $object_type . '_schema', $schema['properties'] );
		return $schema;
	}
}
