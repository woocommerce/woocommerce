<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Product Data Store: Stored in CPT.
 *
 * @version  2.7.0
 * @category Class
 * @author   WooThemes
 */
class WC_Product_Data_Store_CPT extends WC_Data_Store_CPT implements WC_Object_Data_Store, WC_Product_Data_Store {

	/**
	 * If we have already saved our extra data, don't do automatic / default handling.
	 */
	protected $extra_data_saved = false;

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new product in the database.
	 * @param WC_Product
	 */
	public function create( &$product ) {
		$product->set_date_created( current_time( 'timestamp' ) );

		$id = wp_insert_post( apply_filters( 'woocommerce_new_product_data', array(
			'post_type'      => 'product',
			'post_status'    => $product->get_status() ? $product->get_status() : 'publish',
			'post_author'    => get_current_user_id(),
			'post_title'     => $product->get_name() ? $product->get_name() : __( 'Product', 'woocommerce' ),
			'post_content'   => $product->get_description(),
			'post_excerpt'   => $product->get_short_description(),
			'post_parent'    => $product->get_parent_id(),
			'comment_status' => $product->get_reviews_allowed() ? 'open' : 'closed',
			'ping_status'    => 'closed',
			'menu_order'     => $product->get_menu_order(),
			'post_date'      => date( 'Y-m-d H:i:s', $product->get_date_created() ),
			'post_date_gmt'  => get_gmt_from_date( date( 'Y-m-d H:i:s', $product->get_date_created() ) ),
		) ), true );

		if ( $id && ! is_wp_error( $id ) ) {
			$product->set_id( $id );
			$this->update_post_meta( $product );
			$this->update_terms( $product );
			$this->update_attributes( $product );
			$this->update_downloads( $product );
			$product->save_meta_data();

			do_action( 'woocommerce_new_product', $id );

			$product->apply_changes();
			$this->update_version_and_type( $product );
			$this->update_term_counts( $product );
			$this->clear_caches( $product );
		}
	}

	/**
	 * Method to read a product from the database.
	 * @param WC_Product
	 */
	public function read( &$product ) {
		$product->set_defaults();

		if ( ! $product->get_id() || ! ( $post_object = get_post( $product->get_id() ) ) ) {
			throw new Exception( __( 'Invalid product.', 'woocommerce' ) );
		}

		$id = $product->get_id();

		$product->set_props( array(
			'name'              => get_the_title( $post_object ),
			'slug'              => $post_object->post_name,
			'date_created'      => $post_object->post_date,
			'date_modified'     => $post_object->post_modified,
			'status'            => $post_object->post_status,
			'description'       => $post_object->post_content,
			'short_description' => $post_object->post_excerpt,
			'parent_id'         => $post_object->post_parent,
			'menu_order'        => $post_object->menu_order,
			'reviews_allowed'   => 'open' === $post_object->comment_status,
		) );

		$product->read_meta_data();
		$this->read_attributes( $product );
		$this->read_downloads();
		$this->read_product_data( $product );
		$product->set_object_read( true );
	}

	/**
	 * Method to update a product in the database.
	 * @param WC_Product
	 */
	public function update( &$product ) {
		$post_data = array(
			'ID'             => $product->get_id(),
			'post_content'   => $product->get_description(),
			'post_excerpt'   => $product->get_short_description(),
			'post_title'     => $product->get_name(),
			'post_parent'    => $product->get_parent_id(),
			'comment_status' => $product->get_reviews_allowed() ? 'open' : 'closed',
			'post_status'    => $product->get_status() ? $product->get_status() : 'publish',
			'menu_order'     => $product->get_menu_order(),
		);
		wp_update_post( $post_data );

		$this->update_post_meta( $product );
		$this->update_terms( $product );
		$this->update_attributes( $product );
		$this->update_downloads( $product );
		$product->save_meta_data();

		do_action( 'woocommerce_update_product', $product->get_id() );

		$product->apply_changes();
		$this->update_version_and_type( $product );
		$this->update_term_counts( $product );
		$this->clear_caches( $product );
	}

	/**
	 * Method to delete a product from the database.
	 * @param WC_Product
	 */
	public function delete( &$product, $force_delete = false ) {
		$id = $product->get_id();
		$post_type = $product->is_type( 'variation' ) ? 'product_variation' : 'product';

		if ( $force_delete ) {
			wp_delete_post( $product->get_id() );
			$product->set_id( 0 );
		} else {
			wp_trash_post( $product->get_id() );
			$product->set_status( 'trash' );
		}
		do_action( 'woocommerce_delete_' . $post_type, $id );
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Read product data. Can be overridden by child classes to load other props.
	 *
	 * @param WC_Product
	 * @since 2.7.0
	 */
	protected function read_product_data( &$product ) {
		$id             = $product->get_id();
		$review_count   = get_post_meta( $id, '_wc_review_count', true );
		$rating_counts  = get_post_meta( $id, '_wc_rating_count', true );
		$average_rating = get_post_meta( $id, '_wc_average_rating', true );

		if ( '' === $review_count ) {
			$review_count = WC_Comments::get_review_count_for_product( $this );
		}

		if ( '' === $rating_counts ) {
			$rating_counts = WC_Comments::get_rating_counts_for_product( $this );
		}

		if ( '' === $average_rating ) {
			$average_rating = WC_Comments::get_average_rating_for_product( $this );
		}

		$this->set_average_rating( $average_rating );
		$this->set_rating_counts( $rating_counts );
		$this->set_review_count( $review_count );
		$product->set_props( array(
			'featured'           => get_post_meta( $id, '_featured', true ),
			'catalog_visibility' => get_post_meta( $id, '_visibility', true ),
			'sku'                => get_post_meta( $id, '_sku', true ),
			'regular_price'      => get_post_meta( $id, '_regular_price', true ),
			'sale_price'         => get_post_meta( $id, '_sale_price', true ),
			'price'              => get_post_meta( $id, '_price', true ),
			'date_on_sale_from'  => get_post_meta( $id, '_sale_price_dates_from', true ),
			'date_on_sale_to'    => get_post_meta( $id, '_sale_price_dates_to', true ),
			'total_sales'        => get_post_meta( $id, 'total_sales', true ),
			'tax_status'         => get_post_meta( $id, '_tax_status', true ),
			'tax_class'          => get_post_meta( $id, '_tax_class', true ),
			'manage_stock'       => get_post_meta( $id, '_manage_stock', true ),
			'stock_quantity'     => get_post_meta( $id, '_stock', true ),
			'stock_status'       => get_post_meta( $id, '_stock_status', true ),
			'backorders'         => get_post_meta( $id, '_backorders', true ),
			'sold_individually'  => get_post_meta( $id, '_sold_individually', true ),
			'weight'             => get_post_meta( $id, '_weight', true ),
			'length'             => get_post_meta( $id, '_length', true ),
			'width'              => get_post_meta( $id, '_width', true ),
			'height'             => get_post_meta( $id, '_height', true ),
			'upsell_ids'         => get_post_meta( $id, '_upsell_ids', true ),
			'cross_sell_ids'     => get_post_meta( $id, '_crosssell_ids', true ),
			'purchase_note'      => get_post_meta( $id, '_purchase_note', true ),
			'default_attributes' => get_post_meta( $id, '_default_attributes', true ),
			'category_ids'       => $this->get_term_ids( $product, 'product_cat' ),
			'tag_ids'            => $this->get_term_ids( $product, 'product_tag' ),
			'shipping_class_id'  => current( $this->get_term_ids( $product, 'product_shipping_class' ) ),
			'virtual'            => get_post_meta( $id, '_virtual', true ),
			'downloadable'       => get_post_meta( $id, '_downloadable', true ),
			'gallery_image_ids'  => array_filter( explode( ',', get_post_meta( $id, '_product_image_gallery', true ) ) ),
			'download_limit'     => get_post_meta( $id, '_download_limit', true ),
			'download_expiry'    => get_post_meta( $id, '_download_expiry', true ),
			'image_id'           => get_post_thumbnail_id( $id ),
		) );

		// Gets extra data associated with the product.
		// Like button text or product URL for external products.
		foreach ( $product->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;
			if ( is_callable( array( $product, $function ) ) ) {
				$product->{$function}( get_post_meta( $product->get_id(), '_' . $key, true ) );
			}
		}
	}

	/**
	 * Read attributes from post meta.
	 *
	 * @param WC_Product
	 * @since 2.7.0
	 */
	protected function read_attributes( &$product ) {
		$meta_values = maybe_unserialize( get_post_meta( $product->get_id(), '_product_attributes', true ) );

		if ( $meta_values ) {
			$attributes = array();
			foreach ( $meta_values as $meta_value ) {
				if ( ! empty( $meta_value['is_taxonomy'] ) ) {
					if ( ! taxonomy_exists( $meta_value['name'] ) ) {
						continue;
					}
					$options = wp_get_post_terms( $product->get_id(), $meta_value['name'], array( 'fields' => 'ids' ) );
				} else {
					$options = wc_get_text_attributes( $meta_value['value'] );
				}
				$attribute = new WC_Product_Attribute();
				$attribute->set_id( wc_attribute_taxonomy_id_by_name( $meta_value['name'] ) );
				$attribute->set_name( $meta_value['name'] );
				$attribute->set_options( $options );
				$attribute->set_position( $meta_value['position'] );
				$attribute->set_visible( $meta_value['is_visible'] );
				$attribute->set_variation( $meta_value['is_variation'] );
				$attributes[] = $attribute;
			}
			$product->set_attributes( $attributes );
		}
	}

	/**
	 * Read downloads from post meta.
	 *
	 * @param WC_Product
	 * @since 2.7.0
	 */
	protected function read_downloads( &$product ) {
		$meta_values = array_filter( (array) maybe_unserialize( get_post_meta( $this->get_id(), '_downloadable_files', true ) ) );

		if ( $meta_values ) {
			$downloads = array();
			foreach ( $meta_values as $key => $value ) {
				$download    = new WC_Product_Download();
				$download->set_id( $key );
				$download->set_name( $value['name'] ? $value['name'] : wc_get_filename_from_url( $value['file'] ) );
				$download->set_file( apply_filters( 'woocommerce_file_download_path', $value['file'], $this, $key ) );
				$downloads[] = $download;
			}
			$product->set_downloads( $downloads );
		}
	}

	/**
	 * Helper method that updates all the post meta for a product based on it's settings in the WC_Product class.
	 *
	 * @param WC_Product
	 * @since 2.7.0
	 */
	protected function update_post_meta( &$product ) {
		$updated_props     = array();
		$changed_props     = array_keys( $product->get_changes() );
		$meta_key_to_props = array(
			'_visibility'            => 'catalog_visibility',
			'_sku'                   => 'sku',
			'_regular_price'         => 'regular_price',
			'_sale_price'            => 'sale_price',
			'_sale_price_dates_from' => 'date_on_sale_from',
			'_sale_price_dates_to'   => 'date_on_sale_to',
			'total_sales'            => 'total_sales',
			'_tax_status'            => 'tax_status',
			'_tax_class'             => 'tax_class',
			'_manage_stock'          => 'manage_stock',
			'_backorders'            => 'backorders',
			'_sold_individually'     => 'sold_individually',
			'_weight'                => 'weight',
			'_length'                => 'length',
			'_width'                 => 'width',
			'_height'                => 'height',
			'_upsell_ids'            => 'upsell_ids',
			'_crosssell_ids'         => 'cross_sell_ids',
			'_purchase_note'         => 'purchase_note',
			'_default_attributes'    => 'default_attributes',
			'_virtual'               => 'virtual',
			'_downloadable'          => 'downloadable',
			'_product_image_gallery' => 'gallery_image_ids',
			'_download_limit'        => 'download_limit',
			'_download_expiry'       => 'download_expiry',
			'_featured'              => 'featured',
			'_thumbnail_id'          => 'image_id',
			'_downloadable_files'    => 'downloads',
			'_stock'                 => 'stock_quantity',
			'_stock_status'          => 'stock_status',
			'_wc_average_rating'     => 'average_rating',
			'_wc_rating_count'       => 'rating_counts',
			'_wc_review_count'       => 'review_count',
		);

		foreach ( $meta_key_to_props as $meta_key => $prop ) {
			if ( ! in_array( $prop, $changed_props ) ) {
				continue;
			}
			$value = $product->{"get_$prop"}( 'edit' );
			switch ( $prop ) {
				case 'virtual' :
				case 'downloadable' :
				case 'manage_stock' :
				case 'featured' :
				case 'sold_individually' :
					$updated = update_post_meta( $product->get_id(), $meta_key, wc_bool_to_string( $value ) );
					break;
				case 'gallery_image_ids' :
					$updated = update_post_meta( $product->get_id(), $meta_key, implode( ',', $value ) );
					break;
				case 'downloads' :
					// grant permission to any newly added files on any existing orders for this product prior to saving.
					if ( $product->is_type( 'variation' ) ) {
						do_action( 'woocommerce_process_product_file_download_paths', $product->get_parent_id(), $product->get_id(), $value );
					} else {
						do_action( 'woocommerce_process_product_file_download_paths', $product->get_id(), 0, $value );
					}
					$updated = update_post_meta( $product->get_id(), $meta_key, $value );
					break;
				case 'image_id' :
					if ( ! empty( $value ) ) {
						set_post_thumbnail( $product->get_id(), $value );
					} else {
						delete_post_meta( $product->get_id(), '_thumbnail_id' );
					}
					$updated = true;
					break;
				default :
					$updated = update_post_meta( $product->get_id(), $meta_key, $value );
					break;
			}
			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		if ( in_array( 'date_on_sale_from', $updated_props ) || in_array( 'date_on_sale_to', $updated_props ) || in_array( 'regular_price', $updated_props ) || in_array( 'sale_price', $updated_props ) ) {
			if ( $product->is_on_sale() ) {
				update_post_meta( $product->get_id(), '_price', $product->get_sale_price() );
				$product->set_price( $product->get_sale_price() );
			} else {
				update_post_meta( $product->get_id(), '_price', $product->get_regular_price() );
				$product->set_price( $product->get_regular_price() );
			}
		}

		if ( in_array( 'featured', $updated_props ) ) {
			delete_transient( 'wc_featured_products' );
		}

		if ( in_array( 'catalog_visibility', $updated_props ) ) {
			do_action( 'woocommerce_product_set_visibility',  $product->get_id(), $product->get_catalog_visibility() );
		}

		if ( in_array( 'stock_quantity', $updated_props ) ) {
			do_action( $product->is_type( 'variation' ) ? 'woocommerce_variation_set_stock' : 'woocommerce_product_set_stock' , $product );
		}

		if ( in_array( 'stock_status', $updated_props ) ) {
			do_action( $product->is_type( 'variation' ) ? 'woocommerce_variation_set_stock_status' : 'woocommerce_product_set_stock_status' , $product->get_id(), $product->get_stock_status() );
		}

		// Update extra data associated with the product.
		// Like button text or product URL for external products.
		if ( ! $this->extra_data_saved ) {
			foreach ( $product->get_extra_data_keys() as $key ) {
				$function = 'get_' . $key;
				if ( in_array( $key, $changed_props ) && is_callable( array( $product, $function ) ) ) {
					update_post_meta( $product->get_id(), '_' . $key, $product->{$function}( 'edit' ) );
				}
			}
		}
	}

	/**
	 * For all stored terms in all taxonomies, save them to the DB.
	 *
	 * @param WC_Product
	 * @since 2.7.0
	 */
	protected function update_terms( &$product ) {
		wp_set_post_terms( $product->get_id(), $product->get_category_ids( 'edit' ), 'product_cat', false );
		wp_set_post_terms( $product->get_id(), $product->get_tag_ids( 'edit' ), 'product_tag', false );
		wp_set_post_terms( $product->get_id(), array( $product->get_shipping_class_id( 'edit' ) ), 'product_shipping_class', false );
	}

	/**
	 * Update attributes which are a mix of terms and meta data.
	 *
	 * @param WC_Product
	 * @since 2.7.0
	 */
	protected function update_attributes( &$product ) {
		$attributes  = $product->get_attributes();
		$meta_values = array();

		if ( $attributes ) {
			foreach ( $attributes as $attribute_key => $attribute ) {
				$value = '';

				if ( is_null( $attribute ) ) {
					if ( taxonomy_exists( $attribute_key ) ) {
						// Handle attributes that have been unset.
						wp_set_object_terms( $product->get_id(), array(), $attribute_key );
					}
					continue;

				} elseif ( $attribute->is_taxonomy() ) {
					wp_set_object_terms( $product->get_id(), wp_list_pluck( $attribute->get_terms(), 'term_id' ), $attribute->get_name() );

				} else {
					$value = wc_implode_text_attributes( $attribute->get_options() );
				}

				// Store in format WC uses in meta.
				$meta_values[ $attribute_key ] = array(
					'name'         => $attribute->get_name(),
					'value'        => $value,
					'position'     => $attribute->get_position(),
					'is_visible'   => $attribute->get_visible() ? 1 : 0,
					'is_variation' => $attribute->get_variation() ? 1 : 0,
					'is_taxonomy'  => $attribute->is_taxonomy() ? 1 : 0,
				);
			}
		}
		update_post_meta( $product->get_id(), '_product_attributes', $meta_values );
	}

	/**
	 * Update downloads.
	 *
	 * @since 2.7.0
	 */
	protected function update_downloads( &$product ) {
		$downloads   = $product->get_downloads();
		$meta_values = array();

		if ( $downloads ) {
			foreach ( $downloads as $key => $download ) {
				// Store in format WC uses in meta.
				$meta_values[ $key ] = $download->get_data();
			}
		}
		update_post_meta( $product->get_id(), '_downloadable_files', $meta_values );
	}

	/**
	 * Make sure we store the product type and version (to track data changes).
	 *
	 * @param WC_Product
	 * @since 2.7.0
	 */
	protected function update_version_and_type( &$product ) {
		$type_term = get_term_by( 'name', $product->get_type(), 'product_type' );
		wp_set_object_terms( $product->get_id(), absint( $type_term->term_id ), 'product_type' );
		update_post_meta( $product->get_id(), '_product_version', WC_VERSION );
	}

	/**
	 * Count terms. These are done at this point so all product props are set in advance.
	 *
	 * @param WC_Product
	 * @since 2.7.0
	 */
	protected function update_term_counts( &$product ) {
		if ( ! wp_defer_term_counting() ) {
			global $wc_allow_term_recount;

			$wc_allow_term_recount = true;

			$post_type = $product->is_type( 'variation' ) ? 'product_variation' : 'product';

			// Update counts for the post's terms.
			foreach ( (array) get_object_taxonomies( $post_type ) as $taxonomy ) {
				$tt_ids = wp_get_object_terms( $product->get_id(), $taxonomy, array( 'fields' => 'tt_ids' ) );
				wp_update_term_count( $tt_ids, $taxonomy );
			}
		}
	}

	/**
	 * Clear any caches.
	 *
	 * @param WC_Product
	 * @since 2.7.0
	 */
	protected function clear_caches( &$product ) {
		wc_delete_product_transients( $product->get_id() );
	}

	/*
	|--------------------------------------------------------------------------
	| wc-product-functions.php methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Returns an array of on sale products, as an array of objects with an
	 * ID and parent_id present. Example: $return[0]->id, $return[0]->parent_id.
	 *
	 * @return array
	 * @since 2.7.0
	 */
	public function get_on_sale_products() {
		global $wpdb;
		return $wpdb->get_results( "
			SELECT post.ID as id, post.post_parent as parent_id FROM `$wpdb->posts` AS post
			LEFT JOIN `$wpdb->postmeta` AS meta ON post.ID = meta.post_id
			LEFT JOIN `$wpdb->postmeta` AS meta2 ON post.ID = meta2.post_id
			WHERE post.post_type IN ( 'product', 'product_variation' )
				AND post.post_status = 'publish'
				AND meta.meta_key = '_sale_price'
				AND meta2.meta_key = '_price'
				AND CAST( meta.meta_value AS DECIMAL ) >= 0
				AND CAST( meta.meta_value AS CHAR ) != ''
				AND CAST( meta.meta_value AS DECIMAL ) = CAST( meta2.meta_value AS DECIMAL )
			GROUP BY post.ID;
		" );
	}

	/**
	 * Returns a list of product IDs ( id as key => parent as value) that are
	 * featured. Uses get_posts instead of wc_get_products since we want
	 * some extra meta queries and ALL products (posts_per_page = -1).
	 *
	 * @return array
	 * @since 2.7.0
	 */
	public function get_featured_product_ids() {
		return get_posts( array(
			'post_type'      => array( 'product', 'product_variation' ),
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key' 		=> '_visibility',
					'value' 	=> array( 'catalog', 'visible' ),
					'compare' 	=> 'IN',
				),
				array(
					'key' 	=> '_featured',
					'value' => 'yes',
				),
			),
			'fields' => 'id=>parent',
		) );
	}

	/**
	 * Check if product sku is found for any other product IDs.
	 *
	 * @since 2.7.0
	 * @param int $product_id
	 * @param string $sku Will be slashed to work around https://core.trac.wordpress.org/ticket/27421
	 * @return bool
	 */
	public function is_existing_sku( $product_id, $sku ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "
			SELECT $wpdb->posts.ID
			FROM $wpdb->posts
			LEFT JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id )
			WHERE $wpdb->posts.post_type IN ( 'product', 'product_variation' )
			AND $wpdb->posts.post_status = 'publish'
			AND $wpdb->postmeta.meta_key = '_sku' AND $wpdb->postmeta.meta_value = '%s'
			AND $wpdb->postmeta.post_id <> %d LIMIT 1
		 ", wp_slash( $sku ), $product_id ) );
	}

	/**
	 * Return product ID based on SKU.
	 *
	 * @since 2.7.0
	 * @param string $sku
	 * @return int
	 */
	public function get_product_id_by_sku( $sku ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "
			SELECT posts.ID
			FROM $wpdb->posts AS posts
			LEFT JOIN $wpdb->postmeta AS postmeta ON ( posts.ID = postmeta.post_id )
			WHERE posts.post_type IN ( 'product', 'product_variation' )
			AND postmeta.meta_key = '_sku' AND postmeta.meta_value = '%s'
			LIMIT 1
		 ", $sku ) );
	}

	/**
	 * Returns an array of IDs of products that have sales starting soon.
	 *
	 * @since 2.7.0
	 * @return array
	 */
	public function get_starting_sales() {
		global $wpdb;
		return $wpdb->get_col( $wpdb->prepare( "
			SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
			LEFT JOIN {$wpdb->postmeta} as postmeta_2 ON postmeta.post_id = postmeta_2.post_id
			LEFT JOIN {$wpdb->postmeta} as postmeta_3 ON postmeta.post_id = postmeta_3.post_id
			WHERE postmeta.meta_key = '_sale_price_dates_from'
			AND postmeta_2.meta_key = '_price'
			AND postmeta_3.meta_key = '_sale_price'
			AND postmeta.meta_value > 0
			AND postmeta.meta_value < %s
			AND postmeta_2.meta_value != postmeta_3.meta_value
		", current_time( 'timestamp' ) ) );
	}

	/**
	 * Returns an array of IDs of products that have sales which are due to end.
	 *
	 * @since 2.7.0
	 * @return array
	 */
	public function get_ending_sales() {
		global $wpdb;
		return $wpdb->get_col( $wpdb->prepare( "
			SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
			LEFT JOIN {$wpdb->postmeta} as postmeta_2 ON postmeta.post_id = postmeta_2.post_id
			LEFT JOIN {$wpdb->postmeta} as postmeta_3 ON postmeta.post_id = postmeta_3.post_id
			WHERE postmeta.meta_key = '_sale_price_dates_to'
			AND postmeta_2.meta_key = '_price'
			AND postmeta_3.meta_key = '_regular_price'
			AND postmeta.meta_value > 0
			AND postmeta.meta_value < %s
			AND postmeta_2.meta_value != postmeta_3.meta_value
		", current_time( 'timestamp' ) ) );
	}

	/**
	 * Find a matching (enabled) variation within a variable product.
	 *
	 * @since  2.7.0
	 * @param  WC_Product $product Variable product.
	 * @param  array $match_attributes Array of attributes we want to try to match.
	 * @return int Matching variation ID or 0.
	 */
	public function find_matching_product_variation( $product, $match_attributes = array() ) {
		$query_args = array(
			'post_parent' => $product->get_id(),
			'post_type'   => 'product_variation',
			'orderby'     => 'menu_order',
			'order'       => 'ASC',
			'fields'      => 'ids',
			'post_status' => 'publish',
			'numberposts' => 1,
			'meta_query'  => array(),
		);

		// Allow large queries in case user has many variations or attributes.
		$GLOBALS['wpdb']->query( 'SET SESSION SQL_BIG_SELECTS=1' );

		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute->get_variation() ) {
				continue;
			}

			$attribute_field_name = 'attribute_' . sanitize_title( $attribute->get_name() );

			if ( ! isset( $match_attributes[ $attribute_field_name ] ) ) {
				return 0;
			}

			$value = wc_clean( $match_attributes[ $attribute_field_name ] );

			$query_args['meta_query'][] = array(
				'relation' => 'OR',
				array(
					'key'     => $attribute_field_name,
					'value'   => array( '', $value ),
					'compare' => 'IN',
				),
				array(
					'key'     => $attribute_field_name,
					'compare' => 'NOT EXISTS',
				)
			);
		}

		$variations = get_posts( $query_args );

		if ( $variations && ! is_wp_error( $variations ) ) {
			return current( $variations );
	 	} elseif ( version_compare( get_post_meta( $product->get_id(), '_product_version', true ), '2.4.0', '<' ) ) {
			/**
			 * Pre 2.4 handling where 'slugs' were saved instead of the full text attribute.
			 * Fallback is here because there are cases where data will be 'synced' but the product version will remain the same.
			 */
			return ( array_map( 'sanitize_title', $match_attributes ) === $match_attributes ) ? 0 : $this->find_matching_product_variation( $product, array_map( 'sanitize_title', $match_attributes ) );
		}

		return 0;
	}

	/**
	 * Return a list of related products (using data like categories and IDs).
	 *
	 * @since 2.7.0
	 * @param array $cats_array  List of categories IDs.
	 * @param array $tags_array  List of tags IDs.
	 * @param array $exclude_ids Excluded IDs.
	 * @param int   $limit       Limit of results.
	 * @param int   $product_id
	 * @return array
	 */
	public function get_related_products( $cats_array, $tags_array, $exclude_ids, $limit, $product_id ) {
		global $wpdb;
		return $wpdb->get_col( implode( ' ', apply_filters( 'woocommerce_product_related_posts_query', $this->get_related_products_query( $cats_array, $tags_array, $exclude_ids, $limit + 10 ), $product_id ) ) );
	}

	/**
	 * Builds the related posts query.
	 *
	 * @since 2.7.0
	 * @param array $cats_array  List of categories IDs.
	 * @param array $tags_array  List of tags IDs.
	 * @param array $exclude_ids Excluded IDs.
	 * @param int   $limit       Limit of results.
	 * @return string
	 */
	function get_related_products_query( $cats_array, $tags_array, $exclude_ids, $limit ) {
		global $wpdb;

		// Arrays to string.
		$exclude_ids = implode( ',', array_map( 'absint', $exclude_ids ) );
		$cats_array  = implode( ',', array_map( 'absint', $cats_array ) );
		$tags_array  = implode( ',', array_map( 'absint', $tags_array ) );

		$limit           = absint( $limit );
		$query           = array();
		$query['fields'] = "SELECT DISTINCT ID FROM {$wpdb->posts} p";
		$query['join']   = " INNER JOIN {$wpdb->postmeta} pm ON ( pm.post_id = p.ID AND pm.meta_key='_visibility' )";
		$query['join']  .= " INNER JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)";
		$query['join']  .= " INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)";
		$query['join']  .= " INNER JOIN {$wpdb->terms} t ON (t.term_id = tt.term_id)";

		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$query['join'] .= " INNER JOIN {$wpdb->postmeta} pm2 ON ( pm2.post_id = p.ID AND pm2.meta_key='_stock_status' )";
		}

		$query['where']  = ' WHERE 1=1';
		$query['where'] .= " AND p.post_status = 'publish'";
		$query['where'] .= " AND p.post_type = 'product'";
		$query['where'] .= " AND p.ID NOT IN ( {$exclude_ids} )";
		$query['where'] .= " AND pm.meta_value IN ( 'visible', 'catalog' )";

		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$query['where'] .= " AND pm2.meta_value = 'instock'";
		}

		if ( $cats_array || $tags_array ) {
			$query['where'] .= ' AND (';

			if ( $cats_array ) {
				$query['where'] .= " ( tt.taxonomy = 'product_cat' AND t.term_id IN ( {$cats_array} ) ) ";
				if ( $tags_array ) {
					$query['where'] .= ' OR ';
				}
			}

			if ( $tags_array ) {
				$query['where'] .= " ( tt.taxonomy = 'product_tag' AND t.term_id IN ( {$tags_array} ) ) ";
			}

			$query['where'] .= ')';
		}

		$query['limits'] = " LIMIT {$limit} ";

		return $query;
	}

	/**
	 * Update a product's stock amount directly.
	 *
	 * Uses queries rather than update_post_meta so we can do this in one query (to avoid stock issues).
	 *
	 * @since  2.7.0 this supports set, increase and decrease.
	 * @param  int
	 * @param  int|null $stock_quantity
	 * @param  string $operation set, increase and decrease.
	 */
	function update_product_stock( $product_id_with_stock, $stock_quantity = null, $operation = 'set' ) {
		global $wpdb;
		add_post_meta( $product_id_with_stock, '_stock', 0, true );

		// Update stock in DB directly
		switch ( $operation ) {
			case 'increase' :
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = meta_value + %f WHERE post_id = %d AND meta_key='_stock'", $stock_quantity, $product_id_with_stock ) );
				break;
			case 'decrease' :
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = meta_value - %f WHERE post_id = %d AND meta_key='_stock'", $stock_quantity, $product_id_with_stock ) );
				break;
			default :
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = %f WHERE post_id = %d AND meta_key='_stock'", $stock_quantity, $product_id_with_stock ) );
				break;
		}

		wp_cache_delete( $product_id_with_stock, 'post_meta' );
	}

}
