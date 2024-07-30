<?php
/**
 * Product helpers.
 */

namespace Automattic\WooCommerce\RestApi\UnitTests\Helpers;

defined( 'ABSPATH' ) || exit;

use WC_Product_Simple;
use WC_Product_External;
use WC_Product_Grouped;
use WC_Product_Variable;
use WC_Product_Variation;
use WC_Product_Attribute;
use WC_Cache_Helper;

/**
 * Class ProductHelper.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class ProductHelper {

	/**
	 * Counter to insert unique SKU for concurrent tests.
	 * The starting value ensures no conflicts between existing generators.
	 *
	 * @var int $sku_counter
	 */
	private static $sku_counter = 100000;

	/**
	 * Delete a product.
	 *
	 * @param int $product_id ID to delete.
	 */
	public static function delete_product( $product_id ) {
		$product = \wc_get_product( $product_id );
		if ( $product ) {
			$product->delete( true );
		}
	}

	/**
	 * Create simple product.
	 *
	 * @since 2.3
	 * @param bool $save Save or return object.
	 * @return WC_Product_Simple
	 */
	public static function create_simple_product( $save = true ) {
		$product = new WC_Product_Simple();
		$product->set_props(
			array(
				'name'          => 'Dummy Product',
				'regular_price' => 10,
				'price'         => 10,
				'sku'           => 'DUMMY SKU' . self::$sku_counter,
				'manage_stock'  => false,
				'tax_status'    => 'taxable',
				'downloadable'  => false,
				'virtual'       => false,
				'stock_status'  => 'instock',
				'weight'        => '1.1',
			)
		);

		++self::$sku_counter;

		if ( $save ) {
			$product->save();
			return \wc_get_product( $product->get_id() );
		} else {
			return $product;
		}
	}

	/**
	 * Create external product.
	 *
	 * @since 3.0.0
	 * @return WC_Product_External
	 */
	public static function create_external_product() {
		$product = new WC_Product_External();
		$product->set_props(
			array(
				'name'          => 'Dummy External Product',
				'regular_price' => 10,
				'sku'           => 'DUMMY EXTERNAL SKU',
				'product_url'   => 'https://woocommerce.com',
				'button_text'   => 'Buy external product',
			)
		);
		$product->save();

		return \wc_get_product( $product->get_id() );
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
		$product->set_props(
			array(
				'name' => 'Dummy Grouped Product',
				'sku'  => 'DUMMY GROUPED SKU',
			)
		);
		$product->set_children( array( $simple_product_1->get_id(), $simple_product_2->get_id() ) );
		$product->save();

		return \wc_get_product( $product->get_id() );
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
		$product->set_props(
			array(
				'name' => 'Dummy Variable Product',
				'sku'  => 'DUMMY VARIABLE SKU',
			)
		);

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
		$variation_1->set_props(
			array(
				'parent_id'     => $product->get_id(),
				'sku'           => 'DUMMY SKU VARIABLE SMALL',
				'regular_price' => 10,
			)
		);
		$variation_1->set_attributes( array( 'pa_size' => 'small' ) );
		$variation_1->save();

		$variation_2 = new WC_Product_Variation();
		$variation_2->set_props(
			array(
				'parent_id'     => $product->get_id(),
				'sku'           => 'DUMMY SKU VARIABLE LARGE',
				'regular_price' => 15,
			)
		);
		$variation_2->set_attributes( array( 'pa_size' => 'large' ) );
		$variation_2->save();

		return \wc_get_product( $product->get_id() );
	}

	/**
	 * Create a dummy attribute.
	 *
	 * @since 2.3
	 *
	 * @param string        $raw_name Name of attribute to create.
	 * @param array(string) $terms          Terms to create for the attribute.
	 * @return array
	 */
	public static function create_attribute( $raw_name = 'size', $terms = array( 'small' ) ) {
		global $wpdb, $wc_product_attributes;

		// Make sure caches are clean.
		\delete_transient( 'wc_attribute_taxonomies' );
		if ( method_exists( '\WC_Cache_Helper', 'invalidate_cache_group' ) ) {
			\WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
		} else {
			\WC_Cache_Helper::incr_cache_prefix( 'woocommerce-attributes' );
		}

		// These are exported as labels, so convert the label to a name if possible first.
		$attribute_labels = \wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name' );
		$attribute_name   = \array_search( $raw_name, $attribute_labels, true );

		if ( ! $attribute_name ) {
			$attribute_name = \wc_sanitize_taxonomy_name( $raw_name );
		}

		$attribute_id = \wc_attribute_taxonomy_id_by_name( $attribute_name );

		if ( ! $attribute_id ) {
			$taxonomy_name = \wc_attribute_taxonomy_name( $attribute_name );

			// Degister taxonomy which other tests may have created...
			\unregister_taxonomy( $taxonomy_name );

			$attribute_id = \wc_create_attribute(
				array(
					'name'         => $raw_name,
					'slug'         => $attribute_name,
					'type'         => 'select',
					'order_by'     => 'menu_order',
					'has_archives' => 0,
				)
			);

			// Register as taxonomy.
			\register_taxonomy(
				$taxonomy_name,
				apply_filters( 'woocommerce_taxonomy_objects_' . $taxonomy_name, array( 'product' ) ),
				apply_filters(
					'woocommerce_taxonomy_args_' . $taxonomy_name,
					array(
						'labels'       => array(
							'name' => $raw_name,
						),
						'hierarchical' => false,
						'show_ui'      => false,
						'query_var'    => true,
						'rewrite'      => false,
					)
				)
			);

			// Set product attributes global.
			$wc_product_attributes = array();

			foreach ( \wc_get_attribute_taxonomies() as $taxonomy ) {
				$wc_product_attributes[ \wc_attribute_taxonomy_name( $taxonomy->attribute_name ) ] = $taxonomy;
			}
		}

		$attribute = \wc_get_attribute( $attribute_id );
		$return    = array(
			'attribute_name'     => $attribute->name,
			'attribute_taxonomy' => $attribute->slug,
			'attribute_id'       => $attribute_id,
			'term_ids'           => array(),
		);

		foreach ( $terms as $term ) {
			$result = \term_exists( $term, $attribute->slug );

			if ( ! $result ) {
				$result               = wp_insert_term( $term, $attribute->slug );
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
	 * @param int $attribute_id ID to delete.
	 *
	 * @since 2.3
	 */
	public static function delete_attribute( $attribute_id ) {
		global $wpdb;

		$attribute_id = \absint( $attribute_id );

		$wpdb->query(
			$wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_id = %d", $attribute_id )
		);
	}

	/**
	 * Creates a new product review on a specific product.
	 *
	 * @since 3.0
	 * @param int    $product_id integer Product ID that the review is for.
	 * @param string $review_content string Content to use for the product review.
	 * @return integer Product Review ID.
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
		return \wp_insert_comment( $data );
	}

	/**
	 * A helper function for hooking into save_post during the test_product_meta_save_post test.
	 * @since 3.0.1
	 *
	 * @param int $id ID to update.
	 */
	public static function save_post_test_update_meta_data_direct( $id ) {
		\update_post_meta( $id, '_test2', 'world' );
	}
}
