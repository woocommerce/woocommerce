<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Product Variation Class
 *
 * The WooCommerce product variation class handles product variation data.
 *
 * @class       WC_Product_Variation
 * @version     2.2.0
 * @package     WooCommerce/Classes
 * @category    Class
 * @author      WooThemes
 */
class WC_Product_Variation extends WC_Product {

	/** @public int ID of the variation itself. */
	public $variation_id;

	/** @public object Parent Variable product object. */
	public $parent;

	/** @public string Stores the shipping class of the variation. */
	public $variation_shipping_class         = false;

	/** @public int Stores the shipping class ID of the variation. */
	public $variation_shipping_class_id      = false;

	/** @public unused vars @deprecated in 2.2 */
	public $variation_has_sku                = true;
	public $variation_has_length             = true;
	public $variation_has_width              = true;
	public $variation_has_height             = true;
	public $variation_has_weight             = true;
	public $variation_has_tax_class          = true;
	public $variation_has_downloadable_files = true;

	/** @private array Data which is only at variation level - no inheritance plus their default values if left blank. */
	protected $variation_level_meta_data = array(
		'downloadable'          => 'no',
		'virtual'               => 'no',
		'manage_stock'          => 'no',
		'sale_price_dates_from' => '',
		'sale_price_dates_to'   => '',
		'price'                 => '',
		'regular_price'         => '',
		'sale_price'            => '',
		'stock'                 => 0,
		'stock_status'          => 'instock',
		'downloadable_files'    => array()
	);

	/** @private array Data which can be at variation level, otherwise fallback to parent if not set. */
	protected $variation_inherited_meta_data = array(
		'tax_class'  => '',
		'backorders' => 'no',
		'sku'        => '',
		'weight'     => '',
		'length'     => '',
		'width'      => '',
		'height'     => ''
	);

	/**
	 * Loads required variation data.
	 *
	 * @param int $variation ID of the variation to load
	 * @param array $args Array of the arguments containing parent product data
	 */
	public function __construct( $variation, $args = array() ) {
		if ( is_object( $variation ) ) {
			$this->variation_id = absint( $variation->ID );
		} else {
			$this->variation_id = absint( $variation );
		}

		/* Get main product data from parent (args) */
		$this->id = ! empty( $args['parent_id'] ) ? intval( $args['parent_id'] ) : wp_get_post_parent_id( $this->variation_id );

		// The post doesn't have a parent id, therefore its invalid.
		if ( empty( $this->id ) ) {
			return;
		}

		$this->product_type = 'variation';
		$this->parent       = ! empty( $args['parent'] ) ? $args['parent'] : wc_get_product( $this->id );
		$this->post         = ! empty( $this->parent->post ) ? $this->parent->post : array();
	}

	/**
	 * __isset function.
	 *
	 * @param mixed $key
	 * @return bool
	 */
	public function __isset( $key ) {
		if ( in_array( $key, array_keys( $this->variation_level_meta_data ) ) ) {
			return metadata_exists( 'post', $this->variation_id, '_' . $key );
		} elseif ( in_array( $key, array_keys( $this->variation_inherited_meta_data ) ) ) {
			return metadata_exists( 'post', $this->variation_id, '_' . $key ) || metadata_exists( 'post', $this->id, '_' . $key );
		} else {
			return metadata_exists( 'post', $this->id, '_' . $key );
		}
	}

	/**
	 * Get method returns variation meta data if set, otherwise in most cases the data from the parent.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( in_array( $key, array_keys( $this->variation_level_meta_data ) ) ) {

			$value = get_post_meta( $this->variation_id, '_' . $key, true );

			if ( '' === $value ) {
				$value = $this->variation_level_meta_data[ $key ];
			}

		} elseif ( in_array( $key, array_keys( $this->variation_inherited_meta_data ) ) ) {

			$value = metadata_exists( 'post', $this->variation_id, '_' . $key ) ? get_post_meta( $this->variation_id, '_' . $key, true ) : get_post_meta( $this->id, '_' . $key, true );

			// Handle meta data keys which can be empty at variation level to cause inheritance
			if ( '' === $value && in_array( $key, array( 'sku', 'weight', 'length', 'width', 'height' ) ) ) {
				$value = get_post_meta( $this->id, '_' . $key, true );
			}

			if ( '' === $value ) {
				$value = $this->variation_inherited_meta_data[ $key ];
			}

		} elseif ( 'variation_data' === $key ) {
			return $this->variation_data = wc_get_product_variation_attributes( $this->variation_id );

		} elseif ( 'variation_has_stock' === $key ) {
			return $this->managing_stock();

		} else {
			$value = metadata_exists( 'post', $this->variation_id, '_' . $key ) ? get_post_meta( $this->variation_id, '_' . $key, true ) : parent::__get( $key );
		}

		return $value;
	}

	/**
	 * Returns whether or not the product post exists.
	 *
	 * @return bool
	 */
	public function exists() {
		return ! empty( $this->id );
	}

	/**
	 * Wrapper for get_permalink. Adds this variations attributes to the URL.
	 *
	 * @param  $cart item array If the cart item is passed, we can get a link containing the exact attributes selected for the variation, rather than the default attributes.
	 * @return string
	 */
	public function get_permalink( $cart_item = null ) {
		return add_query_arg( array_map( 'urlencode', array_filter( isset( $cart_item['variation'] ) ? $cart_item['variation'] : $this->variation_data ) ), get_permalink( $this->id ) );
	}

	/**
	 * Get the add to url used mainly in loops.
	 *
	 * @return string
	 */
	public function add_to_cart_url() {
		$variation_data = array_map( 'urlencode', $this->variation_data );
		$url            = $this->is_purchasable() && $this->is_in_stock() ? remove_query_arg( 'added-to-cart', add_query_arg( array_merge( array( 'variation_id' => $this->variation_id, 'add-to-cart' => $this->id ), $variation_data ) ) ) : get_permalink( $this->id );

		return apply_filters( 'woocommerce_product_add_to_cart_url', $url, $this );
	}

	/**
	 * Get the add to cart button text
	 *
	 * @return string
	 */
	public function add_to_cart_text() {
		$text = $this->is_purchasable() && $this->is_in_stock() ? __( 'Add to cart', 'woocommerce' ) : __( 'Read More', 'woocommerce' );

		return apply_filters( 'woocommerce_product_add_to_cart_text', $text, $this );
	}

	/**
	 * Checks if this particular variation is visible. Invisible variations are enabled and can be selected, but no price / stock info is displayed.
	 * Instead, a suitable 'unavailable' message is displayed.
	 * Invisible by default: Disabled variations and variations with an empty price.
	 *
	 * @return bool
	 */
	public function variation_is_visible() {
		$visible = true;

		// Published == enabled checkbox
		if ( get_post_status( $this->variation_id ) != 'publish' ) {
			$visible = false;
		}

		// Price not set
		elseif ( $this->get_price() === "" ) {
			$visible = false;
		}

		return apply_filters( 'woocommerce_variation_is_visible', $visible, $this->variation_id, $this->id, $this );
	}

	/**
	 * Controls whether this particular variation will appear greyed-out (inactive) or not (active).
	 * Used by extensions to make incompatible variations appear greyed-out, etc.
	 * Other possible uses: prevent out-of-stock variations from being selected.
	 *
	 * @return bool
	 */
	public function variation_is_active() {
		return apply_filters( 'woocommerce_variation_is_active', true, $this );
	}

	/**
	 * Returns false if the product cannot be bought.
	 * Override abstract method so that: i) Disabled variations are not be purchasable by admins. ii) Enabled variations are not purchasable if the parent product is not purchasable.
	 *
	 * @return bool
	 */
	public function is_purchasable() {
		// Published == enabled checkbox
		if ( get_post_status( $this->variation_id ) != 'publish' ) {
			$purchasable = false;
		} else {
			$purchasable = parent::is_purchasable();
		}
		return apply_filters( 'woocommerce_variation_is_purchasable', $purchasable, $this );
	}

	/**
	 * Returns whether or not the variations parent is visible.
	 *
	 * @return bool
	 */
	public function parent_is_visible() {
		return $this->is_visible();
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
	 * Check if all variation's attributes are set
	 *
	 * @return boolean
	 */
	public function has_all_attributes_set() {

		$set = true;

		// undefined attributes have null strings as array values
		foreach( $this->get_variation_attributes() as $att ){
			if( ! $att ){
				$set = false;
				break;
			}
		}

		return $set;

	}

	/**
	 * Get variation price HTML. Prices are not inherited from parents.
	 *
	 * @return string containing the formatted price
	 */
	public function get_price_html( $price = '' ) {

		$display_price         = $this->get_display_price();
		$display_regular_price = $this->get_display_price( $this->get_regular_price() );
		$display_sale_price    = $this->get_display_price( $this->get_sale_price() );

		if ( $this->get_price() !== '' ) {
			if ( $this->is_on_sale() ) {
				$price = apply_filters( 'woocommerce_variation_sale_price_html', '<del>' . wc_price( $display_regular_price ) . '</del> <ins>' . wc_price( $display_sale_price ) . '</ins>' . $this->get_price_suffix(), $this );
			} elseif ( $this->get_price() > 0 ) {
				$price = apply_filters( 'woocommerce_variation_price_html', wc_price( $display_price ) . $this->get_price_suffix(), $this );
			} else {
				$price = apply_filters( 'woocommerce_variation_free_price_html', __( 'Free!', 'woocommerce' ), $this );
			}
		} else {
			$price = apply_filters( 'woocommerce_variation_empty_price_html', '', $this );
		}

		return apply_filters( 'woocommerce_get_variation_price_html', $price, $this );
	}

	/**
	 * Gets the main product image ID.
	 *
	 * @return int
	 */
	public function get_image_id() {
		if ( $this->variation_id && has_post_thumbnail( $this->variation_id ) ) {
			$image_id = get_post_thumbnail_id( $this->variation_id );
		} elseif ( has_post_thumbnail( $this->id ) ) {
			$image_id = get_post_thumbnail_id( $this->id );
		} elseif ( ( $parent_id = wp_get_post_parent_id( $this->id ) ) && has_post_thumbnail( $parent_id ) ) {
			$image_id = get_post_thumbnail_id( $parent_id );
		} else {
			$image_id = 0;
		}
		return $image_id;
	}

	/**
	 * Gets the main product image.
	 *
	 * @param string $size (default: 'shop_thumbnail')
	 * @return string
	 */
	public function get_image( $size = 'shop_thumbnail', $attr = array() ) {
		if ( $this->variation_id && has_post_thumbnail( $this->variation_id ) ) {
			$image = get_the_post_thumbnail( $this->variation_id, $size, $attr );
		} elseif ( has_post_thumbnail( $this->id ) ) {
			$image = get_the_post_thumbnail( $this->id, $size, $attr );
		} elseif ( ( $parent_id = wp_get_post_parent_id( $this->id ) ) && has_post_thumbnail( $parent_id ) ) {
			$image = get_the_post_thumbnail( $parent_id, $size , $attr);
		} else {
			$image = wc_placeholder_img( $size );
		}
		return $image;
	}

	/**
	 * Returns whether or not the product (or variation) is stock managed.
	 *
	 * @return bool|string Bool if managed at variation level, 'parent' if managed by the parent.
	 */
	public function managing_stock() {
		if ( 'yes' === get_option( 'woocommerce_manage_stock', 'yes' ) ) {
			if ( 'no' === $this->manage_stock ) {
				if ( $this->parent->managing_stock() ) {
					return 'parent';
				}
			} else {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns number of items available for sale from the variation, or parent.
	 *
	 * @return int
	 */
	public function get_stock_quantity() {
		return true === $this->managing_stock() ? wc_stock_amount( $this->stock ) : $this->parent->get_stock_quantity();
	}

	/**
	 * Returns the tax status. Always use parent data.
	 *
	 * @return string
	 */
	public function get_tax_status() {
		return $this->parent->get_tax_status();
	}

	/**
	 * Returns whether or not the product is in stock.
	 *
	 * @return bool
	 */
	public function is_in_stock() {
		// If we're managing stock at variation level, check stock levels
		if ( true === $this->managing_stock() ) {
			if ( $this->backorders_allowed() ) {
				return true;
			} elseif ( $this->get_stock_quantity() <= get_option( 'woocommerce_notify_no_stock_amount' ) ) {
				return false;
			} else {
				return $this->stock_status === 'instock';
			}
		} else {
			return $this->stock_status === 'instock';
		}
	}

	/**
	 * Set stock level of the product variation.
	 *
	 * Uses queries rather than update_post_meta so we can do this in one query (to avoid stock issues).
	 * We cannot rely on the original loaded value in case another order was made since then.
	 *
	 * @param int $amount
	 * @param string $mode can be set, add, or subtract
	 * @return int new stock level
	 */
	public function set_stock( $amount = null, $mode = 'set' ) {
		global $wpdb;

		if ( ! is_null( $amount ) && true === $this->managing_stock() ) {

			// Ensure key exists
			add_post_meta( $this->variation_id, '_stock', 0, true );

			// Update stock in DB directly
			switch ( $mode ) {
				case 'add' :
					$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = meta_value + %f WHERE post_id = %d AND meta_key='_stock'", $amount, $this->variation_id ) );
				break;
				case 'subtract' :
					$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = meta_value - %f WHERE post_id = %d AND meta_key='_stock'", $amount, $this->variation_id ) );
				break;
				default :
					$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = %f WHERE post_id = %d AND meta_key='_stock'", $amount, $this->variation_id ) );
				break;
			}

			// Clear caches
			wp_cache_delete( $this->variation_id, 'post_meta' );

			// Clear total stock transient
			delete_transient( 'wc_product_total_stock_' . $this->id . WC_Cache_Helper::get_transient_version( 'product' ) );

			// Stock status
			$this->check_stock_status();

			// Sync the parent
			WC_Product_Variable::sync( $this->id );

			// Trigger action
			do_action( 'woocommerce_variation_set_stock', $this );

		} elseif ( ! is_null( $amount ) ) {
			return $this->parent->set_stock( $amount, $mode );
		}

		return $this->get_stock_quantity();
	}

	/**
	 * set_stock_status function.
	 */
	public function set_stock_status( $status ) {
		$status = 'outofstock' === $status ? 'outofstock' : 'instock';

		// Sanity check
		if ( true === $this->managing_stock() ) {
			if ( ! $this->backorders_allowed() && $this->get_stock_quantity() <= get_option( 'woocommerce_notify_no_stock_amount' ) ) {
				$status = 'outofstock';
			}
		} elseif ( 'parent' === $this->managing_stock() ) {
			if ( ! $this->parent->backorders_allowed() && $this->parent->get_stock_quantity() <= get_option( 'woocommerce_notify_no_stock_amount' ) ) {
				$status = 'outofstock';
			}
		}

		if ( update_post_meta( $this->variation_id, '_stock_status', $status ) ) {
			do_action( 'woocommerce_variation_set_stock_status', $this->variation_id, $status );

			WC_Product_Variable::sync_stock_status( $this->id );
		}
	}

	/**
	 * Reduce stock level of the product.
	 *
	 * @param int $amount (default: 1) Amount to reduce by
	 * @return int stock level
	 */
	public function reduce_stock( $amount = 1 ) {
		if ( true === $this->managing_stock() ) {
			return $this->set_stock( $amount, 'subtract' );
		} else {
			return $this->parent->reduce_stock( $amount );
		}
	}

	/**
	 * Increase stock level of the product.
	 *
	 * @param int $amount (default: 1) Amount to increase by
	 * @return int stock level
	 */
	public function increase_stock( $amount = 1 ) {
		if ( true === $this->managing_stock() ) {
			return $this->set_stock( $amount, 'add' );
		} else {
			return $this->parent->increase_stock( $amount );
		}
	}

	/**
	 * Returns the availability of the product.
	 *
	 * @return string
	 */
	public function get_availability() {
		$availability = $class = '';

		if ( $this->managing_stock() ) {
			if ( $this->is_in_stock() && $this->get_stock_quantity() > get_option( 'woocommerce_notify_no_stock_amount' ) ) {
				switch ( get_option( 'woocommerce_stock_format' ) ) {
					case 'no_amount' :
						$availability = __( 'In stock', 'woocommerce' );
					break;
					case 'low_amount' :
						if ( $this->get_stock_quantity() <= get_option( 'woocommerce_notify_low_stock_amount' ) ) {
							$availability = sprintf( __( 'Only %s left in stock', 'woocommerce' ), $this->get_stock_quantity() );

							if ( $this->backorders_allowed() && $this->backorders_require_notification() ) {
								$availability .= ' ' . __( '(can be backordered)', 'woocommerce' );
							}
						} else {
							$availability = __( 'In stock', 'woocommerce' );
						}
					break;
					default :
						$availability = sprintf( __( '%s in stock', 'woocommerce' ), $this->get_stock_quantity() );

						if ( $this->backorders_allowed() && $this->backorders_require_notification() ) {
							$availability .= ' ' . __( '(can be backordered)', 'woocommerce' );
						}
					break;
				}
				$class        = 'in-stock';
			} elseif ( $this->backorders_allowed() && $this->backorders_require_notification() ) {
				$availability = __( 'Available on backorder', 'woocommerce' );
				$class        = 'available-on-backorder';
			} elseif ( $this->backorders_allowed() ) {
				$availability = __( 'In stock', 'woocommerce' );
				$class        = 'in-stock';
			} else {
				$availability = __( 'Out of stock', 'woocommerce' );
				$class        = 'out-of-stock';
			}
		} elseif ( ! $this->is_in_stock() ) {
			$availability = __( 'Out of stock', 'woocommerce' );
			$class        = 'out-of-stock';
		}

		return apply_filters( 'woocommerce_get_availability', array( 'availability' => $availability, 'class' => $class ), $this );
	}

	/**
	 * Returns whether or not the product needs to notify the customer on backorder.
	 *
	 * @return bool
	 */
	public function backorders_require_notification() {
		if ( true === $this->managing_stock() ) {
			return parent::backorders_require_notification();
		} else {
			return $this->parent->backorders_require_notification();
		}
	}

	/**
	 * is_on_backorder function.
	 *
	 * @param int $qty_in_cart (default: 0)
	 * @return bool
	 */
	public function is_on_backorder( $qty_in_cart = 0 ) {
		if ( true === $this->managing_stock() ) {
			return parent::is_on_backorder( $qty_in_cart );
		} else {
			return $this->parent->is_on_backorder( $qty_in_cart );
		}
	}

	/**
	 * Returns whether or not the product has enough stock for the order.
	 *
	 * @param mixed $quantity
	 * @return bool
	 */
	public function has_enough_stock( $quantity ) {
		if ( true === $this->managing_stock() ) {
			return parent::has_enough_stock( $quantity );
		} else {
			return $this->parent->has_enough_stock( $quantity );
		}
	}

	/**
	 * Get the shipping class, and if not set, get the shipping class of the parent.
	 *
	 * @return string
	 */
	public function get_shipping_class() {
		if ( ! $this->variation_shipping_class ) {
			$classes = get_the_terms( $this->variation_id, 'product_shipping_class' );

			if ( $classes && ! is_wp_error( $classes ) ) {
				$this->variation_shipping_class = current( $classes )->slug;
			} else {
				$this->variation_shipping_class = parent::get_shipping_class();
			}
		}
		return $this->variation_shipping_class;
	}

	/**
	 * Returns the product shipping class ID.
	 *
	 * @return int
	 */
	public function get_shipping_class_id() {
		if ( ! $this->variation_shipping_class_id ) {
			$classes = get_the_terms( $this->variation_id, 'product_shipping_class' );

			if ( $classes && ! is_wp_error( $classes ) ) {
				$this->variation_shipping_class_id = current( $classes )->term_id;
			} else {
				$this->variation_shipping_class_id = parent::get_shipping_class_id();
			}
		}
		return absint( $this->variation_shipping_class_id );
	}

	/**
	 * Get product name with extra details such as SKU, price and attributes. Used within admin.
	 *
	 * @return string Formatted product name, including attributes and price
	 */
	public function get_formatted_name() {
		if ( $this->get_sku() ) {
			$identifier = $this->get_sku();
		} else {
			$identifier = '#' . $this->variation_id;
		}

		$attributes = $this->get_variation_attributes();
		$extra_data = ' &ndash; ' . implode( ', ', $attributes ) . ' &ndash; ' . wc_price( $this->get_price() );

		return sprintf( __( '%s &ndash; %s%s', 'woocommerce' ), $identifier, $this->get_title(), $extra_data );
	}

	/**
	 * Get product variation description
	 *
	 * @return string
	 */
	public function get_variation_description() {
		return wpautop( wp_kses_post( get_post_meta( $this->variation_id, '_variation_description', true ) ) );
	}
}
