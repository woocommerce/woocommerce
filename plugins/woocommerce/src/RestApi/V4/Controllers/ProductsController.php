<?php

namespace Automattic\WooCommerce\RestApi\V4\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;
use WC_Product;
use WC_Product_Factory;
use Automattic\WooCommerce\RestApi\V4\Schemas\ProductSchema;

/**
 * Products controller for REST API v4.
 *
 * Handles CRUD operations for products with minimal implementation.
 */
class ProductsController extends AbstractController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'products';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'product';

	/**
	 * Register the routes for products.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
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
					'args'                => $this->get_create_item_args(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
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
					'args'                => $this->get_update_item_args(),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Whether to bypass trash and force deletion.', 'woocommerce' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check if a given request has access to read products.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'read' ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_view',
				__( 'Sorry, you cannot view this resource.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Check if a given request has access to read a specific product.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		$product = wc_get_product( (int) $request['id'] );

		if ( $product && 0 !== $product->get_id() && ! wc_rest_check_post_permissions( $this->post_type, 'read', $product->get_id() ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_view',
				__( 'Sorry, you cannot view this resource.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Check if a given request has access to create products.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'create' ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_create',
				__( 'Sorry, you cannot create resources.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Check if a given request has access to update a specific product.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {
		$product = wc_get_product( (int) $request['id'] );

		if ( $product && 0 !== $product->get_id() && ! wc_rest_check_post_permissions( $this->post_type, 'edit', $product->get_id() ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_edit',
				__( 'Sorry, you are not allowed to edit this resource.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Check if a given request has access to delete a specific product.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 */
	public function delete_item_permissions_check( $request ) {
		$product = wc_get_product( (int) $request['id'] );

		if ( $product && 0 !== $product->get_id() && ! wc_rest_check_post_permissions( $this->post_type, 'delete', $product->get_id() ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_delete',
				__( 'Sorry, you are not allowed to delete this resource.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Get a collection of products.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $request['per_page'],
			'paged'          => $request['page'],
		);

		if ( ! empty( $request['include'] ) ) {
			$args['post__in'] = $request['include'];
		}

		if ( ! empty( $request['exclude'] ) ) {
			$args['post__not_in'] = $request['exclude'];
		}

		$query    = new \WP_Query( $args );
		$products = array();

		foreach ( $query->posts as $post ) {
			$product = wc_get_product( $post );
			if ( $product ) {
				$data       = $this->prepare_item_for_response( $product, $request );
				$products[] = $this->prepare_response_for_collection( $data, $request );
			}
		}

		$response = rest_ensure_response( $products );
		$response->header( 'X-WP-Total', $query->found_posts );
		$response->header( 'X-WP-TotalPages', $query->max_num_pages );

		return $response;
	}

	/**
	 * Get a single product.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$product = wc_get_product( $request['id'] );

		if ( ! $product || 0 === $product->get_id() ) {
			return new WP_Error(
				'rest_post_invalid_id',
				__( 'Invalid product ID.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		$data = $this->prepare_item_for_response( $product, $request );
		return rest_ensure_response( $data );
	}

	/**
	 * Create a single product.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		try {
			$product = new \WC_Product_Simple();

			$this->update_product_from_request( $product, $request );
			$product->save();

			$data     = $this->prepare_item_for_response( $product, $request );
			$response = rest_ensure_response( $data );
			$response->set_status( 201 );
			$response->header( 'Location', rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $product->get_id() ) ) );

			return $response;
		} catch ( \Exception $e ) {
			return $this->handle_internal_error( $e, 'Product creation failed' );
		}
	}

	/**
	 * Update a single product.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		$product = wc_get_product( $request['id'] );

		if ( ! $product || 0 === $product->get_id() ) {
			return new WP_Error(
				'rest_post_invalid_id',
				__( 'Invalid product ID.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		try {
			$this->update_product_from_request( $product, $request );
			$product->save();

			$data = $this->prepare_item_for_response( $product, $request );
			return rest_ensure_response( $data );
		} catch ( \Exception $e ) {
			return $this->handle_internal_error( $e, 'Product update failed' );
		}
	}

	/**
	 * Delete a single product.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		$product = wc_get_product( $request['id'] );

		if ( ! $product || 0 === $product->get_id() ) {
			return new WP_Error(
				'rest_post_invalid_id',
				__( 'Invalid product ID.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $product, $request );

		$force = (bool) $request['force'];
		if ( $force ) {
			$result = $product->delete( true );
		} else {
			$result = $product->delete();
		}

		if ( ! $result ) {
			return new WP_Error(
				'rest_cannot_delete',
				__( 'The product cannot be deleted.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Update product from request data.
	 *
	 * @param WC_Product      $product Product object.
	 * @param WP_REST_Request $request Request object.
	 */
	protected function update_product_from_request( $product, $request ) {
		$params = $request->get_params();

		if ( isset( $params['name'] ) ) {
			$product->set_name( $params['name'] );
		}

		if ( isset( $params['description'] ) ) {
			$product->set_description( $params['description'] );
		}

		if ( isset( $params['short_description'] ) ) {
			$product->set_short_description( $params['short_description'] );
		}

		if ( isset( $params['sku'] ) ) {
			$product->set_sku( $params['sku'] );
		}

		if ( isset( $params['regular_price'] ) ) {
			$product->set_regular_price( $params['regular_price'] );
		}

		if ( isset( $params['sale_price'] ) ) {
			$product->set_sale_price( $params['sale_price'] );
		}

		if ( isset( $params['status'] ) ) {
			$product->set_status( $params['status'] );
		}

		if ( isset( $params['featured'] ) ) {
			$product->set_featured( $params['featured'] );
		}

		if ( isset( $params['manage_stock'] ) ) {
			$product->set_manage_stock( $params['manage_stock'] );
		}

		if ( isset( $params['stock_quantity'] ) ) {
			$product->set_stock_quantity( $params['stock_quantity'] );
		}

		if ( isset( $params['stock_status'] ) ) {
			$product->set_stock_status( $params['stock_status'] );
		}
	}

	/**
	 * Prepare a single product output for response.
	 *
	 * @param WC_Product      $product Product object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $product, $request ) {
		$data = array(
			'id'                => $product->get_id(),
			'name'              => $product->get_name(),
			'slug'              => $product->get_slug(),
			'type'              => $product->get_type(),
			'status'            => $product->get_status(),
			'featured'          => $product->get_featured(),
			'description'       => $product->get_description(),
			'short_description' => $product->get_short_description(),
			'sku'               => $product->get_sku(),
			'price'             => $product->get_price(),
			'regular_price'     => $product->get_regular_price(),
			'sale_price'        => $product->get_sale_price(),
			'manage_stock'      => $product->get_manage_stock(),
			'stock_quantity'    => $product->get_stock_quantity(),
			'stock_status'      => $product->get_stock_status(),
			'date_created'      => wc_rest_prepare_date_response( $product->get_date_created() ),
			'date_modified'     => wc_rest_prepare_date_response( $product->get_date_modified() ),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $product ) );

		return $response;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WC_Product $product Product object.
	 * @return array Links for the given product.
	 */
	protected function prepare_links( $product ) {
		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $product->get_id() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		return $links;
	}

	/**
	 * Get arguments for creating products.
	 *
	 * @return array
	 */
	public function get_create_item_args() {
		return array(
			'name'              => array(
				'required'          => true,
				'type'              => 'string',
				'description'       => __( 'Product name.', 'woocommerce' ),
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'type'              => array(
				'type'              => 'string',
				'default'           => 'simple',
				'enum'              => array( 'simple', 'grouped', 'external', 'variable' ),
				'description'       => __( 'Product type.', 'woocommerce' ),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'status'            => array(
				'type'              => 'string',
				'default'           => 'publish',
				'enum'              => array( 'draft', 'pending', 'private', 'publish' ),
				'description'       => __( 'Product status.', 'woocommerce' ),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'description'       => array(
				'type'              => 'string',
				'description'       => __( 'Product description.', 'woocommerce' ),
				'sanitize_callback' => 'wp_kses_post',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'short_description' => array(
				'type'              => 'string',
				'description'       => __( 'Product short description.', 'woocommerce' ),
				'sanitize_callback' => 'wp_kses_post',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'sku'               => array(
				'type'              => 'string',
				'description'       => __( 'Unique identifier.', 'woocommerce' ),
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'regular_price'     => array(
				'type'              => 'string',
				'description'       => __( 'Product regular price.', 'woocommerce' ),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'sale_price'        => array(
				'type'              => 'string',
				'description'       => __( 'Product sale price.', 'woocommerce' ),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'featured'          => array(
				'type'              => 'boolean',
				'default'           => false,
				'description'       => __( 'Featured product.', 'woocommerce' ),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'manage_stock'      => array(
				'type'              => 'boolean',
				'default'           => false,
				'description'       => __( 'Stock management at product level.', 'woocommerce' ),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'stock_quantity'    => array(
				'type'              => 'integer',
				'description'       => __( 'Stock quantity.', 'woocommerce' ),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'stock_status'      => array(
				'type'              => 'string',
				'default'           => 'instock',
				'enum'              => array( 'instock', 'outofstock', 'onbackorder' ),
				'description'       => __( 'Controls the stock status of the product.', 'woocommerce' ),
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}

	/**
	 * Get arguments for updating products.
	 *
	 * @return array
	 */
	public function get_update_item_args() {
		$args = $this->get_create_item_args();

		// Remove required validation for updates
		unset( $args['name']['required'] );

		return $args;
	}

	/**
	 * Get the product schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return ProductSchema::get_schema();
	}
}

