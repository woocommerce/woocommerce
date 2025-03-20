<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\WooCommerce\Checkout\Helpers\ReserveStock;
use Automattic\WooCommerce\StoreApi\Utilities\DraftOrderTrait;

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

		$multiple_of = $this->filter_numeric_value( 1, 'multiple_of', $cart_item );
		$minimum     = $this->filter_numeric_value( 1, 'minimum', $cart_item );
		$maximum     = $this->filter_numeric_value( $this->get_product_quantity_limit( $product ), 'maximum', $cart_item );
		$editable    = $this->filter_boolean_value( ! $product->is_sold_individually(), 'editable', $cart_item );

		// Minimum must be at least 1.
		$minimum = max( $minimum, 1 );

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
		$maximum     = $this->filter_numeric_value( $this->get_product_quantity_limit( $product ), 'maximum', $product );

		// Minimum must be at least 1.
		$minimum = max( $minimum, 1 );

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
	 * @param int   $quantity The quantity to fix.
	 * @param array $cart_item The cart item.
	 * @return int
	 */
	public function normalize_cart_item_quantity( int $quantity, array $cart_item ) {
		$product = $cart_item['data'] ?? false;

		if ( ! $product instanceof \WC_Product ) {
			return $quantity;
		}

		$limits       = $this->get_cart_item_quantity_limits( $cart_item );
		$new_quantity = $quantity;

		if ( $new_quantity % $limits['multiple_of'] ) {
			$new_quantity = $this->limit_to_multiple( $new_quantity, $limits['multiple_of'], 'round' );
		}

		if ( $new_quantity < $limits['minimum'] ) {
			$new_quantity = $limits['minimum'];
		}

		if ( $new_quantity > $limits['maximum'] ) {
			$new_quantity = $limits['maximum'];
		}

		return $new_quantity;
	}

	/**
	 * Return a number using the closest multiple of another number. Used to enforce step/multiple values.
	 *
	 * @param int    $number Number to round.
	 * @param int    $multiple_of The multiple.
	 * @param string $rounding_function ceil, floor, or round.
	 * @return int
	 */
	public function limit_to_multiple( int $number, int $multiple_of, string $rounding_function = 'round' ) {
		if ( $multiple_of <= 1 ) {
			return $number;
		}
		$rounding_function = in_array( $rounding_function, [ 'ceil', 'floor', 'round' ], true ) ? $rounding_function : 'round';
		return $rounding_function( $number / $multiple_of ) * $multiple_of;
	}

	/**
	 * Check that a given quantity is valid according to any limits in place.
	 *
	 * @param integer $quantity Quantity to validate.
	 * @param array   $cart_item Cart item.
	 * @return \WP_Error|true
	 */
	public function validate_cart_item_quantity( $quantity, $cart_item ) {
		$limits  = $this->get_cart_item_quantity_limits( $cart_item );
		$product = $cart_item['data'] ?? false;

		if ( ! $product instanceof \WC_Product ) {
			return true;
		}

		if ( ! $limits['editable'] && $quantity > 1 ) {
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

		if ( $quantity % $limits['multiple_of'] ) {
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
	 * @return int
	 */
	protected function get_product_quantity_limit( \WC_Product $product ) {
		$limits = [ 9999 ];

		if ( $product->is_sold_individually() ) {
			$limits[] = 1;
		} elseif ( ! $product->backorders_allowed() ) {
			$limits[] = $this->get_remaining_stock( $product );
		}

		$limit = max( min( array_filter( $limits ) ), 1 );

		/**
		 * Filters the quantity limit for a product being added to the cart via the Store API.
		 *
		 * Filters the variation option name for custom option slugs.
		 *
		 * @since 6.8.0
		 *
		 * @param integer $quantity_limit Quantity limit which defaults to 9999 unless sold individually.
		 * @param \WC_Product $product Product instance.
		 * @return integer
		 */
		$filtered_limit = apply_filters( 'woocommerce_store_api_product_quantity_limit', $limit, $product );

		// Only return the filtered limit if it's numeric, otherwise return the original limit.
		return is_numeric( $filtered_limit ) ? (int) $filtered_limit : $limit;
	}

	/**
	 * Returns the remaining stock for a product if it has stock.
	 *
	 * This also factors in draft orders.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return integer|null
	 */
	protected function get_remaining_stock( \WC_Product $product ) {
		if ( is_null( $product->get_stock_quantity() ) ) {
			return null;
		}

		$reserve_stock  = new ReserveStock();
		$reserved_stock = $reserve_stock->get_reserved_stock( $product, $this->get_draft_order_id() );

		return $product->get_stock_quantity() - $reserved_stock;
	}

	/**
	 * Get a quantity for a product or cart item by running it through a filter hook.
	 *
	 * @param int               $value Value to filter.
	 * @param string            $value_type Type of value. Used for filter suffix.
	 * @param \WC_Product|array $cart_item_or_product Either a cart item or a product instance.
	 * @return int
	 */
	protected function filter_numeric_value( int $value, string $value_type, $cart_item_or_product ) {
		$is_product = $cart_item_or_product instanceof \WC_Product;
		$product    = $is_product ? $cart_item_or_product : $cart_item_or_product['data'];
		$cart_item  = $is_product ? null : $cart_item_or_product;

		/**
		 * Filters the quantity minimum for a cart item in Store API. This allows extensions to control the minimum qty
		 * of items already within the cart.
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
		$filtered_value = apply_filters( "woocommerce_store_api_product_quantity_{$value_type}", $value, $product, $cart_item );

		return is_numeric( $filtered_value ) ? (int) $filtered_value : $value;
	}

	/**
	 * Get a quantity for a product or cart item by running it through a filter hook.
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
		 * Filters the quantity minimum for a cart item in Store API. This allows extensions to control the minimum qty
		 * of items already within the cart.
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
		$filtered_value = apply_filters( "woocommerce_store_api_product_quantity_{$value_type}", $value, $product, $cart_item );

		return is_bool( $filtered_value ) ? (bool) $filtered_value : $value;
	}
}
