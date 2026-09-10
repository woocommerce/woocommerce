<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * FeaturedCategory class.
 */
class FeaturedCategory extends FeaturedItem {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'featured-category';

	/**
	 * Initialize the block and its term image binding.
	 *
	 * @since 11.2.0
	 */
	protected function initialize(): void {
		parent::initialize();

		add_filter( 'block_bindings_supported_attributes_core/cover', array( $this, 'handle_cover_supported_binding_attributes' ) );

		if ( function_exists( 'register_block_bindings_source' ) ) {
			register_block_bindings_source(
				'woocommerce/term-image',
				array(
					'label'              => __( 'Product category image', 'woocommerce' ),
					'uses_context'       => array( 'termId', 'termTaxonomy', 'taxonomy' ),
					'get_value_callback' => array( $this, 'get_term_image_binding_value' ),
				)
			);
		}
	}

	/**
	 * Allow Cover image attributes to use block bindings.
	 *
	 * @internal
	 *
	 * @param string[] $attributes Supported attribute names.
	 * @return string[]
	 */
	public function handle_cover_supported_binding_attributes( $attributes ) {
		$attributes[] = 'id';
		$attributes[] = 'url';

		return array_values( array_unique( $attributes ) );
	}

	/**
	 * Resolve a product category image for a bound Cover attribute.
	 *
	 * @internal
	 *
	 * @param array     $source_args    Binding source arguments.
	 * @param \WP_Block $block_instance Bound block instance.
	 * @param string    $attribute_name Bound attribute name.
	 * @return int|string|null
	 */
	public function get_term_image_binding_value( $source_args, $block_instance, $attribute_name ) {
		$term_id  = absint( $block_instance->context['termId'] ?? 0 );
		$taxonomy = $block_instance->context['termTaxonomy'] ?? $block_instance->context['taxonomy'] ?? '';

		if ( ! $term_id || 'product_cat' !== $taxonomy ) {
			return null;
		}

		$image_id = absint( get_term_meta( $term_id, 'thumbnail_id', true ) );
		if ( 'id' === $attribute_name ) {
			return $image_id ? $image_id : null;
		}

		if ( 'url' === $attribute_name ) {
			$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : wc_placeholder_img_src();
			return $image_url ? $image_url : null;
		}

		return null;
	}

	/**
	 * Get block attributes.
	 *
	 * @return array
	 */
	protected function get_block_type_attributes() {
		return array_merge(
			parent::get_block_type_attributes(),
			array(
				'textColor'  => $this->get_schema_string(),
				'fontSize'   => $this->get_schema_string(),
				'lineHeight' => $this->get_schema_string(),
				'style'      => array( 'type' => 'object' ),
			)
		);
	}

	/**
	 * Returns the featured category.
	 *
	 * @param array $attributes Block attributes. Default empty array.
	 * @return \WP_Term|null
	 */
	protected function get_item( $attributes ) {
		$id = absint( $attributes['categoryId'] ?? 0 );

		$category = get_term( $id, 'product_cat' );
		if ( ! $category || is_wp_error( $category ) ) {
			return null;
		}

		return $category;
	}

	/**
	 * Adds the category inherited from block context when none is selected.
	 *
	 * @since 11.2.0
	 *
	 * @param array     $attributes Block attributes.
	 * @param \WP_Block $block      Block instance.
	 * @return array
	 */
	private function resolve_context_attributes( $attributes, $block ) {
		if ( empty( $attributes['categoryId'] ) && 'selected' !== ( $attributes['source'] ?? '' ) ) {
			$taxonomy = $block->context['termTaxonomy'] ?? $block->context['taxonomy'] ?? '';
			if ( 'product_cat' === $taxonomy ) {
				$attributes['categoryId'] = absint( $block->context['termId'] ?? 0 );
			}
		}

		return $attributes;
	}

	/**
	 * Render either the legacy Featured Category layout or its Cover layout.
	 *
	 * @since 11.2.0
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	protected function render( $attributes, $content, $block ) {
		$attributes = $this->resolve_context_attributes( $attributes, $block );

		if ( 'cover' !== ( $attributes['layout'] ?? '' ) ) {
			return parent::render( $attributes, $content, $block );
		}

		$category = $this->get_item( $attributes );
		if ( ! $category ) {
			return '';
		}

		$processor = new \WP_HTML_Tag_Processor( $content );
		if ( $processor->next_tag( array( 'class_name' => 'wc-block-featured-category__cover' ) ) ) {
			$processor->add_class( 'wp-block-woocommerce-featured-category' );
		}

		if ( $this->has_bound_cover_image( $block ) && $processor->next_tag( array( 'class_name' => 'wp-block-cover__image-background' ) ) ) {
			$this->update_cover_image_markup( $processor, $category );
		}

		return $this->update_cover_button_markup(
			$processor->get_updated_html(),
			$category,
			$attributes['ariaLabel'] ?? ''
		);
	}

	/**
	 * Whether the Cover child uses the WooCommerce term image binding.
	 *
	 * @param \WP_Block $block Featured Category block instance.
	 * @return bool
	 */
	private function has_bound_cover_image( $block ) {
		$cover_block = $this->find_bound_cover( $block->parsed_block['innerBlocks'] ?? array() );

		return null !== $cover_block;
	}

	/**
	 * Find the marked Cover using the term image binding.
	 *
	 * @param array[] $blocks Parsed blocks.
	 * @return array|null
	 */
	private function find_bound_cover( $blocks ) {
		foreach ( $blocks as $block ) {
			$source  = $block['attrs']['metadata']['bindings']['url']['source'] ?? '';
			$classes = explode( ' ', $block['attrs']['className'] ?? '' );
			if ( 'core/cover' === ( $block['blockName'] ?? '' ) && 'woocommerce/term-image' === $source && in_array( 'wc-block-featured-category__cover', $classes, true ) ) {
				return $block;
			}

			$bound_cover = $this->find_bound_cover( $block['innerBlocks'] ?? array() );
			if ( null !== $bound_cover ) {
				return $bound_cover;
			}
		}

		return null;
	}

	/**
	 * Update a bound Cover image with the current category thumbnail.
	 *
	 * @param \WP_HTML_Tag_Processor $processor HTML processor positioned on the image.
	 * @param \WP_Term               $category  Product category.
	 */
	private function update_cover_image_markup( $processor, $category ): void {
		$image_id  = $this->get_item_image_id( $category );
		$image_src = $image_id ? wp_get_attachment_image_src( $image_id, 'full' ) : false;
		$image_url = $image_src ? $image_src[0] : wc_placeholder_img_src();

		if ( 'IMG' !== $processor->get_tag() ) {
			$style = (string) $processor->get_attribute( 'style' );
			$style = preg_replace( '/(^|;)\s*background-image\s*:[^;]*(?=;|$)/i', '$1', $style );
			$style = trim( (string) $style, " \t\n\r\0\x0B;" );
			$processor->set_attribute( 'style', ( $style ? $style . ';' : '' ) . 'background-image:url("' . esc_url_raw( $image_url ) . '")' );
			return;
		}

		$classes = preg_split( '/\s+/', (string) $processor->get_attribute( 'class' ), -1, PREG_SPLIT_NO_EMPTY );
		$classes = is_array( $classes ) ? $classes : array();
		foreach ( $classes as $class_name ) {
			if ( preg_match( '/^wp-image-\d+$/', $class_name ) ) {
				$processor->remove_class( $class_name );
			}
		}

		if ( ! $image_src ) {
			$processor->set_attribute( 'src', $image_url );
			$processor->remove_attribute( 'srcset' );
			$processor->remove_attribute( 'sizes' );
			$processor->remove_attribute( 'width' );
			$processor->remove_attribute( 'height' );
			return;
		}

		$processor->set_attribute( 'src', $image_src[0] );
		$processor->set_attribute( 'width', (string) $image_src[1] );
		$processor->set_attribute( 'height', (string) $image_src[2] );
		$processor->add_class( 'wp-image-' . $image_id );

		$srcset = wp_get_attachment_image_srcset( $image_id, 'full' );
		$sizes  = wp_get_attachment_image_sizes( $image_id, 'full' );
		if ( $srcset ) {
			$processor->set_attribute( 'srcset', $srcset );
		} else {
			$processor->remove_attribute( 'srcset' );
		}

		if ( $sizes ) {
			$processor->set_attribute( 'sizes', $sizes );
		} else {
			$processor->remove_attribute( 'sizes' );
		}
	}

	/**
	 * Update bound Cover buttons with the current category link.
	 *
	 * @param string   $content    Rendered Cover content.
	 * @param \WP_Term $category   Product category.
	 * @param string   $aria_label Optional accessible label for the first button.
	 * @return string
	 */
	private function update_cover_button_markup( $content, $category, $aria_label ) {
		$term_link = get_term_link( $category );
		$processor = new \WP_HTML_Tag_Processor( $content );
		if ( $processor->next_tag( array( 'class_name' => 'wc-block-featured-category__link' ) ) && $processor->next_tag( array( 'class_name' => 'wp-block-button__link' ) ) ) {
			if ( ! is_wp_error( $term_link ) ) {
				$processor->set_attribute( 'href', $term_link );
			}
			if ( $aria_label ) {
				$processor->set_attribute( 'aria-label', $aria_label );
			}
		}

		return $processor->get_updated_html();
	}

	/**
	 * Returns the name of the featured category.
	 *
	 * @param \WP_Term $category Featured category.
	 * @return string
	 */
	protected function get_item_title( $category ) {
		return $category->name;
	}

	/**
	 * Returns the featured category image attachment ID.
	 *
	 * @param \WP_Term $category Term object.
	 * @return int
	 */
	protected function get_item_image_id( $category ) {
		return (int) get_term_meta( $category->term_id, 'thumbnail_id', true );
	}

	/**
	 * Returns the featured category image URL.
	 *
	 * @param \WP_Term $category Term object.
	 * @param string   $size Image size, defaults to 'full'.
	 * @return string
	 */
	protected function get_item_image( $category, $size = 'full' ) {
		$image_id = $this->get_item_image_id( $category );

		if ( $image_id ) {
			return wp_get_attachment_image_url( $image_id, $size );
		}

		return '';
	}

	/**
	 * Renders the featured category attributes.
	 *
	 * @param \WP_Term $category Term object.
	 * @param array    $attributes Block attributes. Default empty array.
	 * @return string
	 */
	protected function render_attributes( $category, $attributes ) {
		$output = '';

		// Backwards compatibility: Only render legacy attributes if `editMode` exists as boolean value
		// This allows us to distinguish between old and new version of the block (which accept inner blocks).
		if ( array_key_exists( 'editMode', $attributes ) && is_bool( $attributes['editMode'] ) ) {
			$legacy_title = sprintf(
				'<h2 class="wc-block-featured-category__title">%s</h2>',
				wp_kses_post( $category->name )
			);

			$output .= $legacy_title;

			if (
				! isset( $attributes['showDesc'] ) ||
				( isset( $attributes['showDesc'] ) && false !== $attributes['showDesc'] )
			) {
				$desc_str = sprintf(
					'<div class="wc-block-featured-category__description">%s</div>',
					wc_format_content( wp_kses_post( $category->description ) )
				);
				$output  .= $desc_str;
			}
		}

		return $output;
	}
}
