<?php

/**
 * Class WC_Helper_Product.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class WC_Helper_Product {

	/**
	 * Delete a product.
	 *
	 * @param $product_id
	 *
	 * @todo check for variations, attributes, etc.
	 */
	public static function delete_product( $product_id ) {

		/**
		 * @todo check for variations, attributes, etc.
		 */

		// Delete the psot
		wp_delete_post( $product_id, true );

	}

	/**
	 * Create simple product.
	 *
	 * @since 2.3
	 *
	 * @return WC_Product_Simple
	 */
	public static function create_simple_product() {

		// Create the product
		$product = wp_insert_post( array(
			'post_title'  => 'Dummy Product',
			'post_type'   => 'product',
			'post_status' => 'publish'
		) );
		update_post_meta( $product, '_price', '10' );
		update_post_meta( $product, '_regular_price', '10' );
		update_post_meta( $product, '_sale_price', '' );
		update_post_meta( $product, '_sku', 'DUMMY SKU' );
		update_post_meta( $product, '_manage_stock', 'no' );
		update_post_meta( $product, '_tax_status', 'taxable' );
		update_post_meta( $product, '_downloadable', 'no' );
		update_post_meta( $product, '_virtual', 'taxable' );
		update_post_meta( $product, '_visibility', 'visible' );
		update_post_meta( $product, '_stock_status', 'instock' );

		return new WC_Product_Simple( $product );
	}

	/**
	 * Create a dummy simple product.
	 *
	 * @since 2.3
	 *
	 * @return WC_Product_Variable
	 */
	public static function create_variation_product() {
		global $wpdb;

		// Create all attribute related things
		$attribute_data = self::create_attribute();

		// Create the product
		$product_id = wp_insert_post( array(
			'post_title'  => 'Dummy Product',
			'post_type'   => 'product',
			'post_status' => 'publish'
		) );

		// Set it as variable.
		wp_set_object_terms( $product_id, 'variable', 'product_type' );

		// Price related meta
		update_post_meta( $product_id, '_price', '10' );
		update_post_meta( $product_id, '_min_variation_price', '10' );
		update_post_meta( $product_id, '_max_variation_price', '15' );
		update_post_meta( $product_id, '_min_variation_regular_price', '10' );
		update_post_meta( $product_id, '_max_variation_regular_price', '15' );

		// General meta
		update_post_meta( $product_id, '_sku', 'DUMMY SKU' );
		update_post_meta( $product_id, '_manage_stock', 'no' );
		update_post_meta( $product_id, '_tax_status', 'taxable' );
		update_post_meta( $product_id, '_downloadable', 'no' );
		update_post_meta( $product_id, '_virtual', 'taxable' );
		update_post_meta( $product_id, '_visibility', 'visible' );
		update_post_meta( $product_id, '_stock_status', 'instock' );

		// Attributes
		update_post_meta( $product_id, '_default_attributes', array() );
		update_post_meta( $product_id, '_product_attributes', array(
			'pa_size' => array(
				'name'         => 'pa_size',
				'value'        => '',
				'position'     => '1',
				'is_visible'   => 0,
				'is_variation' => 1,
				'is_taxonomy'  => 1
			)
		) );

		// Link the product to the attribute
		$wpdb->insert( $wpdb->prefix . 'term_relationships', array(
			'object_id'        => $product_id,
			'term_taxonomy_id' => $attribute_data['term_taxonomy_id'],
			'term_order'       => 0
		) );
		$return['term_taxonomy_id'] = $wpdb->insert_id;

		// Create the variation
		$variation_id = wp_insert_post( array(
			'post_title'  => 'Variation #' . ( $product_id + 1 ) . ' of Dummy Product',
			'post_type'   => 'product_variation',
			'post_parent' => $product_id,
			'post_status' => 'publish'
		) );

		// Price related meta
		update_post_meta( $variation_id, '_price', '10' );
		update_post_meta( $variation_id, '_regular_price', '10' );

		// General meta
		update_post_meta( $variation_id, '_sku', 'DUMMY SKU VARIABLE SMALL' );
		update_post_meta( $variation_id, '_manage_stock', 'no' );
		update_post_meta( $variation_id, '_downloadable', 'no' );
		update_post_meta( $variation_id, '_virtual', 'taxable' );
		update_post_meta( $variation_id, '_stock_status', 'instock' );

		// Attribute meta
		update_post_meta( $variation_id, 'attribute_pa_size', 'small' );

		// Create the variation
		$variation_id = wp_insert_post( array(
			'post_title'  => 'Variation #' . ( $product_id + 2 ) . ' of Dummy Product',
			'post_type'   => 'product_variation',
			'post_parent' => $product_id,
			'post_status' => 'publish'
		) );

		// Price related meta
		update_post_meta( $variation_id, '_price', '15' );
		update_post_meta( $variation_id, '_regular_price', '15' );

		// General meta
		update_post_meta( $variation_id, '_sku', 'DUMMY SKU VARIABLE SMALL' );
		update_post_meta( $variation_id, '_manage_stock', 'no' );
		update_post_meta( $variation_id, '_downloadable', 'no' );
		update_post_meta( $variation_id, '_virtual', 'taxable' );
		update_post_meta( $variation_id, '_stock_status', 'instock' );

		// Attribute meta
		update_post_meta( $variation_id, 'attribute_pa_size', 'large' );

		// Add the variation meta to the main product

		update_post_meta( $product_id, '_max_price_variation_id', $variation_id );

		return new WC_Product_Variable( $product_id );
	}

	/**
	 * Create a dummy attribute.
	 *
	 * @since 2.3
	 *
	 * @return array
	 */
	public static function create_attribute() {
		global $wpdb;

		$return = array();

		$attribute_name = 'dummyattribute';

		// Create attribute
		$attribute = array(
			'attribute_label'   => $attribute_name,
			'attribute_name'    => $attribute_name,
			'attribute_type'    => 'select',
			'attribute_orderby' => 'menu_order',
			'attribute_public'  => 0,
		);
		$wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute );
		$return['attribute_id'] = $wpdb->insert_id;

		// Register the taxonomy
		$name  = wc_attribute_taxonomy_name( $attribute_name );
		$label = $attribute_name;

		// Add the term
		$wpdb->insert( $wpdb->prefix . 'terms', array(
			'name'       => 'small',
			'slug'       => 'small',
			'term_group' => 0
		), array(
			'%s',
			'%s',
			'%d'
		) );
		$return['term_id'] = $wpdb->insert_id;

		// Add the term_taxonomy
		$wpdb->insert( $wpdb->prefix . 'term_taxonomy', array(
			'term_id'     => $return['term_id'],
			'taxonomy'    => 'pa_dummyattribute',
			'description' => '',
			'parent'      => 0,
			'count'       => 1
		) );
		$return['term_taxonomy_id'] = $wpdb->insert_id;

		// Delete transient
		delete_transient( 'wc_attribute_taxonomies' );

		return $return;
	}

	/**
	 * Delete an attribute.
	 *
	 * @param $attribute_id
	 *
	 * @since 2.3
	 *
	 * @todo clean up all term/taxonomy/etc data
	 */
	public static function delete_attribute( $attribute_id ) {
		global $wpdb;

		$attribute_id = absint( $attribute_id );

		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_id = $attribute_id" );
	}

}
