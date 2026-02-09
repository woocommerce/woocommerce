<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use WP_Block;
use WP_HTML_Tag_Processor;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductDetails class.
 */
class ProductDetails extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-details';

	/**
	 * Initialize the block type.
	 *
	 * @return void
	 */
	protected function initialize() {
		parent::initialize();

		/**
		 * Filter the blocks that are hooked into the Product Details block.
		 *
		 * @hook woocommerce_product_details_hooked_blocks
		 *
		 * @since 10.0.0
		 * @param {array} $hooked_blocks The blocks that are hooked into the Product Details block.
		 * @return {array} The blocks that are hooked into the Product Details block.
		 */
		$hooked_blocks = apply_filters( 'woocommerce_product_details_hooked_blocks', [] );

		foreach ( $this->validate_hooked_blocks( $hooked_blocks ) as $slug => $block ) {
			$this->register_hooked_block( $slug, $block );
		}
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string|null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 *
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( empty( $block->parsed_block['innerBlocks'] ) ) {
			return $this->render_legacy_block( $attributes, $content, $block );
		}

		$parsed_block = $block->parsed_block;
		$parsed_block = $this->hide_empty_accordion_items( $parsed_block, $block->context );

		/**
		 * Filter to disable the compatibility layer for the blockified templates.
		 *
		 * @see AddToCartWithOptions::render() for full documentation.
		 * @since 7.6.0
		 */
		if ( ! apply_filters( 'woocommerce_disable_compatibility_layer', false ) ) {
			$parsed_block = $this->inject_compatible_tabs( $parsed_block );
		}

		$inner_content = array_reduce(
			$parsed_block['innerBlocks'],
			function ( $carry, $parsed_inner_block ) use ( $block ) {
				$carry .= ( new \WP_Block( $parsed_inner_block, $block->context ) )->render();
				return $carry;
			},
			''
		);

		return sprintf(
			'<div %1$s>%2$s</div>',
			get_block_wrapper_attributes(),
			$inner_content
		);
	}

	/**
	 * Inject compatible tabs.
	 *
	 * @param array $parsed_block Parsed block.
	 *
	 * @return array Parsed block.
	 */
	private function inject_compatible_tabs( $parsed_block ) {
		if ( ! $this->has_accordion( $parsed_block ) ) {
			return $parsed_block;
		}

		/**
		 * Filter the product tabs in the product details block.
		 *
		 * @since 3.3.0
		 * @param array $tabs Array of product tabs.
		 */
		$product_tabs = apply_filters(
			'woocommerce_product_tabs',
			array()
		);

		$default_tabs_callbacks = array(
			'woocommerce_product_description_tab',
			'woocommerce_product_additional_information_tab',
			'comments_template',
		);

		$product_tabs = array_filter(
			$product_tabs,
			function ( $tab ) use ( $default_tabs_callbacks ) {
				return ! in_array( $tab['callback'], $default_tabs_callbacks, true );
			}
		);

		usort(
			$product_tabs,
			function ( $a, $b ) {
				return $a['priority'] <=> $b['priority'];
			}
		);

		$accordion_blocks = array();

		$accordion_anchor_block = $this->get_accordion_anchor_block( $parsed_block );

		if ( ! $accordion_anchor_block ) {
			return $parsed_block;
		}

		foreach ( $product_tabs as $key => $tab ) {
			ob_start();
			call_user_func( $tab['callback'], $key, $tab );
			$tab_content        = ob_get_clean();
			$accordion_blocks[] = $this->create_accordion_item_block(
				$tab['title'],
				'<!-- wp:html -->' . $tab_content . '<!-- /wp:html -->',
				$accordion_anchor_block
			);
		}

		return $this->inject_parsed_accordion_blocks( $parsed_block, $accordion_blocks );
	}

	/**
	 * Create an accordion item block.
	 *
	 * @param string $title Title of the accordion item.
	 * @param string $content Content of the accordion item as block markup.
	 * @param array  $anchor_block Accordion anchor block to determine which item block to create.
	 *
	 * @return array Accordion item.
	 */
	private function create_accordion_item_block( $title, $content, $anchor_block ) {
		if ( isset( $anchor_block['blockName'] ) && 'core/accordion' === $anchor_block['blockName'] ) {
			$template = '<!-- wp:accordion-item -->
				<div class="wp-block-accordion-item">
					<!-- wp:accordion-heading -->
					<h3 class="wp-block-accordion-heading">
						<button class="wp-block-accordion-heading__toggle">
							<span class="wp-block-accordion-heading__toggle-title">%1$s</span>
							<span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span>
						</button>
					</h3>
					<!-- /wp:accordion-heading -->

					<!-- wp:accordion-panel -->
					<div class="wp-block-accordion-panel">
						%2$s
					</div>
					<!-- /wp:accordion-panel -->
				</div>
				<!-- /wp:accordion-item -->';
		} else {
			$template = '<!-- wp:woocommerce/accordion-item -->
				<div class="wp-block-woocommerce-accordion-item"><!-- wp:woocommerce/accordion-header -->
				<h3 class="wp-block-woocommerce-accordion-header accordion-item__heading">
				<button class="accordion-item__toggle">
				<span>%1$s</span>
				<span class="accordion-item__toggle-icon has-icon-plus" style="width:1.2em;height:1.2em"><svg width="1.2em" height="1.2em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M11 12.5V17.5H12.5V12.5H17.5V11H12.5V6H11V11H6V12.5H11Z" fill="currentColor"></path></svg></span>
				</button>
				</h3>
				<!-- /wp:woocommerce/accordion-header -->

				<!-- wp:woocommerce/accordion-panel -->
				<div class="wp-block-woocommerce-accordion-panel"><div class="accordion-content__wrapper">
				%2$s
				</div></div>
				<!-- /wp:woocommerce/accordion-panel --></div>
				<!-- /wp:woocommerce/accordion-item -->';
		}

		return parse_blocks( sprintf( $template, $title, $content ) )[0];
	}

	/**
	 * Inject parsed accordion blocks.
	 *
	 * @param array $parsed_block Parsed block.
	 * @param array $accordion_blocks Accordion blocks.
	 *
	 * @return array Parsed block.
	 */
	private function inject_parsed_accordion_blocks( $parsed_block, $accordion_blocks ) {
		if ( 'core/accordion' === $parsed_block['blockName'] || 'woocommerce/accordion-group' === $parsed_block['blockName'] ) {
			$parsed_block['innerBlocks']  = array_merge( $parsed_block['innerBlocks'], $accordion_blocks );
			$parsed_block['innerBlocks']  = array_values( array_filter( $parsed_block['innerBlocks'] ) );
			$opening_tag                  = reset( $parsed_block['innerContent'] );
			$closing_tag                  = end( $parsed_block['innerContent'] );
			$parsed_block['innerContent'] = array_merge(
				array( $opening_tag ),
				array_fill( 0, count( $parsed_block['innerBlocks'] ), null ),
				array( $closing_tag )
			);
			return $parsed_block;
		}

		foreach ( $parsed_block['innerBlocks'] as $key => $inner_block ) {
			$parsed_block['innerBlocks'][ $key ] = $this->inject_parsed_accordion_blocks( $inner_block, $accordion_blocks );
		}

		return $parsed_block;
	}

	/**
	 * Hide empty accordion items.
	 *
	 * @param array $parsed_block Parsed block.
	 * @param array $context Context.
	 *
	 * @return array Parsed block.
	 */
	private function hide_empty_accordion_items( $parsed_block, $context ) {
		if ( ! $this->has_accordion( $parsed_block ) ) {
			return $parsed_block;
		}

		if ( 'core/accordion' === $parsed_block['blockName'] || 'woocommerce/accordion-group' === $parsed_block['blockName'] ) {
			foreach ( $parsed_block['innerBlocks'] as $key => $inner_block ) {
				$parsed_block['innerBlocks'][ $key ] = $this->mark_accordion_item_hidden( $inner_block, $context );
			}
			$parsed_block['innerBlocks']  = array_values( array_filter( $parsed_block['innerBlocks'] ) );
			$opening_tag                  = reset( $parsed_block['innerContent'] );
			$closing_tag                  = end( $parsed_block['innerContent'] );
			$parsed_block['innerContent'] = array_merge(
				array( $opening_tag ),
				array_fill( 0, count( $parsed_block['innerBlocks'] ), null ),
				array( $closing_tag )
			);
			return $parsed_block;
		}

		foreach ( $parsed_block['innerBlocks'] as $key => $inner_block ) {
			$parsed_block['innerBlocks'][ $key ] = $this->hide_empty_accordion_items( $inner_block, $context );
		}

		return $parsed_block;
	}

	/**
	 * Mark an accordion item as hidden if it has no content.
	 *
	 * @param array $item Item to mark.
	 * @param array $context Context.
	 *
	 * @return array Item.
	 */
	private function mark_accordion_item_hidden( $item, $context ) {
		$content_block          = end( $item['innerBlocks'] );
		$rendered_content_block = ( new WP_Block( $content_block, $context ) )->render();
		$p                      = new WP_HTML_Tag_Processor( $rendered_content_block );

		$has_content = $p->next_tag( 'img' ) ||
			$p->next_tag( 'iframe' ) ||
			$p->next_tag( 'video' ) ||
			$p->next_tag( 'meter' ) ||
			! empty( wp_strip_all_tags( $rendered_content_block, true ) );

		if ( ! $has_content ) {
			return array();
		}

		return $item;
	}

	/**
	 * Check if a parsed block has an accordion.
	 *
	 * @param array $parsed_block Parsed block.
	 *
	 * @return bool True if the block has an accordion, false otherwise.
	 */
	private function has_accordion( $parsed_block ) {
		if ( 'core/accordion' === $parsed_block['blockName'] || 'woocommerce/accordion-group' === $parsed_block['blockName'] ) {
			return true;
		}

		foreach ( $parsed_block['innerBlocks'] as $inner_block ) {
			if ( $this->has_accordion( $inner_block ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the first accordion anchor block in a parsed block.
	 *
	 * @param array $parsed_block Parsed block.
	 *
	 * @return array|null Accordion anchor block or null.
	 */
	private function get_accordion_anchor_block( $parsed_block ) {
		if ( 'core/accordion' === $parsed_block['blockName'] || 'woocommerce/accordion-group' === $parsed_block['blockName'] ) {
			return $parsed_block;
		}

		foreach ( $parsed_block['innerBlocks'] as $inner_block ) {
			$anchor_block = $this->get_accordion_anchor_block( $inner_block );
			if ( $anchor_block ) {
				return $anchor_block;
			}
		}

		return null;
	}

	/**
	 * Validate hooked blocks data. Remove duplicated entries with the same title
	 * and invalid entries with invalid content. Log errors to the WC logger.
	 *
	 * @param array $hooked_blocks { Hooked blocks data.
	 *   @type string $title Title of the hooked block.
	 *   @type string $content Content of the hooked block, as block markup.
	 * }
	 *
	 * @return array Validated hooked blocks.
	 */
	private function validate_hooked_blocks( $hooked_blocks ) {
		$logger                  = wc_get_logger();
		$validated_hooked_blocks = [];

		foreach ( $hooked_blocks as $block ) {
			$invalid = ! is_array( $block ) ||
				! isset( $block['title'] ) ||
				! isset( $block['content'] ) ||
				! is_string( $block['title'] ) ||
				! is_string( $block['content'] );

			if ( ! $invalid ) {
				$parsed_content = parse_blocks( $block['content'] );

				foreach ( $parsed_content as $content_block ) {
					if ( ! isset( $content_block['blockName'] ) ) {
						$invalid = true;
						break;
					}
				}
			}

			if ( $invalid ) {
				$logger->error( 'Invalid hooked block data. Expected array with `title` and `content` keys with string values. Content must be valid block markup.', $block );
				continue;
			}

			$slug = sanitize_title( $block['title'] );

			/**
			 * If the block is already registered, replace the block. We use the
			 * last registered block for the same slug. This makes overriding
			 * hooked block easier.
			 */
			if ( isset( $validated_hooked_blocks[ $slug ] ) ) {
				$validated_hooked_blocks[ $slug ] = $block;
				continue;
			}

			$validated_hooked_blocks[ $slug ] = $block;
		}

		return $validated_hooked_blocks;
	}

	/**
	 * Register a product details item using Block Hooks API.
	 *
	 * @param string $slug The slug of the item.
	 * @param array  $block The block data.
	 * @return void
	 */
	private function register_hooked_block( $slug, $block ) {
		add_filter(
			'hooked_block_types',
			function ( $hooked_block_types, $relative_position, $anchor_block_type ) use ( $slug ) {
				if (
					( 'core/accordion' === $anchor_block_type || 'woocommerce/accordion-group' === $anchor_block_type ) &&
					'last_child' === $relative_position &&
					! in_array( $slug, $hooked_block_types, true )
				) {
					$hooked_block_types[] = $slug;
				}
				return $hooked_block_types;
			},
			10,
			3
		);

		add_filter(
			"hooked_block_{$slug}",
			function ( $parsed_hooked_block, $hooked_block_type, $relative_position, $parsed_anchor_block ) use ( $block ) {
				if (
					is_null( $parsed_hooked_block ) ||
					( 'core/accordion' !== $parsed_anchor_block['blockName'] && 'woocommerce/accordion-group' !== $parsed_anchor_block['blockName'] ) ||
					'last_child' !== $relative_position ||
					empty( $parsed_anchor_block['attrs']['metadata']['isDescendantOfProductDetails'] )
				) {
					return null;
				}

				return $this->create_accordion_item_block( $block['title'], $block['content'], $parsed_anchor_block );
			},
			10,
			4
		);
	}

	/**
	 * Enqueue legacy assets when this block is used as we don't enqueue them for block themes anymore.
	 *
	 * @see https://github.com/woocommerce/woocommerce/pull/60223
	 */
	public function enqueue_legacy_assets() {
		wp_enqueue_script( 'wc-single-product' );
	}

	/**
	 * Previously, the Product Details block was a standalone block. It doesn't
	 * have any inner blocks and it rendered the tabs directly like the classic
	 * template. When upgrading, we want the existing stores using the block to
	 * continue working as before, so we moved the logic the legacy render
	 * method here.
	 *
	 * @see https://github.com/woocommerce/woocommerce/pull/59005
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 *
	 * @return string Rendered block output.
	 */
	protected function render_legacy_block( $attributes, $content, $block ) {
		if ( ! is_singular( 'product' ) ) {
			return $content;
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_legacy_assets' ], 20 );

		$hide_tab_title = isset( $attributes['hideTabTitle'] ) ? $attributes['hideTabTitle'] : false;

		if ( $hide_tab_title ) {
			add_filter( 'woocommerce_product_description_heading', '__return_empty_string' );
			add_filter( 'woocommerce_product_additional_information_heading', '__return_empty_string' );
			add_filter( 'woocommerce_reviews_title', '__return_empty_string' );
		}

		$tabs = $this->render_tabs();

		if ( $hide_tab_title ) {
			remove_filter( 'woocommerce_product_description_heading', '__return_empty_string' );
			remove_filter( 'woocommerce_product_additional_information_heading', '__return_empty_string' );
			remove_filter( 'woocommerce_reviews_title', '__return_empty_string' );

			// Remove the first `h2` of every `.wc-tab`. This is required for the Reviews tabs when there are no reviews and for plugin tabs.
			$tabs_html = new WP_HTML_Tag_Processor( $tabs );
			while ( $tabs_html->next_tag( array( 'class_name' => 'wc-tab' ) ) ) {
				if ( $tabs_html->next_tag( 'h2' ) ) {
					$tabs_html->set_attribute( 'hidden', 'true' );
				}
			}
			$tabs = $tabs_html->get_updated_html();
		}

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );

		return sprintf(
			'<div class="wp-block-woocommerce-product-details %1$s">
				<div style="%2$s">
					%3$s
				</div>
			</div>',
			esc_attr( $classes_and_styles['classes'] ),
			esc_attr( $classes_and_styles['styles'] ),
			$tabs
		);
	}

	/**
	 * Gets the tabs with their content to be rendered by the block.
	 *
	 * @return string The tabs html to be rendered by the block
	 */
	protected function render_tabs() {
		ob_start();
		rewind_posts();
		while ( have_posts() ) {
			the_post();
			woocommerce_output_product_data_tabs();
		}

		$tabs = ob_get_clean();

		return $tabs;
	}
}
