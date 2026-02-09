<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Options;

/**
 * TemplateOptions class.
 *
 * @internal
 */
class TemplateOptions {

	/**
	 * Initialization method.
	 */
	public function init() {
		add_action( 'after_switch_theme', array( $this, 'check_should_use_blockified_product_grid_templates' ), 10, 2 );
	}

	/**
	 * Checks the old and current themes and determines if the "wc_blocks_use_blockified_product_grid_block_as_template"
	 * option need to be updated accordingly.
	 *
	 * @param string    $old_name Old theme name.
	 * @param \WP_Theme $old_theme Instance of the old theme.
	 * @return void
	 */
	public function check_should_use_blockified_product_grid_templates( $old_name, $old_theme ) {
		if ( ! $old_theme->is_block_theme() && wp_is_block_theme() ) {
			$option_name = Options::WC_BLOCK_USE_BLOCKIFIED_PRODUCT_GRID_BLOCK_AS_TEMPLATE;
			// We previously stored "yes" or "no" values. This will convert them to true or false.
			$option_value = wc_string_to_bool( get_option( $option_name ) );

			// We don't need to do anything if the option is already set to true.
			if ( ! $option_value ) {
				update_option( $option_name, true );
			}
		}
	}
}
