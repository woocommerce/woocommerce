<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Product Data Store.
 *
 * @version  2.7.0
 * @category Class
 * @author   WooThemes
 */
class WC_Product_Data_Store_Posts extends WC_Data_Store_Posts implements WC_Object_Data_Store {

	/**
	 * Stores a new product in the database.
	 * @param WC_Product Object
	 */
	public function create( $product ) {

	}

	/**
	 * Read a product from the database.
	 * @param WC_Product
	 */
	public function read( &$product ) {
		$product->set_defaults();

		if ( ! $product->get_id() || ! ( $post_object = get_post( $product->get_id() ) ) ) {
			// @todo throw an error? set to empty object? null?
			return;
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
			'featured'           => get_post_meta( $id, '_featured', true ),
			'catalog_visibility' => get_post_meta( $id, '_visibility', true ),
			'sku'                => get_post_meta( $id, '_sku', true ),
			'regular_price'      => get_post_meta( $id, '_regular_price', true ),
			'sale_price'         => get_post_meta( $id, '_sale_price', true ),
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
			'category_ids'       => $this->get_term_ids( $id, 'product_cat' ),
			'tag_ids'            => $this->get_term_ids( $id, 'product_tag' ),
			'shipping_class_id'  => current( $this->get_term_ids( $id, 'product_shipping_class' ) ),
			'virtual'            => get_post_meta( $id, '_virtual', true ),
			'downloadable'       => get_post_meta( $id, '_downloadable', true ),
			'downloads'          => array_filter( (array) get_post_meta( $id, '_downloadable_files', true ) ),
			'gallery_image_ids'  => array_filter( explode( ',', get_post_meta( $id, '_product_image_gallery', true ) ) ),
			'download_limit'     => get_post_meta( $id, '_download_limit', true ),
			'download_expiry'    => get_post_meta( $id, '_download_expiry', true ),
			'image_id'           => get_post_thumbnail_id( $id ),
		) );

		if ( $product->is_on_sale() ) {
			$product->set_price( $product->get_sale_price() );
		} else {
			$product->set_price( $product->get_regular_price() );
		}

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

		// Gets extra data associated with the product.
		// Like button text or product URL for external products.
		foreach ( $product->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;
			if ( is_callable( array( $product, $function ) ) ) {
				$product->{$function}( get_post_meta( $product->get_id(), '_' . $key, true ) );
			}
		}

		// Set object_read true once all data is read.
		$product->set_object_read( true );
	}

	/**
	 * Updates a product in the database.
	 * @param WC_Product Object
	 */
	public function update( $data ) {

	}

	/**
	 * Deletes a product from the database.
	 * @param WC_Product Object
	 * @param bool $force_delete True to permently delete, false to trash.
	 */
	public function delete( $data, $force_delete = false ) {

	}
}
