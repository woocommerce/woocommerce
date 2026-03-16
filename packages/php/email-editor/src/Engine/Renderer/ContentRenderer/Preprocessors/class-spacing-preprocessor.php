<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors;

/**
 * This preprocessor is responsible for setting default spacing values for blocks.
 * In the early development phase, we are setting only margin-top for blocks that are not first or last in the columns block.
 */
class Spacing_Preprocessor implements Preprocessor {
	/**
	 * Preprocesses the parsed blocks.
	 *
	 * @param array $parsed_blocks Parsed blocks.
	 * @param array $layout Layout.
	 * @param array $styles Styles.
	 * @return array
	 */
	public function preprocess( array $parsed_blocks, array $layout, array $styles ): array {
		$root_padding = $this->get_root_padding( $styles );

		$parsed_blocks = $this->add_block_gaps( $parsed_blocks, $styles['spacing']['blockGap'] ?? '', null, $root_padding );
		return $parsed_blocks;
	}

	/**
	 * Container block names that delegate root padding to their children
	 * instead of receiving it themselves.
	 */
	private const CONTAINER_BLOCKS = array( 'core/group', 'core/post-content' );

	/**
	 * Adds spacing to blocks: margin-top for vertical gaps, horizontal padding for
	 * column gaps, and root padding for children of root-level containers.
	 *
	 * Root padding is distributed from the outer email wrapper to individual block
	 * wrappers. Container blocks (groups, post-content) at the root level delegate
	 * padding to their children instead of taking it themselves. This enables
	 * alignfull blocks to skip root padding and span the full email width.
	 *
	 * @param array      $parsed_blocks Parsed blocks.
	 * @param string     $gap Gap.
	 * @param array|null $parent_block Parent block.
	 * @param array      $root_padding Root horizontal padding with 'left' and 'right' keys.
	 * @param bool       $apply_root_padding Whether this block should receive root padding (delegated by parent container).
	 * @return array
	 */
	private function add_block_gaps( array $parsed_blocks, string $gap = '', $parent_block = null, array $root_padding = array(), bool $apply_root_padding = false ): array {
		foreach ( $parsed_blocks as $key => $block ) {
			$block_name        = $block['blockName'] ?? '';
			$parent_block_name = $parent_block['blockName'] ?? '';
			// Ensure that email_attrs are set.
			$block['email_attrs'] = $block['email_attrs'] ?? array();

			/**
			 * Do not add a gap to:
			 * - first child
			 * - parent block is a buttons block (where buttons are side by side).
			 */
			if ( 0 !== $key && $gap && 'core/buttons' !== $parent_block_name ) {
				$block['email_attrs']['margin-top'] = $gap;
			}

			// Handle horizontal gap for columns: apply padding-left to column children (except the first).
			if ( 'core/columns' === $parent_block_name && 0 !== $key && null !== $parent_block ) {
				$columns_gap = $this->get_columns_block_gap( $parent_block, $gap );
				if ( $columns_gap ) {
					$block['email_attrs']['padding-left'] = $columns_gap;
				}
			}

			// Distribute root horizontal padding.
			// Container blocks (group, post-content) at root level do NOT get padding;
			// they delegate it to their children. Non-container blocks at root level
			// (e.g., columns, paragraph) get padding directly.
			// Blocks flagged with $apply_root_padding (children of root containers)
			// also get padding, unless they are post-content or a container wrapping
			// post-content (both delegate further down the tree).
			// Blocks that explicitly define their own horizontal padding are managing
			// their own layout and skip root padding entirely. Containers with explicit
			// padding also stop delegation so their children follow the container's padding.
			$is_root_level      = null === $parent_block;
			$is_container       = in_array( $block_name, self::CONTAINER_BLOCKS, true );
			$alignment          = $block['attrs']['align'] ?? null;
			$has_own_padding    = $this->has_explicit_horizontal_padding( $block );
			$wraps_post_content = $apply_root_padding && $is_container && ! $has_own_padding && $this->contains_post_content( $block );
			$should_apply       = $apply_root_padding || ( $is_root_level && ! $is_container );

			if ( $should_apply && ! $has_own_padding && 'full' !== $alignment && 'core/post-content' !== $block_name && ! $wraps_post_content && ! empty( $root_padding ) ) {
				$block['email_attrs']['padding-left']  = $root_padding['left'];
				$block['email_attrs']['padding-right'] = $root_padding['right'];
			}

			// Determine whether children should receive root padding delegation.
			// Root-level containers delegate to their children.
			// Post-content blocks that received delegation also pass it through.
			// Containers wrapping post-content that received delegation also delegate,
			// so that user blocks inside post-content get padding individually.
			// Containers with explicit horizontal padding stop delegation — they
			// manage their own layout.
			$children_apply = false;
			if ( $is_root_level && $is_container && ! $has_own_padding ) {
				$children_apply = true;
			} elseif ( $apply_root_padding && 'core/post-content' === $block_name ) {
				$children_apply = true;
			} elseif ( $wraps_post_content ) {
				$children_apply = true;
			}

			$block['innerBlocks']  = $this->add_block_gaps( $block['innerBlocks'] ?? array(), $gap, $block, $root_padding, $children_apply );
			$parsed_blocks[ $key ] = $block;
		}

		return $parsed_blocks;
	}

	/**
	 * Checks whether a block contains a core/post-content descendant.
	 *
	 * Searches recursively through container blocks (groups) so that
	 * deeply nested template structures like group → group → post-content
	 * are handled correctly.
	 *
	 * @param array $block The block to check.
	 * @return bool True if the block has a post-content descendant.
	 */
	private function contains_post_content( array $block ): bool {
		foreach ( $block['innerBlocks'] ?? array() as $inner_block ) {
			$name = $inner_block['blockName'] ?? '';
			if ( 'core/post-content' === $name ) {
				return true;
			}
			if ( in_array( $name, self::CONTAINER_BLOCKS, true ) && $this->contains_post_content( $inner_block ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks whether a block explicitly defines its own horizontal padding.
	 *
	 * When a block has explicit padding-left or padding-right in its style
	 * attributes, it is managing its own layout. Root padding should not
	 * be added on top, and containers with explicit padding should not
	 * delegate root padding to their children.
	 *
	 * @param array $block The block to check.
	 * @return bool True if the block defines horizontal padding.
	 */
	private function has_explicit_horizontal_padding( array $block ): bool {
		$padding = $block['attrs']['style']['spacing']['padding'] ?? array();
		return isset( $padding['left'] ) || isset( $padding['right'] );
	}

	/**
	 * Extracts and sanitizes root horizontal padding from theme styles.
	 *
	 * @param array $styles Theme styles.
	 * @return array Root padding with 'left' and 'right' keys, or empty array if invalid.
	 */
	private function get_root_padding( array $styles ): array {
		$padding   = $styles['spacing']['padding'] ?? array();
		$has_left  = isset( $padding['left'] );
		$has_right = isset( $padding['right'] );

		// If neither horizontal padding key is defined, skip root padding entirely.
		if ( ! $has_left && ! $has_right ) {
			return array();
		}

		$left  = $has_left ? $padding['left'] : '0px';
		$right = $has_right ? $padding['right'] : '0px';

		// Validate against potentially malicious values.
		if ( ! is_string( $left ) || ! is_string( $right ) || preg_match( '/[<>"\']/', $left . $right ) ) {
			return array();
		}

		return array(
			'left'  => $left,
			'right' => $right,
		);
	}

	/**
	 * Extracts the horizontal blockGap from a columns block.
	 *
	 * @param array  $columns_block The columns block.
	 * @param string $default_gap Default gap value to use if blockGap is not set on the columns block.
	 * @return string|null The horizontal gap value (e.g., "30px" or "var:preset|spacing|30") or null if not set.
	 */
	private function get_columns_block_gap( array $columns_block, string $default_gap = '' ): ?string {
		$block_gap = $columns_block['attrs']['style']['spacing']['blockGap'] ?? null;

		// Columns block uses object format: { "top": "...", "left": "..." }.
		// If blockGap.left is explicitly set, use it.
		if ( is_array( $block_gap ) && isset( $block_gap['left'] ) && is_string( $block_gap['left'] ) ) {
			$gap_value = $block_gap['left'];

			// Validate against potentially malicious values.
			if ( preg_match( '/[<>"\']/', $gap_value ) ) {
				return null;
			}

			// Return the value as-is. WP's styles engine will handle transformation of preset variables.
			return $gap_value;
		}

		// If blockGap.left is not set, use the default gap value if provided.
		if ( $default_gap ) {
			return $default_gap;
		}

		return null;
	}
}
