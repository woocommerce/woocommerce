<?php
/**
 * WooCommerce API Products Class
 *
 * Handles requests to the /products endpoint
 *
 * @author      WooThemes
 * @category    API
 * @package     WooCommerce/API
 * @since       2.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_API_Products extends WC_API_Resource {

	/** @var string $base the route base */
	protected $base = '/products';

	/**
	 * Register the routes for this class
	 *
	 * GET/POST /products
	 * GET /products/count
	 * GET/DELETE /products/<id>
	 * GET /products/<id>/reviews
	 *
	 * @since 2.1
	 * @param array $routes
	 * @return array
	 */
	public function register_routes( $routes ) {

		# GET/POST /products
		$routes[ $this->base ] = array(
			array( array( $this, 'get_products' ), WC_API_Server::READABLE ),
			array( array( $this, 'create_product' ), WC_API_SERVER::CREATABLE | WC_API_Server::ACCEPT_DATA ),
		);

		# GET /products/count
		$routes[ $this->base . '/count'] = array(
			array( array( $this, 'get_products_count' ), WC_API_Server::READABLE ),
		);

		# GET/DELETE /products/<id>
		$routes[ $this->base . '/(?P<id>\d+)' ] = array(
			array( array( $this, 'get_product' ), WC_API_Server::READABLE ),
			array( array( $this, 'delete_product' ), WC_API_Server::DELETABLE ),
		);

		# GET /products/<id>/reviews
		$routes[ $this->base . '/(?P<id>\d+)/reviews' ] = array(
			array( array( $this, 'get_product_reviews' ), WC_API_Server::READABLE ),
		);

		return $routes;
	}

	/**
	 * Get all products
	 *
	 * @since 2.1
	 * @param string $fields
	 * @param string $type
	 * @param array $filter
	 * @param int $page
	 * @return array
	 */
	public function get_products( $fields = null, $type = null, $filter = array(), $page = 1 ) {

		if ( ! empty( $type ) )
			$filter['type'] = $type;

		$filter['page'] = $page;

		$query = $this->query_products( $filter );

		$products = array();

		foreach( $query->posts as $product_id ) {

			if ( ! $this->is_readable( $product_id ) )
				continue;

			$products[] = current( $this->get_product( $product_id, $fields ) );
		}

		$this->server->add_pagination_headers( $query );

		return array( 'products' => $products );
	}

	/**
	 * Get the product for the given ID
	 *
	 * @since 2.1
	 * @param int $id the product ID
	 * @param string $fields
	 * @return array
	 */
	public function get_product( $id, $fields = null ) {

		$id = $this->validate_request( $id, 'product', 'read' );

		if ( is_wp_error( $id ) )
			return $id;

		$product = get_product( $id );

		// add data that applies to every product type
		$product_data = $this->get_product_data( $product );

		// add variations to variable products
		if ( $product->is_type( 'variable' ) && $product->has_child() ) {

			$product_data['variations'] = $this->get_variation_data( $product );
		}

		// add the parent product data to an individual variation
		if ( $product->is_type( 'variation' ) ) {

			$product_data['parent'] = $this->get_product_data( $product->parent );
		}

		return array( 'product' => apply_filters( 'woocommerce_api_product_response', $product_data, $product, $fields, $this->server ) );
	}

	/**
	 * Get the total number of orders
	 *
	 * @since 2.1
	 * @param string $type
	 * @param array $filter
	 * @return array
	 */
	public function get_products_count( $type = null, $filter = array() ) {

		if ( ! empty( $type ) )
			$filter['type'] = $type;

		if ( ! current_user_can( 'read_private_products' ) )
			return new WP_Error( 'woocommerce_api_user_cannot_read_products_count', __( 'You do not have permission to read the products count', 'woocommerce' ), array( 'status' => 401 ) );

		$query = $this->query_products( $filter );

		return array( 'count' => (int) $query->found_posts );
	}

	/**
	 * Create a new product
	 *
	 * @since 2.2
	 * @param array $data posted data
	 * @return array
	 */
	public function create_product( $data ) {

		// Check permisions
		if ( ! current_user_can( 'publish_products' ) ) {
			return new WP_Error( 'woocommerce_api_user_cannot_create_product', __( 'You do not have permission to create products', 'woocommerce' ), array( 'status' => 401 ) );
		}

		// Check if product title is specified
		if ( ! isset( $data['title'] ) ) {
			return new WP_Error( 'woocommerce_api_missing_product_title', sprintf( __( 'Missing parameter %s' ), 'title' ), array( 'status' => 400 ) );
		}

		// Check product type
		if ( ! isset( $data['type'] ) ) {
			$data['type'] = 'simple';
		}

		// Validate the product type
		if ( ! in_array( wc_clean( $data['type'] ), array_keys( wc_get_product_types() ) ) ) {
			return new WP_Error( 'woocommerce_api_invalid_product_type', sprintf( __( 'Invalid product type - the product type must be any of these: %s', 'woocommerce' ), implode( ', ', array_keys( wc_get_product_types() ) ) ), array( 'status' => 400 ) );
		}

		$new_product = array(
			'post_title'   => wc_clean( $data['title'] ),
			'post_status'  => ( isset( $data['status'] ) ? wc_clean( $data['status'] ) : 'publish' ),
			'post_type'    => 'product',
			'post_excerpt' => ( isset( $data['short_description'] ) ? wc_clean( $data['short_description'] ) : '' ),
			'post_content' => ( isset( $data['description'] ) ? wc_clean( $data['description'] ) : '' ),
			'post_author'  => get_current_user_id(),
		);

		// Attempts to create the new product
		$id = wp_insert_post( $new_product, true );

		// Checks for an error in the product creation
		if ( is_wp_error( $id ) ) {
			return new WP_Error( 'woocommerce_api_cannot_create_product', $id->get_error_message(), array( 'status' => 400 ) );
		}

		// Check for featured/gallery images, upload it and set it
		if ( isset( $data['images'] ) ) {
			$images = $this->save_product_images( $data['images'] );

			if ( is_wp_error( $images ) ) {
				return $images;
			}
		}

		// Save product meta fields
		$meta = $this->save_product_meta( $id, $data );
		if ( is_wp_error( $meta ) ) {
			return $meta;
		}

		$this->server->send_status( 201 );

		return $this->get_product( $id );
	}

	/**
	 * Edit a product
	 *
	 * @TODO implement in 2.2
	 * @param int $id the product ID
	 * @param array $data
	 * @return array
	 */
	public function edit_product( $id, $data ) {

		$id = $this->validate_request( $id, 'product', 'edit' );

		if ( is_wp_error( $id ) )
			return $id;

		return $this->get_product( $id );
	}

	/**
	 * Delete a product
	 *
	 * @since 2.2
	 * @param int $id the product ID
	 * @param bool $force true to permanently delete order, false to move to trash
	 * @return array
	 */
	public function delete_product( $id, $force = false ) {

		$id = $this->validate_request( $id, 'product', 'delete' );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		return $this->delete( $id, 'product', ( 'true' === $force ) );
	}

	/**
	 * Get the reviews for a product
	 *
	 * @since 2.1
	 * @param int $id the product ID to get reviews for
	 * @param string $fields fields to include in response
	 * @return array
	 */
	public function get_product_reviews( $id, $fields = null ) {

		$id = $this->validate_request( $id, 'product', 'read' );

		if ( is_wp_error( $id ) )
			return $id;

		$args = array(
			'post_id' => $id,
			'approve' => 'approve',
		);

		$comments = get_comments( $args );

		$reviews = array();

		foreach ( $comments as $comment ) {

			$reviews[] = array(
				'id'             => $comment->comment_ID,
				'created_at'     => $this->server->format_datetime( $comment->comment_date_gmt ),
				'review'         => $comment->comment_content,
				'rating'         => get_comment_meta( $comment->comment_ID, 'rating', true ),
				'reviewer_name'  => $comment->comment_author,
				'reviewer_email' => $comment->comment_author_email,
				'verified'       => (bool) wc_customer_bought_product( $comment->comment_author_email, $comment->user_id, $id ),
			);
		}

		return array( 'product_reviews' => apply_filters( 'woocommerce_api_product_reviews_response', $reviews, $id, $fields, $comments, $this->server ) );
	}

	/**
	 * Helper method to get product post objects
	 *
	 * @since 2.1
	 * @param array $args request arguments for filtering query
	 * @return WP_Query
	 */
	private function query_products( $args ) {

		// set base query arguments
		$query_args = array(
			'fields'      => 'ids',
			'post_type'   => 'product',
			'post_status' => 'publish',
			'meta_query'  => array(),
		);

		if ( ! empty( $args['type'] ) ) {

			$types = explode( ',', $args['type'] );

			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => $types,
				),
			);

			unset( $args['type'] );
		}

		$query_args = $this->merge_query_args( $query_args, $args );

		return new WP_Query( $query_args );
	}

	/**
	 * Get standard product data that applies to every product type
	 *
	 * @since 2.1
	 * @param WC_Product $product
	 * @return array
	 */
	private function get_product_data( $product ) {

		return array(
			'title'              => $product->get_title(),
			'id'                 => (int) $product->is_type( 'variation' ) ? $product->get_variation_id() : $product->id,
			'created_at'         => $this->server->format_datetime( $product->get_post_data()->post_date_gmt ),
			'updated_at'         => $this->server->format_datetime( $product->get_post_data()->post_modified_gmt ),
			'type'               => $product->product_type,
			'status'             => $product->get_post_data()->post_status,
			'downloadable'       => $product->is_downloadable(),
			'virtual'            => $product->is_virtual(),
			'permalink'          => $product->get_permalink(),
			'sku'                => $product->get_sku(),
			'price'              => wc_format_decimal( $product->get_price(), 2 ),
			'regular_price'      => wc_format_decimal( $product->get_regular_price(), 2 ),
			'sale_price'         => $product->get_sale_price() ? wc_format_decimal( $product->get_sale_price(), 2 ) : null,
			'price_html'         => $product->get_price_html(),
			'taxable'            => $product->is_taxable(),
			'tax_status'         => $product->get_tax_status(),
			'tax_class'          => $product->get_tax_class(),
			'managing_stock'     => $product->managing_stock(),
			'stock_quantity'     => (int) $product->get_stock_quantity(),
			'in_stock'           => $product->is_in_stock(),
			'backorders_allowed' => $product->backorders_allowed(),
			'backordered'        => $product->is_on_backorder(),
			'sold_individually'  => $product->is_sold_individually(),
			'purchaseable'       => $product->is_purchasable(),
			'featured'           => $product->is_featured(),
			'visible'            => $product->is_visible(),
			'catalog_visibility' => $product->visibility,
			'on_sale'            => $product->is_on_sale(),
			'weight'             => $product->get_weight() ? wc_format_decimal( $product->get_weight(), 2 ) : null,
			'dimensions'         => array(
				'length' => $product->length,
				'width'  => $product->width,
				'height' => $product->height,
				'unit'   => get_option( 'woocommerce_dimension_unit' ),
			),
			'shipping_required'  => $product->needs_shipping(),
			'shipping_taxable'   => $product->is_shipping_taxable(),
			'shipping_class'     => $product->get_shipping_class(),
			'shipping_class_id'  => ( 0 !== $product->get_shipping_class_id() ) ? $product->get_shipping_class_id() : null,
			'description'        => wpautop( do_shortcode( $product->get_post_data()->post_content ) ),
			'short_description'  => apply_filters( 'woocommerce_short_description', $product->get_post_data()->post_excerpt ),
			'reviews_allowed'    => ( 'open' === $product->get_post_data()->comment_status ),
			'average_rating'     => wc_format_decimal( $product->get_average_rating(), 2 ),
			'rating_count'       => (int) $product->get_rating_count(),
			'related_ids'        => array_map( 'absint', array_values( $product->get_related() ) ),
			'upsell_ids'         => array_map( 'absint', $product->get_upsells() ),
			'cross_sell_ids'     => array_map( 'absint', $product->get_cross_sells() ),
			'categories'         => wp_get_post_terms( $product->id, 'product_cat', array( 'fields' => 'names' ) ),
			'tags'               => wp_get_post_terms( $product->id, 'product_tag', array( 'fields' => 'names' ) ),
			'images'             => $this->get_images( $product ),
			'featured_src'       => wp_get_attachment_url( get_post_thumbnail_id( $product->is_type( 'variation' ) ? $product->variation_id : $product->id ) ),
			'attributes'         => $this->get_attributes( $product ),
			'downloads'          => $this->get_downloads( $product ),
			'download_limit'     => (int) $product->download_limit,
			'download_expiry'    => (int) $product->download_expiry,
			'download_type'      => $product->download_type,
			'purchase_note'      => wpautop( do_shortcode( $product->purchase_note ) ),
			'total_sales'        => metadata_exists( 'post', $product->id, 'total_sales' ) ? (int) get_post_meta( $product->id, 'total_sales', true ) : 0,
			'variations'         => array(),
			'parent'             => array(),
		);
	}

	/**
	 * Get an individual variation's data
	 *
	 * @since 2.1
	 * @param WC_Product $product
	 * @return array
	 */
	private function get_variation_data( $product ) {

		$variations = array();

		foreach ( $product->get_children() as $child_id ) {

			$variation = $product->get_child( $child_id );

			if ( ! $variation->exists() )
				continue;

			$variations[] = array(
				'id'                => $variation->get_variation_id(),
				'created_at'        => $this->server->format_datetime( $variation->get_post_data()->post_date_gmt ),
				'updated_at'        => $this->server->format_datetime( $variation->get_post_data()->post_modified_gmt ),
				'downloadable'      => $variation->is_downloadable(),
				'virtual'           => $variation->is_virtual(),
				'permalink'         => $variation->get_permalink(),
				'sku'               => $variation->get_sku(),
				'price'             => wc_format_decimal( $variation->get_price(), 2 ),
				'regular_price'     => wc_format_decimal( $variation->get_regular_price(), 2 ),
				'sale_price'        => $variation->get_sale_price() ? wc_format_decimal( $variation->get_sale_price(), 2 ) : null,
				'taxable'           => $variation->is_taxable(),
				'tax_status'        => $variation->get_tax_status(),
				'tax_class'         => $variation->get_tax_class(),
				'stock_quantity'    => (int) $variation->get_stock_quantity(),
				'in_stock'          => $variation->is_in_stock(),
				'backordered'       => $variation->is_on_backorder(),
				'purchaseable'      => $variation->is_purchasable(),
				'visible'           => $variation->variation_is_visible(),
				'on_sale'           => $variation->is_on_sale(),
				'weight'            => $variation->get_weight() ? wc_format_decimal( $variation->get_weight(), 2 ) : null,
				'dimensions'        => array(
					'length' => $variation->length,
					'width'  => $variation->width,
					'height' => $variation->height,
					'unit'   => get_option( 'woocommerce_dimension_unit' ),
				),
				'shipping_class'    => $variation->get_shipping_class(),
				'shipping_class_id' => ( 0 !== $variation->get_shipping_class_id() ) ? $variation->get_shipping_class_id() : null,
				'image'             => $this->get_images( $variation ),
				'attributes'        => $this->get_attributes( $variation ),
				'downloads'         => $this->get_downloads( $variation ),
				'download_limit'    => (int) $product->download_limit,
				'download_expiry'   => (int) $product->download_expiry,
			);
		}

		return $variations;
	}

	/**
	 * Save product meta
	 *
	 * @since 2.2
	 * @param int $id
	 * @param array $data
	 * @return bool|WP_Error
	 */
	public function save_product_meta( $id, $data ) {
		// Product Type
		if ( isset( $data['type'] ) ) {
			wp_set_object_terms( $id, wc_clean( $data['type'] ), 'product_type' );
		}

		// Downloadable
		if ( isset( $data['downloadable'] ) ) {
			if ( $data['downloadable'] === true ) {
				update_post_meta( $id, '_downloadable', 'yes' );
			} else {
				update_post_meta( $id, '_downloadable', 'no' );
			}
		}

		// Virtual
		if ( isset( $data['virtual'] ) ) {
			if ( $data['virtual'] === true ) {
				update_post_meta( $id, '_virtual', 'yes' );
			} else {
				update_post_meta( $id, '_virtual', 'no' );
			}
		}

		// Regular Price
		if ( isset( $data['regular_price'] ) ) {
			update_post_meta( $id, '_regular_price', ( $data['regular_price'] === '' ) ? '' : wc_format_decimal( $data['regular_price'] ) );
		}

		// Sale Price
		if ( isset( $data['sale_price'] ) ) {
			update_post_meta( $id, '_sale_price', ( $data['sale_price'] === '' ? '' : wc_format_decimal( $data['sale_price'] ) ) );
		}

		// Tax status
		if ( isset( $data['tax_status'] ) ) {
			update_post_meta( $id, '_tax_status', wc_clean( $data['tax_status'] ) );
		}

		// Tax Class
		if ( isset( $data['tax_class'] ) ) {
			update_post_meta( $id, '_tax_class', wc_clean( $data['tax_class'] ) );
		}

		// Catalog Visibility
		if ( isset( $data['catalog_visibility'] ) ) {
			update_post_meta( $id, '_visibility', wc_clean( $data['catalog_visibility'] ) );
		}

		// Purchase Note
		if ( isset( $data['purchase_note'] ) ) {
			update_post_meta( $id, '_purchase_note', wc_clean( $data['purchase_note'] ) );
		}

		// Featured Product
		if ( isset( $data['featured'] ) ) {
			if ( $data['featured'] === true ) {
				update_post_meta( $id, '_featured', 'yes' );
			} else {
				update_post_meta( $id, '_featured', 'no' );
			}
		}

		// Weight
		if ( isset( $data['weight'] ) ) {
			update_post_meta( $id, '_weight', ( $data['weight'] === '' ) ? '' : wc_format_decimal( $data['weight'] ) );
		}

		// Product dimensions
		if ( isset( $data['dimensions'] ) ) {
			// Height
			if ( isset( $data['dimensions']['height'] ) ) {
				update_post_meta( $id, '_height', ( $data['dimensions']['height'] === '' ) ? '' : wc_format_decimal( $data['dimensions']['height'] ) );
			}

			// Width
			if ( isset( $data['dimensions']['width'] ) ) {
				update_post_meta( $id, '_width', ( $data['dimensions']['width'] === '' ) ? '' : wc_format_decimal($data['dimensions']['width'] ) );
			}

			// Length
			if ( isset( $data['dimensions']['length'] ) ) {
				update_post_meta( $id, '_length', ( $data['dimensions']['length'] === '' ) ? '' : wc_format_decimal( $data['dimensions']['length'] ) );
			}
		}

		// Shipping class
		if ( isset( $data['shipping_class'] ) ) {
			wp_set_object_terms( $id, wc_clean( $data['shipping_class'] ), 'product_shipping_class' );
		}

		// SKU
		if ( isset( $data['sku'] ) ) {
			$sku_found = $wpdb->get_var( $wpdb->prepare("
					SELECT $wpdb->posts.ID
					FROM $wpdb->posts
					LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id)
					WHERE $wpdb->posts.post_type = 'product'
					AND $wpdb->posts.post_status = 'publish'
					AND $wpdb->postmeta.meta_key = '_sku' AND $wpdb->postmeta.meta_value = '%s'
					AND $wpdb->posts.ID <> %s
				 ", wc_clean( $data['sku'], $id ) ) );

			if ( $sku_found ) {
				return new WP_Error( 'woocommerce_api_product_sku_already_exists', __( 'The SKU already exists on another product' ), array( 'status' => 400 ) );
			}
			update_post_meta( $id, '_sku', wc_clean( $data['sku'] ) );
		}

		// Attributes
		if ( isset( $data['attributes'] ) ) {
			$attributes = array();
			$attribute_taxonomies = wc_get_attribute_taxonomies();
			foreach ( $data['attributes'] as $attribute ) {
				// TODO
			}
		}

		// Sale Price Date From
		if ( isset( $data['sale_price_dates_from'] ) ) {
			update_post_meta( $id, '_sale_price_dates_from', strtotime( $data['sale_price_dates_from'] ) );
		}

		// Sale Price Date To
		if ( isset( $data['sale_price_dates_to'] ) ) {
			update_post_meta( $id, '_sale_price_dates_to', strtotime( $data['sale_price_dates_to'] ) );
		}

		// Sold Individually
		if ( isset( $data['sold_individually'] ) ) {
			if ( wc_clean( $data['sold_individually'] ) === true ) {
				update_post_meta( $id, '_sold_individually', 'yes' );
			} else {
				update_post_meta( $id, '_sold_individually', '' );
			}
		}

		// Manage stock
		if ( isset( $data['managing_stock'] ) ) {
			if ( wc_clean( $data['managing_stock'] ) === true ) {
				update_post_meta( $id, '_manage_stock', 'yes' );
			} else {
				update_post_meta( $id, '_manage_stock', 'no' );
			}
		}

		return true;
	}

	/**
	 * Get the images for a product or product variation
	 *
	 * @since 2.1
	 * @param WC_Product|WC_Product_Variation $product
	 * @return array
	 */
	private function get_images( $product ) {

		$images = $attachment_ids = array();

		if ( $product->is_type( 'variation' ) ) {

			if ( has_post_thumbnail( $product->get_variation_id() ) ) {

				// add variation image if set
				$attachment_ids[] = get_post_thumbnail_id( $product->get_variation_id() );

			} elseif ( has_post_thumbnail( $product->id ) ) {

				// otherwise use the parent product featured image if set
				$attachment_ids[] = get_post_thumbnail_id( $product->id );
			}

		} else {

			// add featured image
			if ( has_post_thumbnail( $product->id ) ) {
				$attachment_ids[] = get_post_thumbnail_id( $product->id );
			}

			// add gallery images
			$attachment_ids = array_merge( $attachment_ids, $product->get_gallery_attachment_ids() );
		}

		// build image data
		foreach ( $attachment_ids as $position => $attachment_id ) {

			$attachment_post = get_post( $attachment_id );

			if ( is_null( $attachment_post ) )
				continue;

			$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );

			if ( ! is_array( $attachment ) )
				continue;

			$images[] = array(
				'id'         => (int) $attachment_id,
				'created_at' => $this->server->format_datetime( $attachment_post->post_date_gmt ),
				'updated_at' => $this->server->format_datetime( $attachment_post->post_modified_gmt ),
				'src'        => current( $attachment ),
				'title'      => get_the_title( $attachment_id ),
				'alt'        => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
				'position'   => $position,
			);
		}

		// set a placeholder image if the product has no images set
		if ( empty( $images ) ) {

			$images[] = array(
				'id'         => 0,
				'created_at' => $this->server->format_datetime( time() ), // default to now
				'updated_at' => $this->server->format_datetime( time() ),
				'src'        => wc_placeholder_img_src(),
				'title'      => __( 'Placeholder', 'woocommerce' ),
				'alt'        => __( 'Placeholder', 'woocommerce' ),
				'position'   => 0,
			);
		}

		return $images;
	}

	/**
	 * Save product images
	 *
	 * @since 2.2
	 * @param array $images
	 * @return void|WP_Error
	 */
	protected function save_product_images( $images ) {
		foreach ( $images as $image ) {
			if ( isset( $image['position'] ) && isset( $image['src'] ) && $image['position'] == 0 ) {
				$upload = $this->upload_product_image( wc_clean( $image['src'] ) );
				if ( is_wp_error( $upload ) ) {
					return new WP_Error( 'woocommerce_api_cannot_upload_product_image', $upload->get_error_message(), array( 'status' => 400 ) );
				}
				$attachment_id = $this->set_product_image_as_attachment( $upload, $id );
				set_post_thumbnail( $id, $attachment_id );
			} else if ( isset( $image['src'] ) ) {
				$upload = $this->upload_product_image( wc_clean( $image['src'] ) );
				if ( is_wp_error( $upload ) ) {
					return new WP_Error( 'woocommerce_api_cannot_upload_product_image', $upload->get_error_message(), array( 'status' => 400 ) );
				}
				$this->set_product_image_as_attachment( $upload, $id );
			}
		}
	}

	/**
	 * Upload image from URL
	 *
	 * @since 2.2
	 * @param string $image_url
	 * @return integer attachment id
	 */
	public function upload_product_image( $image_url ) {
		$file_name 		= basename( current( explode( '?', $image_url ) ) );
		$wp_filetype 	= wp_check_filetype( $file_name, null );
		$parsed_url 	= @parse_url( $image_url );

		// Check parsed URL
		if ( ! $parsed_url || ! is_array( $parsed_url ) ) {
			return new WP_Error( 'woocommerce_api_invalid_product_image', sprintf( __( 'Invalid URL %s' ), $image_url ), array( 'status' => 400 ) );
		}

		// Ensure url is valid
		$image_url = str_replace( ' ', '%20', $image_url );

		// Get the file
		$response = wp_remote_get( $image_url, array(
			'timeout' => 10
		) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'woocommerce_api_invalid_remote_product_image', sprintf( __( 'Error getting remote image %s' ), $image_url ), array( 'status' => 400 ) );
		}

		// Ensure we have a file name and type
		if ( ! $wp_filetype['type'] ) {
			$headers = wp_remote_retrieve_headers( $response );
			if ( isset( $headers['content-disposition'] ) && strstr( $headers['content-disposition'], 'filename=' ) ) {
				$disposition = end( explode( 'filename=', $headers['content-disposition'] ) );
				$disposition = sanitize_file_name( $disposition );
				$file_name   = $disposition;
			} elseif ( isset( $headers['content-type'] ) && strstr( $headers['content-type'], 'image/' ) ) {
				$file_name = 'image.' . str_replace( 'image/', '', $headers['content-type'] );
			}
			unset( $headers );
		}

		// Upload the file
		$upload = wp_upload_bits( $file_name, '', wp_remote_retrieve_body( $response ) );

		if ( $upload['error'] ) {
			return new WP_Error( 'woocommerce_api_product_image_upload_error', $upload['error'], array( 'status' => 400 ) );
		}

		// Get filesize
		$filesize = filesize( $upload['file'] );

		if ( 0 == $filesize ) {
			@unlink( $upload['file'] );
			unset( $upload );
			return new WP_Error( 'woocommerce_api_product_image_upload_file_error', __( 'Zero size file downloaded', 'woocommerce' ), array( 'status' => 400 ) );
		}

		unset( $response );

		return $upload;
	}

	/**
	 * Get product image as attachment
	 *
	 * @since 2.2
	 * @param array $upload
	 * @param int $post_id
	 * @return int
	 */
	protected function set_product_image_as_attachment( $upload, $post_id ) {
		$info = wp_check_filetype( $upload['file'] );
		$attachment = array(
			'guid' => $upload['url'],
			'post_mime_type' => $info['type'],
		);
		$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $post_id );

		return $attachment_id;
	}

	/**
	 * Get the attributes for a product or product variation
	 *
	 * @since 2.1
	 * @param WC_Product|WC_Product_Variation $product
	 * @return array
	 */
	private function get_attributes( $product ) {

		$attributes = array();

		if ( $product->is_type( 'variation' ) ) {

			// variation attributes
			foreach ( $product->get_variation_attributes() as $attribute_name => $attribute ) {

				// taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`
				$attributes[] = array(
					'name'   => ucwords( str_replace( 'attribute_', '', str_replace( 'pa_', '', $attribute_name ) ) ),
					'option' => $attribute,
				);
			}

		} else {

			foreach ( $product->get_attributes() as $attribute ) {

				// taxonomy-based attributes are comma-separated, others are pipe (|) separated
				if ( $attribute['is_taxonomy'] )
					$options = explode( ',', $product->get_attribute( $attribute['name'] ) );
				else
					$options = explode( '|', $product->get_attribute( $attribute['name'] ) );

				$attributes[] = array(
					'name'      => ucwords( str_replace( 'pa_', '', $attribute['name'] ) ),
					'position'  => $attribute['position'],
					'visible'   => (bool) $attribute['is_visible'],
					'variation' => (bool) $attribute['is_variation'],
					'options'   => array_map( 'trim', $options ),
				);
			}
		}

		return $attributes;
	}

	/**
	 * Get the downloads for a product or product variation
	 *
	 * @since 2.1
	 * @param WC_Product|WC_Product_Variation $product
	 * @return array
	 */
	private function get_downloads( $product ) {

		$downloads = array();

		if ( $product->is_downloadable() ) {

			foreach ( $product->get_files() as $file_id => $file ) {

				$downloads[] = array(
					'id'   => $file_id, // do not cast as int as this is a hash
					'name' => $file['name'],
					'file' => $file['file'],
				);
			}
		}

		return $downloads;
	}

}
