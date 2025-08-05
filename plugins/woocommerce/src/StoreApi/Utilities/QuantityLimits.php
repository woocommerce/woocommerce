<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\WooCommerce\Checkout\Helpers\ReserveStock;
use Automattic\WooCommerce\StoreApi\Utilities\DraftOrderTrait;
use Automattic\WooCommerce\Utilities\NumberUtil;

/**
 * QuantityLimits class.
 *
 * Returns limits for products and cart items when using the StoreAPI and supporting classes.
 */
final class QuantityLimits {
	use DraftOrderTrait;

	/**
	 * Get quantity limits (min, max, step/multiple) for a product or cart item.
	 *
	 * @param array $cart_item A cart item array.
	 * @return array
	 */
	public function get_cart_item_quantity_limits( $cart_item ) {
		$product = $cart_item['data'] ?? false;

		if ( ! $product instanceof \WC_Product ) {
			return [
				'minimum'     => 1,
				'maximum'     => 9999,
				'multiple_of' => 1,
				'editable'    => true,
			];
		}

		return array_merge(
			$this->get_add_to_cart_limits( $product, $cart_item ),
			[
				'editable' => $this->filter_boolean_value( ! $product->is_sold_individually(), 'editable', $product, $cart_item ),
			]
		);
	}

	/**
	 * Get limits for product add to cart forms.
	 *
	 * @param \WC_Product $product Product instance.
	 * @param array|null  $cart_item Optional cart item associated with the product.
	 * @return array
	 */
	public function get_add_to_cart_limits( \WC_Product $product, $cart_item = null ) {
		// Compatibility with the woocommerce_quantity_input_args filter. Gets initial values to match classic quantity input.
		$args        = wc_get_quantity_input_args( [], $product );
		$minimum     = $this->filter_numeric_value( $args['min_value'], 'minimum', $product, $cart_item );
		$maximum     = $this->filter_numeric_value(
			$this->adjust_product_quantity_limit( $args['max_value'], $product, $cart_item ),
			'maximum',
			$product,
			$cart_item
		);
		$multiple_of = $this->filter_numeric_value( $args['step'], 'multiple_of', $product, $cart_item );

		// Ensure values are compatible with each other.
		$minimum = max( $multiple_of, $this->limit_to_multiple( $minimum, $multiple_of, 'ceil' ) );
		$maximum = max( $minimum, $this->limit_to_multiple( $maximum, $multiple_of, 'floor' ) );

		return [
			'minimum'     => $minimum,
			'maximum'     => $maximum,
			'multiple_of' => $multiple_of,
		];
	}

	/**
	 * Fix a quantity violation by adjusting it to the nearest valid quantity.
	 *
	 * @param int|float $quantity Quantity.
	 * @param array     $cart_item Cart item.
	 * @return int|float
	 */
	public function normalize_cart_item_quantity( $quantity, array $cart_item ) {
		$product = $cart_item['data'] ?? false;

		if ( ! $product instanceof \WC_Product ) {
			return wc_stock_amount( $quantity );
		}

		$quantity = NumberUtil::normalize( $quantity );

		if ( 0 >= $quantity ) {
			return wc_stock_amount( 0 );
		}

		$limits       = $this->get_cart_item_quantity_limits( $cart_item );
		$new_quantity = $this->limit_to_multiple( $quantity, $limits['multiple_of'], 'round' );

		if ( $new_quantity < $limits['minimum'] ) {
			$new_quantity = $limits['minimum'];
		}

		if ( $new_quantity > $limits['maximum'] ) {
			$new_quantity = $limits['maximum'];
		}

		return wc_stock_amount( $new_quantity );
	}

	/**
	 * Return a number using the closest multiple of another number. Used to enforce step/multiple values.
	 *
	 * @param int|float $number Number to round.
	 * @param int|float $multiple_of The multiple.
	 * @param string    $rounding_function ceil, floor, or round.
	 * @return int|float
	 */
	public function limit_to_multiple( $number, $multiple_of, string $rounding_function = 'round' ) {
		// Handle edge cases.
		$number      = NumberUtil::normalize( $number, null );
		$multiple_of = NumberUtil::normalize( $multiple_of, null );

		if ( is_null( $multiple_of ) || is_null( $number ) ) {
			return 0;
		}

		if ( 0 >= $multiple_of || $this->is_multiple_of( $number, $multiple_of ) ) {
			return $number;
		}

		// Ensure valid rounding function.
		$rounding_function = in_array( $rounding_function, [ 'ceil', 'floor', 'round' ], true ) ? $rounding_function : 'round';

		return NumberUtil::normalize( $rounding_function( $number / $multiple_of ) * $multiple_of );
	}

	/**
	 * Checks if a number is a multiple of another number.
	 *
	 * @param int|float $number The number to check.
	 * @param int|float $multiple_of The multiple.
	 * @return bool
	 */
	protected function is_multiple_of( $number, $multiple_of ) {
		if ( 0 >= $multiple_of ) {
			return false;
		}

		$division_result = $number / $multiple_of;
		// Use tolerance for floating-point comparison to handle precision errors.
		// Example: 0.3 / 0.1 = 2.9999999999999996 instead of exactly 3.0 due to floating-point precision.
		return abs( $division_result - round( $division_result ) ) < 0.0001;
	}

	/**
	 * Check that a given quantity is valid according to any limits in place.
	 *
	 * @param int|float $quantity Quantity to validate.
	 * @param array     $cart_item Cart item.
	 * @return \WP_Error|true
	 */
	public function validate_cart_item_quantity( $quantity, $cart_item ) {
		$limits   = $this->get_cart_item_quantity_limits( $cart_item );
		$product  = $cart_item['data'] ?? false;
		$quantity = wc_stock_amount( $quantity );

		if ( ! $product instanceof \WC_Product ) {
			return true;
		}

		if ( ! $limits['editable'] && $quantity > $limits['maximum'] ) {
			/* translators: 1: product name */
			return new \WP_Error( 'readonly_quantity', sprintf( __( 'The quantity of &quot;%1$s&quot; cannot be changed', 'woocommerce' ), $product->get_name() ) );
		}

		if ( $quantity < $limits['minimum'] ) {
			/* translators: 1: product name 2: minimum quantity */
			return new \WP_Error( 'invalid_quantity', sprintf( __( 'The minimum quantity of &quot;%1$s&quot; allowed in the cart is %2$s', 'woocommerce' ), $product->get_name(), $limits['minimum'] ) );
		}

		if ( $quantity > $limits['maximum'] ) {
			/* translators: 1: product name 2: maximum quantity */
			return new \WP_Error( 'invalid_quantity', sprintf( __( 'The maximum quantity of &quot;%1$s&quot; allowed in the cart is %2$s', 'woocommerce' ), $product->get_name(), $limits['maximum'] ) );
		}

		if ( ! $this->is_multiple_of( $quantity, NumberUtil::normalize( $limits['multiple_of'] ) ) ) {
			/* translators: 1: product name 2: multiple of */
			return new \WP_Error( 'invalid_quantity', sprintf( __( 'The quantity of &quot;%1$s&quot; must be a multiple of %2$s', 'woocommerce' ), $product->get_name(), $limits['multiple_of'] ) );
		}

		return true;
	}

	/**
	 * Get the limit for the total number of a product allowed in the cart.
	 *
	 * This is based on product properties, including remaining stock, and defaults to a maximum of 9999 of any product
	 * in the cart at once.
	 *
	 * @param int|float   $purchase_limit The purchase limit from the product. Usually maps to `get_max_purchase_quantity`.
	 * @param \WC_Product $product Product instance.
	 * @param array|null  $cart_item Optional cart item associated with the product.
	 * @return int|float
	 */
	protected function adjust_product_quantity_limit( $purchase_limit, \WC_Product $product, $cart_item = null ) {
		$limits = [ $purchase_limit > 0 ? $purchase_limit : 9999 ];

		// If managing stock and backorders are not allowed, get the remaining stock considering active carts.
		if ( $product->managing_stock() && ! $product->backorders_allowed() ) {
			$limits[] = $this->get_remaining_stock( $product );
		}

		return $this->filter_numeric_value( min( array_filter( $limits ) ), 'limit', $product, $cart_item );
	}

	/**
	 * Returns the remaining stock for a product if it has stock.
	 *
	 * This also factors in draft orders.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return int|float|null
	 */
	protected function get_remaining_stock( \WC_Product $product ) {
		if ( is_null( $product->get_stock_quantity() ) ) {
			return null;
		}

		$reserve_stock  = new ReserveStock();
		$reserved_stock = $reserve_stock->get_reserved_stock( $product, $this->get_draft_order_id() );

		return wc_stock_amount( $product->get_stock_quantity() - $reserved_stock );
	}

	/**
	 * Get a numeric value while running it through a filter hook.
	 *
	 * @param int|float   $value Value to filter.
	 * @param string      $value_type Type of value. Used for filter suffix.
	 * @param \WC_Product $product Product instance.
	 * @param array|null  $cart_item Optional cart item associated with the product.
	 * @return int|float
	 */
	protected function filter_numeric_value( $value, string $value_type, \WC_Product $product, $cart_item = null ) {
		/**
		 * Filters a quantity for a cart item in Store API. This allows extensions to control the qty of items.
		 *
		 * The suffix of the hook will vary depending on the value being filtered.
		 * For example, minimum, maximum, multiple_of, editable.
		 *
		 * @since 6.8.0
		 *
		 * @param mixed $value The value being filtered.
		 * @param \WC_Product $product The product object.
		 * @param array|null $cart_item The cart item if the product exists in the cart, or null.
		 * @return mixed
		 */
		$filtered_value = apply_filters( 'woocommerce_store_api_product_quantity_' . $value_type, $value, $product, $cart_item );

		return wc_stock_amount( NumberUtil::normalize( $filtered_value, $value ) );
	}

	/**
	 * Get a boolean value while running it through a filter hook.
	 *
	 * @param bool        $value Value to filter.
	 * @param string      $value_type Type of value. Used for filter suffix.
	 * @param \WC_Product $product Product instance.
	 * @param array|null  $cart_item Optional cart item associated with the product.
	 * @return bool
	 */
	protected function filter_boolean_value( $value, string $value_type, \WC_Product $product, $cart_item = null ) {

		/**
		 * Filters boolean data for a cart item in Store API.
		 *
		 * The suffix of the hook will vary depending on the value being filtered. For example, editable.
		 *
		 * @since 6.8.0
		 *
		 * @param mixed $value The value being filtered.
		 * @param \WC_Product $product The product object.
		 * @param array|null $cart_item The cart item if the product exists in the cart, or null.
		 * @return mixed
		 */
		$filtered_value = apply_filters( 'woocommerce_store_api_product_quantity_' . $value_type, $value, $product, $cart_item );

		return is_bool( $filtered_value ) ? $filtered_value : (bool) $value;
	}
}
