<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Grouped Product Class.
 *
 * Grouped products cannot be purchased - they are wrappers for other products.
 *
 * @class 		WC_Product_Grouped
 * @version		3.0.0
 * @package		WooCommerce/Classes/Products
 * @category	Class
 * @author 		WooThemes
 */
class WC_Product_Grouped extends WC_Product {

	/**
	 * Stores product data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'children' => array(),
	);

	/**
	 * Get internal type.
	 * @return string
	 */
	public function get_type() {
		return 'grouped';
	}

	/**
	 * Get the add to cart button text.
	 *
	 * @access public
	 * @return string
	 */
	public function add_to_cart_text() {
		return apply_filters( 'woocommerce_product_add_to_cart_text', __( 'View products', 'woocommerce' ), $this );
	}

	/**
	 * Returns whether or not the product is on sale.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return bool
	 */
	public function is_on_sale( $context = 'view' ) {
		global $wpdb;

		$on_sale = $this->get_children() && null !== $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_sale_price' AND meta_value > 0 AND post_id IN (" . implode( ',', array_map( 'esc_sql', $this->get_children() ) ) . ");" );

		return 'view' === $context ? apply_filters( 'woocommerce_product_is_on_sale', $on_sale, $this ) : $on_sale;
	}

	/**
	 * Returns false if the product cannot be bought.
	 *
	 * @return bool
	 */
	public function is_purchasable() {
		return apply_filters( 'woocommerce_is_purchasable', false, $this );
	}

	/**
	 * Returns the price in html format.
	 *
	 * @access public
	 * @param string $price (default: '')
	 * @return string
	 */
	public function get_price_html( $price = '' ) {
		$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
		$child_prices     = array();

		foreach ( $this->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( $child && '' !== $child->get_price() ) {
				$child_prices[] = 'incl' === $tax_display_mode ? wc_get_price_including_tax( $child ) : wc_get_price_excluding_tax( $child );
			}
		}

		if ( ! empty( $child_prices ) ) {
			$min_price = min( $child_prices );
			$max_price = max( $child_prices );
		} else {
			$min_price = '';
			$max_price = '';
		}

		if ( '' !== $min_price ) {
			$price   = $min_price !== $max_price ? sprintf( _x( '%1$s&ndash;%2$s', 'Price range: from-to', 'woocommerce' ), wc_price( $min_price ), wc_price( $max_price ) ) : wc_price( $min_price );
			$is_free = ( 0 == $min_price && 0 == $max_price );

			if ( $is_free ) {
				$price = apply_filters( 'woocommerce_grouped_free_price_html', __( 'Free!', 'woocommerce' ), $this );
			} else {
				$price = apply_filters( 'woocommerce_grouped_price_html', $price . $this->get_price_suffix(), $this, $child_prices );
			}
		} else {
			$price = apply_filters( 'woocommerce_grouped_empty_price_html', '', $this );
		}

		return apply_filters( 'woocommerce_get_price_html', $price, $this );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	|
	| Methods for getting data from the product object.
	*/

	/**
	 * Return the children of this product.
	 *
	 * @param  string $context
	 * @return array
	 */
	public function get_children( $context = 'view' ) {
		return $this->get_prop( 'children', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	|
	| Methods for getting data from the product object.
	*/

	/**
	 * Return the children of this product.
	 *
	 * @param array $children
	 */
	public function set_children( $children ) {
		$this->set_prop( 'children', array_filter( wp_parse_id_list( (array) $children ) ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Sync with children.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Sync a grouped product with it's children. These sync functions sync
	 * upwards (from child to parent) when the variation is saved.
	 *
	 * @param WC_Product|int $product Product object or ID for which you wish to sync.
	 * @param bool $save If true, the prouduct object will be saved to the DB before returning it.
	 * @return WC_Product Synced product object.
	 */
	public static function sync( $product, $save = true ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			$product = wc_get_product( $product );
		}
		if ( is_a( $product, 'WC_Product_Grouped' ) ) {
			$data_store = WC_Data_Store::load( 'product-' . $product->get_type() );
			$data_store->sync_price( $product );
			if ( $save ) {
				$product->save();
			}
		}
		return $product;
	}
}
