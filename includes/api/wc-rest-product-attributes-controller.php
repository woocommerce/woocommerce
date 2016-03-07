<?php
/**
 * REST API Product Attributes controller
 *
 * Handles requests to the products/attributes endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Product Attributes controller class.
 *
 * @package WooCommerce/API
 * @extends WP_REST_Controller
 */
class WC_REST_Product_Attributes_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	public $namespace = 'wc/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'products/attributes';

	/**
	 * Attribute name.
	 *
	 * @var string
	 */
	protected $attribute = '';

	/**
	 * Register the routes for product attributes.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
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
				'args'                => array_merge( $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ), array(
					'name' => array(
						'required' => true,
					),
				) ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		));

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context'         => $this->get_context_param( array( 'default' => 'view' ) ),
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
					'force' => array(
						'default'     => false,
						'description' => __( 'Required to be true, as resource does not support trashing.', 'woocommerce' ),
					),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Check if a given request has access to read the terms.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( 'edit' === $request['context'] && ! current_user_can( 'manage_product_terms' ) ) {
			return new WP_Error( 'woocommerce_rest_forbidden_context', __( 'Sorry, you cannot view this resource with edit context.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to create a term.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		$taxonomy = $this->get_taxonomy( $request );
		if ( ! $taxonomy ) {
			return new WP_Error( "woocommerce_rest_taxonomy_invalid", __( "Resource doesn't exist.", 'woocommerce' ), array( 'status' => 404 ) );
		}

		$taxonomy_obj = get_taxonomy( $taxonomy );
		if ( ! current_user_can( $taxonomy_obj->cap->manage_terms ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you cannot create new resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read a term.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to update a term.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		$taxonomy = $this->get_taxonomy( $request );
		if ( ! $taxonomy ) {
			return new WP_Error( "woocommerce_rest_taxonomy_invalid", __( "Resource doesn't exist.", 'woocommerce' ), array( 'status' => 404 ) );
		}

		$taxonomy_obj = get_taxonomy( $taxonomy );
		if ( ! current_user_can( $taxonomy_obj->cap->edit_terms ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_update', __( 'Sorry, you cannot update resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to delete a term.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function delete_item_permissions_check( $request ) {
		$taxonomy = $this->get_taxonomy( $request );
		if ( ! $taxonomy ) {
			return new WP_Error( "woocommerce_rest_taxonomy_invalid", __( "Resource doesn't exist.", 'woocommerce' ), array( 'status' => 404 ) );
		}

		$taxonomy_obj = get_taxonomy( $taxonomy );
		if ( ! current_user_can( $taxonomy_obj->cap->delete_terms ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_delete', __( 'Sorry, you cannot delete resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get all attributes.
	 *
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function get_items( $request ) {
		$attributes = wc_get_attribute_taxonomies();

		$data = array();
		foreach ( $attributes as $attribute_obj ) {
			$attribute = $this->prepare_item_for_response( $attribute_obj, $request );
			$attribute = $this->prepare_response_for_collection( $attribute );
			$data[] = $attribute;
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Get a single attribute.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Request|WP_Error
	 */
	public function get_item( $request ) {
		global $wpdb;

		$attribute = $this->get_attribute( $request['id'] );

		if ( is_wp_error( $attribute ) ) {
			return $attribute;
		}

		$response = $this->prepare_item_for_response( $attribute, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Prepare a single product attribute output for response.
	 *
	 * @param obj $item Term object.
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response $response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = array(
			'id'           => (int) $item->attribute_id,
			'name'         => $item->attribute_label,
			'slug'         => wc_attribute_taxonomy_name( $item->attribute_name ),
			'type'         => $item->attribute_type,
			'order_by'     => $item->attribute_orderby,
			'has_archives' => (bool) $item->attribute_public,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item ) );

		/**
		 * Filter a term item returned from the API.
		 *
		 * Allows modification of the product attribute data right before it is returned.
		 *
		 * @param WP_REST_Response  $response  The response object.
		 * @param object            $item      The original term object.
		 * @param WP_REST_Request   $request   Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_product_attribute', $response, $item, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param object $attribute Attribute object.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array Links for the given attribute.
	 */
	protected function prepare_links( $attribute ) {
		$base = '/' . $this->namespace . '/' . $this->rest_base;

		$links = array(
			'self' => array(
				'href' => rest_url( trailingslashit( $base ) . $attribute->attribute_id ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		return $links;
	}

	/**
	 * Get the Term's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => 'product_attribute',
			'type'                 => 'object',
			'properties'           => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name' => array(
					'description' => __( 'Attribute name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'slug' => array(
					'description' => __( 'An alphanumeric identifier for the resource unique to its type.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'type' => array(
					'description' => __( 'Type of attribute.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'select',
					'enum'        => array_keys( wc_get_attribute_types() ),
					'context'     => array( 'view', 'edit' ),
				),
				'order_by' => array(
					'description' => __( 'Default sort order.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'menu_order',
					'enum'        => array( 'menu_order', 'name', 'name_num', 'id' ),
					'context'     => array( 'view', 'edit' ),
				),
				'has_archives' => array(
					'description' => __( 'Enable/Disable attribute archives.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$new_params = array();
		$new_params['context'] = $this->get_context_param( array( 'default' => 'view' ) );

		return $new_params;
	}

	/**
	 * Get attribute name.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return int|WP_Error
	 */
	protected function get_taxonomy( $request ) {
		if ( '' !== $this->attribute ) {
			return $this->attribute;
		}

		if ( $request['id'] ) {
			$name = wc_attribute_taxonomy_name_by_id( (int) $request['id'] );

			$this->attribute = $name;
		}

		return $this->attribute;
	}

	/**
	 * Get attribute data.
	 *
	 * @param int $id Attribute ID.
	 * @return stdClass|WP_Error
	 */
	protected function get_attribute( $id ) {
		$attribute = $wpdb->get_row( $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->prefix}woocommerce_attribute_taxonomies
			WHERE attribute_id = %d
		 ", $id ) );

		if ( is_wp_error( $attribute ) || is_null( $attribute ) ) {
			return new WP_Error( 'woocommerce_rest_attribute_invalid', __( "Resource doesn't exist.", 'woocommerce' ), array( 'status' => 404 ) );
		}

		return $attribute;
	}
}
