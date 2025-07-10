<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Enums\ProductType;
use WP_Block;

/**
 * Utility methods used for the Add to Cart + Options block.
 * {@internal This class and its methods are not intended for public use.}
 */
class Utils {
	/**
	 * Add increment and decrement buttons to the quantity input field.
	 *
	 * @param string $quantity_html Quantity input HTML.
	 * @param string $product_name Product name.
	 * @return string Quantity input HTML with increment and decrement buttons.
	 */
	public static function add_quantity_steppers( $quantity_html, $product_name ) {
		// Regex pattern to match the <input> element with id starting with 'quantity_'.
		$pattern = '/(<input[^>]*id="quantity_[^"]*"[^>]*\/>)/';
		// Replacement string to add button AFTER the matched <input> element.
		/* translators: %s refers to the item name in the cart. */
		$minus_button = '$1<button aria-label="' . esc_attr( sprintf( __( 'Reduce quantity of %s', 'woocommerce' ), $product_name ) ) . '" type="button" data-wp-on--click="actions.decreaseQuantity" data-wp-bind--disabled="!state.allowsDecrease" class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus">−</button>';
		// Replacement string to add button AFTER the matched <input> element.
		/* translators: %s refers to the item name in the cart. */
		$plus_button = '$1<button aria-label="' . esc_attr( sprintf( __( 'Increase quantity of %s', 'woocommerce' ), $product_name ) ) . '" type="button" data-wp-on--click="actions.increaseQuantity" data-wp-bind--disabled="!state.allowsIncrease" class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--plus">+</button>';
		$new_html    = preg_replace( $pattern, $plus_button, $quantity_html );
		$new_html    = preg_replace( $pattern, $minus_button, $new_html );
		return $new_html;
	}

	/**
	 * Add classes to the Quantity Selector needed for the stepper style.
	 *
	 * @param string $quantity_html The Quantity Selector HTML.
	 *
	 * @return string The Quantity Selector HTML with classes added.
	 */
	public static function add_quantity_stepper_classes( $quantity_html ) {
		$processor = new \WP_HTML_Tag_Processor( $quantity_html );

		// Add classes to the form.
		while ( $processor->next_tag( array( 'class_name' => 'quantity' ) ) ) {
			$processor->add_class( 'wc-block-components-quantity-selector' );
		}

		while ( $processor->next_tag( array( 'class_name' => 'input-text' ) ) ) {
			$processor->add_class( 'wc-block-components-quantity-selector__input' );
		}

		return $processor->get_updated_html();
	}

	/**
	 * Get standardized quantity input arguments for WooCommerce quantity input.
	 *
	 * @param \WC_Product $product The product object.
	 * @return array Arguments for woocommerce_quantity_input().
	 */
	public static function get_quantity_input_args( $product ) {
		return array(
			/**
			 * Filter the minimum quantity value allowed for the product.
			 *
			 * @since 10.1.0
			 * @param int        $min_value Minimum quantity value.
			 * @param WC_Product $product   Product object.
			 */
			'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
			/**
			 * Filter the maximum quantity value allowed for the product.
			 *
			 * @since 10.1.0
			 * @param int        $max_value Maximum quantity value.
			 * @param WC_Product $product   Product object.
			 */
			'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
			'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // phpcs:ignore WordPress.Security.NonceVerification.Missing
		);
	}

	/**
	 * Make the quantity input interactive by wrapping it with the necessary data attribute and adding an input event listener.
	 *
	 * @param string   $quantity_html The quantity HTML.
	 * @param string   $wrapper_attributes Optional wrapper attributes.
	 * @param int|null $child_product_id Optional child product ID.
	 *
	 * @return string The quantity HTML with interactive wrapper.
	 */
	public static function make_quantity_input_interactive( $quantity_html, $wrapper_attributes = '', $child_product_id = null ) {
		$processor = new \WP_HTML_Tag_Processor( $quantity_html );
		if (
			$processor->next_tag( 'input' ) &&
			$processor->get_attribute( 'type' ) === 'number' &&
			strpos( $processor->get_attribute( 'name' ), 'quantity' ) !== false
		) {
			$processor->set_attribute( 'data-wp-on--input', 'actions.handleQuantityInput' );
			$processor->set_attribute( 'data-wp-on--change', 'actions.handleQuantityChange' );
		}

		$quantity_html = $processor->get_updated_html();

		$context = array();
		if ( $child_product_id ) {
			$context['childProductId'] = $child_product_id;
		}
		$context_attribute = ! empty( $context ) ? " data-wp-context='" . wp_json_encode( $context ) . "'" : '';

		if ( ! empty( $wrapper_attributes ) ) {
			return sprintf(
				'<div %1$s data-wp-interactive="woocommerce/add-to-cart-with-options"%2$s>%3$s</div>',
				$wrapper_attributes,
				$context_attribute,
				$quantity_html
			);
		}

		return '<div data-wp-interactive="woocommerce/add-to-cart-with-options"' . $context_attribute . '>' . $quantity_html . '</div>';
	}

	/**
	 * Get product from block context.
	 *
	 * @param \WP_Block        $block The block instance.
	 * @param \WC_Product|null $previous_product The previous product (usually from global scope).
	 * @return \WC_Product|null The product instance or null if not found.
	 */
	public static function get_product_from_context( $block, $previous_product ) {
		$post_id = isset( $block->context['postId'] ) ? $block->context['postId'] : '';
		$product = null;

		if ( ! empty( $post_id ) ) {
			$product = wc_get_product( $post_id );
		}

		if ( ! $product instanceof \WC_Product && $previous_product instanceof \WC_Product ) {
			$product = $previous_product;
		}

		return $product instanceof \WC_Product ? $product : null;
	}

	/**
	 * Check if a product is not purchasable or not in stock.
	 *
	 * @param \WC_Product $product The product to check.
	 * @return bool True if the product is not purchasable or not in stock.
	 */
	public static function is_not_purchasable_product( $product ) {
		if ( $product->is_type( 'simple' ) ) {
			return ! $product->is_in_stock() || ! $product->is_purchasable();
		} elseif ( $product->is_type( 'variable' ) ) {
			return ! $product->is_in_stock() || ! $product->has_purchasable_variations();
		}

		return false;
	}

	/**
	 * Renders a new block with custom context
	 *
	 * @param WP_Block $block The block instance.
	 * @param array    $context The context for the new block.
	 * @return string Rendered block content
	 */
	public static function render_block_with_context( $block, $context ) {
		// Get an instance of the current block.
		$block_instance = $block->parsed_block;

		// Create new block with custom context.
		$new_block = new WP_Block(
			$block_instance,
			$context
		);

		// Render with dynamic set to false to prevent calling render_callback.
		return $new_block->render( array( 'dynamic' => false ) );
	}

	/**
	 * Check if min and max purchase quantity are the same for a product.
	 *
	 * @param \WC_Product $product The product to check.
	 * @return bool True if min and max purchase quantity are the same, false otherwise.
	 */
	public static function is_min_max_quantity_same( $product ) {
		/**
		 * Filter the minimum quantity value allowed for the product.
		 *
		 * @since 2.0.0
		 *
		 * @param int        $min_purchase_quantity The minimum purchase quantity.
		 * @param WC_Product $product               The product object.
		 */
		$min_purchase_quantity = apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product );
		/**
		 * Filter the maximum quantity value allowed for the product.
		 *
		 * @since 2.0.0
		 *
		 * @param int        $max_purchase_quantity The maximum purchase quantity.
		 * @param WC_Product $product               The product object.
		 */
		$max_purchase_quantity = apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product );
		return $min_purchase_quantity === $max_purchase_quantity;
	}
}
