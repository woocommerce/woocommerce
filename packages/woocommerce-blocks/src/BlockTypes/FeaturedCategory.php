<?php
/**
 * Featured category block.
 *
 * @package WooCommerce\Blocks
 */

namespace Automattic\WooCommerce\Blocks\BlockTypes;

defined( 'ABSPATH' ) || exit;

/**
 * FeaturedCategory class.
 */
class FeaturedCategory extends AbstractDynamicBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'featured-category';

	/**
	 * Default attribute values, should match what's set in JS `registerBlockType`.
	 *
	 * @var array
	 */
	protected $defaults = array(
		'align'        => 'none',
		'contentAlign' => 'center',
		'dimRatio'     => 50,
		'focalPoint'   => false,
		'height'       => false,
		'mediaId'      => 0,
		'mediaSrc'     => '',
		'showDesc'     => true,
	);

	/**
	 * Render the Featured Category block.
	 *
	 * @param array  $attributes Block attributes. Default empty array.
	 * @param string $content    Block content. Default empty string.
	 * @return string Rendered block type output.
	 */
	public function render( $attributes = array(), $content = '' ) {
		$id       = isset( $attributes['categoryId'] ) ? (int) $attributes['categoryId'] : 0;
		$category = get_term( $id, 'product_cat' );
		if ( ! $category || is_wp_error( $category ) ) {
			return '';
		}
		$attributes = wp_parse_args( $attributes, $this->defaults );
		if ( ! $attributes['height'] ) {
			$attributes['height'] = wc_get_theme_support( 'featured_block::default_height', 500 );
		}

		$title = sprintf(
			'<h2 class="wc-block-featured-category__title">%s</h2>',
			wp_kses_post( $category->name )
		);

		$desc_str = sprintf(
			'<div class="wc-block-featured-category__description">%s</div>',
			wc_format_content( $category->description )
		);

		$output = sprintf( '<div class="%1$s" style="%2$s">', $this->get_classes( $attributes ), $this->get_styles( $attributes, $category ) );

		$output .= $title;
		if ( $attributes['showDesc'] ) {
			$output .= $desc_str;
		}
		$output .= '<div class="wc-block-featured-category__link">' . $content . '</div>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Get the styles for the wrapper element (background image, color).
	 *
	 * @param array    $attributes Block attributes. Default empty array.
	 * @param \WP_Term $category Term object.
	 * @return string
	 */
	public function get_styles( $attributes, $category ) {
		$style      = '';
		$image_size = 'large';
		if ( 'none' !== $attributes['align'] || $attributes['height'] > 800 ) {
			$image_size = 'full';
		}

		if ( $attributes['mediaId'] ) {
			$image = wp_get_attachment_image_url( $attributes['mediaId'], $image_size );
		} else {
			$image = $this->get_image( $category, $image_size );
		}

		if ( ! empty( $image ) ) {
			$style .= sprintf( 'background-image:url(%s);', esc_url( $image ) );
		}

		if ( isset( $attributes['customOverlayColor'] ) ) {
			$style .= sprintf( 'background-color:%s;', esc_attr( $attributes['customOverlayColor'] ) );
		}

		if ( isset( $attributes['height'] ) ) {
			$style .= sprintf( 'min-height:%dpx;', intval( $attributes['height'] ) );
		}

		if ( is_array( $attributes['focalPoint'] ) && 2 === count( $attributes['focalPoint'] ) ) {
			$style .= sprintf(
				'background-position: %s%% %s%%',
				$attributes['focalPoint']['x'] * 100,
				$attributes['focalPoint']['y'] * 100
			);
		}

		return $style;
	}

	/**
	 * Get class names for the block container.
	 *
	 * @param array $attributes Block attributes. Default empty array.
	 * @return string
	 */
	public function get_classes( $attributes ) {
		$classes = array( 'wc-block-' . $this->block_name );

		if ( isset( $attributes['align'] ) ) {
			$classes[] = "align{$attributes['align']}";
		}

		if ( isset( $attributes['dimRatio'] ) && ( 0 !== $attributes['dimRatio'] ) ) {
			$classes[] = 'has-background-dim';

			if ( 50 !== $attributes['dimRatio'] ) {
				$classes[] = 'has-background-dim-' . 10 * round( $attributes['dimRatio'] / 10 );
			}
		}

		if ( isset( $attributes['contentAlign'] ) && 'center' !== $attributes['contentAlign'] ) {
			$classes[] = "has-{$attributes['contentAlign']}-content";
		}

		if ( isset( $attributes['overlayColor'] ) ) {
			$classes[] = "has-{$attributes['overlayColor']}-background-color";
		}

		if ( isset( $attributes['className'] ) ) {
			$classes[] = $attributes['className'];
		}

		return implode( ' ', $classes );
	}

	/**
	 * Returns the main product image URL.
	 *
	 * @param \WP_Term $category Term object.
	 * @param string   $size    Image size, defaults to 'full'.
	 * @return string
	 */
	public function get_image( $category, $size = 'full' ) {
		$image    = '';
		$image_id = get_term_meta( $category->term_id, 'thumbnail_id', true );

		if ( $image_id ) {
			$image = wp_get_attachment_image_url( $image_id, $size );
		}

		return $image;
	}
}
