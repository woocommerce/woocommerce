<?php
/**
 * Legacy Abstract Product
 *
 * Legacy and deprecated functions are here to keep the WC_Abstract_Product
 * clean.
 * This class will be removed in future versions.
 *
 * @version  2.7.0
 * @package  WooCommerce/Abstracts
 * @category Abstract Class
 * @author   WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy Abstract Product class.
 */
abstract class WC_Abstract_Legacy_Product extends WC_Data {

	/**
	 * Get and return related products.
	 * @deprecated 2.7.0 Use wc_get_related_products instead.
	 */
	public function get_related( $limit = 5 ) {
		_deprecated_function( 'WC_Product::get_related', '2.7', 'wc_get_related_products' );
		return wc_get_related_products( $this->get_id(), $limit );
	}

	/**
	 * Retrieves related product terms.
	 * @deprecated 2.7.0 Use wc_get_related_terms instead.
	 */
	protected function get_related_terms( $term ) {
		_deprecated_function( 'WC_Product::get_related_terms', '2.7', 'wc_get_related_terms' );
		return array_merge( array( 0 ), wc_get_related_terms( $this->get_id(), $term ) );
	}

	/**
	 * Builds the related posts query.
	 * @deprecated 2.7.0 Use wc_get_related_products_query instead.
	 */
	protected function build_related_query( $cats_array, $tags_array, $exclude_ids, $limit ) {
		_deprecated_function( 'WC_Product::build_related_query', '2.7', 'wc_get_related_products_query' );
		return wc_get_related_products_query( $cats_array, $tags_array, $exclude_ids, $limit );
	}




	/**
	 * The product's type (simple, variable etc).
	 *
	 * @var string
	 */
	public $product_type = null;

	/**
	 * Product shipping class.
	 *
	 * @var string
	 */
	protected $shipping_class = '';

	/**
	 * ID of the shipping class this product has.
	 *
	 * @var int
	 */
	protected $shipping_class_id = 0;

	/** @public string The product's total stock, including that of its children. */
	public $total_stock;


	/**
	 * Magic __isset method for backwards compatibility. Legacy properties which could be accessed directly in the past.
	 *
	 * @param  string $key Key name.
	 * @return bool
	 */
	public function __isset( $key ) {
		return metadata_exists( 'post', $this->id, '_' . $key );
	}

	/**
	 * Magic __get method for backwards compatibility.Maps legacy vars to new getters.
	 *
	 * @param  string $key Key name.
	 * @return mixed
	 */
	public function __get( $key ) {
		_doing_it_wrong( $key, __( 'Product properties should not be accessed directly.', 'woocommerce' ), '2.7' );

		switch ( $key ) {
			case 'id' :
				$value = $this->get_id();
				break;
			case 'product_attributes' :
				$value = isset( $this->data['attributes'] ) ? $this->data['attributes'] : '';
				break;
			case 'visibility' :
				$value = $this->get_catalog_visibility();
				break;
			case 'sale_price_dates_from' :
				$value = $this->get_date_on_sale_from();
				break;
			case 'sale_price_dates_to' :
				$value = $this->get_date_on_sale_to();
				break;
			case 'post' :
				$value = get_post( $this->get_id() );
				break;

			case 'product_type' :  // @todo What do we do with 3rd party use of product_type now it's hardcoded?
				$value = $this->get_type();
				break;



			default :
				$value = get_post_meta( $this->id, '_' . $key, true );

				// Get values or default if not set.
				if ( in_array( $key, array( 'downloadable', 'virtual', 'backorders', 'manage_stock', 'featured', 'sold_individually' ) ) ) {
					$value = $value ? $value : 'no';
				} elseif ( in_array( $key, array( 'product_attributes', 'crosssell_ids', 'upsell_ids' ) ) ) {
					$value = $value ? $value : array();
				} elseif ( 'stock' === $key ) {
					$value = $value ? $value : 0;
				} elseif ( 'stock_status' === $key ) {
					$value = $value ? $value : 'instock';
				} elseif ( 'tax_status' === $key ) {
					$value = $value ? $value : 'taxable';
				}
				break;
		}

		return $value;
	}

	/**
	 * Get the product's post data.
	 *
	 * @deprecated 2.7.0
	 * @return WP_Post
	 */
	public function get_post_data() {
		return $this->post;
	}

	/**
	 * Get the title of the post.
	 *
	 * @deprecated 2.7.0
	 * @return string
	 */
	public function get_title() {
		return apply_filters( 'woocommerce_product_title', $this->post ? $this->post->post_title : '', $this );
	}

	/**
	 * Get the parent of the post.
	 *
	 * @deprecated 2.7.0
	 * @return int
	 */
	public function get_parent() {
		return apply_filters( 'woocommerce_product_parent', absint( $this->post->post_parent ), $this );
	}
}
