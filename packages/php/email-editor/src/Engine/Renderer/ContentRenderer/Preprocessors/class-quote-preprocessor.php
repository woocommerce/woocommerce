<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors;

/**
 * Class Quote_Preprocessor
 */
class Quote_Preprocessor implements Preprocessor {
	/**
	 * Method to preprocess the content before rendering
	 *
	 * @param array                                                                                                             $parsed_blocks Parsed blocks of the email.
	 * @param array{contentSize: string}                                                                                        $layout Layout of the email.
	 * @param array{spacing: array{padding: array{bottom: string, left: string, right: string, top: string}, blockGap: string}} $styles Styles of the email.
	 * @return array
	 */
	public function preprocess( array $parsed_blocks, array $layout, array $styles ): array {
		return $this->process_blocks( $parsed_blocks, $styles );
	}

	/**
	 * Recursively process blocks to handle quote block alignment and typography
	 *
	 * @param array $blocks The blocks to process.
	 * @param array $styles The styles from the theme.
	 * @return array The processed blocks.
	 */
	private function process_blocks( array $blocks, array $styles ): array {
		foreach ( $blocks as &$block ) {
			if ( ! isset( $block['innerBlocks'] ) ) {
				continue;
			}
			if ( 'core/quote' === $block['blockName'] ) {
				$quote_align      = $block['attrs']['textAlign'] ?? null;
				$quote_typography = $block['attrs']['style']['typography'] ?? array();

				// Apply quote's text alignment to its children.
				$block['innerBlocks'] = $this->apply_alignment_to_children( $block['innerBlocks'], $quote_align );
				// Apply quote's typography to its children.
				$block['innerBlocks'] = $this->apply_typography_to_children( $block['innerBlocks'], $quote_typography, $styles );
			}

			$block['innerBlocks'] = $this->process_blocks( $block['innerBlocks'], $styles );
		}

		return $blocks;
	}

	/**
	 * Apply text alignment to child blocks that don't have their own text alignment set
	 *
	 * @param array       $blocks The blocks to process.
	 * @param string|null $text_align The text alignment to apply.
	 * @return array The processed blocks.
	 */
	private function apply_alignment_to_children( array $blocks, ?string $text_align = null ): array {
		if ( ! $text_align ) {
			return $blocks;
		}

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

	/**
	 * Apply typography styles to immediate paragraph children
	 *
	 * @param array $blocks The blocks to process.
	 * @param array $quote_typography The typography styles from the quote block.
	 * @param array $styles The styles from the theme.
	 * @return array The processed blocks.
	 */
	private function apply_typography_to_children( array $blocks, array $quote_typography, array $styles ): array {
		$default_typography = $styles['blocks']['core/quote']['typography'] ?? array();
		$merged_typography  = array_merge( $default_typography, $quote_typography );

		if ( empty( $merged_typography ) ) {
			return $blocks;
		}

		foreach ( $blocks as &$block ) {
			if ( 'core/paragraph' === $block['blockName'] ) {
				if ( ! isset( $block['attrs'] ) ) {
					$block['attrs'] = array();
				}
				if ( ! isset( $block['attrs']['style'] ) ) {
					$block['attrs']['style'] = array();
				}
				if ( ! isset( $block['attrs']['style']['typography'] ) ) {
					$block['attrs']['style']['typography'] = array();
				}

				// Merge typography styles, with block's own styles taking precedence.
				$block['attrs']['style']['typography'] = array_merge(
					$merged_typography,
					$block['attrs']['style']['typography']
				);
			}
		}

		return $blocks;
	}
}
