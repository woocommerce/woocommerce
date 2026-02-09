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
		$parsed_blocks = $this->add_block_gaps( $parsed_blocks, $styles['spacing']['blockGap'] ?? '', null );
		return $parsed_blocks;
	}

	/**
	 * Adds margin-top to blocks that are not first or last in the columns block.
	 *
	 * @param array      $parsed_blocks Parsed blocks.
	 * @param string     $gap Gap.
	 * @param array|null $parent_block Parent block.
	 * @return array
	 */
	private function add_block_gaps( array $parsed_blocks, string $gap = '', $parent_block = null ): array {
		foreach ( $parsed_blocks as $key => $block ) {
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

			$block['innerBlocks']  = $this->add_block_gaps( $block['innerBlocks'] ?? array(), $gap, $block );
			$parsed_blocks[ $key ] = $block;
		}

		return $parsed_blocks;
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
