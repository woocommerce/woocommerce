<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptionsQuantitySelector;
use WP_Block;

/**
 * Block type for the CTA of grouped product selector items in add to cart with options.
 * It's responsible to render the CTA for each child product, that might be a button,
 * a checkbox, or a link.
 */
class AddToCartWithOptionsGroupedProductSelectorItemCTA extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-grouped-product-selector-item-cta';

	/**
	 * Gets the quantity selector markup for a product.
	 *
	 * @param \WC_Product $product The product object.
	 * @return string The HTML markup for the quantity selector.
	 */
	private function get_quantity_selector_markup( $product ) {
		ob_start();

		woocommerce_quantity_input(
			array(
				/**
				 * Filter the minimum quantity value allowed for the product.
				 *
				 * @since 2.0.0
				 * @param int        $min_value Minimum quantity value.
				 * @param WC_Product $product   Product object.
				 */
				'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
				/**
				 * Filter the maximum quantity value allowed for the product.
				 *
				 * @since 2.0.0
				 * @param int        $max_value Maximum quantity value.
				 * @param WC_Product $product   Product object.
				 */
				'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
				'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // phpcs:ignore WordPress.Security.NonceVerification.Missing
			)
		);

		$quantity_selector_html = ob_get_clean();

		return $quantity_selector_html;
	}

	/**
	 * Gets the add to cart button markup for a product.
	 *
	 * @param \WC_Product $product_to_render The product object.
	 * @return string The HTML markup for the add to cart button.
	 */
	private function get_button_markup( $product_to_render ) {
		ob_start();
		woocommerce_template_loop_add_to_cart();
		$button_html = ob_get_clean();

		return $button_html;
	}

	/**
	 * Gets the checkbox markup for a product.
	 *
	 * @param \WC_Product $product The product object.
	 * @return string The HTML markup for the checkbox input and label.
	 */
	private function get_checkbox_markup( $product ) {
		if ( $product->is_on_sale() ) {
			$label = sprintf(
				/* translators: %1$s: Product name. %2$s: Sale price. %3$s: Regular price */
				esc_html__( 'Buy one of %1$s on sale for %2$s, original price was %3$s', 'woocommerce' ),
				esc_html( $product->get_name() ),
				esc_html( wp_strip_all_tags( wc_price( $product->get_price() ) ) ),
				esc_html( wp_strip_all_tags( wc_price( $product->get_regular_price() ) ) )
			);
		} else {
			$label = sprintf(
				/* translators: %1$s: Product name. %2$s: Product price */
				esc_html__( 'Buy one of %1$s for %2$s', 'woocommerce' ),
				esc_html( $product->get_name() ),
				esc_html( wp_strip_all_tags( wc_price( $product->get_price() ) ) )
			);
		}
		return '<input type="checkbox" name="' . esc_attr( 'quantity[' . $product->get_id() . ']' ) . '" value="1" class="wc-grouped-product-add-to-cart-checkbox" id="' . esc_attr( 'quantity-' . $product->get_id() ) . '" /><label for="' . esc_attr( 'quantity-' . $product->get_id() ) . '" class="screen-reader-text">' . $label . '</label>';
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ): string {
		$post_id = isset( $block->context['postId'] ) ? $block->context['postId'] : '';

		global $product;

		$previous_product = $product;

		if ( ! empty( $post_id ) ) {
			$product = wc_get_product( $post_id );
		}

		$markup = '';

		if ( $product instanceof \WC_Product ) {
			if ( ! $product->is_purchasable() || $product->has_options() || ! $product->is_in_stock() ) {
				$markup = $this->get_button_markup( $product );
			} elseif ( $product->is_sold_individually() ) {
				$markup = $this->get_checkbox_markup( $product );
			} else {
				$markup = $this->get_quantity_selector_markup( $product );
			}

			if ( $markup ) {
				$markup = '<div class="wp-block-add-to-cart-with-options-grouped-product-selector-item-cta wc-block-add-to-cart-with-options-grouped-product-selector-item-cta">' . $markup . '</div>';
			}
		}

		$product = $previous_product;

		return $markup;
	}
}
