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
 * @version		2.7.0
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
	 * Merges grouped product data into the parent object.
	 * @param int|WC_Product|object $product Product to init.
	 */
	public function __construct( $product = 0 ) {
		$this->data = array_merge( $this->data, $this->extra_data );
		parent::__construct( $product );
	}

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
	 * @return bool
	 */
	public function is_on_sale() {
		global $wpdb;
		$on_sale = $this->get_children() && 1 === $wpdb->get_var( "SELECT 1 FROM $wpdb->postmeta WHERE meta_key = '_sale_price' AND meta_value > 0 AND post_id IN (" . implode( ',', array_map( 'esc_sql', $this->get_children() ) ) . ");" );
		return apply_filters( 'woocommerce_product_is_on_sale', $on_sale, $this );
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
			if ( '' !== $child->get_price() ) {
				$child_prices[] = 'incl' === $tax_display_mode ? $child->get_price_including_tax() : $child->get_price_excluding_tax();
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
	| CRUD methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Read post data.
	 *
	 * @since 2.7.0
	 */
	protected function read_product_data() {
		parent::read_product_data();

		$this->set_props( array(
			'children' => wp_parse_id_list( get_post_meta( $this->get_id(), '_children', true ) ),
		) );
	}

	/**
	 * Helper method that updates all the post meta for a grouped product.
	 */
	protected function update_post_meta() {
		if ( update_post_meta( $this->get_id(), '_children', $this->get_children() ) ) {
			$child_prices = array();
			foreach ( $this->get_children() as $child_id ) {
				$child = wc_get_product( $child_id );
				if ( $child ) {
					$child_prices[] = $child->get_price();
				}
			}
			$child_prices = array_filter( $child_prices );
			delete_post_meta( $this->get_id(), '_price' );

			if ( ! empty( $child_prices ) ) {
				add_post_meta( $this->get_id(), '_price', min( $child_prices ) );
				add_post_meta( $this->get_id(), '_price', max( $child_prices ) );
			}
		}
		parent::update_post_meta();
	}
}
