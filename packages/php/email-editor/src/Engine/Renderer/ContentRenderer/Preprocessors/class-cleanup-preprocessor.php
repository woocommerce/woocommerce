<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors;

/**
 * Class Cleanup_Preprocessor
 */
class Cleanup_Preprocessor implements Preprocessor {
	/**
	 * Method to preprocess the content before rendering
	 *
	 * @param array                                                                                                             $parsed_blocks Parsed blocks of the email.
	 * @param array{contentSize: string}                                                                                        $layout Layout of the email.
	 * @param array{spacing: array{padding: array{bottom: string, left: string, right: string, top: string}, blockGap: string}} $styles Styles of the email.
	 * @return array
	 */
	public function preprocess( array $parsed_blocks, array $layout, array $styles ): array {
		foreach ( $parsed_blocks as $key => $block ) {
			// https://core.trac.wordpress.org/ticket/45312
			// \WP_Block_Parser::parse_blocks() sometimes add a block with name null that can cause unexpected spaces in rendered content
			// This behavior was reported as an issue, but it was closed as won't fix.
			if ( null === $block['blockName'] && '' === trim( $block['innerHTML'] ?? '' ) ) {
				unset( $parsed_blocks[ $key ] );
			}
		}
		return array_values( $parsed_blocks );
	}
}
