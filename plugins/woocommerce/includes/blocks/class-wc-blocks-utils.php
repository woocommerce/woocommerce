<?php
/**
 * Blocks Utils
 *
 * Used by core components that need to work with blocks.
 *
 * @package WooCommerce\Blocks\Utils
 * @version 5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Blocks Utility class.
 */
class WC_Blocks_Utils {

	/**
	 * Get blocks from a woocommerce page.
	 *
	 * @param string $woo_page_name A woocommerce page e.g. `checkout` or `cart`.
	 * @return array Array of blocks as returned by parse_blocks().
	 */
	private static function get_all_blocks_from_page( $woo_page_name ) {
		$page_id = wc_get_page_id( $woo_page_name );

		$page = get_post( $page_id );
		if ( ! $page ) {
			return array();
		}

		$blocks = parse_blocks( $page->post_content );
		if ( ! $blocks ) {
			return array();
		}

		return $blocks;
	}

	/**
	 * Get all instances of the specified block on a specific woo page
	 * (e.g. `cart` or `checkout` page).
	 *
	 * @param string $block_name The name (id) of a block, e.g. `woocommerce/cart`.
	 * @param string $woo_page_name The woo page to search, e.g. `cart`.
	 * @return array Array of blocks as returned by parse_blocks().
	 */
	public static function get_blocks_from_page( $block_name, $woo_page_name ) {
		$page_blocks = self::get_all_blocks_from_page( $woo_page_name );

		// Get any instances of the specified block.
		return array_values(
			array_filter(
				$page_blocks,
				function ( $block ) use ( $block_name ) {
					return ( $block_name === $block['blockName'] );
				}
			)
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
		$blocks   = parse_blocks( $template->content );

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

	/**
	 * Check if a given page contains a particular block.
	 *
	 * @param int|WP_Post $page Page post ID or post object.
	 * @param string      $block_name The name (id) of a block, e.g. `woocommerce/cart`.
	 * @return bool Boolean value if the page contains the block or not. Null in case the page does not exist.
	 */
	public static function has_block_in_page( $page, $block_name ) {
		$page_to_check = get_post( $page );
		if ( null === $page_to_check ) {
			return false;
		}

		$blocks = parse_blocks( $page_to_check->post_content );
		foreach ( $blocks as $block ) {
			if ( $block_name === $block['blockName'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return blocks with their inner blocks flattened.
	 *
	 * @param array $blocks Array of blocks as returned by parse_blocks().
	 * @return array All blocks.
	 */
	public static function flatten_blocks( $blocks ) {
		return array_reduce(
			$blocks,
			function( $carry, $block ) {
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
	 * @param array $block_name The name (id) of a block, e.g. `woocommerce/mini-cart`.
	 * @return array Array of blocks as returned by parse_blocks().
	 */
	public static function get_blocks_from_widget_area( $block_name ) {
		return array_reduce(
			get_option( 'widget_block' ),
			function ( $acc, $block ) use ( $block_name ) {
				$parsed_blocks = ! empty( $block ) && is_array( $block ) ? parse_blocks( $block['content'] ) : array();
				if ( ! empty( $parsed_blocks ) && $block_name === $parsed_blocks[0]['blockName'] ) {
					array_push( $acc, $parsed_blocks[0] );
					return $acc;
				}
				return $acc;
			},
			array()
		);
	}
}
