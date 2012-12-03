<?php
/**
 * Grouped Product Class
 *
 * Grouped products cannot be purchased - they are wrappers for other products.
 *
 * @class 		WC_Product_Grouped
 * @version		2.0.0
 * @package		WooCommerce/Classes/Products
 * @author 		WooThemes
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Product_Grouped extends WC_Product {

	/** @var array Array of child products/posts/variations. */
	var $children;

	/** @var string The product's total stock, including that of its children. */
	var $total_stock;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @param mixed $product
	 * @param array $args Contains arguments to set up this product
	 */
	function __construct( $product, $args ) {

		parent::__construct( $product );

		$this->product_type = 'grouped';
		$this->product_custom_fields = get_post_custom( $this->id );
		$this->downloadable = 'no';
		$this->virtual = 'no';
		$this->stock        = '';
		$this->stock_status = 'instock';
		$this->manage_stock = 'no';
		$this->weight       = '';
		$this->length       = '';
		$this->width        = '';
		$this->height       = '';

		// Load data from custom fields
		$this->load_product_data( array(
			'sku'                   => '',
			'price'                 => '',
			'visibility'            => 'hidden',
			'sale_price'            => '',
			'regular_price'         => '',
			'upsell_ids'            => array(),
			'crosssell_ids'         => array(),
			'featured'              => 'no'
		) );
	}

    /**
     * Get total stock.
     *
     * This is the stock of parent and children combined.
     *
     * @access public
     * @return int
     */
    function get_total_stock() {

        if ( is_null( $this->total_stock ) ) {

        	$transient_name = 'wc_product_total_stock_' . $this->id;

        	if ( false === ( $this->total_stock = get_transient( $transient_name ) ) ) {
		        $this->total_stock = $this->stock;

				if ( sizeof( $this->get_children() ) > 0 ) {
					foreach ($this->get_children() as $child_id) {
						$stock = get_post_meta( $child_id, '_stock', true );

						if ( $stock != '' ) {
							$this->total_stock += intval( $stock );
						}
					}
				}

				set_transient( $transient_name, $this->total_stock );
			}
		}

		return apply_filters( 'woocommerce_stock_amount', $this->total_stock );
    }

	/**
	 * Return the products children posts.
	 *
	 * @access public
	 * @return array
	 */
	function get_children() {

		if ( ! is_array( $this->children ) ) {

			$this->children = array();

			$transient_name = 'wc_product_children_ids_' . $this->id;

        	if ( false === ( $this->children = get_transient( $transient_name ) ) ) {

		        $this->children = get_posts( 'post_parent=' . $this->id . '&post_type=product&orderby=menu_order&order=ASC&fields=ids&post_status=any&numberposts=-1' );

				set_transient( $transient_name, $this->children );

			}
		}

		return (array) $this->children;
	}


	/**
	 * get_child function.
	 *
	 * @access public
	 * @param mixed $child_id
	 * @return object WC_Product or WC_Product_variation
	 */
	function get_child( $child_id ) {
		return get_product( $child_id );
	}


	/**
	 * Returns whether or not the product has any child product.
	 *
	 * @access public
	 * @return bool
	 */
	function has_child() {
		return sizeof( $this->get_children() ) ? true : false;
	}


	/**
	 * Returns whether or not the product is on sale.
	 *
	 * @access public
	 * @return bool
	 */
	function is_on_sale() {
		if ( $this->has_child() ) {

			foreach ( $this->get_children() as $child_id ) {
				$sale_price = get_post_meta( $child_id, '_sale_price', true );
				if ( $sale_price !== "" && $sale_price >= 0 )
					return true;
			}

		} else {

			if ( $this->sale_price && $this->sale_price == $this->price )
				return true;

		}
		return false;
	}


	/**
	 * Returns false if the product cannot be bought.
	 *
	 * @access public
	 * @return cool
	 */
	function is_purchasable() {
		return apply_filters( 'woocommerce_is_purchasable', false, $this );
	}


	/**
	 * Returns the price in html format.
	 *
	 * @access public
	 * @param string $price (default: '')
	 * @return string
	 */
	function get_price_html( $price = '' ) {

		$child_prices = array();

		foreach ( $this->get_children() as $child_id )
			$child_prices[] = get_post_meta( $child_id, '_price', true );

		$child_prices = array_unique( $child_prices );

		if ( ! empty( $child_prices ) ) {
			$min_price = min( $child_prices );
		} else {
			$min_price = '';
		}

		if ( sizeof( $child_prices ) > 1 ) $price .= $this->get_price_html_from_text();

		$price .= woocommerce_price( $min_price );

		$price = apply_filters( 'woocommerce_grouped_price_html', $price, $this );

		return apply_filters( 'woocommerce_get_price_html', $price, $this );
	}
}