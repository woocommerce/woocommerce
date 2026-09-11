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
	 * Render all Featured Category versions using Cover markup.
	 *
	 * @since 11.2.0
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	protected function render( $attributes, $content, $block ) {
		$category = $this->get_item( $attributes );
		if ( ! $category ) {
			return '';
		}

		$is_legacy = 'cover' !== ( $attributes['layout'] ?? '' ) && ! $this->contains_cover( $block->parsed_block['innerBlocks'] ?? array() );
		if ( $is_legacy ) {
			$content = $this->render_legacy_cover( $attributes, $content, $category );
		}
		wp_enqueue_style( 'wp-block-cover' );

		$processor = new \WP_HTML_Tag_Processor( $content );
		$cover     = $this->find_managed_cover( $block->parsed_block['innerBlocks'] ?? array() );
		if ( $cover && $processor->next_tag( array( 'class_name' => 'wc-block-featured-category__cover' ) ) && $processor->next_tag( array( 'class_name' => 'wp-block-cover__image-background' ) ) ) {
			$image = $cover['attrs']['metadata']['woocommerce/featured-category-image'] ?? $cover['attrs']['metadata']['bindings']['url']['args'] ?? array();
			$this->update_cover_image_markup( $processor, $category, $image, strpos( $cover['attrs']['className'] ?? '', 'wc-block-featured-category__natural-image' ) !== false );
		}

		$content = $processor->get_updated_html();
		if ( ! empty( $attributes['ariaLabel'] ) ) {
			$processor = new \WP_HTML_Tag_Processor( $content );
			if ( $processor->next_tag( array( 'class_name' => 'wp-block-button__link' ) ) ) {
				$processor->set_attribute( 'aria-label', $attributes['ariaLabel'] );
				$content = $processor->get_updated_html();
			}
		}

		$classes = 'wp-block-woocommerce-featured-category';
		if ( ! empty( $attributes['align'] ) && 'none' !== $attributes['align'] ) {
			$classes .= ' align' . sanitize_html_class( $attributes['align'] );
		}
		$classes .= ' ' . ( $attributes['className'] ?? '' );
		$anchor   = empty( $attributes['anchor'] ) ? '' : ' id="' . esc_attr( $attributes['anchor'] ) . '"';
		return '<div class="' . esc_attr( trim( $classes ) ) . '"' . $anchor . '>' . $content . '</div>';
	}

	/**
	 * Whether saved content already contains the current layout.
	 *
	 * @param array[] $blocks Parsed child blocks.
	 * @return bool
	 */
	private static function contains_cover( $blocks ) {
		foreach ( $blocks as $child ) {
			if ( 'core/cover' === ( $child['blockName'] ?? '' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Adapt untouched posts to Cover without rewriting their saved content.
	 *
	 * @param array    $attributes Legacy attributes.
	 * @param string   $content Already rendered inner blocks.
	 * @param \WP_Term $category Selected category.
	 * @return string
	 */
	private function render_legacy_cover( $attributes, $content, $category ) {
		$natural  = 'cover' !== ( $attributes['imageFit'] ?? 'none' );
		$classes  = 'wp-block-cover wc-block-featured-category__cover wc-block-featured-category__legacy';
		$classes .= $natural ? ' wc-block-featured-category__natural-image' : '';
		$position = in_array( $attributes['contentAlign'] ?? '', array( 'left', 'right' ), true ) ? $attributes['contentAlign'] : 'center';
		if ( 'center' !== $position ) {
			$classes .= ' has-custom-content-position is-position-center-' . $position;
		}
		$fixed    = ! empty( $attributes['hasParallax'] );
		$repeated = ! empty( $attributes['isRepeated'] );
		$classes .= $fixed ? ' has-parallax' : '';
		$classes .= $repeated ? ' is-repeated' : '';
		$padding  = $attributes['style']['spacing']['padding'] ?? null;
		if ( is_string( $padding ) ) {
			$attributes['style']['spacing']['padding'] = array_fill_keys( array( 'top', 'right', 'bottom', 'left' ), $padding );
		}
		$classes .= ' ' . \Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils::get_classes_by_attributes( $attributes, $this->global_style_wrapper );
		$styles   = $this->get_styles( $attributes );
		$styles  .= isset( $attributes['lineHeight'] ) ? 'line-height:' . esc_attr( $attributes['lineHeight'] ) . ';' : '';
		$styles  .= isset( $attributes['style']['spacing']['padding'] ) ? '' : 'padding:0;';

		$custom_id = absint( $attributes['mediaId'] ?? 0 );
		$image_id  = $custom_id ? $custom_id : $this->get_item_image_id( $category );
		$size      = ( empty( $attributes['align'] ) || 'none' === $attributes['align'] ) && ( $attributes['height'] ?? 500 ) <= 800 ? 'large' : 'full';
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, $size ) : ( $attributes['mediaSrc'] ?? '' );
		$image_url = $image_url ? $image_url : '';
		$focal     = $attributes['focalPoint'] ?? array(
			'x' => 0.5,
			'y' => 0.5,
		);
		$focal_css = sprintf( '%s%% %s%%', (float) ( $focal['x'] ?? 0.5 ) * 100, (float) ( $focal['y'] ?? 0.5 ) * 100 );
		$alt       = $attributes['alt'] ?? '';
		if ( $fixed || $repeated ) {
			$image = '<div class="wp-block-cover__image-background wc-block-featured-category__background-image" style="' . esc_attr( 'background-position:' . $focal_css . ';background-image:url("' . esc_url_raw( $image_url ) . '");' ) . '"></div>';
		} else {
			$image = '<img class="wp-block-cover__image-background wc-block-featured-category__background-image" src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $alt ) . '" style="object-position:' . esc_attr( $focal_css ) . '" />';
		}
		if ( ! $image_url ) {
			$image = '';
		}
		$dim                = max( 0, min( 100, (int) ( $attributes['dimRatio'] ?? 50 ) ) );
		$overlay            = ! empty( $attributes['overlayGradient'] ) ? 'background:' . $attributes['overlayGradient'] : 'background-color:' . ( $attributes['overlayColor'] ?? '#000000' );
		$overlay            = '<span aria-hidden="true" class="wp-block-cover__background has-background-dim" style="' . esc_attr( $overlay . ';opacity:' . $dim / 100 ) . '"></span>';
		$historical_content = $this->render_attributes( $category, $attributes );
		// Core uses the first HTML chunk to locate the inner wrapper for layout spacing.
		$inner_content = array(
			'<div class="' . esc_attr( trim( $classes ) ) . '" style="' . esc_attr( $styles ) . '">' . $image . $overlay . '<div class="wp-block-cover__inner-container">',
			$historical_content . '<div class="wp-block-group wc-block-featured-category__inner-blocks" style="padding:0 48px 16px">' . $content . '</div>',
			'</div></div>',
		);

		// Cover's PHP callback expects saved HTML; attributes alone cannot create it.
		return render_block(
			array(
				'blockName'    => 'core/cover',
				'attrs'        => array(
					'backgroundType'   => 'image',
					'useFeaturedImage' => false,
					'style'            => $attributes['style'] ?? array(),
				),
				'innerBlocks'  => array(),
				'innerHTML'    => implode( '', $inner_content ),
				'innerContent' => $inner_content,
			)
		);
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
			$classes    = explode( ' ', $attributes['className'] ?? '' );
			if ( ! in_array( 'wc-block-featured-category__cover', $classes, true ) || ! empty( $attributes['useFeaturedImage'] ) || 'image' !== ( $attributes['backgroundType'] ?? 'image' ) || isset( $bindings['__default'] ) ) {
				return null;
			}
			foreach ( array( 'id', 'url' ) as $attribute ) {
				if ( isset( $bindings[ $attribute ] ) && 'woocommerce/term-image' !== ( $bindings[ $attribute ]['source'] ?? '' ) ) {
					return null;
				}
			}
			if ( is_array( $image ) ) {
				return ( $attributes['id'] ?? 0 ) === ( $image['id'] ?? 0 ) && ( $attributes['url'] ?? '' ) === ( $image['url'] ?? '' ) ? $block : null;
			}
			// Read previews saved by the binding-based implementation without registering a global source.
			return 'woocommerce/term-image' === ( $bindings['url']['source'] ?? '' ) ? $block : null;
		}

		return null;
	}

	/**
	 * Update the managed Cover image with the current category thumbnail.
	 *
	 * @param \WP_HTML_Tag_Processor $processor HTML processor positioned on the image.
	 * @param \WP_Term               $category  Product category.
	 * @param array                  $args Managed image settings.
	 * @param bool                   $natural Whether to retain natural image dimensions.
	 */
	private function update_cover_image_markup( $processor, $category, $args = array(), $natural = false ): void {
		$custom_id = absint( $args['attachmentId'] ?? 0 );
		$image_id  = $custom_id ? $custom_id : $this->get_item_image_id( $category );
		$size      = is_string( $args['size'] ?? null ) ? $args['size'] : 'full';
		$image_src = $image_id ? wp_get_attachment_image_src( $image_id, $size ) : false;
		$image_url = $image_src ? $image_src[0] : wc_placeholder_img_src();
		if ( ! $image_src && ! empty( $args['noPlaceholder'] ) ) {
			$processor->set_attribute( 'hidden', true );
			$processor->remove_attribute( 'src' );
			$processor->remove_attribute( 'srcset' );
			return;
		}
		$processor->remove_attribute( 'hidden' );

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
		if ( $natural ) {
			$processor->remove_attribute( 'srcset' );
			$processor->remove_attribute( 'sizes' );
			return;
		}

		$srcset = wp_get_attachment_image_srcset( $image_id, $size );
		$sizes  = wp_get_attachment_image_sizes( $image_id, $size );
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
