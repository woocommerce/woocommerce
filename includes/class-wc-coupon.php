<?php
include_once( 'legacy/class-wc-legacy-coupon.php' );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce coupons.
 *
 * The WooCommerce coupons class gets coupon data from storage and checks coupon validity.
 *
 * @class 		WC_Coupon
 * @version		3.0.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author		WooThemes
 */
class WC_Coupon extends WC_Legacy_Coupon {

	/**
	 * Data array, with defaults.
	 * @since 3.0.0
	 * @var array
	 */
	protected $data = array(
		'code'                        => '',
		'amount'                      => 0,
		'date_created'                => null,
		'date_modified'               => null,
		'date_expires'                => null,
		'discount_type'               => 'fixed_cart',
		'description'                 => '',
		'usage_count'                 => 0,
		'individual_use'              => false,
		'product_ids'                 => array(),
		'excluded_product_ids'        => array(),
		'usage_limit'                 => 0,
		'usage_limit_per_user'        => 0,
		'limit_usage_to_x_items'      => 0,
		'free_shipping'               => false,
		'product_categories'          => array(),
		'excluded_product_categories' => array(),
		'exclude_sale_items'          => false,
		'minimum_amount'              => '',
		'maximum_amount'              => '',
		'email_restrictions'          => array(),
		'used_by'                     => array(),
	);

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

	/**
	 * Cache group.
	 * @var string
	 */
	protected $cache_group = 'coupons';

	/**
	 * Coupon constructor. Loads coupon data.
	 * @param mixed $data Coupon data, object, ID or code.
	 */
	public function __construct( $data = '' ) {
		parent::__construct( $data );

		if ( $data instanceof WC_Coupon ) {
			$this->set_id( absint( $data->get_id() ) );
		} elseif ( $coupon = apply_filters( 'woocommerce_get_shop_coupon_data', false, $data ) ) {
			$this->read_manual_coupon( $data, $coupon );
			return;
		} elseif ( is_numeric( $data ) && 'shop_coupon' === get_post_type( $data ) ) {
			$this->set_id( $data );
		} elseif ( ! empty( $data ) ) {
			$this->set_id( wc_get_coupon_id_by_code( $data ) );
			$this->set_code( $data );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = WC_Data_Store::load( 'coupon' );
		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/**
	 * Checks the coupon type.
	 * @param  string $type Array or string of types
	 * @return bool
	 */
	public function is_type( $type ) {
		return ( $this->get_discount_type() === $type || ( is_array( $type ) && in_array( $this->get_discount_type(), $type ) ) );
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @since  3.0.0
	 * @return string
	 */
	protected function get_hook_prefix() {
		return 'woocommerce_coupon_get_';
	}

	/*
	 |--------------------------------------------------------------------------
	 | Getters
	 |--------------------------------------------------------------------------
	 |
	 | Methods for getting data from the coupon object.
	 |
	 */

	/**
	 * Get coupon code.
	 * @since  3.0.0
	 * @param  string $context
	 * @return string
	 */
	public function get_code( $context = 'view' ) {
		return $this->get_prop( 'code', $context );
	}

	/**
	 * Get coupon description.
	 * @since  3.0.0
	 * @param  string $context
	 * @return string
	 */
	public function get_description( $context = 'view' ) {
		return $this->get_prop( 'description', $context );
	}

	/**
	 * Get discount type.
	 * @since  3.0.0
	 * @param  string $context
	 * @return string
	 */
	public function get_discount_type( $context = 'view' ) {
		return $this->get_prop( 'discount_type', $context );
	}

	/**
	 * Get coupon code.
	 * @since  3.0.0
	 * @param  string $context
	 * @return float
	 */
	public function get_amount( $context = 'view' ) {
		return $this->get_prop( 'amount', $context );
	}

	/**
	 * Get coupon expiration date.
	 * @since  3.0.0
	 * @param  string $context
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_expires( $context = 'view' ) {
		return $this->get_prop( 'date_expires', $context );
	}

	/**
	 * Get date_created
	 * @since  3.0.0
	 * @param  string $context
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Get date_modified
	 * @since  3.0.0
	 * @param  string $context
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Get coupon usage count.
	 * @since  3.0.0
	 * @param  string $context
	 * @return integer
	 */
	public function get_usage_count( $context = 'view' ) {
		return $this->get_prop( 'usage_count', $context );
	}

	/**
	 * Get the "indvidual use" checkbox status.
	 * @since  3.0.0
	 * @param  string $context
	 * @return bool
	 */
	public function get_individual_use( $context = 'view' ) {
		return $this->get_prop( 'individual_use', $context );
	}

	/**
	 * Get product IDs this coupon can apply to.
	 * @since  3.0.0
	 * @param  string $context
	 * @return array
	 */
	public function get_product_ids( $context = 'view' ) {
		return $this->get_prop( 'product_ids', $context );
	}

	/**
	 * Get product IDs that this coupon should not apply to.
	 * @since  3.0.0
	 * @param  string $context
	 * @return array
	 */
	public function get_excluded_product_ids( $context = 'view' ) {
		return $this->get_prop( 'excluded_product_ids', $context );
	}

	/**
	 * Get coupon usage limit.
	 * @since  3.0.0
	 * @param  string $context
	 * @return integer
	 */
	public function get_usage_limit( $context = 'view' ) {
		return $this->get_prop( 'usage_limit', $context );
	}

	/**
	 * Get coupon usage limit per customer (for a single customer)
	 * @since  3.0.0
	 * @param  string $context
	 * @return integer
	 */
	public function get_usage_limit_per_user( $context = 'view' ) {
		return $this->get_prop( 'usage_limit_per_user', $context );
	}

	/**
	 * Usage limited to certain amount of items
	 * @since  3.0.0
	 * @param  string $context
	 * @return integer
	 */
	public function get_limit_usage_to_x_items( $context = 'view' ) {
		return $this->get_prop( 'limit_usage_to_x_items', $context );
	}

	/**
	 * If this coupon grants free shipping or not.
	 * @since  3.0.0
	 * @param  string $context
	 * @return bool
	 */
	public function get_free_shipping( $context = 'view' ) {
		return $this->get_prop( 'free_shipping', $context );
	}

	/**
	 * Get product categories this coupon can apply to.
	 * @since  3.0.0
	 * @param  string $context
	 * @return array
	 */
	public function get_product_categories( $context = 'view' ) {
		return $this->get_prop( 'product_categories', $context );
	}

	/**
	 * Get product categories this coupon cannot not apply to.
	 * @since  3.0.0
	 * @param  string $context
	 * @return array
	 */
	public function get_excluded_product_categories( $context = 'view' ) {
		return $this->get_prop( 'excluded_product_categories', $context );
	}

	/**
	 * If this coupon should exclude items on sale.
	 * @since  3.0.0
	 * @param  string $context
	 * @return bool
	 */
	public function get_exclude_sale_items( $context = 'view' ) {
		return $this->get_prop( 'exclude_sale_items', $context );
	}

	/**
	 * Get minium spend amount.
	 * @since  3.0.0
	 * @param  string $context
	 * @return float
	 */
	public function get_minimum_amount( $context = 'view' ) {
		return $this->get_prop( 'minimum_amount', $context );
	}
	/**
	 * Get maximum spend amount.
	 * @since  3.0.0
	 * @param  string $context
	 * @return float
	 */
	public function get_maximum_amount( $context = 'view' ) {
		return $this->get_prop( 'maximum_amount', $context );
	}

	/**
	 * Get emails to check customer usage restrictions.
	 * @since  3.0.0
	 * @param  string $context
	 * @return array
	 */
	public function get_email_restrictions( $context = 'view' ) {
		return $this->get_prop( 'email_restrictions', $context );
	}

	/**
	 * Get records of all users who have used the current coupon.
	 * @since  3.0.0
	 * @param  string $context
	 * @return array
	 */
	public function get_used_by( $context = 'view' ) {
		return $this->get_prop( 'used_by', $context );
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

		if ( $this->is_type( array( 'percent' ) ) ) {
			$discount = (float) $this->get_amount() * ( $discounting_amount / 100 );
		} elseif ( $this->is_type( 'fixed_cart' ) && ! is_null( $cart_item ) && WC()->cart->subtotal_ex_tax ) {
			/**
			 * This is the most complex discount - we need to divide the discount between rows based on their price in.
			 * proportion to the subtotal. This is so rows with different tax rates get a fair discount, and so rows.
			 * with no price (free) don't get discounted.
			 *
			 * Get item discount by dividing item cost by subtotal to get a %.
			 *
			 * Uses price inc tax if prices include tax to work around https://github.com/woocommerce/woocommerce/issues/7669 and https://github.com/woocommerce/woocommerce/issues/8074.
			 */
			if ( wc_prices_include_tax() ) {
				$discount_percent = ( wc_get_price_including_tax( $cart_item['data'] ) * $cart_item_qty ) / WC()->cart->subtotal;
			} else {
				$discount_percent = ( wc_get_price_excluding_tax( $cart_item['data'] ) * $cart_item_qty ) / WC()->cart->subtotal_ex_tax;
			}
			$discount = ( (float) $this->get_amount() * $discount_percent ) / $cart_item_qty;

		} elseif ( $this->is_type( 'fixed_product' ) ) {
			$discount = min( $this->get_amount(), $discounting_amount );
			$discount = $single ? $discount : $discount * $cart_item_qty;
		}

		$discount = (float) min( $discount, $discounting_amount );

		// Handle the limit_usage_to_x_items option
		if ( ! $this->is_type( array( 'fixed_cart' ) ) ) {
			if ( $discounting_amount ) {
				if ( ! $this->get_limit_usage_to_x_items() ) {
					$limit_usage_qty = $cart_item_qty;
				} else {
					$limit_usage_qty = min( $this->get_limit_usage_to_x_items(), $cart_item_qty );
					$this->set_limit_usage_to_x_items( max( 0, $this->get_limit_usage_to_x_items() - $limit_usage_qty ) );
				}
				if ( $single ) {
					$discount = ( $discount * $limit_usage_qty ) / $cart_item_qty;
				} else {
					$discount = ( $discount / $cart_item_qty ) * $limit_usage_qty;
				}
			}
		}

		$discount = round( $discount, wc_get_rounding_precision() );

		return apply_filters( 'woocommerce_coupon_get_discount_amount', $discount, $discounting_amount, $cart_item, $single, $this );
	}

	/*
	 |--------------------------------------------------------------------------
	 | Setters
	 |--------------------------------------------------------------------------
	 |
	 | Functions for setting coupon data. These should not update anything in the
	 | database itself and should only change what is stored in the class
	 | object.
	 |
	 */

	/**
	 * Set coupon code.
	 * @since  3.0.0
	 * @param  string $code
	 * @throws WC_Data_Exception
	 */
	public function set_code( $code ) {
		$this->set_prop( 'code', wc_format_coupon_code( $code ) );
	}

	/**
	 * Set coupon description.
	 * @since  3.0.0
	 * @param  string $description
	 * @throws WC_Data_Exception
	 */
	public function set_description( $description ) {
		$this->set_prop( 'description', $description );
	}

	/**
	 * Set discount type.
	 * @since  3.0.0
	 * @param  string $discount_type
	 * @throws WC_Data_Exception
	 */
	public function set_discount_type( $discount_type ) {
		if ( 'percent_product' === $discount_type ) {
			$discount_type = 'percent'; // Backwards compatibility.
		}
		if ( ! in_array( $discount_type, array_keys( wc_get_coupon_types() ) ) ) {
			$this->error( 'coupon_invalid_discount_type', __( 'Invalid discount type', 'woocommerce' ) );
		}
		$this->set_prop( 'discount_type', $discount_type );
	}

	/**
	 * Set amount.
	 * @since  3.0.0
	 * @param  float $amount
	 * @throws WC_Data_Exception
	 */
	public function set_amount( $amount ) {
		$this->set_prop( 'amount', wc_format_decimal( $amount ) );
	}

	/**
	 * Set expiration date.
	 * @since  3.0.0
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 * @throws WC_Data_Exception
	 */
	public function set_date_expires( $date ) {
		$this->set_date_prop( 'date_expires', $date );
	}

	/**
	 * Set date_created
	 * @since  3.0.0
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 * @throws WC_Data_Exception
	 */
	public function set_date_created( $date ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set date_modified
	 * @since  3.0.0
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 * @throws WC_Data_Exception
	 */
	public function set_date_modified( $date ) {
		$this->set_date_prop( 'date_modified', $date );
	}

	/**
	 * Set how many times this coupon has been used.
	 * @since  3.0.0
	 * @param  int $usage_count
	 * @throws WC_Data_Exception
	 */
	public function set_usage_count( $usage_count ) {
		$this->set_prop( 'usage_count', absint( $usage_count ) );
	}

	/**
	 * Set if this coupon can only be used once.
	 * @since  3.0.0
	 * @param  bool $is_individual_use
	 * @throws WC_Data_Exception
	 */
	public function set_individual_use( $is_individual_use ) {
		$this->set_prop( 'individual_use', (bool) $is_individual_use );
	}

	/**
	 * Set the product IDs this coupon can be used with.
	 * @since  3.0.0
	 * @param  array $product_ids
	 * @throws WC_Data_Exception
	 */
	public function set_product_ids( $product_ids ) {
		$this->set_prop( 'product_ids', array_filter( wp_parse_id_list( (array) $product_ids ) ) );
	}

	/**
	 * Set the product IDs this coupon cannot be used with.
	 * @since  3.0.0
	 * @param  array $excluded_product_ids
	 * @throws WC_Data_Exception
	 */
	public function set_excluded_product_ids( $excluded_product_ids ) {
		$this->set_prop( 'excluded_product_ids', array_filter( wp_parse_id_list( (array) $excluded_product_ids ) ) );
	}

	/**
	 * Set the amount of times this coupon can be used.
	 * @since  3.0.0
	 * @param  int $usage_limit
	 * @throws WC_Data_Exception
	 */
	public function set_usage_limit( $usage_limit ) {
		$this->set_prop( 'usage_limit', absint( $usage_limit ) );
	}

	/**
	 * Set the amount of times this coupon can be used per user.
	 * @since  3.0.0
	 * @param  int $usage_limit
	 * @throws WC_Data_Exception
	 */
	public function set_usage_limit_per_user( $usage_limit ) {
		$this->set_prop( 'usage_limit_per_user', absint( $usage_limit ) );
	}

	/**
	 * Set usage limit to x number of items.
	 * @since  3.0.0
	 * @param  int $limit_usage_to_x_items
	 * @throws WC_Data_Exception
	 */
	public function set_limit_usage_to_x_items( $limit_usage_to_x_items ) {
		$this->set_prop( 'limit_usage_to_x_items', absint( $limit_usage_to_x_items ) );
	}

	/**
	 * Set if this coupon enables free shipping or not.
	 * @since  3.0.0
	 * @param  bool $free_shipping
	 * @throws WC_Data_Exception
	 */
	public function set_free_shipping( $free_shipping ) {
		$this->set_prop( 'free_shipping', (bool) $free_shipping );
	}

	/**
	 * Set the product category IDs this coupon can be used with.
	 * @since  3.0.0
	 * @param  array $product_categories
	 * @throws WC_Data_Exception
	 */
	public function set_product_categories( $product_categories ) {
		$this->set_prop( 'product_categories', array_filter( wp_parse_id_list( (array) $product_categories ) ) );
	}

	/**
	 * Set the product category IDs this coupon cannot be used with.
	 * @since  3.0.0
	 * @param  array $excluded_product_categories
	 * @throws WC_Data_Exception
	 */
	public function set_excluded_product_categories( $excluded_product_categories ) {
		$this->set_prop( 'excluded_product_categories', array_filter( wp_parse_id_list( (array) $excluded_product_categories ) ) );
	}

	/**
	 * Set if this coupon should excluded sale items or not.
	 * @since  3.0.0
	 * @param  bool $exclude_sale_items
	 * @throws WC_Data_Exception
	 */
	public function set_exclude_sale_items( $exclude_sale_items ) {
		$this->set_prop( 'exclude_sale_items', (bool) $exclude_sale_items );
	}

	/**
	 * Set the minimum spend amount.
	 * @since  3.0.0
	 * @param  float $amount
	 * @throws WC_Data_Exception
	 */
	public function set_minimum_amount( $amount ) {
		$this->set_prop( 'minimum_amount', wc_format_decimal( $amount ) );
	}

	/**
	 * Set the maximum spend amount.
	 * @since  3.0.0
	 * @param  float $amount
	 * @throws WC_Data_Exception
	 */
	public function set_maximum_amount( $amount ) {
		$this->set_prop( 'maximum_amount', wc_format_decimal( $amount ) );
	}

	/**
	 * Set email restrictions.
	 * @since  3.0.0
	 * @param  array $emails
	 * @throws WC_Data_Exception
	 */
	public function set_email_restrictions( $emails = array() ) {
		$emails = array_filter( array_map( 'sanitize_email', (array) $emails ) );
		foreach ( $emails as $email ) {
			if ( ! is_email( $email ) ) {
				$this->error( 'coupon_invalid_email_address', __( 'Invalid email address restriction', 'woocommerce' ) );
			}
		}
		$this->set_prop( 'email_restrictions', $emails );
	}

	/**
	 * Set which users have used this coupon.
	 * @since 3.0.0
	 * @param array $used_by
	 * @throws WC_Data_Exception
	 */
	public function set_used_by( $used_by ) {
		$this->set_prop( 'used_by', array_filter( $used_by ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Other Actions
	|--------------------------------------------------------------------------
	*/

	/**
	 * Developers can programically return coupons. This function will read those values into our WC_Coupon class.
	 * @since  3.0.0
	 * @param  string $code  Coupon code
	 * @param  array $coupon Array of coupon properties
	 */
	public function read_manual_coupon( $code, $coupon ) {
		foreach ( $coupon as $key => $value ) {
			switch ( $key ) {
				case 'excluded_product_ids' :
				case 'exclude_product_ids' :
					if ( ! is_array( $coupon[ $key ] ) ) {
						wc_doing_it_wrong( $key, $key . ' should be an array instead of a string.', '3.0' );
						$coupon['excluded_product_ids'] = wc_string_to_array( $value );
					}
					break;
				case 'exclude_product_categories' :
				case 'excluded_product_categories' :
					if ( ! is_array( $coupon[ $key ] ) ) {
						wc_doing_it_wrong( $key, $key . ' should be an array instead of a string.', '3.0' );
						$coupon['excluded_product_categories'] = wc_string_to_array( $value );
					}
					break;
				case 'product_ids' :
					if ( ! is_array( $coupon[ $key ] ) ) {
						wc_doing_it_wrong( $key, $key . ' should be an array instead of a string.', '3.0' );
						$coupon[ $key ] = wc_string_to_array( $value );
					}
					break;
				case 'individual_use' :
				case 'free_shipping' :
				case 'exclude_sale_items' :
					if ( ! is_bool( $coupon[ $key ] ) ) {
						wc_doing_it_wrong( $key, $key . ' should be true or false instead of yes or no.', '3.0' );
						$coupon[ $key ] = wc_string_to_bool( $value );
					}
					break;
				case 'expiry_date' :
					$coupon['date_expires'] = $value;
					break;
			}
		}
		$this->set_code( $code );
		$this->set_props( $coupon );
	}

	/**
	 * Increase usage count for current coupon.
	 *
	 * @param string $used_by Either user ID or billing email
	 */
	public function increase_usage_count( $used_by = '' ) {
		if ( $this->get_id() && $this->data_store ) {
			$new_count = $this->data_store->increase_usage_count( $this, $used_by );

			// Bypass set_prop and remove pending changes since the data store saves the count already.
			$this->data['usage_count'] = $new_count;
			if ( isset( $this->changes['usage_count'] ) ) {
				unset( $this->changes['usage_count'] );
			}
		}
	}

	/**
	 * Decrease usage count for current coupon.
	 *
	 * @param string $used_by Either user ID or billing email
	 */
	public function decrease_usage_count( $used_by = '' ) {
		if ( $this->get_id() && $this->get_usage_count() > 0 && $this->data_store ) {
			$new_count = $this->data_store->decrease_usage_count( $this, $used_by );

			// Bypass set_prop and remove pending changes since the data store saves the count already.
			$this->data['usage_count'] = $new_count;
			if ( isset( $this->changes['usage_count'] ) ) {
				unset( $this->changes['usage_count'] );
			}
		}
	}

	/*
	 |--------------------------------------------------------------------------
	 | Validation & Error Handling
	 |--------------------------------------------------------------------------
	 */

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
		if ( ! $this->get_id() ) {
			throw new Exception( self::E_WC_COUPON_NOT_EXIST );
		}
	}

	/**
	 * Ensure coupon usage limit is valid or throw exception.
	 *
	 * @throws Exception
	 */
	private function validate_usage_limit() {
		if ( $this->get_usage_limit() > 0 && $this->get_usage_count() >= $this->get_usage_limit() ) {
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
		if ( $this->get_usage_limit_per_user() > 0 && is_user_logged_in() && $this->get_id() && $this->data_store ) {
			global $wpdb;
			$usage_count = $this->data_store->get_usage_by_user_id( $this, $user_id );
			if ( $usage_count >= $this->get_usage_limit_per_user() ) {
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
		if ( $this->get_date_expires() && current_time( 'timestamp', true ) > $this->get_date_expires()->getTimestamp() ) {
			throw new Exception( $error_code = self::E_WC_COUPON_EXPIRED );
		}
	}

	/**
	 * Ensure coupon amount is valid or throw exception.
	 *
	 * @throws Exception
	 */
	private function validate_minimum_amount() {
		if ( $this->get_minimum_amount() > 0 && apply_filters( 'woocommerce_coupon_validate_minimum_amount', $this->get_minimum_amount() > WC()->cart->get_displayed_subtotal(), $this ) ) {
			throw new Exception( self::E_WC_COUPON_MIN_SPEND_LIMIT_NOT_MET );
		}
	}

	/**
	 * Ensure coupon amount is valid or throw exception.
	 *
	 * @throws Exception
	 */
	private function validate_maximum_amount() {
		if ( $this->get_maximum_amount() > 0 && apply_filters( 'woocommerce_coupon_validate_maximum_amount', $this->get_maximum_amount() < WC()->cart->get_displayed_subtotal(), $this ) ) {
			throw new Exception( self::E_WC_COUPON_MAX_SPEND_LIMIT_MET );
		}
	}

	/**
	 * Ensure coupon is valid for products in the cart is valid or throw exception.
	 *
	 * @throws Exception
	 */
	private function validate_product_ids() {
		if ( sizeof( $this->get_product_ids() ) > 0 ) {
			$valid_for_cart = false;
			if ( ! WC()->cart->is_empty() ) {
				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					if ( in_array( $cart_item['product_id'], $this->get_product_ids() ) || in_array( $cart_item['variation_id'], $this->get_product_ids() ) || in_array( $cart_item['data']->get_parent_id(), $this->get_product_ids() ) ) {
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
		if ( sizeof( $this->get_product_categories() ) > 0 ) {
			$valid_for_cart = false;
			if ( ! WC()->cart->is_empty() ) {
				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					$product_cats = wc_get_product_cat_ids( $cart_item['product_id'] );

					// If we find an item with a cat in our allowed cat list, the coupon is valid
					if ( sizeof( array_intersect( $product_cats, $this->get_product_categories() ) ) > 0 ) {
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
		if ( $this->get_exclude_sale_items() ) {
			$valid_for_cart      = false;
			$product_ids_on_sale = wc_get_product_ids_on_sale();

			if ( ! WC()->cart->is_empty() ) {
				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
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

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
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
		}
	}

	/**
	 * Exclude products from cart.
	 *
	 * @throws Exception
	 */
	private function validate_cart_excluded_product_ids() {
		// Exclude Products
		if ( sizeof( $this->get_excluded_product_ids() ) > 0 ) {
			$valid_for_cart = true;
			if ( ! WC()->cart->is_empty() ) {
				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					if ( in_array( $cart_item['product_id'], $this->get_excluded_product_ids() ) || in_array( $cart_item['variation_id'], $this->get_excluded_product_ids() ) || in_array( $cart_item['data']->get_parent_id(), $this->get_excluded_product_ids() ) ) {
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
		if ( sizeof( $this->get_excluded_product_categories() ) > 0 ) {
			$valid_for_cart = true;
			if ( ! WC()->cart->is_empty() ) {
				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					$product_cats = wc_get_product_cat_ids( $cart_item['product_id'] );
					if ( sizeof( array_intersect( $product_cats, $this->get_excluded_product_categories() ) ) > 0 ) {
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
		$product_cats = wc_get_product_cat_ids( $product->get_id() );
		$product_ids  = array( $product->get_id(), $product->get_parent_id() );

		// Specific products get the discount
		if ( sizeof( $this->get_product_ids() ) && sizeof( array_intersect( $product_ids, $this->get_product_ids() ) ) ) {
			$valid = true;
		}

		// Category discounts
		if ( sizeof( $this->get_product_categories() ) && sizeof( array_intersect( $product_cats, $this->get_product_categories() ) ) ) {
			$valid = true;
		}

		// No product ids - all items discounted
		if ( ! sizeof( $this->get_product_ids() ) && ! sizeof( $this->get_product_categories() ) ) {
			$valid = true;
		}

		// Specific product IDs excluded from the discount
		if ( sizeof( $this->get_excluded_product_ids() ) && sizeof( array_intersect( $product_ids, $this->get_excluded_product_ids() ) ) ) {
			$valid = false;
		}

		// Specific categories excluded from the discount
		if ( sizeof( $this->get_excluded_product_categories() ) && sizeof( array_intersect( $product_cats, $this->get_excluded_product_categories() ) ) ) {
			$valid = false;
		}

		// Sale Items excluded from discount
		if ( $this->get_exclude_sale_items() ) {
			$product_ids_on_sale = wc_get_product_ids_on_sale();

			if ( in_array( $product->get_id(), $product_ids_on_sale, true ) ) {
				$valid = false;
			}
		}

		return apply_filters( 'woocommerce_coupon_is_valid_for_product', $valid, $product, $this, $values );
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
				/* translators: %s: coupon code */
				$err = sprintf( __( 'Coupon "%s" does not exist!', 'woocommerce' ), $this->get_code() );
			break;
			case self::E_WC_COUPON_INVALID_REMOVED:
				/* translators: %s: coupon code */
				$err = sprintf( __( 'Sorry, it seems the coupon "%s" is invalid - it has now been removed from your order.', 'woocommerce' ), $this->get_code() );
			break;
			case self::E_WC_COUPON_NOT_YOURS_REMOVED:
				/* translators: %s: coupon code */
				$err = sprintf( __( 'Sorry, it seems the coupon "%s" is not yours - it has now been removed from your order.', 'woocommerce' ), $this->get_code() );
			break;
			case self::E_WC_COUPON_ALREADY_APPLIED:
				$err = __( 'Coupon code already applied!', 'woocommerce' );
			break;
			case self::E_WC_COUPON_ALREADY_APPLIED_INDIV_USE_ONLY:
				/* translators: %s: coupon code */
				$err = sprintf( __( 'Sorry, coupon "%s" has already been applied and cannot be used in conjunction with other coupons.', 'woocommerce' ), $this->get_code() );
			break;
			case self::E_WC_COUPON_USAGE_LIMIT_REACHED:
				$err = __( 'Coupon usage limit has been reached.', 'woocommerce' );
			break;
			case self::E_WC_COUPON_EXPIRED:
				$err = __( 'This coupon has expired.', 'woocommerce' );
			break;
			case self::E_WC_COUPON_MIN_SPEND_LIMIT_NOT_MET:
				/* translators: %s: coupon minimum amount */
				$err = sprintf( __( 'The minimum spend for this coupon is %s.', 'woocommerce' ), wc_price( $this->get_minimum_amount() ) );
			break;
			case self::E_WC_COUPON_MAX_SPEND_LIMIT_MET:
				/* translators: %s: coupon maximum amount */
				$err = sprintf( __( 'The maximum spend for this coupon is %s.', 'woocommerce' ), wc_price( $this->get_maximum_amount() ) );
			break;
			case self::E_WC_COUPON_NOT_APPLICABLE:
				$err = __( 'Sorry, this coupon is not applicable to your cart contents.', 'woocommerce' );
			break;
			case self::E_WC_COUPON_EXCLUDED_PRODUCTS:
				// Store excluded products that are in cart in $products
				$products = array();
				if ( ! WC()->cart->is_empty() ) {
					foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
						if ( in_array( $cart_item['product_id'], $this->get_excluded_product_ids() ) || in_array( $cart_item['variation_id'], $this->get_excluded_product_ids() ) || in_array( $cart_item['data']->get_parent_id(), $this->get_excluded_product_ids() ) ) {
							$products[] = $cart_item['data']->get_name();
						}
					}
				}

				/* translators: %s: products list */
				$err = sprintf( __( 'Sorry, this coupon is not applicable to the products: %s.', 'woocommerce' ), implode( ', ', $products ) );
				break;
			case self::E_WC_COUPON_EXCLUDED_CATEGORIES:
				// Store excluded categories that are in cart in $categories
				$categories = array();
				if ( ! WC()->cart->is_empty() ) {
					foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
						$product_cats = wc_get_product_cat_ids( $cart_item['product_id'] );

						if ( sizeof( $intersect = array_intersect( $product_cats, $this->get_excluded_product_categories() ) ) > 0 ) {

							foreach ( $intersect as $cat_id ) {
								$cat = get_term( $cat_id, 'product_cat' );
								$categories[] = $cat->name;
							}
						}
					}
				}

				/* translators: %s: categories list */
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
