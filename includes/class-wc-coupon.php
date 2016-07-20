<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce coupons
 *
 * The WooCommerce coupons class gets coupon data from storage and checks coupon validity.
 *
 * @class 		WC_Coupon
 * @version		2.3.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author		WooThemes
 *
 * @property    string $discount_type
 * @property    string $coupon_amount
 * @property    string $individual_use
 * @property    array $product_ids
 * @property    array $exclude_product_ids
 * @property    string $usage_limit
 * @property    string $usage_limit_per_user
 * @property    string $limit_usage_to_x_items
 * @property    string $usage_count
 * @property    string $expiry_date
 * @property    string $free_shipping
 * @property    array $product_categories
 * @property    array $exclude_product_categories
 * @property    string $exclude_sale_items
 * @property    string $minimum_amount
 * @property    string $maximum_amount
 * @property    array $customer_email
 */
class WC_Coupon {

	// Coupon message codes
	const E_WC_COUPON_INVALID_FILTERED               = 100;
	const E_WC_COUPON_INVALID_REMOVED                = 101;
	const E_WC_COUPON_NOT_YOURS_REMOVED              = 102;
	const E_WC_COUPON_ALREADY_APPLIED                = 103;
	const E_WC_COUPON_ALREADY_APPLIED_INDIV_USE_ONLY = 104;
	const E_WC_COUPON_NOT_EXIST                      = 105;
	const E_WC_COUPON_USAGE_LIMIT_REACHED            = 106;
	const E_WC_COUPON_EXPIRED                        = 107;
	const E_WC_COUPON_MIN_SPEND_LIMIT_NOT_MET        = 108;
	const E_WC_COUPON_NOT_APPLICABLE                 = 109;
	const E_WC_COUPON_NOT_VALID_SALE_ITEMS           = 110;
	const E_WC_COUPON_PLEASE_ENTER                   = 111;
	const E_WC_COUPON_MAX_SPEND_LIMIT_MET 			 = 112;
	const E_WC_COUPON_EXCLUDED_PRODUCTS              = 113;
	const E_WC_COUPON_EXCLUDED_CATEGORIES            = 114;
	const WC_COUPON_SUCCESS                          = 200;
	const WC_COUPON_REMOVED                          = 201;

	/** @public string Coupon code. */
	public $code   = '';

	/** @public int Coupon ID. */
	public $id     = 0;

	/** @public bool Coupon exists */
	public $exists = false;

	/**
	 * Coupon constructor. Loads coupon data.
	 *
	 * @access public
	 * @param mixed $code code of the coupon to load
	 */
	public function __construct( $code ) {
		$this->exists = $this->get_coupon( $code );
	}

	/**
	 * __isset function.
	 *
	 * @param mixed $key
	 * @return bool
	 */
	public function __isset( $key ) {
		if ( in_array( $key, array( 'coupon_custom_fields', 'type', 'amount' ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * __get function.
	 *
	 * @param mixed $key
	 * @return mixed
	 */
	public function __get( $key ) {
		// Get values or default if not set
		if ( 'coupon_custom_fields' === $key ) {
			$value = $this->id ? get_post_meta( $this->id ) : array();
		} elseif ( 'type' === $key ) {
			$value = $this->discount_type;
		} elseif ( 'amount' === $key ) {
			$value = $this->coupon_amount;
		} else {
			$value = '';
		}
		return $value;
	}

	/**
	 * Checks the coupon type.
	 *
	 * @param string $type Array or string of types
	 * @return bool
	 */
	public function is_type( $type ) {
		return ( $this->discount_type == $type || ( is_array( $type ) && in_array( $this->discount_type, $type ) ) ) ? true : false;
	}

	/**
	 * Gets an coupon from the database.
	 *
	 * @param string $code
	 * @return bool
	 */
	private function get_coupon( $code ) {
		$this->code  = apply_filters( 'woocommerce_coupon_code', $code );

		// Coupon data lets developers create coupons through code
		if ( $coupon = apply_filters( 'woocommerce_get_shop_coupon_data', false, $this->code ) ) {
			$this->populate( $coupon );
			return true;
		}

		// Otherwise get ID from the code
		$this->id    = $this->get_coupon_id_from_code( $this->code );
		$coupon_post = get_post( $this->id );

		if ( $coupon_post && $this->code === apply_filters( 'woocommerce_coupon_code', $coupon_post->post_title ) ) {
			$this->populate();
			return true;
		}

		return false;
	}

	/**
	 * Get a coupon ID from it's code.
	 * @since 2.5.0 woocommerce_coupon_code_query was removed in favour of woocommerce_get_coupon_id_from_code filter on the return. wp_cache was also implemented.
	 * @param  string $code
	 * @return int
	 */
	private function get_coupon_id_from_code( $code ) {
		global $wpdb;

		$coupon_id = wp_cache_get( WC_Cache_Helper::get_cache_prefix( 'coupons' ) . 'coupon_id_from_code_' . $code, 'coupons' );

		if ( false === $coupon_id ) {
			$sql = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish' ORDER BY post_date DESC LIMIT 1;", $this->code );

			if ( $coupon_id = apply_filters( 'woocommerce_get_coupon_id_from_code', $wpdb->get_var( $sql ), $this->code ) ) {
				wp_cache_set( WC_Cache_Helper::get_cache_prefix( 'coupons' ) . 'coupon_id_from_code_' . $code, $coupon_id, 'coupons' );
			}
		}

		return absint( $coupon_id );
	}

	/**
	 * Populates an order from the loaded post data.
	 */
	private function populate( $data = array() ) {
		$defaults = array(
			'discount_type'              => 'fixed_cart',
			'coupon_amount'              => 0,
			'individual_use'             => 'no',
			'product_ids'                => array(),
			'exclude_product_ids'        => array(),
			'usage_limit'                => '',
			'usage_limit_per_user'       => '',
			'limit_usage_to_x_items'     => '',
			'usage_count'                => '',
			'expiry_date'                => '',
			'free_shipping'              => 'no',
			'product_categories'         => array(),
			'exclude_product_categories' => array(),
			'exclude_sale_items'         => 'no',
			'minimum_amount'             => '',
			'maximum_amount'             => '',
			'customer_email'             => array()
		);

		if ( ! empty( $this->id ) ) {
			$postmeta = get_post_meta( $this->id );
		}

		foreach ( $defaults as $key => $value ) {
			// Try to load from meta if an ID is present
			if ( ! empty( $this->id ) ) {
				/**
				 * By not calling `get_post_meta()` individually, we may be breaking compatibility with.
				 * some plugins that filter on `get_post_metadata` and erroneously override based solely.
				 * on $meta_key -- but don't override when querying for all as $meta_key is empty().
				 */
				$this->$key = isset( $postmeta[ $key ] ) ? maybe_unserialize( array_shift( $postmeta[ $key ] ) ) : '';
			} else {
				$this->$key = ! empty( $data[ $key ] ) ? wc_clean( $data[ $key ] ) : '';

				// Backwards compat field names @deprecated
				if ( 'coupon_amount' === $key ) {
					$this->coupon_amount = ! empty( $data[ 'amount' ] ) ? wc_clean( $data[ 'amount' ] ) : $this->coupon_amount;
				} elseif ( 'discount_type' === $key ) {
					$this->discount_type = ! empty( $data[ 'type' ] ) ? wc_clean( $data[ 'type' ] ) : $this->discount_type;
				}
			}

			if ( empty( $this->$key ) ) {
				$this->$key = $value;
			} elseif ( in_array( $key, array( 'product_ids', 'exclude_product_ids', 'product_categories', 'exclude_product_categories', 'customer_email' ) ) ) {
				$this->$key = $this->format_array( $this->$key );
			} elseif ( in_array( $key, array( 'usage_limit', 'usage_limit_per_user', 'limit_usage_to_x_items', 'usage_count' ) ) ) {
				$this->$key = absint( $this->$key );
			} elseif( 'expiry_date' === $key ) {
				$this->expiry_date = $this->expiry_date && ! is_numeric( $this->expiry_date ) ? strtotime( $this->expiry_date ) : $this->expiry_date;
			}
		}

		do_action( 'woocommerce_coupon_loaded', $this );
	}

	/**
	 * Format loaded data as array.
	 * @param  string|array $array
	 * @return array
	 */
	public function format_array( $array ) {
		if ( ! is_array( $array ) ) {
			if ( is_serialized( $array ) ) {
				$array = maybe_unserialize( $array );
			} else {
				$array = explode( ',', $array );
			}
		}
		return array_filter( array_map( 'trim', array_map( 'strtolower', $array ) ) );
	}

	/**
	 * Check if coupon needs applying before tax.
	 *
	 * @return bool
	 */
	public function apply_before_tax() {
		return true;
	}

	/**
	 * Check if a coupon enables free shipping.
	 *
	 * @return bool
	 */
	public function enable_free_shipping() {
		return 'yes' === $this->free_shipping;
	}

	/**
	 * Check if a coupon excludes sale items.
	 *
	 * @return bool
	 */
	public function exclude_sale_items() {
		return 'yes' === $this->exclude_sale_items;
	}

	/**
	 * Increase usage count for current coupon.
	 *
	 * @param string $used_by Either user ID or billing email
	 */
	public function inc_usage_count( $used_by = '' ) {
		if ( $this->id ) {
			$this->usage_count++;
			update_post_meta( $this->id, 'usage_count', $this->usage_count );

			if ( $used_by ) {
				add_post_meta( $this->id, '_used_by', strtolower( $used_by ) );
			}
		}
	}

	/**
	 * Decrease usage count for current coupon.
	 *
	 * @param string $used_by Either user ID or billing email
	 */
	public function dcr_usage_count( $used_by = '' ) {
		if ( $this->id && $this->usage_count > 0 ) {
			global $wpdb;
			$this->usage_count--;
			update_post_meta( $this->id, 'usage_count', $this->usage_count );

			if ( $used_by ) {
				/**
				 * We're doing this the long way because `delete_post_meta( $id, $key, $value )` deletes.
				 * all instances where the key and value match, and we only want to delete one.
				 */
				$meta_id = $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM $wpdb->postmeta WHERE meta_key = '_used_by' AND meta_value = %s AND post_id = %d LIMIT 1;", $used_by, $this->id ) );
				if ( $meta_id ) {
					delete_metadata_by_mid( 'post', $meta_id );
				}
			}
		}
	}

	/**
	 * Get records of all users who have used the current coupon.
	 *
	 * @access public
	 * @return array
	 */
	public function get_used_by() {
		$_used_by = (array) get_post_meta( $this->id, '_used_by' );
		// Strip out any null values.
		return array_filter( $_used_by );
	}

	/**
	 * Returns the error_message string.
	 *
	 * @access public
	 * @return string
	 */
	public function get_error_message() {
		return $this->error_message;
	}

	/**
	 * Ensure coupon exists or throw exception.
	 *
	 * @throws Exception
	 */
	private function validate_exists() {
		if ( ! $this->exists ) {
			throw new Exception( self::E_WC_COUPON_NOT_EXIST );
		}
	}

	/**
	 * Ensure coupon usage limit is valid or throw exception.
	 *
	 * @throws Exception
	 */
	private function validate_usage_limit() {
		if ( $this->usage_limit > 0 && $this->usage_count >= $this->usage_limit ) {
			throw new Exception( self::E_WC_COUPON_USAGE_LIMIT_REACHED );
		}
	}

	/**
	 * Ensure coupon user usage limit is valid or throw exception.
	 *
	 * Per user usage limit - check here if user is logged in (against user IDs).
	 * Checked again for emails later on in WC_Cart::check_customer_coupons().
	 *
	 * @param  int  $user_id
	 * @throws Exception
	 */
	private function validate_user_usage_limit( $user_id = 0 ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		if ( $this->usage_limit_per_user > 0 && is_user_logged_in() && $this->id ) {
			global $wpdb;
			$usage_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( meta_id ) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_used_by' AND meta_value = %d;", $this->id, $user_id ) );

			if ( $usage_count >= $this->usage_limit_per_user ) {
				throw new Exception( self::E_WC_COUPON_USAGE_LIMIT_REACHED );
			}
		}
	}

	/**
	 * Ensure coupon date is valid or throw exception.
	 *
	 * @throws Exception
	 */
	private function validate_expiry_date() {
		if ( $this->expiry_date && current_time( 'timestamp' ) > $this->expiry_date ) {
			throw new Exception( $error_code = self::E_WC_COUPON_EXPIRED );
		}
	}

	/**
	 * Ensure coupon amount is valid or throw exception.
	 *
	 * @throws Exception
	 */
	private function validate_minimum_amount() {
		if ( $this->minimum_amount > 0 && apply_filters( 'woocommerce_coupon_validate_minimum_amount', wc_format_decimal( $this->minimum_amount ) > WC()->cart->get_displayed_subtotal(), $this ) ) {
			throw new Exception( self::E_WC_COUPON_MIN_SPEND_LIMIT_NOT_MET );
		}
	}

	/**
	 * Ensure coupon amount is valid or throw exception.
	 *
	 * @throws Exception
	 */
	private function validate_maximum_amount() {
		if ( $this->maximum_amount > 0 && apply_filters( 'woocommerce_coupon_validate_maximum_amount', wc_format_decimal( $this->maximum_amount ) < WC()->cart->get_displayed_subtotal(), $this ) ) {
			throw new Exception( self::E_WC_COUPON_MAX_SPEND_LIMIT_MET );
		}
	}

	/**
	 * Ensure coupon is valid for products in the cart is valid or throw exception.
	 *
	 * @throws Exception
	 */
	private function validate_product_ids() {
		if ( sizeof( $this->product_ids ) > 0 ) {
			$valid_for_cart = false;
			if ( ! WC()->cart->is_empty() ) {
				foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					if ( in_array( $cart_item['product_id'], $this->product_ids ) || in_array( $cart_item['variation_id'], $this->product_ids ) || in_array( $cart_item['data']->get_parent(), $this->product_ids ) ) {
						$valid_for_cart = true;
					}
				}
			}
			if ( ! $valid_for_cart ) {
				throw new Exception( self::E_WC_COUPON_NOT_APPLICABLE );
			}
		}
	}

	/**
	 * Ensure coupon is valid for product categories in the cart is valid or throw exception.
	 *
	 * @throws Exception
	 */
	private function validate_product_categories() {
		if ( sizeof( $this->product_categories ) > 0 ) {
			$valid_for_cart = false;
			if ( ! WC()->cart->is_empty() ) {
				foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					$product_cats = wc_get_product_cat_ids( $cart_item['product_id'] );

					// If we find an item with a cat in our allowed cat list, the coupon is valid
					if ( sizeof( array_intersect( $product_cats, $this->product_categories ) ) > 0 ) {
						$valid_for_cart = true;
					}
				}
			}
			if ( ! $valid_for_cart ) {
				throw new Exception( self::E_WC_COUPON_NOT_APPLICABLE );
			}
		}
	}

	/**
	 * Ensure coupon is valid for sale items in the cart is valid or throw exception.
	 *
	 * @throws Exception
	 */
	private function validate_sale_items() {
		if ( 'yes' === $this->exclude_sale_items && $this->is_type( wc_get_product_coupon_types() ) ) {
			$valid_for_cart      = false;
			$product_ids_on_sale = wc_get_product_ids_on_sale();

			if ( ! WC()->cart->is_empty() ) {
				foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					if ( ! empty( $cart_item['variation_id'] ) ) {
						if ( ! in_array( $cart_item['variation_id'], $product_ids_on_sale, true ) ) {
							$valid_for_cart = true;
						}
					} elseif ( ! in_array( $cart_item['product_id'], $product_ids_on_sale, true ) ) {
						$valid_for_cart = true;
					}
				}
			}
			if ( ! $valid_for_cart ) {
				throw new Exception( self::E_WC_COUPON_NOT_VALID_SALE_ITEMS );
			}
		}
	}

	/**
	 * All exclusion rules must pass at the same time for a product coupon to be valid.
	 */
	private function validate_excluded_items() {
		if ( ! WC()->cart->is_empty() && $this->is_type( wc_get_product_coupon_types() ) ) {
			$valid = false;

			foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				if ( $this->is_valid_for_product( $cart_item['data'], $cart_item ) ) {
					$valid = true;
					break;
				}
			}

			if ( ! $valid ) {
				throw new Exception( self::E_WC_COUPON_NOT_APPLICABLE );
			}
		}
	}

	/**
	 * Cart discounts cannot be added if non-eligble product is found in cart.
	 */
	private function validate_cart_excluded_items() {
		if ( ! $this->is_type( wc_get_product_coupon_types() ) ) {
			$this->validate_cart_excluded_product_ids();
			$this->validate_cart_excluded_product_categories();
			$this->validate_cart_excluded_sale_items();
		}
	}

	/**
	 * Exclude products from cart.
	 *
	 * @throws Exception
	 */
	private function validate_cart_excluded_product_ids() {
		// Exclude Products
		if ( sizeof( $this->exclude_product_ids ) > 0 ) {
			$valid_for_cart = true;
			if ( ! WC()->cart->is_empty() ) {
				foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					if ( in_array( $cart_item['product_id'], $this->exclude_product_ids ) || in_array( $cart_item['variation_id'], $this->exclude_product_ids ) || in_array( $cart_item['data']->get_parent(), $this->exclude_product_ids ) ) {
						$valid_for_cart = false;
					}
				}
			}
			if ( ! $valid_for_cart ) {
				throw new Exception( self::E_WC_COUPON_EXCLUDED_PRODUCTS );
			}
		}
	}

	/**
	 * Exclude categories from cart.
	 *
	 * @throws Exception
	 */
	private function validate_cart_excluded_product_categories() {
		if ( sizeof( $this->exclude_product_categories ) > 0 ) {
			$valid_for_cart = true;
			if ( ! WC()->cart->is_empty() ) {
				foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

					$product_cats = wc_get_product_cat_ids( $cart_item['product_id'] );

					if ( sizeof( array_intersect( $product_cats, $this->exclude_product_categories ) ) > 0 ) {
						$valid_for_cart = false;
					}
				}
			}
			if ( ! $valid_for_cart ) {
				throw new Exception( self::E_WC_COUPON_EXCLUDED_CATEGORIES );
			}
		}
	}

	/**
	 * Exclude sale items from cart.
	 *
	 * @throws Exception
	 */
	private function validate_cart_excluded_sale_items() {
		if ( $this->exclude_sale_items == 'yes' ) {
			$valid_for_cart = true;
			$product_ids_on_sale = wc_get_product_ids_on_sale();
			if ( ! WC()->cart->is_empty() ) {
				foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					if ( ! empty( $cart_item['variation_id'] ) ) {
						if ( in_array( $cart_item['variation_id'], $product_ids_on_sale, true ) ) {
							$valid_for_cart = false;
						}
					} elseif ( in_array( $cart_item['product_id'], $product_ids_on_sale, true ) ) {
						$valid_for_cart = false;
					}
				}
			}
			if ( ! $valid_for_cart ) {
				throw new Exception( self::E_WC_COUPON_NOT_VALID_SALE_ITEMS );
			}
		}
	}

	/**
	 * Check if a coupon is valid.
	 *
	 * @return boolean validity
	 * @throws Exception
	 */
	public function is_valid() {
		try {
			$this->validate_exists();
			$this->validate_usage_limit();
			$this->validate_user_usage_limit();
			$this->validate_expiry_date();
			$this->validate_minimum_amount();
			$this->validate_maximum_amount();
			$this->validate_product_ids();
			$this->validate_product_categories();
			$this->validate_sale_items();
			$this->validate_excluded_items();
			$this->validate_cart_excluded_items();

			if ( ! apply_filters( 'woocommerce_coupon_is_valid', true, $this ) ) {
				throw new Exception( self::E_WC_COUPON_INVALID_FILTERED );
			}
		} catch ( Exception $e ) {
			$this->error_message = $this->get_coupon_error( $e->getMessage() );
			return false;
		}

		return true;
	}

	/**
	 * Check if a coupon is valid.
	 *
	 * @return bool
	 */
	public function is_valid_for_cart() {
		return apply_filters( 'woocommerce_coupon_is_valid_for_cart', $this->is_type( wc_get_cart_coupon_types() ), $this );
	}

	/**
	 * Check if a coupon is valid for a product.
	 *
	 * @param  WC_Product  $product
	 * @return boolean
	 */
	public function is_valid_for_product( $product, $values = array() ) {
		if ( ! $this->is_type( wc_get_product_coupon_types() ) ) {
			return apply_filters( 'woocommerce_coupon_is_valid_for_product', false, $product, $this, $values );
		}

		$valid        = false;
		$product_cats = wc_get_product_cat_ids( $product->id );
		$product_ids  = array( $product->id, ( isset( $product->variation_id ) ? $product->variation_id : 0 ), $product->get_parent() );

		// Specific products get the discount
		if ( sizeof( $this->product_ids ) && sizeof( array_intersect( $product_ids, $this->product_ids ) ) ) {
			$valid = true;
		}

		// Category discounts
		if ( sizeof( $this->product_categories ) && sizeof( array_intersect( $product_cats, $this->product_categories ) ) ) {
			$valid = true;
		}

		// No product ids - all items discounted
		if ( ! sizeof( $this->product_ids ) && ! sizeof( $this->product_categories ) ) {
			$valid = true;
		}

		// Specific product ID's excluded from the discount
		if ( sizeof( $this->exclude_product_ids ) && sizeof( array_intersect( $product_ids, $this->exclude_product_ids ) ) ) {
			$valid = false;
		}

		// Specific categories excluded from the discount
		if ( sizeof( $this->exclude_product_categories ) && sizeof( array_intersect( $product_cats, $this->exclude_product_categories ) ) ) {
			$valid = false;
		}

		// Sale Items excluded from discount
		if ( 'yes' === $this->exclude_sale_items ) {
			$product_ids_on_sale = wc_get_product_ids_on_sale();

			if ( isset( $product->variation_id ) ) {
				if ( in_array( $product->variation_id, $product_ids_on_sale, true ) ) {
					$valid = false;
				}
			} elseif ( in_array( $product->id, $product_ids_on_sale, true ) ) {
				$valid = false;
			}
		}

		return apply_filters( 'woocommerce_coupon_is_valid_for_product', $valid, $product, $this, $values );
	}

	/**
	 * Get discount amount for a cart item.
	 *
	 * @param  float $discounting_amount Amount the coupon is being applied to
	 * @param  array|null $cart_item Cart item being discounted if applicable
	 * @param  boolean $single True if discounting a single qty item, false if its the line
	 * @return float Amount this coupon has discounted
	 */
	public function get_discount_amount( $discounting_amount, $cart_item = null, $single = false ) {
		$discount      = 0;
		$cart_item_qty = is_null( $cart_item ) ? 1 : $cart_item['quantity'];

		if ( $this->is_type( array( 'percent_product', 'percent' ) ) ) {
			$discount = $this->coupon_amount * ( $discounting_amount / 100 );

		} elseif ( $this->is_type( 'fixed_cart' ) && ! is_null( $cart_item ) && WC()->cart->subtotal_ex_tax ) {
			/**
			 * This is the most complex discount - we need to divide the discount between rows based on their price in.
			 * proportion to the subtotal. This is so rows with different tax rates get a fair discount, and so rows.
			 * with no price (free) don't get discounted.
			 *
			 * Get item discount by dividing item cost by subtotal to get a %.
			 *
			 * Uses price inc tax if prices include tax to work around https://github.com/woothemes/woocommerce/issues/7669 and https://github.com/woothemes/woocommerce/issues/8074.
			 */
			if ( wc_prices_include_tax() ) {
				$discount_percent = ( $cart_item['data']->get_price_including_tax() * $cart_item_qty ) / WC()->cart->subtotal;
			} else {
				$discount_percent = ( $cart_item['data']->get_price_excluding_tax() * $cart_item_qty ) / WC()->cart->subtotal_ex_tax;
			}
			$discount         = ( $this->coupon_amount * $discount_percent ) / $cart_item_qty;

		} elseif ( $this->is_type( 'fixed_product' ) ) {
			$discount = min( $this->coupon_amount, $discounting_amount );
			$discount = $single ? $discount : $discount * $cart_item_qty;
		}

		$discount = min( $discount, $discounting_amount );

		// Handle the limit_usage_to_x_items option
		if ( $this->is_type( array( 'percent_product', 'fixed_product' ) ) ) {
			if ( $discounting_amount ) {
				if ( '' === $this->limit_usage_to_x_items ) {
					$limit_usage_qty = $cart_item_qty;
				} else {
					$limit_usage_qty              = min( $this->limit_usage_to_x_items, $cart_item_qty );
					$this->limit_usage_to_x_items = max( 0, $this->limit_usage_to_x_items - $limit_usage_qty );
				}
				if ( $single ) {
					$discount = ( $discount * $limit_usage_qty ) / $cart_item_qty;
				} else {
					$discount = ( $discount / $cart_item_qty ) * $limit_usage_qty;
				}
			}
		}

		$discount = wc_cart_round_discount( $discount, wc_get_rounding_precision() );

		return apply_filters( 'woocommerce_coupon_get_discount_amount', $discount, $discounting_amount, $cart_item, $single, $this );
	}

	/**
	 * Converts one of the WC_Coupon message/error codes to a message string and.
	 * displays the message/error.
	 *
	 * @param int $msg_code Message/error code.
	 */
	public function add_coupon_message( $msg_code ) {

		$msg = $msg_code < 200 ? $this->get_coupon_error( $msg_code ) : $this->get_coupon_message( $msg_code );

		if ( ! $msg ) {
			return;
		}

		if ( $msg_code < 200 ) {
			wc_add_notice( $msg, 'error' );
		} else {
			wc_add_notice( $msg );
		}
	}

	/**
	 * Map one of the WC_Coupon message codes to a message string.
	 *
	 * @param integer $msg_code
	 * @return string| Message/error string
	 */
	public function get_coupon_message( $msg_code ) {
		switch ( $msg_code ) {
			case self::WC_COUPON_SUCCESS :
				$msg = __( 'Coupon code applied successfully.', 'woocommerce' );
			break;
			case self::WC_COUPON_REMOVED :
				$msg = __( 'Coupon code removed successfully.', 'woocommerce' );
			break;
			default:
				$msg = '';
			break;
		}
		return apply_filters( 'woocommerce_coupon_message', $msg, $msg_code, $this );
	}

	/**
	 * Map one of the WC_Coupon error codes to a message string.
	 *
	 * @param int $err_code Message/error code.
	 * @return string| Message/error string
	 */
	public function get_coupon_error( $err_code ) {
		switch ( $err_code ) {
			case self::E_WC_COUPON_INVALID_FILTERED:
				$err = __( 'Coupon is not valid.', 'woocommerce' );
			break;
			case self::E_WC_COUPON_NOT_EXIST:
				$err = sprintf( __( 'Coupon "%s" does not exist!', 'woocommerce' ), $this->code );
			break;
			case self::E_WC_COUPON_INVALID_REMOVED:
				$err = sprintf( __( 'Sorry, it seems the coupon "%s" is invalid - it has now been removed from your order.', 'woocommerce' ), $this->code );
			break;
			case self::E_WC_COUPON_NOT_YOURS_REMOVED:
				$err = sprintf( __( 'Sorry, it seems the coupon "%s" is not yours - it has now been removed from your order.', 'woocommerce' ), $this->code );
			break;
			case self::E_WC_COUPON_ALREADY_APPLIED:
				$err = __( 'Coupon code already applied!', 'woocommerce' );
			break;
			case self::E_WC_COUPON_ALREADY_APPLIED_INDIV_USE_ONLY:
				$err = sprintf( __( 'Sorry, coupon "%s" has already been applied and cannot be used in conjunction with other coupons.', 'woocommerce' ), $this->code );
			break;
			case self::E_WC_COUPON_USAGE_LIMIT_REACHED:
				$err = __( 'Coupon usage limit has been reached.', 'woocommerce' );
			break;
			case self::E_WC_COUPON_EXPIRED:
				$err = __( 'This coupon has expired.', 'woocommerce' );
			break;
			case self::E_WC_COUPON_MIN_SPEND_LIMIT_NOT_MET:
				$err = sprintf( __( 'The minimum spend for this coupon is %s.', 'woocommerce' ), wc_price( $this->minimum_amount ) );
			break;
			case self::E_WC_COUPON_MAX_SPEND_LIMIT_MET:
				$err = sprintf( __( 'The maximum spend for this coupon is %s.', 'woocommerce' ), wc_price( $this->maximum_amount ) );
			break;
			case self::E_WC_COUPON_NOT_APPLICABLE:
				$err = __( 'Sorry, this coupon is not applicable to your cart contents.', 'woocommerce' );
			break;
			case self::E_WC_COUPON_EXCLUDED_PRODUCTS:
				// Store excluded products that are in cart in $products
				$products = array();
				if ( ! WC()->cart->is_empty() ) {
					foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
						if ( in_array( $cart_item['product_id'], $this->exclude_product_ids ) || in_array( $cart_item['variation_id'], $this->exclude_product_ids ) || in_array( $cart_item['data']->get_parent(), $this->exclude_product_ids ) ) {
							$products[] = $cart_item['data']->get_title();
						}
					}
				}

				$err = sprintf( __( 'Sorry, this coupon is not applicable to the products: %s.', 'woocommerce' ), implode( ', ', $products ) );
				break;
			case self::E_WC_COUPON_EXCLUDED_CATEGORIES:
				// Store excluded categories that are in cart in $categories
				$categories = array();
				if ( ! WC()->cart->is_empty() ) {
					foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
						$product_cats = wc_get_product_cat_ids( $cart_item['product_id'] );

						if ( sizeof( $intersect = array_intersect( $product_cats, $this->exclude_product_categories ) ) > 0 ) {

							foreach( $intersect as $cat_id) {
								$cat = get_term( $cat_id, 'product_cat' );
								$categories[] = $cat->name;
							}
						}
					}
				}

				$err = sprintf( __( 'Sorry, this coupon is not applicable to the categories: %s.', 'woocommerce' ), implode( ', ', array_unique( $categories ) ) );
				break;
			case self::E_WC_COUPON_NOT_VALID_SALE_ITEMS:
				$err = __( 'Sorry, this coupon is not valid for sale items.', 'woocommerce' );
			break;
			default:
				$err = '';
			break;
		}
		return apply_filters( 'woocommerce_coupon_error', $err, $err_code, $this );
	}

	/**
	 * Map one of the WC_Coupon error codes to an error string.
	 * No coupon instance will be available where a coupon does not exist,
	 * so this static method exists.
	 *
	 * @param int $err_code Error code
	 * @return string| Error string
	 */
	public static function get_generic_coupon_error( $err_code ) {
		switch ( $err_code ) {
			case self::E_WC_COUPON_NOT_EXIST:
				$err = __( 'Coupon does not exist!', 'woocommerce' );
			break;
			case self::E_WC_COUPON_PLEASE_ENTER:
				$err = __( 'Please enter a coupon code.', 'woocommerce' );
			break;
			default:
				$err = '';
			break;
		}
		// When using this static method, there is no $this to pass to filter
		return apply_filters( 'woocommerce_coupon_error', $err, $err_code, null );
	}
}
