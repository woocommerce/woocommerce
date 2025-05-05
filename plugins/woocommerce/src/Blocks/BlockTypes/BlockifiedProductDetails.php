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
		return $this->hide_empty_accordion_items( $content, $block );
	}

	/**
	 * Hide empty accordion items.
	 *
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 *
	 * @return string Rendered block output.
	 */
	private function hide_empty_accordion_items( $content, $block ) {
		$accordion_items = $this->find_accordion_items( $block->parsed_block );

		if ( ! $accordion_items ) {
			return $content;
		}

		$accordion_items_visibility = array_map(
			function ( $item ) use ( $block ) {
				$content_block          = end( $item['innerBlocks'] );
				$rendered_content_block = ( new WP_Block( $content_block, $block->context ) )->render();
				$p                      = new WP_HTML_Tag_Processor( $rendered_content_block );

				return $p->next_tag( 'img' ) ||
					$p->next_tag( 'iframe' ) ||
					$p->next_tag( 'video' ) ||
					$p->next_tag( 'meter' ) ||
					! empty( wp_strip_all_tags( $rendered_content_block, true ) );
			},
			$accordion_items
		);

		$p = new WP_HTML_Tag_Processor( $content );

		$counter = 0;
		while ( $p->next_tag( array( 'class_name' => 'wp-block-woocommerce-accordion-item' ) ) ) {
			if ( ! $accordion_items_visibility[ $counter ] ) {
				$p->set_attribute( 'style', 'display:none;' );
				$p->set_attribute( 'hidden', true );
			}
			++$counter;
		}

		return $p->get_updated_html();
	}

	/**
	 * Find accordion items.
	 *
	 * @param array $block Block instance.
	 *
	 * @return array|false Accordion items.
	 */
	private function find_accordion_items( $block ) {
		if ( 'woocommerce/accordion-group' === $block['blockName'] ) {
			return $block['innerBlocks'];
		}

		foreach ( $block['innerBlocks'] as $inner_block ) {
			$items = $this->find_accordion_items( $inner_block );
			if ( $items ) {
				return $items;
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
