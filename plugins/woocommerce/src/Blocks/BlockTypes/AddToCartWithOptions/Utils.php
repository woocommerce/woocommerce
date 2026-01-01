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
	 * Check if the HTML content has a visible quantity input.
	 *
	 * @param string $html_content The HTML content.
	 * @return bool True if the HTML content has a visible input, false otherwise.
	 */
	public static function has_visible_quantity_input( $html_content ) {
		$processor = new \WP_HTML_Tag_Processor( $html_content );

		while ( $processor->next_tag() ) {
			if (
				$processor->get_tag() === 'INPUT' &&
				$processor->get_attribute( 'name' ) === 'quantity' &&
				$processor->get_attribute( 'type' ) !== 'hidden'
			) {
				return true;
			}
		}
		return false;
	}
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
	 * Make the quantity input interactive by wrapping it with the necessary data attribute and adding a blur event listener.
	 *
	 * @param string $quantity_html The quantity HTML.
	 * @param array  $wrapper_attributes Optional wrapper attributes.
	 * @param array  $input_attributes Optional input attributes.
	 * @param array  $context {
	 *     Optional context for quantity input.
	 *     @type int  $productId  Product ID for context-specific behavior.
	 *     @type bool $allowZero  Whether to allow zero quantity.
	 * }
	 *
	 * @return string The quantity HTML with interactive wrapper.
	 */
	public static function make_quantity_input_interactive( $quantity_html, $wrapper_attributes = array(), $input_attributes = array(), $context = array() ) {
		$processor = new \WP_HTML_Tag_Processor( $quantity_html );
		global $product;

		if (
			$processor->next_tag( 'input' ) &&
			$processor->get_attribute( 'type' ) === 'number' &&
			strpos( $processor->get_attribute( 'name' ), 'quantity' ) !== false
		) {
			$default_quantity = $product instanceof \WC_Product ? $product->get_min_purchase_quantity() : 1;
			$input_quantity   = isset( $context['allowZero'] ) && true === $context['allowZero'] ? 0 : $default_quantity;

			wp_interactivity_state(
				'woocommerce/add-to-cart-with-options-quantity-selector',
				array(
					'inputQuantity' => $input_quantity,
				)
			);

			$processor->set_attribute( 'data-wp-on--blur', 'actions.handleQuantityBlur' );
			$processor->set_attribute( 'data-wp-bind--value', 'state.inputQuantity' );
			foreach ( $input_attributes as $attribute => $value ) {
				$processor->set_attribute( $attribute, $value );
			}
		}

		$quantity_html = $processor->get_updated_html();

		$wrapper_attributes = array_merge(
			array(
				'data-wp-interactive' => 'woocommerce/add-to-cart-with-options-quantity-selector',
				'data-wp-init'        => 'callbacks.storeInputElementRef',
			),
			$wrapper_attributes
		);

		$context_attribute = wp_interactivity_data_wp_context(
			wp_parse_args(
				$context,
				array(
					'productId' => $product instanceof \WC_Product ? $product->get_id() : 0,
				)
			)
		);

		return sprintf(
			'<div %1$s %2$s>%3$s</div>',
			get_block_wrapper_attributes( $wrapper_attributes ),
			$context_attribute,
			$quantity_html
		);
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
		if ( $product->is_type( ProductType::SIMPLE ) ) {
			return ! $product->is_in_stock() || ! $product->is_purchasable();
		} elseif ( $product->is_type( ProductType::VARIABLE ) ) {
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
		$min_purchase_quantity = $product->get_min_purchase_quantity();
		$max_purchase_quantity = $product->get_max_purchase_quantity();
		return $min_purchase_quantity === $max_purchase_quantity;
	}

	/**
	 * Get the quantity constraints for a product.
	 *
	 * @param \WC_Product $product The product to get the quantity constraints for.
	 * @return array The quantity constraints.
	 */
	public static function get_product_quantity_constraints( $product ) {
		$min          = is_numeric( $product->get_min_purchase_quantity() ) ? $product->get_min_purchase_quantity() : 1;
		$max_quantity = $product->get_max_purchase_quantity();
		$max          = is_numeric( $max_quantity ) && -1 !== $max_quantity ? $max_quantity : null;
		$step         = is_numeric( $product->get_purchase_quantity_step() ) ? $product->get_purchase_quantity_step() : 1;

		return array(
			'min'  => $min,
			'max'  => $max,
			'step' => $step,
		);
	}
}
