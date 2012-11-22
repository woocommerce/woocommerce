<?php
/**
 * Grouped Product Class
 *
 * Grouped products cannot be purchased - they are wrappers for other products.
 *
 * @class 		WC_Product_Grouped
 * @version		1.7.0
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
	 */
	function __construct( $product ) {
		
		parent::__construct( $product );
		
		$this->product_type = 'grouped';
		$this->product_custom_fields = get_post_custom( $this->id );

		// Load data from custom fields
		$this->load_product_data( array(
			'sku'			=> '',
			'downloadable' 	=> 'no',
			'virtual' 		=> 'no',
			'price' 		=> '',
			'visibility'	=> 'hidden',
			'stock'			=> 0,
			'stock_status'	=> 'instock',
			'backorders'	=> 'no',
			'manage_stock'	=> 'no',
			'sale_price'	=> '',
			'regular_price' => '',
			'weight'		=> '',
			'length'		=> '',
			'width'		=> '',
			'height'		=> '',
			'tax_status'	=> 'taxable',
			'tax_class'		=> '',
			'upsell_ids'	=> array(),
			'crosssell_ids' => array(),
			'sale_price_dates_from' => '',
			'sale_price_dates_to' 	=> '',
			'featured'		=> 'no'
		) );
		
		$this->check_sale_price();
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
		if ($this->has_child()) :

			foreach ($this->get_children() as $child_id) :
				$sale_price = get_post_meta( $child_id, '_sale_price', true );
				if ( $sale_price!=="" && $sale_price >= 0 ) return true;
			endforeach;

		else :

			if ( $this->sale_price && $this->sale_price==$this->price ) return true;

		endif;
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

		foreach ( $this->get_children() as $child_id ) $child_prices[] = get_post_meta( $child_id, '_price', true );

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


    /**
     * Checks sale data to see if the product is due to go on sale/sale has expired, and updates the main price.
     *
     * @access public
     * @return void
     */
    function check_sale_price() {

    	if ( $this->sale_price_dates_from && $this->sale_price_dates_from < current_time('timestamp') ) {

    		if ( $this->sale_price && $this->price !== $this->sale_price ) {

    			// Update price
    			$this->price = $this->sale_price;
    			update_post_meta( $this->id, '_price', $this->price );

    			// Grouped product prices and sale status are affected by children
    			$this->grouped_product_sync();
    		}

    	}

    	if ( $this->sale_price_dates_to && $this->sale_price_dates_to < current_time('timestamp') ) {

    		if ( $this->regular_price && $this->price !== $this->regular_price ) {

    			$this->price = $this->regular_price;
    			update_post_meta( $this->id, '_price', $this->price );

				// Sale has expired - clear the schedule boxes
				update_post_meta( $this->id, '_sale_price', '' );
				update_post_meta( $this->id, '_sale_price_dates_from', '' );
				update_post_meta( $this->id, '_sale_price_dates_to', '' );

				// Grouped product prices and sale status are affected by children
    			$this->grouped_product_sync();
			}

    	}
    }


	/**
	 * Sync grouped products with the childs lowest price (so they can be sorted by price accurately).
	 *
	 * @access public
	 * @return void
	 */
	function grouped_product_sync() {
		global $wpdb, $woocommerce;
		$post_parent = $wpdb->get_var( $wpdb->prepare( "SELECT post_parent FROM $wpdb->posts WHERE ID = %d;"), $this->id );

		if (!$post_parent) return;

		$children_by_price = get_posts( array(
			'post_parent' 	=> $post_parent,
			'orderby' 	=> 'meta_value_num',
			'order'		=> 'asc',
			'meta_key'	=> '_price',
			'posts_per_page' => 1,
			'post_type' 	=> 'product',
			'fields' 		=> 'ids'
		));
		if ($children_by_price) :
			foreach ($children_by_price as $child) :
				$child_price = get_post_meta($child, '_price', true);
				update_post_meta( $post_parent, '_price', $child_price );
			endforeach;
		endif;

		$woocommerce->clear_product_transients( $this->id );
	}
}