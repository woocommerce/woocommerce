<?php

namespace Automattic\WooCommerce\RestApi\V4\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;
use WC_Product;
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
		// 1. Pure function: prepare data (validation handled by WordPress REST API)
		$product_data = $this->prepare_product_data_for_creation( $request );

		// 2. Separate persistence operation
		try {
			$product = $this->create_product_from_data( $product_data );
			return $this->build_create_response( $product, $request );
		} catch ( \Exception $e ) {
			return $this->handle_creation_error( $e );
		}
	}

	/**
	 * Update a single product.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		// 1. Get existing product
		$product = wc_get_product( $request['id'] );
		if ( ! $product || 0 === $product->get_id() ) {
			return new WP_Error(
				'rest_post_invalid_id',
				__( 'Invalid product ID.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		// 2. Pure function: prepare update data (validation handled by WordPress REST API)
		$update_data = $this->prepare_product_data_for_update( $request );

		// 3. Separate persistence operation
		try {
			$updated_product = $this->update_product_with_data( $product, $update_data );
			$data = $this->prepare_item_for_response( $updated_product, $request );
			return rest_ensure_response( $data );
		} catch ( \Exception $e ) {
			return $this->handle_update_error( $e );
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
	 * Pure function: Prepare data for product creation.
	 *
	 * Note: WordPress REST API handles validation and sanitization automatically
	 * based on our JSON schema. This function only prepares business data.
	 *
	 * @param WP_REST_Request $request Request object with validated/sanitized data.
	 * @return array Prepared data for product creation.
	 */
	protected function prepare_product_data_for_creation( $request ) {
		$params = $request->get_params();
		
		// WordPress REST API has already validated and sanitized the data
		// based on our JSON schema, so we can trust the input here
		return array(
			'name'              => $params['name'], // Required, already validated
			'type'              => $params['type'] ?? 'simple', // Default applied by schema
			'status'            => $params['status'] ?? 'publish', // Default applied by schema
			'description'       => $params['description'] ?? '',
			'short_description' => $params['short_description'] ?? '',
			'sku'               => $params['sku'] ?? '',
			'regular_price'     => $params['regular_price'] ?? '',
			'sale_price'        => $params['sale_price'] ?? '',
			'featured'          => $params['featured'] ?? false, // Default applied by schema
			'manage_stock'      => $params['manage_stock'] ?? false, // Default applied by schema
			'stock_quantity'    => $params['stock_quantity'] ?? null,
			'stock_status'      => $params['stock_status'] ?? 'instock', // Default applied by schema
		);
	}

	/**
	 * Separate persistence: Create product from prepared data.
	 *
	 * @param array $product_data Prepared product data.
	 * @return WC_Product Created product.
	 * @throws Exception If product creation fails.
	 */
	protected function create_product_from_data( $product_data ) {
		$product = new WC_Product();
		
		$product->set_name( $product_data['name'] );
		$product->set_status( $product_data['status'] );
		$product->set_description( $product_data['description'] );
		$product->set_short_description( $product_data['short_description'] );
		
		if ( ! empty( $product_data['sku'] ) ) {
			$product->set_sku( $product_data['sku'] );
		}
		
		if ( ! empty( $product_data['regular_price'] ) ) {
			$product->set_regular_price( $product_data['regular_price'] );
		}
		
		if ( ! empty( $product_data['sale_price'] ) ) {
			$product->set_sale_price( $product_data['sale_price'] );
		}
		
		$product->set_featured( $product_data['featured'] );
		$product->set_manage_stock( $product_data['manage_stock'] );
		$product->set_stock_status( $product_data['stock_status'] );
		
		if ( $product_data['manage_stock'] && null !== $product_data['stock_quantity'] ) {
			$product->set_stock_quantity( $product_data['stock_quantity'] );
		}
		
		$product_id = $product->save();
		
		if ( ! $product_id ) {
			throw new \Exception( __( 'Failed to create product.', 'woocommerce' ) );
		}
		
		return $product;
	}

	/**
	 * Build response for successful product creation.
	 *
	 * @param WC_Product      $product Created product.
	 * @param WP_REST_Request $request Original request.
	 * @return WP_REST_Response Response object.
	 */
	protected function build_create_response( $product, $request ) {
		$request->set_param( 'context', 'edit' );
		$data = $this->prepare_item_for_response( $product, $request );
		$response = rest_ensure_response( $data );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $product->get_id() ) ) );
		return $response;
	}

	/**
	 * Handle creation errors consistently.
	 *
	 * @param Exception $e Exception that occurred.
	 * @return WP_Error Error response.
	 */
	protected function handle_creation_error( $e ) {
		return $this->handle_internal_error( $e->getMessage() );
	}

	/**
	 * Pure function: Prepare data for product update.
	 *
	 * Note: WordPress REST API handles validation and sanitization automatically
	 * based on our JSON schema. This function only prepares business data.
	 *
	 * @param WP_REST_Request $request Request object with validated/sanitized data.
	 * @return array Prepared update data.
	 */
	protected function prepare_product_data_for_update( $request ) {
		$params = $request->get_params();
		$update_data = array();
		
		// WordPress REST API has already validated and sanitized the data
		// Only include fields that are actually being updated
		$updatable_fields = array(
			'name', 'description', 'short_description', 'sku',
			'regular_price', 'sale_price', 'status', 'featured',
			'manage_stock', 'stock_quantity', 'stock_status'
		);
		
		foreach ( $updatable_fields as $field ) {
			if ( isset( $params[ $field ] ) ) {
				$update_data[ $field ] = $params[ $field ];
			}
		}
		
		return $update_data;
	}

	/**
	 * Separate persistence: Update product with prepared data.
	 *
	 * @param WC_Product $product Product to update.
	 * @param array      $update_data Prepared update data.
	 * @return WC_Product Updated product.
	 * @throws Exception If product update fails.
	 */
	protected function update_product_with_data( $product, $update_data ) {
		foreach ( $update_data as $key => $value ) {
			switch ( $key ) {
				case 'name':
					$product->set_name( $value );
					break;
				case 'description':
					$product->set_description( $value );
					break;
				case 'short_description':
					$product->set_short_description( $value );
					break;
				case 'sku':
					$product->set_sku( $value );
					break;
				case 'regular_price':
					$product->set_regular_price( $value );
					break;
				case 'sale_price':
					$product->set_sale_price( $value );
					break;
				case 'status':
					$product->set_status( $value );
					break;
				case 'featured':
					$product->set_featured( $value );
					break;
				case 'manage_stock':
					$product->set_manage_stock( $value );
					break;
				case 'stock_quantity':
					if ( $product->get_manage_stock() || ( isset( $update_data['manage_stock'] ) && $update_data['manage_stock'] ) ) {
						$product->set_stock_quantity( $value );
					}
					break;
				case 'stock_status':
					$product->set_stock_status( $value );
					break;
			}
		}
		
		$product_id = $product->save();
		
		if ( ! $product_id ) {
			throw new \Exception( __( 'Failed to update product.', 'woocommerce' ) );
		}
		
		return $product;
	}

	/**
	 * Handle update errors consistently.
	 *
	 * @param Exception $e Exception that occurred.
	 * @return WP_Error Error response.
	 */
	protected function handle_update_error( $e ) {
		return $this->handle_internal_error( $e->getMessage() );
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
	 * Get endpoint arguments for item schema.
	 *
	 * @param string $method HTTP method of the request.
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$schema = ProductSchema::get_create_schema();
		$schema_properties = ! empty( $schema['properties'] ) ? $schema['properties'] : array();
		
		// Generate validation args from JSON schema
		$endpoint_args = rest_get_endpoint_args_for_schema( $schema_properties, $method );
		
		// Add custom validation for SKU uniqueness
		if ( isset( $endpoint_args['sku'] ) ) {
			$endpoint_args['sku']['validate_callback'] = array( $this, 'validate_sku_callback' );
		}
		
		return $endpoint_args;
	}

	/**
	 * Get arguments for creating products.
	 *
	 * @return array
	 */
	public function get_create_item_args() {
		return $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE );
	}

	/**
	 * Custom validation callback for SKU field.
	 *
	 * @param mixed           $value   Value of the 'sku' argument.
	 * @param WP_REST_Request $request Request object.
	 * @param string          $param   Key of the parameter.
	 * @return true|WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_sku_callback( $value, $request, $param ) {
		// First, run standard validation
		$is_valid = rest_validate_request_arg( $value, $request, $param );
		if ( is_wp_error( $is_valid ) ) {
			return $is_valid;
		}
		
		// Skip uniqueness check if SKU is empty
		if ( empty( $value ) ) {
			return true;
		}
		
		// Check SKU uniqueness for creation
		if ( WP_REST_Server::CREATABLE === $request->get_method() ) {
			$existing_product_id = wc_get_product_id_by_sku( $value );
			if ( $existing_product_id ) {
				return new WP_Error(
					'rest_product_sku_exists',
					__( 'Product SKU already exists.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}
		}
		
		// Check SKU uniqueness for updates (exclude current product)
		if ( WP_REST_Server::EDITABLE === $request->get_method() && isset( $request['id'] ) ) {
			$existing_product_id = wc_get_product_id_by_sku( $value );
			if ( $existing_product_id && $existing_product_id !== (int) $request['id'] ) {
				return new WP_Error(
					'rest_product_sku_exists',
					__( 'Product SKU already exists.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}
		}
		
		return true;
	}

	/**
	 * Get arguments for updating products.
	 *
	 * @return array
	 */
	public function get_update_item_args() {
		return $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE );
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
