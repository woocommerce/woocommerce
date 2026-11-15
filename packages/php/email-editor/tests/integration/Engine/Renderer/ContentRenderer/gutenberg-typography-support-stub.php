<?php
/**
 * Stand-in for the Gutenberg plugin's typography support callback, which isn't installed in tests.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );

if ( ! function_exists( 'gutenberg_render_typography_support' ) ) {
	/**
	 * Marks every block it runs on with the block name, so a test can tell whether it ran.
	 *
	 * @param string $block_content Rendered block content.
	 * @param array  $block Block object.
	 * @return string
	 */
	function gutenberg_render_typography_support( $block_content, $block ) {
		return $block_content . '<!-- gutenberg-typography-support ' . ( $block['blockName'] ?? '' ) . ' -->';
	}
}
