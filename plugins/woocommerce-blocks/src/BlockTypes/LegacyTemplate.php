<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Legacy Single Product class
 *
 * @internal
 */
class LegacyTemplate extends AbstractDynamicBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'legacy-template';

	/**
	 * API version.
	 *
	 * @var string
	 */
	protected $api_version = '2';

	/**
	 * Render method for the Legacy Template block. This method will determine which template to render.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 *
	 * @return string | void Rendered block type output.
	 */
	protected function render( $attributes, $content ) {
		if ( null === $attributes['template'] ) {
			return;
		}

		if ( 'single-product' === $attributes['template'] ) {
			return $this->render_single_product();
		} else {
			ob_start();

			echo "You're using the LegacyTemplate block";

			wp_reset_postdata();
			return ob_get_clean();
		}
	}

	/**
	 * Render method for the single product template and parts.
	 *
	 * @return string Rendered block type output.
	 */
	protected function render_single_product() {
		ob_start();

		echo 'This method will render the single product template';

		wp_reset_postdata();
		return ob_get_clean();
	}
}
