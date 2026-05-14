<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\Utils as AddToCartWithOptionsUtils;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;
use WP_Block;

/**
 * Block type for variation selector item in add to cart with options.
 * It's responsible to render each child attribute in a form of a list item.
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
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ): string {
		global $product;

		if ( ! $product instanceof \WC_Product_Variable ) {
			return '';
		}

		$content = '';

		$product_attributes = $product->get_variation_attributes();

		foreach ( $product_attributes as $product_attribute_name => $product_attribute_terms ) {
			$content .= $this->get_product_row( $product_attribute_name, $product_attribute_terms, $block, $attributes );
		}

		return $content;
	}

	/**
	 * Get product row HTML.
	 *
	 * @param string   $attribute_name Product Attribute Name.
	 * @param array    $product_attribute_terms Product Attribute Terms.
	 * @param WP_Block $block The Block.
	 * @param array    $attributes Template block attributes (displayStyle, autoselect, etc.).
	 * @return string Row HTML
	 */
	private function get_product_row( $attribute_name, $product_attribute_terms, $block, $attributes ): string {
		global $product;

		$attribute_terms    = $this->get_terms( $attribute_name, $product_attribute_terms );
		$product_variations = $product->get_available_variations( 'objects' );

		// Filter out terms which are not available in any product variation.
		$attribute_terms = array_filter(
			$attribute_terms,
			function ( $term ) use ( $product_variations, $attribute_name ) {
				foreach ( $product_variations as $variation ) {
					$attributes_var = $variation->get_variation_attributes();
					if (
						$term['value'] === $attributes_var[ wc_variation_attribute_name( $attribute_name ) ] ||
						'' === $attributes_var[ wc_variation_attribute_name( $attribute_name ) ]
					) {
						return true;
					}
				}
			}
		);

		if ( empty( $attribute_terms ) ) {
			return '';
		}

		$row_context = array(
			'woocommerce/attributeId'    => 'wc_product_attribute_' . uniqid(),
			'woocommerce/attributeName'  => $attribute_name,
			'woocommerce/attributeTerms' => $attribute_terms,
		);

		$parsed = $block->parsed_block;
		if ( ! is_array( $parsed ) ) {
			return AddToCartWithOptionsUtils::render_block_with_context( $block, $row_context );
		}

		$merged_attrs            = $this->merge_variation_template_attributes( $attributes, $parsed );
		$stripped                = $parsed;
		$stripped['innerBlocks'] = $this->strip_legacy_attribute_options_from_tree(
			$parsed['innerBlocks'] ?? array(),
			$merged_attrs
		);
		$stripped['attrs']       = $merged_attrs;

		$group_block = $this->find_first_block_by_name( $stripped['innerBlocks'], 'core/group' );
		if ( $group_block ) {
			$display_style = $this->resolve_display_style( $merged_attrs );
			return $this->render_core_group_row( $group_block, $row_context, $merged_attrs, $display_style );
		}

		return ( new WP_Block( $stripped, $row_context ) )->render( array( 'dynamic' => false ) );
	}

	/**
	 * @param array<string, mixed> $render_attributes Attributes from render().
	 * @param array<string, mixed> $parsed_block Parsed block for the template.
	 * @return array<string, mixed>
	 */
	private function merge_variation_template_attributes( array $render_attributes, array $parsed_block ): array {
		return array_merge(
			array(
				'displayStyle'             => 'woocommerce/product-filter-chips',
				'autoselect'               => false,
				'disabledAttributesAction' => 'disable',
			),
			$parsed_block['attrs'] ?? array(),
			$render_attributes
		);
	}

	/**
	 * Removes legacy attribute-options wrapper blocks and merges their attributes.
	 *
	 * @param array<int, array<string, mixed>> $blocks Parsed inner blocks.
	 * @param array<string, mixed>             $merged_attrs Merged template attributes (updated by reference when legacy blocks are found).
	 * @return array<int, array<string, mixed>>
	 */
	private function strip_legacy_attribute_options_from_tree( array $blocks, array &$merged_attrs ): array {
		$out = array();
		foreach ( $blocks as $block ) {
			$name = $block['blockName'] ?? '';
			if ( 'woocommerce/add-to-cart-with-options-variation-selector-attribute-options' === $name ) {
				foreach ( array( 'displayStyle', 'autoselect', 'disabledAttributesAction', 'optionStyle' ) as $key ) {
					if ( array_key_exists( $key, $block['attrs'] ?? array() ) ) {
						$merged_attrs[ $key ] = $block['attrs'][ $key ];
					}
				}
				foreach ( $this->strip_legacy_attribute_options_from_tree( $block['innerBlocks'] ?? array(), $merged_attrs ) as $inner ) {
					$out[] = $inner;
				}
				continue;
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = $this->strip_legacy_attribute_options_from_tree( $block['innerBlocks'], $merged_attrs );
			}
			$out[] = $block;
		}
		return $out;
	}

	/**
	 * @param array<int, array<string, mixed>> $blocks Parsed inner blocks.
	 * @param string                           $needle Block name.
	 * @return array<string, mixed>|null
	 */
	private function find_first_block_by_name( array $blocks, string $needle ): ?array {
		foreach ( $blocks as $block ) {
			if ( ( $block['blockName'] ?? '' ) === $needle ) {
				return $block;
			}
		}
		return null;
	}

	/**
	 * @param array<string, mixed> $merged_attrs Merged template attributes.
	 */
	private function resolve_display_style( array $merged_attrs ): string {
		if ( array_key_exists( 'displayStyle', $merged_attrs ) ) {
			return (string) $merged_attrs['displayStyle'];
		}
		if ( array_key_exists( 'optionStyle', $merged_attrs ) ) {
			return 'dropdown' === $merged_attrs['optionStyle']
				? 'woocommerce/product-filter-dropdown'
				: 'woocommerce/product-filter-chips';
		}
		return 'woocommerce/product-filter-chips';
	}

	/**
	 * Renders a core/group row using WordPress block rendering so layout, className, and innerContent
	 * match the editor. Chips/dropdown output is wrapped for add-to-cart-with-options interactivity.
	 *
	 * @param array<string, mixed> $group_node Parsed core/group block.
	 * @param array<string, mixed> $row_context Row context (attribute id, name, terms).
	 * @param array<string, mixed> $merged_attrs Merged template attributes.
	 * @param string               $display_style Resolved display style block name.
	 */
	private function render_core_group_row( array $group_node, array $row_context, array $merged_attrs, string $display_style ): string {
		$selectable_items_context = $this->build_selectable_items_context_for_variation_row( $row_context );

		$merged_context = array_merge(
			$row_context,
			array(
				'woocommerceSelectableItems' => $selectable_items_context,
			)
		);

		$wrap_chips_or_dropdown = function ( string $block_content, array $parsed_block ) use ( $merged_attrs, $row_context, $display_style ): string {
			$name = $parsed_block['blockName'] ?? '';
			if ( 'woocommerce/product-filter-chips' !== $name && 'woocommerce/product-filter-dropdown' !== $name ) {
				return $block_content;
			}

			return $this->wrap_rendered_variation_style_block_markup( $block_content, $merged_attrs, $row_context, $display_style );
		};

		add_filter( 'render_block', $wrap_chips_or_dropdown, 10, 2 );

		try {
			return ( new WP_Block( $group_node, $merged_context ) )->render();
		} finally {
			remove_filter( 'render_block', $wrap_chips_or_dropdown, 10 );
		}
	}

	/**
	 * Context passed to product-filter-chips / product-filter-dropdown when rendering a variation row.
	 *
	 * @param array<string, mixed> $row_context Row context (attribute id, name, terms).
	 * @return array<string, mixed>
	 */
	private function build_selectable_items_context_for_variation_row( array $row_context ): array {
		$attribute_id    = $row_context['woocommerce/attributeId'];
		$attribute_slug  = wc_variation_attribute_name( $row_context['woocommerce/attributeName'] );
		$attribute_terms = $row_context['woocommerce/attributeTerms'];

		$variation_items = $this->build_variation_selectable_items( $attribute_slug, $attribute_terms );

		return array(
			'items'           => $variation_items,
			'selectionMode'   => 'single',
			'storeNamespace'  => 'woocommerce/add-to-cart-with-options',
			'groupLabel'      => wc_attribute_label(
				$row_context['woocommerce/attributeName']
			),
			'selectElementId' => $attribute_id,
		);
	}

	/**
	 * Outer interactivity wrapper for chips/dropdown markup already rendered by those blocks.
	 *
	 * @param string               $inner_html Rendered chips or dropdown HTML.
	 * @param array<string, mixed> $merged_attrs Merged template attributes.
	 * @param array<string, mixed> $row_context Row context.
	 * @param string               $display_style Resolved display style block name.
	 */
	private function wrap_rendered_variation_style_block_markup( string $inner_html, array $merged_attrs, array $row_context, string $display_style ): string {
		$attribute_id    = $row_context['woocommerce/attributeId'];
		$attribute_slug  = wc_variation_attribute_name( $row_context['woocommerce/attributeName'] );
		$attribute_terms = $row_context['woocommerce/attributeTerms'];
		$autoselect      = $merged_attrs['autoselect'] ?? false;
		$disabled_action = $merged_attrs['disabledAttributesAction'] ?? 'disable';

		$variation_items  = $this->build_variation_selectable_items( $attribute_slug, $attribute_terms );
		$default_selected = $this->get_default_selected_attribute( $attribute_slug, $attribute_terms );

		$interactive_context = array(
			'name'                      => wc_attribute_label( $row_context['woocommerce/attributeName'] ),
			'variationAttributeOptions' => $variation_items,
			'selectedValue'             => $default_selected,
			'autoselect'                => $autoselect,
			'disabledAttributesAction'  => $disabled_action,
		);

		$interactive_attributes = array(
			'data-wp-interactive' => 'woocommerce/add-to-cart-with-options',
			'data-wp-context'     => (string) wp_json_encode(
				$interactive_context,
				JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
			),
			'data-wp-init'        => 'callbacks.setDefaultSelectedAttribute',
		);

		if ( 'woocommerce/product-filter-dropdown' !== $display_style ) {
			$interactive_attributes['role']            = 'radiogroup';
			$interactive_attributes['id']              = $attribute_id;
			$interactive_attributes['aria-labelledby'] = $attribute_id . '_label';
		}

		return sprintf(
			'<div %s>%s</div>',
			get_block_wrapper_attributes( $interactive_attributes ),
			$inner_html
		);
	}

	/**
	 * Get the default selected attribute.
	 *
	 * @param string $attribute_slug The attribute's slug.
	 * @param array  $attribute_terms The attribute's terms.
	 * @return string|null The default selected attribute.
	 */
	private function get_default_selected_attribute( $attribute_slug, $attribute_terms ) {
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
	 * @param string $attribute_slug Attribute slug.
	 * @param array  $attribute_terms Terms from context.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_variation_selectable_items( string $attribute_slug, array $attribute_terms ): array {
		$id_prefix = sanitize_title( $attribute_slug );
		$items     = array();

		foreach ( $attribute_terms as $attribute_term ) {
			if ( ! is_array( $attribute_term ) || ! isset( $attribute_term['value'], $attribute_term['label'] ) ) {
				continue;
			}
			$value   = (string) $attribute_term['value'];
			$slug    = sanitize_title( $value );
			$items[] = array(
				'id'        => $id_prefix . '-' . $slug,
				'label'     => (string) $attribute_term['label'],
				'value'     => $value,
				'ariaLabel' => (string) $attribute_term['label'],
			);
		}

		return $items;
	}

	/**
	 * Get product attributes terms.
	 *
	 * @param string $attribute_name Product Attribute Name.
	 * @param array  $attribute_terms Product Attribute Terms.
	 * @return array[] Array of term data with structure:
	 *                 [
	 *                     'label'      => (string) Display label for the term.
	 *                     'value'      => (string) Internal value/slug for the term.
	 *                     'isSelected' => (bool)   Whether this term is the default selection.
	 *                 ]
	 */
	protected function get_terms( $attribute_name, $attribute_terms ) {
		global $product;

		$is_taxonomy = taxonomy_exists( $attribute_name );

		$selected_attribute = $product->get_variation_default_attribute( $attribute_name );

		if ( $is_taxonomy ) {
			$items = array_map(
				function ( $term ) use ( $attribute_name, $product, $selected_attribute ) {
					return array(
						'value'      => $term->slug,
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
							$term->name,
							$term,
							$attribute_name,
							$product
						),
						'isSelected' => $selected_attribute === $term->slug,
					);
				},
				wc_get_product_terms( $product->get_id(), $attribute_name, array( 'fields' => 'all' ) ),
			);
		} else {
			$items = array_map(
				function ( $term ) use ( $attribute_name, $product, $selected_attribute ) {
					return array(
						'value'      => $term,
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
							$term,
							null,
							$attribute_name,
							$product
						),
						'isSelected' => $selected_attribute === $term,
					);
				},
				$attribute_terms,
			);
		}

		return $items;
	}
}
