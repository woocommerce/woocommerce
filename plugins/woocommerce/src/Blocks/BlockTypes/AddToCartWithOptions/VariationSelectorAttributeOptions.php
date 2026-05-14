<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use WP_Block;

/**
 * Block type for variation selector attribute options in add to cart with options.
 * It's responsible to render the attribute options.
 */
class VariationSelectorAttributeOptions extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-variation-selector-attribute-options';

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content Block content.
	 * @param \WP_Block $block Block instance.
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ): string {
		if (
			! isset(
				$block->context['woocommerce/attributeName'],
				$block->context['woocommerce/attributeId'],
				$block->context['woocommerce/attributeTerms']
			)
		) {
			return '';
		}

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );

		if ( array_key_exists( 'displayStyle', $attributes ) ) {
			$display_style = $attributes['displayStyle'];
		} elseif ( array_key_exists( 'optionStyle', $attributes ) ) {
			$display_style = 'dropdown' === $attributes['optionStyle'] ? 'woocommerce/product-filter-dropdown' : 'woocommerce/product-filter-chips';
		} else {
			$display_style = 'woocommerce/product-filter-chips';
		}

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => $classes_and_styles['classes'],
				'style' => $classes_and_styles['styles'],
			)
		);

		$content = $this->render_attribute_options( $attributes, $block, $display_style );

		return sprintf(
			'<div %s>%s</div>',
			$wrapper_attributes,
			$content
		);
	}

	/**
	 * Get the normalized version of the attributes.
	 *
	 * @param array $attributes         The element's attributes.
	 * @param array $default_attributes The element's default attributes.
	 * @return string The HTML element's attributes.
	 */
	public static function get_normalized_attributes( $attributes, $default_attributes = array() ) {
		$normalized_attributes = array();

		$merged_attributes = array_merge( $default_attributes, $attributes );

		foreach ( $merged_attributes as $key => $value ) {
			if ( is_null( $value ) ) {
				continue;
			}
			if ( is_array( $value ) || is_object( $value ) ) {
				$value = wp_json_encode(
					$value,
					JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
				);
			}
			$normalized_attributes[] = sprintf( '%s="%s"', esc_attr( $key ), esc_attr( $value ) );
		}

		return implode( ' ', $normalized_attributes );
	}

	/**
	 * Get the default selected attribute.
	 *
	 * @param string $attribute_slug The attribute's slug.
	 * @param array  $attribute_terms The attribute's terms.
	 * @return string|null The default selected attribute.
	 */
	protected function get_default_selected_attribute( $attribute_slug, $attribute_terms ) {
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
	protected function build_variation_selectable_items( string $attribute_slug, array $attribute_terms ): array {
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
	 * Render attribute options using selectable inner blocks (chips / dropdown).
	 *
	 * @param array     $attributes Block attributes.
	 * @param \WP_Block $block Block instance.
	 * @param string    $display_style Resolved option style.
	 * @return string
	 */
	protected function render_attribute_options( array $attributes, WP_Block $block, string $display_style ): string {
		$attribute_id    = $block->context['woocommerce/attributeId'];
		$attribute_slug  = wc_variation_attribute_name( $block->context['woocommerce/attributeName'] );
		$attribute_terms = $block->context['woocommerce/attributeTerms'];
		$autoselect      = $attributes['autoselect'] ?? false;
		$disabled_action = $attributes['disabledAttributesAction'] ?? 'disable';

		$variation_items = $this->build_variation_selectable_items( $attribute_slug, $attribute_terms );

		$default_selected = $this->get_default_selected_attribute( $attribute_slug, $attribute_terms );

		$selectable_items_context = array(
			'items'           => $variation_items,
			'selectionMode'   => 'single',
			'storeNamespace'  => 'woocommerce/add-to-cart-with-options',
			'groupLabel'      => wc_attribute_label(
				$block->context['woocommerce/attributeName']
			),
			'selectElementId' => $attribute_id,
		);

		$merged_context = array_merge(
			$block->context,
			array(
				'woocommerceSelectableItems' => $selectable_items_context,
			)
		);

		$inner_blocks = $block->parsed_block['innerBlocks'] ?? array();
		if ( empty( $inner_blocks ) ) {
			$inner_blocks = array(
				array(
					'blockName'    => $display_style,
					'attrs'        => array(),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(),
				),
			);
		}

		$inner_html = '';
		foreach ( $inner_blocks as $inner_block ) {
			if ( ! is_array( $inner_block ) || empty( $inner_block['blockName'] ) ) {
				continue;
			}
			$inner_html .= ( new WP_Block( $inner_block, $merged_context ) )->render();
		}

		$interactive_context = array(
			'name'                      => wc_attribute_label( $block->context['woocommerce/attributeName'] ),
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
}
