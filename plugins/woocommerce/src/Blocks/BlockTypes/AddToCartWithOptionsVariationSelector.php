<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Block type for variation selector in add to cart with options.
 */
class AddToCartWithOptionsVariationSelector extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-variation-selector';

	/**
	 * Render variation label.
	 *
	 * @param string $attribute_name Name of the attribute.
	 * @return string Rendered label HTML.
	 */
	protected function render_variation_label( $attribute_name ): string {
		$label_id   = esc_attr( 'attribute_' . sanitize_title( $attribute_name ) );
		$label_text = wc_attribute_label( $attribute_name );

		$html = sprintf(
			'<label class="wc-block-product-add-to-cart-attribute-label" for="%s">%s</label>',
			$label_id,
			esc_html( $label_text )
		);
		return $html;
	}

	/**
	 * Render variation selector dropdown.
	 *
	 * @param WC_Product $product The product object.
	 * @param string     $attribute_name Name of the attribute.
	 * @param array      $options Available options for this attribute.
	 * @return string Rendered dropdown HTML.
	 */
	protected function render_variation_selector( $product, $attribute_name, $options ): string {
		$selected  = $this->get_selected_attribute_value( $product, $attribute_name );
		$select_id = esc_attr( 'attribute_' . sanitize_title( $attribute_name ) );

		$html = sprintf(
			'<select id="%1$s"
				class="wc-block-product-add-to-cart-attribute-select"
				name="%1$s"
				data-attribute_name="attribute_%2$s">',
			$select_id,
			esc_attr( sanitize_title( $attribute_name ) )
		);

		$html .= '<option value="">' . esc_html__( 'Choose an option', 'woocommerce' ) . '</option>';
		$html .= $this->get_variation_options_html( $product, $attribute_name, $options, $selected, taxonomy_exists( $attribute_name ) );
		$html .= '</select>';

		return $html;
	}

	/**
	 * Get selected attribute value.
	 *
	 * @param WC_Product $product The product object.
	 * @param string     $attribute_name Name of the attribute.
	 * @return string Selected value
	 */
	private function get_selected_attribute_value( $product, $attribute_name ): string {
		return $product->get_variation_default_attribute( $attribute_name );
	}

	/**
	 * Get HTML for variation options.
	 *
	 * @param WC_Product $product The product object.
	 * @param string     $attribute_name Name of the attribute.
	 * @param array      $options Available options.
	 * @param string     $selected Selected value.
	 * @param bool       $is_taxonomy Whether this is a taxonomy-based attribute.
	 * @return string Options HTML
	 */
	private function get_variation_options_html( $product, $attribute_name, $options, $selected, $is_taxonomy ): string {
		if ( empty( $options ) ) {
			return '';
		}

		$html  = '';
		$items = $is_taxonomy
			? wc_get_product_terms( $product->get_id(), $attribute_name, array( 'fields' => 'all' ) )
			: $options;

		foreach ( $items as $item ) {
			$option_value = $is_taxonomy ? $item->slug : $item;
			$option_label = $is_taxonomy ? $item->name : $item;

			if ( ! $is_taxonomy || in_array( $option_value, $options, true ) ) {
				$selected_attr = $is_taxonomy
					? selected( sanitize_title( $selected ), $option_value, false )
					: selected( $selected, $option_value, false );

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
				$filtered_label = apply_filters(
					'woocommerce_variation_option_name',
					$option_label,
					$is_taxonomy ? $item : null,
					$attribute_name,
					$product
				);

				$html .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $option_value ),
					$selected_attr,
					esc_html( $filtered_label )
				);
			}
		}

		return $html;
	}

	/**
	 * Render variation form.
	 *
	 * @param WC_Product $product Product instance.
	 * @param array      $attributes Block attributes.
	 * @return string Rendered form HTML
	 */
	private function render_variation_form( $product, $attributes ): string {
		$variation_attributes = $product->get_variation_attributes();
		if ( empty( $variation_attributes ) ) {
			return '';
		}

		$variations = $this->get_variations_data( $product );
		if ( empty( $variations ) ) {
			return '';
		}

		wp_enqueue_script_module( $this->get_full_block_name() );

		return $this->get_form_html( $product, $variations, $variation_attributes );
	}

	/**
	 * Get variations data.
	 *
	 * @param WC_Product $product Product instance.
	 * @return array|false
	 */
	private function get_variations_data( $product ) {
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
	 * Get form HTML.
	 *
	 * @param WC_Product $product Product instance.
	 * @param array      $variations Variations data.
	 * @param array      $variation_attributes Variation attributes.
	 * @return string Form HTML
	 */
	private function get_form_html( $product, $variations, $variation_attributes ): string {
		$variations_json = wp_json_encode( $variations );
		$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

		$form  = $this->get_form_opening( $product, $variations_attr );
		$form .= $this->get_variations_table( $product, $variation_attributes );
		$form .= '</form>';

		return $form;
	}

	/**
	 * Get form opening HTML.
	 *
	 * @param WC_Product $product Product instance.
	 * @param string     $variations_attr Variations JSON.
	 * @return string Form opening HTML
	 */
	private function get_form_opening( $product, $variations_attr ): string {
		return sprintf(
			'<form class="variations_form cart" data-product_id="%d" data-product_variations="%s">',
			absint( $product->get_id() ),
			$variations_attr
		);
	}

	/**
	 * Get variations table HTML.
	 *
	 * @param WC_Product $product Product instance.
	 * @param array      $variation_attributes Variation attributes.
	 * @return string Table HTML
	 */
	private function get_variations_table( $product, $variation_attributes ): string {
		ob_start();

		/**
		 * Action hook to add content before the variations table.
		 *
		 * @since 9.7.0
		 */
		do_action( 'woocommerce_before_variations_table' );
		$before_table = ob_get_clean();

		$table = '<table class="variations" cellspacing="0" role="presentation"><tbody>';

		foreach ( $variation_attributes as $attribute_name => $options ) {
			$table .= $this->get_variation_row( $product, $attribute_name, $options );
		}

		$table .= '</tbody></table>';

		ob_start();

		/**
		 * Action hook to add content after the variations table.
		 *
		 * @since 9.7.0
		 */
		do_action( 'woocommerce_after_variations_table' );
		$after_table = ob_get_clean();

		return $before_table . $table . $after_table;
	}

	/**
	 * Get variation row HTML.
	 *
	 * @param WC_Product $product Product instance.
	 * @param string     $attribute_name Attribute name.
	 * @param array      $options Attribute options.
	 * @return string Row HTML
	 */
	private function get_variation_row( $product, $attribute_name, $options ): string {
		$html  = '<tr>';
		$html .= '<th class="label">' . $this->render_variation_label( $attribute_name ) . '</th>';
		$html .= '<td class="value">';
		$html .= $this->render_variation_selector( $product, $attribute_name, $options );
		$html .= '</td>';
		$html .= '</tr>';

		$variation_attributes = $product->get_variation_attributes();
		$attribute_keys       = array_keys( $variation_attributes );
		if ( end( $attribute_keys ) === $attribute_name ) {
			$html .= $this->get_reset_button_row();
		}

		return $html;
	}

	/**
	 * Get reset button row HTML.
	 *
	 * @return string Row HTML
	 */
	private function get_reset_button_row(): string {
		return sprintf(
			'<tr><td colspan="2">%s</td></tr>',
			wp_kses_post(
				/**
				 * Filter the reset variation button.
				 *
				 * @since 9.7.0
				 */
				apply_filters(
					'woocommerce_reset_variations_link',
					sprintf(
						'<button class="reset_variations" aria-label="%1$s">%2$s</button>',
						esc_html__( 'Clear options', 'woocommerce' ),
						esc_html__( 'Clear', 'woocommerce' )
					)
				)
			)
		);
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

		if ( $product instanceof \WC_Product && $product->is_type( 'variable' ) ) {
			return $this->render_variation_form( $product, $attributes );
		}

		return '';
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
