<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Variation Product Data Store: Stored in CPT.
 *
 * @version  2.7.0
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
		return ! in_array( $meta->meta_key, $this->internal_meta_keys ) && 0 !== stripos( $meta->meta_key, 'attribute_' );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Reads a product from the database and sets its data to the class.
	 *
	 * @since 2.7.0
	 * @param WC_Product
	 */
	public function read( &$product ) {
		$product->set_defaults();

		if ( ! $product->get_id() || ! ( $post_object = get_post( $product->get_id() ) ) ) {
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
			'name'              => get_the_title( $post_object ),
			'slug'              => $post_object->post_name,
			'date_created'      => $post_object->post_date,
			'date_modified'     => $post_object->post_modified,
			'status'            => $post_object->post_status,
			'menu_order'        => $post_object->menu_order,
			'reviews_allowed'   => 'open' === $post_object->comment_status,
		) );

		$this->read_product_data( $product );
		$product->set_attributes( wc_get_product_variation_attributes( $product->get_id() ) );

		// Set object_read true once all data is read.
		$product->set_object_read( true );
	}

	/**
	 * Create a new product.
	 *
	 * @since 2.7.0
	 * @param WC_Product
	 */
	public function create( &$product ) {
		$product->set_date_created( current_time( 'timestamp' ) );

		$id = wp_insert_post( apply_filters( 'woocommerce_new_product_variation_data', array(
			'post_type'      => 'product_variation',
			'post_status'    => $product->get_status() ? $product->get_status() : 'publish',
			'post_author'    => get_current_user_id(),
			'post_title'     => get_the_title( $product->get_parent_id() ) . ' &ndash; ' . wc_get_formatted_variation( $product, true, false ),
			'post_content'   => '',
			'post_parent'    => $product->get_parent_id(),
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'menu_order'     => $product->get_menu_order(),
			'post_date'      => date( 'Y-m-d H:i:s', $product->get_date_created() ),
			'post_date_gmt'  => get_gmt_from_date( date( 'Y-m-d H:i:s', $product->get_date_created() ) ),
		) ), true );

		if ( $id && ! is_wp_error( $id ) ) {
			$product->set_id( $id );
			$this->update_post_meta( $product, true );
			$this->update_terms( $product );
			$this->update_attributes( $product );
			$product->save_meta_data();

			do_action( 'woocommerce_create_product_variation', $id );

			$product->apply_changes();
			$this->update_version_and_type( $product );
			$this->update_term_counts( $product );
			$this->clear_caches( $product );
		}
	}

	/**
	 * Updates an existing product.
	 *
	 * @since 2.7.0
	 * @param WC_Product
	 */
	public function update( &$product ) {
		$post_data = array(
			'ID'             => $product->get_id(),
			'post_title'     => get_the_title( $product->get_parent_id() ) . ' &ndash; ' . wc_get_formatted_variation( $product, true, false ),
			'post_parent'    => $product->get_parent_id(),
			'comment_status' => 'closed',
			'post_status'    => $product->get_status() ? $product->get_status() : 'publish',
			'menu_order'     => $product->get_menu_order(),
		);
		wp_update_post( $post_data );
		$this->update_post_meta( $product );
		$this->update_terms( $product );
		$this->update_attributes( $product );
		$product->save_meta_data();

		do_action( 'woocommerce_update_product_variation', $product->get_id() );

		$product->apply_changes();
		$this->update_version_and_type( $product );
		$this->update_term_counts( $product );
		$this->clear_caches( $product );
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Make sure we store the product version (to track data changes).
	 *
	 * @param WC_Product
	 * @since 2.7.0
	 */
	protected function update_version_and_type( &$product ) {
		update_post_meta( $product->get_id(), '_product_version', WC_VERSION );
	}

	/**
	 * Read post data.
	 *
	 * @since 2.7.0
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
			'tax_status'        => get_post_meta( $id, '_tax_status', true ),
			'manage_stock'      => get_post_meta( $id, '_manage_stock', true ),
			'stock_status'      => get_post_meta( $id, '_stock_status', true ),
			'shipping_class_id' => current( $this->get_term_ids( $product, 'product_shipping_class' ) ),
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
			'tax_class'         => get_post_meta( $id, '_tax_class', true ),
		) );

		if ( $product->is_on_sale() ) {
			$product->set_price( $product->get_sale_price() );
		} else {
			$product->set_price( $product->get_regular_price() );
		}

		$product->set_parent_data( array(
			'title'          => get_the_title( $product->get_parent_id() ),
			'sku'            => get_post_meta( $product->get_parent_id(), '_sku', true ),
			'manage_stock'   => get_post_meta( $product->get_parent_id(), '_manage_stock', true ),
			'backorders'     => get_post_meta( $product->get_parent_id(), '_backorders', true ),
			'stock_quantity' => get_post_meta( $product->get_parent_id(), '_stock', true ),
			'weight'         => get_post_meta( $product->get_parent_id(), '_weight', true ),
			'length'         => get_post_meta( $product->get_parent_id(), '_length', true ),
			'width'          => get_post_meta( $product->get_parent_id(), '_width', true ),
			'height'         => get_post_meta( $product->get_parent_id(), '_height', true ),
			'tax_class'      => get_post_meta( $product->get_parent_id(), '_tax_class', true ),
			'image_id'       => get_post_thumbnail_id( $product->get_parent_id() ),
		) );
	}

	/**
	 * For all stored terms in all taxonomies, save them to the DB.
	 *
	 * @since 2.7.0
	 * @param WC_Product
	 */
	protected function update_terms( &$product ) {
		wp_set_post_terms( $product->get_id(), array( $product->get_shipping_class_id( 'edit' ) ), 'product_shipping_class', false );
	}

	/**
	 * Update attribute meta values.
	 *
	 * @since 2.7.0
	 * @param WC_Product
	 */
	protected function update_attributes( &$product ) {
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

	/**
	 * Helper method that updates all the post meta for a product based on it's settings in the WC_Product class.
	 *
	 * @since 2.7.0
	 * @param WC_Product
	 * @param bool $force Force all props to be written even if not changed. This is used during creation.
	 */
	public function update_post_meta( &$product, $force = false ) {
		update_post_meta( $product->get_id(), '_variation_description', $product->get_description() );
		parent::update_post_meta( $product, $force );
	}
}
