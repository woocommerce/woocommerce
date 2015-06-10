<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

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
	 * Constructor
	 *
	 * @param mixed $product
	 */
	public function __construct( $product ) {
		$this->product_type = 'variable';
		parent::__construct( $product );
	}

	/**
	 * Get the add to cart button text
	 *
	 * @access public
	 * @return string
	 */
	public function add_to_cart_text() {
		return apply_filters( 'woocommerce_product_add_to_cart_text', __( 'Select options', 'woocommerce' ), $this );
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
        	$transient_name = 'wc_product_total_stock_' . $this->id . WC_Cache_Helper::get_transient_version( 'product' );

        	if ( false === ( $this->total_stock = get_transient( $transient_name ) ) ) {
		        $this->total_stock = max( 0, wc_stock_amount( $this->stock ) );

				if ( sizeof( $this->get_children() ) > 0 ) {
					foreach ( $this->get_children() as $child_id ) {
						if ( 'yes' === get_post_meta( $child_id, '_manage_stock', true ) ) {
							$stock = get_post_meta( $child_id, '_stock', true );
							$this->total_stock += max( 0, wc_stock_amount( $stock ) );
						}
					}
				}
				set_transient( $transient_name, $this->total_stock, DAY_IN_SECONDS * 30 );
			}
		}
		return wc_stock_amount( $this->total_stock );
    }

	/**
	 * Set stock level of the product.
	 *
	 * @param mixed $amount (default: null)
	 * @param string $mode can be set, add, or subtract
	 * @return int Stock
	 */
	public function set_stock( $amount = null, $mode = 'set' ) {
		$this->total_stock = '';
		delete_transient( 'wc_product_total_stock_' . $this->id . WC_Cache_Helper::get_transient_version( 'product' ) );
		return parent::set_stock( $amount, $mode );
	}

	/**
	 * Performed after a stock level change at product level
	 */
	protected function check_stock_status() {
		$set_child_stock_status = '';

		if ( ! $this->backorders_allowed() && $this->get_stock_quantity() <= get_option( 'woocommerce_notify_no_stock_amount' ) ) {
			$set_child_stock_status = 'outofstock';
		} elseif ( $this->backorders_allowed() || $this->get_stock_quantity() > get_option( 'woocommerce_notify_no_stock_amount' ) ) {
			$set_child_stock_status = 'instock';
		}

		if ( $set_child_stock_status ) {
			foreach ( $this->get_children() as $child_id ) {
				if ( 'yes' !== get_post_meta( $child_id, '_manage_stock', true ) ) {
					wc_update_product_stock_status( $child_id, $set_child_stock_status );
				}
			}

			// Children statuses changed, so sync self
			self::sync_stock_status( $this->id );
		}
	}

	/**
	 * set_stock_status function.
	 *
	 * @access public
	 * @return void
	 */
	public function set_stock_status( $status ) {
		$status = 'outofstock' === $status ? 'outofstock' : 'instock';

		if ( update_post_meta( $this->id, '_stock_status', $status ) ) {
			do_action( 'woocommerce_product_set_stock_status', $this->id, $status );
		}
	}

	/**
	 * Return the products children posts.
	 *
	 * @param  boolean $visible_only Only return variations which are not hidden
	 * @return array of children ids
	 */
	public function get_children( $visible_only = false ) {
		if ( ! is_array( $this->children ) || empty( $this->children ) ) {
			$transient_name = 'wc_product_children_ids_' . $this->id . WC_Cache_Helper::get_transient_version( 'product' );
			$this->children = get_transient( $transient_name );

        	if ( empty( $this->children ) ) {
		        $args = array(
					'post_parent' => $this->id,
					'post_type'   => 'product_variation',
					'orderby'     => 'menu_order',
					'order'       => 'ASC',
					'fields'      => 'ids',
					'post_status' => 'publish',
					'numberposts' => -1
		        );

				$this->children = get_posts( $args );

				set_transient( $transient_name, $this->children, DAY_IN_SECONDS * 30 );
			}
		}

		if ( $visible_only ) {
			$children = array();
			foreach( $this->children as $child_id ) {
				if ( 'yes' === get_post_meta( $child_id, '_manage_stock', true ) ) {
					if ( 'instock' === get_post_meta( $child_id, '_stock_status', true ) ) {
						$children[] = $child_id;
					}
				} elseif ( $this->is_in_stock() ) {
					$children[] = $child_id;
				}
			}
		} else {
			$children = $this->children;
		}

		return apply_filters( 'woocommerce_get_children', $children, $this );
	}

	/**
	 * get_child function.
	 *
	 * @access public
	 * @param mixed $child_id
	 * @return WC_Product WC_Product or WC_Product_variation
	 */
	public function get_child( $child_id ) {
		return wc_get_product( $child_id, array(
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
		$is_on_sale = false;
		if ( $this->has_child() ) {
			foreach ( $this->get_children( true ) as $child_id ) {
				$price      = get_post_meta( $child_id, '_price', true );
				$sale_price = get_post_meta( $child_id, '_sale_price', true );
				if ( $sale_price !== "" && $sale_price >= 0 && $sale_price == $price ) {
					$is_on_sale = true;
				}
			}
		}
		return apply_filters( 'woocommerce_product_is_on_sale', $is_on_sale, $this );
	}

	/**
	 * Get the min or max variation regular price.
	 * @param  string $min_or_max - min or max
	 * @param  boolean  $display Whether the value is going to be displayed
	 * @return string
	 */
	public function get_variation_regular_price( $min_or_max = 'min', $display = false ) {
		$variation_id = get_post_meta( $this->id, '_' . $min_or_max . '_regular_price_variation_id', true );

		if ( ! $variation_id ) {
			$price = false;
		} else {
			$price = get_post_meta( $variation_id, '_regular_price', true );

			if ( $display && ( $variation = $this->get_child( $variation_id ) ) ) {
				$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
				$price            = $tax_display_mode == 'incl' ? $variation->get_price_including_tax( 1, $price ) : $variation->get_price_excluding_tax( 1, $price );
			}
		}

		return apply_filters( 'woocommerce_get_variation_regular_price', $price, $this, $min_or_max, $display );
	}

	/**
	 * Get the min or max variation sale price.
	 * @param  string $min_or_max - min or max
	 * @param  boolean  $display Whether the value is going to be displayed
	 * @return string
	 */
	public function get_variation_sale_price( $min_or_max = 'min', $display = false ) {
		$variation_id = get_post_meta( $this->id, '_' . $min_or_max . '_sale_price_variation_id', true );

		if ( ! $variation_id ) {
			$price = false;
		} else {
			$price = get_post_meta( $variation_id, '_sale_price', true );

			if ( $display ) {
				$variation        = $this->get_child( $variation_id );
				$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
				$price            = $tax_display_mode == 'incl' ? $variation->get_price_including_tax( 1, $price ) : $variation->get_price_excluding_tax( 1, $price );
			}
		}

		return apply_filters( 'woocommerce_get_variation_sale_price', $price, $this, $min_or_max, $display );
	}

	/**
	 * Get the min or max variation (active) price.
	 * @param  string $min_or_max - min or max
	 * @param  boolean  $display Whether the value is going to be displayed
	 * @return string
	 */
	public function get_variation_price( $min_or_max = 'min', $display = false ) {
		$variation_id = get_post_meta( $this->id, '_' . $min_or_max . '_price_variation_id', true );

		if ( $display ) {
			$variation        = $this->get_child( $variation_id );

			if ( $variation ) {
				$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
				$price            = $tax_display_mode == 'incl' ? $variation->get_price_including_tax() : $variation->get_price_excluding_tax();
			} else {
				$price = '';
			}
		} else {
			$price = get_post_meta( $variation_id, '_price', true );
		}

		return apply_filters( 'woocommerce_get_variation_price', $price, $this, $min_or_max, $display );
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
		if ( $this->get_variation_regular_price( 'min' ) === false || $this->get_variation_price( 'min' ) === false || $this->get_variation_price( 'min' ) === '' || $this->get_price() === '' ) {
			$this->variable_product_sync( $this->id );
		}

		// Get the price
		if ( $this->get_price() === '' ) {

			$price = apply_filters( 'woocommerce_variable_empty_price_html', '', $this );

		} else {

			// Main price
			$prices = array( $this->get_variation_price( 'min', true ), $this->get_variation_price( 'max', true ) );
			$price  = $prices[0] !== $prices[1] ? sprintf( _x( '%1$s&ndash;%2$s', 'Price range: from-to', 'woocommerce' ), wc_price( $prices[0] ), wc_price( $prices[1] ) ) : wc_price( $prices[0] );

			// Sale
			$prices = array( $this->get_variation_regular_price( 'min', true ), $this->get_variation_regular_price( 'max', true ) );
			sort( $prices );
			$saleprice = $prices[0] !== $prices[1] ? sprintf( _x( '%1$s&ndash;%2$s', 'Price range: from-to', 'woocommerce' ), wc_price( $prices[0] ), wc_price( $prices[1] ) ) : wc_price( $prices[0] );

			if ( $price !== $saleprice ) {
				$price = apply_filters( 'woocommerce_variable_sale_price_html', $this->get_price_html_from_to( $saleprice, $price ) . $this->get_price_suffix(), $this );
			} else {
				if ( $prices[0] == 0 && $prices[1] == 0 ) {
					$price = __( 'Free!', 'woocommerce' );
					$price = apply_filters( 'woocommerce_variable_free_price_html', $price, $this );
				} else {
					$price = apply_filters( 'woocommerce_variable_price_html', $price . $this->get_price_suffix(), $this );
				}
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

        if ( ! $this->has_child() ) {
        	return $variation_attributes;
        }

        $attributes = $this->get_attributes();

        foreach ( $attributes as $attribute ) {
            if ( ! $attribute['is_variation'] ) {
            	continue;
            }

            $values = array();
            $attribute_field_name = 'attribute_' . sanitize_title( $attribute['name'] );

            foreach ( $this->get_children() as $child_id ) {

            	$variation = $this->get_child( $child_id );

				if ( ! empty( $variation->variation_id ) ) {

					if ( ! $variation->variation_is_visible() ) {
						continue; // Disabled or hidden
					}

					$child_variation_attributes = $variation->get_variation_attributes();

	                foreach ( $child_variation_attributes as $name => $value ) {
	                    if ( $name == $attribute_field_name ) {
	                    	$values[] = sanitize_title( $value );
	                    }
	                }
                }
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
					$values = array_map( 'trim', explode( WC_DELIMITER, $attribute['value'] ) );
				}

				$values = array_unique( $values );

			// Order custom attributes (non taxonomy) as defined
            } elseif ( ! $attribute['is_taxonomy'] ) {

            	$option_names = array_map( 'trim', explode( WC_DELIMITER, $attribute['value'] ) );
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

			// Hide out of stock variations if 'Hide out of stock items from the catalog' is checked
			if ( empty( $variation->variation_id ) || ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $variation->is_in_stock() ) ) {
				continue;
			}

			// Filter 'woocommerce_hide_invisible_variations' to optionally hide invisible variations (disabled variations and variations with empty price)
			if ( apply_filters( 'woocommerce_hide_invisible_variations', false, $this->id ) && ! $variation->variation_is_visible() ) {
				continue;
			}

			$variation_attributes = $variation->get_variation_attributes();
			$availability         = $variation->get_availability();
			$availability_html    = empty( $availability['availability'] ) ? '' : '<p class="stock ' . esc_attr( $availability['class'] ) . '">' . wp_kses_post( $availability['availability'] ) . '</p>';
			$availability_html    = apply_filters( 'woocommerce_stock_html', $availability_html, $availability['availability'], $variation );

			if ( has_post_thumbnail( $variation->get_variation_id() ) ) {
				$attachment_id = get_post_thumbnail_id( $variation->get_variation_id() );

				$attachment    = wp_get_attachment_image_src( $attachment_id, apply_filters( 'single_product_large_thumbnail_size', 'shop_single' )  );
				$image         = $attachment ? current( $attachment ) : '';

				$attachment    = wp_get_attachment_image_src( $attachment_id, 'full'  );
				$image_link    = $attachment ? current( $attachment ) : '';

				$image_title   = get_the_title( $attachment_id );
				$image_alt     = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			} else {
				$image = $image_link = $image_title = $image_alt = '';
			}

			$available_variations[] = apply_filters( 'woocommerce_available_variation', array(
				'variation_id'          => $child_id,
				'variation_is_visible'  => $variation->variation_is_visible(),
				'variation_is_active'   => $variation->variation_is_active(),
				'is_purchasable'        => $variation->is_purchasable(),
				'display_price'         => $variation->get_display_price(),
				'display_regular_price' => $variation->get_display_price( $variation->get_regular_price() ),
				'attributes'            => $variation_attributes,
				'image_src'             => $image,
				'image_link'            => $image_link,
				'image_title'           => $image_title,
				'image_alt'             => $image_alt,
				'price_html'            => $variation->get_price() === "" || $this->get_variation_price( 'min' ) !== $this->get_variation_price( 'max' ) ? '<span class="price">' . $variation->get_price_html() . '</span>' : '',
				'availability_html'     => $availability_html,
				'sku'                   => $variation->get_sku(),
				'weight'                => $variation->get_weight() . ' ' . esc_attr( get_option('woocommerce_weight_unit' ) ),
				'dimensions'            => $variation->get_dimensions(),
				'min_qty'               => 1,
				'max_qty'               => $variation->backorders_allowed() ? '' : $variation->get_stock_quantity(),
				'backorders_allowed'    => $variation->backorders_allowed(),
				'is_in_stock'           => $variation->is_in_stock(),
				'is_downloadable'       => $variation->is_downloadable() ,
				'is_virtual'            => $variation->is_virtual(),
				'is_sold_individually'  => $variation->is_sold_individually() ? 'yes' : 'no',
			), $this, $variation );
		}

		return $available_variations;
    }

	/**
	 * Sync variable product prices with the children lowest/highest prices.
	 */
	public function variable_product_sync( $product_id = '' ) {
		if ( empty( $product_id ) ) {
			$product_id = $this->id;
		}

		// Sync prices with children
		self::sync( $product_id );

		// Re-load prices
		$this->price                  = get_post_meta( $product_id, '_price', true );

		foreach ( array( 'price', 'regular_price', 'sale_price' ) as $price_type ) {
			$min_variation_id_key        = "min_{$price_type}_variation_id";
			$max_variation_id_key        = "max_{$price_type}_variation_id";
			$min_price_key               = "_min_variation_{$price_type}";
			$max_price_key               = "_max_variation_{$price_type}";
			$this->$min_variation_id_key = get_post_meta( $product_id, '_' . $min_variation_id_key, true );
			$this->$max_variation_id_key = get_post_meta( $product_id, '_' . $max_variation_id_key, true );
			$this->$min_price_key        = get_post_meta( $product_id, '_' . $min_price_key, true );
			$this->$max_price_key        = get_post_meta( $product_id, '_' . $max_price_key, true );
		}
	}

	/**
	 * Sync variable product stock status with children
	 * @param  int $product_id
	 */
	public static function sync_stock_status( $product_id ) {
		$children = get_posts( array(
			'post_parent' 	=> $product_id,
			'posts_per_page'=> -1,
			'post_type' 	=> 'product_variation',
			'fields' 		=> 'ids',
			'post_status'	=> 'publish'
		) );

		$stock_status = 'outofstock';

		foreach ( $children as $child_id ) {
			$child_stock_status = get_post_meta( $child_id, '_stock_status', true );
			$child_stock_status = $child_stock_status ? $child_stock_status : 'instock';
			if ( 'instock' === $child_stock_status ) {
				$stock_status = 'instock';
				break;
			}
		}

		wc_update_product_stock_status( $product_id, $stock_status );
	}

	/**
	 * Sync the variable product with it's children
	 */
	public static function sync( $product_id ) {
		global $wpdb;

		$children = get_posts( array(
			'post_parent' 	=> $product_id,
			'posts_per_page'=> -1,
			'post_type' 	=> 'product_variation',
			'fields' 		=> 'ids',
			'post_status'	=> 'publish'
		) );

		// No published variations - update parent post status. Use $wpdb to prevent endless loop on save_post hooks.
		if ( ! $children && get_post_status( $product_id ) == 'publish' ) {
			$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $product_id ) );

			if ( is_admin() ) {
				WC_Admin_Meta_Boxes::add_error( __( 'This variable product has no active variations so cannot be published. Changing status to draft.', 'woocommerce' ) );
			}

		// Loop the variations
		} else {

			// Set the variable product to be virtual/downloadable if all children are virtual/downloadable
			foreach ( array( '_downloadable', '_virtual' ) as $meta_key ) {

				$all_variations_yes = true;

				foreach ( $children as $child_id ) {
					if ( 'yes' != get_post_meta( $child_id, $meta_key, true ) ) {
						$all_variations_yes = false;
						break;
					}
				}

				update_post_meta( $product_id, $meta_key, ( true === $all_variations_yes ) ? 'yes' : 'no' );
			}

			// Main active prices
			$min_price            = null;
			$max_price            = null;
			$min_price_id         = null;
			$max_price_id         = null;

			// Regular prices
			$min_regular_price    = null;
			$max_regular_price    = null;
			$min_regular_price_id = null;
			$max_regular_price_id = null;

			// Sale prices
			$min_sale_price       = null;
			$max_sale_price       = null;
			$min_sale_price_id    = null;
			$max_sale_price_id    = null;

			foreach ( array( 'price', 'regular_price', 'sale_price' ) as $price_type ) {
				foreach ( $children as $child_id ) {
					$child_price = get_post_meta( $child_id, '_' . $price_type, true );

					// Skip non-priced variations
					if ( $child_price === '' ) {
						continue;
					}

					// Skip hidden variations
					if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
						$stock = get_post_meta( $child_id, '_stock', true );
						if ( $stock !== "" && $stock <= get_option( 'woocommerce_notify_no_stock_amount' ) ) {
							continue;
						}
					}

					// Find min price
					if ( is_null( ${"min_{$price_type}"} ) || $child_price < ${"min_{$price_type}"} ) {
						${"min_{$price_type}"}    = $child_price;
						${"min_{$price_type}_id"} = $child_id;
					}

					// Find max price
					if ( $child_price > ${"max_{$price_type}"} ) {
						${"max_{$price_type}"}    = $child_price;
						${"max_{$price_type}_id"} = $child_id;
					}
				}

				// Store prices
				update_post_meta( $product_id, '_min_variation_' . $price_type, ${"min_{$price_type}"} );
				update_post_meta( $product_id, '_max_variation_' . $price_type, ${"max_{$price_type}"} );

				// Store ids
				update_post_meta( $product_id, '_min_' . $price_type . '_variation_id', ${"min_{$price_type}_id"} );
				update_post_meta( $product_id, '_max_' . $price_type . '_variation_id', ${"max_{$price_type}_id"} );
			}

			// The VARIABLE PRODUCT price should equal the min price of any type
			update_post_meta( $product_id, '_price', $min_price );
			delete_transient( 'wc_products_onsale' );

			do_action( 'woocommerce_variable_product_sync', $product_id, $children );
		}
	}
}
