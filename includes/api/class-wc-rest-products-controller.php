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
	 * Initialize product actions.
	 */
	public function __construct() {
		add_filter( "woocommerce_rest_{$this->post_type}_query", array( $this, 'query_args' ), 10, 2 );
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
	}

	/**
	 * Query args.
	 *
	 * @param array $args
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function query_args( $args, $request ) {
		// Set post_status.
		$args['post_status'] = $request['status'];

		return $args;
	}

	/**
	 * Get product data.
	 *
	 * @param WC_Product $product
	 * @return array
	 */
	protected function get_product_data( $product ) {
		$data = array(
			'id'                    => (int) $product->is_type( 'variation' ) ? $product->get_variation_id() : $product->id,
			'name'                  => $product->get_title(),
			'slug'                  => $product->get_post_data()->name,
			'permalink'             => $product->get_permalink(),
			'date_created'          => wc_rest_api_prepare_date_response( $product->get_post_data()->post_date_gmt ),
			'date_modified'         => wc_rest_api_prepare_date_response( $product->get_post_data()->post_modified_gmt ),
			'type'                  => $product->product_type,
			'status'                => $product->get_post_data()->post_status,
			'featured'              => $product->is_featured(),
			'catalog_visibility'    => $product->visibility,
			'description'           => wpautop( do_shortcode( $product->get_post_data()->post_content ) ),
			'short_description'     => apply_filters( 'woocommerce_short_description', $product->get_post_data()->post_excerpt ),
			'sku'                   => $product->get_sku(),
			'price'                 => $product->get_price(),
			'regular_price'         => $product->get_regular_price(),
			'sale_price'            => $product->get_sale_price() ? $product->get_sale_price() : '',
			'sale_price_dates_from' => $product->sale_price_dates_from ? date( 'Y-m-d', $product->sale_price_dates_from ) : '',
			'sale_price_dates_to'   => $product->sale_price_dates_to ? date( 'Y-m-d', $product->sale_price_dates_to ) : '',
			'price_html'            => $product->get_price_html(),
			'on_sale'               => $product->is_on_sale(),
			'purchaseable'          => $product->is_purchasable(),
			'total_sales'           => (int) get_post_meta( $product->id, 'total_sales', true ),
			'virtual'               => $product->is_virtual(),
			'downloadable'          => $product->is_downloadable(),
			'downloads'             => $this->get_downloads( $product ),
			'download_limit'        => (int) $product->download_limit,
			'download_expiry'       => (int) $product->download_expiry,
			'download_type'         => $product->download_type ? $product->download_type : 'standard',
			'external_url'          => $product->is_type( 'external' ) ? $product->get_product_url() : '',
			'button_text'           => $product->is_type( 'external' ) ? $product->get_button_text() : '',
			'tax_status'            => $product->get_tax_status(),
			'tax_class'             => $product->get_tax_class(),
			'managing_stock'        => $product->managing_stock(),
			'stock_quantity'        => $product->get_stock_quantity(),
			'in_stock'              => $product->is_in_stock(),
			'backorders'            => $product->backorders,
			'backorders_allowed'    => $product->backorders_allowed(),
			'backordered'           => $product->is_on_backorder(),
			'sold_individually'     => $product->is_sold_individually(),
			'weight'                => $product->get_weight(),
			'length'                => $product->get_length(),
			'width'                 => $product->get_width(),
			'height'                => $product->get_height(),
			'shipping_required'     => $product->needs_shipping(),
			'shipping_taxable'      => $product->is_shipping_taxable(),
			'shipping_class'        => $product->get_shipping_class(),
			'shipping_class_id'     => (int) $product->get_shipping_class_id(),
			'reviews_allowed'       => ( 'open' === $product->get_post_data()->comment_status ),
			'average_rating'        => wc_format_decimal( $product->get_average_rating(), 2 ),
			'rating_count'          => (int) $product->get_rating_count(),
			'related_ids'           => array_map( 'absint', array_values( $product->get_related() ) ),
			'upsell_ids'            => array_map( 'absint', $product->get_upsells() ),
			'cross_sell_ids'        => array_map( 'absint', $product->get_cross_sells() ),
			'parent_id'             => $product->is_type( 'variation' ) ? $product->parent->id : $product->get_post_data()->post_parent,
			'purchase_note'         => wpautop( do_shortcode( wp_kses_post( $product->purchase_note ) ) ),
			'categories'            => array(),
			'tags'                  => array(),
			'images'                => $this->get_images( $product ),
			'attributes'            => $this->get_attributes( $product ),
			'default_attributes'    => array(),
			'variations'            => array(),
			'grouped_products'      => array(),
			'menu_order'            => $this->get_product_menu_order( $product ),
		);

		return $data;
	}

	/**
	 * Get an individual variation's data.
	 *
	 * @param WC_Product $product
	 * @return array
	 */
	protected function get_variation_data( $product ) {
		$variations = array();

		foreach ( $product->get_children() as $child_id ) {
			$variation = $product->get_child( $child_id );
			if ( ! $variation->exists() ) {
				continue;
			}

			$variations[] = array(
				'id'                    => $variation->get_variation_id(),
				'date_created'          => wc_rest_api_prepare_date_response( $variation->get_post_data()->post_date_gmt ),
				'date_modified'         => wc_rest_api_prepare_date_response( $variation->get_post_data()->post_modified_gmt ),
				'permalink'             => $variation->get_permalink(),
				'sku'                   => $variation->get_sku(),
				'price'                 => $variation->get_price(),
				'regular_price'         => $variation->get_regular_price(),
				'sale_price'            => $variation->get_sale_price(),
				'sale_price_dates_from' => $variation->sale_price_dates_from ? date( 'Y-m-d', $variation->sale_price_dates_from ) : '',
				'sale_price_dates_to'   => $variation->sale_price_dates_to ? date( 'Y-m-d', $variation->sale_price_dates_to ) : '',
				'on_sale'               => $variation->is_on_sale(),
				'purchaseable'          => $variation->is_purchasable(),
				'virtual'               => $variation->is_virtual(),
				'downloadable'          => $variation->is_downloadable(),
				'downloads'             => $this->get_downloads( $variation ),
				'download_limit'        => (int) $variation->download_limit,
				'download_expiry'       => (int) $variation->download_expiry,
				'tax_status'            => $variation->get_tax_status(),
				'tax_class'             => $variation->get_tax_class(),
				'managing_stock'        => $variation->managing_stock(),
				'stock_quantity'        => $variation->get_stock_quantity(),
				'in_stock'              => $variation->is_in_stock(),
				'backorders'            => $variation->backorders,
				'backorders_allowed'    => $variation->backorders_allowed(),
				'backordered'           => $variation->is_on_backorder(),
				'weight'                => $variation->get_weight(),
				'length'                => $variation->get_length(),
				'width'                 => $variation->get_width(),
				'height'                => $variation->get_height(),
				'shipping_class'        => $variation->get_shipping_class(),
				'shipping_class_id'     => $variation->get_shipping_class_id(),
				'image'                 => $this->get_images( $variation ),
				'attributes'            => $this->get_attributes( $variation ),
			);
		}

		return $variations;
	}

	/**
	 * Get the downloads for a product or product variation.
	 *
	 * @param WC_Product|WC_Product_Variation $product
	 * @return array
	 */
	protected function get_downloads( $product ) {
		$downloads = array();

		if ( $product->is_downloadable() ) {
			foreach ( $product->get_files() as $file_id => $file ) {
				$downloads[] = array(
					'id'   => $file_id, // MD5 hash.
					'name' => $file['name'],
					'file' => $file['file'],
				);
			}
		}

		return $downloads;
	}

	/**
	 * Get the images for a product or product variation.
	 *
	 * @param WC_Product|WC_Product_Variation $product
	 * @return array
	 */
	protected function get_images( $product ) {
		$images = array();
		$attachment_ids = array();

		if ( $product->is_type( 'variation' ) ) {
			if ( has_post_thumbnail( $product->get_variation_id() ) ) {
				// Add variation image if set.
				$attachment_ids[] = get_post_thumbnail_id( $product->get_variation_id() );
			} elseif ( has_post_thumbnail( $product->id ) ) {
				// Otherwise use the parent product featured image if set.
				$attachment_ids[] = get_post_thumbnail_id( $product->id );
			}
		} else {
			// Add featured image.
			if ( has_post_thumbnail( $product->id ) ) {
				$attachment_ids[] = get_post_thumbnail_id( $product->id );
			}
			// Add gallery images.
			$attachment_ids = array_merge( $attachment_ids, $product->get_gallery_attachment_ids() );
		}

		// Build image data.
		foreach ( $attachment_ids as $position => $attachment_id ) {
			$attachment_post = get_post( $attachment_id );
			if ( is_null( $attachment_post ) ) {
				continue;
			}

			$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );
			if ( ! is_array( $attachment ) ) {
				continue;
			}

			$images[] = array(
				'id'            => (int) $attachment_id,
				'date_created'  => wc_rest_api_prepare_date_response( $attachment_post->post_date_gmt ),
				'date_modified' => wc_rest_api_prepare_date_response( $attachment_post->post_modified_gmt ),
				'src'           => current( $attachment ),
				'title'         => get_the_title( $attachment_id ),
				'alt'           => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
				'position'      => (int) $position,
			);
		}

		// Set a placeholder image if the product has no images set.
		if ( empty( $images ) ) {
			$images[] = array(
				'id'            => 0,
				'date_created'  => wc_rest_api_prepare_date_response( time() ), // Default to now.
				'date_modified' => wc_rest_api_prepare_date_response( time() ),
				'src'           => wc_placeholder_img_src(),
				'title'         => __( 'Placeholder', 'woocommerce' ),
				'alt'           => __( 'Placeholder', 'woocommerce' ),
				'position'      => 0,
			);
		}

		return $images;
	}

	/**
	 * Get the attributes for a product or product variation.
	 *
	 * @param WC_Product|WC_Product_Variation $product
	 * @return array
	 */
	protected function get_attributes( $product ) {
		$attributes = array();

		if ( $product->is_type( 'variation' ) ) {
			// Variation attributes.
			foreach ( $product->get_variation_attributes() as $attribute_name => $attribute ) {
				// Taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`.
				$attributes[] = array(
					'name'   => wc_attribute_label( str_replace( 'attribute_', '', $attribute_name ) ),
					'slug'   => str_replace( 'attribute_', '', str_replace( 'pa_', '', $attribute_name ) ),
					'option' => $attribute,
				);
			}
		} else {
			foreach ( $product->get_attributes() as $attribute ) {
				// Taxonomy-based attributes are comma-separated, others are pipe (|) separated.
				if ( $attribute['is_taxonomy'] ) {
					$options = explode( ',', $product->get_attribute( $attribute['name'] ) );
				} else {
					$options = explode( '|', $product->get_attribute( $attribute['name'] ) );
				}

				$attributes[] = array(
					'name'      => wc_attribute_label( $attribute['name'] ),
					'slug'      => str_replace( 'pa_', '', $attribute['name'] ),
					'position'  => (int) $attribute['position'],
					'visible'   => (bool) $attribute['is_visible'],
					'variation' => (bool) $attribute['is_variation'],
					'options'   => array_map( 'trim', $options ),
				);
			}
		}

		return $attributes;
	}

	/**
	 * Get product menu order.
	 *
	 * @param WC_Product $product
	 * @return int
	 */
	protected function get_product_menu_order( $product ) {
		$menu_order = $product->get_post_data()->menu_order;

		if ( $product->is_type( 'variation' ) ) {
			$variation  = get_post( $product->get_variation_id() );
			$menu_order = $variation->menu_order;
		}

		return apply_filters( 'woocommerce_rest_product_menu_order', $menu_order, $product );
	}

	/**
	 * Prepare a single product output for response.
	 *
	 * @param WP_Post $post Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $data
	 */
	public function prepare_item_for_response( $post, $request ) {
		$product = wc_get_product( $post );

		$data = $this->get_product_data( $product );

		// Add variations to variable products.
		if ( $product->is_type( 'variable' ) && $product->has_child() ) {
			$data['variations'] = $this->get_variation_data( $product );
		}

		// Add grouped products data.
		if ( $product->is_type( 'grouped' ) && $product->has_child() ) {
			$data['grouped_products'] = $product->get_children();
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $product ) );

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
	 * Prepare links for the request.
	 *
	 * @param WC_Product $product Product object.
	 * @return array Links for the given product.
	 */
	protected function prepare_links( $product ) {
		$links = array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $product->id ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		if ( $product->is_type( 'variation' ) && $product->parent ) {
			$links['up'] = array(
				'href' => rest_url( sprintf( '/%s/products/%d', $this->namespace, $product->parent ) ),
			);
		} elseif ( $product->is_type( 'simple' ) && ! empty( $product->post->post_parent ) ) {
			$links['up'] = array(
				'href' => rest_url( sprintf( '/%s/products/%d', $this->namespace, $product->post->post_parent ) ),
			);
		}

		return $links;
	}

	/**
	 * Get the Product's schema, conforming to JSON Schema.
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
				'slug' => array(
					'description' => __( 'Product slug.', 'woocommerce' ),
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

	/**
	 * Get the query params for collections of attachments.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['status'] = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to products assigned a specific status.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => array_merge( array( 'any' ), array_keys( get_post_statuses() ) ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}
