<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;
use Automattic\WooCommerce\Internal\ProductAttributes\VisualAttributeTermMeta;
use WP_Block;

/**
 * Block type for Variation Selector in the Add to Cart + Options block.
 */
class VariationSelectorAttribute extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-variation-selector-attribute';

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 * @return void
	 */
	protected function enqueue_data( array $attributes = array() ): void {
		parent::enqueue_data( $attributes );

		if ( is_admin() ) {
			$this->asset_data_registry->add(
				'experimentalVisualAttributes',
				array_key_exists( 'wc-visual', wc_get_attribute_types() )
			);
		}
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		global $product;

		if ( ! $product instanceof \WC_Product_Variable ) {
			return '';
		}

		$content = '';

		$product_attributes            = $product->get_variation_attributes();
		$available_values_by_attribute = $this->get_available_variation_values_by_attribute_slug();

		foreach ( $product_attributes as $product_attribute_name => $product_attribute_terms ) {
			$content .= $this->render_attribute_row( $product_attribute_name, $product_attribute_terms, $block, $attributes, $available_values_by_attribute );
		}

		return $content;
	}

	/**
	 * Get attribute row HTML.
	 *
	 * @param string   $attribute_name Product Attribute Name.
	 * @param array    $product_attribute_terms Product Attribute Terms.
	 * @param WP_Block $block The Block.
	 * @param array    $attributes Template block attributes (displayStyle, autoselect, etc.).
	 * @param array    $available_values_by_attribute Variation values keyed by attribute slug.
	 * @return string Row HTML
	 */
	private function render_attribute_row( string $attribute_name, array $product_attribute_terms, WP_Block $block, array $attributes, array $available_values_by_attribute ): string {
		$inner_blocks = $block->parsed_block['innerBlocks'] ?? array();

		if ( empty( $inner_blocks ) ) {
			return '';
		}

		$attribute_slug  = wc_variation_attribute_name( $attribute_name );
		$attribute_terms = $this->get_filtered_attribute_terms(
			$attribute_name,
			$product_attribute_terms,
			$available_values_by_attribute[ $attribute_slug ] ?? array()
		);

		if ( empty( $attribute_terms ) ) {
			return '';
		}

		$default_selected = $this->get_default_selected_attribute( $attribute_slug, $attribute_terms );
		$variation_items  = $this->build_variation_selectable_items( $attribute_name, $attribute_slug, $attribute_terms, $default_selected );
		$attribute_label  = wc_attribute_label( $attribute_name );
		$attribute_id     = 'wc_product_attribute_' . uniqid();
		$context          = array(
			'woocommerce/attributeId'     => $attribute_id,
			'woocommerce/attributeName'   => $attribute_name,
			'woocommerce/attributeTerms'  => $attribute_terms,
			'woocommerce/selectableItems' => array(
				'items'          => $variation_items,
				'selectionMode'  => 'single',
				'storeNamespace' => 'woocommerce/add-to-cart-with-options',
				'groupLabel'     => $attribute_label,
			),
		);

		$inner_html = '';

		foreach ( $inner_blocks as $inner_block ) {
			$inner_block = $this->replace_legacy_attribute_options_block( $inner_block, $attributes );
			$inner_html .= ( new WP_Block( $inner_block, $context ) )->render();
		}

		$interactive_context = array(
			'name'                      => $attribute_label,
			'variationAttributeOptions' => $variation_items,
			'selectedValue'             => $default_selected,
			'autoselect'                => $attributes['autoselect'] ?? false,
			'disabledAttributesAction'  => $attributes['disabledAttributesAction'] ?? 'disable',
		);

		$interactive_attributes = array(
			'data-wp-interactive' => 'woocommerce/add-to-cart-with-options',
			'data-wp-init'        => 'callbacks.setDefaultSelectedAttribute',
		);

		// Hidden input for legacy form POST submissions (page refresh). Chips and
		// dropdown UI elements do not include name="attribute_*" fields.
		$hidden_attribute_input = sprintf(
			'<input type="hidden" name="%1$s" value="%2$s" data-wp-bind--value="context.selectedValue" />',
			esc_attr( $attribute_slug ),
			esc_attr( $default_selected ?? '' )
		);

		return sprintf(
			'<div %s %s>%s%s</div>',
			get_block_wrapper_attributes( $interactive_attributes ),
			wp_interactivity_data_wp_context( $interactive_context ),
			$inner_html,
			$hidden_attribute_input
		);
	}

	/**
	 * Replace legacy Attribute Options block and apply its settings to the parent attributes.
	 *
	 * @param array $inner_block  The inner block to replace.
	 * @param array $attributes   Parent block attributes, updated when attributes in the legacy Attribute Options block are found.
	 * @return array The replaced inner block.
	 */
	private function replace_legacy_attribute_options_block( array $inner_block, array &$attributes ): array {
		if ( 'woocommerce/add-to-cart-with-options-variation-selector-attribute-options' === $inner_block['blockName'] ) {
			$legacy_attrs = $inner_block['attrs'] ?? array();

			if ( array_key_exists( 'autoselect', $legacy_attrs ) && true === $legacy_attrs['autoselect'] ) {
				$attributes['autoselect'] = true;
			}

			if ( array_key_exists( 'disabledAttributesAction', $legacy_attrs ) && 'hide' === $legacy_attrs['disabledAttributesAction'] ) {
				$attributes['disabledAttributesAction'] = 'hide';
			}

			if ( array_key_exists( 'optionStyle', $legacy_attrs ) && 'dropdown' === $legacy_attrs['optionStyle'] ) {
				$attributes['displayStyle'] = 'woocommerce/dropdown';
			}

			return array(
				'blockName'    => 'woocommerce/dropdown' === $attributes['displayStyle'] ? 'woocommerce/dropdown' : 'woocommerce/product-filter-chips',
				'attrs'        => array(),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			);
		} elseif ( isset( $inner_block['innerBlocks'] ) && is_array( $inner_block['innerBlocks'] ) && ! empty( $inner_block['innerBlocks'] ) ) {
			foreach ( $inner_block['innerBlocks'] as $key => $child_inner_block ) {
				$inner_block['innerBlocks'][ $key ] = $this->replace_legacy_attribute_options_block( $child_inner_block, $attributes );
			}
		}

		return $inner_block;
	}

	/**
	 * Build filtered attribute term options for the variation selector.
	 *
	 * @param string $attribute_name Product attribute name.
	 * @param array  $product_attribute_terms Custom attribute terms when not a taxonomy.
	 * @param array  $available_values Available variation values for this attribute (value => true).
	 * @return array Attribute terms, or empty string when none match.
	 */
	private function get_filtered_attribute_terms( string $attribute_name, array $product_attribute_terms, array $available_values ) {
		global $product;

		$selected_attribute = $product->get_variation_default_attribute( $attribute_name );
		$terms              = taxonomy_exists( $attribute_name )
			? wc_get_product_terms( $product->get_id(), $attribute_name, array( 'fields' => 'all' ) )
			: $product_attribute_terms;

		$attribute_terms = array();
		if ( ! empty( $available_values ) ) {
			$allows_any_value = isset( $available_values[''] );

			foreach ( $terms as $term ) {
				$option = $this->map_term_to_option( $term, $attribute_name, $product, $selected_attribute );

				if ( ! isset( $option['value'] ) ) {
					continue;
				}

				if ( $allows_any_value || isset( $available_values[ $option['value'] ] ) ) {
					$attribute_terms[] = $option;
				}
			}
		}

		return $attribute_terms;
	}

	/**
	 * Build a lookup of attribute values used by available variations.
	 *
	 * @return array Map of attribute slug to set of values (keys are values).
	 */
	private function get_available_variation_values_by_attribute_slug(): array {
		global $product;

		$product_variations = $product->get_available_variations( 'objects' );
		$available_by_slug  = array();

		foreach ( $product_variations as $variation ) {
			foreach ( $variation->get_variation_attributes() as $attribute_slug => $value ) {
				$available_by_slug[ $attribute_slug ][ $value ] = true;
			}
		}

		return $available_by_slug;
	}

	/**
	 * Get the default selected attribute.
	 *
	 * @param string $attribute_slug The attribute's slug.
	 * @param array  $attribute_terms The attribute's terms.
	 * @return string|null The default selected attribute.
	 */
	private function get_default_selected_attribute( string $attribute_slug, array $attribute_terms ): ?string {
		if ( isset( $_GET[ $attribute_slug ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$raw = wp_unslash( $_GET[ $attribute_slug ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( is_string( $raw ) ) {
				$attribute_slug_from_request = sanitize_title( $raw );
				foreach ( $attribute_terms as $attribute_term ) {
					if ( sanitize_title( $attribute_term['value'] ) === $attribute_slug_from_request ) {
						return $attribute_term['value'];
					}
				}
			}
		} else {
			foreach ( $attribute_terms as $attribute_term ) {
				if ( $attribute_term['isSelected'] ) {
					return $attribute_term['value'];
				}
			}
		}

		return null;
	}

	/**
	 * Build selectable items for the inner block protocol and client context.
	 *
	 * @param string      $attribute_name Product attribute name.
	 * @param string      $attribute_slug Attribute slug.
	 * @param array       $attribute_terms Terms from context.
	 * @param string|null $default_selected Default selected attribute value.
	 * @return array
	 */
	private function build_variation_selectable_items( string $attribute_name, string $attribute_slug, array $attribute_terms, ?string $default_selected ): array {
		$id_prefix    = sanitize_title( $attribute_slug );
		$items        = array();
		$term_visuals = VisualAttributeTermMeta::is_visual_attribute_taxonomy( $attribute_name )
			? VisualAttributeTermMeta::get_term_visuals( wp_list_pluck( $attribute_terms, 'term_id' ) )
			: array();

		foreach ( $attribute_terms as $attribute_term ) {
			if ( ! is_array( $attribute_term ) || ! isset( $attribute_term['value'], $attribute_term['label'] ) ) {
				continue;
			}
			$value = (string) $attribute_term['value'];
			$slug  = sanitize_title( $value );
			$item  = array(
				'id'        => $id_prefix . '-' . $slug,
				'label'     => (string) $attribute_term['label'],
				'value'     => $value,
				'ariaLabel' => (string) $attribute_term['label'],
				'selected'  => $default_selected === $value,
			);

			if ( isset( $attribute_term['term_id'], $term_visuals[ $attribute_term['term_id'] ] ) ) {
				$item['visual'] = $term_visuals[ $attribute_term['term_id'] ];
			}

			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Map a taxonomy term or custom attribute option to the variation row option shape.
	 *
	 * @param \WP_Term|string $term Term object for taxonomies, option string for custom attributes.
	 * @param string          $attribute_name Name of the attribute.
	 * @param \WC_Product     $product Product object.
	 * @param string          $selected_attribute Default selected attribute value.
	 * @return array
	 */
	private function map_term_to_option( $term, string $attribute_name, \WC_Product $product, string $selected_attribute ): array {
		if ( $term instanceof \WP_Term ) {
			$value       = $term->slug;
			$label       = $term->name;
			$filter_item = $term;
		} elseif ( is_string( $term ) ) {
			$value       = $term;
			$label       = $term;
			$filter_item = null;
		} else {
			return array();
		}

		$option = array(
			'value'      => $value,
			/**
			 * Filter the variation option name.
			 *
			 * @since 9.7.0
			 *
			 * @param string                $option_label   The option label.
			 * @param \WP_Term|string|null $item            Term object for taxonomies, option string for custom attributes.
			 * @param string                $attribute_name Name of the attribute.
			 * @param \WC_Product           $product        Product object.
			 */
			'label'      => apply_filters(
				'woocommerce_variation_option_name',
				$label,
				$filter_item,
				$attribute_name,
				$product
			),
			'isSelected' => $selected_attribute === $value,
		);

		if ( $term instanceof \WP_Term ) {
			$option['term_id'] = $term->term_id;
		}

		return $option;
	}
}
