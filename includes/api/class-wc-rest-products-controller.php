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
	protected $namespace = 'wc/v1';

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
		add_action( "woocommerce_rest_insert_{$this->post_type}", array( $this, 'clear_transients' ) );
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
	 * Query args.
	 *
	 * @param array $args
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function query_args( $args, $request ) {
		// Set post_status.
		$args['post_status'] = $request['status'];

		// Taxonomy query to filter products by type, category,
		// tag, shipping class, and attribute.
		$tax_query = array();

		// Map between taxonomy name and arg's key.
		$taxonomies = array(
			'product_cat'            => 'category',
			'product_tag'            => 'tag',
			'product_shipping_class' => 'shipping_class',
		);

		// Set tax_query for each passed arg.
		foreach ( $taxonomies as $taxonomy => $key ) {
			if ( ! empty( $request[ $key ] ) ) {
				$terms = explode( ',', $request[ $key ] );

				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $terms,
				);
			}
		}

		// Filter product type by slug.
		if ( ! empty( $request['type'] ) ) {
			$terms = explode( ',', $request['type'] );

			$tax_query[] = array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => $terms,
			);
		}

		// Filter by attribute and term.
		if ( ! empty( $request['attribute'] ) && ! empty( $request['attribute_term'] ) ) {
			if ( in_array( $request['attribute'], wc_get_attribute_taxonomy_names() ) ) {
				$terms = explode( ',', $request['attribute_term'] );

				$tax_query[] = array(
					'taxonomy' => $request['attribute'],
					'field'    => 'term_id',
					'terms'    => $terms,
				);
			}
		}

		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query;
		}

		// Filter by sku.
		if ( ! empty( $request['sku'] ) ) {
			if ( ! empty( $args['meta_query'] ) ) {
				$args['meta_query'] = array();
			}

			$args['meta_query'][] = array(
				'key'     => '_sku',
				'value'   => $request['sku'],
				'compare' => '='
			);

			$args['post_type'] = array( 'product', 'product_variation' );
		}

		return $args;
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
	 * Get taxonomy terms.
	 *
	 * @param WC_Product $product
	 * @param string $taxonomy
	 * @return array
	 */
	protected function get_taxonomy_terms( $product, $taxonomy = 'cat' ) {
		$terms = array();

		foreach ( wp_get_post_terms( $product->id, 'product_' . $taxonomy ) as $term ) {
			$terms[] = array(
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			);
		}

		return $terms;
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
				'date_created'  => wc_rest_prepare_date_response( $attachment_post->post_date_gmt ),
				'date_modified' => wc_rest_prepare_date_response( $attachment_post->post_modified_gmt ),
				'src'           => current( $attachment ),
				'name'          => get_the_title( $attachment_id ),
				'alt'           => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
				'position'      => (int) $position,
			);
		}

		// Set a placeholder image if the product has no images set.
		if ( empty( $images ) ) {
			$images[] = array(
				'id'            => 0,
				'date_created'  => wc_rest_prepare_date_response( current_time( 'mysql' ) ), // Default to now.
				'date_modified' => wc_rest_prepare_date_response( current_time( 'mysql' ) ),
				'src'           => wc_placeholder_img_src(),
				'name'          => __( 'Placeholder', 'woocommerce' ),
				'alt'           => __( 'Placeholder', 'woocommerce' ),
				'position'      => 0,
			);
		}

		return $images;
	}

	/**
	 * Get attribute taxonomy label.
	 *
	 * @param  string $name
	 * @return string
	 */
	protected function get_attribute_taxonomy_label( $name ) {
		$tax    = get_taxonomy( $name );
		$labels = get_taxonomy_labels( $tax );

		return $labels->singular_name;
	}

	/**
	 * Get default attributes.
	 *
	 * @param WC_Product $product
	 * @return array
	 */
	protected function get_default_attributes( $product ) {
		$default = array();

		if ( $product->is_type( 'variable' ) ) {
			foreach ( (array) get_post_meta( $product->id, '_default_attributes', true ) as $key => $value ) {
				if ( 0 === strpos( $key, 'pa_' ) ) {
					$default[] = array(
						'id'     => wc_attribute_taxonomy_id_by_name( $key ),
						'name'   => $this->get_attribute_taxonomy_label( $key ),
						'option' => $value,
					);
				} else {
					$default[] = array(
						'id'     => 0,
						'name'   => str_replace( 'pa_', '', $key ),
						'option' => $value,
					);
				}
			}
		}

		return $default;
	}

	/**
	 * Get attribute options.
	 *
	 * @param int $product_id
	 * @param array $attribute
	 * @return array
	 */
	protected function get_attribute_options( $product_id, $attribute ) {
		if ( isset( $attribute['is_taxonomy'] ) && $attribute['is_taxonomy'] ) {
			return wc_get_product_terms( $product_id, $attribute['name'], array( 'fields' => 'names' ) );
		} elseif ( isset( $attribute['value'] ) ) {
			return array_map( 'trim', explode( '|', $attribute['value'] ) );
		}

		return array();
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
				$name = str_replace( 'attribute_', '', $attribute_name );

				// Taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`.
				if ( 0 === strpos( $attribute_name, 'attribute_pa_' ) ) {
					$attributes[] = array(
						'id'     => wc_attribute_taxonomy_id_by_name( $name ),
						'name'   => $this->get_attribute_taxonomy_label( $name ),
						'option' => $attribute,
					);
				} else {
					$attributes[] = array(
						'id'     => 0,
						'name'   => str_replace( 'pa_', '', $name ),
						'option' => $attribute,
					);
				}
			}
		} else {
			foreach ( $product->get_attributes() as $attribute ) {
				if ( $attribute['is_taxonomy'] ) {
					$attributes[] = array(
						'id'        => wc_attribute_taxonomy_id_by_name( $attribute['name'] ),
						'name'      => $this->get_attribute_taxonomy_label( $attribute['name'] ),
						'position'  => (int) $attribute['position'],
						'visible'   => (bool) $attribute['is_visible'],
						'variation' => (bool) $attribute['is_variation'],
						'options'   => $this->get_attribute_options( $product->id, $attribute ),
					);
				} else {
					$attributes[] = array(
						'id'        => 0,
						'name'      => str_replace( 'pa_', '', $attribute['name'] ),
						'position'  => (int) $attribute['position'],
						'visible'   => (bool) $attribute['is_visible'],
						'variation' => (bool) $attribute['is_variation'],
						'options'   => $this->get_attribute_options( $product->id, $attribute ),
					);
				}
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

		return $menu_order;
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
			'slug'                  => $product->get_post_data()->post_name,
			'permalink'             => $product->get_permalink(),
			'date_created'          => wc_rest_prepare_date_response( $product->get_post_data()->post_date_gmt ),
			'date_modified'         => wc_rest_prepare_date_response( $product->get_post_data()->post_modified_gmt ),
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
			'date_on_sale_from'     => $product->sale_price_dates_from ? date( 'Y-m-d', $product->sale_price_dates_from ) : '',
			'date_on_sale_to'       => $product->sale_price_dates_to ? date( 'Y-m-d', $product->sale_price_dates_to ) : '',
			'price_html'            => $product->get_price_html(),
			'on_sale'               => $product->is_on_sale(),
			'purchasable'           => $product->is_purchasable(),
			'total_sales'           => (int) get_post_meta( $product->id, 'total_sales', true ),
			'virtual'               => $product->is_virtual(),
			'downloadable'          => $product->is_downloadable(),
			'downloads'             => $this->get_downloads( $product ),
			'download_limit'        => '' !== $product->download_limit ? (int) $product->download_limit : -1,
			'download_expiry'       => '' !== $product->download_expiry ? (int) $product->download_expiry : -1,
			'download_type'         => $product->download_type ? $product->download_type : 'standard',
			'external_url'          => $product->is_type( 'external' ) ? $product->get_product_url() : '',
			'button_text'           => $product->is_type( 'external' ) ? $product->get_button_text() : '',
			'tax_status'            => $product->get_tax_status(),
			'tax_class'             => $product->get_tax_class(),
			'manage_stock'          => $product->managing_stock(),
			'stock_quantity'        => $product->get_stock_quantity(),
			'in_stock'              => $product->is_in_stock(),
			'backorders'            => $product->backorders,
			'backorders_allowed'    => $product->backorders_allowed(),
			'backordered'           => $product->is_on_backorder(),
			'sold_individually'     => $product->is_sold_individually(),
			'weight'                => $product->get_weight(),
			'dimensions'            => array(
				'length' => $product->get_length(),
				'width'  => $product->get_width(),
				'height' => $product->get_height(),
			),
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
			'categories'            => $this->get_taxonomy_terms( $product ),
			'tags'                  => $this->get_taxonomy_terms( $product, 'tag' ),
			'images'                => $this->get_images( $product ),
			'attributes'            => $this->get_attributes( $product ),
			'default_attributes'    => $this->get_default_attributes( $product ),
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

			$post_data = get_post( $variation->get_variation_id() );

			$variations[] = array(
				'id'                 => $variation->get_variation_id(),
				'date_created'       => wc_rest_prepare_date_response( $post_data->post_date_gmt ),
				'date_modified'      => wc_rest_prepare_date_response( $post_data->post_modified_gmt ),
				'permalink'          => $variation->get_permalink(),
				'sku'                => $variation->get_sku(),
				'price'              => $variation->get_price(),
				'regular_price'      => $variation->get_regular_price(),
				'sale_price'         => $variation->get_sale_price(),
				'date_on_sale_from'  => $variation->sale_price_dates_from ? date( 'Y-m-d', $variation->sale_price_dates_from ) : '',
				'date_on_sale_to'    => $variation->sale_price_dates_to ? date( 'Y-m-d', $variation->sale_price_dates_to ) : '',
				'on_sale'            => $variation->is_on_sale(),
				'purchasable'        => $variation->is_purchasable(),
				'virtual'            => $variation->is_virtual(),
				'downloadable'       => $variation->is_downloadable(),
				'downloads'          => $this->get_downloads( $variation ),
				'download_limit'     => '' !== $variation->download_limit ? (int) $variation->download_limit : -1,
				'download_expiry'    => '' !== $variation->download_expiry ? (int) $variation->download_expiry : -1,
				'tax_status'         => $variation->get_tax_status(),
				'tax_class'          => $variation->get_tax_class(),
				'manage_stock'       => $variation->managing_stock(),
				'stock_quantity'     => $variation->get_stock_quantity(),
				'in_stock'           => $variation->is_in_stock(),
				'backorders'         => $variation->backorders,
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
		}

		return $variations;
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
		$data    = $this->get_product_data( $product );

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
	 * Prepare a single product for create or update.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_Error|stdClass $data Post object.
	 */
	protected function prepare_item_for_database( $request ) {
		$data = new stdClass;

		// ID.
		if ( isset( $request['id'] ) ) {
			$data->ID = absint( $request['id'] );
		}

		// Post title.
		if ( isset( $request['name'] ) ) {
			$data->post_title = wp_filter_post_kses( $request['name'] );
		}

		// Post content.
		if ( isset( $request['description'] ) ) {
			$data->post_content = wp_filter_post_kses( $request['description'] );
		}

		// Post excerpt.
		if ( isset( $request['short_description'] ) ) {
			$data->post_excerpt = wp_filter_post_kses( $request['short_description'] );
		}

		// Post status.
		if ( isset( $request['status'] ) ) {
			$data->post_status = get_post_status_object( $request['status'] ) ? $request['status'] : 'draft';
		}

		// Post slug.
		if ( isset( $request['slug'] ) ) {
			$data->post_name = $request['slug'];
		}

		// Menu order.
		if ( isset( $request['menu_order'] ) ) {
			$data->menu_order = (int) $request['menu_order'];
		}

		// Comment status.
		if ( ! empty( $request['reviews_allowed'] ) ) {
			$data->comment_status = $request['reviews_allowed'] ? 'open' : 'closed';
		}

		// Only when creating products.
		if ( empty( $request['id'] ) ) {
			// Post type.
			$data->post_type = $this->post_type;

			// Ping status.
			$data->ping_status = 'closed';
		}

		/**
		 * Filter the query_vars used in `get_items` for the constructed query.
		 *
		 * The dynamic portion of the hook name, $this->post_type, refers to post_type of the post being
		 * prepared for insertion.
		 *
		 * @param stdClass        $data An object representing a single item prepared
		 *                                       for inserting or updating the database.
		 * @param WP_REST_Request $request       Request object.
		 */
		return apply_filters( "woocommerce_rest_pre_insert_{$this->post_type}", $data, $request );
	}

	/**
	 * Save product images.
	 *
	 * @param WC_Product $product
	 * @param array $images
	 * @throws WC_REST_Exception
	 */
	protected function save_product_images( $product, $images ) {
		if ( is_array( $images ) ) {
			$gallery = array();

			foreach ( $images as $image ) {
				if ( isset( $image['position'] ) && 0 === $image['position'] ) {
					$attachment_id = isset( $image['id'] ) ? absint( $image['id'] ) : 0;

					if ( 0 === $attachment_id && isset( $image['src'] ) ) {
						$upload = wc_rest_upload_image_from_url( esc_url_raw( $image['src'] ) );

						if ( is_wp_error( $upload ) ) {
							throw new WC_REST_Exception( 'woocommerce_product_image_upload_error', $upload->get_error_message(), 400 );
						}

						$attachment_id = wc_rest_set_uploaded_image_as_attachment( $upload, $product->id );
					}

					set_post_thumbnail( $product->id, $attachment_id );
				} else {
					$attachment_id = isset( $image['id'] ) ? absint( $image['id'] ) : 0;

					if ( 0 === $attachment_id && isset( $image['src'] ) ) {
						$upload = wc_rest_upload_image_from_url( esc_url_raw( $image['src'] ) );

						if ( is_wp_error( $upload ) ) {
							throw new WC_REST_Exception( 'woocommerce_product_image_upload_error', $upload->get_error_message(), 400 );
						}

						$attachment_id = wc_rest_set_uploaded_image_as_attachment( $upload, $product->id );
					}

					$gallery[] = $attachment_id;
				}

				// Set the image alt if present.
				if ( ! empty( $image['alt'] ) && $attachment_id ) {
					update_post_meta( $attachment_id, '_wp_attachment_image_alt', wc_clean( $image['alt'] ) );
				}

				// Set the image name if present.
				if ( ! empty( $image['name'] ) && $attachment_id ) {
					wp_update_post( array( 'ID' => $attachment_id, 'post_title' => $image['name'] ) );
				}
			}

			if ( ! empty( $gallery ) ) {
				update_post_meta( $product->id, '_product_image_gallery', implode( ',', $gallery ) );
			}
		} else {
			delete_post_thumbnail( $product->id );
			update_post_meta( $product->id, '_product_image_gallery', '' );
		}
	}

	/**
	 * Save product shipping data.
	 *
	 * @param WC_Product $product
	 * @param array $data
	 */
	private function save_product_shipping_data( $product, $data ) {
		// Virtual.
		if ( isset( $data['virtual'] ) && true === $data['virtual'] ) {
			update_post_meta( $product->id, '_weight', '' );
			update_post_meta( $product->id, '_length', '' );
			update_post_meta( $product->id, '_width', '' );
			update_post_meta( $product->id, '_height', '' );
		} else {
			if ( isset( $data['weight'] ) ) {
				update_post_meta( $product->id, '_weight', '' === $data['weight'] ? '' : wc_format_decimal( $data['weight'] ) );
			}

			// Height.
			if ( isset( $data['dimensions']['height'] ) ) {
				update_post_meta( $product->id, '_height', '' === $data['dimensions']['height'] ? '' : wc_format_decimal( $data['dimensions']['height'] ) );
			}

			// Width.
			if ( isset( $data['dimensions']['width'] ) ) {
				update_post_meta( $product->id, '_width', '' === $data['dimensions']['width'] ? '' : wc_format_decimal( $data['dimensions']['width'] ) );
			}

			// Length.
			if ( isset( $data['dimensions']['length'] ) ) {
				update_post_meta( $product->id, '_length', '' === $data['dimensions']['length'] ? '' : wc_format_decimal( $data['dimensions']['length'] ) );
			}
		}

		// Shipping class.
		if ( isset( $data['shipping_class'] ) ) {
			wp_set_object_terms( $product->id, wc_clean( $data['shipping_class'] ), 'product_shipping_class' );
		}
	}

	/**
	 * Save downloadable files.
	 *
	 * @param WC_Product $product
	 * @param array $downloads
	 * @param int $variation_id
	 */
	private function save_downloadable_files( $product, $downloads, $variation_id = 0 ) {
		$files = array();

		// File paths will be stored in an array keyed off md5(file path).
		foreach ( $downloads as $key => $file ) {
			if ( isset( $file['url'] ) ) {
				$file['file'] = $file['url'];
			}

			if ( ! isset( $file['file'] ) ) {
				continue;
			}

			$file_name = isset( $file['name'] ) ? wc_clean( $file['name'] ) : '';

			if ( 0 === strpos( $file['file'], 'http' ) ) {
				$file_url = esc_url_raw( $file['file'] );
			} else {
				$file_url = wc_clean( $file['file'] );
			}

			$files[ md5( $file_url ) ] = array(
				'name' => $file_name,
				'file' => $file_url,
			);
		}

		// Grant permission to any newly added files on any existing orders for this product prior to saving.
		do_action( 'woocommerce_process_product_file_download_paths', $product->id, $variation_id, $files );

		$id = ( 0 === $variation_id ) ? $product->id : $variation_id;

		update_post_meta( $id, '_downloadable_files', $files );
	}

	/**
	 * Save taxonomy terms.
	 *
	 * @param WC_Product $product
	 * @param array $terms
	 * @param string $taxonomy
	 * @return array
	 */
	protected function save_taxonomy_terms( $product, $terms, $taxonomy = 'cat' ) {
		$term_ids = wp_list_pluck( $terms, 'id' );
		$term_ids = array_unique( array_map( 'intval', $term_ids ) );

		wp_set_object_terms( $product->id, $term_ids, 'product_' . $taxonomy );

		return $terms;
	}

	/**
	 * Save product meta.
	 *
	 * @param WC_Product $product
	 * @param WP_REST_Request $request
	 * @return bool
	 * @throws WC_REST_Exception
	 */
	protected function save_product_meta( $product, $request ) {
		global $wpdb;

		// Product Type.
		$product_type = null;
		if ( isset( $request['type'] ) ) {
			$product_type = wc_clean( $request['type'] );
			wp_set_object_terms( $product->id, $product_type, 'product_type' );
		} else {
			$_product_type = get_the_terms( $product->id, 'product_type' );
			if ( is_array( $_product_type ) ) {
				$_product_type = current( $_product_type );
				$product_type  = $_product_type->slug;
			}
		}

		// Default total sales.
		add_post_meta( $product->id, 'total_sales', '0', true );

		// Virtual.
		if ( isset( $request['virtual'] ) ) {
			update_post_meta( $product->id, '_virtual', true === $request['virtual'] ? 'yes' : 'no' );
		}

		// Tax status.
		if ( isset( $request['tax_status'] ) ) {
			update_post_meta( $product->id, '_tax_status', wc_clean( $request['tax_status'] ) );
		}

		// Tax Class.
		if ( isset( $request['tax_class'] ) ) {
			update_post_meta( $product->id, '_tax_class', wc_clean( $request['tax_class'] ) );
		}

		// Catalog Visibility.
		if ( isset( $request['catalog_visibility'] ) ) {
			update_post_meta( $product->id, '_visibility', wc_clean( $request['catalog_visibility'] ) );
		}

		// Purchase Note.
		if ( isset( $request['purchase_note'] ) ) {
			update_post_meta( $product->id, '_purchase_note', wc_clean( $request['purchase_note'] ) );
		}

		// Featured Product.
		if ( isset( $request['featured'] ) ) {
			update_post_meta( $product->id, '_featured', true === $request['featured'] ? 'yes' : 'no' );
		}

		// Shipping data.
		$this->save_product_shipping_data( $product, $request );

		// SKU.
		if ( isset( $request['sku'] ) ) {
			$sku     = get_post_meta( $product->id, '_sku', true );
			$new_sku = wc_clean( $request['sku'] );

			if ( '' === $new_sku ) {
				update_post_meta( $product->id, '_sku', '' );
			} elseif ( $new_sku !== $sku ) {
				if ( ! empty( $new_sku ) ) {
					$unique_sku = wc_product_has_unique_sku( $product->id, $new_sku );
					if ( ! $unique_sku ) {
						throw new WC_REST_Exception( 'woocommerce_rest_product_sku_already_exists', __( 'The SKU already exists on another product.', 'woocommerce' ), 400 );
					} else {
						update_post_meta( $product->id, '_sku', $new_sku );
					}
				} else {
					update_post_meta( $product->id, '_sku', '' );
				}
			}
		}

		// Attributes.
		if ( isset( $request['attributes'] ) ) {
			$attributes = array();

			foreach ( $request['attributes'] as $attribute ) {
				$attribute_id   = 0;
				$attribute_name = '';

				// Check ID for global attributes or name for product attributes.
				if ( ! empty( $attribute['id'] ) ) {
					$attribute_id   = absint( $attribute['id'] );
					$attribute_name = wc_attribute_taxonomy_name_by_id( $attribute_id );
				} elseif ( ! empty( $attribute['name'] ) ) {
					$attribute_name = wc_clean( $attribute['name'] );
				}

				if ( ! $attribute_id && ! $attribute_name ) {
					continue;
				}

				if ( $attribute_id ) {

					if ( isset( $attribute['options'] ) ) {
						$options = $attribute['options'];

						if ( ! is_array( $attribute['options'] ) ) {
							// Text based attributes - Posted values are term names.
							$options = explode( WC_DELIMITER, $options );
						}

						$values = array_map( 'wc_sanitize_term_text_based', $options );
						$values = array_filter( $values, 'strlen' );
					} else {
						$values = array();
					}

					// Update post terms.
					if ( taxonomy_exists( $attribute_name ) ) {
						wp_set_object_terms( $product->id, $values, $attribute_name );
					}

					if ( ! empty( $values ) ) {
						// Add attribute to array, but don't set values.
						$attributes[ $attribute_name ] = array(
							'name'         => $attribute_name,
							'value'        => '',
							'position'     => isset( $attribute['position'] ) ? absint( $attribute['position'] ) : 0,
							'is_visible'   => ( isset( $attribute['visible'] ) && $attribute['visible'] ) ? 1 : 0,
							'is_variation' => ( isset( $attribute['variation'] ) && $attribute['variation'] ) ? 1 : 0,
							'is_taxonomy'  => true,
						);
					}

				} elseif ( isset( $attribute['options'] ) ) {
					// Array based.
					if ( is_array( $attribute['options'] ) ) {
						$values = implode( ' ' . WC_DELIMITER . ' ', array_map( 'wc_clean', $attribute['options'] ) );

					// Text based, separate by pipe.
					} else {
						$values = implode( ' ' . WC_DELIMITER . ' ', array_map( 'wc_clean', explode( WC_DELIMITER, $attribute['options'] ) ) );
					}

					// Custom attribute - Add attribute to array and set the values.
					$attributes[ sanitize_title( $attribute_name ) ] = array(
						'name'         => $attribute_name,
						'value'        => $values,
						'position'     => isset( $attribute['position'] ) ? absint( $attribute['position'] ) : 0,
						'is_visible'   => ( isset( $attribute['visible'] ) && $attribute['visible'] ) ? 1 : 0,
						'is_variation' => ( isset( $attribute['variation'] ) && $attribute['variation'] ) ? 1 : 0,
						'is_taxonomy'  => false,
					);
				}
			}

			uasort( $attributes, 'wc_product_attribute_uasort_comparison' );

			update_post_meta( $product->id, '_product_attributes', $attributes );
		}

		// Sales and prices.
		if ( in_array( $product_type, array( 'variable', 'grouped' ) ) ) {

			// Variable and grouped products have no prices.
			update_post_meta( $product->id, '_regular_price', '' );
			update_post_meta( $product->id, '_sale_price', '' );
			update_post_meta( $product->id, '_sale_price_dates_from', '' );
			update_post_meta( $product->id, '_sale_price_dates_to', '' );
			update_post_meta( $product->id, '_price', '' );

		} else {

			// Regular Price
			if ( isset( $request['regular_price'] ) ) {
				$regular_price = ( '' === $request['regular_price'] ) ? '' : $request['regular_price'];
			} else {
				$regular_price = get_post_meta( $product->id, '_regular_price', true );
			}

			// Sale Price
			if ( isset( $request['sale_price'] ) ) {
				$sale_price = ( '' === $request['sale_price'] ) ? '' : $request['sale_price'];
			} else {
				$sale_price = get_post_meta( $product->id, '_sale_price', true );
			}

			if ( isset( $request['date_on_sale_from'] ) ) {
				$date_from = $request['date_on_sale_from'];
			} else {
				$date_from = get_post_meta( $product->id, '_sale_price_dates_from', true );
				$date_from = ( '' === $date_from ) ? '' : date( 'Y-m-d', $date_from );
			}

			if ( isset( $request['date_on_sale_to'] ) ) {
				$date_to = $request['date_on_sale_to'];
			} else {
				$date_to = get_post_meta( $product->id, '_sale_price_dates_to', true );
				$date_to = ( '' === $date_to ) ? '' : date( 'Y-m-d', $date_to );
			}

			_wc_save_product_price( $product->id, $regular_price, $sale_price, $date_from, $date_to );
		}

		// Product parent ID for groups.
		$parent_id = 0;
		if ( isset( $request['parent_id'] ) ) {
			$parent_id = wp_update_post( array( 'ID' => $product->id, 'post_parent' => absint( $request['parent_id'] ) ) );
		}

		// Update parent if grouped so price sorting works and stays in sync with the cheapest child.
		if ( $parent_id > 0 || 'grouped' === $product_type ) {

			$clear_parent_ids = array();

			if ( $parent_id > 0 ) {
				$clear_parent_ids[] = $parent_id;
			}

			if ( 'grouped' === $product_type ) {
				$clear_parent_ids[] = $product->id;
			}

			if ( ! empty( $clear_parent_ids ) ) {
				foreach ( $clear_parent_ids as $clear_id ) {

					$children_by_price = get_posts( array(
						'post_parent'    => $clear_id,
						'orderby'        => 'meta_value_num',
						'order'          => 'asc',
						'meta_key'       => '_price',
						'posts_per_page' => 1,
						'post_type'      => 'product',
						'fields'         => 'ids'
					) );

					if ( $children_by_price ) {
						foreach ( $children_by_price as $child ) {
							$child_price = get_post_meta( $child, '_price', true );
							update_post_meta( $clear_id, '_price', $child_price );
						}
					}
				}
			}
		}

		// Sold individually.
		if ( isset( $request['sold_individually'] ) ) {
			update_post_meta( $product->id, '_sold_individually', true === $request['sold_individually'] ? 'yes' : '' );
		}

		// Stock status.
		if ( isset( $request['in_stock'] ) ) {
			$stock_status = true === $request['in_stock'] ? 'instock' : 'outofstock';
		} else {
			$stock_status = get_post_meta( $product->id, '_stock_status', true );

			if ( '' === $stock_status ) {
				$stock_status = 'instock';
			}
		}

		// Stock data.
		if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) {
			// Manage stock.
			if ( isset( $request['manage_stock'] ) ) {
				$manage_stock = ( true === $request['manage_stock'] ) ? 'yes' : 'no';
				update_post_meta( $product->id, '_manage_stock', $manage_stock );
			} else {
				$manage_stock = get_post_meta( $product->id, '_manage_stock', true );
			}

			// Backorders.
			if ( isset( $request['backorders'] ) ) {
				$backorders = $request['backorders'];
				update_post_meta( $product->id, '_backorders', $backorders );
			} else {
				$backorders = get_post_meta( $product->id, '_backorders', true );
			}

			if ( 'grouped' === $product_type ) {
				update_post_meta( $product->id, '_manage_stock', 'no' );
				update_post_meta( $product->id, '_backorders', 'no' );
				update_post_meta( $product->id, '_stock', '' );

				wc_update_product_stock_status( $product->id, $stock_status );
			} elseif ( 'external' === $product_type ) {
				update_post_meta( $product->id, '_manage_stock', 'no' );
				update_post_meta( $product->id, '_backorders', 'no' );
				update_post_meta( $product->id, '_stock', '' );

				wc_update_product_stock_status( $product->id, 'instock' );
			} elseif ( 'variable' === $product_type ) {
				update_post_meta( $product->id, '_stock', '' );
			} elseif ( 'yes' === $manage_stock ) {
				update_post_meta( $product->id, '_backorders', $backorders );

				wc_update_product_stock_status( $product->id, $stock_status );

				// Stock quantity.
				if ( isset( $request['stock_quantity'] ) ) {
					wc_update_product_stock( $product->id, wc_stock_amount( $request['stock_quantity'] ) );
				} elseif ( isset( $request['inventory_delta'] ) ) {
					$stock_quantity  = wc_stock_amount( get_post_meta( $product->id, '_stock', true ) );
					$stock_quantity += wc_stock_amount( $request['inventory_delta'] );

					wc_update_product_stock( $product->id, wc_stock_amount( $stock_quantity ) );
				}
			} else {
				// Don't manage stock.
				update_post_meta( $product->id, '_manage_stock', 'no' );
				update_post_meta( $product->id, '_backorders', $backorders );
				update_post_meta( $product->id, '_stock', '' );

				wc_update_product_stock_status( $product->id, $stock_status );
			}

		} elseif ( 'variable' !== $product_type ) {
			wc_update_product_stock_status( $product->id, $stock_status );
		}

		// Upsells.
		if ( isset( $request['upsell_ids'] ) ) {
			$upsells = array();
			$ids     = $request['upsell_ids'];

			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					if ( $id && $id > 0 ) {
						$upsells[] = $id;
					}
				}

				update_post_meta( $product->id, '_upsell_ids', $upsells );
			} else {
				delete_post_meta( $product->id, '_upsell_ids' );
			}
		}

		// Cross sells.
		if ( isset( $request['cross_sell_ids'] ) ) {
			$crosssells = array();
			$ids        = $request['cross_sell_ids'];

			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					if ( $id && $id > 0 ) {
						$crosssells[] = $id;
					}
				}

				update_post_meta( $product->id, '_crosssell_ids', $crosssells );
			} else {
				delete_post_meta( $product->id, '_crosssell_ids' );
			}
		}

		// Product categories.
		if ( isset( $request['categories'] ) && is_array( $request['categories'] ) ) {
			$this->save_taxonomy_terms( $product, $request['categories'] );
		}

		// Product tags.
		if ( isset( $request['tags'] ) && is_array( $request['tags'] ) ) {
			$this->save_taxonomy_terms( $product, $request['tags'], 'tag' );
		}

		// Downloadable.
		if ( isset( $request['downloadable'] ) ) {
			$is_downloadable = true === $request['downloadable'] ? 'yes' : 'no';
			update_post_meta( $product->id, '_downloadable', $is_downloadable );
		} else {
			$is_downloadable = get_post_meta( $product->id, '_downloadable', true );
		}

		// Downloadable options.
		if ( 'yes' === $is_downloadable ) {

			// Downloadable files.
			if ( isset( $request['downloads'] ) && is_array( $request['downloads'] ) ) {
				$this->save_downloadable_files( $product, $request['downloads'] );
			}

			// Download limit.
			if ( isset( $request['download_limit'] ) ) {
				update_post_meta( $product->id, '_download_limit', -1 === $request['download_limit'] ? '' : absint( $request['download_limit'] ) );
			}

			// Download expiry.
			if ( isset( $request['download_expiry'] ) ) {
				update_post_meta( $product->id, '_download_expiry', -1 === $request['download_expiry'] ? '' : absint( $request['download_expiry'] ) );
			}

			// Download type.
			if ( isset( $request['download_type'] ) ) {
				update_post_meta( $product->id, '_download_type', 'standard' === $request['download_type'] ? '' : wc_clean( $request['download_type'] ) );
			}
		}

		// Product url and button text for external products.
		if ( 'external' === $product_type ) {
			if ( isset( $request['external_url'] ) ) {
				update_post_meta( $product->id, '_product_url', wc_clean( $request['external_url'] ) );
			}

			if ( isset( $request['button_text'] ) ) {
				update_post_meta( $product->id, '_button_text', wc_clean( $request['button_text'] ) );
			}
		}

		return true;
	}

	/**
	 * Save variations.
	 *
	 * @param WC_Product $product
	 * @param WP_REST_Request $request
	 * @return bool
	 * @throws WC_REST_Exception
	 */
	protected function save_variations_data( $product, $request ) {
		global $wpdb;

		$variations = $request['variations'];
		$attributes = $product->get_attributes();

		foreach ( $variations as $menu_order => $variation ) {
			$variation_id = isset( $variation['id'] ) ? absint( $variation['id'] ) : 0;

			// Generate a useful post title.
			$variation_post_title = sprintf( __( 'Variation #%s of %s', 'woocommerce' ), $variation_id, esc_html( get_the_title( $product->id ) ) );

			// Update or Add post.
			if ( ! $variation_id ) {
				$post_status = ( isset( $variation['visible'] ) && false === $variation['visible'] ) ? 'private' : 'publish';

				$new_variation = array(
					'post_title'   => $variation_post_title,
					'post_content' => '',
					'post_status'  => $post_status,
					'post_author'  => get_current_user_id(),
					'post_parent'  => $product->id,
					'post_type'    => 'product_variation',
					'menu_order'   => $menu_order,
				);

				$variation_id = wp_insert_post( $new_variation );

				do_action( 'woocommerce_create_product_variation', $variation_id );
			} else {
				$update_variation = array( 'post_title' => $variation_post_title, 'menu_order' => $menu_order );
				if ( isset( $variation['visible'] ) ) {
					$post_status = ( false === $variation['visible'] ) ? 'private' : 'publish';
					$update_variation['post_status'] = $post_status;
				}

				$wpdb->update( $wpdb->posts, $update_variation, array( 'ID' => $variation_id ) );

				do_action( 'woocommerce_update_product_variation', $variation_id );
			}

			// Stop with we don't have a variation ID.
			if ( is_wp_error( $variation_id ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_cannot_save_product_variation', $variation_id->get_error_message(), 400 );
			}

			// SKU.
			if ( isset( $variation['sku'] ) ) {
				$sku     = get_post_meta( $variation_id, '_sku', true );
				$new_sku = wc_clean( $variation['sku'] );

				if ( '' === $new_sku ) {
					update_post_meta( $variation_id, '_sku', '' );
				} elseif ( $new_sku !== $sku ) {
					if ( ! empty( $new_sku ) ) {
						$unique_sku = wc_product_has_unique_sku( $variation_id, $new_sku );
						if ( ! $unique_sku ) {
							throw new WC_REST_Exception( 'woocommerce_rest_product_sku_already_exists', __( 'The SKU already exists on another product.', 'woocommerce' ), 400 );
						} else {
							update_post_meta( $variation_id, '_sku', $new_sku );
						}
					} else {
						update_post_meta( $variation_id, '_sku', '' );
					}
				}
			}

			// Thumbnail.
			if ( isset( $variation['image'] ) && is_array( $variation['image'] ) ) {
				$image = current( $variation['image'] );
				if ( $image && is_array( $image ) ) {
					if ( isset( $image['position'] ) && isset( $image['src'] ) && 0 === $image['position'] ) {
						$upload = wc_rest_upload_image_from_url( wc_clean( $image['src'] ) );

						if ( is_wp_error( $upload ) ) {
							throw new WC_REST_Exception( 'woocommerce_product_image_upload_error', $upload->get_error_message(), 400 );
						}

						$attachment_id = wc_rest_set_uploaded_image_as_attachment( $upload, $product->id );

						// Set the image alt if present.
						if ( ! empty( $image['alt'] ) ) {
							update_post_meta( $attachment_id, '_wp_attachment_image_alt', wc_clean( $image['alt'] ) );
						}

						// Set the image name if present.
						if ( ! empty( $image['name'] ) ) {
							wp_update_post( array( 'ID' => $attachment_id, 'post_title' => $image['name'] ) );
						}

						update_post_meta( $variation_id, '_thumbnail_id', $attachment_id );
					}
				} else {
					delete_post_meta( $variation_id, '_thumbnail_id' );
				}
			}

			// Virtual variation.
			if ( isset( $variation['virtual'] ) ) {
				$is_virtual = ( true === $variation['virtual'] ) ? 'yes' : 'no';
				update_post_meta( $variation_id, '_virtual', $is_virtual );
			}

			// Downloadable variation.
			if ( isset( $variation['downloadable'] ) ) {
				$is_downloadable = ( true === $variation['downloadable'] ) ? 'yes' : 'no';
				update_post_meta( $variation_id, '_downloadable', $is_downloadable );
			} else {
				$is_downloadable = get_post_meta( $variation_id, '_downloadable', true );
			}

			// Shipping data.
			$this->save_product_shipping_data( $variation_id, $variation );

			// Stock handling.
			if ( isset( $variation['manage_stock'] ) ) {
				$manage_stock = ( true === $variation['manage_stock'] ) ? 'yes' : 'no';
			} else {
				$manage_stock = get_post_meta( $variation_id, '_manage_stock', true );
			}

			update_post_meta( $variation_id, '_manage_stock', '' === $manage_stock ? 'no' : $manage_stock );

			if ( isset( $variation['in_stock'] ) ) {
				$stock_status = ( true === $variation['in_stock'] ) ? 'instock' : 'outofstock';
			} else {
				$stock_status = get_post_meta( $variation_id, '_stock_status', true );
			}

			wc_update_product_stock_status( $variation_id, '' === $stock_status ? 'instock' : $stock_status );

			if ( 'yes' === $manage_stock ) {
				$backorders = get_post_meta( $variation_id, '_backorders', true );

				if ( isset( $variation['backorders'] ) ) {
					$backorders = $variation['backorders'];
				}

				update_post_meta( $variation_id, '_backorders', '' === $backorders ? 'no' : $backorders );

				if ( isset( $variation['stock_quantity'] ) ) {
					wc_update_product_stock( $variation_id, wc_stock_amount( $variation['stock_quantity'] ) );
				}  elseif ( isset( $request['inventory_delta'] ) ) {
					$stock_quantity  = wc_stock_amount( get_post_meta( $variation_id, '_stock', true ) );
					$stock_quantity += wc_stock_amount( $request['inventory_delta'] );

					wc_update_product_stock( $variation_id, wc_stock_amount( $stock_quantity ) );
				}
			} else {
				delete_post_meta( $variation_id, '_backorders' );
				delete_post_meta( $variation_id, '_stock' );
			}

			// Regular Price.
			if ( isset( $variation['regular_price'] ) ) {
				$regular_price = ( '' === $variation['regular_price'] ) ? '' : $variation['regular_price'];
			} else {
				$regular_price = get_post_meta( $variation_id, '_regular_price', true );
			}

			// Sale Price.
			if ( isset( $variation['sale_price'] ) ) {
				$sale_price = ( '' === $variation['sale_price'] ) ? '' : $variation['sale_price'];
			} else {
				$sale_price = get_post_meta( $variation_id, '_sale_price', true );
			}

			if ( isset( $variation['date_on_sale_from'] ) ) {
				$date_from = $variation['date_on_sale_from'];
			} else {
				$date_from = get_post_meta( $variation_id, '_sale_price_dates_from', true );
				$date_from = ( '' === $date_from ) ? '' : date( 'Y-m-d', $date_from );
			}

			if ( isset( $variation['date_on_sale_to'] ) ) {
				$date_to = $variation['date_on_sale_to'];
			} else {
				$date_to = get_post_meta( $variation_id, '_sale_price_dates_to', true );
				$date_to = ( '' === $date_to ) ? '' : date( 'Y-m-d', $date_to );
			}

			_wc_save_product_price( $variation_id, $regular_price, $sale_price, $date_from, $date_to );

			// Tax class.
			if ( isset( $variation['tax_class'] ) ) {
				if ( $variation['tax_class'] !== 'parent' ) {
					update_post_meta( $variation_id, '_tax_class', wc_clean( $variation['tax_class'] ) );
				} else {
					delete_post_meta( $variation_id, '_tax_class' );
				}
			}

			// Downloads.
			if ( 'yes' === $is_downloadable ) {
				// Downloadable files.
				if ( isset( $variation['downloads'] ) && is_array( $variation['downloads'] ) ) {
					$this->save_downloadable_files( $product->id, $variation['downloads'], $variation_id );
				}

				// Download limit.
				if ( isset( $variation['download_limit'] ) ) {
					update_post_meta( $variation_id, '_download_limit', -1 === $variation['download_limit'] ? '' : absint( $variation['download_limit'] ) );
				}

				// Download expiry.
				if ( isset( $variation['download_expiry'] ) ) {
					update_post_meta( $variation_id, '_download_expiry', -1 === $variation['download_expiry'] ? '' : absint( $variation['download_expiry'] ) );
				}
			} else {
				update_post_meta( $variation_id, '_download_limit', '' );
				update_post_meta( $variation_id, '_download_expiry', '' );
				update_post_meta( $variation_id, '_downloadable_files', '' );
			}

			// Description.
			if ( isset( $variation['description'] ) ) {
				update_post_meta( $variation_id, '_variation_description', wp_kses_post( $variation['description'] ) );
			}

			// Update taxonomies.
			if ( isset( $variation['attributes'] ) ) {
				$updated_attribute_keys = array();

				foreach ( $variation['attributes'] as $attribute ) {
					$attribute_id   = 0;
					$attribute_name = '';

					// Check ID for global attributes or name for product attributes.
					if ( ! empty( $attribute['id'] ) ) {
						$attribute_id   = absint( $attribute['id'] );
						$attribute_name = wc_attribute_taxonomy_name_by_id( $attribute_id );
					} elseif ( ! empty( $attribute['name'] ) ) {
						$attribute_name = sanitize_title( $attribute['name'] );
					}

					if ( ! $attribute_id && ! $attribute_name ) {
						continue;
					}

					if ( isset( $attributes[ $attribute_name ] ) ) {
						$_attribute = $attributes[ $attribute_name ];
					}

					if ( isset( $_attribute['is_variation'] ) && $_attribute['is_variation'] ) {
						$_attribute_key           = 'attribute_' . sanitize_title( $_attribute['name'] );
						$updated_attribute_keys[] = $_attribute_key;

						if ( isset( $_attribute['is_taxonomy'] ) && $_attribute['is_taxonomy'] ) {
							// Don't use wc_clean as it destroys sanitized characters
							$_attribute_value = isset( $attribute['option'] ) ? sanitize_title( stripslashes( $attribute['option'] ) ) : '';
						} else {
							$_attribute_value = isset( $attribute['option'] ) ? wc_clean( stripslashes( $attribute['option'] ) ) : '';
						}

						update_post_meta( $variation_id, $_attribute_key, $_attribute_value );
					}
				}

				// Remove old taxonomies attributes so data is kept up to date - first get attribute key names.
				$delete_attribute_keys = $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE 'attribute_%%' AND meta_key NOT IN ( '" . implode( "','", $updated_attribute_keys ) . "' ) AND post_id = %d;", $variation_id ) );

				foreach ( $delete_attribute_keys as $key ) {
					delete_post_meta( $variation_id, $key );
				}
			}

			do_action( 'woocommerce_rest_save_product_variation', $variation_id, $menu_order, $variation );
		}

		// Update parent if variable so price sorting works and stays in sync with the cheapest child.
		WC_Product_Variable::sync( $product->id );

		// Update default attributes options setting.
		if ( isset( $request['default_attribute'] ) ) {
			$request['default_attributes'] = $request['default_attribute'];
		}

		if ( isset( $request['default_attributes'] ) && is_array( $request['default_attributes'] ) ) {
			$default_attributes = array();

			foreach ( $request['default_attributes'] as $attribute ) {
				$attribute_id   = 0;
				$attribute_name = '';

				// Check ID for global attributes or name for product attributes.
				if ( ! empty( $attribute['id'] ) ) {
					$attribute_id   = absint( $attribute['id'] );
					$attribute_name = wc_attribute_taxonomy_name_by_id( $attribute_id );
				} elseif ( ! empty( $attribute['name'] ) ) {
					$attribute_name = sanitize_title( $attribute['name'] );
				}

				if ( ! $attribute_id && ! $attribute_name ) {
					continue;
				}

				if ( isset( $attributes[ $attribute_name ] ) ) {
					$_attribute = $attributes[ $attribute_name ];

					if ( $_attribute['is_variation'] ) {
						$value = '';

						if ( isset( $attribute['option'] ) ) {
							if ( $_attribute['is_taxonomy'] ) {
								// Don't use wc_clean as it destroys sanitized characters.
								$value = sanitize_title( trim( stripslashes( $attribute['option'] ) ) );
							} else {
								$value = wc_clean( trim( stripslashes( $attribute['option'] ) ) );
							}
						}

						if ( $value ) {
							$default_attributes[ $attribute_name ] = $value;
						}
					}
				}
			}

			update_post_meta( $product->id, '_default_attributes', $default_attributes );
		}

		return true;
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
			$product = wc_get_product( $post );

			// Check for featured/gallery images, upload it and set it.
			if ( isset( $request['images'] ) ) {
				$this->save_product_images( $product, $request['images'] );
			}

			// Save product meta fields.
			$this->save_product_meta( $product, $request );

			// Save variations.
			if ( isset( $request['type'] ) && 'variable' === $request['type'] && isset( $request['variations'] ) && is_array( $request['variations'] ) ) {
				$this->save_variations_data( $product, $request );
			}

			return true;
		} catch ( WC_REST_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
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
			$product = wc_get_product( $post );

			// Check for featured/gallery images, upload it and set it.
			if ( isset( $request['images'] ) ) {
				$this->save_product_images( $product, $request['images'] );
			}

			// Save product meta fields.
			$this->save_product_meta( $product, $request );

			// Save variations.
			if ( $product->is_type( 'variable' ) ) {
				if ( isset( $request['variations'] ) && is_array( $request['variations'] ) ) {
					$this->save_variations_data( $product, $request );
				} else {
					// Just sync variations.
					WC_Product_Variable::sync( $product->id );
					WC_Product_Variable::sync_stock_status( $product->id );
				}
			}

			return true;
		} catch ( WC_REST_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Clear cache/transients.
	 *
	 * @param WP_Post $post Post data.
	 */
	public function clear_transients( $post ) {
		wc_delete_product_transients( $post->ID );
	}

	/**
	 * Delete post.
	 *
	 * @param WP_Post $post
	 */
	protected function delete_post( $post ) {
		// Delete product attachments.
		$attachments = get_children( array(
			'post_parent' => $post->ID,
			'post_status' => 'any',
			'post_type'   => 'attachment',
		) );

		foreach ( (array) $attachments as $attachment ) {
			wp_delete_attachment( $attachment->ID, true );
		}

		// Delete product.
		wp_delete_post( $post->ID, true );
	}

	/**
	 * Get the Product's schema, conforming to JSON Schema.
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
				'purchasable' => array(
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
					'default'     => -1,
					'context'     => array( 'view', 'edit' ),
				),
				'download_expiry' => array(
					'description' => __( 'Number of days that the customer has up to be able to download the product.', 'woocommerce' ),
					'type'        => 'integer',
					'default'     => -1,
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
				'manage_stock' => array(
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
					'description' => __( 'Shows if the product is on backordered.', 'woocommerce' ),
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
					'description' => sprintf( __( 'Product weight (%s).', 'woocommerce' ), $weight_unit ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'dimensions' => array(
					'description' => __( 'Product dimensions.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'length' => array(
							'description' => sprintf( __( 'Product length (%s).', 'woocommerce' ), $dimension_unit ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'width' => array(
							'description' => sprintf( __( 'Product width (%s).', 'woocommerce' ), $dimension_unit ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'height' => array(
							'description' => sprintf( __( 'Product height (%s).', 'woocommerce' ), $dimension_unit ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
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
							'description' => __( 'Amount of times the variation can be downloaded.', 'woocommerce' ),
							'type'        => 'integer',
							'default'     => null,
							'context'     => array( 'view', 'edit' ),
						),
						'download_expiry' => array(
							'description' => __( 'Number of days that the customer has up to be able to download the variation.', 'woocommerce' ),
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
							'description' => sprintf( __( 'Variation weight (%s).', 'woocommerce' ), $weight_unit ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'dimensions' => array(
							'description' => __( 'Variation dimensions.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'properties'  => array(
								'length' => array(
									'description' => sprintf( __( 'Variation length (%s).', 'woocommerce' ), $dimension_unit ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'width' => array(
									'description' => sprintf( __( 'Variation width (%s).', 'woocommerce' ), $dimension_unit ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'height' => array(
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
							'type'        => 'array',
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
							'properties'  => array(
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
				'grouped_products' => array(
					'description' => __( 'List of grouped products ID.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
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

		$params['slug'] = array(
			'description'       => __( 'Limit result set to products with a specific slug.', 'woocommerce', 'woocommerce' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['status'] = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to products assigned a specific status.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => array_merge( array( 'any' ), array_keys( get_post_statuses() ) ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['type'] = array(
			'description'       => __( 'Limit result set to products assigned a specific type.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => array_keys( wc_get_product_types() ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['category'] = array(
			'description'       => __( 'Limit result set to products assigned a specific category.', 'woocommerce' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['tag'] = array(
			'description'       => __( 'Limit result set to products assigned a specific tag.', 'woocommerce' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['shipping_class'] = array(
			'description'       => __( 'Limit result set to products assigned a specific shipping class.', 'woocommerce' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['attribute'] = array(
			'description'       => __( 'Limit result set to products with a specific attribute.', 'woocommerce' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['attribute_term'] = array(
			'description'       => __( 'Limit result set to products with a specific attribute term (required an assigned attribute).', 'woocommerce' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['sku'] = array(
			'description'       => __( 'Limit result set to products with a specific SKU.', 'woocommerce' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}
