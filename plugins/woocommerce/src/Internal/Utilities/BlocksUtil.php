<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Utilities;

/**
 * Helper functions for working with blocks.
 */
class BlocksUtil {

	/**
	 * Return blocks with their inner blocks flattened.
	 *
	 * @param array $blocks Array of blocks as returned by parse_blocks().
	 * @return array All blocks.
	 */
	public static function flatten_blocks( $blocks ) {
		return array_reduce(
			$blocks,
			function ( $carry, $block ) {
				array_push( $carry, array_diff_key( $block, array_flip( array( 'innerBlocks' ) ) ) );
				if ( isset( $block['innerBlocks'] ) ) {
					$inner_blocks = self::flatten_blocks( $block['innerBlocks'] );
					return array_merge( $carry, $inner_blocks );
				}

				return $carry;
			},
			array()
		);
	}

	/**
	 * Get all instances of the specified block from the widget area.
	 *
	 * @param string $block_name The name (id) of a block, e.g. `woocommerce/mini-cart`.
	 * @return array Array of blocks as returned by parse_blocks().
	 */
	public static function get_blocks_from_widget_area( $block_name ) {
		$blocks = get_option( 'widget_block' );

		if ( ! is_array( $blocks ) || empty( $blocks ) ) {
			return array();
		}

		return array_reduce(
			$blocks,
			function ( $acc, $block ) use ( $block_name ) {
				$parsed_blocks = ! empty( $block['content'] ) ? parse_blocks( $block['content'] ) : array();
				if ( ! empty( $parsed_blocks ) && $block_name === $parsed_blocks[0]['blockName'] ) {
					array_push( $acc, $parsed_blocks[0] );
				}
				return $acc;
			},
			array()
		);
	}

	/**
	 * Get all instances of the specified block on a specific template part.
	 *
	 * @param string $block_name The name (id) of a block, e.g. `woocommerce/mini-cart`.
	 * @param string $template_part_slug The woo page to search, e.g. `header`.
	 * @return array Array of blocks as returned by parse_blocks().
	 */
	public static function get_block_from_template_part( $block_name, $template_part_slug ) {
		$template = get_block_template( get_stylesheet() . '//' . $template_part_slug, 'wp_template_part' );

		if ( ! $template || null === $template->content ) {
			return array();
		}

		$blocks = parse_blocks( $template->content );

		$flatten_blocks = self::flatten_blocks( $blocks );

		return array_values(
			array_filter(
				$flatten_blocks,
				function ( $block ) use ( $block_name ) {
					return ( $block_name === $block['blockName'] );
				}
			)
		);
	}
}
