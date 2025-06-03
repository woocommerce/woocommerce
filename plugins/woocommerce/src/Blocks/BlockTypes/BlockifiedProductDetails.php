<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use WP_Block;
use WP_HTML_Tag_Processor;

/**
 * BlockifiedProductDetails class.
 */
class BlockifiedProductDetails extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'blockified-product-details';

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
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

		foreach ( $product_tabs as $key => $tab ) {
			ob_start();
			call_user_func( $tab['callback'], $key, $tab );
			$tab_content        = ob_get_clean();
			$accordion_blocks[] = $this->create_accordion_item_block(
				$tab['title'],
				'<!-- wp:html -->' . $tab_content . '<!-- /wp:html -->'
			);
		}

		return $this->inject_parsed_accordion_blocks( $parsed_block, $accordion_blocks );
	}

	/**
	 * Create an accordion item block.
	 *
	 * @param string $title Title of the accordion item.
	 * @param string $content Content of the accordion item as block markup.
	 *
	 * @return array Accordion item.
	 */
	private function create_accordion_item_block( $title, $content ) {
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
		if ( 'woocommerce/accordion-group' === $parsed_block['blockName'] ) {
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

		if ( 'woocommerce/accordion-group' === $parsed_block['blockName'] ) {
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
		if ( 'woocommerce/accordion-group' === $parsed_block['blockName'] ) {
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
	 * Get the frontend script handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string|null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}
}
