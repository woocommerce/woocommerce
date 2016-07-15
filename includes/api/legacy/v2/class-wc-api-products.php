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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_API_Products extends WC_API_Resource {

	/** @var string $base the route base */
	protected $base = '/products';

	/**
	 * Register the routes for this class
	 *
	 * GET/POST /products
	 * GET /products/count
	 * GET/PUT/DELETE /products/<id>
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

		# GET/PUT/DELETE /products/<id>
		$routes[ $this->base . '/(?P<id>\d+)' ] = array(
			array( array( $this, 'get_product' ), WC_API_Server::READABLE ),
			array( array( $this, 'edit_product' ), WC_API_Server::EDITABLE | WC_API_Server::ACCEPT_DATA ),
			array( array( $this, 'delete_product' ), WC_API_Server::DELETABLE ),
		);

		# GET /products/<id>/reviews
		$routes[ $this->base . '/(?P<id>\d+)/reviews' ] = array(
			array( array( $this, 'get_product_reviews' ), WC_API_Server::READABLE ),
		);

		# GET /products/<id>/orders
		$routes[ $this->base . '/(?P<id>\d+)/orders' ] = array(
			array( array( $this, 'get_product_orders' ), WC_API_Server::READABLE ),
		);

		# GET /products/categories
		$routes[ $this->base . '/categories' ] = array(
			array( array( $this, 'get_product_categories' ), WC_API_Server::READABLE ),
		);

		# GET /products/categories/<id>
		$routes[ $this->base . '/categories/(?P<id>\d+)' ] = array(
			array( array( $this, 'get_product_category' ), WC_API_Server::READABLE ),
		);

		# GET/POST /products/attributes
		$routes[ $this->base . '/attributes' ] = array(
			array( array( $this, 'get_product_attributes' ), WC_API_Server::READABLE ),
			array( array( $this, 'create_product_attribute' ), WC_API_SERVER::CREATABLE | WC_API_Server::ACCEPT_DATA ),
		);

		# GET/PUT/DELETE /attributes/<id>
		$routes[ $this->base . '/attributes/(?P<id>\d+)' ] = array(
			array( array( $this, 'get_product_attribute' ), WC_API_Server::READABLE ),
			array( array( $this, 'edit_product_attribute' ), WC_API_Server::EDITABLE | WC_API_Server::ACCEPT_DATA ),
			array( array( $this, 'delete_product_attribute' ), WC_API_Server::DELETABLE ),
		);

		# GET /products/sku/<product sku>
		$routes[ $this->base . '/sku/(?P<sku>\w[\w\s\-]*)' ] = array(
			array( array( $this, 'get_product_by_sku' ), WC_API_Server::READABLE ),
		);

		# POST|PUT /products/bulk
		$routes[ $this->base . '/bulk' ] = array(
			array( array( $this, 'bulk' ), WC_API_Server::EDITABLE | WC_API_Server::ACCEPT_DATA ),
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

		if ( ! empty( $type ) ) {
			$filter['type'] = $type;
		}

		$filter['page'] = $page;

		$query = $this->query_products( $filter );

		$products = array();

		foreach ( $query->posts as $product_id ) {

			if ( ! $this->is_readable( $product_id ) ) {
				continue;
			}

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

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$product = wc_get_product( $id );

		// add data that applies to every product type
		$product_data = $this->get_product_data( $product );

		// add variations to variable products
		if ( $product->is_type( 'variable' ) && $product->has_child() ) {

			$product_data['variations'] = $this->get_variation_data( $product );
		}

		// add the parent product data to an individual variation
		if ( $product->is_type( 'variation' ) && $product->parent ) {

			$product_data['parent'] = $this->get_product_data( $product->parent );
		}

		return array( 'product' => apply_filters( 'woocommerce_api_product_response', $product_data, $product, $fields, $this->server ) );
	}

	/**
	 * Get the total number of products
	 *
	 * @since 2.1
	 * @param string $type
	 * @param array $filter
	 * @return array
	 */
	public function get_products_count( $type = null, $filter = array() ) {
		try {
			if ( ! current_user_can( 'read_private_products' ) ) {
				throw new WC_API_Exception( 'woocommerce_api_user_cannot_read_products_count', __( 'You do not have permission to read the products count', 'woocommerce' ), 401 );
			}

			if ( ! empty( $type ) ) {
				$filter['type'] = $type;
			}

			$query = $this->query_products( $filter );

			return array( 'count' => (int) $query->found_posts );
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Create a new product
	 *
	 * @since 2.2
	 * @param array $data posted data
	 * @return array
	 */
	public function create_product( $data ) {
		$id = 0;

		try {
			if ( ! isset( $data['product'] ) ) {
				throw new WC_API_Exception( 'woocommerce_api_missing_product_data', sprintf( __( 'No %1$s data specified to create %1$s', 'woocommerce' ), 'product' ), 400 );
			}

			$data = $data['product'];

			// Check permissions
			if ( ! current_user_can( 'publish_products' ) ) {
				throw new WC_API_Exception( 'woocommerce_api_user_cannot_create_product', __( 'You do not have permission to create products', 'woocommerce' ), 401 );
			}

			$data = apply_filters( 'woocommerce_api_create_product_data', $data, $this );

			// Check if product title is specified
			if ( ! isset( $data['title'] ) ) {
				throw new WC_API_Exception( 'woocommerce_api_missing_product_title', sprintf( __( 'Missing parameter %s', 'woocommerce' ), 'title' ), 400 );
			}

			// Check product type
			if ( ! isset( $data['type'] ) ) {
				$data['type'] = 'simple';
			}

			// Set visible visibility when not sent
			if ( ! isset( $data['catalog_visibility'] ) ) {
				$data['catalog_visibility'] = 'visible';
			}

			// Validate the product type
			if ( ! in_array( wc_clean( $data['type'] ), array_keys( wc_get_product_types() ) ) ) {
				throw new WC_API_Exception( 'woocommerce_api_invalid_product_type', sprintf( __( 'Invalid product type - the product type must be any of these: %s', 'woocommerce' ), implode( ', ', array_keys( wc_get_product_types() ) ) ), 400 );
			}

			// Enable description html tags.
			$post_content = isset( $data['description'] ) ? wc_clean( $data['description'] ) : '';
			if ( $post_content && isset( $data['enable_html_description'] ) && true === $data['enable_html_description'] ) {

				$post_content = $data['description'];
			}

			// Enable short description html tags.
			$post_excerpt = isset( $data['short_description'] ) ? wc_clean( $data['short_description'] ) : '';
			if ( $post_excerpt && isset( $data['enable_html_short_description'] ) && true === $data['enable_html_short_description'] ) {
				$post_excerpt = $data['short_description'];
			}

			$new_product = array(
				'post_title'   => wc_clean( $data['title'] ),
				'post_status'  => ( isset( $data['status'] ) ? wc_clean( $data['status'] ) : 'publish' ),
				'post_type'    => 'product',
				'post_excerpt' => ( isset( $data['short_description'] ) ? $post_excerpt : '' ),
				'post_content' => ( isset( $data['description'] ) ? $post_content : '' ),
				'post_author'  => get_current_user_id(),
			);

			// Attempts to create the new product
			$id = wp_insert_post( $new_product, true );

			// Checks for an error in the product creation
			if ( is_wp_error( $id ) ) {
				throw new WC_API_Exception( 'woocommerce_api_cannot_create_product', $id->get_error_message(), 400 );
			}

			// Check for featured/gallery images, upload it and set it
			if ( isset( $data['images'] ) ) {
				$this->save_product_images( $id, $data['images'] );
			}

			// Save product meta fields
			$this->save_product_meta( $id, $data );

			// Save variations
			if ( isset( $data['type'] ) && 'variable' == $data['type'] && isset( $data['variations'] ) && is_array( $data['variations'] ) ) {
				$this->save_variations( $id, $data );
			}

			do_action( 'woocommerce_api_create_product', $id, $data );

			// Clear cache/transients
			wc_delete_product_transients( $id );

			$this->server->send_status( 201 );

			return $this->get_product( $id );
		} catch ( WC_API_Exception $e ) {
			// Remove the product when fails
			$this->clear_product( $id );

			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Edit a product
	 *
	 * @since 2.2
	 * @param int $id the product ID
	 * @param array $data
	 * @return array
	 */
	public function edit_product( $id, $data ) {
		try {
			if ( ! isset( $data['product'] ) ) {
				throw new WC_API_Exception( 'woocommerce_api_missing_product_data', sprintf( __( 'No %1$s data specified to edit %1$s', 'woocommerce' ), 'product' ), 400 );
			}

			$data = $data['product'];

			$id = $this->validate_request( $id, 'product', 'edit' );

			if ( is_wp_error( $id ) ) {
				return $id;
			}

			$data = apply_filters( 'woocommerce_api_edit_product_data', $data, $this );

			// Product title.
			if ( isset( $data['title'] ) ) {
				wp_update_post( array( 'ID' => $id, 'post_title' => wc_clean( $data['title'] ) ) );
			}

			// Product name (slug).
			if ( isset( $data['name'] ) ) {
				wp_update_post( array( 'ID' => $id, 'post_name' => sanitize_title( $data['name'] ) ) );
			}

			// Product status.
			if ( isset( $data['status'] ) ) {
				wp_update_post( array( 'ID' => $id, 'post_status' => wc_clean( $data['status'] ) ) );
			}

			// Product short description.
			if ( isset( $data['short_description'] ) ) {
				// Enable short description html tags.
				$post_excerpt = ( isset( $data['enable_html_short_description'] ) && true === $data['enable_html_short_description'] ) ? $data['short_description'] : wc_clean( $data['short_description'] );

				wp_update_post( array( 'ID' => $id, 'post_excerpt' => $post_excerpt ) );
			}

			// Product description.
			if ( isset( $data['description'] ) ) {
				// Enable description html tags.
				$post_content = ( isset( $data['enable_html_description'] ) && true === $data['enable_html_description'] ) ? $data['description'] : wc_clean( $data['description'] );

				wp_update_post( array( 'ID' => $id, 'post_content' => $post_content ) );
			}

			// Validate the product type
			if ( isset( $data['type'] ) && ! in_array( wc_clean( $data['type'] ), array_keys( wc_get_product_types() ) ) ) {
				throw new WC_API_Exception( 'woocommerce_api_invalid_product_type', sprintf( __( 'Invalid product type - the product type must be any of these: %s', 'woocommerce' ), implode( ', ', array_keys( wc_get_product_types() ) ) ), 400 );
			}

			// Check for featured/gallery images, upload it and set it
			if ( isset( $data['images'] ) ) {
				$this->save_product_images( $id, $data['images'] );
			}

			// Save product meta fields
			$this->save_product_meta( $id, $data );

			// Save variations
			$product = get_product( $id );
			if ( $product->is_type( 'variable' ) ) {
				if ( isset( $data['variations'] ) && is_array( $data['variations'] ) ) {
					$this->save_variations( $id, $data );
				} else {
					// Just sync variations
					WC_Product_Variable::sync( $id );
					WC_Product_Variable::sync_stock_status( $id );
				}
			}

			do_action( 'woocommerce_api_edit_product', $id, $data );

			// Clear cache/transients
			wc_delete_product_transients( $id );

			return $this->get_product( $id );
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Delete a product.
	 *
	 * @since 2.2
	 * @param int $id the product ID.
	 * @param bool $force true to permanently delete order, false to move to trash.
	 * @return array
	 */
	public function delete_product( $id, $force = false ) {

		$id = $this->validate_request( $id, 'product', 'delete' );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		do_action( 'woocommerce_api_delete_product', $id, $this );

		// If we're forcing, then delete permanently.
		if ( $force ) {
			$child_product_variations = get_children( 'post_parent=' . $id . '&post_type=product_variation' );

			if ( ! empty( $child_product_variations ) ) {
				foreach ( $child_product_variations as $child ) {
					wp_delete_post( $child->ID, true );
				}
			}

			$child_products = get_children( 'post_parent=' . $id . '&post_type=product' );

			if ( ! empty( $child_products ) ) {
				foreach ( $child_products as $child ) {
					$child_post                = array();
					$child_post['ID']          = $child->ID;
					$child_post['post_parent'] = 0;
					wp_update_post( $child_post );
				}
			}

			$result = wp_delete_post( $id, true );
		} else {
			$result = wp_trash_post( $id );
		}

		if ( ! $result ) {
			return new WP_Error( 'woocommerce_api_cannot_delete_product', sprintf( __( 'This %s cannot be deleted', 'woocommerce' ), 'product' ), array( 'status' => 500 ) );
		}

		// Delete parent product transients.
		if ( $parent_id = wp_get_post_parent_id( $id ) ) {
			wc_delete_product_transients( $parent_id );
		}

		if ( $force ) {
			return array( 'message' => sprintf( __( 'Permanently deleted %s', 'woocommerce' ), 'product' ) );
		} else {
			$this->server->send_status( '202' );

			return array( 'message' => sprintf( __( 'Deleted %s', 'woocommerce' ), 'product' ) );
		}
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

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$comments = get_approved_comments( $id );
		$reviews  = array();

		foreach ( $comments as $comment ) {

			$reviews[] = array(
				'id'             => intval( $comment->comment_ID ),
				'created_at'     => $this->server->format_datetime( $comment->comment_date_gmt ),
				'review'         => $comment->comment_content,
				'rating'         => get_comment_meta( $comment->comment_ID, 'rating', true ),
				'reviewer_name'  => $comment->comment_author,
				'reviewer_email' => $comment->comment_author_email,
				'verified'       => wc_review_is_from_verified_owner( $comment->comment_ID ),
			);
		}

		return array( 'product_reviews' => apply_filters( 'woocommerce_api_product_reviews_response', $reviews, $id, $fields, $comments, $this->server ) );
	}

	/**
	 * Get the orders for a product
	 *
	 * @since 2.4.0
	 * @param int $id the product ID to get orders for
	 * @param string fields  fields to retrieve
	 * @param string $filter filters to include in response
	 * @param string $status the order status to retrieve
	 * @param $page  $page   page to retrieve
	 * @return array
	 */
	public function get_product_orders( $id, $fields = null, $filter = array(), $status = null, $page = 1 ) {
		global $wpdb;

		$id = $this->validate_request( $id, 'product', 'read' );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$order_ids = $wpdb->get_col( $wpdb->prepare( "
			SELECT order_id
			FROM {$wpdb->prefix}woocommerce_order_items
			WHERE order_item_id IN ( SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_product_id' AND meta_value = %d )
			AND order_item_type = 'line_item'
		 ", $id ) );

		if ( empty( $order_ids ) ) {
			return array( 'orders' => array() );
		}

		$filter = array_merge( $filter, array(
			'in' => implode( ',', $order_ids )
		) );

		$orders = WC()->api->WC_API_Orders->get_orders( $fields, $filter, $status, $page );

		return array( 'orders' => apply_filters( 'woocommerce_api_product_orders_response', $orders['orders'], $id, $filter, $fields, $this->server ) );
	}

	/**
	 * Get a listing of product categories
	 *
	 * @since 2.2
	 * @param string|null $fields fields to limit response to
	 * @return array
	 */
	public function get_product_categories( $fields = null ) {
		try {
			// Permissions check
			if ( ! current_user_can( 'manage_product_terms' ) ) {
				throw new WC_API_Exception( 'woocommerce_api_user_cannot_read_product_categories', __( 'You do not have permission to read product categories', 'woocommerce' ), 401 );
			}

			$product_categories = array();

			$terms = get_terms( 'product_cat', array( 'hide_empty' => false, 'fields' => 'ids' ) );

			foreach ( $terms as $term_id ) {
				$product_categories[] = current( $this->get_product_category( $term_id, $fields ) );
			}

			return array( 'product_categories' => apply_filters( 'woocommerce_api_product_categories_response', $product_categories, $terms, $fields, $this ) );
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Get the product category for the given ID
	 *
	 * @since 2.2
	 * @param string $id product category term ID
	 * @param string|null $fields fields to limit response to
	 * @return array
	 */
	public function get_product_category( $id, $fields = null ) {
		try {
			$id = absint( $id );

			// Validate ID
			if ( empty( $id ) ) {
				throw new WC_API_Exception( 'woocommerce_api_invalid_product_category_id', __( 'Invalid product category ID', 'woocommerce' ), 400 );
			}

			// Permissions check
			if ( ! current_user_can( 'manage_product_terms' ) ) {
				throw new WC_API_Exception( 'woocommerce_api_user_cannot_read_product_categories', __( 'You do not have permission to read product categories', 'woocommerce' ), 401 );
			}

			$term = get_term( $id, 'product_cat' );

			if ( is_wp_error( $term ) || is_null( $term ) ) {
				throw new WC_API_Exception( 'woocommerce_api_invalid_product_category_id', __( 'A product category with the provided ID could not be found', 'woocommerce' ), 404 );
			}

			$term_id = intval( $term->term_id );

			// Get category display type
			$display_type = get_woocommerce_term_meta( $term_id, 'display_type' );

			// Get category image
			$image = '';
			if ( $image_id = get_woocommerce_term_meta( $term_id, 'thumbnail_id' ) ) {
				$image = wp_get_attachment_url( $image_id );
			}

			$product_category = array(
				'id'          => $term_id,
				'name'        => $term->name,
				'slug'        => $term->slug,
				'parent'      => $term->parent,
				'description' => $term->description,
				'display'     => $display_type ? $display_type : 'default',
				'image'       => $image ? esc_url( $image ) : '',
				'count'       => intval( $term->count )
			);

			return array( 'product_category' => apply_filters( 'woocommerce_api_product_category_response', $product_category, $id, $fields, $term, $this ) );
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Helper method to get product post objects
	 *
	 * @since 2.1
	 * @param array $args request arguments for filtering query
	 * @return WP_Query
	 */
	private function query_products( $args ) {

		// Set base query arguments
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

		// Filter products by category
		if ( ! empty( $args['category'] ) ) {
			$query_args['product_cat'] = $args['category'];
		}

		// Filter by specific sku
		if ( ! empty( $args['sku'] ) ) {
			if ( ! is_array( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array();
			}

			$query_args['meta_query'][] = array(
				'key'     => '_sku',
				'value'   => $args['sku'],
				'compare' => '='
			);

			$query_args['post_type'] = array( 'product', 'product_variation' );
		}

		$query_args = $this->merge_query_args( $query_args, $args );

		return new WP_Query( $query_args );
	}

	/**
	 * Get standard product data that applies to every product type
	 *
	 * @since 2.1
	 * @param WC_Product $product
	 * @return WC_Product
	 */
	private function get_product_data( $product ) {
		$prices_precision = wc_get_price_decimals();

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
			'price'              => wc_format_decimal( $product->get_price(), $prices_precision ),
			'regular_price'      => wc_format_decimal( $product->get_regular_price(), $prices_precision ),
			'sale_price'         => $product->get_sale_price() ? wc_format_decimal( $product->get_sale_price(), $prices_precision ) : null,
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
			'product_url'        => $product->is_type( 'external' ) ? $product->get_product_url() : '',
			'button_text'        => $product->is_type( 'external' ) ? $product->get_button_text() : '',
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
			'parent_id'          => $product->post->post_parent,
			'categories'         => wp_get_post_terms( $product->id, 'product_cat', array( 'fields' => 'names' ) ),
			'tags'               => wp_get_post_terms( $product->id, 'product_tag', array( 'fields' => 'names' ) ),
			'images'             => $this->get_images( $product ),
			'featured_src'       => (string) wp_get_attachment_url( get_post_thumbnail_id( $product->is_type( 'variation' ) ? $product->variation_id : $product->id ) ),
			'attributes'         => $this->get_attributes( $product ),
			'downloads'          => $this->get_downloads( $product ),
			'download_limit'     => (int) $product->download_limit,
			'download_expiry'    => (int) $product->download_expiry,
			'download_type'      => $product->download_type,
			'purchase_note'      => wpautop( do_shortcode( wp_kses_post( $product->purchase_note ) ) ),
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
		$prices_precision = wc_get_price_decimals();
		$variations       = array();

		foreach ( $product->get_children() as $child_id ) {

			$variation = $product->get_child( $child_id );

			if ( ! $variation->exists() ) {
				continue;
			}

			$post_data = get_post( $variation->get_variation_id() );

			$variations[] = array(
				'id'                => $variation->get_variation_id(),
				'created_at'        => $this->server->format_datetime( $post_data->post_date_gmt ),
				'updated_at'        => $this->server->format_datetime( $post_data->post_modified_gmt ),
				'downloadable'      => $variation->is_downloadable(),
				'virtual'           => $variation->is_virtual(),
				'permalink'         => $variation->get_permalink(),
				'sku'               => $variation->get_sku(),
				'price'             => wc_format_decimal( $variation->get_price(), $prices_precision ),
				'regular_price'     => wc_format_decimal( $variation->get_regular_price(), $prices_precision ),
				'sale_price'        => $variation->get_sale_price() ? wc_format_decimal( $variation->get_sale_price(), $prices_precision ) : null,
				'taxable'           => $variation->is_taxable(),
				'tax_status'        => $variation->get_tax_status(),
				'tax_class'         => $variation->get_tax_class(),
				'managing_stock'    => $variation->managing_stock(),
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
	 * @since  2.2
	 * @param  int $product_id
	 * @param  array $data
	 * @return bool
	 * @throws WC_API_Exception
	 */
	protected function save_product_meta( $product_id, $data ) {
		global $wpdb;

		// Product Type
		$product_type = null;
		if ( isset( $data['type'] ) ) {
			$product_type = wc_clean( $data['type'] );
			wp_set_object_terms( $product_id, $product_type, 'product_type' );
		} else {
			$_product_type = get_the_terms( $product_id, 'product_type' );
			if ( is_array( $_product_type ) ) {
				$_product_type = current( $_product_type );
				$product_type  = $_product_type->slug;
			}
		}

		// Default total sales.
		add_post_meta( $product_id, 'total_sales', '0', true );

		// Virtual
		if ( isset( $data['virtual'] ) ) {
			update_post_meta( $product_id, '_virtual', ( true === $data['virtual'] ) ? 'yes' : 'no' );
		}

		// Tax status
		if ( isset( $data['tax_status'] ) ) {
			update_post_meta( $product_id, '_tax_status', wc_clean( $data['tax_status'] ) );
		}

		// Tax Class
		if ( isset( $data['tax_class'] ) ) {
			update_post_meta( $product_id, '_tax_class', wc_clean( $data['tax_class'] ) );
		}

		// Catalog Visibility
		if ( isset( $data['catalog_visibility'] ) ) {
			update_post_meta( $product_id, '_visibility', wc_clean( $data['catalog_visibility'] ) );
		}

		// Purchase Note
		if ( isset( $data['purchase_note'] ) ) {
			update_post_meta( $product_id, '_purchase_note', wc_clean( $data['purchase_note'] ) );
		}

		// Featured Product
		if ( isset( $data['featured'] ) ) {
			update_post_meta( $product_id, '_featured', ( true === $data['featured'] ) ? 'yes' : 'no' );
		}

		// Shipping data
		$this->save_product_shipping_data( $product_id, $data );

		// SKU
		if ( isset( $data['sku'] ) ) {
			$sku     = get_post_meta( $product_id, '_sku', true );
			$new_sku = wc_clean( $data['sku'] );

			if ( '' == $new_sku ) {
				update_post_meta( $product_id, '_sku', '' );
			} elseif ( $new_sku !== $sku ) {
				if ( ! empty( $new_sku ) ) {
					$unique_sku = wc_product_has_unique_sku( $product_id, $new_sku );
					if ( ! $unique_sku ) {
						throw new WC_API_Exception( 'woocommerce_api_product_sku_already_exists', __( 'The SKU already exists on another product', 'woocommerce' ), 400 );
					} else {
						update_post_meta( $product_id, '_sku', $new_sku );
					}
				} else {
					update_post_meta( $product_id, '_sku', '' );
				}
			}
		}

		// Attributes
		if ( isset( $data['attributes'] ) ) {
			$attributes = array();

			foreach ( $data['attributes'] as $attribute ) {
				$is_taxonomy = 0;
				$taxonomy    = 0;

				if ( ! isset( $attribute['name'] ) ) {
					continue;
				}

				$attribute_slug = sanitize_title( $attribute['name'] );

				if ( isset( $attribute['slug'] ) ) {
					$taxonomy       = $this->get_attribute_taxonomy_by_slug( $attribute['slug'] );
					$attribute_slug = sanitize_title( $attribute['slug'] );
				}

				if ( $taxonomy ) {
					$is_taxonomy = 1;
				}

				if ( $is_taxonomy ) {

					if ( isset( $attribute['options'] ) ) {
						$options = $attribute['options'];

						if ( ! is_array( $attribute['options'] ) ) {
							// Text based attributes - Posted values are term names
							$options = explode( WC_DELIMITER, $options );
						}

						$values = array_map( 'wc_sanitize_term_text_based', $options );
						$values = array_filter( $values, 'strlen' );
					} else {
						$values = array();
					}

					// Update post terms
					if ( taxonomy_exists( $taxonomy ) ) {
						wp_set_object_terms( $product_id, $values, $taxonomy );
					}

					if ( ! empty( $values ) ) {
						// Add attribute to array, but don't set values
						$attributes[ $taxonomy ] = array(
							'name'         => $taxonomy,
							'value'        => '',
							'position'     => isset( $attribute['position'] ) ? (string) absint( $attribute['position'] ) : '0',
							'is_visible'   => ( isset( $attribute['visible'] ) && $attribute['visible'] ) ? 1 : 0,
							'is_variation' => ( isset( $attribute['variation'] ) && $attribute['variation'] ) ? 1 : 0,
							'is_taxonomy'  => $is_taxonomy
						);
					}

				} elseif ( isset( $attribute['options'] ) ) {
					// Array based
					if ( is_array( $attribute['options'] ) ) {
						$values = implode( ' ' . WC_DELIMITER . ' ', array_map( 'wc_clean', $attribute['options'] ) );

					// Text based, separate by pipe
					} else {
						$values = implode( ' ' . WC_DELIMITER . ' ', array_map( 'wc_clean', explode( WC_DELIMITER, $attribute['options'] ) ) );
					}

					// Custom attribute - Add attribute to array and set the values
					$attributes[ $attribute_slug ] = array(
						'name'         => wc_clean( $attribute['name'] ),
						'value'        => $values,
						'position'     => isset( $attribute['position'] ) ? (string) absint( $attribute['position'] ) : '0',
						'is_visible'   => ( isset( $attribute['visible'] ) && $attribute['visible'] ) ? 1 : 0,
						'is_variation' => ( isset( $attribute['variation'] ) && $attribute['variation'] ) ? 1 : 0,
						'is_taxonomy'  => $is_taxonomy
					);
				}
			}

			uasort( $attributes, 'wc_product_attribute_uasort_comparison' );

			update_post_meta( $product_id, '_product_attributes', $attributes );
		}

		// Sales and prices
		if ( in_array( $product_type, array( 'variable', 'grouped' ) ) ) {

			// Variable and grouped products have no prices
			update_post_meta( $product_id, '_regular_price', '' );
			update_post_meta( $product_id, '_sale_price', '' );
			update_post_meta( $product_id, '_sale_price_dates_from', '' );
			update_post_meta( $product_id, '_sale_price_dates_to', '' );
			update_post_meta( $product_id, '_price', '' );

		} else {

			// Regular Price
			if ( isset( $data['regular_price'] ) ) {
				$regular_price = ( '' === $data['regular_price'] ) ? '' : $data['regular_price'];
			} else {
				$regular_price = get_post_meta( $product_id, '_regular_price', true );
			}

			// Sale Price
			if ( isset( $data['sale_price'] ) ) {
				$sale_price = ( '' === $data['sale_price'] ) ? '' : $data['sale_price'];
			} else {
				$sale_price = get_post_meta( $product_id, '_sale_price', true );
			}

			if ( isset( $data['sale_price_dates_from'] ) ) {
				$date_from = $data['sale_price_dates_from'];
			} else {
				$date_from = get_post_meta( $product_id, '_sale_price_dates_from', true );
				$date_from = ( '' === $date_from ) ? '' : date( 'Y-m-d', $date_from );
			}

			if ( isset( $data['sale_price_dates_to'] ) ) {
				$date_to = $data['sale_price_dates_to'];
			} else {
				$date_to = get_post_meta( $product_id, '_sale_price_dates_to', true );
				$date_to = ( '' === $date_to ) ? '' : date( 'Y-m-d', $date_to );
			}

			_wc_save_product_price( $product_id, $regular_price, $sale_price, $date_from, $date_to );

		}

		// Product parent ID for groups
		if ( isset( $data['parent_id'] ) ) {
			wp_update_post( array( 'ID' => $product_id, 'post_parent' => absint( $data['parent_id'] ) ) );
		}

		// Update parent if grouped so price sorting works and stays in sync with the cheapest child
		$_product = wc_get_product( $product_id );
		if ( $_product->post->post_parent > 0 || $product_type == 'grouped' ) {

			$clear_parent_ids = array();

			if ( $_product->post->post_parent > 0 ) {
				$clear_parent_ids[] = $_product->post->post_parent;
			}

			if ( $product_type == 'grouped' ) {
				$clear_parent_ids[] = $product_id;
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

		// Sold Individually
		if ( isset( $data['sold_individually'] ) ) {
			update_post_meta( $product_id, '_sold_individually', ( true === $data['sold_individually'] ) ? 'yes' : '' );
		}

		// Stock status
		if ( isset( $data['in_stock'] ) ) {
			$stock_status = ( true === $data['in_stock'] ) ? 'instock' : 'outofstock';
		} else {
			$stock_status = get_post_meta( $product_id, '_stock_status', true );

			if ( '' === $stock_status ) {
				$stock_status = 'instock';
			}
		}

		// Stock Data
		if ( 'yes' == get_option( 'woocommerce_manage_stock' ) ) {
			// Manage stock
			if ( isset( $data['managing_stock'] ) ) {
				$managing_stock = ( true === $data['managing_stock'] ) ? 'yes' : 'no';
				update_post_meta( $product_id, '_manage_stock', $managing_stock );
			} else {
				$managing_stock = get_post_meta( $product_id, '_manage_stock', true );
			}

			// Backorders
			if ( isset( $data['backorders'] ) ) {
				if ( 'notify' == $data['backorders'] ) {
					$backorders = 'notify';
				} else {
					$backorders = ( true === $data['backorders'] ) ? 'yes' : 'no';
				}

				update_post_meta( $product_id, '_backorders', $backorders );
			} else {
				$backorders = get_post_meta( $product_id, '_backorders', true );
			}

			if ( 'grouped' == $product_type ) {

				update_post_meta( $product_id, '_manage_stock', 'no' );
				update_post_meta( $product_id, '_backorders', 'no' );
				update_post_meta( $product_id, '_stock', '' );

				wc_update_product_stock_status( $product_id, $stock_status );

			} elseif ( 'external' == $product_type ) {

				update_post_meta( $product_id, '_manage_stock', 'no' );
				update_post_meta( $product_id, '_backorders', 'no' );
				update_post_meta( $product_id, '_stock', '' );

				wc_update_product_stock_status( $product_id, 'instock' );
			} elseif ( 'yes' == $managing_stock ) {
				update_post_meta( $product_id, '_backorders', $backorders );

				// Stock status is always determined by children so sync later.
				if ( 'variable' !== $product_type ) {
					wc_update_product_stock_status( $product_id, $stock_status );
				}

				// Stock quantity
				if ( isset( $data['stock_quantity'] ) ) {
					wc_update_product_stock( $product_id, intval( $data['stock_quantity'] ) );
				}
			} else {

				// Don't manage stock
				update_post_meta( $product_id, '_manage_stock', 'no' );
				update_post_meta( $product_id, '_backorders', $backorders );
				update_post_meta( $product_id, '_stock', '' );

				wc_update_product_stock_status( $product_id, $stock_status );
			}

		} elseif ( 'variable' !== $product_type ) {
			wc_update_product_stock_status( $product_id, $stock_status );
		}

		// Upsells
		if ( isset( $data['upsell_ids'] ) ) {
			$upsells = array();
			$ids     = $data['upsell_ids'];

			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					if ( $id && $id > 0 ) {
						$upsells[] = $id;
					}
				}

				update_post_meta( $product_id, '_upsell_ids', $upsells );
			} else {
				delete_post_meta( $product_id, '_upsell_ids' );
			}
		}

		// Cross sells
		if ( isset( $data['cross_sell_ids'] ) ) {
			$crosssells = array();
			$ids        = $data['cross_sell_ids'];

			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					if ( $id && $id > 0 ) {
						$crosssells[] = $id;
					}
				}

				update_post_meta( $product_id, '_crosssell_ids', $crosssells );
			} else {
				delete_post_meta( $product_id, '_crosssell_ids' );
			}
		}

		// Product categories
		if ( isset( $data['categories'] ) && is_array( $data['categories'] ) ) {
			$term_ids = array_unique( array_map( 'intval', $data['categories'] ) );
			wp_set_object_terms( $product_id, $term_ids, 'product_cat' );
		}

		// Product tags
		if ( isset( $data['tags'] ) && is_array( $data['tags'] ) ) {
			$term_ids = array_unique( array_map( 'intval', $data['tags'] ) );
			wp_set_object_terms( $product_id, $term_ids, 'product_tag' );
		}

		// Downloadable
		if ( isset( $data['downloadable'] ) ) {
			$is_downloadable = ( true === $data['downloadable'] ) ? 'yes' : 'no';
			update_post_meta( $product_id, '_downloadable', $is_downloadable );
		} else {
			$is_downloadable = get_post_meta( $product_id, '_downloadable', true );
		}

		// Downloadable options
		if ( 'yes' == $is_downloadable ) {

			// Downloadable files
			if ( isset( $data['downloads'] ) && is_array( $data['downloads'] ) ) {
				$this->save_downloadable_files( $product_id, $data['downloads'] );
			}

			// Download limit
			if ( isset( $data['download_limit'] ) ) {
				update_post_meta( $product_id, '_download_limit', ( '' === $data['download_limit'] ) ? '' : absint( $data['download_limit'] ) );
			}

			// Download expiry
			if ( isset( $data['download_expiry'] ) ) {
				update_post_meta( $product_id, '_download_expiry', ( '' === $data['download_expiry'] ) ? '' : absint( $data['download_expiry'] ) );
			}

			// Download type
			if ( isset( $data['download_type'] ) ) {
				update_post_meta( $product_id, '_download_type', wc_clean( $data['download_type'] ) );
			}
		}

		// Product url
		if ( $product_type == 'external' ) {
			if ( isset( $data['product_url'] ) ) {
				update_post_meta( $product_id, '_product_url', wc_clean( $data['product_url'] ) );
			}

			if ( isset( $data['button_text'] ) ) {
				update_post_meta( $product_id, '_button_text', wc_clean( $data['button_text'] ) );
			}
		}

		// Reviews allowed
		if ( isset( $data['reviews_allowed'] ) ) {
			$reviews_allowed = ( true === $data['reviews_allowed'] ) ? 'open' : 'closed';

			$wpdb->update( $wpdb->posts, array( 'comment_status' => $reviews_allowed ), array( 'ID' => $product_id ) );
		}

		// Do action for product type
		do_action( 'woocommerce_api_process_product_meta_' . $product_type, $product_id, $data );

		return true;
	}

	/**
	 * Save variations
	 *
	 * @since  2.2
	 * @param  int $id
	 * @param  array $data
	 * @return bool
	 * @throws WC_API_Exception
	 */
	protected function save_variations( $id, $data ) {
		global $wpdb;

		$variations = $data['variations'];
		$attributes = (array) maybe_unserialize( get_post_meta( $id, '_product_attributes', true ) );

		foreach ( $variations as $menu_order => $variation ) {
			$variation_id = isset( $variation['id'] ) ? absint( $variation['id'] ) : 0;

			// Generate a useful post title
			$variation_post_title = sprintf( __( 'Variation #%s of %s', 'woocommerce' ), $variation_id, esc_html( get_the_title( $id ) ) );

			// Update or Add post
			if ( ! $variation_id ) {
				$post_status = ( isset( $variation['visible'] ) && false === $variation['visible'] ) ? 'private' : 'publish';

				$new_variation = array(
					'post_title'   => $variation_post_title,
					'post_content' => '',
					'post_status'  => $post_status,
					'post_author'  => get_current_user_id(),
					'post_parent'  => $id,
					'post_type'    => 'product_variation',
					'menu_order'   => $menu_order
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

			// Stop with we don't have a variation ID
			if ( is_wp_error( $variation_id ) ) {
				throw new WC_API_Exception( 'woocommerce_api_cannot_save_product_variation', $variation_id->get_error_message(), 400 );
			}

			// SKU
			if ( isset( $variation['sku'] ) ) {
				$sku     = get_post_meta( $variation_id, '_sku', true );
				$new_sku = wc_clean( $variation['sku'] );

				if ( '' == $new_sku ) {
					update_post_meta( $variation_id, '_sku', '' );
				} elseif ( $new_sku !== $sku ) {
					if ( ! empty( $new_sku ) ) {
						$unique_sku = wc_product_has_unique_sku( $variation_id, $new_sku );
						if ( ! $unique_sku ) {
							throw new WC_API_Exception( 'woocommerce_api_product_sku_already_exists', __( 'The SKU already exists on another product', 'woocommerce' ), 400 );
						} else {
							update_post_meta( $variation_id, '_sku', $new_sku );
						}
					} else {
						update_post_meta( $variation_id, '_sku', '' );
					}
				}
			}

			// Thumbnail
			if ( isset( $variation['image'] ) && is_array( $variation['image'] ) ) {
				$image = current( $variation['image'] );
				if ( $image && is_array( $image ) ) {
					if ( isset( $image['position'] ) && isset( $image['src'] ) && $image['position'] == 0 ) {
						$upload = $this->upload_product_image( wc_clean( $image['src'] ) );
						if ( is_wp_error( $upload ) ) {
							throw new WC_API_Exception( 'woocommerce_api_cannot_upload_product_image', $upload->get_error_message(), 400 );
						}
						$attachment_id = $this->set_product_image_as_attachment( $upload, $id );
						update_post_meta( $variation_id, '_thumbnail_id', $attachment_id );
					}
				} else {
					delete_post_meta( $variation_id, '_thumbnail_id' );
				}
			}

			// Virtual variation
			if ( isset( $variation['virtual'] ) ) {
				$is_virtual = ( true === $variation['virtual'] ) ? 'yes' : 'no';
				update_post_meta( $variation_id, '_virtual', $is_virtual );
			}

			// Downloadable variation
			if ( isset( $variation['downloadable'] ) ) {
				$is_downloadable = ( true === $variation['downloadable'] ) ? 'yes' : 'no';
				update_post_meta( $variation_id, '_downloadable', $is_downloadable );
			} else {
				$is_downloadable = get_post_meta( $variation_id, '_downloadable', true );
			}

			// Shipping data
			$this->save_product_shipping_data( $variation_id, $variation );

			// Stock handling
			if ( isset( $variation['managing_stock'] ) ) {
				$managing_stock = ( true === $variation['managing_stock'] ) ? 'yes' : 'no';
			} else {
				$managing_stock = get_post_meta( $variation_id, '_manage_stock', true );
			}

			update_post_meta( $variation_id, '_manage_stock', '' === $managing_stock ? 'no' : $managing_stock );

			if ( isset( $variation['in_stock'] ) ) {
				$stock_status = ( true === $variation['in_stock'] ) ? 'instock' : 'outofstock';
			} else {
				$stock_status = get_post_meta( $variation_id, '_stock_status', true );
			}

			wc_update_product_stock_status( $variation_id, '' === $stock_status ? 'instock' : $stock_status );

			if ( 'yes' === $managing_stock ) {
				$backorders = get_post_meta( $variation_id, '_backorders', true );

				if ( isset( $variation['backorders'] ) ) {
					if ( 'notify' === $variation['backorders'] ) {
						$backorders = 'notify';
					} else {
						$backorders = ( true === $variation['backorders'] ) ? 'yes' : 'no';
					}
				}

				update_post_meta( $variation_id, '_backorders', '' === $backorders ? 'no' : $backorders );

				if ( isset( $variation['stock_quantity'] ) ) {
					wc_update_product_stock( $variation_id, wc_stock_amount( $variation['stock_quantity'] ) );
				}
			} else {
				delete_post_meta( $variation_id, '_backorders' );
				delete_post_meta( $variation_id, '_stock' );
			}

			// Regular Price
			if ( isset( $variation['regular_price'] ) ) {
				$regular_price = ( '' === $variation['regular_price'] ) ? '' : $variation['regular_price'];
			} else {
				$regular_price = get_post_meta( $variation_id, '_regular_price', true );
			}

			// Sale Price
			if ( isset( $variation['sale_price'] ) ) {
				$sale_price = ( '' === $variation['sale_price'] ) ? '' : $variation['sale_price'];
			} else {
				$sale_price = get_post_meta( $variation_id, '_sale_price', true );
			}

			if ( isset( $variation['sale_price_dates_from'] ) ) {
				$date_from = $variation['sale_price_dates_from'];
			} else {
				$date_from = get_post_meta( $variation_id, '_sale_price_dates_from', true );
				$date_from = ( '' === $date_from ) ? '' : date( 'Y-m-d', $date_from );
			}

			if ( isset( $variation['sale_price_dates_to'] ) ) {
				$date_to = $variation['sale_price_dates_to'];
			} else {
				$date_to = get_post_meta( $variation_id, '_sale_price_dates_to', true );
				$date_to = ( '' === $date_to ) ? '' : date( 'Y-m-d', $date_to );
			}

			_wc_save_product_price( $variation_id, $regular_price, $sale_price, $date_from, $date_to );

			// Tax class
			if ( isset( $variation['tax_class'] ) ) {
				if ( $variation['tax_class'] !== 'parent' ) {
					update_post_meta( $variation_id, '_tax_class', wc_clean( $variation['tax_class'] ) );
				} else {
					delete_post_meta( $variation_id, '_tax_class' );
				}
			}

			// Downloads
			if ( 'yes' == $is_downloadable ) {
				// Downloadable files
				if ( isset( $variation['downloads'] ) && is_array( $variation['downloads'] ) ) {
					$this->save_downloadable_files( $id, $variation['downloads'], $variation_id );
				}

				// Download limit
				if ( isset( $variation['download_limit'] ) ) {
					$download_limit = absint( $variation['download_limit'] );
					update_post_meta( $variation_id, '_download_limit', ( ! $download_limit ) ? '' : $download_limit );
				}

				// Download expiry
				if ( isset( $variation['download_expiry'] ) ) {
					$download_expiry = absint( $variation['download_expiry'] );
					update_post_meta( $variation_id, '_download_expiry', ( ! $download_expiry ) ? '' : $download_expiry );
				}
			} else {
				update_post_meta( $variation_id, '_download_limit', '' );
				update_post_meta( $variation_id, '_download_expiry', '' );
				update_post_meta( $variation_id, '_downloadable_files', '' );
			}

			// Update taxonomies
			if ( isset( $variation['attributes'] ) ) {
				$updated_attribute_keys = array();

				foreach ( $variation['attributes'] as $attribute_key => $attribute ) {
					if ( ! isset( $attribute['name'] ) ) {
						continue;
					}

					$taxonomy   = 0;
					$_attribute = array();

					if ( isset( $attribute['slug'] ) ) {
						$taxonomy = $this->get_attribute_taxonomy_by_slug( $attribute['slug'] );
					}

					if ( ! $taxonomy ) {
						$taxonomy = sanitize_title( $attribute['name'] );
					}

					if ( isset( $attributes[ $taxonomy ] ) ) {
						$_attribute = $attributes[ $taxonomy ];
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

				// Remove old taxonomies attributes so data is kept up to date - first get attribute key names
				$delete_attribute_keys = $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE 'attribute_%%' AND meta_key NOT IN ( '" . implode( "','", $updated_attribute_keys ) . "' ) AND post_id = %d;", $variation_id ) );

				foreach ( $delete_attribute_keys as $key ) {
					delete_post_meta( $variation_id, $key );
				}
			}

			do_action( 'woocommerce_api_save_product_variation', $variation_id, $menu_order, $variation );
		}

		// Update parent if variable so price sorting works and stays in sync with the cheapest child
		WC_Product_Variable::sync( $id );

		// Update default attributes options setting
		if ( isset( $data['default_attribute'] ) ) {
			$data['default_attributes'] = $data['default_attribute'];
		}

		if ( isset( $data['default_attributes'] ) && is_array( $data['default_attributes'] ) ) {
			$default_attributes = array();

			foreach ( $data['default_attributes'] as $default_attr_key => $default_attr ) {
				if ( ! isset( $default_attr['name'] ) ) {
					continue;
				}

				$taxonomy = sanitize_title( $default_attr['name'] );

				if ( isset( $default_attr['slug'] ) ) {
					$taxonomy = $this->get_attribute_taxonomy_by_slug( $default_attr['slug'] );
				}

				if ( isset( $attributes[ $taxonomy ] ) ) {
					$_attribute = $attributes[ $taxonomy ];

					if ( $_attribute['is_variation'] ) {
						$value = '';

						if ( isset( $default_attr['option'] ) ) {
							if ( $_attribute['is_taxonomy'] ) {
								// Don't use wc_clean as it destroys sanitized characters
								$value = sanitize_title( trim( stripslashes( $default_attr['option'] ) ) );
							} else {
								$value = wc_clean( trim( stripslashes( $default_attr['option'] ) ) );
							}
						}

						if ( $value ) {
							$default_attributes[ $taxonomy ] = $value;
						}
					}
				}
			}

			update_post_meta( $id, '_default_attributes', $default_attributes );
		}

		return true;
	}

	/**
	 * Save product shipping data
	 *
	 * @since 2.2
	 * @param int $id
	 * @param array $data
	 */
	private function save_product_shipping_data( $id, $data ) {
		if ( isset( $data['weight'] ) ) {
			update_post_meta( $id, '_weight', ( '' === $data['weight'] ) ? '' : wc_format_decimal( $data['weight'] ) );
		}

		// Product dimensions
		if ( isset( $data['dimensions'] ) ) {
			// Height
			if ( isset( $data['dimensions']['height'] ) ) {
				update_post_meta( $id, '_height', ( '' === $data['dimensions']['height'] ) ? '' : wc_format_decimal( $data['dimensions']['height'] ) );
			}

			// Width
			if ( isset( $data['dimensions']['width'] ) ) {
				update_post_meta( $id, '_width', ( '' === $data['dimensions']['width'] ) ? '' : wc_format_decimal($data['dimensions']['width'] ) );
			}

			// Length
			if ( isset( $data['dimensions']['length'] ) ) {
				update_post_meta( $id, '_length', ( '' === $data['dimensions']['length'] ) ? '' : wc_format_decimal( $data['dimensions']['length'] ) );
			}
		}

		// Virtual
		if ( isset( $data['virtual'] ) ) {
			$virtual = ( true === $data['virtual'] ) ? 'yes' : 'no';

			if ( 'yes' == $virtual ) {
				update_post_meta( $id, '_weight', '' );
				update_post_meta( $id, '_length', '' );
				update_post_meta( $id, '_width', '' );
				update_post_meta( $id, '_height', '' );
			}
		}

		// Shipping class
		if ( isset( $data['shipping_class'] ) ) {
			wp_set_object_terms( $id, wc_clean( $data['shipping_class'] ), 'product_shipping_class' );
		}
	}

	/**
	 * Save downloadable files
	 *
	 * @since 2.2
	 * @param int $product_id
	 * @param array $downloads
	 * @param int $variation_id
	 */
	private function save_downloadable_files( $product_id, $downloads, $variation_id = 0 ) {
		$files = array();

		// File paths will be stored in an array keyed off md5(file path)
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
				'file' => $file_url
			);
		}

		// Grant permission to any newly added files on any existing orders for this product prior to saving
		do_action( 'woocommerce_process_product_file_download_paths', $product_id, $variation_id, $files );

		$id = ( 0 === $variation_id ) ? $product_id : $variation_id;
		update_post_meta( $id, '_downloadable_files', $files );
	}

	/**
	 * Get attribute taxonomy by slug.
	 *
	 * @since 2.2
	 * @param string $slug
	 * @return string|null
	 */
	private function get_attribute_taxonomy_by_slug( $slug ) {
		$taxonomy = null;
		$attribute_taxonomies = wc_get_attribute_taxonomies();

		foreach ( $attribute_taxonomies as $key => $tax ) {
			if ( $slug == $tax->attribute_name ) {
				$taxonomy = 'pa_' . $tax->attribute_name;

				break;
			}
		}

		return $taxonomy;
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

				// Add variation image if set
				$attachment_ids[] = get_post_thumbnail_id( $product->get_variation_id() );

			} elseif ( has_post_thumbnail( $product->id ) ) {

				// Otherwise use the parent product featured image if set
				$attachment_ids[] = get_post_thumbnail_id( $product->id );
			}

		} else {

			// Add featured image
			if ( has_post_thumbnail( $product->id ) ) {
				$attachment_ids[] = get_post_thumbnail_id( $product->id );
			}

			// Add gallery images
			$attachment_ids = array_merge( $attachment_ids, $product->get_gallery_attachment_ids() );
		}

		// Build image data
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
				'id'         => (int) $attachment_id,
				'created_at' => $this->server->format_datetime( $attachment_post->post_date_gmt ),
				'updated_at' => $this->server->format_datetime( $attachment_post->post_modified_gmt ),
				'src'        => current( $attachment ),
				'title'      => get_the_title( $attachment_id ),
				'alt'        => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
				'position'   => (int) $position,
			);
		}

		// Set a placeholder image if the product has no images set
		if ( empty( $images ) ) {

			$images[] = array(
				'id'         => 0,
				'created_at' => $this->server->format_datetime( time() ), // Default to now
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
	 * @since  2.2
	 * @param  array $images
	 * @param  int $id
	 * @throws WC_API_Exception
	 */
	protected function save_product_images( $id, $images ) {
		if ( is_array( $images ) ) {
			$gallery = array();

			foreach ( $images as $image ) {
				if ( isset( $image['position'] ) && $image['position'] == 0 ) {
					$attachment_id = isset( $image['id'] ) ? absint( $image['id'] ) : 0;

					if ( 0 === $attachment_id && isset( $image['src'] ) ) {
						$upload = $this->upload_product_image( esc_url_raw( $image['src'] ) );

						if ( is_wp_error( $upload ) ) {
							throw new WC_API_Exception( 'woocommerce_api_cannot_upload_product_image', $upload->get_error_message(), 400 );
						}

						$attachment_id = $this->set_product_image_as_attachment( $upload, $id );
					}

					set_post_thumbnail( $id, $attachment_id );
				} else {
					$attachment_id = isset( $image['id'] ) ? absint( $image['id'] ) : 0;

					if ( 0 === $attachment_id && isset( $image['src'] ) ) {
						$upload = $this->upload_product_image( esc_url_raw( $image['src'] ) );

						if ( is_wp_error( $upload ) ) {
							throw new WC_API_Exception( 'woocommerce_api_cannot_upload_product_image', $upload->get_error_message(), 400 );
						}

						$gallery[] = $this->set_product_image_as_attachment( $upload, $id );
					} else {
						$gallery[] = $attachment_id;
					}
				}
			}

			if ( ! empty( $gallery ) ) {
				update_post_meta( $id, '_product_image_gallery', implode( ',', $gallery ) );
			}
		} else {
			delete_post_thumbnail( $id );
			update_post_meta( $id, '_product_image_gallery', '' );
		}
	}

	/**
	 * Upload image from URL
	 *
	 * @since  2.2
	 * @param  string $image_url
	 * @return int|WP_Error attachment id
	 * @throws WC_API_Exception
	 */
	public function upload_product_image( $image_url ) {
		$file_name 		= basename( current( explode( '?', $image_url ) ) );
		$wp_filetype 	= wp_check_filetype( $file_name, null );
		$parsed_url 	= @parse_url( $image_url );

		// Check parsed URL
		if ( ! $parsed_url || ! is_array( $parsed_url ) ) {
			throw new WC_API_Exception( 'woocommerce_api_invalid_product_image', sprintf( __( 'Invalid URL %s', 'woocommerce' ), $image_url ), 400 );
		}

		// Ensure url is valid
		$image_url = str_replace( ' ', '%20', $image_url );

		// Get the file
		$response = wp_safe_remote_get( $image_url, array(
			'timeout' => 10
		) );

		if ( is_wp_error( $response ) ) {
			throw new WC_API_Exception( 'woocommerce_api_invalid_remote_product_image', sprintf( __( 'Error getting remote image %s.', 'woocommerce' ), $image_url ) . ' ' . sprintf( __( 'Error: %s.', 'woocommerce' ), $response->get_error_message() ), 400 );
		} elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			throw new WC_API_Exception( 'woocommerce_api_invalid_remote_product_image', sprintf( __( 'Error getting remote image %s.', 'woocommerce' ), $image_url ), 400 );
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
			throw new WC_API_Exception( 'woocommerce_api_product_image_upload_error', $upload['error'], 400 );
		}

		// Get filesize
		$filesize = filesize( $upload['file'] );

		if ( 0 == $filesize ) {
			@unlink( $upload['file'] );
			unset( $upload );
			throw new WC_API_Exception( 'woocommerce_api_product_image_upload_file_error', __( 'Zero size file downloaded', 'woocommerce' ), 400 );
		}

		unset( $response );

		return $upload;
	}

	/**
	 * Sets product image as attachment and returns the attachment ID.
	 *
	 * @since 2.2
	 * @param array $upload
	 * @param int $id
	 * @return int
	 */
	protected function set_product_image_as_attachment( $upload, $id ) {
		$info    = wp_check_filetype( $upload['file'] );
		$title   = '';
		$content = '';

		if ( $image_meta = @wp_read_image_metadata( $upload['file'] ) ) {
			if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
				$title = $image_meta['title'];
			}
			if ( trim( $image_meta['caption'] ) ) {
				$content = $image_meta['caption'];
			}
		}

		$attachment = array(
			'post_mime_type' => $info['type'],
			'guid'           => $upload['url'],
			'post_parent'    => $id,
			'post_title'     => $title,
			'post_content'   => $content
		);

		$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $id );
		if ( ! is_wp_error( $attachment_id ) ) {
			wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
		}

		return $attachment_id;
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
					'name'   => wc_attribute_label( str_replace( 'attribute_', '', $attribute_name ) ),
					'slug'   => str_replace( 'attribute_', '', str_replace( 'pa_', '', $attribute_name ) ),
					'option' => $attribute,
				);
			}

		} else {

			foreach ( $product->get_attributes() as $attribute ) {
				$attributes[] = array(
					'name'      => wc_attribute_label( $attribute['name'] ),
					'slug'      => str_replace( 'pa_', '', $attribute['name'] ),
					'position'  => (int) $attribute['position'],
					'visible'   => (bool) $attribute['is_visible'],
					'variation' => (bool) $attribute['is_variation'],
					'options'   => $this->get_attribute_options( $product->id, $attribute ),
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

	/**
	 * Get a listing of product attributes
	 *
	 * @since 2.4.0
	 * @param string|null $fields fields to limit response to
	 * @return array
	 */
	public function get_product_attributes( $fields = null ) {
		try {
			// Permissions check
			if ( ! current_user_can( 'manage_product_terms' ) ) {
				throw new WC_API_Exception( 'woocommerce_api_user_cannot_read_product_attributes', __( 'You do not have permission to read product attributes', 'woocommerce' ), 401 );
			}

			$product_attributes   = array();
			$attribute_taxonomies = wc_get_attribute_taxonomies();

			foreach ( $attribute_taxonomies as $attribute ) {
				$product_attributes[] = array(
					'id'           => intval( $attribute->attribute_id ),
					'name'         => $attribute->attribute_label,
					'slug'         => wc_attribute_taxonomy_name( $attribute->attribute_name ),
					'type'         => $attribute->attribute_type,
					'order_by'     => $attribute->attribute_orderby,
					'has_archives' => (bool) $attribute->attribute_public
				);
			}

			return array( 'product_attributes' => apply_filters( 'woocommerce_api_product_attributes_response', $product_attributes, $attribute_taxonomies, $fields, $this ) );
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Get the product attribute for the given ID
	 *
	 * @since 2.4.0
	 * @param string $id product attribute term ID
	 * @param string|null $fields fields to limit response to
	 * @return array
	 */
	public function get_product_attribute( $id, $fields = null ) {
		global $wpdb;

		try {
			$id = absint( $id );

			// Validate ID
			if ( empty( $id ) ) {
				throw new WC_API_Exception( 'woocommerce_api_invalid_product_attribute_id', __( 'Invalid product attribute ID', 'woocommerce' ), 400 );
			}

			// Permissions check
			if ( ! current_user_can( 'manage_product_terms' ) ) {
				throw new WC_API_Exception( 'woocommerce_api_user_cannot_read_product_categories', __( 'You do not have permission to read product attributes', 'woocommerce' ), 401 );
			}

			$attribute = $wpdb->get_row( $wpdb->prepare( "
				SELECT *
				FROM {$wpdb->prefix}woocommerce_attribute_taxonomies
				WHERE attribute_id = %d
			 ", $id ) );

			if ( is_wp_error( $attribute ) || is_null( $attribute ) ) {
				throw new WC_API_Exception( 'woocommerce_api_invalid_product_attribute_id', __( 'A product attribute with the provided ID could not be found', 'woocommerce' ), 404 );
			}

			$product_attribute = array(
				'id'           => intval( $attribute->attribute_id ),
				'name'         => $attribute->attribute_label,
				'slug'         => wc_attribute_taxonomy_name( $attribute->attribute_name ),
				'type'         => $attribute->attribute_type,
				'order_by'     => $attribute->attribute_orderby,
				'has_archives' => (bool) $attribute->attribute_public
			);

			return array( 'product_attribute' => apply_filters( 'woocommerce_api_product_attribute_response', $product_attribute, $id, $fields, $attribute, $this ) );
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Validate attribute data.
	 *
	 * @since  2.4.0
	 * @param  string $name
	 * @param  string $slug
	 * @param  string $type
	 * @param  string $order_by
	 * @param  bool   $new_data
	 * @return bool
	 * @throws WC_API_Exception
	 */
	protected function validate_attribute_data( $name, $slug, $type, $order_by, $new_data = true ) {
		if ( empty( $name ) ) {
			throw new WC_API_Exception( 'woocommerce_api_missing_product_attribute_name', sprintf( __( 'Missing parameter %s', 'woocommerce' ), 'name' ), 400 );
		}

		if ( strlen( $slug ) >= 28 ) {
			throw new WC_API_Exception( 'woocommerce_api_invalid_product_attribute_slug_too_long', sprintf( __( 'Slug "%s" is too long (28 characters max). Shorten it, please.', 'woocommerce' ), $slug ), 400 );
		} else if ( wc_check_if_attribute_name_is_reserved( $slug ) ) {
			throw new WC_API_Exception( 'woocommerce_api_invalid_product_attribute_slug_reserved_name', sprintf( __( 'Slug "%s" is not allowed because it is a reserved term. Change it, please.', 'woocommerce' ), $slug ), 400 );
		} else if ( $new_data && taxonomy_exists( wc_attribute_taxonomy_name( $slug ) ) ) {
			throw new WC_API_Exception( 'woocommerce_api_invalid_product_attribute_slug_already_exists', sprintf( __( 'Slug "%s" is already in use. Change it, please.', 'woocommerce' ), $slug ), 400 );
		}

		// Validate the attribute type
		if ( ! in_array( wc_clean( $type ), array_keys( wc_get_attribute_types() ) ) ) {
			throw new WC_API_Exception( 'woocommerce_api_invalid_product_attribute_type', sprintf( __( 'Invalid product attribute type - the product attribute type must be any of these: %s', 'woocommerce' ), implode( ', ', array_keys( wc_get_attribute_types() ) ) ), 400 );
		}

		// Validate the attribute order by
		if ( ! in_array( wc_clean( $order_by ), array( 'menu_order', 'name', 'name_num', 'id' ) ) ) {
			throw new WC_API_Exception( 'woocommerce_api_invalid_product_attribute_order_by', sprintf( __( 'Invalid product attribute order_by type - the product attribute order_by type must be any of these: %s', 'woocommerce' ), implode( ', ', array( 'menu_order', 'name', 'name_num', 'id' ) ) ), 400 );
		}

		return true;
	}

	/**
	 * Create a new product attribute
	 *
	 * @since 2.4.0
	 * @param array $data posted data
	 * @return array
	 */
	public function create_product_attribute( $data ) {
		global $wpdb;

		try {
			if ( ! isset( $data['product_attribute'] ) ) {
				throw new WC_API_Exception( 'woocommerce_api_missing_product_attribute_data', sprintf( __( 'No %1$s data specified to create %1$s', 'woocommerce' ), 'product_attribute' ), 400 );
			}

			$data = $data['product_attribute'];

			// Check permissions
			if ( ! current_user_can( 'manage_product_terms' ) ) {
				throw new WC_API_Exception( 'woocommerce_api_user_cannot_create_product_attribute', __( 'You do not have permission to create product attributes', 'woocommerce' ), 401 );
			}

			$data = apply_filters( 'woocommerce_api_create_product_attribute_data', $data, $this );

			if ( ! isset( $data['name'] ) ) {
				$data['name'] = '';
			}

			// Set the attribute slug
			if ( ! isset( $data['slug'] ) ) {
				$data['slug'] = wc_sanitize_taxonomy_name( stripslashes( $data['name'] ) );
			} else {
				$data['slug'] = preg_replace( '/^pa\_/', '', wc_sanitize_taxonomy_name( stripslashes( $data['slug'] ) ) );
			}

			// Set attribute type when not sent
			if ( ! isset( $data['type'] ) ) {
				$data['type'] = 'select';
			}

			// Set order by when not sent
			if ( ! isset( $data['order_by'] ) ) {
				$data['order_by'] = 'menu_order';
			}

			// Validate the attribute data
			$this->validate_attribute_data( $data['name'], $data['slug'], $data['type'], $data['order_by'], true );

			$insert = $wpdb->insert(
				$wpdb->prefix . 'woocommerce_attribute_taxonomies',
				array(
					'attribute_label'   => $data['name'],
					'attribute_name'    => $data['slug'],
					'attribute_type'    => $data['type'],
					'attribute_orderby' => $data['order_by'],
					'attribute_public'  => isset( $data['has_archives'] ) && true === $data['has_archives'] ? 1 : 0
				),
				array( '%s', '%s', '%s', '%s', '%d' )
			);

			// Checks for an error in the product creation
			if ( is_wp_error( $insert ) ) {
				throw new WC_API_Exception( 'woocommerce_api_cannot_create_product_attribute', $insert->get_error_message(), 400 );
			}

			$id = $wpdb->insert_id;

			do_action( 'woocommerce_api_create_product_attribute', $id, $data );

			// Clear transients
			delete_transient( 'wc_attribute_taxonomies' );

			$this->server->send_status( 201 );

			return $this->get_product_attribute( $id );
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Edit a product attribute
	 *
	 * @since 2.4.0
	 * @param int $id the attribute ID
	 * @param array $data
	 * @return array
	 */
	public function edit_product_attribute( $id, $data ) {
		global $wpdb;

		try {
			if ( ! isset( $data['product_attribute'] ) ) {
				throw new WC_API_Exception( 'woocommerce_api_missing_product_attribute_data', sprintf( __( 'No %1$s data specified to edit %1$s', 'woocommerce' ), 'product_attribute' ), 400 );
			}

			$id   = absint( $id );
			$data = $data['product_attribute'];

			// Check permissions
			if ( ! current_user_can( 'manage_product_terms' ) ) {
				throw new WC_API_Exception( 'woocommerce_api_user_cannot_edit_product_attribute', __( 'You do not have permission to edit product attributes', 'woocommerce' ), 401 );
			}

			$data      = apply_filters( 'woocommerce_api_edit_product_attribute_data', $data, $this );
			$attribute = $this->get_product_attribute( $id );

			if ( is_wp_error( $attribute ) ) {
				return $attribute;
			}

			$attribute_name     = isset( $data['name'] ) ? $data['name'] : $attribute['product_attribute']['name'];
			$attribute_type     = isset( $data['type'] ) ? $data['type'] : $attribute['product_attribute']['type'];
			$attribute_order_by = isset( $data['order_by'] ) ? $data['order_by'] : $attribute['product_attribute']['order_by'];

			if ( isset( $data['slug'] ) ) {
				$attribute_slug = wc_sanitize_taxonomy_name( stripslashes( $data['slug'] ) );
			} else {
				$attribute_slug = $attribute['product_attribute']['slug'];
			}
			$attribute_slug = preg_replace( '/^pa\_/', '', $attribute_slug );

			if ( isset( $data['has_archives'] ) ) {
				$attribute_public = true === $data['has_archives'] ? 1 : 0;
			} else {
				$attribute_public = $attribute['product_attribute']['has_archives'];
			}

			// Validate the attribute data
			$this->validate_attribute_data( $attribute_name, $attribute_slug, $attribute_type, $attribute_order_by, false );

			$update = $wpdb->update(
				$wpdb->prefix . 'woocommerce_attribute_taxonomies',
				array(
					'attribute_label'   => $attribute_name,
					'attribute_name'    => $attribute_slug,
					'attribute_type'    => $attribute_type,
					'attribute_orderby' => $attribute_order_by,
					'attribute_public'  => $attribute_public
				),
				array( 'attribute_id' => $id ),
				array( '%s', '%s', '%s', '%s', '%d' ),
				array( '%d' )
			);

			// Checks for an error in the product creation
			if ( false === $update ) {
				throw new WC_API_Exception( 'woocommerce_api_cannot_edit_product_attribute', __( 'Could not edit the attribute', 'woocommerce' ), 400 );
			}

			do_action( 'woocommerce_api_edit_product_attribute', $id, $data );

			// Clear transients
			delete_transient( 'wc_attribute_taxonomies' );

			return $this->get_product_attribute( $id );
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Delete a product attribute
	 *
	 * @since  2.4.0
	 * @param  int $id the product attribute ID
	 * @return array
	 */
	public function delete_product_attribute( $id ) {
		global $wpdb;

		try {
			// Check permissions
			if ( ! current_user_can( 'manage_product_terms' ) ) {
				throw new WC_API_Exception( 'woocommerce_api_user_cannot_delete_product_attribute', __( 'You do not have permission to delete product attributes', 'woocommerce' ), 401 );
			}

			$id = absint( $id );

			$attribute_name = $wpdb->get_var( $wpdb->prepare( "
				SELECT attribute_name
				FROM {$wpdb->prefix}woocommerce_attribute_taxonomies
				WHERE attribute_id = %d
			 ", $id ) );

			if ( is_null( $attribute_name ) ) {
				throw new WC_API_Exception( 'woocommerce_api_invalid_product_attribute_id', __( 'A product attribute with the provided ID could not be found', 'woocommerce' ), 404 );
			}

			$deleted = $wpdb->delete(
				$wpdb->prefix . 'woocommerce_attribute_taxonomies',
				array( 'attribute_id' => $id ),
				array( '%d' )
			);

			if ( false === $deleted ) {
				throw new WC_API_Exception( 'woocommerce_api_cannot_delete_product_attribute', __( 'Could not delete the attribute', 'woocommerce' ), 401 );
			}

			$taxonomy = wc_attribute_taxonomy_name( $attribute_name );

			if ( taxonomy_exists( $taxonomy ) ) {
				$terms = get_terms( $taxonomy, 'orderby=name&hide_empty=0' );
				foreach ( $terms as $term ) {
					wp_delete_term( $term->term_id, $taxonomy );
				}
			}

			do_action( 'woocommerce_attribute_deleted', $id, $attribute_name, $taxonomy );
			do_action( 'woocommerce_api_delete_product_attribute', $id, $this );

			// Clear transients
			delete_transient( 'wc_attribute_taxonomies' );

			return array( 'message' => sprintf( __( 'Deleted %s', 'woocommerce' ), 'product_attribute' ) );
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Get product by SKU
	 *
	 * @deprecated 2.4.0
	 *
	 * @since  2.3.0
	 * @param  int    $sku the product SKU
	 * @param  string $fields
	 * @return array
	 */
	public function get_product_by_sku( $sku, $fields = null ) {
		try {
			$id = wc_get_product_id_by_sku( $sku );

			if ( empty( $id ) ) {
				throw new WC_API_Exception( 'woocommerce_api_invalid_product_sku', __( 'Invalid product SKU', 'woocommerce' ), 404 );
			}

			return $this->get_product( $id, $fields );
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Clear product
	 */
	protected function clear_product( $product_id ) {
		if ( ! is_numeric( $product_id ) || 0 >= $product_id ) {
			return;
		}

		// Delete product attachments
		$attachments = get_children( array(
			'post_parent' => $product_id,
			'post_status' => 'any',
			'post_type'   => 'attachment',
		) );

		foreach ( (array) $attachments as $attachment ) {
			wp_delete_attachment( $attachment->ID, true );
		}

		// Delete product
		wp_delete_post( $product_id, true );
	}

	/**
	 * Bulk update or insert products
	 * Accepts an array with products in the formats supported by
	 * WC_API_Products->create_product() and WC_API_Products->edit_product()
	 *
	 * @since 2.4.0
	 * @param array $data
	 * @return array
	 */
	public function bulk( $data ) {

		try {
			if ( ! isset( $data['products'] ) ) {
				throw new WC_API_Exception( 'woocommerce_api_missing_products_data', sprintf( __( 'No %1$s data specified to create/edit %1$s', 'woocommerce' ), 'products' ), 400 );
			}

			$data  = $data['products'];
			$limit = apply_filters( 'woocommerce_api_bulk_limit', 100, 'products' );

			// Limit bulk operation
			if ( count( $data ) > $limit ) {
				throw new WC_API_Exception( 'woocommerce_api_products_request_entity_too_large', sprintf( __( 'Unable to accept more than %s items for this request', 'woocommerce' ), $limit ), 413 );
			}

			$products = array();

			foreach ( $data as $_product ) {
				$product_id  = 0;
				$product_sku = '';

				// Try to get the product ID
				if ( isset( $_product['id'] ) ) {
					$product_id = intval( $_product['id'] );
				}

				if ( ! $product_id && isset( $_product['sku'] ) ) {
					$product_sku = wc_clean( $_product['sku'] );
					$product_id  = wc_get_product_id_by_sku( $product_sku );
				}

				// Product exists / edit product
				if ( $product_id ) {
					$edit = $this->edit_product( $product_id, array( 'product' => $_product ) );

					if ( is_wp_error( $edit ) ) {
						$products[] = array(
							'id'    => $product_id,
							'sku'   => $product_sku,
							'error' => array( 'code' => $edit->get_error_code(), 'message' => $edit->get_error_message() )
						);
					} else {
						$products[] = $edit['product'];
					}
				}

				// Product don't exists / create product
				else {
					$new = $this->create_product( array( 'product' => $_product ) );

					if ( is_wp_error( $new ) ) {
						$products[] = array(
							'id'    => $product_id,
							'sku'   => $product_sku,
							'error' => array( 'code' => $new->get_error_code(), 'message' => $new->get_error_message() )
						);
					} else {
						$products[] = $new['product'];
					}
				}
			}

			return array( 'products' => apply_filters( 'woocommerce_api_products_bulk_response', $products, $this ) );
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}
}
