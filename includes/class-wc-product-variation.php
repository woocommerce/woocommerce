<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Product Variation Class.
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
	public $variation_shipping_class         = '';

	/** @public int Stores the shipping class ID of the variation. */
	public $variation_shipping_class_id      = 0;

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

		// The post doesn't have a parent id, therefore its invalid and we should prevent this being created.
		if ( empty( $this->id ) ) {
			throw new Exception( sprintf( 'No parent product set for variation #%d', $this->variation_id ), 422 );
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
	 * Return the variation ID
	 *
	 * @since 2.5.0
	 * @return int variation (post) ID
	 */
	public function get_id() {
		return $this->variation_id;
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
	 * @param  $item_object item array If a cart or order item is passed, we can get a link containing the exact attributes selected for the variation, rather than the default attributes.
	 * @return string
	 */
	public function get_permalink( $item_object = null ) {
		if ( ! empty( $item_object['variation'] ) ) {
			$data = $item_object['variation'];
		} elseif ( ! empty( $item_object['item_meta_array'] ) ) {
			$data_keys    = array_map( 'wc_variation_attribute_name', wp_list_pluck( $item_object['item_meta_array'], 'key' ) );
			$data_values  = wp_list_pluck( $item_object['item_meta_array'], 'value' );
			$data         = array_intersect_key( array_combine( $data_keys, $data_values ), $this->variation_data );
		} else {
			$data = $this->variation_data;
		}
		return add_query_arg( array_map( 'urlencode', array_filter( $data ) ), get_permalink( $this->id ) );
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
	 * Get the add to cart button text.
	 *
	 * @return string
	 */
	public function add_to_cart_text() {
		$text = $this->is_purchasable() && $this->is_in_stock() ? __( 'Add to cart', 'woocommerce' ) : __( 'Read more', 'woocommerce' );

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
	 * Get variation ID.
	 *
	 * @return int
	 */
	public function get_variation_id() {
		return absint( $this->variation_id );
	}

	/**
	 * Get variation attribute values.
	 *
	 * @return array of attributes and their values for this variation
	 */
	public function get_variation_attributes() {
		return $this->variation_data;
	}

	/**
	 * Check if all variation's attributes are set.
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
	 * @param bool True to return $placeholder if no image is found, or false to return an empty string.
	 * @return string
	 */
	public function get_image( $size = 'shop_thumbnail', $attr = array(), $placeholder = true ) {
		if ( $this->variation_id && has_post_thumbnail( $this->variation_id ) ) {
			$image = get_the_post_thumbnail( $this->variation_id, $size, $attr );
		} elseif ( has_post_thumbnail( $this->id ) ) {
			$image = get_the_post_thumbnail( $this->id, $size, $attr );
		} elseif ( ( $parent_id = wp_get_post_parent_id( $this->id ) ) && has_post_thumbnail( $parent_id ) ) {
			$image = get_the_post_thumbnail( $parent_id, $size , $attr);
		} elseif ( $placeholder ) {
			$image = wc_placeholder_img( $size );
		} else {
			$image = '';
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
				if ( $this->parent && $this->parent->managing_stock() ) {
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
		return apply_filters( 'woocommerce_variation_get_stock_quantity', true === $this->managing_stock() ? wc_stock_amount( $this->stock ) : $this->parent->get_stock_quantity(), $this );
	}

	/**
	 * Get total stock - This is the stock of parent and children combined.
	 *
	 * @return int
	 */
	public function get_total_stock() {
		return $this->get_stock_quantity();
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
		$status = $this->stock_status === 'instock';

		/**
		 * Sanity check to ensure stock qty is not lower than 0 but still listed
		 * instock.
		 *
		 * Check is not required for products on backorder since they can be
		 * instock regardless of actual stock quantity.
		 */
		if ( true === $this->managing_stock() && ! $this->backorders_allowed() && $this->get_stock_quantity() <= get_option( 'woocommerce_notify_no_stock_amount' ) ) {
			$status = false;
		}

		return apply_filters( 'woocommerce_variation_is_in_stock', $status );
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
	 * Set stock status.
	 *
	 * @param string $status
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
	 * Is on backorder?
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
	 * Get formatted variation data with WC < 2.4 back compat and proper formatting of text-based attribute names.
	 *
	 * @return string
	 */
	public function get_formatted_variation_attributes( $flat = false ) {
		$variation_data = $this->get_variation_attributes();
		$attributes     = $this->parent->get_attributes();
		$description    = array();
		$return         = '';

		if ( is_array( $variation_data ) ) {

			if ( ! $flat ) {
				$return = '<dl class="variation">';
			}

			foreach ( $attributes as $attribute ) {

				// Only deal with attributes that are variations
				if ( ! $attribute[ 'is_variation' ] ) {
					continue;
				}

				$variation_selected_value = isset( $variation_data[ 'attribute_' . sanitize_title( $attribute[ 'name' ] ) ] ) ? $variation_data[ 'attribute_' . sanitize_title( $attribute[ 'name' ] ) ] : '';
				$description_name         = esc_html( wc_attribute_label( $attribute[ 'name' ] ) );
				$description_value        = __( 'Any', 'woocommerce' );

				// Get terms for attribute taxonomy or value if its a custom attribute
				if ( $attribute[ 'is_taxonomy' ] ) {

					$post_terms = wp_get_post_terms( $this->id, $attribute[ 'name' ] );

					foreach ( $post_terms as $term ) {
						if ( $variation_selected_value === $term->slug ) {
							$description_value = esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name ) );
						}
					}

				} else {

					$options = wc_get_text_attributes( $attribute[ 'value' ] );

					foreach ( $options as $option ) {

						if ( sanitize_title( $variation_selected_value ) === $variation_selected_value ) {
							if ( $variation_selected_value !== sanitize_title( $option ) ) {
								continue;
							}
						} else {
							if ( $variation_selected_value !== $option ) {
								continue;
							}
						}

						$description_value = esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) );
					}
				}

				if ( $flat ) {
					$description[] = $description_name . ': ' . rawurldecode( $description_value );
				} else {
					$description[] = '<dt>' . $description_name . ':</dt><dd>' . rawurldecode( $description_value ) . '</dd>';
				}
			}

			if ( $flat ) {
				$return .= implode( ', ', $description );
			} else {
				$return .= implode( '', $description );
			}

			if ( ! $flat ) {
				$return .= '</dl>';
			}
		}

		return $return;
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

		$formatted_attributes = $this->get_formatted_variation_attributes( true );
		$extra_data           = ' &ndash; ' . $formatted_attributes . ' &ndash; ' . wc_price( $this->get_price() );

		return sprintf( __( '%s &ndash; %s%s', 'woocommerce' ), $identifier, $this->get_title(), $extra_data );
	}

	/**
	 * Get product variation description.
	 *
	 * @return string
	 */
	public function get_variation_description() {
		return wpautop( do_shortcode( wp_kses_post( get_post_meta( $this->variation_id, '_variation_description', true ) ) ) );
	}
}
