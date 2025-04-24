<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors;

/**
 * Class Quote_Text_Align_Preprocessor
 */
class Quote_Text_Align_Preprocessor implements Preprocessor {
	/**
	 * Method to preprocess the content before rendering
	 *
	 * @param array                                                                                                             $parsed_blocks Parsed blocks of the email.
	 * @param array{contentSize: string}                                                                                        $layout Layout of the email.
	 * @param array{spacing: array{padding: array{bottom: string, left: string, right: string, top: string}, blockGap: string}} $styles Styles of the email.
	 * @return array
	 */
	public function preprocess( array $parsed_blocks, array $layout, array $styles ): array {
		return $this->process_blocks( $parsed_blocks );
	}

	/**
	 * Recursively process blocks to handle quote block alignment
	 *
	 * @param array $blocks The blocks to process.
	 * @return array The processed blocks.
	 */
	private function process_blocks( array $blocks ): array {
		foreach ( $blocks as &$block ) {
			if ( 'core/quote' === $block['blockName'] ) {
				$quote_align = $block['attrs']['textAlign'] ?? null;

				if ( $quote_align && isset( $block['innerBlocks'] ) ) {
					$block['innerBlocks'] = $this->apply_alignment_to_children( $block['innerBlocks'], $quote_align );
				}
			}

			if ( isset( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = $this->process_blocks( $block['innerBlocks'] );
			}
		}

		return $blocks;
	}

	/**
	 * Apply text alignment to child blocks that don't have their own text alignment set
	 *
	 * @param array  $blocks The blocks to process.
	 * @param string $text_align  The text alignment to apply.
	 * @return array The processed blocks.
	 */
	private function apply_alignment_to_children( array $blocks, string $text_align ): array {
		foreach ( $blocks as &$block ) {
			// Only apply alignment if the block doesn't already have one set.
			if ( ! isset( $block['attrs']['textAlign'] ) && ! isset( $block['attrs']['align'] ) ) {
				if ( ! isset( $block['attrs'] ) ) {
					$block['attrs'] = array();
				}
				$block['attrs']['textAlign'] = $text_align;
			}

			if ( isset( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = $this->apply_alignment_to_children( $block['innerBlocks'], $block['attrs']['textAlign'] ?? $block['attrs']['align'] );
			}
		}

		return $blocks;
	}
}
