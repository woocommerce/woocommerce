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

		$validated_hooked_blocks = array_filter(
			$hooked_blocks,
			function ( $block ) {
				return isset( $block['title'] ) && isset( $block['content'] ) && is_string( $block['title'] ) && is_string( $block['content'] );
			}
		);

		foreach ( $validated_hooked_blocks as $block ) {
			$this->register_hooked_blocks( $block['title'], $block['content'] );
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
		$parsed_block = $block->parsed_block;
		$parsed_block = $this->hide_empty_accordion_items( $parsed_block, $block->context );

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
			$openning_tag                 = reset( $parsed_block['innerContent'] );
			$closing_tag                  = end( $parsed_block['innerContent'] );
			$parsed_block['innerContent'] = array_merge(
				array( $openning_tag ),
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
		if (
			'woocommerce/accordion-group' === $parsed_block['blockName'] &&
			! empty( $parsed_block['attrs']['metadata']['isProductDetailsInnerBlock'] )
		) {
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
	 * Register a product details item using Block Hooks API.
	 *
	 * @param string $title The title of the item.
	 * @param string $content The content of the item.
	 * @return void
	 */
	private function register_hooked_blocks( $title, $content ) {
		$slug = sanitize_title( $title );

		add_filter(
			'hooked_block_types',
			function ( $hooked_block_types, $relative_position, $anchor_block_type ) use ( $slug ) {
				if ( 'woocommerce/accordion-group' === $anchor_block_type && 'last_child' === $relative_position ) {
					$hooked_block_types[] = $slug;
				}
				return $hooked_block_types;
			},
			10,
			3
		);

		add_filter(
			"hooked_block_{$slug}",
			function (
				$parsed_hooked_block,
				$hooked_block_type,
				$relative_position,
				$parsed_anchor_block
			) use (
				$title,
				$content
			) {

				if ( is_null( $parsed_hooked_block ) ) {
					return $parsed_hooked_block;
				}

				if (
					'woocommerce/accordion-group' !== $parsed_anchor_block['blockName'] ||
					'last_child' !== $relative_position ||
					! isset( $parsed_anchor_block['attrs']['metadata']['isProductDetailsInnerBlock'] ) ||
					! $parsed_anchor_block['attrs']['metadata']['isProductDetailsInnerBlock']
				) {
					return array();
				}

				return Accordion\AccordionItem::create_block(
					array(), // Attributes for Accordion Item block.
					array(
						// Inner blocks of the Accordion Item block: Header and Panel.
						Accordion\AccordionHeader::create_block( array( 'title' => $title ) ),
						Accordion\AccordionPanel::create_block( array(), parse_blocks( $content ) )
					)
				);
			},
			10,
			4
		);
	}
}
