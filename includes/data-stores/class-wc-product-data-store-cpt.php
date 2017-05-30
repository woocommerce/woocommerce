<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Product Data Store: Stored in CPT.
 *
 * @version  3.0.0
 * @category Class
 * @author   WooThemes
 */
class WC_Product_Data_Store_CPT extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface, WC_Product_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta".
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_visibility',
		'_sku',
		'_price',
		'_regular_price',
		'_sale_price',
		'_sale_price_dates_from',
		'_sale_price_dates_to',
		'total_sales',
		'_tax_status',
		'_tax_class',
		'_manage_stock',
		'_stock',
		'_stock_status',
		'_backorders',
		'_sold_individually',
		'_weight',
		'_length',
		'_width',
		'_height',
		'_upsell_ids',
		'_crosssell_ids',
		'_purchase_note',
		'_default_attributes',
		'_product_attributes',
		'_virtual',
		'_downloadable',
		'_featured',
		'_downloadable_files',
		'_wc_rating_count',
		'_wc_average_rating',
		'_wc_review_count',
		'_variation_description',
		'_thumbnail_id',
		'_file_paths',
		'_product_image_gallery',
		'_product_version',
		'_wp_old_slug',
		'_edit_last',
		'_edit_lock',
	);

	/**
	 * If we have already saved our extra data, don't do automatic / default handling.
	 */
	protected $extra_data_saved = false;

	/**
	 * Stores updated props.
	 * @var array
	 */
	protected $updated_props = array();

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new product in the database.
	 *
	 * @param WC_Product
	 */
	public function create( &$product ) {
		if ( ! $product->get_date_created() ) {
			$product->set_date_created( current_time( 'timestamp', true ) );
		}

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
			'post_date'      => gmdate( 'Y-m-d H:i:s', $product->get_date_created( 'edit' )->getOffsetTimestamp() ),
			'post_date_gmt'  => gmdate( 'Y-m-d H:i:s', $product->get_date_created( 'edit' )->getTimestamp() ),
			'post_name'      => $product->get_slug( 'edit' ),
		) ), true );

		if ( $id && ! is_wp_error( $id ) ) {
			$product->set_id( $id );

			$this->update_post_meta( $product, true );
			$this->update_terms( $product, true );
			$this->update_visibility( $product, true );
			$this->update_attributes( $product, true );
			$this->update_version_and_type( $product );
			$this->handle_updated_props( $product );

			$product->save_meta_data();
			$product->apply_changes();

			$this->clear_caches( $product );

			do_action( 'woocommerce_new_product', $id );
		}
	}

	/**
	 * Method to read a product from the database.
	 * @param WC_Product
	 */
	public function read( &$product ) {
		$product->set_defaults();

		if ( ! $product->get_id() || ! ( $post_object = get_post( $product->get_id() ) ) || 'product' !== $post_object->post_type ) {
			throw new Exception( __( 'Invalid product.', 'woocommerce' ) );
		}

		$id = $product->get_id();

		$product->set_props( array(
			'name'              => $post_object->post_title,
			'slug'              => $post_object->post_name,
			'date_created'      => 0 < $post_object->post_date_gmt ? wc_string_to_timestamp( $post_object->post_date_gmt ) : null,
			'date_modified'     => 0 < $post_object->post_modified_gmt ? wc_string_to_timestamp( $post_object->post_modified_gmt ) : null,
			'status'            => $post_object->post_status,
			'description'       => $post_object->post_content,
			'short_description' => $post_object->post_excerpt,
			'parent_id'         => $post_object->post_parent,
			'menu_order'        => $post_object->menu_order,
			'reviews_allowed'   => 'open' === $post_object->comment_status,
		) );

		$this->read_attributes( $product );
		$this->read_downloads( $product );
		$this->read_visibility( $product );
		$this->read_product_data( $product );
		$this->read_extra_data( $product );
		$product->set_object_read( true );
	}

	/**
	 * Method to update a product in the database.
	 *
	 * @param WC_Product
	 */
	public function update( &$product ) {
		$product->save_meta_data();
		$changes = $product->get_changes();

		// Only update the post when the post data changes.
		if ( array_intersect( array( 'description', 'short_description', 'name', 'parent_id', 'reviews_allowed', 'status', 'menu_order', 'date_created', 'date_modified', 'slug' ), array_keys( $changes ) ) ) {
			$post_data = array(
				'post_content'   => $product->get_description( 'edit' ),
				'post_excerpt'   => $product->get_short_description( 'edit' ),
				'post_title'     => $product->get_name( 'edit' ),
				'post_parent'    => $product->get_parent_id( 'edit' ),
				'comment_status' => $product->get_reviews_allowed( 'edit' ) ? 'open' : 'closed',
				'post_status'    => $product->get_status( 'edit' ) ? $product->get_status( 'edit' ) : 'publish',
				'menu_order'     => $product->get_menu_order( 'edit' ),
				'post_name'      => $product->get_slug( 'edit' ),
			);
			if ( $product->get_date_created( 'edit' ) ) {
				$post_data['post_date']     = gmdate( 'Y-m-d H:i:s', $product->get_date_created( 'edit' )->getOffsetTimestamp() );
				$post_data['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', $product->get_date_created( 'edit' )->getTimestamp() );
			}
			if ( isset( $changes['date_modified'] ) && $product->get_date_modified( 'edit' ) ) {
				$post_data['post_modified']     = gmdate( 'Y-m-d H:i:s', $product->get_date_modified( 'edit' )->getOffsetTimestamp() );
				$post_data['post_modified_gmt'] = gmdate( 'Y-m-d H:i:s', $product->get_date_modified( 'edit' )->getTimestamp() );
			} else {
				$post_data['post_modified']     = current_time( 'mysql' );
				$post_data['post_modified_gmt'] = current_time( 'mysql', 1 );
			}

			/**
			 * When updating this object, to prevent infinite loops, use $wpdb
			 * to update data, since wp_update_post spawns more calls to the
			 * save_post action.
			 *
			 * This ensures hooks are fired by either WP itself (admin screen save),
			 * or an update purely from CRUD.
			 */
			if ( doing_action( 'save_post' ) ) {
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $product->get_id() ) );
				clean_post_cache( $product->get_id() );
			} else {
				wp_update_post( array_merge( array( 'ID' => $product->get_id() ), $post_data ) );
			}
			$product->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		}

		$this->update_post_meta( $product );
		$this->update_terms( $product );
		$this->update_visibility( $product );
		$this->update_attributes( $product );
		$this->update_version_and_type( $product );
		$this->handle_updated_props( $product );

		$product->apply_changes();

		$this->clear_caches( $product );

		do_action( 'woocommerce_update_product', $product->get_id() );
	}

	/**
	 * Method to delete a product from the database.
	 * @param WC_Product
	 * @param array $args Array of args to pass to the delete method.
	 */
	public function delete( &$product, $args = array() ) {
		$id        = $product->get_id();
		$post_type = $product->is_type( 'variation' ) ? 'product_variation' : 'product';

		$args = wp_parse_args( $args, array(
			'force_delete' => false,
		) );

		if ( ! $id ) {
			return;
		}

		if ( $args['force_delete'] ) {
			wp_delete_post( $id );
			$product->set_id( 0 );
			do_action( 'woocommerce_delete_' . $post_type, $id );
		} else {
			wp_trash_post( $id );
			$product->set_status( 'trash' );
			do_action( 'woocommerce_trash_' . $post_type, $id );
		}
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
	 * @since 3.0.0
	 */
	protected function read_product_data( &$product ) {
		$id = $product->get_id();

		if ( '' === ( $review_count = get_post_meta( $id, '_wc_review_count', true ) ) ) {
			WC_Comments::get_review_count_for_product( $product );
		} else {
			$product->set_review_count( $review_count );
		}

		if ( '' === ( $rating_counts = get_post_meta( $id, '_wc_rating_count', true ) ) ) {
			WC_Comments::get_rating_counts_for_product( $product );
		} else {
			$product->set_rating_counts( $rating_counts );
		}

		if ( '' === ( $average_rating = get_post_meta( $id, '_wc_average_rating', true ) ) ) {
			WC_Comments::get_average_rating_for_product( $product );
		} else {
			$product->set_average_rating( $average_rating );
		}

		$product->set_props( array(
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
	}

	/**
	 * Read extra data associated with the product, like button text or product URL for external products.
	 *
	 * @param WC_Product
	 * @since 3.0.0
	 */
	protected function read_extra_data( &$product ) {
		foreach ( $product->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;
			if ( is_callable( array( $product, $function ) ) ) {
				$product->{$function}( get_post_meta( $product->get_id(), '_' . $key, true ) );
			}
		}
	}

	/**
	 * Convert visibility terms to props.
	 * Catalog visibility valid values are 'visible', 'catalog', 'search', and 'hidden'.
	 *
	 * @param WC_Product
	 * @since 3.0.0
	 */
	protected function read_visibility( &$product ) {
		$terms           = get_the_terms( $product->get_id(), 'product_visibility' );
		$term_names      = is_array( $terms ) ? wp_list_pluck( $terms, 'name' ) : array();
		$featured        = in_array( 'featured', $term_names );
		$exclude_search  = in_array( 'exclude-from-search', $term_names );
		$exclude_catalog = in_array( 'exclude-from-catalog', $term_names );

		if ( $exclude_search && $exclude_catalog ) {
			$catalog_visibility = 'hidden';
		} elseif ( $exclude_search ) {
			$catalog_visibility = 'catalog';
		} elseif ( $exclude_catalog ) {
			$catalog_visibility = 'search';
		} else {
			$catalog_visibility = 'visible';
		}

		$product->set_props( array(
			'featured'           => $featured,
			'catalog_visibility' => $catalog_visibility,
		) );
	}

	/**
	 * Read attributes from post meta.
	 *
	 * @param WC_Product
	 * @since 3.0.0
	 */
	protected function read_attributes( &$product ) {
		$meta_values = get_post_meta( $product->get_id(), '_product_attributes', true );

		if ( ! empty( $meta_values ) && is_array( $meta_values ) ) {
			$attributes = array();
			foreach ( $meta_values as $meta_value ) {
				$id         = 0;
				$meta_value = array_merge( array(
					'name'         => '',
					'value'        => '',
					'position'     => 0,
					'is_visible'   => 0,
					'is_variation' => 0,
					'is_taxonomy'  => 0,
				), (array) $meta_value );

				// Check if is a taxonomy attribute.
				if ( ! empty( $meta_value['is_taxonomy'] ) ) {
					if ( ! taxonomy_exists( $meta_value['name'] ) ) {
						continue;
					}
					$id      = wc_attribute_taxonomy_id_by_name( $meta_value['name'] );
					$options = wc_get_object_terms( $product->get_id(), $meta_value['name'], 'term_id' );
				} else {
					$options = wc_get_text_attributes( $meta_value['value'] );
				}

				$attribute = new WC_Product_Attribute();

				$attribute->set_id( $id );
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
	 * @since 3.0.0
	 */
	protected function read_downloads( &$product ) {
		$meta_values = array_filter( (array) get_post_meta( $product->get_id(), '_downloadable_files', true ) );

		if ( $meta_values ) {
			$downloads = array();
			foreach ( $meta_values as $key => $value ) {
				if ( ! isset( $value['name'], $value['file'] ) ) {
					continue;
				}
				$download    = new WC_Product_Download();
				$download->set_id( $key );
				$download->set_name( $value['name'] ? $value['name'] : wc_get_filename_from_url( $value['file'] ) );
				$download->set_file( apply_filters( 'woocommerce_file_download_path', $value['file'], $product, $key ) );
				$downloads[] = $download;
			}
			$product->set_downloads( $downloads );
		}
	}

	/**
	 * Helper method that updates all the post meta for a product based on it's settings in the WC_Product class.
	 *
	 * @param WC_Product
	 * @param bool Force update. Used during create.
	 * @since 3.0.0
	 */
	protected function update_post_meta( &$product, $force = false ) {
		$meta_key_to_props = array(
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
			'_thumbnail_id'          => 'image_id',
			'_stock'                 => 'stock_quantity',
			'_stock_status'          => 'stock_status',
			'_wc_average_rating'     => 'average_rating',
			'_wc_rating_count'       => 'rating_counts',
			'_wc_review_count'       => 'review_count',
		);

		// Make sure to take extra data (like product url or text for external products) into account.
		$extra_data_keys = $product->get_extra_data_keys();

		foreach ( $extra_data_keys as $key ) {
			$meta_key_to_props[ '_' . $key ] = $key;
		}

		$props_to_update = $force ? $meta_key_to_props : $this->get_props_to_update( $product, $meta_key_to_props );

		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $product->{"get_$prop"}( 'edit' );
			switch ( $prop ) {
				case 'virtual' :
				case 'downloadable' :
				case 'manage_stock' :
				case 'sold_individually' :
					$updated = update_post_meta( $product->get_id(), $meta_key, wc_bool_to_string( $value ) );
					break;
				case 'gallery_image_ids' :
					$updated = update_post_meta( $product->get_id(), $meta_key, implode( ',', $value ) );
					break;
				case 'image_id' :
					if ( ! empty( $value ) ) {
						set_post_thumbnail( $product->get_id(), $value );
					} else {
						delete_post_meta( $product->get_id(), '_thumbnail_id' );
					}
					$updated = true;
					break;
				case 'date_on_sale_from' :
				case 'date_on_sale_to' :
					$updated = update_post_meta( $product->get_id(), $meta_key, $value ? $value->getTimestamp() : '' );
					break;
				default :
					$updated = update_post_meta( $product->get_id(), $meta_key, $value );
					break;
			}
			if ( $updated ) {
				$this->updated_props[] = $prop;
			}
		}

		// Update extra data associated with the product like button text or product URL for external products.
		if ( ! $this->extra_data_saved ) {
			foreach ( $extra_data_keys as $key ) {
				if ( ! array_key_exists( $key, $props_to_update ) ) {
					continue;
				}
				$function = 'get_' . $key;
				if ( is_callable( array( $product, $function ) ) ) {
					if ( update_post_meta( $product->get_id(), '_' . $key, $product->{$function}( 'edit' ) ) ) {
						$this->updated_props[] = $key;
					}
				}
			}
		}

		if ( $this->update_downloads( $product, $force ) ) {
			$this->updated_props[] = 'downloads';
		}
	}

	/**
	 * Handle updated meta props after updating meta data.
	 *
	 * @since  3.0.0
	 * @param  WC_Product $product
	 */
	protected function handle_updated_props( &$product ) {
		if ( in_array( 'date_on_sale_from', $this->updated_props ) || in_array( 'date_on_sale_to', $this->updated_props ) || in_array( 'regular_price', $this->updated_props ) || in_array( 'sale_price', $this->updated_props ) ) {
			if ( $product->is_on_sale( 'edit' ) ) {
				update_post_meta( $product->get_id(), '_price', $product->get_sale_price( 'edit' ) );
				$product->set_price( $product->get_sale_price( 'edit' ) );
			} else {
				update_post_meta( $product->get_id(), '_price', $product->get_regular_price( 'edit' ) );
				$product->set_price( $product->get_regular_price( 'edit' ) );
			}
		}

		if ( in_array( 'stock_quantity', $this->updated_props ) ) {
			do_action( $product->is_type( 'variation' ) ? 'woocommerce_variation_set_stock' : 'woocommerce_product_set_stock' , $product );
		}

		if ( in_array( 'stock_status', $this->updated_props ) ) {
			do_action( $product->is_type( 'variation' ) ? 'woocommerce_variation_set_stock_status' : 'woocommerce_product_set_stock_status' , $product->get_id(), $product->get_stock_status(), $product );
		}

		// Trigger action so 3rd parties can deal with updated props.
		do_action( 'woocommerce_product_object_updated_props', $product, $this->updated_props );

		// After handling, we can reset the props array.
		$this->updated_props = array();
	}

	/**
	 * For all stored terms in all taxonomies, save them to the DB.
	 *
	 * @param WC_Product
	 * @param bool Force update. Used during create.
	 * @since 3.0.0
	 */
	protected function update_terms( &$product, $force = false ) {
		$changes = $product->get_changes();

		if ( $force || array_key_exists( 'category_ids', $changes ) ) {
			wp_set_post_terms( $product->get_id(), $product->get_category_ids( 'edit' ), 'product_cat', false );
		}
		if ( $force || array_key_exists( 'tag_ids', $changes ) ) {
			wp_set_post_terms( $product->get_id(), $product->get_tag_ids( 'edit' ), 'product_tag', false );
		}
		if ( $force || array_key_exists( 'shipping_class_id', $changes ) ) {
			wp_set_post_terms( $product->get_id(), array( $product->get_shipping_class_id( 'edit' ) ), 'product_shipping_class', false );
		}
	}

	/**
	 * Update visibility terms based on props.
	 *
	 * @since 3.0.0
	 * @param bool Force update. Used during create.
	 * @param WC_Product
	 */
	protected function update_visibility( &$product, $force = false ) {
		$changes = $product->get_changes();

		if ( $force || array_intersect( array( 'featured', 'stock_status', 'average_rating', 'catalog_visibility' ), array_keys( $changes ) ) ) {
			$terms = array();

			if ( $product->get_featured() ) {
				$terms[] = 'featured';
			}

			if ( 'outofstock' === $product->get_stock_status() ) {
				$terms[] = 'outofstock';
			}

			$rating = min( 5, round( $product->get_average_rating(), 0 ) );

			if ( $rating > 0 ) {
				$terms[] = 'rated-' . $rating;
			}

			switch ( $product->get_catalog_visibility() ) {
				case 'hidden' :
					$terms[] = 'exclude-from-search';
					$terms[] = 'exclude-from-catalog';
					break;
				case 'catalog' :
					$terms[] = 'exclude-from-search';
					break;
				case 'search' :
					$terms[] = 'exclude-from-catalog';
					break;
			}

			if ( ! is_wp_error( wp_set_post_terms( $product->get_id(), $terms, 'product_visibility', false ) ) ) {
				delete_transient( 'wc_featured_products' );
				do_action( 'woocommerce_product_set_visibility', $product->get_id(), $product->get_catalog_visibility() );
			}
		}
	}

	/**
	 * Update attributes which are a mix of terms and meta data.
	 *
	 * @param WC_Product
	 * @param bool Force update. Used during create.
	 * @since 3.0.0
	 */
	protected function update_attributes( &$product, $force = false ) {
		$changes = $product->get_changes();

		if ( $force || array_key_exists( 'attributes', $changes ) ) {
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
	}

	/**
	 * Update downloads.
	 *
	 * @since 3.0.0
	 * @param WC_Product $product
	 * @param bool Force update. Used during create.
	 * @return bool If updated or not.
	 */
	protected function update_downloads( &$product, $force = false ) {
		$changes = $product->get_changes();

		if ( $force || array_key_exists( 'downloads', $changes ) ) {
			$downloads   = $product->get_downloads();
			$meta_values = array();

			if ( $downloads ) {
				foreach ( $downloads as $key => $download ) {
					// Store in format WC uses in meta.
					$meta_values[ $key ] = $download->get_data();
				}
			}

			if ( $product->is_type( 'variation' ) ) {
				do_action( 'woocommerce_process_product_file_download_paths', $product->get_parent_id(), $product->get_id(), $downloads );
			} else {
				do_action( 'woocommerce_process_product_file_download_paths', $product->get_id(), 0, $downloads );
			}

			return update_post_meta( $product->get_id(), '_downloadable_files', $meta_values );
		}
		return false;
	}

	/**
	 * Make sure we store the product type and version (to track data changes).
	 *
	 * @param WC_Product
	 * @since 3.0.0
	 */
	protected function update_version_and_type( &$product ) {
		$old_type = WC_Product_Factory::get_product_type( $product->get_id() );
		$new_type = $product->get_type();

		wp_set_object_terms( $product->get_id(), $new_type, 'product_type' );
		update_post_meta( $product->get_id(), '_product_version', WC_VERSION );

		// Action for the transition.
		if ( $old_type !== $new_type ) {
			do_action( 'woocommerce_product_type_changed', $product, $old_type, $new_type );
		}
	}

	/**
	 * Clear any caches.
	 *
	 * @param WC_Product
	 * @since 3.0.0
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
	 * @since 3.0.0
	 */
	public function get_on_sale_products() {
		global $wpdb;

		$decimals = absint( wc_get_price_decimals() );

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
				AND CAST( meta.meta_value AS DECIMAL( 10, {$decimals} ) ) = CAST( meta2.meta_value AS DECIMAL( 10, {$decimals} ) )
			GROUP BY post.ID;
		" );
	}

	/**
	 * Returns a list of product IDs ( id as key => parent as value) that are
	 * featured. Uses get_posts instead of wc_get_products since we want
	 * some extra meta queries and ALL products (posts_per_page = -1).
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function get_featured_product_ids() {
		$product_visibility_term_ids = wc_get_product_visibility_term_ids();

		return get_posts( array(
			'post_type'      => array( 'product', 'product_variation' ),
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'tax_query'      => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => array( $product_visibility_term_ids['featured'] ),
				),
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => array( $product_visibility_term_ids['exclude-from-catalog'] ),
					'operator' => 'NOT IN',
				),
			),
			'fields' => 'id=>parent',
		) );
	}

	/**
	 * Check if product sku is found for any other product IDs.
	 *
	 * @since 3.0.0
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
			AND $wpdb->posts.post_status != 'trash'
			AND $wpdb->postmeta.meta_key = '_sku' AND $wpdb->postmeta.meta_value = '%s'
			AND $wpdb->postmeta.post_id <> %d LIMIT 1
		 ", wp_slash( $sku ), $product_id ) );
	}

	/**
	 * Return product ID based on SKU.
	 *
	 * @since 3.0.0
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
			AND posts.post_status != 'trash'
			AND postmeta.meta_key = '_sku'
			AND postmeta.meta_value = '%s'
			LIMIT 1
		 ", $sku ) );
	}

	/**
	 * Returns an array of IDs of products that have sales starting soon.
	 *
	 * @since 3.0.0
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
		", current_time( 'timestamp', true ) ) );
	}

	/**
	 * Returns an array of IDs of products that have sales which are due to end.
	 *
	 * @since 3.0.0
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
		", current_time( 'timestamp', true ) ) );
	}

	/**
	 * Find a matching (enabled) variation within a variable product.
	 *
	 * @since  3.0.0
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

			// Note not wc_clean here to prevent removal of entities.
			$value = $match_attributes[ $attribute_field_name ];

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
	 * Make sure all variations have a sort order set so they can be reordered correctly.
	 *
	 * 	@param int $parent_id
	 */
	public function sort_all_product_variations( $parent_id ) {
		global $wpdb;
		$ids   = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type='product_variation' AND post_parent=%d AND post_status='publish' ORDER BY menu_order ASC, ID ASC", $parent_id ) );
		$index = 0;

		foreach ( $ids as $id ) {
			$wpdb->update( $wpdb->posts, array( 'menu_order' => ( $index ++ ) ), array( 'ID' => absint( $id ) ) );
		}
	}

	/**
	 * Return a list of related products (using data like categories and IDs).
	 *
	 * @since 3.0.0
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
	 * @since 3.0.0
	 * @param array $cats_array  List of categories IDs.
	 * @param array $tags_array  List of tags IDs.
	 * @param array $exclude_ids Excluded IDs.
	 * @param int   $limit       Limit of results.
	 * @return string
	 */
	public function get_related_products_query( $cats_array, $tags_array, $exclude_ids, $limit ) {
		global $wpdb;

		$include_term_ids            = array_merge( $cats_array, $tags_array );
		$exclude_term_ids            = array();
		$product_visibility_term_ids = wc_get_product_visibility_term_ids();

		if ( $product_visibility_term_ids['exclude-from-catalog'] ) {
			$exclude_term_ids[] = $product_visibility_term_ids['exclude-from-catalog'];
		}

		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && $product_visibility_term_ids['outofstock'] ) {
			$exclude_term_ids[] = $product_visibility_term_ids['outofstock'];
		}

		$query = array(
			'fields' => "
				SELECT DISTINCT ID FROM {$wpdb->posts} p
			",
			'join'   => '',
			'where'  => "
				WHERE 1=1
				AND p.post_status = 'publish'
				AND p.post_type = 'product'

			",
			'limits' => "
				LIMIT " . absint( $limit ) . "
			",
		);

		if ( count( $exclude_term_ids ) ) {
			$query['join']  .= " LEFT JOIN ( SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ( " . implode( ',', array_map( 'absint', $exclude_term_ids ) ) . " ) ) AS exclude_join ON exclude_join.object_id = p.ID";
			$query['where'] .= " AND exclude_join.object_id IS NULL";
		}

		if ( count( $include_term_ids ) ) {
			$query['join']  .= " INNER JOIN ( SELECT object_id FROM {$wpdb->term_relationships} INNER JOIN {$wpdb->term_taxonomy} using( term_taxonomy_id ) WHERE term_id IN ( " . implode( ',', array_map( 'absint', $include_term_ids ) ) . " ) ) AS include_join ON include_join.object_id = p.ID";
		}

		if ( count( $exclude_ids ) ) {
			$query['where'] .= " AND p.ID NOT IN ( " . implode( ',', array_map( 'absint', $exclude_ids ) ) . " )";
		}

		return $query;
	}

	/**
	 * Update a product's stock amount directly.
	 *
	 * Uses queries rather than update_post_meta so we can do this in one query (to avoid stock issues).
	 *
	 * @since  3.0.0 this supports set, increase and decrease.
	 * @param  int
	 * @param  int|null $stock_quantity
	 * @param  string $operation set, increase and decrease.
	 */
	public function update_product_stock( $product_id_with_stock, $stock_quantity = null, $operation = 'set' ) {
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

	/**
	 * Update a product's sale count directly.
	 *
	 * Uses queries rather than update_post_meta so we can do this in one query for performance.
	 *
	 * @since  3.0.0 this supports set, increase and decrease.
	 * @param  int
	 * @param  int|null $quantity
	 * @param  string $operation set, increase and decrease.
	 */
	public function update_product_sales( $product_id, $quantity = null, $operation = 'set' ) {
		global $wpdb;
		add_post_meta( $product_id, 'total_sales', 0, true );

		// Update stock in DB directly
		switch ( $operation ) {
			case 'increase' :
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = meta_value + %f WHERE post_id = %d AND meta_key='total_sales'", $quantity, $product_id ) );
				break;
			case 'decrease' :
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = meta_value - %f WHERE post_id = %d AND meta_key='total_sales'", $quantity, $product_id ) );
				break;
			default :
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = %f WHERE post_id = %d AND meta_key='total_sales'", $quantity, $product_id ) );
				break;
		}

		wp_cache_delete( $product_id, 'post_meta' );
	}

	/**
	 * Update a products average rating meta.
	 *
	 * @since 3.0.0
	 * @param WC_Product $product
	 */
	public function update_average_rating( $product ) {
		update_post_meta( $product->get_id(), '_wc_average_rating', $product->get_average_rating( 'edit' ) );
	}

	/**
	 * Update a products review count meta.
	 *
	 * @since 3.0.0
	 * @param WC_Product $product
	 */
	public function update_review_count( $product ) {
		update_post_meta( $product->get_id(), '_wc_review_count', $product->get_review_count( 'edit' ) );
	}

	/**
	 * Update a products rating counts.
	 *
	 * @since 3.0.0
	 * @param WC_Product $product
	 */
	public function update_rating_counts( $product ) {
		update_post_meta( $product->get_id(), '_wc_rating_count', $product->get_rating_counts( 'edit' ) );
	}

	/**
	 * Get shipping class ID by slug.
	 *
	 * @since 3.0.0
	 * @param $slug string
	 * @return int|false
	 */
	public function get_shipping_class_id_by_slug( $slug ) {
		$shipping_class_term = get_term_by( 'slug', $slug, 'product_shipping_class' );
		if ( $shipping_class_term ) {
			return $shipping_class_term->term_id;
		} else {
			return false;
		}
	}

	/**
	 * Returns an array of products.
	 *
	 * @param  array $args @see wc_get_products
	 * @return array
	 */
	public function get_products( $args = array() ) {
		/**
		 * Generate WP_Query args.
		 */
		$wp_query_args = array(
			'post_type'      => 'variation' === $args['type'] ? 'product_variation' : 'product',
			'post_status'    => $args['status'],
			'posts_per_page' => $args['limit'],
			'meta_query'     => array(),
			'orderby'        => $args['orderby'],
			'order'          => $args['order'],
			'tax_query'      => array(),
		);
		// Do not load unnecessary post data if the user only wants IDs.
		if ( 'ids' === $args['return'] ) {
			$wp_query_args['fields'] = 'ids';
		}

		if ( 'variation' !== $args['type'] ) {
			$wp_query_args['tax_query'][] = array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => $args['type'],
			);
		}

		if ( ! empty( $args['sku'] ) ) {
			$wp_query_args['meta_query'][] = array(
				'key'     => '_sku',
				'value'   => $args['sku'],
				'compare' => 'LIKE',
			);
		}

		if ( ! empty( $args['category'] ) ) {
			$wp_query_args['tax_query'][] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'   => $args['category'],
			);
		}

		if ( ! empty( $args['tag'] ) ) {
			$wp_query_args['tax_query'][] = array(
				'taxonomy' => 'product_tag',
				'field'    => 'slug',
				'terms'   => $args['tag'],
			);
		}

		if ( ! empty( $args['shipping_class'] ) ) {
			$wp_query_args['tax_query'][] = array(
				'taxonomy' => 'product_shipping_class',
				'field'    => 'slug',
				'terms'   => $args['shipping_class'],
			);
		}

		if ( ! is_null( $args['parent'] ) ) {
			$wp_query_args['post_parent'] = absint( $args['parent'] );
		}

		if ( ! is_null( $args['offset'] ) ) {
			$wp_query_args['offset'] = absint( $args['offset'] );
		} else {
			$wp_query_args['paged'] = absint( $args['page'] );
		}

		if ( ! empty( $args['include'] ) ) {
			$wp_query_args['post__in'] = array_map( 'absint', $args['include'] );
		}

		if ( ! empty( $args['exclude'] ) ) {
			$wp_query_args['post__not_in'] = array_map( 'absint', $args['exclude'] );
		}

		if ( ! $args['paginate'] ) {
			$wp_query_args['no_found_rows'] = true;
		}

		// Get results.
		$products = new WP_Query( $wp_query_args );

		if ( 'objects' === $args['return'] ) {
			$return = array_filter( array_map( 'wc_get_product', $products->posts ) );
		} else {
			$return = $products->posts;
		}

		if ( $args['paginate'] ) {
			return (object) array(
				'products'      => $return,
				'total'         => $products->found_posts,
				'max_num_pages' => $products->max_num_pages,
			);
		} else {
			return $return;
		}
	}

	/**
	 * Search product data for a term and return ids.
	 *
	 * @param  string $term
	 * @param  string $type of product
	 * @param  bool $include_variations in search or not
	 * @return array of ids
	 */
	public function search_products( $term, $type = '', $include_variations = false ) {
		global $wpdb;

		$search_fields = array_map( 'wc_clean', apply_filters( 'woocommerce_product_search_fields', array(
			'_sku',
		) ) );
		$like_term     = '%' . $wpdb->esc_like( $term ) . '%';
		$post_types    = $include_variations ? array( 'product', 'product_variation' ) : array( 'product' );
		$post_statuses = current_user_can( 'edit_private_products' ) ? array( 'private', 'publish' ) : array( 'publish' );
		$type_join     = '';
		$type_where    = '';

		if ( $type ) {
			if ( in_array( $type, array( 'virtual', 'downloadable' ) ) ) {
				$type_join  = " LEFT JOIN {$wpdb->postmeta} postmeta_type ON posts.ID = postmeta_type.post_id ";
				$type_where = " AND ( postmeta_type.meta_key = '_{$type}' AND postmeta_type.meta_value = 'yes' ) ";
			}
		}

		$product_ids = $wpdb->get_col(
			$wpdb->prepare( "
				SELECT DISTINCT posts.ID FROM {$wpdb->posts} posts
				LEFT JOIN {$wpdb->postmeta} postmeta ON posts.ID = postmeta.post_id
				$type_join
				WHERE (
					posts.post_title LIKE %s
					OR posts.post_content LIKE %s
					OR (
						postmeta.meta_key = '_sku' AND postmeta.meta_value LIKE %s
					)
				)
				AND posts.post_type IN ('" . implode( "','", $post_types ) . "')
				AND posts.post_status IN ('" . implode( "','", $post_statuses ) . "')
				$type_where
				ORDER BY posts.post_parent ASC, posts.post_title ASC
				",
				$like_term,
				$like_term,
				$like_term
			)
		);

		if ( is_numeric( $term ) ) {
			$post_id   = absint( $term );
			$post_type = get_post_type( $post_id );

			if ( 'product_variation' === $post_type && $include_variations ) {
				$product_ids[] = $post_id;
			} elseif ( 'product' === $post_type ) {
				$product_ids[] = $post_id;
			}

			$product_ids[] = wp_get_post_parent_id( $post_id );
		}

		return wp_parse_id_list( $product_ids );
	}

	/**
	 * Get the product type based on product ID.
	 *
	 * @since 3.0.0
	 * @param int $product_id
	 * @return bool|string
	 */
	public function get_product_type( $product_id ) {
		$post_type = get_post_type( $product_id );
		if ( 'product_variation' === $post_type ) {
			return 'variation';
		} elseif ( 'product' === $post_type ) {
			$terms = get_the_terms( $product_id, 'product_type' );
			return ! empty( $terms ) ? sanitize_title( current( $terms )->name ) : 'simple';
		} else {
			return false;
		}
	}
}
