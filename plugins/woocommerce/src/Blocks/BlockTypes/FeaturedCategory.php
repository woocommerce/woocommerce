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
	 * Pass the resolved product category to inner blocks.
	 *
	 * @since 11.2.0
	 *
	 * @param array          $context      Block context.
	 * @param array          $parsed_block Block attributes.
	 * @param \WP_Block|null $parent_block Parent block instance.
	 * @return array Updated block context.
	 */
	public function update_context( $context, $parsed_block, $parent_block ) {
		$context = parent::update_context( $context, $parsed_block, $parent_block );

		if ( is_array( $context ) && $parent_block instanceof \WP_Block && 'woocommerce/featured-category' === $parent_block->name ) {
			$category_id = self::resolve_category_id( $parent_block->attributes, $parent_block->context );
			if ( $category_id ) {
				$context['termId']       = $category_id;
				$context['termTaxonomy'] = 'product_cat';
				$context['taxonomy']     = 'product_cat';
			}
		}

		return $context;
	}

	/**
	 * Resolve the selected or inherited product category ID.
	 *
	 * @param array $attributes Block attributes.
	 * @param array $context Block context.
	 * @return int
	 */
	private static function resolve_category_id( array $attributes, array $context ): int {
		if ( ! empty( $attributes['categoryId'] ) ) {
			return absint( $attributes['categoryId'] );
		}

		$taxonomy = $context['termTaxonomy'] ?? $context['taxonomy'] ?? '';
		return 'product_cat' === $taxonomy ? absint( $context['termId'] ?? 0 ) : 0;
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
	 * Render Cover layouts while retaining the original renderer for unmigrated blocks.
	 *
	 * @since 11.2.0
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	protected function render( $attributes, $content, $block ) {
		$attributes['categoryId'] = self::resolve_category_id( $attributes, $block->context );

		if ( 'cover' !== ( $attributes['layout'] ?? '' ) ) {
			return parent::render( $attributes, $content, $block );
		}

		$category = $this->get_item( $attributes );
		if ( ! $category ) {
			return '';
		}

		$content = $this->render_cover( $content, $category, $block );
		wp_enqueue_style( 'wp-block-cover' );

		if ( ! empty( $attributes['ariaLabel'] ) ) {
			$processor = new \WP_HTML_Tag_Processor( $content );
			if ( $processor->next_tag( array( 'class_name' => 'wp-block-button__link' ) ) ) {
				$processor->set_attribute( 'aria-label', $attributes['ariaLabel'] );
				$content = $processor->get_updated_html();
			}
		}

		$classes = 'wp-block-woocommerce-featured-category ' . ( $attributes['className'] ?? '' );
		if ( ! empty( $attributes['align'] ) && 'none' !== $attributes['align'] ) {
			$classes .= ' align' . sanitize_html_class( $attributes['align'] );
		}
		$anchor = empty( $attributes['anchor'] ) ? '' : ' id="' . esc_attr( $attributes['anchor'] ) . '"';
		return '<div class="' . esc_attr( trim( $classes ) ) . '"' . $anchor . '>' . $content . '</div>';
	}

	/**
	 * Render the current Cover layout with its category-managed image.
	 *
	 * @param string    $content Already rendered inner blocks.
	 * @param \WP_Term  $category Selected category.
	 * @param \WP_Block $block Block instance.
	 * @return string
	 */
	private function render_cover( $content, $category, $block ) {
		$cover = $this->find_managed_cover( $block->parsed_block['innerBlocks'] ?? array() );
		if ( ! $cover ) {
			return $content;
		}

		return self::replace_cover_image_layer( $content, $this->render_managed_image( $category, $cover['attrs'] ) );
	}


	/**
	 * Find the direct Cover whose image is still managed by Featured Category.
	 *
	 * @param array[] $blocks Parsed blocks.
	 * @return array|null
	 */
	private static function find_managed_cover( $blocks ) {
		foreach ( $blocks as $block ) {
			if ( 'core/cover' !== ( $block['blockName'] ?? '' ) ) {
				continue;
			}
			$attributes = $block['attrs'] ?? array();
			$bindings   = $attributes['metadata']['bindings'] ?? array();
			$image      = $attributes['metadata']['woocommerce/featured-category-image'] ?? null;
			if ( ! is_array( $image ) || ! empty( $attributes['useFeaturedImage'] ) || 'image' !== ( $attributes['backgroundType'] ?? 'image' ) || isset( $bindings['id'] ) || isset( $bindings['url'] ) || isset( $bindings['__default'] ) ) {
				return null;
			}
			return ( $attributes['id'] ?? 0 ) === ( $image['id'] ?? 0 ) && ( $attributes['url'] ?? '' ) === ( $image['url'] ?? '' ) ? $block : null;
		}

		return null;
	}

	/**
	 * Render the managed Cover image from the current category thumbnail.
	 *
	 * Cover saves its image markup in the post content, so category thumbnail changes do not update it automatically.
	 * Until Cover supports image block bindings, rebuild only its managed image and leave the rendered children alone.
	 *
	 * @param \WP_Term $category Product category.
	 * @param array    $attributes Saved Cover attributes.
	 * @return string
	 */
	private function render_managed_image( $category, $attributes ) {
		$args      = $attributes['metadata']['woocommerce/featured-category-image'];
		$custom_id = absint( $args['attachmentId'] ?? 0 );
		$image_id  = $custom_id ? $custom_id : $this->get_item_image_id( $category );
		$size      = is_string( $args['size'] ?? null ) ? $args['size'] : 'full';
		$image_src = $image_id ? wp_get_attachment_image_src( $image_id, $size ) : false;
		// Like the legacy block, a category without an image shows only the overlay and content.
		if ( ! $image_src ) {
			return '';
		}

		$position = sprintf( '%s%% %s%%', (float) ( $attributes['focalPoint']['x'] ?? 0.5 ) * 100, (float) ( $attributes['focalPoint']['y'] ?? 0.5 ) * 100 );
		$alt      = $attributes['alt'] ?? '';
		$classes  = 'wp-block-cover__image-background';
		$fixed    = ! empty( $attributes['hasParallax'] );
		$repeated = ! empty( $attributes['isRepeated'] );
		// Core uses a CSS background for fixed and repeated images.
		if ( $fixed || $repeated ) {
			$classes .= $fixed ? ' has-parallax' : '';
			$classes .= $repeated ? ' is-repeated' : '';
			return '<div class="' . esc_attr( $classes ) . '"' . ( $alt ? ' role="img" aria-label="' . esc_attr( $alt ) . '"' : '' ) . ' style="' . esc_attr( 'background-position:' . $position . ';background-image:url("' . esc_url_raw( $image_src[0] ) . '");' ) . '"></div>';
		}

		// Natural-size images omit the attachment class so later content processing cannot restore srcset.
		if ( strpos( $attributes['className'] ?? '', 'wc-block-featured-category__natural-image' ) !== false ) {
			return sprintf(
				'<img class="%s" src="%s" width="%d" height="%d" alt="%s" style="object-position:%s" />',
				esc_attr( $classes ),
				esc_url( $image_src[0] ),
				$image_src[1],
				$image_src[2],
				esc_attr( $alt ),
				esc_attr( $position )
			);
		}

		return wp_get_attachment_image(
			$image_id,
			$size,
			false,
			array(
				'class'                => $classes . ' wp-image-' . $image_id,
				'alt'                  => $alt,
				'style'                => 'object-position:' . $position,
				'data-object-fit'      => 'cover',
				'data-object-position' => $position,
			)
		);
	}

	/**
	 * Replace or insert the Cover's image without reserializing its overlay or inner blocks.
	 *
	 * @param string $content Rendered Cover markup.
	 * @param string $image New image markup, or an empty string to remove it.
	 * @return string
	 */
	private static function replace_cover_image_layer( $content, $image ) {
		$processor = new class( $content ) extends \WP_HTML_Tag_Processor {
			/**
			 * Expose the current token's bounds for a surgical replacement.
			 *
			 * @internal
			 * @return \WP_HTML_Span
			 */
			public function get_token_span() {
				$this->set_bookmark( 'image_layer' );
				return $this->bookmarks['image_layer'];
			}
		};
		if ( ! $processor->next_tag( array( 'class_name' => 'wp-block-cover' ) ) ) {
			return $content;
		}
		$wrapper = $processor->get_token_span();

		while ( $processor->next_token() ) {
			// Stop before the content: an image or Cover nested there belongs to the user.
			if ( $processor->has_class( 'wp-block-cover__inner-container' ) ) {
				return substr_replace( $content, $image, $wrapper->start + $wrapper->length, 0 );
			}
			if ( $processor->has_class( 'wp-block-cover__image-background' ) ) {
				$start  = $processor->get_token_span();
				$length = $start->length;
				$tag    = $processor->get_tag();
				// Background divs/spans are empty, but their closing tag must be replaced too.
				if ( 'IMG' !== $tag ) {
					if ( ! $processor->next_tag( array( 'tag_closers' => 'visit' ) ) || ! $processor->is_tag_closer() || $processor->get_tag() !== $tag ) {
						return $content;
					}
					$end    = $processor->get_token_span();
					$length = $end->start + $end->length - $start->start;
				}
				return substr_replace( $content, $image, $start->start, $length );
			}
			// Only Core's overlay may precede the content besides the image layer.
			if ( $processor->get_tag() && ! $processor->has_class( 'wp-block-cover__background' ) && ! ( $processor->is_tag_closer() && 'SPAN' === $processor->get_tag() ) ) {
				return $content;
			}
		}

		return $content;
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
	 * Render v0's title and description, which were not saved as inner blocks.
	 *
	 * @param \WP_Term $category Term object.
	 * @param array    $attributes Block attributes. Default empty array.
	 * @return string
	 */
	protected function render_attributes( $category, $attributes ) {
		// Backwards compatibility: Only render legacy attributes if `editMode` exists as boolean value
		// This allows us to distinguish between old and new version of the block (which accept inner blocks).
		if ( ! array_key_exists( 'editMode', $attributes ) || ! is_bool( $attributes['editMode'] ) ) {
			return '';
		}

		$output = sprintf(
			'<h2 class="wc-block-featured-category__title">%s</h2>',
			wp_kses_post( $category->name )
		);

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

		return $output;
	}
}
