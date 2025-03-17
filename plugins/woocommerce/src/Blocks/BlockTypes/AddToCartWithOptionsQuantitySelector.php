<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * AddToCartWithOptionsQuantitySelector class.
 */
class AddToCartWithOptionsQuantitySelector extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-quantity-selector';

	/**
	 * Get the block's attributes.
	 *
	 * @param array $attributes Block attributes. Default empty array.
	 * @return array  Block attributes merged with defaults.
	 */
	private function parse_attributes( $attributes ) {
		// These should match what's set in JS `registerBlockType`.
		$defaults = array(
			'quantitySelectorStyle' => 'input',
		);

		return wp_parse_args( $attributes, $defaults );
	}


	/**
	 * Enqueue assets specific to this block.
	 * We enqueue frontend scripts only if the quantitySelectorStyle is set to 'stepper'.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 */
	protected function enqueue_assets( $attributes, $content, $block ) {
		if ( ! isset( $attributes['quantitySelectorStyle'] ) || 'stepper' !== $attributes['quantitySelectorStyle'] ) {
			return;
		}

		parent::enqueue_assets( $attributes, $content, $block );
	}

	/**
	 * Add increment and decrement buttons to the quantity input field.
	 *
	 * @param string $product_html Quantity input HTML.
	 * @param string $product_name Product name.
	 * @return stringa Quantity input HTML with increment and decrement buttons.
	 */
	private function add_steppers( $product_html, $product_name ) {
		// Regex pattern to match the <input> element with id starting with 'quantity_'.
		$pattern = '/(<input[^>]*id="quantity_[^"]*"[^>]*\/>)/';
		// Replacement string to add button BEFORE the matched <input> element.
		/* translators: %s refers to the item name in the cart. */
		$minus_button = '<button aria-label="' . esc_attr( sprintf( __( 'Reduce quantity of %s', 'woocommerce' ), $product_name ) ) . '"type="button" data-wp-on--click="actions.removeQuantity" class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus">-</button>$1';
		// Replacement string to add button AFTER the matched <input> element.
		/* translators: %s refers to the item name in the cart. */
		$plus_button = '$1<button aria-label="' . esc_attr( sprintf( __( 'Increase quantity of %s', 'woocommerce' ), $product_name ) ) . '" type="button" data-wp-on--click="actions.addQuantity" class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--plus">+</button>';
		$new_html    = preg_replace( $pattern, $minus_button, $product_html );
		$new_html    = preg_replace( $pattern, $plus_button, $new_html );
		return $new_html;
	}

	/**
	 * Add classes to the Quantity Selector needed for the stepper style.
	 *
	 * @param string $product_html The Quantity Selector HTML.
	 *
	 * @return string The Quantity Selector HTML with classes added.
	 */
	private function add_stepper_classes( $product_html ) {
		$html = new \WP_HTML_Tag_Processor( $product_html );

		// Add classes to the form.
		while ( $html->next_tag( array( 'class_name' => 'quantity' ) ) ) {
			$html->add_class( 'wc-block-components-quantity-selector' );
		}

		$html = new \WP_HTML_Tag_Processor( $html->get_updated_html() );
		while ( $html->next_tag( array( 'class_name' => 'input-text' ) ) ) {
			$html->add_class( 'wc-block-components-quantity-selector__input' );
		}

		return $html->get_updated_html();
	}


	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 *
	 * @return string | void Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		global $product;
		$previous_product = $product;

		// Try to load the product from the block context, if not available,
		// use the global $product.
		$post_id = isset( $block->context['postId'] ) ? $block->context['postId'] : '';
		$post    = $post_id ? wc_get_product( $post_id ) : null;
		if ( $post instanceof \WC_Product ) {
			$product = $post;
		} elseif ( ! $product instanceof \WC_Product ) {
			return '';
		}

		wp_enqueue_script_module( $this->get_full_block_name() );

		$is_external_product_with_url        = $product instanceof \WC_Product_External && $product->get_product_url();
		$can_only_be_purchased_one_at_a_time = $product->is_sold_individually();

		if ( $is_external_product_with_url || $can_only_be_purchased_one_at_a_time ) {
			return '';
		}

		$is_stepper_style = isset( $attributes['quantitySelectorStyle'] ) && 'stepper' === $attributes['quantitySelectorStyle'] && ! $product->is_sold_individually();

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

		$product_html = ob_get_clean();

		$product_name = $product->get_name();
		$product_html = $is_stepper_style ? $this->add_steppers( $product_html, $product_name ) : $product_html;

		$parsed_attributes  = $this->parse_attributes( $attributes );
		$product_html       = $is_stepper_style ? $this->add_stepper_classes( $product_html ) : $product_html;
		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );

		$classes = implode(
			' ',
			array_filter(
				array(
					'wp-block-add-to-cart-with-options-quantity-selector wc-block-add-to-cart-with-options__quantity-selector',
					esc_attr( $classes_and_styles['classes'] ),
					$is_stepper_style ? 'wc-block-add-to-cart-with-options__quantity-selector--stepper' : 'wc-block-add-to-cart-with-options__quantity-selector--input',
				)
			)
		);

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => $classes,
				'style' => esc_attr( $classes_and_styles['styles'] ),
			)
		);

		$form = sprintf(
			'<div %1$s %2$s>%3$s</div>',
			$wrapper_attributes,
			$is_stepper_style ? 'data-wp-interactive="woocommerce/add-to-cart-with-options"' : '',
			$product_html
		);

		$product = $previous_product;

		return $form;
	}

	/**
	 * Disable the frontend script for this block type, it's built with script modules.
	 *
	 * @param string $key Data to get, or default to everything.
	 * @return array|string|null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}
}
