<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use WC_Product_Variable;
use WC_Product_Variation;
use WP_Block;
use WP_HTML_Tag_Processor;
use WP_Term;

/**
 * Product Image with Color Swatches block.
 */
class ProductImageWithColorSwatches extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	private const STORE_NAMESPACE = 'woocommerce/product-image-with-color-swatches';

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-image-with-color-swatches';

	/**
	 * Cached map of term ID to color value, keyed by attribute taxonomy.
	 *
	 * @var array<string, array<int, string>>
	 */
	private $term_colors = array();

	/**
	 * Get block context consumed by the block.
	 *
	 * @return array
	 *
	 * @since 10.9.0
	 */
	protected function get_block_type_uses_context() {
		return array( 'postId', 'query', 'queryId' );
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes Any attributes that currently are available from the block.
	 * @return void
	 *
	 * @since 10.9.0
	 */
	protected function enqueue_data( array $attributes = array() ) {
		parent::enqueue_data( $attributes );

		if ( is_admin() ) {
			$this->asset_data_registry->add( 'productImageWithColorSwatchesTermColors', $this->get_visual_attribute_term_colors() );
		}
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block output.
	 *
	 * @since 10.9.0
	 */
	protected function render( $attributes, $content, $block ) {
		$post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;
		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return '';
		}

		$swatch_data  = $product instanceof WC_Product_Variable ? $this->get_swatch_data( $product, $block ) : $this->get_empty_swatch_data();
		$items        = $swatch_data['items'];
		$inner_markup = $this->render_inner_blocks( $block, $swatch_data );

		if ( '' === $inner_markup ) {
			return '';
		}

		$default_image = $this->get_product_image_data_from_html( $inner_markup );

		if ( ! empty( $items ) && ! empty( $default_image ) ) {
			$inner_markup = $this->inject_image_directives( $inner_markup );
		}

		$wrapper_attributes = array(
			'class' => 'wc-block-product-image-with-color-swatches',
		);

		if ( ! empty( $items ) ) {
			$wrapper_attributes['data-wp-interactive'] = self::STORE_NAMESPACE;
			$wrapper_attributes['data-wp-context']     = (string) wp_json_encode(
				array(
					'items'          => $items,
					'selectedItemId' => null,
					'defaultImage'   => ! empty( $default_image ) ? $default_image : $this->get_empty_image_data(),
				),
				JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
			);
		}

		return sprintf(
			'<div %1$s>%2$s</div>',
			get_block_wrapper_attributes( $wrapper_attributes ),
			$inner_markup
		);
	}

	/**
	 * Get empty swatch data.
	 *
	 * @return array{items: array<int, array<string, mixed>>, groupLabel: string}
	 */
	private function get_empty_swatch_data(): array {
		return array(
			'items'      => array(),
			'groupLabel' => __( 'Color', 'woocommerce' ),
		);
	}

	/**
	 * Render inner blocks with selectable items context.
	 *
	 * @param WP_Block $block       Block instance.
	 * @param array    $swatch_data Swatch data.
	 * @return string Rendered inner blocks.
	 */
	private function render_inner_blocks( WP_Block $block, array $swatch_data ): string {
		$inner_blocks = $block->parsed_block['innerBlocks'] ?? array();

		if ( empty( $inner_blocks ) ) {
			return '';
		}

		$items              = $swatch_data['items'];
		$selectable_context = array(
			'items'          => $items,
			'selectionMode'  => 'single',
			'storeNamespace' => self::STORE_NAMESPACE,
			'groupLabel'     => $swatch_data['groupLabel'],
		);
		$context            = array_merge(
			(array) $block->context,
			array(
				'woocommerce/selectableItems' => $selectable_context,
			)
		);
		$inner_markup       = '';

		foreach ( $inner_blocks as $inner_block ) {
			if ( empty( $items ) && 'woocommerce/product-filter-chips' === ( $inner_block['blockName'] ?? '' ) ) {
				continue;
			}

			$inner_markup .= ( new WP_Block( $inner_block, $context ) )->render();
		}

		return $inner_markup;
	}

	/**
	 * Build swatch data for the first color-like variation attribute.
	 *
	 * @param WC_Product_Variable $product Product instance.
	 * @param WP_Block            $block   Block instance.
	 * @return array{items: array<int, array<string, mixed>>, groupLabel: string}
	 */
	private function get_swatch_data( WC_Product_Variable $product, WP_Block $block ): array {
		$attribute_name = $this->get_color_variation_attribute_name( $product );

		if ( null === $attribute_name ) {
			return $this->get_empty_swatch_data();
		}

		$image_ids_by_value = $this->get_first_variation_image_ids_by_attribute_value( $product, $attribute_name );

		if ( empty( $image_ids_by_value ) ) {
			return array(
				'items'      => array(),
				'groupLabel' => wc_attribute_label( $attribute_name ),
			);
		}

		$product_image_attributes = $this->get_product_image_block_attributes( $block->parsed_block['innerBlocks'] ?? array() );
		$image_size               = $this->get_image_size( $product_image_attributes );
		$image_style              = $this->get_image_style( $product_image_attributes );
		$terms_by_value           = $this->get_attribute_terms_by_value( $product, $attribute_name );
		$term_colors              = $this->get_visual_attribute_term_colors( $attribute_name );
		$variation_attributes     = $product->get_variation_attributes();
		$attribute_values         = $variation_attributes[ $attribute_name ] ?? array_keys( $image_ids_by_value );
		$type                     = 'attribute/' . $this->get_attribute_key( $attribute_name );
		$items                    = array();

		foreach ( $attribute_values as $attribute_value ) {
			$value = (string) $attribute_value;

			if ( '' === $value || empty( $image_ids_by_value[ $value ] ) ) {
				continue;
			}

			$image = $this->get_image_data( (int) $image_ids_by_value[ $value ], $product, $image_size, $image_style );

			if ( empty( $image ) ) {
				continue;
			}

			$term  = $terms_by_value[ $value ] ?? null;
			$label = $term instanceof WP_Term ? $term->name : $value;
			$item  = array(
				'id'        => 'product-image-swatch-' . sanitize_title( $attribute_name ) . '-' . sanitize_title( $value ),
				'label'     => $label,
				'ariaLabel' => sprintf(
					/* translators: %s: color name */
					__( 'Show %s image', 'woocommerce' ),
					$label
				),
				'value'     => $value,
				'type'      => $type,
				'selected'  => false,
				'image'     => $image,
			);

			if ( $term instanceof WP_Term && array_key_exists( $term->term_id, $term_colors ) ) {
				$item['color'] = $term_colors[ $term->term_id ];
			}

			$items[] = $item;
		}

		return array(
			'items'      => $items,
			'groupLabel' => wc_attribute_label( $attribute_name ),
		);
	}

	/**
	 * Get the first visual or color variation attribute name.
	 *
	 * @param WC_Product_Variable $product Product instance.
	 * @return string|null Attribute name.
	 */
	private function get_color_variation_attribute_name( WC_Product_Variable $product ): ?string {
		$variation_attributes = $product->get_variation_attributes();

		if ( empty( $variation_attributes ) ) {
			return null;
		}

		$visual_attributes = $this->get_visual_attribute_taxonomies();

		foreach ( array_keys( $variation_attributes ) as $attribute_name ) {
			if ( isset( $visual_attributes[ $attribute_name ] ) ) {
				return $attribute_name;
			}
		}

		foreach ( array_keys( $variation_attributes ) as $attribute_name ) {
			$attribute_key = $this->get_attribute_key( $attribute_name );
			if ( 'color' === $attribute_key || 'colour' === $attribute_key ) {
				return $attribute_name;
			}
		}

		return null;
	}

	/**
	 * Get wc-visual attribute taxonomies.
	 *
	 * @return array<string, true> Attribute taxonomy map.
	 */
	private function get_visual_attribute_taxonomies(): array {
		$attributes = wc_get_attribute_taxonomies();
		$visual     = array();

		foreach ( $attributes as $attribute ) {
			if ( 'wc-visual' === $attribute->attribute_type ) {
				$visual[ wc_attribute_taxonomy_name( $attribute->attribute_name ) ] = true;
			}
		}

		return $visual;
	}

	/**
	 * Get the first variation image ID for each attribute value.
	 *
	 * @param WC_Product_Variable $product        Product instance.
	 * @param string              $attribute_name Attribute name.
	 * @return array<string, int> Image IDs keyed by attribute value.
	 */
	private function get_first_variation_image_ids_by_attribute_value( WC_Product_Variable $product, string $attribute_name ): array {
		$variation_ids = $product->get_children();

		if ( empty( $variation_ids ) ) {
			return array();
		}

		// Prime caches to reduce future queries.
		_prime_post_caches( $variation_ids );

		$attribute_slug               = wc_variation_attribute_name( $attribute_name );
		$candidate_image_ids_by_value = array();

		foreach ( $variation_ids as $variation_id ) {
			$variation = wc_get_product( $variation_id );

			if ( ! $variation instanceof WC_Product_Variation || ! $variation->variation_is_visible() ) {
				continue;
			}

			$variation_attributes = $variation->get_variation_attributes();
			$value                = (string) ( $variation_attributes[ $attribute_slug ] ?? '' );

			if ( '' === $value ) {
				continue;
			}

			$image_id = (int) $variation->get_image_id( 'edit' );
			if ( $image_id > 0 ) {
				$candidate_image_ids_by_value[ $value ][] = $image_id;
			}
		}

		$candidate_image_ids = array();
		foreach ( $candidate_image_ids_by_value as $candidate_ids ) {
			$candidate_image_ids = array_merge( $candidate_image_ids, $candidate_ids );
		}
		$candidate_image_ids = array_values( array_unique( $candidate_image_ids ) );

		if ( ! empty( $candidate_image_ids ) ) {
			// Prime caches to reduce future queries.
			_prime_post_caches( $candidate_image_ids );
		}

		$image_ids_by_value = array();
		foreach ( $candidate_image_ids_by_value as $value => $candidate_ids ) {
			foreach ( $candidate_ids as $image_id ) {
				if ( wp_attachment_is_image( $image_id ) ) {
					$image_ids_by_value[ $value ] = $image_id;
					break;
				}
			}
		}

		return $image_ids_by_value;
	}

	/**
	 * Get taxonomy terms keyed by variation attribute value.
	 *
	 * @param WC_Product_Variable $product        Product instance.
	 * @param string              $attribute_name Attribute taxonomy.
	 * @return array<string, WP_Term> Terms keyed by slug.
	 */
	private function get_attribute_terms_by_value( WC_Product_Variable $product, string $attribute_name ): array {
		if ( ! taxonomy_exists( $attribute_name ) ) {
			return array();
		}

		$terms = wc_get_product_terms( $product->get_id(), $attribute_name, array( 'fields' => 'all' ) );

		if ( ! is_array( $terms ) ) {
			return array();
		}

		$terms_by_value = array();
		foreach ( $terms as $term ) {
			if ( $term instanceof WP_Term ) {
				$terms_by_value[ $term->slug ] = $term;
			}
		}

		return $terms_by_value;
	}

	/**
	 * Get color values for wc-visual attribute terms.
	 *
	 * @param string|null $attribute_name Optional attribute taxonomy name.
	 * @return array<int, string> Map of term ID to hex color.
	 */
	private function get_visual_attribute_term_colors( ?string $attribute_name = null ): array {
		$cache_key = $attribute_name ?? '__all';
		if ( isset( $this->term_colors[ $cache_key ] ) ) {
			return $this->term_colors[ $cache_key ];
		}

		$colors     = array();
		$attributes = wc_get_attribute_taxonomies();

		foreach ( $attributes as $attribute ) {
			$taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );

			if ( 'wc-visual' !== $attribute->attribute_type ) {
				continue;
			}

			if ( $attribute_name && $taxonomy !== $attribute_name ) {
				continue;
			}

			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				)
			);

			if ( is_wp_error( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				if ( $term instanceof WP_Term ) {
					$color                    = sanitize_hex_color( get_term_meta( $term->term_id, 'color', true ) );
					$colors[ $term->term_id ] = $color ? $color : '';
				}
			}
		}

		$this->term_colors[ $cache_key ] = $colors;

		return $this->term_colors[ $cache_key ];
	}

	/**
	 * Get the product image block attributes from inner blocks.
	 *
	 * @param array $inner_blocks Inner blocks.
	 * @return array<string, mixed> Product image block attributes.
	 */
	private function get_product_image_block_attributes( array $inner_blocks ): array {
		foreach ( $inner_blocks as $inner_block ) {
			if ( 'woocommerce/product-image' === ( $inner_block['blockName'] ?? '' ) ) {
				return is_array( $inner_block['attrs'] ?? null ) ? $inner_block['attrs'] : array();
			}

			if ( ! empty( $inner_block['innerBlocks'] ) && is_array( $inner_block['innerBlocks'] ) ) {
				$attributes = $this->get_product_image_block_attributes( $inner_block['innerBlocks'] );

				if ( ! empty( $attributes ) ) {
					return $attributes;
				}
			}
		}

		return array();
	}

	/**
	 * Get the image size for product image block attributes.
	 *
	 * @param array $attributes Product image block attributes.
	 * @return string Image size.
	 */
	private function get_image_size( array $attributes ): string {
		return 'single' === ( $attributes['imageSizing'] ?? 'single' ) ? 'woocommerce_single' : 'woocommerce_thumbnail';
	}

	/**
	 * Get inline image style for product image block attributes.
	 *
	 * @param array $attributes Product image block attributes.
	 * @return string Inline style.
	 */
	private function get_image_style( array $attributes ): string {
		$image_style = '';

		if ( ! empty( $attributes['height'] ) ) {
			$image_style .= sprintf( 'height:%s;', $attributes['height'] );
		}
		if ( ! empty( $attributes['width'] ) ) {
			$image_style .= sprintf( 'width:%s;', $attributes['width'] );
		}
		if ( ! empty( $attributes['scale'] ) ) {
			$image_style .= sprintf( 'object-fit:%s;', $attributes['scale'] );
		}
		if ( ! empty( $attributes['aspectRatio'] ) ) {
			$image_style .= sprintf( 'aspect-ratio:%s;', $attributes['aspectRatio'] );
		}
		if ( ! empty( $attributes['style']['dimensions']['aspectRatio'] ) ) {
			$image_style .= sprintf( 'aspect-ratio:%s;', $attributes['style']['dimensions']['aspectRatio'] );
		}
		if ( ! empty( $attributes['style']['dimensions']['minHeight'] ) ) {
			$image_style .= sprintf( 'min-height:%s;', $attributes['style']['dimensions']['minHeight'] );
		}

		return $image_style;
	}

	/**
	 * Get image data for a variation image.
	 *
	 * @param int                 $image_id    Image ID.
	 * @param WC_Product_Variable $product     Product instance.
	 * @param string              $image_size  Image size.
	 * @param string              $image_style Inline image style.
	 * @return array<string, string|int> Image data.
	 */
	private function get_image_data( int $image_id, WC_Product_Variable $product, string $image_size, string $image_style ): array {
		$alt_text = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
		$attr     = array(
			'alt'           => empty( $alt_text ) ? $product->get_title() : $alt_text,
			'data-image-id' => $image_id,
		);

		if ( '' !== $image_style ) {
			$attr['style'] = $image_style;
		}

		$image = wp_get_attachment_image( $image_id, $image_size, false, $attr );

		if ( ! is_string( $image ) || '' === $image ) {
			return array();
		}

		return $this->get_image_data_from_html( $image );
	}

	/**
	 * Get empty image data.
	 *
	 * @return array<string, string|int> Image data.
	 */
	private function get_empty_image_data(): array {
		return array(
			'id'     => 0,
			'src'    => '',
			'srcset' => '',
			'sizes'  => '',
			'alt'    => '',
			'width'  => '',
			'height' => '',
		);
	}

	/**
	 * Extract product image data from rendered block markup.
	 *
	 * @param string $html Rendered HTML.
	 * @return array<string, string|int> Image data.
	 */
	private function get_product_image_data_from_html( string $html ): array {
		$tags = new WP_HTML_Tag_Processor( $html );

		while ( $tags->next_tag( array( 'tag_name' => 'IMG' ) ) ) {
			if ( 'product-image' === $tags->get_attribute( 'data-testid' ) ) {
				return $this->get_image_data_from_current_tag( $tags );
			}
		}

		return array();
	}

	/**
	 * Extract image data from an image HTML fragment.
	 *
	 * @param string $html Image HTML.
	 * @return array<string, string|int> Image data.
	 */
	private function get_image_data_from_html( string $html ): array {
		$tags = new WP_HTML_Tag_Processor( $html );

		if ( $tags->next_tag( array( 'tag_name' => 'IMG' ) ) ) {
			return $this->get_image_data_from_current_tag( $tags );
		}

		return array();
	}

	/**
	 * Extract image data from the current image tag.
	 *
	 * @param WP_HTML_Tag_Processor $tags Tag processor.
	 * @return array<string, string|int> Image data.
	 */
	private function get_image_data_from_current_tag( WP_HTML_Tag_Processor $tags ): array {
		$attribute_map = array(
			'id'     => 'data-image-id',
			'src'    => 'src',
			'srcset' => 'srcset',
			'sizes'  => 'sizes',
			'alt'    => 'alt',
			'width'  => 'width',
			'height' => 'height',
		);
		$image_data    = array();

		foreach ( $attribute_map as $key => $attribute_name ) {
			$value              = $tags->get_attribute( $attribute_name );
			$image_data[ $key ] = is_string( $value ) ? $value : '';
		}

		$image_data['id'] = (int) $image_data['id'];

		return '' !== $image_data['src'] ? $image_data : array();
	}

	/**
	 * Inject image binding directives into the product image.
	 *
	 * @param string $html Rendered block markup.
	 * @return string Updated markup.
	 */
	private function inject_image_directives( string $html ): string {
		$tags = new WP_HTML_Tag_Processor( $html );

		while ( $tags->next_tag( array( 'tag_name' => 'IMG' ) ) ) {
			if ( 'product-image' !== $tags->get_attribute( 'data-testid' ) ) {
				continue;
			}

			$tags->set_attribute( 'data-wp-bind--src', 'state.currentImage.src' );
			$tags->set_attribute( 'data-wp-bind--srcset', 'state.currentImage.srcset' );
			$tags->set_attribute( 'data-wp-bind--sizes', 'state.currentImage.sizes' );
			$tags->set_attribute( 'data-wp-bind--alt', 'state.currentImage.alt' );
			$tags->set_attribute( 'data-wp-bind--width', 'state.currentImage.width' );
			$tags->set_attribute( 'data-wp-bind--height', 'state.currentImage.height' );
			$tags->set_attribute( 'data-wp-bind--data-image-id', 'state.currentImage.id' );
			break;
		}

		return $tags->get_updated_html();
	}

	/**
	 * Get normalized attribute key without the pa_ prefix.
	 *
	 * @param string $attribute_name Attribute name.
	 * @return string Attribute key.
	 */
	private function get_attribute_key( string $attribute_name ): string {
		$attribute_key = 0 === strpos( $attribute_name, 'pa_' ) ? substr( $attribute_name, 3 ) : $attribute_name;

		return sanitize_title( $attribute_key );
	}
}
