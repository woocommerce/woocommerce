<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Options;

/**
 * OptionsManager class.
 *
 * @internal
 */
class OptionsManager {

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
	 * @return void
	 */
	public function check_should_use_blockified_product_grid_templates() {
		$option_name           = Options::WC_BLOCK_USE_BLOCKIFIED_PRODUCT_GRID_BLOCK_AS_TEMPLATE;
		$should_use_blockified = wc_bool_to_string( wc_current_theme_is_fse_theme() );

		// We don't need to do anything if the user switched to a classic theme or if the option is already set to true.
		if ( ! $should_use_blockified || get_option( $option_name ) === 'true' ) {
			return;
		}

		update_option( $option_name, wc_bool_to_string( $should_use_blockified ) );
	}
}
