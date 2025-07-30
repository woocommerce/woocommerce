<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;
use Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\Utils as AddToCartWithOptionsUtils;
use WP_Block;

/**
 * Block type for the CTA of grouped product selector items in add to cart with options.
 * It's responsible to render the CTA for each child product, that might be a button,
 * a checkbox, or a link.
 */
class GroupedProductItemSelector extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-grouped-product-item-selector';

	/**
	 * Set the quantity input type to number.
	 *
	 * @return string The quantity input type.
	 */
	public function set_quantity_input_type() {
		return 'number';
	}

	/**
	 * Gets the quantity selector markup for a product.
	 *
	 * @param \WC_Product $product The product object.
	 * @return string The HTML markup for the quantity selector.
	 */
	private function get_quantity_selector_markup( $product ) {
		ob_start();

		/**
		 * Filter the minimum quantity value allowed for the product.
		 *
		 * @since 10.1.0
		 * @param int        $min_value Minimum quantity value.
		 * @param WC_Product $product   Product object.
		 */
		$min_value = apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product );

		// By default, products have a min value of 1. In that case, we can
		// safely override it to 0 when they are a child of a grouped product.
		// The main benefit is that the the decrease quantity button will be
		// enabled even when the quantity is 1, which is a small quality of life
		// enhancement for shoppers.
		if ( 1 === $min_value ) {
			$min_value = 0;
		}

		/**
		 * Filter the maximum quantity value allowed for the product.
		 *
		 * @since 10.1.0
		 * @param int        $max_value Maximum quantity value.
		 * @param WC_Product $product   Product object.
		 */
		$max_value = apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product );

		if ( $min_value === $max_value && $min_value > 0 ) {
			add_filter( 'woocommerce_quantity_input_type', array( $this, 'set_quantity_input_type' ) );
		}

		woocommerce_quantity_input(
			array(
				'input_name'  => 'quantity[' . $product->get_id() . ']',
				'input_id'    => 'quantity_' . $product->get_id(),
				'input_value' => isset( $_POST['quantity'][ $product->get_id() ] ) ? wc_stock_amount( wc_clean( wp_unslash( $_POST['quantity'][ $product->get_id() ] ) ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'min_value'   => $min_value,
				'max_value'   => $max_value,
				/**
				 * Filter the placeholder value allowed for the product.
				 *
				 * @since 3.10.0
				 * @param int        $max_value Maximum quantity value.
				 * @param WC_Product $product   Product object.
				 */
				'placeholder' => apply_filters( 'woocommerce_quantity_input_placeholder', 0, $product ),
			)
		);

		if ( $min_value === $max_value && $min_value > 0 ) {
			remove_filter( 'woocommerce_quantity_input_type', array( $this, 'set_quantity_input_type' ) );
		}

		$quantity_html = ob_get_clean();

		// Remove the label because we are rendering one as a separate block via GroupedProductItemLabel.
		$quantity_html = $this->remove_quantity_label( $quantity_html );

		// Modify the quantity input to add stepper buttons.
		$product_name = $product->get_name();

		$quantity_html = AddToCartWithOptionsUtils::add_quantity_steppers( $quantity_html, $product_name );
		$quantity_html = AddToCartWithOptionsUtils::add_quantity_stepper_classes( $quantity_html );

		// Add interactive data attribute for the stepper functionality.
		$quantity_html = AddToCartWithOptionsUtils::make_quantity_input_interactive( $quantity_html, '', $product->get_id() );

		return $quantity_html;
	}

	/**
	 * Removes the label from quantity input HTML.
	 *
	 * @param string $quantity_html The quantity input HTML.
	 * @return string The quantity input HTML without the label.
	 */
	private function remove_quantity_label( $quantity_html ) {
		// Remove the label and aria-label from the quantity input.
		$quantity_html = preg_replace( '/<label[^>]*>.*?<\/label>/s', '', $quantity_html );
		return preg_replace( '/\s*aria-label="[^"]*"/', '', $quantity_html );
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
		return '<input type="checkbox" name="' . esc_attr( 'quantity[' . $product->get_id() . ']' ) . '" value="1" class="wc-grouped-product-add-to-cart-checkbox" id="' . esc_attr( 'quantity_' . $product->get_id() ) . '" data-wp-on--change="actions.handleQuantityCheckboxChange" />';
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
		global $product;
		$previous_product = $product;

		$product = AddToCartWithOptionsUtils::get_product_from_context( $block, $previous_product );
		$markup  = '';

		if ( $product ) {
			if ( ! $product->is_purchasable() || $product->has_options() || ! $product->is_in_stock() ) {
				$markup = $this->get_button_markup( $product );
			} elseif ( $product->is_sold_individually() ) {
				$markup = $this->get_checkbox_markup( $product );
			} else {
				$markup = $this->get_quantity_selector_markup( $product );
			}

			if ( $markup ) {
				$markup = '<div class="wp-block-add-to-cart-with-options-grouped-product-item-selector wc-block-add-to-cart-with-options-grouped-product-item-selector">' . $markup . '</div>';
			}
		}

		$product = $previous_product;

		return $markup;
	}
}
