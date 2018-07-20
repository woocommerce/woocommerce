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
	 */
	public static function delete_product( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product ) {
			$product->delete( true );
		}
	}

	/**
	 * Create simple product.
	 *
	 * @since 2.3
	 * @return WC_Product_Simple
	 */
	public static function create_simple_product() {
		$product = new WC_Product_Simple();
		$product->set_props( array(
			'name'          => 'Dummy Product',
			'regular_price' => 10,
			'sku'           => 'DUMMY SKU',
			'manage_stock'  => false,
			'tax_status'    => 'taxable',
			'downloadable'  => false,
			'virtual'       => false,
			'stock_status'  => 'instock',
			'weight'        => '1.1',
		) );
		$product->save();

		return wc_get_product( $product->get_id() );
	}

	/**
	 * Create external product.
	 *
	 * @since 3.0.0
	 * @return WC_Product_External
	 */
	public static function create_external_product() {
		$product = new WC_Product_External();
		$product->set_props( array(
			'name'          => 'Dummy External Product',
			'regular_price' => 10,
			'sku'           => 'DUMMY EXTERNAL SKU',
			'product_url'   => 'http://woocommerce.com',
			'button_text'   => 'Buy external product',
		) );
		$product->save();

		return wc_get_product( $product->get_id() );
	}

	/**
	 * Create grouped product.
	 *
	 * @since 3.0.0
	 * @return WC_Product_Grouped
	 */
	public static function create_grouped_product() {
		$simple_product_1 = self::create_simple_product();
		$simple_product_2 = self::create_simple_product();
		$product          = new WC_Product_Grouped();
		$product->set_props( array(
			'name'          => 'Dummy Grouped Product',
			'sku'           => 'DUMMY GROUPED SKU',
		) );
		$product->set_children( array( $simple_product_1->get_id(), $simple_product_2->get_id() ) );
		$product->save();

		return wc_get_product( $product->get_id() );
	}

	/**
	 * Create a dummy variation product.
	 *
	 * @since 2.3
	 *
	 * @return WC_Product_Variable
	 */
	public static function create_variation_product() {
		$product = new WC_Product_Variable();
		$product->set_props( array(
			'name' => 'Dummy Variable Product',
			'sku'  => 'DUMMY VARIABLE SKU',
		) );

		$attribute_data = self::create_attribute( 'size', array( 'small', 'large' ) ); // Create all attribute related things.
		$attributes     = array();
		$attribute      = new WC_Product_Attribute();
		$attribute->set_id( $attribute_data['attribute_id'] );
		$attribute->set_name( $attribute_data['attribute_taxonomy'] );
		$attribute->set_options( $attribute_data['term_ids'] );
		$attribute->set_position( 1 );
		$attribute->set_visible( true );
		$attribute->set_variation( true );
		$attributes[] = $attribute;

		$product->set_attributes( $attributes );
		$product->save();

		$variation_1 = new WC_Product_Variation();
		$variation_1->set_props( array(
			'parent_id'     => $product->get_id(),
			'sku'           => 'DUMMY SKU VARIABLE SMALL',
			'regular_price' => 10,
		) );
		$variation_1->set_attributes( array( 'pa_size' => 'small' ) );
		$variation_1->save();

		$variation_2 = new WC_Product_Variation();
		$variation_2->set_props( array(
			'parent_id'     => $product->get_id(),
			'sku'           => 'DUMMY SKU VARIABLE LARGE',
			'regular_price' => 15,
		) );
		$variation_2->set_attributes( array( 'pa_size' => 'large' ) );
		$variation_2->save();

		return wc_get_product( $product->get_id() );
	}

	/**
	 * Create a dummy attribute.
	 *
	 * @since 2.3
	 *
	 * @return array
	 */
	public static function create_attribute( $attribute_name = 'size', $terms = array( 'small' ) ) {
		global $wpdb;

		$attribute = array(
			'attribute_label'   => $attribute_name,
			'attribute_name'    => $attribute_name,
			'attribute_type'    => 'select',
			'attribute_orderby' => 'menu_order',
			'attribute_public'  => 0,
		);
		$wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute );

		$return = array(
			'attribute_name'     => $attribute_name,
			'attribute_taxonomy' => 'pa_' . $attribute_name,
			'attribute_id'       => $wpdb->insert_id,
			'term_ids'           => array(),
		);

		// Register the taxonomy.
		$name  = wc_attribute_taxonomy_name( $attribute_name );
		$label = $attribute_name;

		delete_transient( 'wc_attribute_taxonomies' );

		register_taxonomy( 'pa_' . $attribute_name, array( 'product' ), array(
			'labels' => array(
				'name' => $attribute_name,
			),
		) );

		// Set product attributes global.
		global $wc_product_attributes;
		$wc_product_attributes = array();
		foreach ( wc_get_attribute_taxonomies() as $tax ) {
			if ( $name = wc_attribute_taxonomy_name( $tax->attribute_name ) ) {
				$wc_product_attributes[ $name ] = $tax;
			}
		}

		foreach ( $terms as $term ) {
			$result = term_exists( $term, 'pa_' . $attribute_name );

			if ( ! $result ) {
				$result = wp_insert_term( $term, 'pa_' . $attribute_name );
				$return['term_ids'][] = $result['term_id'];
			} else {
				$return['term_ids'][] = $result['term_id'];
			}
		}

		return $return;
	}

	/**
	 * Delete an attribute.
	 *
	 * @param $attribute_id
	 *
	 * @since 2.3
	 */
	public static function delete_attribute( $attribute_id ) {
		global $wpdb;

		$attribute_id = absint( $attribute_id );

		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_id = $attribute_id" );
	}

	/**
	 * Creates a new product review on a specific product.
	 *
	 * @since 3.0
	 * @param $product_id integer Product ID that the review is for
	 * @param $revieww_content string Content to use for the product review
	 * @return integer Product Review ID
	 */
	public static function create_product_review( $product_id, $review_content = 'Review content here' ) {
		$data = array(
			'comment_post_ID'      => $product_id,
			'comment_author'       => 'admin',
			'comment_author_email' => 'woo@woo.local',
			'comment_author_url'   => '',
			'comment_date'         => '2016-01-01T11:11:11',
			'comment_content'      => $review_content,
			'comment_approved'     => 1,
			'comment_type'         => 'review',
		);
		return wp_insert_comment( $data );
	}

	/**
	 * A helper function for hooking into save_post during the test_product_meta_save_post test.
	 * @since 3.0.1
	 */
	public static function save_post_test_update_meta_data_direct( $id ) {
		update_post_meta( $id, '_test2', 'world' );
	}
}
