<?php
/**
 * REST API variations controller
 *
 * Handles requests to the /products/<product_id>/variations endpoints.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API variations controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Products_Controller
 */
class WC_REST_Product_Variations_Controller extends WC_REST_Products_Controller {

	/**
 	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'products/(?P<product_id>[\d]+)/variations';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'product_variation';

	/**
	 * Initialize product actions (parent).
	 */
	public function __construct() {
		add_filter( "woocommerce_rest_{$this->post_type}_query", array( $this, 'add_product_id' ), 9, 2 );
		parent::__construct();
	}

	/**
	 * Register the routes for products.
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
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
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
					'force' => array(
						'default'     => false,
						'description' => __( 'Whether to bypass trash and force deletion.', 'woocommerce' ),
					),
					'reassign' => array(),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/batch', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'batch_items' ),
				'permission_callback' => array( $this, 'batch_items_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			'schema' => array( $this, 'get_public_batch_schema' ),
		) );
	}

	/**
	 * Adds the parent product ID to the query so we filter / get the correct variations.
	 *
	 * @param array $args
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function add_product_id( $args, $request ) {
		$args['post_parent'] = $request['product_id'];
		return $args;
	}

	/**
	 * Prepare a single variation output for response.
	 *
	 * @param WP_Post $post Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $data
	 */
	public function prepare_item_for_response( $post, $request ) {
		$variation = wc_get_product( $post );
		$data      = array(
			'id'                 => $variation->get_id(),
			'date_created'       => wc_rest_prepare_date_response( $variation->get_date_created() ),
			'date_modified'      => wc_rest_prepare_date_response( $variation->get_date_modified() ),
			'description'        => $variation->get_description(),
			'permalink'          => $variation->get_permalink(),
			'sku'                => $variation->get_sku(),
			'price'              => $variation->get_price(),
			'regular_price'      => $variation->get_regular_price(),
			'sale_price'         => $variation->get_sale_price(),
			'date_on_sale_from'  => $variation->get_date_on_sale_from() ? date( 'Y-m-d', $variation->get_date_on_sale_from() ) : '',
			'date_on_sale_to'    => $variation->get_date_on_sale_to() ? date( 'Y-m-d', $variation->get_date_on_sale_to() ) : '',
			'on_sale'            => $variation->is_on_sale(),
			'visible'            => $variation->is_visible(),
			'purchasable'        => $variation->is_purchasable(),
			'virtual'            => $variation->is_virtual(),
			'downloadable'       => $variation->is_downloadable(),
			'downloads'          => $this->get_downloads( $variation ),
			'download_limit'     => '' !== $variation->get_download_limit() ? (int) $variation->get_download_limit() : -1,
			'download_expiry'    => '' !== $variation->get_download_expiry() ? (int) $variation->get_download_expiry() : -1,
			'tax_status'         => $variation->get_tax_status(),
			'tax_class'          => $variation->get_tax_class(),
			'manage_stock'       => $variation->managing_stock(),
			'stock_quantity'     => $variation->get_stock_quantity(),
			'in_stock'           => $variation->is_in_stock(),
			'backorders'         => $variation->get_backorders(),
			'backorders_allowed' => $variation->backorders_allowed(),
			'backordered'        => $variation->is_on_backorder(),
			'weight'             => $variation->get_weight(),
			'dimensions'         => array(
				'length' => $variation->get_length(),
				'width'  => $variation->get_width(),
				'height' => $variation->get_height(),
			),
			'shipping_class'     => $variation->get_shipping_class(),
			'shipping_class_id'  => $variation->get_shipping_class_id(),
			'image'              => $this->get_images( $variation ),
			'attributes'         => $this->get_attributes( $variation ),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $variation, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type, refers to post_type of the post being
		 * prepared for the response.
		 *
		 * @param WP_REST_Response   $response   The response object.
		 * @param WP_Post            $post       Post object.
		 * @param WP_REST_Request    $request    Request object.
		 */
		return apply_filters( "woocommerce_rest_prepare_{$this->post_type}", $response, $post, $request );
	}

	/**
	 * Prepare a single variation for create or update.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_Error|stdClass $data Post object.
	 */
	protected function prepare_item_for_database( $request ) {
		if ( isset( $request['id'] ) ) {
			$variation = wc_get_product( absint( $request['id'] ) );
		} else {
			$variation = new WC_Product_Variation();
		}

		$variation->set_parent_id( absint( $request['product_id'] ) );

		/**
		 * Filter the query_vars used in `get_items` for the constructed query.
		 *
		 * The dynamic portion of the hook name, $this->post_type, refers to post_type of the post being
		 * prepared for insertion.
		 *
		 * @param WC_Product_Variation $variation An object representing a single item prepared
		 *                                        for inserting or updating the database.
		 * @param WP_REST_Request      $request   Request object.
		 */
		return apply_filters( "woocommerce_rest_pre_insert_{$this->post_type}", $variation, $request );
	}

	/**
	 * Update post meta fields.
	 *
	 * @param WP_Post $post
	 * @param WP_REST_Request $request
	 * @return bool|WP_Error
	 */
	protected function update_post_meta_fields( $post, $request ) {
		try {
			$variable_product = wc_get_product( $post );
			$product          = wc_get_product( $variable_product->get_parent_id() );
			$this->save_variations_data( $product, $request, true );
			return true;
		} catch ( WC_REST_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Add post meta fields.
	 *
	 * @param WP_Post $post
	 * @param WP_REST_Request $request
	 * @return bool|WP_Error
	 */
	protected function add_post_meta_fields( $post, $request ) {
		try {
			$variable_product = wc_get_product( $post->ID );
			$product          = wc_get_product( $variable_product->get_parent_id() );
			$request['id']    = $post->ID;
			$this->save_variations_data( $product, $request, true );
			return true;
		} catch ( WC_REST_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Delete a variation.
	 *
	 * @param WP_REST_Request $request Full details about the request
	 * @return WP_Error|boolean
	 */
	public function delete_item( $request ) {
		$request['id'] = is_array( $request['id'] ) ? $request['id']['id'] : $request['id'];
		return parent::delete_item( $request );
	}

	/**
	 * Bulk create, update and delete items.
	 *
	 * @since  2.7.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array Of WP_Error or WP_REST_Response.
	 */
	public function batch_items( $request ) {
		$items       = array_filter( $request->get_params() );
		$params      = $request->get_url_params();
		$product_id  = $params['product_id'];
		$body_params = array();

		foreach ( array( 'update', 'create', 'delete' ) as $batch_type ) {
			if ( ! empty( $items[ $batch_type ] ) ) {
				$injected_items = array();
				foreach ( $items[ $batch_type ] as $item ) {
					$injected_items[] = array_merge( array( 'product_id' => $product_id ), $item );
				}
				$body_params[ $batch_type ] = $injected_items;
			}
		}

		$request = new WP_REST_Request( $request->get_method() );
		$request->set_body_params( $body_params );

		return parent::batch_items( $request );
	}

	/**
	 * Get the Variation's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$weight_unit    = get_option( 'woocommerce_weight_unit' );
		$dimension_unit = get_option( 'woocommerce_dimension_unit' );
		$schema         = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created' => array(
					'description' => __( "The date the variation was created, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified' => array(
					'description' => __( "The date the variation was last modified, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => __( 'Variation description.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'permalink' => array(
					'description' => __( 'Variation URL.', 'woocommerce' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'sku' => array(
					'description' => __( 'Unique identifier.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'price' => array(
					'description' => __( 'Current variation price.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'regular_price' => array(
					'description' => __( 'Variation regular price.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'sale_price' => array(
					'description' => __( 'Variation sale price.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_on_sale_from' => array(
					'description' => __( 'Start date of sale price.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_on_sale_to' => array(
					'description' => __( 'End data of sale price.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'on_sale' => array(
					'description' => __( 'Shows if the variation is on sale.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'visible' => array(
					'description' => __( "Define if the attribute is visible on the \"Additional information\" tab in the product's page.", 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => true,
					'context'     => array( 'view', 'edit' ),
				),
				'purchasable' => array(
					'description' => __( 'Shows if the variation can be bought.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'virtual' => array(
					'description' => __( 'If the variation is virtual.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'downloadable' => array(
					'description' => __( 'If the variation is downloadable.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'downloads' => array(
					'description' => __( 'List of downloadable files.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id' => array(
								'description' => __( 'File MD5 hash.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'name' => array(
								'description' => __( 'File name.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'file' => array(
								'description' => __( 'File URL.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
				'download_limit' => array(
					'description' => __( 'Amount of times the variation can be downloaded.', 'woocommerce' ),
					'type'        => 'integer',
					'default'     => -1,
					'context'     => array( 'view', 'edit' ),
				),
				'download_expiry' => array(
					'description' => __( 'Number of days that the customer has up to be able to download the variation.', 'woocommerce' ),
					'type'        => 'integer',
					'default'     => -1,
					'context'     => array( 'view', 'edit' ),
				),
				'tax_status' => array(
					'description' => __( 'Tax status.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'taxable',
					'enum'        => array( 'taxable', 'shipping', 'none' ),
					'context'     => array( 'view', 'edit' ),
				),
				'tax_class' => array(
					'description' => __( 'Tax class.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'manage_stock' => array(
					'description' => __( 'Stock management at variation level.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'stock_quantity' => array(
					'description' => __( 'Stock quantity.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'in_stock' => array(
					'description' => __( 'Controls whether or not the variation is listed as "in stock" or "out of stock" on the frontend.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => true,
					'context'     => array( 'view', 'edit' ),
				),
				'backorders' => array(
					'description' => __( 'If managing stock, this controls if backorders are allowed.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'no',
					'enum'        => array( 'no', 'notify', 'yes' ),
					'context'     => array( 'view', 'edit' ),
				),
				'backorders_allowed' => array(
					'description' => __( 'Shows if backorders are allowed.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'backordered' => array(
					'description' => __( 'Shows if the variation is on backordered.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'weight' => array(
					/* translators: %s: weight unit */
					'description' => sprintf( __( 'Variation weight (%s).', 'woocommerce' ), $weight_unit ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'dimensions' => array(
					'description' => __( 'Variation dimensions.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'length' => array(
							/* translators: %s: dimension unit */
							'description' => sprintf( __( 'Variation length (%s).', 'woocommerce' ), $dimension_unit ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'width' => array(
							/* translators: %s: dimension unit */
							'description' => sprintf( __( 'Variation width (%s).', 'woocommerce' ), $dimension_unit ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'height' => array(
							/* translators: %s: dimension unit */
							'description' => sprintf( __( 'Variation height (%s).', 'woocommerce' ), $dimension_unit ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'shipping_class' => array(
					'description' => __( 'Shipping class slug.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'shipping_class_id' => array(
					'description' => __( 'Shipping class ID.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'image' => array(
					'description' => __( 'Variation image data.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'id' => array(
							'description' => __( 'Image ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'date_created' => array(
							'description' => __( "The date the image was created, in the site's timezone.", 'woocommerce' ),
							'type'        => 'date-time',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'date_modified' => array(
							'description' => __( "The date the image was last modified, in the site's timezone.", 'woocommerce' ),
							'type'        => 'date-time',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'src' => array(
							'description' => __( 'Image URL.', 'woocommerce' ),
							'type'        => 'string',
							'format'      => 'uri',
							'context'     => array( 'view', 'edit' ),
						),
						'name' => array(
							'description' => __( 'Image name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'alt' => array(
							'description' => __( 'Image alternative text.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'position' => array(
							'description' => __( 'Image position. 0 means that the image is featured.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'attributes' => array(
					'description' => __( 'List of attributes.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id' => array(
								'description' => __( 'Attribute ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'name' => array(
								'description' => __( 'Attribute name.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'option' => array(
								'description' => __( 'Selected attribute term name.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WC_Product $product Product object.
	 * @param WP_REST_Request $request Request object.
	 * @return array Links for the given product.
	 */
	protected function prepare_links( $variation, $request ) {
		$product_id = (int) $request['product_id'];
		$base       = str_replace( '(?P<product_id>[\d]+)', $product_id, $this->rest_base );
		$links      = array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $base, $variation->get_id() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ),
			),
			'up' => array(
				'href' => rest_url( sprintf( '/%s/products/%d', $this->namespace, $product_id ) ),
			),
		);
		return $links;
	}

}
