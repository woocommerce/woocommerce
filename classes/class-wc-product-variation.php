<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Product Variation Class
 *
 * The WooCommerce product variation class handles product variation data.
 *
 * @class 		WC_Product_Variation
 * @version		2.0.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Product_Variation extends WC_Product {

	/** @public int ID of the variable product. */
	public $variation_id;

	/** @public object Parent Variable product object. */
	public $parent;

	/** @public array Stores variation data (attributes) for the current variation. */
	public $variation_data = array();

	/** @public bool True if the variation has a length. */
	public $variation_has_length = false;

	/** @public bool True if the variation has a width. */
	public $variation_has_width = false;

	/** @public bool True if the variation has a height. */
	public $variation_has_height = false;

	/** @public bool True if the variation has a weight. */
	public $variation_has_weight = false;

	/** @public bool True if the variation has stock and is managing stock. */
	public $variation_has_stock = false;

	/** @public bool True if the variation has a sku. */
	public $variation_has_sku = false;

	/** @public string Stores the shipping class of the variation. */
	public $variation_shipping_class = false;

	/** @public int Stores the shipping class ID of the variation. */
	public $variation_shipping_class_id = false;

	/** @public bool True if the variation has a tax class. */
	public $variation_has_tax_class = false;

	/**
	 * Loads all product data from custom fields
	 *
	 * @access public
	 * @param int $variation_id ID of the variation to load
	 * @param array $args Array of the arguments containing parent product data
	 * @return void
	 */
	public function __construct( $variation, $args = array() ) {

		$this->product_type = 'variation';

		if ( is_object( $variation ) ) {
			$this->variation_id = absint( $variation->ID );
		} else {
			$this->variation_id = absint( $variation );
		}

		/* Get main product data from parent (args) */
		$this->id   = ! empty( $args['parent_id'] ) ? intval( $args['parent_id'] ) : wp_get_post_parent_id( $this->variation_id );

		// The post doesn't have a parent id, therefore its invalid.
		if ( empty( $this->id ) )
			return false;

		// Get post data
		$this->parent = ! empty( $args['parent'] ) ? $args['parent'] : get_product( $this->id );
		$this->post   = ! empty( $this->parent->post ) ? $this->parent->post : array();
		$this->product_custom_fields = get_post_meta( $this->variation_id );

		// Get the variation attributes from meta
		foreach ( $this->product_custom_fields as $name => $value ) {
			if ( ! strstr( $name, 'attribute_' ) )
				continue;

			$this->variation_data[ $name ] = sanitize_title( $value[0] );
		}

		// Now get variation meta to override the parent variable product
		if ( ! empty( $this->product_custom_fields['_sku'][0] ) ) {
			$this->variation_has_sku = true;
			$this->sku               = $this->product_custom_fields['_sku'][0];
		}

		if ( isset( $this->product_custom_fields['_stock'][0] ) && $this->product_custom_fields['_stock'][0] !== '' ) {
			$this->variation_has_stock = true;
			$this->manage_stock        = 'yes';
			$this->stock               = $this->product_custom_fields['_stock'][0];
		}

		if ( isset( $this->product_custom_fields['_weight'][0] ) && $this->product_custom_fields['_weight'][0] !== '' ) {
			$this->variation_has_weight = true;
			$this->weight               = $this->product_custom_fields['_weight'][0];
		}

		if ( isset( $this->product_custom_fields['_length'][0] ) && $this->product_custom_fields['_length'][0] !== '' ) {
			$this->variation_has_length = true;
			$this->length               = $this->product_custom_fields['_length'][0];
		}

		if ( isset( $this->product_custom_fields['_width'][0] ) && $this->product_custom_fields['_width'][0] !== '' ) {
			$this->variation_has_width = true;
			$this->width               = $this->product_custom_fields['_width'][0];
		}

		if ( isset( $this->product_custom_fields['_height'][0] ) && $this->product_custom_fields['_height'][0] !== '' ) {
			$this->variation_has_height = true;
			$this->height               = $this->product_custom_fields['_height'][0];
		}

		if ( isset( $this->product_custom_fields['_downloadable'][0] ) && $this->product_custom_fields['_downloadable'][0] == 'yes' ) {
			$this->downloadable = 'yes';
		} else {
			$this->downloadable = 'no';
		}

		if ( isset( $this->product_custom_fields['_virtual'][0] ) && $this->product_custom_fields['_virtual'][0] == 'yes' ) {
			$this->virtual = 'yes';
		} else {
			$this->virtual = 'no';
		}

		if ( isset( $this->product_custom_fields['_tax_class'][0] ) ) {
			$this->variation_has_tax_class = true;
			$this->tax_class               = $this->product_custom_fields['_tax_class'][0];
		}

		if ( isset( $this->product_custom_fields['_sale_price_dates_from'][0] ) )
			$this->sale_price_dates_from = $this->product_custom_fields['_sale_price_dates_from'][0];

		if ( isset( $this->product_custom_fields['_sale_price_dates_to'][0] ) )
			$this->sale_price_dates_from = $this->product_custom_fields['_sale_price_dates_to'][0];

		// Prices
		$this->price         = isset( $this->product_custom_fields['_price'][0] ) ? $this->product_custom_fields['_price'][0] : '';
		$this->regular_price = isset( $this->product_custom_fields['_regular_price'][0] ) ? $this->product_custom_fields['_regular_price'][0] : '';
		$this->sale_price    = isset( $this->product_custom_fields['_sale_price'][0] ) ? $this->product_custom_fields['_sale_price'][0] : '';

		// Backwards compat for prices
		if ( $this->price !== '' && $this->regular_price == '' ) {
			update_post_meta( $this->variation_id, '_regular_price', $this->price );
			$this->regular_price = $this->price;

			if ( $this->sale_price !== '' && $this->sale_price < $this->regular_price ) {
				update_post_meta( $this->variation_id, '_price', $this->sale_price );
				$this->price = $this->sale_price;
			}
		}

		$this->total_stock = $this->stock;
	}

	/**
	 * Returns whether or not the product post exists.
	 *
	 * @access public
	 * @return bool
	 */
	function exists() {
		return empty( $this->id ) ? false : true;
	}

	/**
	 * Returns whether or not the variation is visible.
	 *
	 * @access public
	 * @return bool
	 */
	public function is_visible() {

		$visible = true;

		// Out of stock visibility
		if ( get_option('woocommerce_hide_out_of_stock_items') == 'yes' && ! $this->is_in_stock() )
			$visible = false;

		// Price not set
		elseif ( $this->price == "" )
			$visible = false;

		return apply_filters( 'woocommerce_product_is_visible', $visible, $this->id );
	}


	/**
	 * Returns whether or not the variations parent is visible.
	 *
	 * @access public
	 * @return bool
	 */
	public function parent_is_visible() {
		return parent::is_visible();
	}

	/**
     * Get variation ID
     *
     * @return int
     */
    public function get_variation_id() {
        return absint( $this->variation_id );
    }

    /**
     * Get variation attribute values
     *
     * @return array of attributes and their values for this variation
     */
    public function get_variation_attributes() {
        return $this->variation_data;
    }

	/**
     * Get variation price HTML. Prices are not inherited from parents.
     *
	 * @param string $price (default: '')
     * @return string containing the formatted price
     */
	public function get_price_html( $price = '' ) {

		if ( $this->price !== '' ) {
			if ( $this->price == $this->sale_price && $this->sale_price < $this->regular_price ) {

				$price = '<del>' . woocommerce_price( $this->regular_price ) . '</del> <ins>' . woocommerce_price( $this->sale_price ) . '</ins>';
				$price = apply_filters( 'woocommerce_variation_sale_price_html', $price, $this );

			} elseif ( $this->price > 0 ) {

				$price = woocommerce_price( $this->price );
				$price = apply_filters( 'woocommerce_variation_price_html', $price, $this );

			} else {

				$price = __( 'Free!', 'woocommerce' );
				$price = apply_filters( 'woocommerce_variation_free_price_html', $price, $this );

			}
		} else {
			$price = apply_filters( 'woocommerce_variation_empty_price_html', '', $this );
		}

		return $price;
	}


    /**
     * Gets the main product image.
     *
     * @access public
     * @param string $size (default: 'shop_thumbnail')
     * @return string
     */
    public function get_image( $size = 'shop_thumbnail', $attr = array() ) {
    	global $woocommerce;

    	$image = '';

    	if ( $this->variation_id && has_post_thumbnail( $this->variation_id ) ) {
			$image = get_the_post_thumbnail( $this->variation_id, $size, $attr );
		} elseif ( has_post_thumbnail( $this->id ) ) {
			$image = get_the_post_thumbnail( $this->id, $size, $attr );
		} elseif ( $parent_id = wp_get_post_parent_id( $this->id ) && has_post_thumbnail( $parent_id ) ) {
			$image = get_the_post_thumbnail( $parent_id, $size , $attr);
		} else {
			$image = woocommerce_placeholder_img( $size );
		}

		return $image;
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

		if ( $this->variation_has_stock ) {
			if ( $this->managing_stock() && ! is_null( $amount ) ) {

				$this->stock = intval( $amount );
				$this->total_stock = intval( $amount );
				update_post_meta( $this->variation_id, '_stock', $this->stock );
				$woocommerce->clear_product_transients( $this->id ); // Clear transient

				// Check parents out of stock attribute
				if ( ! $this->is_in_stock() ) {

					// Check parent
					$parent_product = get_product( $this->id );

					// Only continue if the parent has backorders off
					if ( ! $parent_product->backorders_allowed() && $parent_product->get_total_stock() <= 0 )
						$this->set_stock_status( 'outofstock' );

				} elseif ( $this->is_in_stock() ) {
					$this->set_stock_status( 'instock' );
				}

				return apply_filters( 'woocommerce_stock_amount', $this->stock );
			}
		} else {
			return parent::set_stock( $amount );
		}
	}


	/**
	 * Reduce stock level of the product.
	 *
	 * @access public
	 * @param int $by (default: 1) Amount to reduce by
	 * @return int stock level
	 */
	public function reduce_stock( $by = 1 ) {
		global $woocommerce;

		if ( $this->variation_has_stock ) {
			if ( $this->managing_stock() ) {

				$this->stock 		= $this->stock - $by;
				$this->total_stock 	= $this->total_stock - $by;
				update_post_meta( $this->variation_id, '_stock', $this->stock );
				$woocommerce->clear_product_transients( $this->id ); // Clear transient

				// Check parents out of stock attribute
				if ( ! $this->is_in_stock() ) {

					// Check parent
					$parent_product = get_product( $this->id );

					// Only continue if the parent has backorders off
					if ( ! $parent_product->backorders_allowed() && $parent_product->get_total_stock() <= 0 )
						$this->set_stock_status( 'outofstock' );

				}

				return apply_filters( 'woocommerce_stock_amount', $this->stock );
			}
		} else {
			return parent::reduce_stock( $by );
		}
	}


	/**
	 * Increase stock level of the product.
	 *
	 * @access public
	 * @param int $by (default: 1) Amount to increase by
	 * @return int stock level
	 */
	public function increase_stock( $by = 1 ) {
		global $woocommerce;

		if ($this->variation_has_stock) :
			if ($this->managing_stock()) :

				$this->stock 		= $this->stock + $by;
				$this->total_stock 	= $this->total_stock + $by;
				update_post_meta( $this->variation_id, '_stock', $this->stock );
				$woocommerce->clear_product_transients( $this->id ); // Clear transient

				// Parents out of stock attribute
				if ( $this->is_in_stock() )
					$this->set_stock_status( 'instock' );

				return apply_filters( 'woocommerce_stock_amount', $this->stock );
			endif;
		else :
			return parent::increase_stock( $by );
		endif;
	}


	/**
	 * Get the shipping class, and if not set, get the shipping class of the parent.
	 *
	 * @access public
	 * @return string
	 */
	public function get_shipping_class() {
		if ( ! $this->variation_shipping_class ) {
			$classes = get_the_terms( $this->variation_id, 'product_shipping_class' );

			if ( $classes && ! is_wp_error( $classes ) ) {
				$this->variation_shipping_class = esc_attr( current( $classes )->slug );
			} else {
				$this->variation_shipping_class = parent::get_shipping_class();
			}
		}

		return $this->variation_shipping_class;
	}


	/**
	 * Returns the product shipping class ID.
	 *
	 * @access public
	 * @return int
	 */
	public function get_shipping_class_id() {
		if ( ! $this->variation_shipping_class_id ) {

			$classes = get_the_terms( $this->variation_id, 'product_shipping_class' );

			if ( $classes && ! is_wp_error( $classes ) )
				$this->variation_shipping_class_id = current( $classes )->term_id;
			else
				$this->variation_shipping_class_id = parent::get_shipping_class_id();

		}
		return absint( $this->variation_shipping_class_id );
	}

	/**
	 * Get file download path identified by $download_id
	 *
	 * @access public
	 * @param string $download_id file identifier
	 * @return array
	 */
	public function get_file_download_path( $download_id ) {

		$file_path = '';
		$file_paths = apply_filters( 'woocommerce_file_download_paths', get_post_meta( $this->variation_id, '_file_paths', true ), $this->variation_id, null, null );

		if ( ! $download_id && count( $file_paths ) == 1 ) {
			// backwards compatibility for old-style download URLs and template files
			$file_path = array_shift( $file_paths );
		} elseif ( isset( $file_paths[ $download_id ] ) ) {
			$file_path = $file_paths[ $download_id ];
		}

		// allow overriding based on the particular file being requested
		return apply_filters( 'woocommerce_file_download_path', $file_path, $this->variation_id, $download_id );
	}
}