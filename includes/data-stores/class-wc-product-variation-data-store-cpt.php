<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Variation Product Data Store: Stored in CPT.
 *
 * @version  3.0.0
 * @category Class
 * @author   WooThemes
 */
class WC_Product_Variation_Data_Store_CPT extends WC_Product_Data_Store_CPT implements WC_Object_Data_Store_Interface {

	/**
	 * Callback to remove unwanted meta data.
	 *
	 * @param object $meta
	 * @return bool false if excluded.
	 */
	protected function exclude_internal_meta_keys( $meta ) {
		return ! in_array( $meta->meta_key, $this->internal_meta_keys ) && 0 !== stripos( $meta->meta_key, 'attribute_' ) && 0 !== stripos( $meta->meta_key, 'wp_' );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Reads a product from the database and sets its data to the class.
	 *
	 * @since 3.0.0
	 * @param WC_Product
	 */
	public function read( &$product ) {
		$product->set_defaults();

		if ( ! $product->get_id() || ! ( $post_object = get_post( $product->get_id() ) ) || 'product_variation' !== $post_object->post_type ) {
			return;
		}

		$id = $product->get_id();
		$product->set_parent_id( $post_object->post_parent );
		$parent_id = $product->get_parent_id();

		// The post doesn't have a parent id, therefore its invalid and we should prevent this being created.
		if ( empty( $parent_id ) ) {
			throw new Exception( sprintf( 'No parent product set for variation #%d', $product->get_id() ), 422 );
		}

		// The post parent is not a valid variable product so we should prevent this being created.
		if ( 'product' !== get_post_type( $product->get_parent_id() ) ) {
			throw new Exception( sprintf( 'Invalid parent for variation #%d', $product->get_id() ), 422 );
		}

		$product->set_props( array(
			'name'            => $post_object->post_title,
			'slug'            => $post_object->post_name,
			'date_created'    => 0 < $post_object->post_date_gmt ? wc_string_to_timestamp( $post_object->post_date_gmt ) : null,
			'date_modified'   => 0 < $post_object->post_modified_gmt ? wc_string_to_timestamp( $post_object->post_modified_gmt ) : null,
			'status'          => $post_object->post_status,
			'menu_order'      => $post_object->menu_order,
			'reviews_allowed' => 'open' === $post_object->comment_status,
		) );

		$this->read_product_data( $product );
		$this->read_extra_data( $product );
		$product->set_attributes( wc_get_product_variation_attributes( $product->get_id() ) );

		/**
		 * If a variation title is not in sync with the parent e.g. saved prior to 3.0, or if the parent title has changed, detect here and update.
		 */
		if ( version_compare( get_post_meta( $product->get_id(), '_product_version', true ), '3.0', '<' ) && 0 !== strpos( $post_object->post_title, get_post_field( 'post_title', $product->get_parent_id() ) ) ) {
			global $wpdb;

			$new_title = $this->generate_product_title( $product );
			$product->set_name( $new_title );
			$wpdb->update( $wpdb->posts, array( 'post_title' => $new_title ), array( 'ID' => $product->get_id() ) );
			clean_post_cache( $product->get_id() );
		}

		// Set object_read true once all data is read.
		$product->set_object_read( true );
	}

	/**
	 * Create a new product.
	 *
	 * @since 3.0.0
	 * @param WC_Product
	 */
	public function create( &$product ) {
		if ( ! $product->get_date_created() ) {
			$product->set_date_created( current_time( 'timestamp', true ) );
		}

		$id = wp_insert_post( apply_filters( 'woocommerce_new_product_variation_data', array(
			'post_type'      => 'product_variation',
			'post_status'    => $product->get_status() ? $product->get_status() : 'publish',
			'post_author'    => get_current_user_id(),
			'post_title'     => $this->generate_product_title( $product ),
			'post_content'   => '',
			'post_parent'    => $product->get_parent_id(),
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'menu_order'     => $product->get_menu_order(),
			'post_date'      => gmdate( 'Y-m-d H:i:s', $product->get_date_created( 'edit' )->getOffsetTimestamp() ),
			'post_date_gmt'  => gmdate( 'Y-m-d H:i:s', $product->get_date_created( 'edit' )->getTimestamp() ),
		) ), true );

		if ( $id && ! is_wp_error( $id ) ) {
			$product->set_id( $id );

			$this->update_post_meta( $product, true );
			$this->update_terms( $product, true );
			$this->update_attributes( $product, true );
			$this->handle_updated_props( $product );

			$product->save_meta_data();
			$product->apply_changes();

			$this->update_version_and_type( $product );

			$this->clear_caches( $product );

			do_action( 'woocommerce_new_product_variation', $id );
		}
	}

	/**
	 * Updates an existing product.
	 *
	 * @since 3.0.0
	 * @param WC_Product
	 */
	public function update( &$product ) {
		$product->save_meta_data();
		$changes = $product->get_changes();
		$title   = $this->generate_product_title( $product );

		// Only update the post when the post data changes.
		if ( $title !== $product->get_name( 'edit' ) || array_intersect( array( 'parent_id', 'status', 'menu_order', 'date_created', 'date_modified' ), array_keys( $changes ) ) ) {
			wp_update_post( array(
				'ID'                => $product->get_id(),
				'post_title'        => $title,
				'post_parent'       => $product->get_parent_id( 'edit' ),
				'comment_status'    => 'closed',
				'post_status'       => $product->get_status( 'edit' ) ? $product->get_status( 'edit' ) : 'publish',
				'menu_order'        => $product->get_menu_order( 'edit' ),
				'post_date'         => gmdate( 'Y-m-d H:i:s', $product->get_date_created( 'edit' )->getOffsetTimestamp() ),
				'post_date_gmt'     => gmdate( 'Y-m-d H:i:s', $product->get_date_created( 'edit' )->getTimestamp() ),
				'post_modified'     => isset( $changes['date_modified'] ) ? gmdate( 'Y-m-d H:i:s', $product->get_date_modified( 'edit' )->getOffsetTimestamp() ) : current_time( 'mysql' ),
				'post_modified_gmt' => isset( $changes['date_modified'] ) ? gmdate( 'Y-m-d H:i:s', $product->get_date_modified( 'edit' )->getTimestamp() ) : current_time( 'mysql', 1 ),
			) );
			$product->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		}

		$this->update_post_meta( $product );
		$this->update_terms( $product );
		$this->update_attributes( $product );
		$this->handle_updated_props( $product );

		$product->apply_changes();

		$this->update_version_and_type( $product );

		$this->clear_caches( $product );

		do_action( 'woocommerce_update_product_variation', $product->get_id() );
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Generates a title with attribute information for a variation.
	 * Products will get a title of the form "Name - Value, Value" or just "Name".
	 *
	 * @since 3.0.0
	 * @param WC_Product
	 * @return string
	 */
	protected function generate_product_title( $product ) {
		$attributes = (array) $product->get_attributes();

		// Don't include attributes if the product has 3+ attributes.
		$should_include_attributes = count( $attributes ) < 3;

		// Don't include attributes if an attribute name has 2+ words.
		if ( $should_include_attributes ) {
			foreach ( $attributes as $name => $value ) {
				if ( false !== strpos( $name, '-' ) ) {
					$should_include_attributes = false;
					break;
				}
			}
		}

		$should_include_attributes = apply_filters( 'woocommerce_product_variation_title_include_attributes', $should_include_attributes, $product );
		$separator                 = apply_filters( 'woocommerce_product_variation_title_attributes_separator', ' - ', $product );
		$title_base                = get_post_field( 'post_title', $product->get_parent_id() );
		$title_suffix              = $should_include_attributes ? wc_get_formatted_variation( $product, true, false ) : '';

		return apply_filters( 'woocommerce_product_variation_title', rtrim( $title_base . $separator . $title_suffix, $separator ), $product, $title_base, $title_suffix );
	}

	/**
	 * Make sure we store the product version (to track data changes).
	 *
	 * @param WC_Product
	 * @since 3.0.0
	 */
	protected function update_version_and_type( &$product ) {
		update_post_meta( $product->get_id(), '_product_version', WC_VERSION );
	}

	/**
	 * Read post data.
	 *
	 * @since 3.0.0
	 * @param WC_Product
	 */
	protected function read_product_data( &$product ) {
		$id = $product->get_id();
		$product->set_props( array(
			'description'       => get_post_meta( $id, '_variation_description', true ),
			'regular_price'     => get_post_meta( $id, '_regular_price', true ),
			'sale_price'        => get_post_meta( $id, '_sale_price', true ),
			'date_on_sale_from' => get_post_meta( $id, '_sale_price_dates_from', true ),
			'date_on_sale_to'   => get_post_meta( $id, '_sale_price_dates_to', true ),
			'manage_stock'      => get_post_meta( $id, '_manage_stock', true ),
			'stock_status'      => get_post_meta( $id, '_stock_status', true ),
			'shipping_class_id' => current( $this->get_term_ids( $id, 'product_shipping_class' ) ),
			'virtual'           => get_post_meta( $id, '_virtual', true ),
			'downloadable'      => get_post_meta( $id, '_downloadable', true ),
			'downloads'         => array_filter( (array) get_post_meta( $id, '_downloadable_files', true ) ),
			'gallery_image_ids' => array_filter( explode( ',', get_post_meta( $id, '_product_image_gallery', true ) ) ),
			'download_limit'    => get_post_meta( $id, '_download_limit', true ),
			'download_expiry'   => get_post_meta( $id, '_download_expiry', true ),
			'image_id'          => get_post_thumbnail_id( $id ),
			'backorders'        => get_post_meta( $id, '_backorders', true ),
			'sku'               => get_post_meta( $id, '_sku', true ),
			'stock_quantity'    => get_post_meta( $id, '_stock', true ),
			'weight'            => get_post_meta( $id, '_weight', true ),
			'length'            => get_post_meta( $id, '_length', true ),
			'width'             => get_post_meta( $id, '_width', true ),
			'height'            => get_post_meta( $id, '_height', true ),
			'tax_class'         => ! metadata_exists( 'post', $id, '_tax_class' ) ? 'parent' : get_post_meta( $id, '_tax_class', true ),
		) );

		if ( $product->is_on_sale( 'edit' ) ) {
			$product->set_price( $product->get_sale_price( 'edit' ) );
		} else {
			$product->set_price( $product->get_regular_price( 'edit' ) );
		}

		$parent_object = get_post( $product->get_parent_id() );
		$product->set_parent_data( array(
			'title'             => $parent_object->post_title,
			'sku'               => get_post_meta( $product->get_parent_id(), '_sku', true ),
			'manage_stock'      => get_post_meta( $product->get_parent_id(), '_manage_stock', true ),
			'backorders'        => get_post_meta( $product->get_parent_id(), '_backorders', true ),
			'stock_quantity'    => get_post_meta( $product->get_parent_id(), '_stock', true ),
			'weight'            => get_post_meta( $product->get_parent_id(), '_weight', true ),
			'length'            => get_post_meta( $product->get_parent_id(), '_length', true ),
			'width'             => get_post_meta( $product->get_parent_id(), '_width', true ),
			'height'            => get_post_meta( $product->get_parent_id(), '_height', true ),
			'tax_class'         => get_post_meta( $product->get_parent_id(), '_tax_class', true ),
			'shipping_class_id' => absint( current( $this->get_term_ids( $product->get_parent_id(), 'product_shipping_class' ) ) ),
			'image_id'          => get_post_thumbnail_id( $product->get_parent_id() ),
		) );

		// Pull data from the parent when there is no user-facing way to set props.
		$product->set_sold_individually( get_post_meta( $product->get_parent_id(), '_sold_individually', true ) );
		$product->set_tax_status( get_post_meta( $product->get_parent_id(), '_tax_status', true ) );
		$product->set_cross_sell_ids( get_post_meta( $product->get_parent_id(), '_crosssell_ids', true ) );
	}

	/**
	 * For all stored terms in all taxonomies, save them to the DB.
	 *
	 * @since 3.0.0
	 * @param WC_Product
	 * @param bool Force update. Used during create.
	 */
	protected function update_terms( &$product, $force = false ) {
		$changes = $product->get_changes();

		if ( $force || array_key_exists( 'shipping_class_id', $changes ) ) {
			wp_set_post_terms( $product->get_id(), array( $product->get_shipping_class_id( 'edit' ) ), 'product_shipping_class', false );
		}
	}

	/**
	 * Update attribute meta values.
	 *
	 * @since 3.0.0
	 * @param WC_Product
	 * @param bool Force update. Used during create.
	 */
	protected function update_attributes( &$product, $force = false ) {
		$changes = $product->get_changes();

		if ( $force || array_key_exists( 'attributes', $changes ) ) {
			global $wpdb;
			$attributes             = $product->get_attributes();
			$updated_attribute_keys = array();
			foreach ( $attributes as $key => $value ) {
				update_post_meta( $product->get_id(), 'attribute_' . $key, $value );
				$updated_attribute_keys[] = 'attribute_' . $key;
			}

			// Remove old taxonomies attributes so data is kept up to date - first get attribute key names.
			$delete_attribute_keys = $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE 'attribute_%%' AND meta_key NOT IN ( '" . implode( "','", array_map( 'esc_sql', $updated_attribute_keys ) ) . "' ) AND post_id = %d;", $product->get_id() ) );

			foreach ( $delete_attribute_keys as $key ) {
				delete_post_meta( $product->get_id(), $key );
			}
		}
	}

	/**
	 * Helper method that updates all the post meta for a product based on it's settings in the WC_Product class.
	 *
	 * @since 3.0.0
	 * @param WC_Product
	 * @param bool Force update. Used during create.
	 */
	public function update_post_meta( &$product, $force = false ) {
		$meta_key_to_props = array(
			'_variation_description' => 'description',
		);

		$props_to_update = $force ? $meta_key_to_props : $this->get_props_to_update( $product, $meta_key_to_props );

		foreach ( $props_to_update as $meta_key => $prop ) {
			$value   = $product->{"get_$prop"}( 'edit' );
			$updated = update_post_meta( $product->get_id(), $meta_key, $value );
			if ( $updated ) {
				$this->updated_props[] = $prop;
			}
		}

		parent::update_post_meta( $product, $force );
	}
}
