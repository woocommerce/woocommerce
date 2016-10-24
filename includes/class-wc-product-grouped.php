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

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	|
	| Methods for getting data from the product object.
	*/

	/**
	 * Get internal type.
	 * @return string
	 */
	public function get_type() {
		return 'grouped';
	}

	/**
	 * Return the children of this product.
	 *
	 * @return array
	 */
	public function get_children() {
		return $this->data['children'];
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
	 * Returns the price in html format. @todo consider moving to template function
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
		$this->data['children'] = array_filter( wp_parse_id_list( (array) $children ) );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Reads a product from the database and sets its data to the class.
	 *
	 * @since 2.7.0
	 * @param int $id Product ID.
	 */
	public function read( $id ) {
		parent::read( $id );

		$transient_name   = 'wc_product_children_' . $this->get_id();
		$grouped_products = array_filter( wp_parse_id_list( (array) get_transient( $transient_name ) ) );

		if ( empty( $grouped_products ) ) {
			$grouped_products = get_posts( apply_filters( 'woocommerce_grouped_children_args', array(
				'post_parent' 	 => $this->get_id(),
				'post_type'		 => 'product',
				'orderby'		 => 'menu_order',
				'order'			 => 'ASC',
				'fields'		 => 'ids',
				'post_status'	 => 'publish',
				'numberposts'	 => -1,
			) ) );
			set_transient( $transient_name, $grouped_products, DAY_IN_SECONDS * 30 );
		}

		$this->set_props( array(
			'children' => $grouped_products,
		) );
		do_action( 'woocommerce_product_loaded', $this );
		do_action( 'woocommerce_product_' . $this->get_type() . '_loaded', $this );
	}

	/**
	 * Helper method that updates all the post meta for a grouped product.
	 */
	protected function update_post_meta() {
		if ( update_post_meta( $this->get_id(), '_children', $this->get_children() ) ) {
			$child_prices = array();
			foreach ( $this->get_children() as $child_id ) {
				$child = wc_get_product( $child_id );
				$child_prices[] = $child->get_price();
			}
			delete_post_meta( $this->get_id(), '_price' );
			add_post_meta( $this->get_id(), '_price', min( $child_prices ) );
			add_post_meta( $this->get_id(), '_price', max( $child_prices ) );
		}
		parent::update_post_meta();
	}
}
