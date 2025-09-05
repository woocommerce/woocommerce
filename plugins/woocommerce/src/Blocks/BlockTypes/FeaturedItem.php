<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * FeaturedItem class.
 */
abstract class FeaturedItem extends AbstractDynamicBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name;

	/**
	 * Default attribute values.
	 *
	 * @var array
	 */
	protected $defaults = array(
		'align' => 'none',
	);

	/**
	 * Global style enabled for this block.
	 *
	 * @var array
	 */
	protected $global_style_wrapper = array(
		'background_color',
		'border_color',
		'border_radius',
		'border_width',
		'font_size',
		'padding',
		'text_color',
		'extra_classes',
	);

	/**
	 * Initialize the block.
	 */
	protected function initialize() {
		parent::initialize();
		add_filter( 'render_block_context', [ $this, 'update_context' ], 10, 3 );
		add_filter( 'render_block_core/post-title', [ $this, 'restore_global_post' ], 10, 3 );
	}

	/**
	 * Current item (product or category) for context
	 *
	 * @var \WP_Term|\WC_Product|null
	 */
	private $current_item = null;

	/**
	 * Update context for inner blocks to provide postId and postType.
	 *
	 * @param array    $context Block context.
	 * @param array    $parsed_block Block attributes.
	 * @param WP_Block $parent_block Block instance.
	 *
	 * @return array Updated block context.
	 */
	public function update_context( $context, $parsed_block, $parent_block ) {
		// Check if this is a core block inside a featured item block
		if ( $parent_block && 
			 isset( $parent_block->name ) && 
			 ( $parent_block->name === 'woocommerce/featured-product' || $parent_block->name === 'woocommerce/featured-category' ) &&
			 isset( $parent_block->attributes ) ) {
			
			// Get the item from parent block attributes
			$item = $this->get_item( $parent_block->attributes );
			
			if ( $item instanceof \WC_Product ) {
				// Manipulate global post for core blocks
				if ( 'core/post-title' === $parsed_block['blockName'] ) {
					global $post;
					$post = get_post( $item->get_id() );
					
					if ( $post instanceof \WP_Post ) {
						setup_postdata( $post );
					}
				}
				
				$context['postId'] = $item->get_id();
				$context['postType'] = 'product';
				$this->current_item = $item;
			}
		}
		
		return $context;
	}

	/**
	 * Restore global post data after rendering core/post-title.
	 *
	 * @param string    $block_content The block content.
	 * @param array     $parsed_block The full block, including name and attributes.
	 * @param \WP_Block $block_instance The block instance.
	 *
	 * @return string
	 */
	public function restore_global_post( $block_content, $parsed_block, $block_instance ) {
		if ( $this->current_item ) {
			wp_reset_postdata();
		}
		
		return $block_content;
	}

	/**
	 * Returns the featured item.
	 *
	 * @param array $attributes Block attributes. Default empty array.
	 * @return \WP_Term|\WC_Product|null
	 */
	abstract protected function get_item( $attributes );

	/**
	 * Returns the name of the featured item.
	 *
	 * @param \WP_Term|\WC_Product $item Item object.
	 * @return string
	 */
	abstract protected function get_item_title( $item );

	/**
	 * Returns the featured item image URL.
	 *
	 * @param \WP_Term|\WC_Product $item Item object.
	 * @param string               $size Image size, defaults to 'full'.
	 * @return string
	 */
	abstract protected function get_item_image( $item, $size = 'full' );

	/**
	 * Renders the featured item attributes.
	 *
	 * @param \WP_Term|\WC_Product $item       Item object.
	 * @param array                $attributes Block attributes. Default empty array.
	 * @return string
	 */
	abstract protected function render_attributes( $item, $attributes );

	/**
	 * Render the featured item block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$item = $this->get_item( $attributes );
		if ( ! $item ) {
			return '';
		}

		$aria_label = $attributes['ariaLabel'] ?? '';
		$attributes = wp_parse_args( $attributes, $this->defaults );

		$attributes['height'] = $attributes['height'] ?? wc_get_theme_support( 'featured_block::default_height', 500 );

		$image_url = esc_url( $this->get_image_url( $attributes, $item ) );

		$styles  = $this->get_styles( $attributes );
		$classes = $this->get_classes( $attributes );

		$output  = sprintf( '<div class="%1$s wp-block-woocommerce-%2$s" style="%3$s">', esc_attr( trim( $classes ) ), $this->block_name, esc_attr( $styles ) );
		$output .= sprintf( '<div class="wc-block-%s__wrapper">', $this->block_name );
		$output .= $this->render_overlay( $attributes );

		if ( ! $attributes['isRepeated'] && ! $attributes['hasParallax'] ) {
			$output .= $this->render_image( $attributes, $item, $image_url );
		} else {
			$output .= $this->render_bg_image( $attributes, $image_url );
		}

		if ( isset( $aria_label ) && ! empty( $aria_label ) ) {
			$p = new \WP_HTML_Tag_Processor( $content );

			if ( $p->next_tag( 'a', [ 'class' => 'wp-block-button__link' ] ) ) {
				$p->set_attribute( 'aria-label', $aria_label );
				$content = $p->get_updated_html();
			}
		}

		// Determine if explicit title inner blocks exist; if so, disable legacy title fallback.
		if ( isset( $block->parsed_block['innerBlocks'] ) && is_array( $block->parsed_block['innerBlocks'] ) ) {
			foreach ( $block->parsed_block['innerBlocks'] as $inner_block ) {
				$block_name = isset( $inner_block['blockName'] ) ? $inner_block['blockName'] : '';
				if ( in_array( $block_name, array( 'core/post-title', 'core/heading', 'woocommerce/category-title', 'woocommerce/product-title' ), true ) ) {
					$attributes['useLegacyTitle'] = false;
					break;
				}
			}
		}

		// Render InnerBlocks content wrapped in __inner-blocks div to match editor structure
		if ( ! empty( $content ) ) {
			$output .= sprintf( '<div class="wc-block-%s__inner-blocks">%s</div>', $this->block_name, $content );
		}
		// Render additional attributes (e.g. description/price) for legacy compatibility.
		$output .= $this->render_attributes( $item, $attributes );
		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Returns the url the item's image
	 *
	 * @param array                $attributes Block attributes. Default empty array.
	 * @param \WP_Term|\WC_Product $item       Item object.
	 *
	 * @return string
	 */
	private function get_image_url( $attributes, $item ) {
		$image_size = 'large';
		if ( 'none' !== $attributes['align'] || $attributes['height'] > 800 ) {
			$image_size = 'full';
		}

		if ( $attributes['mediaId'] ) {
			return wp_get_attachment_image_url( $attributes['mediaId'], $image_size );
		}

		return $this->get_item_image( $item, $image_size );
	}

	/**
	 * Renders the featured image as a div background.
	 *
	 * @param array  $attributes Block attributes. Default empty array.
	 * @param string $image_url  Item image url.
	 *
	 * @return string
	 */
	private function render_bg_image( $attributes, $image_url ) {
		$styles = $this->get_bg_styles( $attributes, $image_url );

		$classes = [ "wc-block-{$this->block_name}__background-image" ];

		if ( $attributes['hasParallax'] ) {
			$classes[] = ' has-parallax';
		}

		return sprintf( '<div class="%1$s" style="%2$s" /></div>', esc_attr( implode( ' ', $classes ) ), esc_attr( $styles ) );
	}

	/**
	 * Get the styles for the wrapper element (background image, color).
	 *
	 * @param array  $attributes Block attributes. Default empty array.
	 * @param string $image_url  Item image url.
	 *
	 * @return string
	 */
	public function get_bg_styles( $attributes, $image_url ) {
		$style = '';

		if ( $attributes['isRepeated'] || $attributes['hasParallax'] ) {
			$style .= "background-image: url($image_url);";
		}

		if ( ! $attributes['isRepeated'] ) {
			$style .= 'background-repeat: no-repeat;';

			$bg_size = 'cover' === $attributes['imageFit'] ? $attributes['imageFit'] : 'auto';
			$style  .= 'background-size: ' . $bg_size . ';';
		}

		if ( $this->hasFocalPoint( $attributes ) ) {
			$style .= sprintf(
				'background-position: %s%% %s%%;',
				$attributes['focalPoint']['x'] * 100,
				$attributes['focalPoint']['y'] * 100
			);
		}

		$global_style_style = StyleAttributesUtils::get_styles_by_attributes( $attributes, $this->global_style_wrapper );
		$style             .= $global_style_style;

		return $style;
	}

	/**
	 * Renders the featured image
	 *
	 * @param array                $attributes Block attributes. Default empty array.
	 * @param \WC_Product|\WP_Term $item       Item object.
	 * @param string               $image_url  Item image url.
	 *
	 * @return string
	 */
	private function render_image( $attributes, $item, string $image_url ) {
		$style   = sprintf( 'object-fit: %s;', esc_attr( $attributes['imageFit'] ) );
		$img_alt = $attributes['alt'] ?: $this->get_item_title( $item );

		if ( $this->hasFocalPoint( $attributes ) ) {
			$style .= sprintf(
				'object-position: %s%% %s%%;',
				$attributes['focalPoint']['x'] * 100,
				$attributes['focalPoint']['y'] * 100
			);
		}

		if ( ! empty( $image_url ) ) {
			return sprintf(
				'<img alt="%1$s" class="wc-block-%2$s__background-image" src="%3$s" style="%4$s" />',
				esc_attr( $img_alt ),
				$this->block_name,
				esc_url( $image_url ),
				esc_attr( $style )
			);
		}

		return '';
	}

	/**
	 * Get the styles for the wrapper element (background image, color).
	 *
	 * @param array $attributes Block attributes. Default empty array.
	 * @return string
	 */
	public function get_styles( $attributes ) {
		$style = '';

		$min_height = $attributes['minHeight'] ?? wc_get_theme_support( 'featured_block::default_height', 500 );

		if ( isset( $attributes['minHeight'] ) ) {
			$style .= sprintf( 'min-height:%dpx;', intval( $min_height ) );
		}

		$global_style_style = StyleAttributesUtils::get_styles_by_attributes( $attributes, $this->global_style_wrapper );
		$style             .= $global_style_style;

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

		$global_style_classes = StyleAttributesUtils::get_classes_by_attributes( $attributes, $this->global_style_wrapper );

		$classes[] = $global_style_classes;

		return implode( ' ', $classes );
	}

	/**
	 * Renders the block overlay
	 *
	 * @param array $attributes Block attributes. Default empty array.
	 *
	 * @return string
	 */
	private function render_overlay( $attributes ) {
		if ( isset( $attributes['overlayGradient'] ) ) {
			$overlay_styles = sprintf( 'background-image: %s', $attributes['overlayGradient'] );
		} elseif ( isset( $attributes['overlayColor'] ) ) {
			$overlay_styles = sprintf( 'background-color: %s', $attributes['overlayColor'] );
		} else {
			$overlay_styles = 'background-color: #000000';
		}

		return sprintf( '<div class="background-dim__overlay" style="%s"></div>', esc_attr( $overlay_styles ) );
	}

	/**
	 * Returns whether the focal point is defined for the block.
	 *
	 * @param array $attributes Block attributes. Default empty array.
	 *
	 * @return bool
	 */
	private function hasFocalPoint( $attributes ): bool {
		return is_array( $attributes['focalPoint'] ) && 2 === count( $attributes['focalPoint'] );
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		parent::enqueue_data( $attributes );
		$this->asset_data_registry->add( 'defaultHeight', wc_get_theme_support( 'featured_block::default_height', 500 ) );
	}
}
