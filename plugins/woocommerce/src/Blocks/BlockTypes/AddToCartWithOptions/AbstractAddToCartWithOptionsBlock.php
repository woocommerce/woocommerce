<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;
use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\Utils;
use WP_Block;

/**
 * AbstractAddToCartWithOptionsBlock class.
 */
abstract class AbstractAddToCartWithOptionsBlock extends AbstractBlock {
	use EnableBlockJsonAssetsTrait;

	// AddToCartWithOptions Class.
	/**
	 * Get template part IDs for each product type.
	 *
	 * @return array Array of product types with their corresponding template part IDs.
	 */
	protected function get_template_part_ids() {
		$product_types = array_keys( wc_get_product_types() );
		$current_theme = wp_get_theme()->get_stylesheet();

		$template_part_ids = array();
		foreach ( $product_types as $product_type ) {
			$slug = $product_type . '-product-add-to-cart-with-options';

			// Check if theme template exists.
			$theme_has_template = BlockTemplateUtils::theme_has_template_part( $slug );

			if ( $theme_has_template ) {
				$template_part_ids[ $product_type ] = "{$current_theme}//{$slug}";
			} else {
				$template_part_ids[ $product_type ] = "woocommerce/woocommerce//{$slug}";
			}
		}

		return $template_part_ids;
	}
	// AddToCartWithOptions Class.

	// GroupedProductSelectorItemCTA Class.
	/**
	 * Gets the quantity selector markup for a product.
	 *
	 * @param \WC_Product $product The product object.
	 * @return string The HTML markup for the quantity selector.
	 */
	protected function get_quantity_selector_markup( $product ) {
		ob_start();

		woocommerce_quantity_input( $this->get_quantity_input_args( $product ) );

		$quantity_html = ob_get_clean();

		// Modify the quantity input to add stepper buttons.
		$product_name = $product->get_name();

		$quantity_html = $this->add_quantity_steppers( $quantity_html, $product_name );
		$quantity_html = $this->add_quantity_stepper_classes( $quantity_html );

		// Add interactive data attribute for the stepper functionality.
		$quantity_html = $this->make_quantity_input_interactive( $quantity_html );

		return $quantity_html;
	}

	/**
	 * Gets the add to cart button markup for a product.
	 *
	 * @param \WC_Product $product_to_render The product object.
	 * @return string The HTML markup for the add to cart button.
	 */
	protected function get_button_markup( $product_to_render ) {
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
	protected function get_checkbox_markup( $product ) {
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
	// GroupedProductSelectorItemCTA Class.

	// GroupedProductSelectorItemTemplate Class.
	// VariationSelectorItemTemplate Class.
	/**
	 * Renders a new block with custom context
	 *
	 * @param WP_Block $block The block instance.
	 * @param array    $context The context for the new block.
	 * @return string Rendered block content
	 */
	protected function render_block_with_context( $block, $context ) {
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
	// GroupedProductSelectorItemTemplate Class.
	// VariationSelectorItemTemplate Class.

	// QuantitySelector Class.
	/**
	 * Add increment and decrement buttons to the quantity input field.
	 *
	 * @param string $quantity_html Quantity input HTML.
	 * @param string $product_name Product name.
	 * @return string Quantity input HTML with increment and decrement buttons.
	 */
	protected function add_quantity_steppers( $quantity_html, $product_name ) {
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
	protected function add_quantity_stepper_classes( $quantity_html ) {
		$html = new \WP_HTML_Tag_Processor( $quantity_html );

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
	 * Get standardized quantity input arguments for WooCommerce quantity input.
	 *
	 * @param \WC_Product $product The product object.
	 * @return array Arguments for woocommerce_quantity_input().
	 */
	protected function get_quantity_input_args( $product ) {
		return array(
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
		);
	}

	/**
	 * Make the quantity input interactive by wrapping it with the necessary data attribute.
	 *
	 * @param string $quantity_html The quantity HTML.
	 * @param string $wrapper_attributes Optional wrapper attributes.
	 * @return string The quantity HTML with interactive wrapper.
	 */
	protected function make_quantity_input_interactive( $quantity_html, $wrapper_attributes = '' ) {
		if ( ! empty( $wrapper_attributes ) ) {
			return sprintf(
				'<div %1$s data-wp-interactive="woocommerce/add-to-cart-with-options">%2$s</div>',
				$wrapper_attributes,
				$quantity_html
			);
		}

		return '<div data-wp-interactive="woocommerce/add-to-cart-with-options">' . $quantity_html . '</div>';
	}
	// QuantitySelector Class.

	// VariationSelector Class.
	/**
	 * Get variations data.
	 *
	 * @param WC_Product $product Product instance.
	 * @return array|false
	 */
	protected function get_variations_data( $product ) {
		/**
		 * Filter the number of variations threshold.
		 *
		 * @since 9.7.0
		 *
		 * @param int        $threshold Maximum number of variations to load upfront.
		 * @param WC_Product $product   Product object.
		 */
		$get_variations = count( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
		return $get_variations ? $product->get_available_variations() : false;
	}

	/**
	 * Add 'ajax_add_to_cart' support to a Variable Product.
	 *
	 * This is needed so the ProductButton block could add a Variable Product to
	 * the Cart without a page refresh.
	 *
	 * @param  bool        $supports If features are already supported or not.
	 * @param  string      $feature  The feature to check if is supported.
	 * @param  \WC_Product $product  The product to check.
	 * @return bool True if the product supports the feature, false otherwise.
	 * @since  9.9.0
	 */
	protected function check_product_supports( $supports, $feature, $product ) {
		if ( 'ajax_add_to_cart' === $feature ) {
			return true;
		}

		return $supports;
	}
	// VariationSelector Class.

	// VariationSelectorAttributeOptions Class.
	/**
	 * Get the block's attributes.
	 *
	 * @param array $attributes Block attributes. Default empty array.
	 * @return array  Block attributes merged with defaults.
	 */
	protected function parse_attributes( $attributes ) {
		// These should match what's set in JS `registerBlockType`.
		$defaults = array(
			'style' => 'pills',
		);

		return wp_parse_args( $attributes, $defaults );
	}

	/**
	 * Get the default selected attribute.
	 *
	 * @param array $attribute_terms The attribute's.
	 * @return string|null The default selected attribute.
	 */
	protected function get_default_selected_attribute( $attribute_terms ) {
		foreach ( $attribute_terms as $attribute_term ) {
			if ( $attribute_term['isSelected'] ) {
				return $attribute_term['value'];
			}
		}

		return null;
	}

	/**
	 * Render the attribute options as pills.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 * @return string The pills.
	 */
	protected function render_pills( $attributes, $content, $block ) {
		$attribute_id    = $block->context['woocommerce/attributeId'];
		$attribute_name  = $block->context['woocommerce/attributeName'];
		$attribute_terms = $block->context['woocommerce/attributeTerms'];

		$pills = '';
		foreach ( $attribute_terms as $attribute_term ) {
			$pills .= sprintf(
				'<div %s>%s</div>',
				Utils::get_normalized_attributes(
					array(
						'role'                       => 'radio',
						'class'                      => 'wc-block-add-to-cart-with-options-variation-selector-attribute-options__pill',
						'data-wp-bind--tabindex'     => 'state.pillTabIndex',
						'data-wp-bind--aria-checked' => 'state.isPillSelected',
						'data-wp-watch'              => 'callbacks.watchSelected',
						'data-wp-on--click'          => 'actions.toggleSelected',
						'data-wp-on--keydown'        => 'actions.handleKeyDown',
						'data-wp-context'            => array(
							'option' => $attribute_term,
						),
					),
				),
				$attribute_term['label']
			);
		}

		return sprintf(
			'<div %s>%s</div>',
			Utils::get_normalized_attributes(
				array(
					'class'               => 'wc-block-add-to-cart-with-options-variation-selector-attribute-options__pills',
					'role'                => 'radiogroup',
					'id'                  => $attribute_id,
					'aria-labeledby'      => $attribute_id . '_label',
					'data-wp-interactive' => $this->get_full_block_name() . '__pills',
					'data-wp-context'     => array(
						'name'          => $attribute_name,
						'options'       => $attribute_terms,
						'selectedValue' => $this->get_default_selected_attribute( $attribute_terms ),
						'focused'       => '',
					),
					'data-wp-init'        => 'callbacks.setDefaultSelectedAttribute',
				),
			),
			$pills,
		);
	}

	/**
	 * Render the attribute options as a dropdown.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 * @return string The dropdown.
	 */
	protected function render_dropdown( $attributes, $content, $block ) {
		$attribute_id    = $block->context['woocommerce/attributeId'];
		$attribute_name  = $block->context['woocommerce/attributeName'];
		$attribute_terms = $block->context['woocommerce/attributeTerms'];
		$default_option  = array(
			'label'      => esc_html__( 'Choose an option', 'woocommerce' ),
			'value'      => '',
			'isSelected' => false,
		);

		$attribute_terms = array_merge(
			array( $default_option ),
			$attribute_terms
		);

		$options = '';
		foreach ( $attribute_terms as $attribute_term ) {
			$options .= sprintf(
				'<option %s>%s</option>',
				Utils::get_normalized_attributes(
					array(
						'value'           => $attribute_term['value'],
						'selected'        => $attribute_term['isSelected'] ? 'selected' : null,
						'data-wp-context' => array(
							'option' => $attribute_term,
						),
					),
				),
				$attribute_term['label']
			);
		}

		return sprintf(
			'<select %s>%s</select>',
			Utils::get_normalized_attributes(
				array(
					'class'               => 'wc-block-add-to-cart-with-options-variation-selector-attribute-options__dropdown',
					'id'                  => $attribute_id,
					'data-wp-interactive' => $this->get_full_block_name() . '__dropdown',
					'data-wp-context'     => array(
						'name'          => $attribute_name,
						'options'       => $attribute_terms,
						'selectedValue' => $this->get_default_selected_attribute( $attribute_terms ),
					),
					'data-wp-init'        => 'callbacks.setDefaultSelectedAttribute',
					'data-wp-on--change'  => 'actions.handleChange',
				),
			),
			$options,
		);
	}
	// VariationSelectorAttributeOptions Class.

	// VariationSelectorItemTemplate Class.
	/**
	 * Get product attributes terms.
	 *
	 * @param string $attribute_name Product Attribute Name.
	 * @param array  $attribute_terms Product Attribute Terms.
	 * @return srtring
	 */
	protected function get_terms( $attribute_name, $attribute_terms ) {
		global $product;

		$is_taxonomy = taxonomy_exists( $attribute_name );

		$selected_attribute = $product->get_variation_default_attribute( $attribute_name );

		if ( $is_taxonomy ) {
			$items = array_map(
				function ( $term ) use ( $attribute_name, $product, $selected_attribute ) {
					return $this->create_term_item(
						$term,              // The term object.
						$term->slug,        // The term value.
						$term->name,        // The term label.
						$attribute_name,
						$product,
						$selected_attribute
					);
				},
				wc_get_product_terms( $product->get_id(), $attribute_name, array( 'fields' => 'all' ) ),
			);
		} else {
			$items = array_map(
				function ( $term_value ) use ( $attribute_name, $product, $selected_attribute ) {
					return $this->create_term_item(
						null,               // No term object for custom attributes.
						$term_value,        // The term value.
						$term_value,        // The term label (same as value).
						$attribute_name,
						$product,
						$selected_attribute
					);
				},
				$attribute_terms,
			);
		}

		return $items;
	}
	// VariationSelectorItemTemplate Class.

	/**
	 * Create a term item for attribute options.
	 *
	 * @param mixed      $term The term object or null.
	 * @param string     $value The value.
	 * @param string     $label The label.
	 * @param string     $attribute_name The attribute name.
	 * @param WC_Product $product The product.
	 * @param string     $selected_attribute The selected attribute.
	 * @return array The term item.
	 */
	protected function create_term_item( $term, $value, $label, $attribute_name, $product, $selected_attribute ) {
		return array(
			'value'      => $value,
			/**
			 * Filter the variation option name.
			 *
			 * @since 9.7.0
			 *
			 * @param string     $option_label    The option label.
			 * @param WP_Term|string|null $item   Term object for taxonomies, option string for custom attributes.
			 * @param string     $attribute_name  Name of the attribute.
			 * @param WC_Product $product         Product object.
			 */
			'label'      => apply_filters(
				'woocommerce_variation_option_name',
				$label,
				$term,
				$attribute_name,
				$product
			),
			'isSelected' => $selected_attribute === $value,
		);
	}
}
