<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * CatalogSorting class.
 */
class AddToCartForm extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-form';

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
		if ( 'stepper' !== $attributes['quantitySelectorStyle'] ) {
			return;
		}

		parent::enqueue_assets( $attributes, $content, $block );
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		parent::enqueue_data( $attributes );
		$this->asset_data_registry->add( 'isStepperLayoutFeatureEnabled', Features::is_enabled( 'add-to-cart-with-options-stepper-layout' ) );
		$this->asset_data_registry->add( 'isBlockTheme', wp_is_block_theme() );
	}

	/**
	 * Add increment and decrement buttons to the quantity input field.
	 *
	 * @param string $product_html add-to-cart form HTML.
	 * @param string $product_name Product name.
	 * @return stringa add-to-cart form HTML with increment and decrement buttons.
	 */
	private function add_steppers( $product_html, $product_name ) {
		$pattern_input     = '/(<input[^>]*id="quantity_[^\"]*"[^>]*)\/>/';
		$replacement_input = '$1 data-wp-on--change="actions.handleInputChange" />';
		$product_html      = preg_replace( $pattern_input, $replacement_input, $product_html );

		// Regex pattern to match the <input> element with id starting with 'quantity_'.
		$pattern_stepper = '/(<input[^>]*id="quantity_[^\"]*"[^>]*data-wp-on--change="actions.handleInputChange"[^>]*\/>)/';
		// Replacement string to add button AFTER the matched <input> element.
		/* translators: %s refers to the item name in the cart. */
		$minus_button = '$1<button aria-label="' . esc_attr( sprintf( __( 'Reduce quantity of %s', 'woocommerce' ), $product_name ) ) . '" type="button" data-wp-on--click="actions.removeQuantity" data-wp-bind--disabled="!state.allowsDecrease" class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus">-</button>';
		// Replacement string to add button AFTER the matched <input> element.
		/* translators: %s refers to the item name in the cart. */
		$plus_button = '$1<button aria-label="' . esc_attr( sprintf( __( 'Increase quantity of %s', 'woocommerce' ), $product_name ) ) . '" type="button" data-wp-on--click="actions.addQuantity" data-wp-bind--disabled="!state.allowsIncrease" class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--plus">+</button>';
		$new_html    = preg_replace( $pattern_stepper, $plus_button, $product_html );
		$new_html    = preg_replace( $pattern_stepper, $minus_button, $new_html );
		return $new_html;
	}

	/**
	 * Filter the quantity column for grouped products to add interactive context for each child.
	 *
	 * @param string     $value The HTML for the quantity input.
	 * @param WC_Product $grouped_product_child The child product object.
	 * @return string     The wrapped HTML with interactive context.
	 */
	public function filter_grouped_product_quantity_column( $value, $grouped_product_child ) {
		// Only wrap if stepper style is enabled and the child is purchasable and in stock.
		if ( ! Features::is_enabled( 'add-to-cart-with-options-stepper-layout' ) ) {
			return $value;
		}
		if ( ! $grouped_product_child->is_purchasable() || $grouped_product_child->has_options() || ! $grouped_product_child->is_in_stock() ) {
			return $value;
		}
		$child_id     = $grouped_product_child->get_id();
		$context_json = esc_attr(
			wp_json_encode(
				array(
					'childProductId' => $child_id,
					'productType'    => 'grouped',
				)
			)
		);
		return '<div data-wp-interactive="woocommerce/add-to-cart-form" data-wp-context=' . $context_json . '>' . $value . '</div>';
	}

	/**
	 * Add classes to the Add to Cart form input needed for the stepper style.
	 *
	 * @param string $product_html The Add to Cart form HTML.
	 *
	 * @return string The Add to Cart form HTML with classes added.
	 */
	private function add_stepper_classes_to_add_to_cart_form_input( $product_html ) {
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
	 * Check if a variation product has all attributes set.
	 * Returns true if the product is not variation, or if all variation attributes have defined values.
	 *
	 * @param WC_Product $product The product to check.
	 *
	 * @return bool True if all attributes are set, false otherwise.
	 */
	private function has_all_attributes_set( $product ) {
		// If it's not a variation product, return true.
		if ( ! $product->is_type( 'variation' ) ) {
			return true;
		}

		// Get all variation attributes.
		$variation_attributes = $product->get_variation_attributes();

		// If there are no variation attributes, return true.
		if ( empty( $variation_attributes ) ) {
			return true;
		}

		// Check if any attribute has an empty value (marked as 'any').
		if ( in_array( '', array_values( $variation_attributes ), true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get quantity constraints for a product.
	 *
	 * @param WC_Product $product The product object.
	 * @param bool       $is_grouped_child Whether this is a grouped child product.
	 * @return array
	 */
	private function get_quantity_constraints( $product, $is_grouped_child = false ) {
		/**
		 * Filter the minimum quantity value allowed for the product.
		 *
		 * @since 10.1.0
		 * @param int        $min_value Minimum quantity value.
		 * @param WC_Product $product   Product object.
		 */
		$min = apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product );
		/**
		 * Filter the maximum quantity value allowed for the product.
		 *
		 * @since 10.1.0
		 * @param int        $max_value Maximum quantity value.
		 * @param WC_Product $product   Product object.
		 */
		$max = apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product );
		/**
		 * Filter the step quantity value allowed for the product.
		 *
		 * @since 10.1.0
		 * @param int        $step_value Step quantity value.
		 * @param WC_Product $product    Product object.
		 */
		$step = apply_filters( 'woocommerce_quantity_input_step', 1, $product );

		$min = max( $min, 0 );
		$max = 0 < $max ? $max : '';
		if ( '' !== $max && $max < $min ) {
			$max = $min;
		}
		return array(
			'min'  => $is_grouped_child ? 0 : (int) $min,
			'max'  => ( '' !== $max && -1 !== $max ) ? (int) $max : null,
			'step' => (int) $step,
		);
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

		$post_id = $block->context['postId'];

		if ( ! isset( $post_id ) ) {
			return '';
		}

		$is_descendent_of_single_product_block = is_null( $product ) || $post_id !== $product->get_id();

		$previous_product = $product;
		$product          = wc_get_product( $post_id );
		if ( ! $product instanceof \WC_Product ) {
			$product = $previous_product;

			return '';
		}

		// Check if all attributes are set for variation product.
		if ( $product->is_type( 'variation' ) && ! $this->has_all_attributes_set( $product ) ) {
			$product = $previous_product;

			return '';
		}

		$is_external_product_with_url = $product instanceof \WC_Product_External && $product->get_product_url();
		$managing_stock               = $product->managing_stock();
		$stock_quantity               = $product->get_stock_quantity();

		/**
		 * The stepper buttons don't show when the product is sold individually or stock quantity is less or equal to 1 because the quantity input field is hidden.
		 */
		$is_stepper_style = 'stepper' === $attributes['quantitySelectorStyle'] && ! $product->is_sold_individually() && ( ( $managing_stock && $stock_quantity > 1 ) || ! $managing_stock ) && Features::is_enabled( 'add-to-cart-with-options-stepper-layout' );

		if ( $is_descendent_of_single_product_block ) {
			add_filter( 'woocommerce_add_to_cart_form_action', array( $this, 'add_to_cart_form_action' ), 10 );
		}

		/**
		 * Add filter for grouped product quantity column to inject context for each child.
		 */
		add_filter( 'woocommerce_grouped_product_list_column_quantity', array( $this, 'filter_grouped_product_quantity_column' ), 10, 2 );

		ob_start();

		/**
		 * Manage variations in the same way as simple products.
		 */
		add_action( 'woocommerce_variation_add_to_cart', 'woocommerce_simple_add_to_cart', 10 );

		/**
		 * Trigger the single product add to cart action for each product type.
		 *
		 * @since 9.7.0
		 */
		do_action( 'woocommerce_' . $product->get_type() . '_add_to_cart' );

		/**
		 * Remove the hook to prevent potential conflicts with existing code and extensions.
		 */
		remove_action( 'woocommerce_variation_add_to_cart', 'woocommerce_simple_add_to_cart', 10 );

		$product_html = ob_get_clean();

		remove_filter( 'woocommerce_grouped_product_list_column_quantity', array( $this, 'filter_grouped_product_quantity_column' ), 10 );

		if ( $is_descendent_of_single_product_block ) {
			remove_filter( 'woocommerce_add_to_cart_form_action', array( $this, 'add_to_cart_form_action' ), 10 );
		}

		if ( ! $product_html ) {
			$product = $previous_product;

			return '';
		}

		$product_name = $product->get_name();
		$product_html = $is_stepper_style ? $this->add_steppers( $product_html, $product_name ) : $product_html;

		$parsed_attributes  = $this->parse_attributes( $attributes );
		$product_html       = $is_stepper_style ? $this->add_stepper_classes_to_add_to_cart_form_input( $product_html ) : $product_html;
		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );

		$product_classname = $is_descendent_of_single_product_block ? 'product' : '';

		$classes = implode(
			' ',
			array_filter(
				array(
					'wp-block-add-to-cart-form wc-block-add-to-cart-form',
					esc_attr( $classes_and_styles['classes'] ),
					esc_attr( $product_classname ),
					$is_stepper_style ? 'wc-block-add-to-cart-form--stepper' : 'wc-block-add-to-cart-form--input',
				)
			)
		);

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => $classes,
				'style' => esc_attr( $classes_and_styles['styles'] ),
			)
		);

		if ( $is_stepper_style ) {
			$context = array();
			if ( $product->is_type( 'grouped' ) ) {
				$context['groupedProductIds']   = array();
				$context['quantityConstraints'] = array();
				foreach ( $product->get_children() as $child_product_id ) {
					$child_product = wc_get_product( $child_product_id );
					if ( $child_product && $child_product->is_purchasable() && $child_product->is_in_stock() && ! $child_product->has_options() ) {
						$context['groupedProductIds'][]                      = $child_product_id;
						$context['quantityConstraints'][ $child_product_id ] = $this->get_quantity_constraints( $child_product, true );
					}
				}
			} else {
				$context['quantityConstraints'] = array(
					$product->get_id() => $this->get_quantity_constraints( $product ),
				);
				$context['productId']           = $product->get_id();
				$context['productType']         = $product->get_type();
			}
		}

		$form = sprintf(
			'<div %1$s %2$s %3$s>%4$s</div>',
			$wrapper_attributes,
			$is_stepper_style ? 'data-wp-interactive="woocommerce/add-to-cart-form"' : '',
			$is_stepper_style ? sprintf(
				'data-wp-context=\'%s\'',
				wp_json_encode(
					$context,
					JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
				)
			) : '',
			$product_html
		);

		$product = $previous_product;

		return $form;
	}

	/**
	 * Use current url as the add to cart form action.
	 *
	 * @return string The current URL.
	 */
	public function add_to_cart_form_action() {
		global $wp;
		return home_url( add_query_arg( $_GET, $wp->request ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
}
