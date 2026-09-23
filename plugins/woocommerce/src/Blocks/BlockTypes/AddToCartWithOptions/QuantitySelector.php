<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;
use Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\Utils as AddToCartWithOptionsUtils;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Enums\ProductType;

/**
 * Block type for quantity selector in add to cart with options.
 */
class QuantitySelector extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-quantity-selector';

	/**
	 * Render the block.
	 *
	 * The selector is hidden for:
	 * - Simple products that are out of stock.
	 * - Not purchasable simple products.
	 * - External products with URLs
	 * - Products sold individually
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

		$product = AddToCartWithOptionsUtils::get_product_from_context( $block, $previous_product );

		if ( ! $product ) {
			$product = $previous_product;

			return '';
		}

		if ( AddToCartWithOptionsUtils::is_not_purchasable_product( $product ) ) {
			$product = $previous_product;

			return '';
		}

		$is_external_product_with_url        = $product instanceof \WC_Product_External && $product->get_product_url();
		$can_only_be_purchased_one_at_a_time = $product->is_sold_individually();
		$managing_stock                      = $product->managing_stock();
		$stock_quantity                      = $product->get_stock_quantity();
		$allows_backorders                   = $product->backorders_allowed();

		if ( AddToCartWithOptionsUtils::is_min_max_quantity_same( $product ) ) {
			$product = $previous_product;

			return '';
		}

		if ( $is_external_product_with_url || $can_only_be_purchased_one_at_a_time || ( $managing_stock && $stock_quantity <= 1 && ! $allows_backorders ) ) {
			$product = $previous_product;

			return '';
		}

		ob_start();

		woocommerce_quantity_input(
			array(
				'min_value'   => $product->get_min_purchase_quantity(),
				'max_value'   => $product->get_max_purchase_quantity(),
				'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // phpcs:ignore WordPress.Security.NonceVerification.Missing
			)
		);

		$product_html = ob_get_clean();

		// If the quantity input is hidden, don't render the stepper buttons and styles.
		$has_visible_quantity_input = AddToCartWithOptionsUtils::has_visible_quantity_input( $product_html );
		if ( $has_visible_quantity_input ) {
			$product_name = $product->get_name();
			$product_html = AddToCartWithOptionsUtils::add_quantity_steppers( $product_html, $product_name );
			$product_html = AddToCartWithOptionsUtils::add_quantity_stepper_classes( $product_html );
		}

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );

		$classes = implode(
			' ',
			array_filter(
				array(
					'wp-block-add-to-cart-with-options-quantity-selector wc-block-add-to-cart-with-options__quantity-selector',
					esc_attr( $classes_and_styles['classes'] ),
					$has_visible_quantity_input ? '' : 'wc-block-add-to-cart-with-options__quantity-selector--hidden',
				)
			)
		);

		$wrapper_attributes = array(
			'class' => $classes,
			'style' => esc_attr( $classes_and_styles['styles'] ),
		);
		$input_attributes   = array();

		$product_quantity_constraints = AddToCartWithOptionsUtils::get_product_quantity_constraints( $product );

		if ( $product->is_type( ProductType::VARIABLE ) ) {
			wp_enqueue_script_module( 'woocommerce/product-elements' );

			$wrapper_attributes['data-wp-bind--hidden'] = 'woocommerce/add-to-cart-with-options-quantity-selector::!state.allowsQuantityChange';
			$input_attributes['data-wp-bind--min']      = 'woocommerce/products::state.productInContext.add_to_cart.minimum';
			$input_attributes['data-wp-bind--max']      = 'woocommerce/products::state.productInContext.add_to_cart.maximum';
			$input_attributes['data-wp-bind--step']     = 'woocommerce/products::state.productInContext.add_to_cart.multiple_of';
			$input_attributes['data-wp-watch']          = 'woocommerce/add-to-cart-with-options::callbacks.watchQuantityConstraints';
		}

		$form = AddToCartWithOptionsUtils::make_quantity_input_interactive( $product_html, $wrapper_attributes, $input_attributes );

		$product = $previous_product;

		return $form;
	}
}
