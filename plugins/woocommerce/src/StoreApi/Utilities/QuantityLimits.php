<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\WooCommerce\Checkout\Helpers\ReserveStock;
use Automattic\WooCommerce\StoreApi\Utilities\DraftOrderTrait;

/**
 * QuantityLimits class.
 *
 * Returns limits for products and cart items when using the StoreAPI and supporting classes.
 * Supports both integer and decimal quantities, with integers as the default unless
 * wc_stock_amount() is customized to support decimals.
 */
final class QuantityLimits {
	use DraftOrderTrait;

	/**
	 * Tolerance for floating point comparisons.
	 */
	const FLOAT_TOLERANCE = 0.00001;

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

		$minimum     = $this->filter_numeric_value( 1, 'minimum', $cart_item );
		$maximum     = $this->filter_numeric_value( $this->get_product_quantity_limit( $product, $minimum ), 'maximum', $cart_item );
		$multiple_of = $this->filter_numeric_value( $minimum, 'multiple_of', $cart_item );
		$editable    = $this->filter_boolean_value( ! $product->is_sold_individually(), 'editable', $cart_item );

		// Maximum must be at least minimum.
		$maximum = max( $maximum, $minimum );

		return [
			'minimum'     => $this->limit_to_multiple( $minimum, $multiple_of, 'ceil' ),
			'maximum'     => $this->limit_to_multiple( $maximum, $multiple_of, 'floor' ),
			'multiple_of' => $multiple_of,
			'editable'    => $editable,
		];
	}

	/**
	 * Get limits for product add to cart forms.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return array
	 */
	public function get_add_to_cart_limits( \WC_Product $product ) {
		$multiple_of = $this->filter_numeric_value( 1, 'multiple_of', $product );
		$minimum     = $this->filter_numeric_value( 1, 'minimum', $product );
		$maximum     = $this->filter_numeric_value( $this->get_product_quantity_limit( $product, $minimum ), 'maximum', $product );

		// Maximum must be at least minimum.
		$maximum = max( $maximum, $minimum );

		return [
			'minimum'     => $this->limit_to_multiple( $minimum, $multiple_of, 'ceil' ),
			'maximum'     => $this->limit_to_multiple( $maximum, $multiple_of, 'floor' ),
			'multiple_of' => $multiple_of,
		];
	}

	/**
	 * Fix a quantity violation by adjusting it to the nearest valid quantity.
	 *
	 * @param int|float $quantity The quantity to fix.
	 * @param array     $cart_item The cart item.
	 * @return int|float
	 */
	public function normalize_cart_item_quantity( $quantity, array $cart_item ) {
		if ( ! is_numeric( $quantity ) || $quantity < 0 ) {
			return 0;
		}

		$product = $cart_item['data'] ?? false;

		if ( ! $product instanceof \WC_Product ) {
			return wc_stock_amount( $quantity );
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
		if ( 0 === $multiple_of ) {
			return $number; // Avoid division by zero.
		}

		if ( $this->is_multiple_of( $number, $multiple_of ) ) {
			return wc_stock_amount( $number );
		}

		// Ensure valid rounding function.
		$rounding_function = in_array( $rounding_function, [ 'ceil', 'floor', 'round' ], true ) ? $rounding_function : 'round';

		// Calculate the division result and apply rounding.
		$division_result  = $number / $multiple_of;
		$rounded_division = $rounding_function( $division_result );
		$result           = $rounded_division * $multiple_of;

		return wc_stock_amount( $result );
	}

	/**
	 * Check that a given quantity is valid according to any limits in place.
	 *
	 * @param int|float $quantity Quantity to validate.
	 * @param array     $cart_item Cart item.
	 * @return \WP_Error|true
	 */
	public function validate_cart_item_quantity( $quantity, $cart_item ) {
		$limits  = $this->get_cart_item_quantity_limits( $cart_item );
		$product = $cart_item['data'] ?? false;

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

		if ( ! $this->is_multiple_of( $quantity, $limits['multiple_of'] ) ) {
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
	 * @param \WC_Product $product Product instance.
	 * @param int|float   $minimum Minimum quantity.
	 * @return int|float
	 */
	protected function get_product_quantity_limit( \WC_Product $product, $minimum = 1 ) {
		$limits = [ 9999 ];

		if ( $product->is_sold_individually() ) {
			$limits[] = $minimum;
		} elseif ( $product->managing_stock() || ! $product->backorders_allowed() ) {
			$limits[] = $this->get_remaining_stock( $product );
		}

		$limit = max( min( array_filter( $limits ) ), $minimum );

		return $this->filter_numeric_value( $limit, 'limit', $product );
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
	 * @param int|float         $value Value to filter.
	 * @param string            $value_type Type of value. Used for filter suffix.
	 * @param \WC_Product|array $cart_item_or_product Either a cart item or a product instance.
	 * @return int|float
	 */
	protected function filter_numeric_value( $value, string $value_type, $cart_item_or_product ) {
		$is_product = $cart_item_or_product instanceof \WC_Product;
		$product    = $is_product ? $cart_item_or_product : $cart_item_or_product['data'];
		$cart_item  = $is_product ? null : $cart_item_or_product;

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

		return wc_stock_amount( is_numeric( $filtered_value ) ? $filtered_value : $value );
	}

	/**
	 * Get a boolean value while running it through a filter hook.
	 *
	 * @param bool              $value Value to filter.
	 * @param string            $value_type Type of value. Used for filter suffix.
	 * @param \WC_Product|array $cart_item_or_product Either a cart item or a product instance.
	 * @return bool
	 */
	protected function filter_boolean_value( $value, string $value_type, $cart_item_or_product ) {
		$is_product = $cart_item_or_product instanceof \WC_Product;
		$product    = $is_product ? $cart_item_or_product : $cart_item_or_product['data'];
		$cart_item  = $is_product ? null : $cart_item_or_product;

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

		return boolval( is_bool( $filtered_value ) ? $filtered_value : $value );
	}

	/**
	 * Checks if number is a multiple of another number.
	 *
	 * @param int|float $number The number to check.
	 * @param int|float $multiple_of The multiple.
	 * @return bool
	 */
	protected function is_multiple_of( $number, $multiple_of ) {
		// Handle negative numbers by working with absolute values.
		$number      = abs( $number );
		$multiple_of = abs( $multiple_of );

		if ( 0 === $multiple_of ) {
			return true; // Avoid division by zero.
		}

		// Handle very small multiples that could cause precision issues. Treat as effectively zero.
		if ( $multiple_of < self::FLOAT_TOLERANCE ) {
			return true;
		}

		// For integers, use exact modulo comparison.
		if ( is_int( $number ) && is_int( $multiple_of ) ) {
			return 0 === $number % $multiple_of;
		}

		// For floats, use division and check if result is close to an integer.
		$division_result = $number / $multiple_of;
		$rounded_result  = round( $division_result );
		return abs( $division_result - $rounded_result ) < self::FLOAT_TOLERANCE;
	}
}
