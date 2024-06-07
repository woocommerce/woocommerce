<?php
/**
 * WordPressLayoutPolyfill class file.
 */

namespace Automattic\WooCommerce\Internal\Polyfills\BlockSupports;

use Automattic\WooCommerce\Internal\Compatibility\BaseWordPressPolyfill;
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;

/**
 * Class for polyfilling WordPress block supports.
 */
class WordPressLayoutPolyfill extends BaseWordPressPolyfill {
	use AccessiblePrivateMethods;

	/**
	 * Registers a polyfull for the `supports.layout` block feature.
	 */
	public function add_polyfill() {
		// Remove the existing layout support render functions
		// so that we can replace it with our own.
		if ( function_exists( 'wp_render_layout_support_flag' ) ) {
			remove_filter( 'render_block', 'wp_render_layout_support_flag' );
		}
		if ( function_exists( 'gutenberg_render_layout_support_flag' ) ) {
			remove_filter( 'render_block', 'gutenberg_render_layout_support_flag' );
		}
		self::add_filter( 'render_block', array( $this, 'render_layout_support_flag' ), 10, 2 );
	}

	/**
	 * Renders the layout config to the block wrapper.
	 *
	 * @param  string $block_content Rendered block content.
	 * @param  array  $block         Block object.
	 * @return string                Filtered block content.
	 */
	protected function render_layout_support_flag( $block_content, $block ) {
		// Make sure that we're using the built-in layout support flag
		// for blocks that don't need to be polyfilled.
		if ( ! $this->should_polyfill( $block ) ) {
			if ( function_exists( 'gutenberg_render_layout_support_flag' ) ) {
				return gutenberg_render_layout_support_flag( $block_content, $block );
			}
			if ( function_exists( 'wp_render_layout_support_flag' ) ) {
				return wp_render_layout_support_flag( $block_content, $block );
			}

			return $block_content;
		}

		return $this->render_layout( $block_content, $block );
	}

	/**
	 * Determines if the block should be polyfilled.
	 *
	 * @param array $block         Block object.
	 * @return bool
	 */
	private function should_polyfill( $block ) {
		if ( 'woocommerce/product-template' === $block['blockName'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Renders the layout config to the block wrapper.
	 *
	 * @param  string $block_content Rendered block content.
	 * @param  array  $block         Block object.
	 * @return string                Filtered block content.
	 */
	private function render_layout( $block_content, $block ) {
		return $block_content;
	}
}
