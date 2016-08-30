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
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author		WooThemes
 */
class WC_Coupon extends WC_Legacy_Coupon {

	/**
	 * Data array, with defaults.
	 * @since 2.7.0
	 * @var array
	 */
	protected $_data = array(
		'id'                          => 0,
		'code'                        => '',
		'amount'                      => 0,
		'date_created'                => '',
		'date_modified'               => '',
		'discount_type'               => 'fixed_cart',
		'description'                 => '',
		'date_expires'                => '',
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
		'used_by'                     => '',
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
	 * Internal meta type used to store coupon data.
	 * @since 2.7.0
	 * @var string
	 */
	protected $_meta_type = 'post';

	/**
	 * Data stored in meta keys, but not considered "meta" for a coupon.
	 * @since 2.7.0
	 * @var array
	 */
	protected $_internal_meta_keys = array(
		'discount_type', 'coupon_amount', 'expiry_date', 'usage_count',
		'individual_use', 'product_ids', 'exclude_product_ids', 'usage_limit',
		'usage_limit_per_user', 'limit_usage_to_x_items', 'free_shipping',
		'product_categories', 'exclude_product_categories', 'exclude_sale_items',
		'minimum_amount', 'maximum_amount', 'customer_email', '_used_by',
		'_edit_lock', '_edit_last',
	);

	/**
	 * Coupon constructor. Loads coupon data.
	 * @param mixed $data Coupon data, object, ID or code.
	 */
	public function __construct( $data = '' ) {
		parent::__construct( $data );

		if ( $data instanceof WC_Coupon ) {
			$this->read( absint( $data->get_id() ) );
		} elseif ( $coupon = apply_filters( 'woocommerce_get_shop_coupon_data', false, $data ) ) {
			_doing_it_wrong( 'woocommerce_get_shop_coupon_data', 'Reading a manual coupon via woocommerce_get_shop_coupon_data has been deprecated. Please sent an instance of WC_Coupon instead.', '2.7' );
			$this->read_manual_coupon( $data, $coupon );
		} elseif ( is_numeric( $data ) && 'shop_coupon' === get_post_type( $data ) ) {
			$this->read( $data );
		} elseif ( ! empty( $data ) ) {
			$this->set_code( $data );
			$this->read( wc_get_coupon_id_by_code( $data ) );
		}
	}

	/**
	 * Checks the coupon type.
	 * @param  string $type Array or string of types
	 * @return bool
	 */
	public function is_type( $type ) {
		return ( $this->get_discount_type() == $type || ( is_array( $type ) && in_array( $this->get_discount_type(), $type ) ) );
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
    * Get coupon ID.
    * @since  2.7.0
    * @return integer
    */
	public function get_id() {
		return $this->_data['id'];
	}

	/**
	 * Get coupon code.
	 * @since  2.7.0
	 * @return string
	 */
	public function get_code() {
		return $this->_data['code'];
	}

	/**
	 * Get coupon description.
	 * @since  2.7.0
	 * @return string
	 */
	public function get_description() {
		return $this->_data['description'];
	}

	/**
	 * Get discount type.
	 * @since  2.7.0
	 * @return string
	 */
	public function get_discount_type() {
		return $this->_data['discount_type'];
	}

	/**
	 * Get coupon code.
	 * @since  2.7.0
	 * @return float
	 */
	public function get_amount() {
		return wc_format_decimal( $this->_data['amount'] );
	}

	/**
	 * Get coupon expiration date.
	 * @since  2.7.0
	 * @return int
	 */
	public function get_date_expires() {
		return $this->_data['date_expires'];
	}

	/**
	 * Get date_created
	 * @since 2.7.0
	 * @return int
	 */
	public function get_date_created() {
		return $this->_data['date_created'];
	}

	/**
	 * Get date_modified
	 * @since 2.7.0
	 * @return int
	 */
	public function get_date_modified() {
		return $this->_data['date_modified'];
	}

	/**
	 * Get coupon usage count.
	 * @since  2.7.0
	 * @return integer
	 */
	public function get_usage_count() {
		return $this->_data['usage_count'];
	}

	/**
	 * Get the "indvidual use" checkbox status.
	 * @since  2.7.0
	 * @return bool
	 */
	public function get_individual_use() {
		return $this->_data['individual_use'];
	}

	/**
	 * Get product IDs this coupon can apply to.
	 * @since  2.7.0
	 * @return array
	 */
	public function get_product_ids() {
		return $this->_data['product_ids'];
	}

	/**
	 * Get product IDs that this coupon should not apply to.
	 * @since  2.7.0
	 * @return array
	 */
	public function get_excluded_product_ids() {
		return $this->_data['excluded_product_ids'];
	}

	/**
	 * Get coupon usage limit.
	 * @since  2.7.0
	 * @return integer
	 */
	public function get_usage_limit() {
		return $this->_data['usage_limit'];
	}

	/**
	 * Get coupon usage limit per customer (for a single customer)
	 * @since  2.7.0
	 * @return integer
	 */
	public function get_usage_limit_per_user() {
		return $this->_data['usage_limit_per_user'];
	}

	/**
	 * Usage limited to certain amount of items
	 * @since  2.7.0
	 * @return integer
	 */
	public function get_limit_usage_to_x_items() {
		return $this->_data['limit_usage_to_x_items'];
	}

	/**
	 * If this coupon grants free shipping or not.
	 * @since  2.7.0
	 * @return bool
	 */
	public function get_free_shipping() {
		return $this->_data['free_shipping'];
	}

	/**
	 * Get product categories this coupon can apply to.
	 * @since  2.7.0
	 * @return array
	 */
	public function get_product_categories() {
		return $this->_data['product_categories'];
	}

	/**
	 * Get product categories this coupon cannot not apply to.
	 * @since  2.7.0
	 * @return array
	 */
	public function get_excluded_product_categories() {
		return $this->_data['excluded_product_categories'];
	}

	/**
	 * If this coupon should exclude items on sale.
	 * @since  2.7.0
	 * @return bool
	 */
	public function get_exclude_sale_items() {
		return $this->_data['exclude_sale_items'];
	}

	/**
	 * Get minium spend amount.
	 * @since  2.7.0
	 * @return float
	 */
	public function get_minimum_amount() {
		return wc_format_decimal( $this->_data['minimum_amount'] );
	}
	/**
	 * Get maximum spend amount.
	 * @since  2.7.0
	 * @return float
	 */
	public function get_maximum_amount() {
		return wc_format_decimal( $this->_data['maximum_amount'] );
	}

	/**
	 * Get emails to check customer usage restrictions.
	 * @since  2.7.0
	 * @return array
	 */
	public function get_email_restrictions() {
		return $this->_data['email_restrictions'];
	}

	/**
	 * Get records of all users who have used the current coupon.
	 *
	 * @return array
	 */
	public function get_used_by() {
		return $this->_data['used_by'];
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
			$discount = $this->get_amount() * ( $discounting_amount / 100 );
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
			$discount = ( $this->get_amount() * $discount_percent ) / $cart_item_qty;

		} elseif ( $this->is_type( 'fixed_product' ) ) {
			$discount = min( $this->get_amount(), $discounting_amount );
			$discount = $single ? $discount : $discount * $cart_item_qty;
		}

		$discount = min( $discount, $discounting_amount );

		// Handle the limit_usage_to_x_items option
		if ( $this->is_type( array( 'percent_product', 'fixed_product' ) ) ) {
			if ( $discounting_amount ) {
				if ( '' === $this->get_limit_usage_to_x_items() ) {
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
	 * Set ID
	 * @param int $value
	 * @throws WC_Data_Exception
	 */
	public function set_id( $value ) {
		$this->_data['id'] = absint( $value );
	}

	/**
	 * Set coupon code.
	 * @since  2.7.0
	 * @param  string $code
	 * @throws WC_Data_Exception
	 */
	public function set_code( $code ) {
		$this->_data['code'] = apply_filters( 'woocommerce_coupon_code', $code );
	}

	/**
	 * Set coupon description.
	 * @since  2.7.0
	 * @param  string $description
	 * @throws WC_Data_Exception
	 */
	public function set_description( $description ) {
		$this->_data['description'] = $description;
	}

	/**
	 * Set discount type.
	 * @since  2.7.0
	 * @param  string $discount_type
	 * @throws WC_Data_Exception
	 */
	public function set_discount_type( $discount_type ) {
		if ( ! in_array( $discount_type, array_keys( wc_get_coupon_types() ) ) ) {
			$this->error( 'coupon_invalid_discount_type', __( 'Invalid discount type', 'woocommerce' ) );
		}
		$this->_data['discount_type'] = $discount_type;
	}

	/**
	 * Set amount.
	 * @since  2.7.0
	 * @param  float $amount
	 * @throws WC_Data_Exception
	 */
	public function set_amount( $amount ) {
		$this->_data['amount'] = wc_format_decimal( $amount );
	}

	/**
	 * Set expiration date.
	 * @since  2.7.0
	 * @param string $timestamp Timestamp
	 * @throws WC_Data_Exception
	 */
	public function set_date_expires( $timestamp ) {
		$this->_data['date_expires'] = is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp );
	}

	/**
	 * Set date_created
	 * @since  2.7.0
	 * @param string $timestamp Timestamp
	 * @throws WC_Data_Exception
	 */
	public function set_date_created( $timestamp ) {
		$this->_data['date_created'] = is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp );
	}

	/**
	 * Set date_modified
	 * @since  2.7.0
	 * @param string $timestamp
	 * @throws WC_Data_Exception
	 */
	public function set_date_modified( $timestamp ) {
		$this->_data['date_modified'] = is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp );
	}

	/**
	 * Set how many times this coupon has been used.
	 * @since  2.7.0
	 * @param  int $usage_count
	 * @throws WC_Data_Exception
	 */
	public function set_usage_count( $usage_count ) {
		$this->_data['usage_count'] = absint( $usage_count );
	}

	/**
	 * Set if this coupon can only be used once.
	 * @since  2.7.0
	 * @param  bool $is_individual_use
	 * @throws WC_Data_Exception
	 */
	public function set_individual_use( $is_individual_use ) {
		$this->_data['individual_use'] = (bool) $is_individual_use;
	}

	/**
	 * Set the product IDs this coupon can be used with.
	 * @since  2.7.0
	 * @param  array $product_ids
	 * @throws WC_Data_Exception
	 */
	public function set_product_ids( $product_ids ) {
		$this->_data['product_ids'] = (array) $product_ids;
	}

	/**
	 * Set the product IDs this coupon cannot be used with.
	 * @since  2.7.0
	 * @param  array $excluded_product_ids
	 * @throws WC_Data_Exception
	 */
	public function set_excluded_product_ids( $excluded_product_ids ) {
		$this->_data['excluded_product_ids'] = (array) $excluded_product_ids;
	}

	/**
	 * Set the amount of times this coupon can be used.
	 * @since  2.7.0
	 * @param  int $usage_limit
	 * @throws WC_Data_Exception
	 */
	public function set_usage_limit( $usage_limit ) {
		$this->_data['usage_limit'] = absint( $usage_limit );
	}

	/**
	 * Set the amount of times this coupon can be used per user.
	 * @since  2.7.0
	 * @param  int $usage_limit
	 * @throws WC_Data_Exception
	 */
	public function set_usage_limit_per_user( $usage_limit ) {
		$this->_data['usage_limit_per_user'] = absint( $usage_limit );
	}

	/**
	 * Set usage limit to x number of items.
	 * @since  2.7.0
	 * @param  int $limit_usage_to_x_items
	 * @throws WC_Data_Exception
	 */
	public function set_limit_usage_to_x_items( $limit_usage_to_x_items ) {
		$this->_data['limit_usage_to_x_items'] = $limit_usage_to_x_items;
	}

	/**
	 * Set if this coupon enables free shipping or not.
	 * @since  2.7.0
	 * @param  bool $free_shipping
	 * @throws WC_Data_Exception
	 */
	public function set_free_shipping( $free_shipping ) {
		$this->_data['free_shipping'] = (bool) $free_shipping;
	}

	/**
	 * Set the product category IDs this coupon can be used with.
	 * @since  2.7.0
	 * @param  array $product_categories
	 * @throws WC_Data_Exception
	 */
	public function set_product_categories( $product_categories ) {
		$this->_data['product_categories'] = (array) $product_categories;
	}

	/**
	 * Set the product category IDs this coupon cannot be used with.
	 * @since  2.7.0
	 * @param  array $excluded_product_categories
	 * @throws WC_Data_Exception
	 */
	public function set_excluded_product_categories( $excluded_product_categories ) {
		$this->_data['excluded_product_categories'] = (array) $excluded_product_categories;
	}

	/**
	 * Set if this coupon should excluded sale items or not.
	 * @since  2.7.0
	 * @param  bool $exclude_sale_items
	 * @throws WC_Data_Exception
	 */
	public function set_exclude_sale_items( $exclude_sale_items ) {
		$this->_data['exclude_sale_items'] = (bool) $exclude_sale_items;
	}

	/**
	 * Set the minimum spend amount.
	 * @since  2.7.0
	 * @param  float $amount
	 * @throws WC_Data_Exception
	 */
	public function set_minimum_amount( $amount ) {
		$this->_data['minimum_amount'] = wc_format_decimal( $amount );
	}

	/**
	 * Set the maximum spend amount.
	 * @since  2.7.0
	 * @param  float $amount
	 * @throws WC_Data_Exception
	 */
	public function set_maximum_amount( $amount ) {
		$this->_data['maximum_amount'] = wc_format_decimal( $amount );
	}

	/**
	 * Set email restrictions.
	 * @since  2.7.0
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
		$this->_data['email_restrictions'] = $emails;
	}

	/**
	 * Set which users have used this coupon.
	 * @since 2.7.0
	 * @param array $used_by
	 * @throws WC_Data_Exception
	 */
	public function set_used_by( $used_by ) {
		$this->_data['used_by'] = array_filter( $used_by );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	|
	| Methods which create, read, update and delete coupons from the database.
	|
	| A save method is included for convenience (chooses update or create based
	| on if the order exists yet).
	|
	*/

	/**
	 * Reads an coupon from the database and sets its data to the class.
	 * @since 2.7.0
	 * @param  int $coupon_id
	 */
	public function read( $coupon_id ) {
		$this->set_defaults();

		if ( ! $coupon_id ) {
			return;
		}

		$post_object = get_post( $coupon_id );

		if ( ! $post_object ) {
			return;
		}

		$this->set_props( array(
			'id'                          => $coupon_id,
			'code'                        => $post_object->post_title,
			'description'                 => $post_object->post_excerpt,
			'date_created'                => $post_object->post_date,
			'date_modified'               => $post_object->post_modified,
			'date_expires'                => get_post_meta( $coupon_id, 'expiry_date', true ),
			'discount_type'               => get_post_meta( $coupon_id, 'discount_type', true ),
			'amount'                      => get_post_meta( $coupon_id, 'coupon_amount', true ),
			'usage_count'                 => get_post_meta( $coupon_id, 'usage_count', true ),
			'individual_use'              => 'yes' === get_post_meta( $coupon_id, 'individual_use', true ),
			'product_ids'                 => array_filter( (array) explode( ',', get_post_meta( $coupon_id, 'product_ids', true ) ) ),
			'excluded_product_ids'        => array_filter( (array) explode( ',', get_post_meta( $coupon_id, 'exclude_product_ids', true ) ) ),
			'usage_limit'                 => get_post_meta( $coupon_id, 'usage_limit', true ),
			'usage_limit_per_user'        => get_post_meta( $coupon_id, 'usage_limit_per_user', true ),
			'limit_usage_to_x_items'      => get_post_meta( $coupon_id, 'limit_usage_to_x_items', true ),
			'free_shipping'               => 'yes' === get_post_meta( $coupon_id, 'free_shipping', true ),
			'product_categories'          => array_filter( (array) get_post_meta( $coupon_id, 'product_categories', true ) ),
			'excluded_product_categories' => array_filter( (array) get_post_meta( $coupon_id, 'exclude_product_categories', true ) ),
			'exclude_sale_items'          => 'yes' === get_post_meta( $coupon_id, 'exclude_sale_items', true ),
			'minimum_amount'              => get_post_meta( $coupon_id, 'minimum_amount', true ),
			'maximum_amount'              => get_post_meta( $coupon_id, 'maximum_amount', true ),
			'email_restrictions'          => array_filter( (array) get_post_meta( $coupon_id, 'customer_email', true ) ),
			'used_by'                     => array_filter( (array) get_post_meta( $coupon_id, '_used_by' ) ),
		) );
		$this->read_meta_data();

		do_action( 'woocommerce_coupon_loaded', $this );
	}

	/**
	 * Create a new coupon.
	 * @since 2.7.0
	 */
	public function create() {
		$this->set_date_created( current_time( 'timestamp' ) );

		$coupon_id = wp_insert_post( apply_filters( 'woocommerce_new_coupon_data', array(
			'post_type'     => 'shop_coupon',
			'post_status'   => 'publish',
			'post_author'   => get_current_user_id(),
			'post_title'    => $this->get_code(),
			'post_content'  => '',
			'post_excerpt'  => $this->get_description(),
			'post_date'     => date( 'Y-m-d H:i:s', $this->get_date_created() ),
			'post_date_gmt' => get_gmt_from_date( date( 'Y-m-d H:i:s', $this->get_date_created() ) ),
		) ), true );

		if ( $coupon_id ) {
			$this->_data['id'] = $coupon_id;
			$this->update_post_meta( $coupon_id );
			$this->save_meta_data();
			do_action( 'woocommerce_new_coupon', $coupon_id );
		}
	}

	/**
	 * Updates an existing coupon.
	 * @since 2.7.0
	 */
	public function update() {
		$coupon_id = $this->get_id();

		$post_data = array(
			'ID'           => $coupon_id,
			'post_title'   => $this->get_code(),
			'post_excerpt' => $this->get_description(),
		);

		wp_update_post( $post_data );
		$this->update_post_meta( $coupon_id );
		$this->save_meta_data();
		do_action( 'woocommerce_update_coupon', $coupon_id );
	}

	/**
	 * Save data (either create or update depending on if we are working on an existing coupon)
	 * @since 2.7.0
	 */
	public function save() {
		if ( $this->get_id() ) {
			$this->update();
		} else {
			$this->create();
		}
	}

	/**
	 * Delete coupon from the database.
	 * @since 2.7.0
	 */
	public function delete() {
		wp_delete_post( $this->get_id() );
		do_action( 'woocommerce_delete_coupon', $this->get_id() );
	}

	/**
	* Helper method that updates all the post meta for a coupon based on it's settings in the WC_Coupon class.
	* @since 2.7.0
	* @param int $coupon_id
	*/
	private function update_post_meta( $coupon_id ) {
		update_post_meta( $coupon_id, 'discount_type', $this->get_discount_type() );
		update_post_meta( $coupon_id, 'coupon_amount', $this->get_amount() );
		update_post_meta( $coupon_id, 'individual_use', ( true === $this->get_individual_use() ) ? 'yes' : 'no' );
		update_post_meta( $coupon_id, 'product_ids', implode( ',', array_filter( array_map( 'intval', $this->get_product_ids() ) ) ) );
		update_post_meta( $coupon_id, 'exclude_product_ids', implode( ',', array_filter( array_map( 'intval', $this->get_excluded_product_ids() ) ) ) );
		update_post_meta( $coupon_id, 'usage_limit', $this->get_usage_limit() );
		update_post_meta( $coupon_id, 'usage_limit_per_user', $this->get_usage_limit_per_user() );
		update_post_meta( $coupon_id, 'limit_usage_to_x_items', $this->get_limit_usage_to_x_items() );
		update_post_meta( $coupon_id, 'usage_count', $this->get_usage_count() );
		update_post_meta( $coupon_id, 'expiry_date', $this->get_date_expires() );
		update_post_meta( $coupon_id, 'free_shipping', ( true === $this->get_free_shipping() ) ? 'yes' : 'no' );
		update_post_meta( $coupon_id, 'product_categories', array_filter( array_map( 'intval', $this->get_product_categories() ) ) );
		update_post_meta( $coupon_id, 'exclude_product_categories', array_filter( array_map( 'intval', $this->get_excluded_product_categories() ) ) );
		update_post_meta( $coupon_id, 'exclude_sale_items', ( true === $this->get_exclude_sale_items() ) ? 'yes' : 'no' );
		update_post_meta( $coupon_id, 'minimum_amount', $this->get_minimum_amount() );
		update_post_meta( $coupon_id, 'maximum_amount', $this->get_maximum_amount() );
		update_post_meta( $coupon_id, 'customer_email', array_filter( array_map( 'sanitize_email', $this->get_email_restrictions() ) ) );
	}

	/**
	 * Developers can programically return coupons. This function will read those values into our WC_Coupon class.
	 * @since  2.7.0
	 * @param  string $code  Coupon code
	 * @param  array $coupon Array of coupon properties
	 */
	public function read_manual_coupon( $code, $coupon ) {
		// product_ids and exclude_product_ids could be passed in as an empty string '', or comma separated values, when it should be an empty array for the new format.
		$convert_fields_to_array = array( 'product_ids', 'exclude_product_ids' );
		foreach ( $convert_fields_to_array as $field ) {
			if ( ! is_array( $coupon[ $field ] ) ) {
				_doing_it_wrong( $field, $field . ' should be an array instead of a string.', '2.7' );
				$coupon[ $field ] = array_filter( explode( ',', $coupon[ $field ] ) );
			}
		}

		// flip yes|no to true|false
		$yes_no_fields = array( 'individual_use', 'free_shipping', 'exclude_sale_items' );
		foreach ( $yes_no_fields as $field ) {
			if ( 'yes' === $coupon[ $field ] || 'no' === $coupon[ $field ] ) {
				_doing_it_wrong( $field, $field . ' should be true or false instead of yes or no.', '2.7' );
				$coupon[ $field ] = 'yes' === $coupon[ $field ];
			}
		}

		// BW compat
		$coupon[ 'date_expires' ]                = isset( $coupon[ 'date_expires' ] ) ? $coupon[ 'date_expires' ]                             : '';
		$coupon[ 'date_expires' ]                = isset( $coupon[ 'expiry_date' ] ) ? $coupon[ 'expiry_date' ]                               : $coupon[ 'date_expires' ];
		$coupon[ 'excluded_product_ids' ]        = isset( $coupon[ 'excluded_product_ids'] ) ? $coupon[ 'excluded_product_ids']               : '';
		$coupon[ 'excluded_product_ids' ]        = isset( $coupon[ 'exclude_product_ids'] ) ? $coupon[ 'exclude_product_ids']                 : $coupon[ 'excluded_product_ids'];
		$coupon[ 'excluded_product_categories' ] = isset( $coupon[ 'excluded_product_categories'] ) ? $coupon[ 'excluded_product_categories'] : '';
		$coupon[ 'excluded_product_categories' ] = isset( $coupon[ 'exclude_product_categories'] ) ? $coupon[ 'exclude_product_categories']   : $coupon[ 'excluded_product_categories'];

		$this->set_code( $code );
		$this->set_props( $coupon );
	}

	/*
    |--------------------------------------------------------------------------
    | Other Actions
    |--------------------------------------------------------------------------
    */

	/**
	 * Increase usage count for current coupon.
	 *
	 * @param string $used_by Either user ID or billing email
	 */
	public function inc_usage_count( $used_by = '' ) {
		if ( $this->get_id() ) {
			$this->_data['usage_count']++;
			update_post_meta( $this->get_id(), 'usage_count', $this->get_usage_count() );
			if ( $used_by ) {
				add_post_meta( $this->get_id(), '_used_by', strtolower( $used_by ) );
				$this->set_used_by( (array) get_post_meta( $this->get_id(), '_used_by' ) );
			}
		}
	}

	/**
	 * Decrease usage count for current coupon.
	 *
	 * @param string $used_by Either user ID or billing email
	 */
	public function dcr_usage_count( $used_by = '' ) {
		if ( $this->get_id() && $this->get_usage_count() > 0 ) {
			global $wpdb;
			$this->_data['usage_count']--;
			update_post_meta( $this->get_id(), 'usage_count', $this->get_usage_count() );
			if ( $used_by ) {
				/**
				 * We're doing this the long way because `delete_post_meta( $id, $key, $value )` deletes.
				 * all instances where the key and value match, and we only want to delete one.
				 */
				$meta_id = $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM $wpdb->postmeta WHERE meta_key = '_used_by' AND meta_value = %s AND post_id = %d LIMIT 1;", $used_by, $this->get_id() ) );
				if ( $meta_id ) {
					delete_metadata_by_mid( 'post', $meta_id );
					$this->set_used_by( (array) get_post_meta( $this->get_id(), '_used_by' ) );
				}
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
		if ( $this->get_usage_limit_per_user() > 0 && is_user_logged_in() && $this->get_id() ) {
			global $wpdb;
			$usage_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( meta_id ) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_used_by' AND meta_value = %d;", $this->get_id(), $user_id ) );

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
		if ( $this->get_date_expires() && current_time( 'timestamp' ) > $this->get_date_expires() ) {
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
				foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					if ( in_array( $cart_item['product_id'], $this->get_product_ids() ) || in_array( $cart_item['variation_id'], $this->get_product_ids() ) || in_array( $cart_item['data']->get_parent(), $this->get_product_ids() ) ) {
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
				foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
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
		if ( $this->get_exclude_sale_items() && $this->is_type( wc_get_product_coupon_types() ) ) {
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
		if ( sizeof( $this->get_excluded_product_ids() ) > 0 ) {
			$valid_for_cart = true;
			if ( ! WC()->cart->is_empty() ) {
				foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					if ( in_array( $cart_item['product_id'], $this->get_excluded_product_ids() ) || in_array( $cart_item['variation_id'], $this->get_excluded_product_ids() ) || in_array( $cart_item['data']->get_parent(), $this->get_excluded_product_ids() ) ) {
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
				foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
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
	 * Exclude sale items from cart.
	 *
	 * @throws Exception
	 */
	private function validate_cart_excluded_sale_items() {
		if ( $this->get_exclude_sale_items() ) {
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

		// Specific product ID's excluded from the discount
		if ( sizeof( $this->get_excluded_product_ids()) && sizeof( array_intersect( $product_ids, $this->get_excluded_product_ids() ) ) ) {
			$valid = false;
		}

		// Specific categories excluded from the discount
		if ( sizeof( $this->get_excluded_product_categories() ) && sizeof( array_intersect( $product_cats, $this->get_excluded_product_categories() ) ) ) {
			$valid = false;
		}

		// Sale Items excluded from discount
		if ( $this->get_exclude_sale_items() ) {
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
				$err = sprintf( __( 'Coupon "%s" does not exist!', 'woocommerce' ), $this->get_code() );
			break;
			case self::E_WC_COUPON_INVALID_REMOVED:
				$err = sprintf( __( 'Sorry, it seems the coupon "%s" is invalid - it has now been removed from your order.', 'woocommerce' ), $this->get_code() );
			break;
			case self::E_WC_COUPON_NOT_YOURS_REMOVED:
				$err = sprintf( __( 'Sorry, it seems the coupon "%s" is not yours - it has now been removed from your order.', 'woocommerce' ), $this->get_code() );
			break;
			case self::E_WC_COUPON_ALREADY_APPLIED:
				$err = __( 'Coupon code already applied!', 'woocommerce' );
			break;
			case self::E_WC_COUPON_ALREADY_APPLIED_INDIV_USE_ONLY:
				$err = sprintf( __( 'Sorry, coupon "%s" has already been applied and cannot be used in conjunction with other coupons.', 'woocommerce' ), $this->get_code() );
			break;
			case self::E_WC_COUPON_USAGE_LIMIT_REACHED:
				$err = __( 'Coupon usage limit has been reached.', 'woocommerce' );
			break;
			case self::E_WC_COUPON_EXPIRED:
				$err = __( 'This coupon has expired.', 'woocommerce' );
			break;
			case self::E_WC_COUPON_MIN_SPEND_LIMIT_NOT_MET:
				$err = sprintf( __( 'The minimum spend for this coupon is %s.', 'woocommerce' ), wc_price( $this->get_minimum_amount() ) );
			break;
			case self::E_WC_COUPON_MAX_SPEND_LIMIT_MET:
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
						if ( in_array( $cart_item['product_id'], $this->get_excluded_product_ids() ) || in_array( $cart_item['variation_id'], $this->get_excluded_product_ids() ) || in_array( $cart_item['data']->get_parent(), $this->get_excluded_product_ids() ) ) {
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

						if ( sizeof( $intersect = array_intersect( $product_cats, $this->get_excluded_product_categories() ) ) > 0 ) {

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
