<?php
/**
 * OptionsStore class file
 *
 * @package  WooCommerceDocs
 */

namespace WooCommerceDocs\Data;

/**
 * A class to store options for configuring the plugin. Options are stored in the WordPress options table.
 */
class OptionsStore {
	const OPTIONS_PREFIX = 'woocommerce_docs_';

	/**
	 * Retrieve a plugin option
	 */
	public static function get_option( $option_name ) {
		return get_option( self::OPTIONS_PREFIX . $option_name );
	}

	/**
	 * Save a plugin option
	 *
	 * @param string $option_name The name of the option to save.
	 * @param mixed  $option_value The value of the option to save.
	 */
	public static function save_option( $option_name, $option_value ) {
		update_option( self::OPTIONS_PREFIX . $option_name, $option_value );
	}
}
