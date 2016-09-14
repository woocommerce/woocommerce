<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy Coupon.
 *
 * Legacy and deprecated functions are here to keep the WC_Legacy_Coupon class clean.
 * This class will be removed in future versions.
 *
 * @class       WC_Legacy_Coupon
 * @version     2.7.0
 * @package     WooCommerce/Classes
 * @category    Class
 * @author      WooThemes
 */
abstract class WC_Legacy_Coupon extends WC_Data {

	/**
	 * Magic __isset method for backwards compatibility. Legacy properties which could be accessed directly in the past.
	 * @param  string $key
	 * @return bool
	 */
	public function __isset( $key ) {
		$legacy_keys = array(
			'id',
			'exists',
			'coupon_custom_fields',
			'type',
			'discount_type',
			'amount',
			'code',
			'individual_use',
			'product_ids',
			'exclude_product_ids',
			'usage_limit',
			'usage_limit_per_user',
			'limit_usage_to_x_items',
			'usage_count',
			'expiry_date',
			'product_categories',
			'exclude_product_categories',
			'minimum_amount',
			'maximum_amount',
			'customer_email',
		);
		if ( in_array( $key, $legacy_keys ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Magic __get method for backwards compatibility. Maps legacy vars to new getters.
	 * @param  string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		_doing_it_wrong( $key, 'Coupon properties should not be accessed directly.', '2.7' );

		switch ( $key ) {
			case 'id' :
				$value = $this->get_id();
			break;
			case 'exists' :
				$value = ( $this->get_id() > 0 ) ? true : false;
			break;
			case 'coupon_custom_fields' :
				$legacy_custom_fields = array();
				$custom_fields = $this->get_id() ? $this->get_meta_data() : array();
				if ( ! empty( $custom_fields ) ) {
					foreach ( $custom_fields as  $cf_value ) {
						// legacy only supports 1 key
						$legacy_custom_fields[ $cf_value->key ][0] = $cf_value->value;
					}
				}
				$value = $legacy_custom_fields;
			break;
			case 'type' :
			case 'discount_type' :
				$value = $this->get_discount_type();
			break;
			case 'amount' :
				$value = $this->get_amount();
			break;
			case 'code' :
				$value = $this->get_code();
			break;
			case 'individual_use' :
				$value = ( true === $this->get_individual_use() ) ? 'yes' : 'no';
			break;
			case 'product_ids' :
				$value = $this->get_product_ids();
			break;
			case 'exclude_product_ids' :
				$value = $this->get_excluded_product_ids();
			break;
			case 'usage_limit' :
				$value = $this->get_usage_limit();
			break;
			case 'usage_limit_per_user' :
				$value = $this->get_usage_limit_per_user();
			break;
			case 'limit_usage_to_x_items' :
				$value = $this->get_limit_usage_to_x_items();
			break;
			case 'usage_count' :
				$value = $this->get_usage_count();
			break;
			case 'expiry_date' :
				$value = $this->get_date_expires();
			break;
			case 'product_categories' :
				$value = $this->get_product_categories();
			break;
			case 'exclude_product_categories' :
				$value = $this->get_excluded_product_categories();
			break;
			case 'minimum_amount' :
				$value = $this->get_minimum_amount();
			break;
			case 'maximum_amount' :
				$value = $this->get_maximum_amount();
			break;
			case 'customer_email' :
				$value = $this->get_email_restrictions();
			break;
			default :
				$value = '';
			break;
		}

		return $value;
	}

	/**
	 * Format loaded data as array.
	 * @param  string|array $array
	 * @return array
	 */
	public function format_array( $array ) {
		_deprecated_function( 'format_array', '2.7', '' );
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
		_deprecated_function( 'apply_before_tax', '2.7', '' );
		return true;
	}

	/**
	 * Check if a coupon enables free shipping.
	 *
	 * @return bool
	 */
	public function enable_free_shipping() {
		_deprecated_function( 'enable_free_shipping', '2.7', 'get_free_shipping' );
		return $this->get_free_shipping();
	}

	/**
	 * Check if a coupon excludes sale items.
	 *
	 * @return bool
	 */
	public function exclude_sale_items() {
		_deprecated_function( 'exclude_sale_items', '2.7', 'get_exclude_sale_items' );
		return $this->get_exclude_sale_items();
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
		_deprecated_function( 'get_discount_amount', '2.7', 'WC_Cart_Totals::get_discounted_price' );
		$discount      = 0;
		$cart_item_qty = is_null( $cart_item ) ? 1 : $cart_item['quantity'];

		if ( $this->is_type( array( 'percent_product', 'percent' ) ) ) {
			$discount = $this->get_amount() * ( $discounting_amount / 100 );
		} elseif ( $this->is_type( 'fixed_cart' ) && ! is_null( $cart_item ) && WC()->cart->get_subtotal() ) {
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
				$discount_percent = ( $cart_item['data']->get_price_including_tax() * $cart_item_qty ) / WC()->cart->get_subtotal();
			} else {
				$discount_percent = ( $cart_item['data']->get_price_excluding_tax() * $cart_item_qty ) / WC()->cart->get_subtotal( false );
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
}
