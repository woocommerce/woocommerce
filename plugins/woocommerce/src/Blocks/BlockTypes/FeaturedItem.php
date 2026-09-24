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
		add_filter( 'render_block_woocommerce/' . $this->block_name, array( $this, 'move_layout_to_content' ), 10, 2 );
	}

	/**
	 * Apply Core vertical flex layout classes to the dynamic inner-block container.
	 *
	 * @internal
	 * @since 11.3.0
	 *
	 * @param string $content Rendered block markup.
	 * @param array  $block Parsed block.
	 * @return string
	 */
	public function move_layout_to_content( $content, $block ) {
		if ( ! is_string( $content ) || ! is_array( $block ) ) {
			return $content;
		}

		$processor = new \WP_HTML_Tag_Processor( $content );
		if ( ! $processor->next_tag() ) {
			return $content;
		}

		$layout_classes   = array(
			'is-layout-flex',
			'is-vertical',
			'is-nowrap',
			'is-content-justification-stretch',
			'wp-block-woocommerce-' . $this->block_name . '-is-layout-flex',
		);
		$container_prefix = 'wp-container-woocommerce-' . $this->block_name . '-is-layout-';
		$classes          = array();
		foreach ( explode( ' ', (string) $processor->get_attribute( 'class' ) ) as $class ) {
			if ( in_array( $class, $layout_classes, true ) || 0 === strpos( $class, $container_prefix ) ) {
				$classes[] = $class;
			}
		}
		foreach ( $classes as $class ) {
			$processor->remove_class( $class );
		}

		// Legacy blocks render their title and description outside the inner-block container.
		// Leave Core layout classes removed for them so their existing CSS controls the layout;
		// only modern blocks receive these classes on the inner-block container.
		$is_legacy = isset( $block['attrs']['editMode'] ) && is_bool( $block['attrs']['editMode'] );
		if ( ! $is_legacy && $processor->next_tag( array( 'class_name' => 'wc-block-' . $this->block_name . '__inner-blocks' ) ) ) {
			foreach ( $classes as $class ) {
				$processor->add_class( $class );
			}
		}

		return $processor->get_updated_html();
	}

	/**
	 * Current item (product or category) for context
	 *
	 * @var \WP_Term|\WC_Product|null
	 */
	private $current_item = null;

	/**
	 * Current featured item ID (product or category) for context
	 *
	 * @var int
	 */
	protected $featured_item_id = 0;

	/**
	 * Featured Item inner blocks names.
	 * This is used to map all the inner blocks for a Featured Item block.
	 *
	 * @var array
	 */
	protected $featured_item_inner_blocks_names = [];

	/**
	 * Extract the inner block names for the Featured Item block. This way it's possible
	 * to map all the inner blocks for a Featured Item block and manipulate the data as needed.
	 *
	 * @param array $block The Featured Item block or its inner blocks.
	 * @param array $result Array of inner block names.
	 *
	 * @return array Array containing all the inner block names of a Featured Item block.
	 */
	protected function extract_featured_item_inner_block_names( $block, &$result = [] ) {
		if ( isset( $block['blockName'] ) ) {
			$result[] = $block['blockName'];
		}

		if ( 'woocommerce/product-template' === $block['blockName'] || 'core/post-template' === $block['blockName'] ) {
			return $result;
		}

		if ( isset( $block['innerBlocks'] ) ) {
			foreach ( $block['innerBlocks'] as $inner_block ) {
				$this->extract_featured_item_inner_block_names( $inner_block, $result );
			}
		}
		return $result;
	}

	/**
	 * Replace the global post for the Featured Item inner blocks and reset it after.
	 *
	 * This is needed because some of the inner blocks may use the global post
	 * instead of fetching the product through the context, so even if the
	 * context is passed to the inner block, it will still use the global post.
	 *
	 * @param array $block Block attributes.
	 * @param array $context Block context.
	 */
	protected function replace_post_for_featured_item_inner_block( $block, &$context ) {
		if ( $this->featured_item_inner_blocks_names ) {
			$block_name = end( $this->featured_item_inner_blocks_names );

			if ( $block_name === $block['blockName'] ) {
				array_pop( $this->featured_item_inner_blocks_names );

				// Handle core blocks that need global post manipulation.
				if ( 'core/post-excerpt' === $block_name || 'core/post-title' === $block_name ) {
					global $post;
					$post = get_post( $this->featured_item_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

					if ( $post instanceof \WP_Post ) {
						setup_postdata( $post );
					}
				}

				$context['postId']   = $this->featured_item_id;
				$context['postType'] = 'product';
				$this->current_item  = wc_get_product( $this->featured_item_id );
			}
		}
	}

	/**
	 * Update context for inner blocks to provide postId and postType.
	 *
	 * @param array          $context Block context.
	 * @param array          $parsed_block Block attributes.
	 * @param \WP_Block|null $parent_block Block instance.
	 *
	 * @return array Updated block context.
	 */
	public function update_context( $context, $parsed_block, $parent_block ) {
		// Check if this is a featured item block and extract all inner block names.
		if ( ( 'woocommerce/featured-product' === $parsed_block['blockName'] || 'woocommerce/featured-category' === $parsed_block['blockName'] )
			&& isset( $parsed_block['attrs'] ) ) {

			$item = $this->get_item( $parsed_block['attrs'] );
			if ( $item instanceof \WC_Product ) {
				$this->featured_item_id = $item->get_id();

				$this->featured_item_inner_blocks_names = array_reverse(
					$this->extract_featured_item_inner_block_names( $parsed_block )
				);
			}
		}

		// Replace post context for featured item inner blocks.
		$this->replace_post_for_featured_item_inner_block( $parsed_block, $context );

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
	 * Returns the featured item image attachment ID.
	 *
	 * Note: Ideally, this method would be declared as abstract.
	 * However, it remains a concrete method returning 0 to preserve legacy
	 * compatibility with existing child classes that may not implement it.
	 * See:
	 * https://github.com/woocommerce/woocommerce/pull/66466#discussion_r3559124282
	 *
	 * @param \WP_Term|\WC_Product $item Item object.
	 * @return int
	 */
	protected function get_item_image_id( $item ) {
		return 0;
	}

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
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
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

		$styles  = $this->get_styles( $attributes );
		$classes = $this->get_classes( $attributes );

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => trim( $classes ) . ' wp-block-woocommerce-' . $this->block_name,
				'style' => $styles,
				'id'    => $attributes['anchor'] ?? '',
			)
		);

		$output  = '<div ' . $wrapper_attributes . '>';
		$output .= sprintf( '<div class="wc-block-%s__wrapper">', $this->block_name );
		$output .= $this->render_overlay( $attributes );

		if ( ! $attributes['isRepeated'] && ! $attributes['hasParallax'] ) {
			$output .= $this->render_image( $attributes, $item );
		} else {
			$output .= $this->render_bg_image( $attributes, $item );
		}

		if ( isset( $aria_label ) && ! empty( $aria_label ) ) {
			$p = new \WP_HTML_Tag_Processor( $content );

			if ( $p->next_tag( 'a', [ 'class' => 'wp-block-button__link' ] ) ) {
				$p->set_attribute( 'aria-label', $aria_label );
				$content = $p->get_updated_html();
			}
		}

		// Render additional attributes (e.g. description/price) for legacy compatibility.
		$output .= $this->render_attributes( $item, $attributes );

		if ( ! empty( $content ) ) {
			$output .= sprintf( '<div class="wc-block-%s__inner-blocks">%s</div>', $this->block_name, $content );
		}

		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Returns the image size slug for the featured item image.
	 *
	 * @param array $attributes Block attributes. Default empty array.
	 * @return string
	 */
	private function get_image_size( $attributes ) {
		$image_size = 'large';
		if ( 'none' !== $attributes['align'] || $attributes['height'] > 800 ) {
			$image_size = 'full';
		}

		return $image_size;
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
		$image_size = $this->get_image_size( $attributes );

		if ( $attributes['mediaId'] ) {
			return wp_get_attachment_image_url( $attributes['mediaId'], $image_size );
		}

		return $this->get_item_image( $item, $image_size );
	}

	/**
	 * Renders the featured image as a div background.
	 *
	 * @param array                $attributes Block attributes. Default empty array.
	 * @param \WC_Product|\WP_Term $item       Item object.
	 *
	 * @return string
	 */
	private function render_bg_image( $attributes, $item ) {
		$image_url = $this->get_image_url( $attributes, $item );
		$styles    = $this->get_bg_styles( $attributes, $image_url );

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
	 *
	 * @return string
	 */
	private function render_image( $attributes, $item ) {
		$style   = sprintf( 'object-fit: %s;', $attributes['imageFit'] );
		$img_alt = $attributes['alt'] ?: $this->get_item_title( $item );

		if ( $this->hasFocalPoint( $attributes ) ) {
			$style .= sprintf(
				'object-position: %s%% %s%%;',
				$attributes['focalPoint']['x'] * 100,
				$attributes['focalPoint']['y'] * 100
			);
		}

		$image_size = $this->get_image_size( $attributes );

		// When imageFit is not "cover", the image uses object-fit: none, so it is
		// not scaled to the block — it renders at its natural pixel size. Before
		// this block used responsive images, a single large URL was always loaded,
		// which was typically bigger than the block and got clipped by overflow:
		// hidden, making the block look filled. wp_get_attachment_image() picks a
		// smaller srcset candidate on narrow viewports by default, leaving empty
		// space around the image. Override sizes so the browser still selects the
		// full image width and the previous appearance is preserved.
		if ( 'cover' === $attributes['imageFit'] ) {
			$image_id = empty( $attributes['mediaId'] ) ?
				$this->get_item_image_id( $item ) :
				absint( $attributes['mediaId'] );

			if ( $image_id ) {
				$attr = array(
					'alt'   => $img_alt,
					'class' => "wc-block-{$this->block_name}__background-image",
					'style' => $style,
				);

				return wp_get_attachment_image( $image_id, $image_size, false, $attr );
			}
		}

		$image_url = $this->get_image_url( $attributes, $item );

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
	 * Get Featured-specific height styles for the block wrapper.
	 *
	 * @param array $attributes Block attributes. Default empty array.
	 * @return string
	 */
	public function get_styles( $attributes ) {
		$style = sprintf( '--wc-featured-item-min-height:%dpx;', wc_get_theme_support( 'featured_block::default_height', 500 ) );

		if ( isset( $attributes['minHeight'] ) ) {
			$style .= sprintf( 'min-height:%dpx;', intval( $attributes['minHeight'] ) );
		}

		return $style;
	}


	/**
	 * Get Featured-specific class names for the block container.
	 *
	 * @param array $attributes Block attributes. Default empty array.
	 * @return string
	 */
	public function get_classes( $attributes ) {
		$classes = array( 'wc-block-' . $this->block_name );

		if ( isset( $attributes['dimRatio'] ) && ( 0 !== $attributes['dimRatio'] ) ) {
			$classes[] = 'has-background-dim';

			if ( 50 !== $attributes['dimRatio'] ) {
				$classes[] = 'has-background-dim-' . 10 * round( $attributes['dimRatio'] / 10 );
			}
		}

		if ( isset( $attributes['contentAlign'] ) && 'center' !== $attributes['contentAlign'] ) {
			$classes[] = "has-{$attributes['contentAlign']}-content";
		}

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
