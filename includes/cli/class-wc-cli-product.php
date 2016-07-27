<?php

/**
 * Manage Products.
 *
 * @since    2.5.0
 * @package  WooCommerce/CLI
 * @category CLI
 * @author   WooThemes
 */
class WC_CLI_Product extends WC_CLI_Command {

	/**
	 * Create a product.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Associative args for the new product.
	 *
	 * [--porcelain]
	 * : Outputs just the new product id.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * Required fields:
	 *
	 * * title
	 *
	 * These fields are optionally available for create command:
	 *
	 * * type
	 * * status
	 * * downloadable
	 * * virtual
	 * * sku
	 * * regular_price
	 * * sale_price
	 * * sale_price_dates_from
	 * * sale_price_dates_to
	 * * tax_status
	 * * tax_class
	 * * managing_stock
	 * * stock_quantity
	 * * in_stock
	 * * backorders
	 * * sold_individually
	 * * featured
	 * * shipping_class
	 * * description
	 * * enable_html_description
	 * * short_description
	 * * enable_html_short_description
	 * * reviews_allowed
	 * * upsell_ids
	 * * cross_sell_ids
	 * * parent_id
	 * * categories
	 * * tags
	 *
	 * Dimensions fields:
	 *
	 * * dimensions.length
	 * * dimensions.width
	 * * dimensions.height
	 * * dimensions.unit
	 *
	 * Images is an array in which element can be set by specifying its index:
	 *
	 * * images
	 * * images.size
	 * * images.0.id
	 * * images.0.created_at
	 * * images.0.updated_at
	 * * images.0.src
	 * * images.0.title
	 * * images.0.alt
	 * * images.0.position
	 *
	 * Attributes is an array in which element can be set by specifying its index:
	 *
	 * * attributes
	 * * attributes.size
	 * * attributes.0.name
	 * * attributes.0.slug
	 * * attributes.0.position
	 * * attributes.0.visible
	 * * attributes.0.variation
	 * * attributes.0.options
	 *
	 * Downloads is an array in which element can be accessed by specifying its index:
	 *
	 * * downloads
	 * * downloads.size
	 * * downloads.0.id
	 * * downloads.0.name
	 * * downloads.0.file
	 *
	 * Variations is an array in which element can be accessed by specifying its index:
	 *
	 * * variations
	 * * variations.size
	 * * variations.0.id
	 * * variations.0.created_at
	 * * variations.0.updated_at
	 * * variations.0.downloadable
	 * * variations.0.virtual
	 * * variations.0.permalink
	 * * variations.0.sku
	 * * variations.0.price
	 * * variations.0.regular_price
	 * * variations.0.sale_price
	 * * variations.0.sale_price_dates_from
	 * * variations.0.sale_price_dates_to
	 * * variations.0.taxable
	 * * variations.0.tax_status
	 * * variations.0.tax_class
	 * * variations.0.managing_stock
	 * * variations.0.stock_quantity
	 * * variations.0.in_stock
	 * * variations.0.backordered
	 * * variations.0.purchaseable
	 * * variations.0.visible
	 * * variations.0.on_sale
	 * * variations.0.weight
	 * * variations.0.dimensions -- See dimensions fields
	 * * variations.0.shipping_class
	 * * variations.0.shipping_class_id
	 * * variations.0.images -- See images fields
	 * * variations.0.attributes -- See attributes fields
	 * * variations.0.downloads -- See downloads fields
	 * * variations.0.download_limit
	 * * variations.0.download_expiry
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc product create --title="Product Name"
	 *
	 *     wp wc product create --title="Product Name" --
	 *
	 * @since 2.5.0
	 */
	public function create( $args, $assoc_args ) {
		$id = 0;

		try {
			$porcelain = isset( $assoc_args['porcelain'] );
			unset( $assoc_args['porcelain'] );

			$data = apply_filters( 'woocommerce_cli_create_product_data', $this->unflatten_array( $assoc_args ) );

			// Check if product title is specified
			if ( ! isset( $data['title'] ) ) {
				throw new WC_CLI_Exception( 'woocommerce_missing_product_title', sprintf( __( 'Missing parameter %s', 'woocommerce' ), 'title' ) );
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
				throw new WC_CLI_Exception( 'woocommerce_cli_invalid_product_type', sprintf( __( 'Invalid product type - the product type must be any of these: %s', 'woocommerce' ), implode( ', ', array_keys( wc_get_product_types() ) ) ) );
			}

			// Enable description html tags.
			$post_content = isset( $data['description'] ) ? wc_clean( $data['description'] ) : '';
			if ( $post_content && isset( $data['enable_html_description'] ) && $this->is_true( $data['enable_html_description'] ) ) {
				$post_content = $data['description'];
			}

			// Enable short description html tags.
			$post_excerpt = isset( $data['short_description'] ) ? wc_clean( $data['short_description'] ) : '';
			if ( $post_excerpt && isset( $data['enable_html_short_description'] ) && $this->is_true( $data['enable_html_short_description'] ) ) {
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
				throw new WC_CLI_Exception( 'woocommerce_cli_cannot_create_product', $id->get_error_message() );
			}

			// Check for featured/gallery images, upload it and set it
			if ( isset( $data['images'] ) ) {
				$this->save_product_images( $id, $data['images'] );
			}

			// Save product meta fields
			$this->save_product_meta( $id, $data );

			// Save variations
			if ( $this->is_variable( $data ) ) {
				$this->save_variations( $id, $data );
			}

			do_action( 'woocommerce_cli_create_product', $id, $data );

			// Clear cache/transients
			wc_delete_product_transients( $id );

			if ( $porcelain ) {
				WP_CLI::line( $id );
			} else {
				WP_CLI::success( "Created product $id." );
			}
		} catch ( WC_CLI_Exception $e ) {
			// Remove the product when fails
			$this->clear_product( $id );

			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Delete products.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : The product ID to delete.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc product delete 123
	 *
	 *     wp wc product delete $(wp wc product list --format=ids)
	 *
	 * @since 2.5.0
	 */
	public function delete( $args, $assoc_args ) {
		$exit_code = 0;
		foreach ( $args as $id ) {
			do_action( 'woocommerce_cli_delete_product', $id );
			$r = wp_delete_post( $id, true );

			if ( $r ) {
				WP_CLI::success( "Deleted product $id." );
			} else {
				$exit_code += 1;
				WP_CLI::warning( "Failed deleting product $id." );
			}
		}
		exit( $exit_code ? 1 : 0 );
	}

	/**
	 * Get a product.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Product ID.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole product fields, returns the value of a single fields.
	 *
	 * [--fields=<fields>]
	 * : Get a specific subset of the product's fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * For more fields, see: wp wc product list --help
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc product get 123 --fields=id,title,sku
	 *
	 * @since 2.5.0
	 */
	public function get( $args, $assoc_args ) {
		try {
			$product = wc_get_product( $args[0] );
			if ( ! $product ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_invalid_product', sprintf( __( 'Invalid product "%s"', 'woocommerce' ), $args[0] ) );
			}
			$product_data = $this->get_product_data( $product );

			$formatter = $this->get_formatter( $assoc_args );
			$formatter->display_item( $product_data );
		} catch ( WC_CLI_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * List products.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Filter products based on product property.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each product.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific product fields.
	 *
	 * [--format=<format>]
	 * : Acceptec values: table, csv, json, count, ids. Default: table.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each product:
	 *
	 * * id
	 * * title
	 * * sku
	 * * in_stock
	 * * price
	 * * sale_price
	 * * categories
	 * * tags
	 * * type
	 * * created_at
	 *
	 * These fields are optionally available:
	 *
	 * * updated_at
	 * * status
	 * * downloadable
	 * * virtual
	 * * permalink
	 * * regular_price
	 * * sale_price_dates_from
	 * * sale_price_dates_to
	 * * price_html
	 * * taxable
	 * * tax_status
	 * * tax_class
	 * * managing_stock
	 * * stock_quantity
	 * * backorders_allowed
	 * * backordered
	 * * backorders
	 * * sold_individually
	 * * purchaseable
	 * * featured
	 * * visible
	 * * catalog_visibility
	 * * on_sale
	 * * weight
	 * * shipping_required
	 * * shipping_taxable
	 * * shipping_class
	 * * shipping_class_id
	 * * description
	 * * enable_html_description
	 * * short_description
	 * * enable_html_short_description
	 * * reviews_allowed
	 * * average_rating
	 * * rating_count
	 * * related_ids
	 * * upsell_ids
	 * * cross_sell_ids
	 * * parent_id
	 * * featured_src
	 * * download_limit
	 * * download_expiry
	 * * download_type
	 * * purchase_note
	 * * total_sales
	 * * parent
	 * * product_url
	 * * button_text
	 *
	 * There are some properties that are nested array. In such case, if array.size
	 * is zero then listing the fields with `array.0.some_field` will results
	 * in error that field `array.0.some_field` does not exists.
	 *
	 * Dimensions fields:
	 *
	 * * dimensions.length
	 * * dimensions.width
	 * * dimensions.height
	 * * dimensions.unit
	 *
	 * Images is an array in which element can be accessed by specifying its index:
	 *
	 * * images
	 * * images.size
	 * * images.0.id
	 * * images.0.created_at
	 * * images.0.updated_at
	 * * images.0.src
	 * * images.0.title
	 * * images.0.alt
	 * * images.0.position
	 *
	 * Attributes is an array in which element can be accessed by specifying its index:
	 *
	 * * attributes
	 * * attributes.size
	 * * attributes.0.name
	 * * attributes.0.slug
	 * * attributes.0.position
	 * * attributes.0.visible
	 * * attributes.0.variation
	 * * attributes.0.options
	 *
	 * Downloads is an array in which element can be accessed by specifying its index:
	 *
	 * * downloads
	 * * downloads.size
	 * * downloads.0.id
	 * * downloads.0.name
	 * * downloads.0.file
	 *
	 * Variations is an array in which element can be accessed by specifying its index:
	 *
	 * * variations
	 * * variations.size
	 * * variations.0.id
	 * * variations.0.created_at
	 * * variations.0.updated_at
	 * * variations.0.downloadable
	 * * variations.0.virtual
	 * * variations.0.permalink
	 * * variations.0.sku
	 * * variations.0.price
	 * * variations.0.regular_price
	 * * variations.0.sale_price
	 * * variations.0.sale_price_dates_from
	 * * variations.0.sale_price_dates_to
	 * * variations.0.taxable
	 * * variations.0.tax_status
	 * * variations.0.tax_class
	 * * variations.0.managing_stock
	 * * variations.0.stock_quantity
	 * * variations.0.in_stock
	 * * variations.0.backordered
	 * * variations.0.purchaseable
	 * * variations.0.visible
	 * * variations.0.on_sale
	 * * variations.0.weight
	 * * variations.0.dimensions -- See dimensions fields
	 * * variations.0.shipping_class
	 * * variations.0.shipping_class_id
	 * * variations.0.images -- See images fields
	 * * variations.0.attributes -- See attributes fields
	 * * variations.0.downloads -- See downloads fields
	 * * variations.0.download_limit
	 * * variations.0.download_expiry
	 *
	 * Fields for filtering query result also available:
	 *
	 * * q              Filter products with search query.
	 * * created_at_min Filter products whose created after this date.
	 * * created_at_max Filter products whose created before this date.
	 * * updated_at_min Filter products whose updated after this date.
	 * * updated_at_max Filter products whose updated before this date.
	 * * limit          The maximum returned number of results.
	 * * offset         Offset the returned results.
	 * * order          Accepted values: ASC and DESC. Default: DESC.
	 * * orderby        Sort retrieved products by parameter. One or more options can be passed.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc product list
	 *
	 *     wp wc product list --field=id
	 *
	 *     wp wc product list --fields=id,title,type --format=json
	 *
	 * @subcommand list
	 * @since      2.5.0
	 */
	public function list_( $args, $assoc_args ) {
		$query_args = $this->merge_wp_query_args( $this->get_list_query_args( $assoc_args ), $assoc_args );
		$formatter  = $this->get_formatter( $assoc_args );

		if ( 'ids' === $formatter->format ) {
			$query_args['fields'] = 'ids';
			$query = new WP_Query( $query_args );
			echo implode( ' ', $query->posts );
		} else {
			$query = new WP_Query( $query_args );
			$items = $this->format_posts_to_items( $query->posts );
			$formatter->display_items( $items );
		}
	}

	/**
	 * List of product reviews.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Product ID.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole review fields, returns the value of a single fields.
	 *
	 * [--fields=<fields>]
	 * : Get a specific subset of the review's fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * * id
	 * * rating
	 * * reviewer_name
	 * * reviewer_email
	 * * verified
	 * * created_at
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc product reviews 123
	 *
	 *     wp wc product reviews 123 --fields=id,rating,reviewer_email
	 *
	 * @since 2.5.0
	 */
	public function reviews( $args, $assoc_args ) {
		try {
			$id      = $args[0];
			$product = wc_get_product( $id );
			if ( ! $product ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_invalid_product', sprintf( __( 'Invalid product "%s"', 'woocommerce' ), $id ) );
			}

			$comments = get_approved_comments( $id );
			$reviews  = array();

			foreach ( $comments as $comment ) {
				$reviews[] = array(
					'id'             => intval( $comment->comment_ID ),
					'created_at'     => $this->format_datetime( $comment->comment_date_gmt ),
					'review'         => $comment->comment_content,
					'rating'         => get_comment_meta( $comment->comment_ID, 'rating', true ),
					'reviewer_name'  => $comment->comment_author,
					'reviewer_email' => $comment->comment_author_email,
					'verified'       => (bool) get_comment_meta( $comment->comment_ID, 'verified', true ),
				);
			}

			if ( empty( $assoc_args['fields'] ) ) {
				$assoc_args['fields'] = $this->get_review_fields();
			}

			$formatter = $this->get_formatter( $assoc_args );
			$formatter->display_items( $reviews );
		} catch ( WC_CLI_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Get product types.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc product types
	 *
	 * @since 2.5.0
	 */
	public function types( $__, $___ ) {
		$product_types = wc_get_product_types();
		foreach ( $product_types as $type => $label ) {
			WP_CLI::line( sprintf( '%s: %s', $label, $type ) );
		}
	}

	/**
	 * Update one or more products.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Product ID
	 *
	 * [--<field>=<value>]
	 * : One or more fields to update.
	 *
	 * ## AVAILABLE_FIELDS
	 *
	 * For more fields, see: wp wc product create --help
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc product update 123 --title="New Product Title" --description="New description"
	 *
	 * @since 2.5.0
	 */
	public function update( $args, $assoc_args ) {
		try {
			$id   = $args[0];
			$data = apply_filters( 'woocommerce_cli_update_product_data', $this->unflatten_array( $assoc_args ) );

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
				$post_excerpt = ( isset( $data['enable_html_short_description'] ) && $this->is_true( $data['enable_html_short_description'] ) ) ? $data['short_description'] : wc_clean( $data['short_description'] );

				wp_update_post( array( 'ID' => $id, 'post_excerpt' => $post_excerpt ) );
			}

			// Product description.
			if ( isset( $data['description'] ) ) {
				// Enable description html tags.
				$post_content = ( isset( $data['enable_html_description'] ) && $this->is_true( $data['enable_html_description'] ) ) ? $data['description'] : wc_clean( $data['description'] );

				wp_update_post( array( 'ID' => $id, 'post_content' => $post_content ) );
			}

			// Validate the product type
			if ( isset( $data['type'] ) && ! in_array( wc_clean( $data['type'] ), array_keys( wc_get_product_types() ) ) ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_invalid_product_type', sprintf( __( 'Invalid product type - the product type must be any of these: %s', 'woocommerce' ), implode( ', ', array_keys( wc_get_product_types() ) ) ) );
			}

			// Check for featured/gallery images, upload it and set it
			if ( isset( $data['images'] ) ) {
				$this->save_product_images( $id, $data['images'] );
			}

			// Save product meta fields
			$this->save_product_meta( $id, $data );

			// Save variations
			if ( $this->is_variable( $data ) ) {
				$this->save_variations( $id, $data );
			}

			do_action( 'woocommerce_cli_update_product', $id, $data );

			// Clear cache/transients
			wc_delete_product_transients( $id );

			WP_CLI::success( "Updated product $id." );
		} catch ( WC_CLI_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Get query args for list subcommand.
	 *
	 * @since  2.5.0
	 * @param  array $args Args from command line
	 * @return array
	 */
	protected function get_list_query_args( $args ) {
		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(),
		);

		if ( ! empty( $args['type'] ) ) {
			$types                   = explode( ',', $args['type'] );
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => $types,
				),
			);
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
		}

		return $query_args;
	}

	/**
	 * Get default format fields that will be used in `list` and `get` subcommands.
	 *
	 * @since  2.5.0
	 * @return string
	 */
	protected function get_default_format_fields() {
		return 'id,title,sku,in_stock,price,sale_price,categories,tags,type,created_at';
	}

	/**
	 * Get review fields for formatting.
	 *
	 * @since  2.5.0
	 * @return string
	 */
	protected function get_review_fields() {
		return 'id,rating,reviewer_name,reviewer_email,verified,created_at';
	}

	/**
	 * Format posts from WP_Query result to items in which each item contain
	 * common properties of item, for instance `post_title` will be `title`.
	 *
	 * @since  2.5.0
	 * @param  array $posts Array of post
	 * @return array Items
	 */
	protected function format_posts_to_items( $posts ) {
		$items = array();
		foreach ( $posts as $post ) {
			$product = wc_get_product( $post->ID );
			if ( ! $product ) {
				continue;
			}
			$items[] = $this->get_product_data( $product );
		}

		return $items;
	}

	/**
	 * Get standard product data that applies to every product type.
	 *
	 * @since  2.5.0
	 * @param  WC_Product $product
	 * @return array
	 */
	private function get_product_data( $product ) {

		// Add data that applies to every product type.
		$product_data = array(
			'title'              => $product->get_title(),
			'id'                 => (int) $product->is_type( 'variation' ) ? $product->get_variation_id() : $product->id,
			'created_at'         => $this->format_datetime( $product->get_post_data()->post_date_gmt ),
			'updated_at'         => $this->format_datetime( $product->get_post_data()->post_modified_gmt ),
			'type'               => $product->product_type,
			'status'             => $product->get_post_data()->post_status,
			'downloadable'       => $product->is_downloadable(),
			'virtual'            => $product->is_virtual(),
			'permalink'          => $product->get_permalink(),
			'sku'                => $product->get_sku(),
			'price'              => $product->get_price(),
			'regular_price'      => $product->get_regular_price(),
			'sale_price'         => $product->get_sale_price() ? $product->get_sale_price() : null,
			'price_html'         => $product->get_price_html(),
			'taxable'            => $product->is_taxable(),
			'tax_status'         => $product->get_tax_status(),
			'tax_class'          => $product->get_tax_class(),
			'managing_stock'     => $product->managing_stock(),
			'stock_quantity'     => $product->get_stock_quantity(),
			'in_stock'           => $product->is_in_stock() ? 'yes' : 'no',
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
			'weight'             => $product->get_weight() ? $product->get_weight() : null,
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
			'related_ids'        => implode( ', ', $product->get_related() ),
			'upsell_ids'         => implode( ', ', $product->get_upsells() ),
			'cross_sell_ids'     => implode( ', ', $product->get_cross_sells() ),
			'parent_id'          => $product->post->post_parent,
			'categories'         => implode( ', ', wp_get_post_terms( $product->id, 'product_cat', array( 'fields' => 'names' ) ) ),
			'tags'               => implode( ', ', wp_get_post_terms( $product->id, 'product_tag', array( 'fields' => 'names' ) ) ),
			'images'             => $this->get_images( $product ),
			'featured_src'       => wp_get_attachment_url( get_post_thumbnail_id( $product->is_type( 'variation' ) ? $product->variation_id : $product->id ) ),
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

		// add variations to variable products
		if ( $product->is_type( 'variable' ) && $product->has_child() ) {
			$product_data['variations'] = $this->get_variation_data( $product );
		}

		// add the parent product data to an individual variation
		if ( $product->is_type( 'variation' ) ) {
			$product_data['parent'] = $this->get_product_data( $product->parent );
		}

		return $this->flatten_array( $product_data );
	}

	/**
	 * Get the images for a product or product variation
	 *
	 * @since  2.5.0
	 * @param  WC_Product|WC_Product_Variation $product
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
				'created_at' => $this->format_datetime( $attachment_post->post_date_gmt ),
				'updated_at' => $this->format_datetime( $attachment_post->post_modified_gmt ),
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
				'created_at' => $this->format_datetime( time() ), // Default to now
				'updated_at' => $this->format_datetime( time() ),
				'src'        => wc_placeholder_img_src(),
				'title'      => __( 'Placeholder', 'woocommerce' ),
				'alt'        => __( 'Placeholder', 'woocommerce' ),
				'position'   => 0,
			);
		}

		return $images;
	}

	/**
	 * Get the attributes for a product or product variation
	 *
	 * @since  2.5.0
	 * @param  WC_Product|WC_Product_Variation $product
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

				// taxonomy-based attributes are comma-separated, others are pipe (|) separated
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
	 * Get the downloads for a product or product variation
	 *
	 * @since  2.5.0
	 * @param  WC_Product|WC_Product_Variation $product
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
	 * Get an individual variation's data
	 *
	 * @since  2.5.0
	 * @param  WC_Product $product
	 * @return array
	 */
	private function get_variation_data( $product ) {
		$variations = array();

		foreach ( $product->get_children() as $child_id ) {

			$variation = $product->get_child( $child_id );

			if ( ! $variation->exists() ) {
				continue;
			}

			$variations[] = array(
					'id'                => $variation->get_variation_id(),
					'created_at'        => $this->format_datetime( $variation->get_post_data()->post_date_gmt ),
					'updated_at'        => $this->format_datetime( $variation->get_post_data()->post_modified_gmt ),
					'downloadable'      => $variation->is_downloadable(),
					'virtual'           => $variation->is_virtual(),
					'permalink'         => $variation->get_permalink(),
					'sku'               => $variation->get_sku(),
					'price'             => $variation->get_price(),
					'regular_price'     => $variation->get_regular_price(),
					'sale_price'        => $variation->get_sale_price() ? $variation->get_sale_price() : null,
					'taxable'           => $variation->is_taxable(),
					'tax_status'        => $variation->get_tax_status(),
					'tax_class'         => $variation->get_tax_class(),
					'managing_stock'    => $variation->managing_stock(),
					'stock_quantity'    => $variation->get_stock_quantity(),
					'in_stock'          => $variation->is_in_stock(),
					'backordered'       => $variation->is_on_backorder(),
					'purchaseable'      => $variation->is_purchasable(),
					'visible'           => $variation->variation_is_visible(),
					'on_sale'           => $variation->is_on_sale(),
					'weight'            => $variation->get_weight() ? $variation->get_weight() : null,
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
	 * @since  2.5.0
	 * @param  int $product_id
	 * @param  array $data
	 * @return bool
	 * @throws WC_CLI_Exception
	 */
	private function save_product_meta( $product_id, $data ) {
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

		// Virtual
		if ( isset( $data['virtual'] ) ) {
			update_post_meta( $product_id, '_virtual', ( $this->is_true( $data['virtual'] ) ) ? 'yes' : 'no' );
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
			update_post_meta( $product_id, '_featured', ( $this->is_true( $data['featured'] ) ) ? 'yes' : 'no' );
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
						throw new WC_CLI_Exception( 'woocommerce_cli_product_sku_already_exists', __( 'The SKU already exists on another product', 'woocommerce' ) );
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
				$is_taxonomy    = 0;
				$taxonomy       = 0;

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
							'position'     => isset( $attribute['position'] ) ? absint( $attribute['position'] ) : 0,
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
						'position'     => isset( $attribute['position'] ) ? absint( $attribute['position'] ) : 0,
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
				$regular_price = ( '' === $data['regular_price'] ) ? '' : wc_format_decimal( $data['regular_price'] );
				update_post_meta( $product_id, '_regular_price', $regular_price );
			} else {
				$regular_price = get_post_meta( $product_id, '_regular_price', true );
			}

			// Sale Price
			if ( isset( $data['sale_price'] ) ) {
				$sale_price = ( '' === $data['sale_price'] ) ? '' : wc_format_decimal( $data['sale_price'] );
				update_post_meta( $product_id, '_sale_price', $sale_price );
			} else {
				$sale_price = get_post_meta( $product_id, '_sale_price', true );
			}

			$date_from = isset( $data['sale_price_dates_from'] ) ? strtotime( $data['sale_price_dates_from'] ) : get_post_meta( $product_id, '_sale_price_dates_from', true );
			$date_to   = isset( $data['sale_price_dates_to'] ) ? strtotime( $data['sale_price_dates_to'] ) : get_post_meta( $product_id, '_sale_price_dates_to', true );

			// Dates
			if ( $date_from ) {
				update_post_meta( $product_id, '_sale_price_dates_from', $date_from );
			} else {
				update_post_meta( $product_id, '_sale_price_dates_from', '' );
			}

			if ( $date_to ) {
				update_post_meta( $product_id, '_sale_price_dates_to', $date_to );
			} else {
				update_post_meta( $product_id, '_sale_price_dates_to', '' );
			}

			if ( $date_to && ! $date_from ) {
				$date_from = strtotime( 'NOW', current_time( 'timestamp' ) );
				update_post_meta( $product_id, '_sale_price_dates_from', $date_from );
			}

			// Update price if on sale
			if ( '' !== $sale_price && '' == $date_to && '' == $date_from ) {
				update_post_meta( $product_id, '_price', wc_format_decimal( $sale_price ) );
			} else {
				update_post_meta( $product_id, '_price', $regular_price );
			}

			if ( '' !== $sale_price && $date_from && $date_from <= strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
				update_post_meta( $product_id, '_price', wc_format_decimal( $sale_price ) );
			}

			if ( $date_to && $date_to < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
				update_post_meta( $product_id, '_price', $regular_price );
				update_post_meta( $product_id, '_sale_price_dates_from', '' );
				update_post_meta( $product_id, '_sale_price_dates_to', '' );
			}
		}

		// Product parent ID for groups
		if ( isset( $data['parent_id'] ) ) {
			wp_update_post( array( 'ID' => $product_id, 'post_parent' => absint( $data['parent_id'] ) ) );
		}

		// Update parent if grouped so price sorting works and stays in sync with the cheapest child
		$_product = wc_get_product( $product_id );
		if ( $_product && $_product->post->post_parent > 0 || $product_type == 'grouped' ) {

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
			update_post_meta( $product_id, '_sold_individually', ( $this->is_true( $data['sold_individually'] ) ) ? 'yes' : '' );
		}

		// Stock status
		if ( isset( $data['in_stock'] ) ) {
			$stock_status = ( $this->is_true( $data['in_stock'] ) ) ? 'instock' : 'outofstock';
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
				$managing_stock = ( $this->is_true( $data['managing_stock'] ) ) ? 'yes' : 'no';
				update_post_meta( $product_id, '_manage_stock', $managing_stock );
			} else {
				$managing_stock = get_post_meta( $product_id, '_manage_stock', true );
			}

			// Backorders
			if ( isset( $data['backorders'] ) ) {
				if ( 'notify' == $data['backorders'] ) {
					$backorders = 'notify';
				} else {
					$backorders = ( $this->is_true( $data['backorders'] ) ) ? 'yes' : 'no';
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

				wc_update_product_stock_status( $product_id, $stock_status );

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

		} else {
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
		if ( isset( $data['categories'] ) ) {
			$term_ids = array_unique( array_map( 'intval', explode( ',', $data['categories'] ) ) );
			wp_set_object_terms( $product_id, $term_ids, 'product_cat' );
		}

		// Product tags
		if ( isset( $data['tags'] ) ) {
			$term_ids = array_unique( array_map( 'intval', explode( ',', $data['tags'] ) ) );
			wp_set_object_terms( $product_id, $term_ids, 'product_tag' );
		}

		// Downloadable
		if ( isset( $data['downloadable'] ) ) {
			$is_downloadable = ( $this->is_true( $data['downloadable'] ) ) ? 'yes' : 'no';
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
			$reviews_allowed = ( $this->is_true( $data['reviews_allowed'] ) ) ? 'open' : 'closed';

			$wpdb->update( $wpdb->posts, array( 'comment_status' => $reviews_allowed ), array( 'ID' => $product_id ) );
		}

		// Do action for product type
		do_action( 'woocommerce_cli_process_product_meta_' . $product_type, $product_id, $data );

		return true;
	}

	/**
	 * Save variations.
	 *
	 * @since  2.5.0
	 * @param  int $id
	 * @param  array $data
	 * @return bool
	 * @throws WC_CLI_Exception
	 */
	private function save_variations( $id, $data ) {
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
				throw new WC_CLI_Exception( 'woocommerce_cli_cannot_save_product_variation', $variation_id->get_error_message() );
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
							throw new WC_CLI_Exception( 'woocommerce_cli_product_sku_already_exists', __( 'The SKU already exists on another product', 'woocommerce' ) );
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
							throw new WC_CLI_Exception( 'woocommerce_cli_cannot_upload_product_image', $upload->get_error_message() );
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
				$is_virtual = ( $this->is_true( $variation['virtual'] ) ) ? 'yes' : 'no';
				update_post_meta( $variation_id, '_virtual', $is_virtual );
			}

			// Downloadable variation
			if ( isset( $variation['downloadable'] ) ) {
				$is_downloadable = ( $this->is_true( $variation['downloadable'] ) ) ? 'yes' : 'no';
				update_post_meta( $variation_id, '_downloadable', $is_downloadable );
			} else {
				$is_downloadable = get_post_meta( $variation_id, '_downloadable', true );
			}

			// Shipping data
			$this->save_product_shipping_data( $variation_id, $variation );

			// Stock handling
			if ( isset( $variation['managing_stock'] ) ) {
				$managing_stock = ( $this->is_true( $variation['managing_stock'] ) ) ? 'yes' : 'no';
				update_post_meta( $variation_id, '_manage_stock', $managing_stock );
			} else {
				$managing_stock = get_post_meta( $variation_id, '_manage_stock', true );
			}

			// Only update stock status to user setting if changed by the user, but do so before looking at stock levels at variation level
			if ( isset( $variation['in_stock'] ) ) {
				$stock_status = ( $this->is_true( $variation['in_stock'] ) ) ? 'instock' : 'outofstock';
				wc_update_product_stock_status( $variation_id, $stock_status );
			}

			if ( 'yes' === $managing_stock ) {
				if ( isset( $variation['backorders'] ) ) {
					if ( 'notify' === $variation['backorders'] ) {
						$backorders = 'notify';
					} else {
						$backorders = ( $this->is_true( $variation['backorders'] ) ) ? 'yes' : 'no';
					}
				} else {
					$backorders = 'no';
				}

				update_post_meta( $variation_id, '_backorders', $backorders );

				if ( isset( $variation['stock_quantity'] ) ) {
					wc_update_product_stock( $variation_id, wc_stock_amount( $variation['stock_quantity'] ) );
				}
			} else {
				delete_post_meta( $variation_id, '_backorders' );
				delete_post_meta( $variation_id, '_stock' );
			}

			// Regular Price
			if ( isset( $variation['regular_price'] ) ) {
				$regular_price = ( '' === $variation['regular_price'] ) ? '' : wc_format_decimal( $variation['regular_price'] );
				update_post_meta( $variation_id, '_regular_price', $regular_price );
			} else {
				$regular_price = get_post_meta( $variation_id, '_regular_price', true );
			}

			// Sale Price
			if ( isset( $variation['sale_price'] ) ) {
				$sale_price = ( '' === $variation['sale_price'] ) ? '' : wc_format_decimal( $variation['sale_price'] );
				update_post_meta( $variation_id, '_sale_price', $sale_price );
			} else {
				$sale_price = get_post_meta( $variation_id, '_sale_price', true );
			}

			$date_from = isset( $variation['sale_price_dates_from'] ) ? strtotime( $variation['sale_price_dates_from'] ) : get_post_meta( $variation_id, '_sale_price_dates_from', true );
			$date_to   = isset( $variation['sale_price_dates_to'] ) ? strtotime( $variation['sale_price_dates_to'] ) : get_post_meta( $variation_id, '_sale_price_dates_to', true );

			// Save Dates
			if ( $date_from ) {
				update_post_meta( $variation_id, '_sale_price_dates_from', $date_from );
			} else {
				update_post_meta( $variation_id, '_sale_price_dates_from', '' );
			}

			if ( $date_to ) {
				update_post_meta( $variation_id, '_sale_price_dates_to', $date_to );
			} else {
				update_post_meta( $variation_id, '_sale_price_dates_to', '' );
			}

			if ( $date_to && ! $date_from ) {
				update_post_meta( $variation_id, '_sale_price_dates_from', strtotime( 'NOW', current_time( 'timestamp' ) ) );
			}

			// Update price if on sale
			if ( '' != $sale_price && '' == $date_to && '' == $date_from ) {
				update_post_meta( $variation_id, '_price', $sale_price );
			} else {
				update_post_meta( $variation_id, '_price', $regular_price );
			}

			if ( '' != $sale_price && $date_from && $date_from < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
				update_post_meta( $variation_id, '_price', $sale_price );
			}

			if ( $date_to && $date_to < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
				update_post_meta( $variation_id, '_price', $regular_price );
				update_post_meta( $variation_id, '_sale_price_dates_from', '' );
				update_post_meta( $variation_id, '_sale_price_dates_to', '' );
			}

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

				foreach ( $variation['attributes'] as $slug => $value ) {
					$taxonomy   = sanitize_title( $slug );
					$_attribute = array();

					if ( $this->get_attribute_taxonomy_by_slug( $slug ) !== null ) {
						$taxonomy = $this->get_attribute_taxonomy_by_slug( $slug );
					}

					if ( isset( $attributes[ $taxonomy ] ) ) {
						$_attribute = $attributes[ $taxonomy ];
					}

					if ( isset( $_attribute['is_variation'] ) && $_attribute['is_variation'] ) {
						$attribute_key   = 'attribute_' . sanitize_title( $_attribute['name'] );
						$attribute_value = ! empty( $value ) ? sanitize_title( stripslashes( $value ) ) : '';
						$updated_attribute_keys[] = $attribute_key;

						update_post_meta( $variation_id, $attribute_key, $attribute_value );
					}
				}

				// Remove old taxonomies attributes so data is kept up to date - first get attribute key names
				$delete_attribute_keys = $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE 'attribute_%%' AND meta_key NOT IN ( '" . implode( "','", $updated_attribute_keys ) . "' ) AND post_id = %d;", $variation_id ) );

				foreach ( $delete_attribute_keys as $key ) {
					delete_post_meta( $variation_id, $key );
				}
			}

			do_action( 'woocommerce_cli_save_product_variation', $variation_id, $menu_order, $variation );
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
						// Don't use wc_clean as it destroys sanitized characters
						if ( isset( $default_attr['option'] ) ) {
							$value = sanitize_title( trim( stripslashes( $default_attr['option'] ) ) );
						} else {
							$value = '';
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
	 * Save product images.
	 *
	 * @since  2.5.0
	 * @param  array $images
	 * @param  int $id
	 * @throws WC_CLI_Exception
	 */
	private function save_product_images( $id, $images ) {
		if ( is_array( $images ) ) {
			$gallery = array();

			foreach ( $images as $image ) {
				if ( isset( $image['position'] ) && $image['position'] == 0 ) {
					$attachment_id = isset( $image['id'] ) ? absint( $image['id'] ) : 0;

					if ( 0 === $attachment_id && isset( $image['src'] ) ) {
						$upload = $this->upload_product_image( esc_url_raw( $image['src'] ) );

						if ( is_wp_error( $upload ) ) {
							throw new WC_CLI_Exception( 'woocommerce_cli_cannot_upload_product_image', $upload->get_error_message() );
						}

						$attachment_id = $this->set_product_image_as_attachment( $upload, $id );
					}

					set_post_thumbnail( $id, $attachment_id );
				} else {
					$attachment_id = isset( $image['id'] ) ? absint( $image['id'] ) : 0;

					if ( 0 === $attachment_id && isset( $image['src'] ) ) {
						$upload = $this->upload_product_image( esc_url_raw( $image['src'] ) );

						if ( is_wp_error( $upload ) ) {
							throw new WC_CLI_Exception( 'woocommerce_cli_cannot_upload_product_image', $upload->get_error_message() );
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
	 * Save product shipping data.
	 *
	 * @since 2.5.0
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
			$virtual = ( $this->is_true( $data['virtual'] ) ) ? 'yes' : 'no';

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
	 * Save downloadable files.
	 *
	 * @since 2.5.0
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
	 * @since  2.5.0
	 * @param  string $slug
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
	 * Returns image mime types users are allowed to upload via the API.
	 * @since  2.6.4
	 * @return array
	 */
	private function allowed_image_mime_types() {
		return apply_filters( 'woocommerce_cli_allowed_image_mime_types', array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png',
			'bmp'          => 'image/bmp',
			'tiff|tif'     => 'image/tiff',
			'ico'          => 'image/x-icon',
		) );
	}

	/**
	 * Upload image from URL
	 *
	 * @since  2.5.0
	 * @param  string $image_url
	 * @return int|WP_Error attachment id
	 * @throws WC_CLI_Exception
	 */
	private function upload_product_image( $image_url ) {
		$file_name 		= basename( current( explode( '?', $image_url ) ) );
		$parsed_url 	= @parse_url( $image_url );

		// Check parsed URL
		if ( ! $parsed_url || ! is_array( $parsed_url ) ) {
			throw new WC_CLI_Exception( 'woocommerce_cli_invalid_product_image', sprintf( __( 'Invalid URL %s', 'woocommerce' ), $image_url ) );
		}

		// Ensure url is valid
		$image_url = str_replace( ' ', '%20', $image_url );

		// Get the file
		$response = wp_safe_remote_get( $image_url, array(
			'timeout' => 10
		) );

		if ( is_wp_error( $response ) ) {
			throw new WC_CLI_Exception( 'woocommerce_cli_invalid_remote_product_image', sprintf( __( 'Error getting remote image %s.', 'woocommerce' ), $image_url ) . ' ' . sprintf( __( 'Error: %s.', 'woocommerce' ), $response->get_error_message() ) );
		} elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			throw new WC_CLI_Exception( 'woocommerce_cli_invalid_remote_product_image', sprintf( __( 'Error getting remote image %s.', 'woocommerce' ), $image_url ) );
		}

		// Ensure we have a file name and type
		$wp_filetype = wp_check_filetype( $file_name, $this->allowed_image_mime_types() );

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

			// Recheck filetype
			$wp_filetype = wp_check_filetype( $file_name, $this->allowed_image_mime_types() );

			if ( ! $wp_filetype['type'] ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_invalid_image_type', __( 'Invalid image type.', 'woocommerce' ) );
			}
		}

		// Upload the file.
		$upload = wp_upload_bits( $file_name, '', wp_remote_retrieve_body( $response ) );

		if ( $upload['error'] ) {
			throw new WC_CLI_Exception( 'woocommerce_cli_product_image_upload_error', $upload['error'] );
		}

		// Get filesize
		$filesize = filesize( $upload['file'] );

		if ( 0 == $filesize ) {
			@unlink( $upload['file'] );
			unset( $upload );
			throw new WC_CLI_Exception( 'woocommerce_cli_product_image_upload_file_error', __( 'Zero size file downloaded', 'woocommerce' ) );
		}

		unset( $response );

		return $upload;
	}

	/**
	 * Sets product image as attachment and returns the attachment ID.
	 *
	 * @since  2.5.0
	 * @param  array $upload
	 * @param  int $id
	 * @return int
	 */
	private function set_product_image_as_attachment( $upload, $id ) {
		$info    = wp_check_filetype( $upload['file'] );
		$title   = '';
		$content = '';

		if ( $image_meta = @wp_read_image_metadata( $upload['file'] ) ) {
			if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
				$title = wc_clean( $image_meta['title'] );
			}
			if ( trim( $image_meta['caption'] ) ) {
				$content = wc_clean( $image_meta['caption'] );
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
	 * Clear product
	 *
	 * @since 2.5.0
	 * @param int $product_id Product ID
	 */
	private function clear_product( $product_id ) {
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
	 * Check if is a variable.
	 *
	 * @since 2.6.2
	 * @param array $data
	 * @return bool
	 */
	private function is_variable( $data ) {
		if ( isset( $data['type'] ) && isset( $data['variations'] ) && is_array( $data['variations'] ) ) {
			$types = apply_filters( 'woocommerce_cli_get_product_variable_types', array( 'variable' ) );

			return in_array( $data['type'], $types );
		}

		return false;
	}
}
