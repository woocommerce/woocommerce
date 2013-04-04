<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Variable Product Class
 *
 * The WooCommerce product class handles individual product data.
 *
 * @class 		WC_Product_Variable
 * @version		2.0.0
 * @package		WooCommerce/Classes/Products
 * @category	Class
 * @author 		WooThemes
 */
class WC_Product_Variable extends WC_Product {

	/** @public array Array of child products/posts/variations. */
	public $children;

	/** @public string The product's total stock, including that of its children. */
	public $total_stock;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @param mixed $product
	 */
	public function __construct( $product ) {
		$this->product_type = 'variable';
		parent::__construct( $product );
	}

    /**
     * Get total stock.
     *
     * This is the stock of parent and children combined.
     *
     * @access public
     * @return int
     */
    public function get_total_stock() {

        if ( empty( $this->total_stock ) ) {

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
	 * Set stock level of the product.
	 *
	 * @access public
	 * @param mixed $amount (default: null)
	 * @return int Stock
	 */
	function set_stock( $amount = null ) {
		global $woocommerce;

		if ( $this->managing_stock() && ! is_null( $amount ) ) {

			$this->stock = intval( $amount );
			$this->total_stock = intval( $amount );
			update_post_meta( $this->id, '_stock', $this->stock );

			// Check parents out of stock attribute
			if ( ! $this->backorders_allowed() && $this->get_total_stock() <= 0 )
				$this->set_stock_status( 'outofstock' );
			elseif ( $this->backorders_allowed() || $this->get_total_stock() > 0 )
				$this->set_stock_status( 'instock' );

			$woocommerce->clear_product_transients( $this->id ); // Clear transient

			return apply_filters( 'woocommerce_stock_amount', $this->stock );
		}
	}

	/**
	 * Reduce stock level of the product.
	 *
	 * @access public
	 * @param int $by (default: 1) Amount to reduce by.
	 * @return int Stock
	 */
	public function reduce_stock( $by = 1 ) {
		global $woocommerce;

		if ( $this->managing_stock() ) {
			$this->stock = $this->stock - $by;
			$this->total_stock = $this->get_total_stock() - $by;
			update_post_meta($this->id, '_stock', $this->stock);

			// Out of stock attribute
			if ( ! $this->backorders_allowed() && $this->get_total_stock() <= 0 )
				$this->set_stock_status( 'outofstock' );

			$woocommerce->clear_product_transients( $this->id ); // Clear transient

			return apply_filters( 'woocommerce_stock_amount', $this->stock );
		}
	}


	/**
	 * Increase stock level of the product.
	 *
	 * @access public
	 * @param int $by (default: 1) Amount to increase by
	 * @return int Stock
	 */
	public function increase_stock( $by = 1 ) {
		global $woocommerce;

		if ($this->managing_stock()) :
			$this->stock = $this->stock + $by;
			$this->total_stock = $this->get_total_stock() + $by;
			update_post_meta($this->id, '_stock', $this->stock);

			// Out of stock attribute
			if ( $this->backorders_allowed() || $this->get_total_stock() > 0 )
				$this->set_stock_status( 'instock' );

			$woocommerce->clear_product_transients( $this->id ); // Clear transient

			return apply_filters( 'woocommerce_stock_amount', $this->stock );
		endif;
	}


	/**
	 * Return the products children posts.
	 *
	 * @access public
	 * @return array
	 */
	public function get_children() {

		if ( ! is_array( $this->children ) ) {

			$this->children = array();

			$transient_name = 'wc_product_children_ids_' . $this->id;

        	if ( false === ( $this->children = get_transient( $transient_name ) ) ) {

		        $this->children = get_posts( 'post_parent=' . $this->id . '&post_type=product_variation&orderby=menu_order&order=ASC&fields=ids&post_status=any&numberposts=-1' );

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
	public function get_child( $child_id ) {
		return get_product( $child_id, array(
			'parent_id' => $this->id,
			'parent' 	=> $this
			) );
	}


	/**
	 * Returns whether or not the product has any child product.
	 *
	 * @access public
	 * @return bool
	 */
	public function has_child() {
		return sizeof( $this->get_children() ) ? true : false;
	}


	/**
	 * Returns whether or not the product is on sale.
	 *
	 * @access public
	 * @return bool
	 */
	public function is_on_sale() {

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
	 * Returns the price in html format.
	 *
	 * @access public
	 * @param string $price (default: '')
	 * @return string
	 */
	public function get_price_html( $price = '' ) {

		// Ensure variation prices are synced with variations
		if ( $this->min_variation_price === '' || $this->min_variation_regular_price === '' || $this->price === '' ) {
			$this->variable_product_sync();
			$this->price = $this->min_variation_price;
		}

		// Get the price
		if ( $this->price > 0 ) {
			if ( $this->is_on_sale() && isset( $this->min_variation_price ) && $this->min_variation_regular_price !== $this->get_price() ) {

				if ( ! $this->min_variation_price || $this->min_variation_price !== $this->max_variation_price )
					$price .= $this->get_price_html_from_text();

				$price .= $this->get_price_html_from_to( $this->min_variation_regular_price, $this->get_price() );

				$price = apply_filters( 'woocommerce_variable_sale_price_html', $price, $this );

			} else {

				if ( $this->min_variation_price !== $this->max_variation_price )
					$price .= $this->get_price_html_from_text();

				$price .= woocommerce_price( $this->get_price() );

				$price = apply_filters('woocommerce_variable_price_html', $price, $this);

			}
		} elseif ( $this->price === '' ) {

			$price = apply_filters('woocommerce_variable_empty_price_html', '', $this);

		} elseif ( $this->price == 0 ) {

			if ( $this->is_on_sale() && isset( $this->min_variation_regular_price ) && $this->min_variation_regular_price !== $this->get_price() ) {

				if ( $this->min_variation_price !== $this->max_variation_price )
					$price .= $this->get_price_html_from_text();

				$price .= $this->get_price_html_from_to( $this->min_variation_regular_price, __( 'Free!', 'woocommerce' ) );

				$price = apply_filters( 'woocommerce_variable_free_sale_price_html', $price, $this );

			} else {

				if ( $this->min_variation_price !== $this->max_variation_price )
					$price .= $this->get_price_html_from_text();

				$price .= __( 'Free!', 'woocommerce' );

				$price = apply_filters( 'woocommerce_variable_free_price_html', $price, $this );

			}

		}

		return apply_filters( 'woocommerce_get_price_html', $price, $this );
	}


    /**
     * Return an array of attributes used for variations, as well as their possible values.
     *
     * @access public
     * @return array of attributes and their available values
     */
    public function get_variation_attributes() {

	    $variation_attributes = array();

        if ( ! $this->has_child() )
        	return $variation_attributes;

        $attributes = $this->get_attributes();

        foreach ( $attributes as $attribute ) {
            if ( ! $attribute['is_variation'] )
            	continue;

            $values = array();
            $attribute_field_name = 'attribute_' . sanitize_title( $attribute['name'] );

            foreach ( $this->get_children() as $child_id ) {

                if ( get_post_status( $child_id ) != 'publish' )
                	continue; // Disabled

            	$child = $this->get_child( $child_id );

                $child_variation_attributes = $child->get_variation_attributes();

                foreach ( $child_variation_attributes as $name => $value )
                    if ( $name == $attribute_field_name )
                    	$values[] = sanitize_title( $value );
            }

            // empty value indicates that all options for given attribute are available
            if ( in_array( '', $values ) ) {

            	$values = array();

            	// Get all options
            	if ( $attribute['is_taxonomy'] ) {
	            	$post_terms = wp_get_post_terms( $this->id, $attribute['name'] );
					foreach ( $post_terms as $term )
						$values[] = $term->slug;
				} else {
					$values = array_map( 'trim', explode( '|', $attribute['value'] ) );
				}

				$values = array_unique( $values );

			// Order custom attributes (non taxonomy) as defined
            } elseif ( ! $attribute['is_taxonomy'] ) {

            	$option_names = array_map( 'trim', explode( '|', $attribute['value'] ) );
            	$option_slugs = $values;
            	$values       = array();

            	foreach ( $option_names as $option_name ) {
	            	if ( in_array( sanitize_title( $option_name ), $option_slugs ) )
	            		$values[] = $option_name;
            	}
            }

            $variation_attributes[ $attribute['name'] ] = array_unique( $values );
        }

        return $variation_attributes;
    }

    /**
     * If set, get the default attributes for a variable product.
     *
     * @access public
     * @return array
     */
    public function get_variation_default_attributes() {

    	$default = isset( $this->default_attributes ) ? $this->default_attributes : '';

	    return apply_filters( 'woocommerce_product_default_attributes', (array) maybe_unserialize( $default ), $this );
    }

    /**
     * Get an array of available variations for the current product.
     *
     * @access public
     * @return array
     */
    public function get_available_variations() {

	    $available_variations = array();

		foreach ( $this->get_children() as $child_id ) {

			$variation = $this->get_child( $child_id );

			if ( ! empty( $variation->variation_id ) ) {

				if ( get_post_status( $variation->get_variation_id() ) != 'publish' || ! $variation->is_visible() )
					continue; // Disabled or hidden

				$variation_attributes 	= $variation->get_variation_attributes();
				$availability 			= $variation->get_availability();
				$availability_html 		= empty( $availability['availability'] ) ? '' : apply_filters( 'woocommerce_stock_html', '<p class="stock ' . esc_attr( $availability['class'] ) . '">'. wp_kses_post( $availability['availability'] ).'</p>', wp_kses_post( $availability['availability'] ) );

				if ( has_post_thumbnail( $variation->get_variation_id() ) ) {
					$attachment_id = get_post_thumbnail_id( $variation->get_variation_id() );

					$attachment = wp_get_attachment_image_src( $attachment_id, apply_filters( 'single_product_large_thumbnail_size', 'shop_single' )  );
					$image = $attachment ? current( $attachment ) : '';

					$attachment = wp_get_attachment_image_src( $attachment_id, 'full'  );
					$image_link = $attachment ? current( $attachment ) : '';

					$image_title = get_the_title( $attachment_id );
				} else {
					$image = $image_link = $image_title = '';
				}

				$available_variations[] = apply_filters( 'woocommerce_available_variation', array(
					'variation_id' 			=> $child_id,
					'attributes' 			=> $variation_attributes,
					'image_src' 			=> $image,
					'image_link' 			=> $image_link,
					'image_title'			=> $image_title,
					'price_html' 			=> $this->min_variation_price != $this->max_variation_price ? '<span class="price">' . $variation->get_price_html() . '</span>' : '',
					'availability_html' 	=> $availability_html,
					'sku' 					=> $variation->get_sku(),
					'weight'				=> $variation->get_weight() . ' ' . esc_attr( get_option('woocommerce_weight_unit' ) ),
					'dimensions'			=> $variation->get_dimensions(),
					'min_qty' 				=> 1,
					'max_qty' 				=> $this->backorders_allowed() ? '' : $variation->stock,
					'backorders_allowed' 	=> $this->backorders_allowed(),
					'is_in_stock'			=> $variation->is_in_stock(),
					'is_downloadable' 		=> $variation->is_downloadable() ,
					'is_virtual' 			=> $variation->is_virtual(),
					'is_sold_individually' 	=> $variation->is_sold_individually() ? 'yes' : 'no',
				), $this, $variation );
			}
		}

		return $available_variations;
    }


	/**
	 * Sync variable product prices with the children lowest/highest prices.
	 *
	 * @access public
	 * @return void
	 */
	public function variable_product_sync() {
		global $woocommerce;

		$children = get_posts( array(
			'post_parent' 	=> $this->id,
			'posts_per_page'=> -1,
			'post_type' 	=> 'product_variation',
			'fields' 		=> 'ids',
			'post_status'	=> 'publish'
		));

		$this->min_variation_price = $this->min_variation_regular_price = $this->min_variation_sale_price = $this->max_variation_price = $this->max_variation_regular_price = $this->max_variation_sale_price = '';

		if ( $children ) {
			foreach ( $children as $child ) {

				$child_price 			= get_post_meta( $child, '_price', true );
				$child_regular_price 	= get_post_meta( $child, '_regular_price', true );
				$child_sale_price 		= get_post_meta( $child, '_sale_price', true );

				if ( $child_price === '' && $child_regular_price === '' )
					continue;

				// Regular prices
				if ( $child_regular_price !== '' ) {
					if ( ! is_numeric( $this->min_variation_regular_price ) || $child_regular_price < $this->min_variation_regular_price )
						$this->min_variation_regular_price = $child_regular_price;

					if ( ! is_numeric( $this->max_variation_regular_price ) || $child_regular_price > $this->max_variation_regular_price )
						$this->max_variation_regular_price = $child_regular_price;
				}

				// Sale prices
				if ( $child_sale_price !== '' ) {
					if ( $child_price == $child_sale_price ) {
						if ( ! is_numeric( $this->min_variation_sale_price ) || $child_sale_price < $this->min_variation_sale_price )
							$this->min_variation_sale_price = $child_sale_price;

						if ( ! is_numeric( $this->max_variation_sale_price ) || $child_sale_price > $this->max_variation_sale_price )
							$this->max_variation_sale_price = $child_sale_price;
					}
				}

				// Actual prices
				if ( $child_price !== '' ) {
					if ( $child_price > $this->max_variation_price )
						$this->max_variation_price = $child_price;

					if ( $this->min_variation_price === '' || $child_price < $this->min_variation_price )
						$this->min_variation_price = $child_price;
				}
			}

			update_post_meta( $this->id, '_price', $this->min_variation_price );
			update_post_meta( $this->id, '_min_variation_price', $this->min_variation_price );
			update_post_meta( $this->id, '_max_variation_price', $this->max_variation_price );
			update_post_meta( $this->id, '_min_variation_regular_price', $this->min_variation_regular_price );
			update_post_meta( $this->id, '_max_variation_regular_price', $this->max_variation_regular_price );
			update_post_meta( $this->id, '_min_variation_sale_price', $this->min_variation_sale_price );
			update_post_meta( $this->id, '_max_variation_sale_price', $this->max_variation_sale_price );

			$woocommerce->clear_product_transients( $this->id );
		}
	}
}