<?php
/**
 * REST API Products controller
 *
 * Handles requests to the /products endpoint.
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
 * REST API Products controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Posts_Controller
 */
class WC_REST_Products_Controller extends WC_REST_Posts_Controller {

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
	}

	/**
	 * Get the Order's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
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
				'name' => array(
					'description' => __( 'Product name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'permalink' => array(
					'description' => __( 'Product URL.', 'woocommerce' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created' => array(
					'description' => __( "The date the product was created, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified' => array(
					'description' => __( "The date the product was last modified, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'type' => array(
					'description' => __( 'Product type.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'simple',
					'enum'        => array_keys( wc_get_product_types() ),
					'context'     => array( 'view', 'edit' ),
				),
				'status' => array(
					'description' => __( 'Product status (post status).', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'publish',
					'enum'        => array_keys( get_post_statuses() ),
					'context'     => array( 'view', 'edit' ),
				),
				'featured' => array(
					'description' => __( 'Featured product.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'catalog_visibility' => array(
					'description' => __( 'Catalog visibility.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'visible',
					'enum'        => array( 'visible', 'catalog', 'search', 'hidden' ),
					'context'     => array( 'view', 'edit' ),
				),
				'description' => array(
					'description' => __( 'Product description.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'short_description' => array(
					'description' => __( 'Product short description.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'sku' => array(
					'description' => __( 'Unique identifier.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'price' => array(
					'description' => __( 'Current product price.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'regular_price' => array(
					'description' => __( 'Product regular price.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'sale_price' => array(
					'description' => __( 'Product sale price.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'sale_price_dates_from' => array(
					'description' => __( 'Start date of sale price.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'sale_price_dates_to' => array(
					'description' => __( 'End data of sale price.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'price_html' => array(
					'description' => __( 'Price formatted in HTML.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'on_sale' => array(
					'description' => __( 'Shows if the product is on sale.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'purchaseable' => array(
					'description' => __( 'Shows if the product can be bought.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total_sales' => array(
					'description' => __( 'Amount of sales.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'virtual' => array(
					'description' => __( 'If the product is virtual.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'downloadable' => array(
					'description' => __( 'If the product is downloadable.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'downloads' => array(
					'description' => __( 'List of downloadable files.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
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
				'download_limit' => array(
					'description' => __( 'Amount of times the product can be downloaded.', 'woocommerce' ),
					'type'        => 'integer',
					'default'     => null,
					'context'     => array( 'view', 'edit' ),
				),
				'download_expiry' => array(
					'description' => __( 'Number of days that the customer has up to be able to download the product.', 'woocommerce' ),
					'type'        => 'integer',
					'default'     => null,
					'context'     => array( 'view', 'edit' ),
				),
				'download_type' => array(
					'description' => __( 'Download type, this controls the schema on the front-end.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'standard',
					'enum'        => array( 'standard', 'application', 'music' ),
					'context'     => array( 'view', 'edit' ),
				),
				'external_url' => array(
					'description' => __( 'Product external URL. Only for external products.', 'woocommerce' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
				),
				'button_text' => array(
					'description' => __( 'Product external button text. Only for external products.', 'woocommerce' ),
					'type'        => 'string',
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
				'managing_stock' => array(
					'description' => __( 'Stock management at product level.', 'woocommerce' ),
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
					'description' => __( 'Controls whether or not the product is listed as "in stock" or "out of stock" on the frontend.', 'woocommerce' ),
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
					'description' => __( 'Shows if a product is on backorder.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'sold_individually' => array(
					'description' => __( 'Allow one item to be bought in a single order.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'weight' => array(
					'description' => sprintf( __( 'Product weight (%s).', 'woocommerce' ), get_option( 'woocommerce_weight_unit' ) ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'length' => array(
					'description' => sprintf( __( 'Product length (%s).', 'woocommerce' ), get_option( 'woocommerce_dimension_unit' ) ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'width' => array(
					'description' => sprintf( __( 'Product width (%s).', 'woocommerce' ), get_option( 'woocommerce_dimension_unit' ) ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'height' => array(
					'description' => sprintf( __( 'Product height (%s).', 'woocommerce' ), get_option( 'woocommerce_dimension_unit' ) ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'shipping_required' => array(
					'description' => __( 'Shows if the product need to be shipped.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_taxable' => array(
					'description' => __( 'Shows whether or not the product shipping is taxable.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
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
				'reviews_allowed' => array(
					'description' => __( 'Allow reviews.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => true,
					'context'     => array( 'view', 'edit' ),
				),
				'average_rating' => array(
					'description' => __( 'Reviews average rating.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'rating_count' => array(
					'description' => __( 'Amount of reviews that the product have.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'related_ids' => array(
					'description' => __( 'List of related products IDs.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'upsell_ids' => array(
					'description' => __( 'List of up-sell products IDs.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'cross_sell_ids' => array(
					'description' => __( 'List of cross-sell products IDs.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'parent_id' => array(
					'description' => __( 'Product parent ID.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'purchase_note' => array(
					'description' => __( 'Optional note to send the customer after purchase.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'categories' => array(
					'description' => __( 'List of categories.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'id' => array(
							'description' => __( 'Category ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'name' => array(
							'description' => __( 'Category name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'slug' => array(
							'description' => __( 'Category slug.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'tags' => array(
					'description' => __( 'List of tags.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'id' => array(
							'description' => __( 'Tag ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'name' => array(
							'description' => __( 'Tag name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'slug' => array(
							'description' => __( 'Tag slug.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'images' => array(
					'description' => __( 'List of images.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'id' => array(
							'description' => __( 'Image ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
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
					'properties'  => array(
						'name' => array(
							'description' => __( 'Attribute name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'required'    => true,
						),
						'slug' => array(
							'description' => __( 'Attribute slug.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'position' => array(
							'description' => __( 'Attribute position.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'visible' => array(
							'description' => __( "Define if the attribute is visible on the \"Additional Information\" tab in the product's page.", 'woocommerce' ),
							'type'        => 'boolean',
							'default'     => false,
							'context'     => array( 'view', 'edit' ),
						),
						'variation' => array(
							'description' => __( 'Define if the attribute can be used as variation.', 'woocommerce' ),
							'type'        => 'boolean',
							'default'     => false,
							'context'     => array( 'view', 'edit' ),
						),
						'options' => array(
							'description' => __( 'List of available term names of the attribute.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'default_attributes' => array(
					'description' => __( 'Defaults variation attributes.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'name' => array(
							'description' => __( 'Attribute name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'slug' => array(
							'description' => __( 'Attribute slug.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'option' => array(
							'description' => __( 'Selected term name of the attribute.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'variations' => array(
					'description' => __( 'List of variations.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'id' => array(
							'description' => __( 'Variation ID.', 'woocommerce' ),
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
						'permalink' => array(
							'description' => __( 'Product URL.', 'woocommerce' ),
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
							'description' => __( 'Current product price.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'regular_price' => array(
							'description' => __( 'Product regular price.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'sale_price' => array(
							'description' => __( 'Product sale price.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'sale_price_dates_from' => array(
							'description' => __( 'Start date of sale price.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'sale_price_dates_to' => array(
							'description' => __( 'End data of sale price.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'on_sale' => array(
							'description' => __( 'Shows if the product is on sale.', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'purchaseable' => array(
							'description' => __( 'Shows if the product can be bought.', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'virtual' => array(
							'description' => __( 'If the product is virtual.', 'woocommerce' ),
							'type'        => 'boolean',
							'default'     => false,
							'context'     => array( 'view', 'edit' ),
						),
						'downloadable' => array(
							'description' => __( 'If the product is downloadable.', 'woocommerce' ),
							'type'        => 'boolean',
							'default'     => false,
							'context'     => array( 'view', 'edit' ),
						),
						'downloads' => array(
							'description' => __( 'List of downloadable files.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'properties'  => array(
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
						'download_limit' => array(
							'description' => __( 'Amount of times the product can be downloaded.', 'woocommerce' ),
							'type'        => 'integer',
							'default'     => null,
							'context'     => array( 'view', 'edit' ),
						),
						'download_expiry' => array(
							'description' => __( 'Number of days that the customer has up to be able to download the product.', 'woocommerce' ),
							'type'        => 'integer',
							'default'     => null,
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
						'managing_stock' => array(
							'description' => __( 'Stock management at product level.', 'woocommerce' ),
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
							'description' => __( 'Controls whether or not the product is listed as "in stock" or "out of stock" on the frontend.', 'woocommerce' ),
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
							'description' => __( 'Shows if a product is on backorder.', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'weight' => array(
							'description' => sprintf( __( 'Product weight (%s).', 'woocommerce' ), get_option( 'woocommerce_weight_unit' ) ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'length' => array(
							'description' => sprintf( __( 'Product length (%s).', 'woocommerce' ), get_option( 'woocommerce_dimension_unit' ) ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'width' => array(
							'description' => sprintf( __( 'Product width (%s).', 'woocommerce' ), get_option( 'woocommerce_dimension_unit' ) ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'height' => array(
							'description' => sprintf( __( 'Product height (%s).', 'woocommerce' ), get_option( 'woocommerce_dimension_unit' ) ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
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
							'description' => __( 'Varition image data.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'properties'  => array(
								'id' => array(
									'description' => __( 'Image ID.', 'woocommerce' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
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
							'properties'  => array(
								'name' => array(
									'description' => __( 'Attribute name.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'required'    => true,
								),
								'slug' => array(
									'description' => __( 'Attribute slug.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'position' => array(
									'description' => __( 'Attribute position.', 'woocommerce' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
								),
								'visible' => array(
									'description' => __( "Define if the attribute is visible on the \"Additional Information\" tab in the product's page.", 'woocommerce' ),
									'type'        => 'boolean',
									'default'     => false,
									'context'     => array( 'view', 'edit' ),
								),
								'variation' => array(
									'description' => __( 'Define if the attribute can be used as variation.', 'woocommerce' ),
									'type'        => 'boolean',
									'default'     => false,
									'context'     => array( 'view', 'edit' ),
								),
								'options' => array(
									'description' => __( 'List of available term names of the attribute.', 'woocommerce' ),
									'type'        => 'array',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
					),
				),
				'grouped_products' => array(
					'description' => __( 'List of grouped products ID.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'menu_order' => array(
					'description' => __( 'Menu order, used to custom sort products.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
