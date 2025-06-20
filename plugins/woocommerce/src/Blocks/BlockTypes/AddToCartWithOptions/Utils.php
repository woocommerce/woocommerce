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
		// Replacement string to add button BEFORE the matched <input> element.
		/* translators: %s refers to the item name in the cart. */
		$minus_button = '<button aria-label="' . esc_attr( sprintf( __( 'Reduce quantity of %s', 'woocommerce' ), $product_name ) ) . '"type="button" data-wp-on--click="actions.decreaseQuantity" class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus">-</button>$1';
		// Replacement string to add button AFTER the matched <input> element.
		/* translators: %s refers to the item name in the cart. */
		$plus_button = '$1<button aria-label="' . esc_attr( sprintf( __( 'Increase quantity of %s', 'woocommerce' ), $product_name ) ) . '" type="button" data-wp-on--click="actions.increaseQuantity" class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--plus">+</button>';
		$new_html    = preg_replace( $pattern, $minus_button, $quantity_html );
		$new_html    = preg_replace( $pattern, $plus_button, $new_html );
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
	 * Make the quantity input interactive by wrapping it with the necessary data attribute and adding an input event listener.
	 *
	 * @param string $quantity_html The quantity HTML.
	 * @param string $wrapper_attributes Optional wrapper attributes.
	 * @return string The quantity HTML with interactive wrapper.
	 */
	public static function make_quantity_input_interactive( $quantity_html, $wrapper_attributes = '' ) {
		$processor = new \WP_HTML_Tag_Processor( $quantity_html );
		if (
			$processor->next_tag( 'input' ) &&
			$processor->get_attribute( 'type' ) === 'number' &&
			strpos( $processor->get_attribute( 'name' ), 'quantity' ) !== false
		) {
			$processor->set_attribute( 'data-wp-on--input', 'actions.handleQuantityInputChange' );
		}

		$quantity_html = $processor->get_updated_html();

		if ( ! empty( $wrapper_attributes ) ) {
			return sprintf(
				'<div %1$s data-wp-interactive="woocommerce/add-to-cart-with-options">%2$s</div>',
				$wrapper_attributes,
				$quantity_html
			);
		}

		return '<div data-wp-interactive="woocommerce/add-to-cart-with-options">' . $quantity_html . '</div>';
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
	 * Check if a product is a simple product that is not purchasable or not in stock.
	 *
	 * @param \WC_Product $product The product to check.
	 * @return bool True if the product is a simple product that is not purchasable or not in stock.
	 */
	public static function is_not_purchasable_simple_product( $product ) {
		return ProductType::SIMPLE === $product->get_type() && ( ! $product->is_in_stock() || ! $product->is_purchasable() );
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
}
