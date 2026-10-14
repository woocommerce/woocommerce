<?php
/**
 * Pattern header translation extraction.
 *
 * @package WooCommerce
 */

namespace Automattic\WooCommerce\Tools;

use WP_CLI\I18n\FileDataExtractor;

/**
 * Generates extractable PHP strings from pattern headers.
 *
 * @internal
 */
class Generate_Pattern_Translations {

	/**
	 * Generates a PHP translation source without executing pattern files.
	 *
	 * @since 11.3.0
	 *
	 * @param string $plugin_directory The plugin directory containing patterns.
	 * @return string
	 * @throws \RuntimeException When no pattern files are found.
	 */
	public function generate( string $plugin_directory ): string {
		$pattern_files = glob( $plugin_directory . '/patterns/*.php' );
		if ( empty( $pattern_files ) ) {
			throw new \RuntimeException( 'No pattern files found for translation extraction.' );
		}
		$strings = array();
		foreach ( $pattern_files as $pattern_file ) {
			$headers = FileDataExtractor::get_file_data(
				$pattern_file,
				array(
					'Pattern title'       => 'Title',
					'Pattern description' => 'Description',
				)
			);
			foreach ( $headers as $context => $text ) {
				if ( '' === $text ) {
					continue;
				}
				$strings[] = sprintf(
					"\t_x( %s, %s, 'woocommerce' ),\n",
					var_export( $text, true ), // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- Serialize PHP string literals for the generated file.
					var_export( $context, true ) // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- Serialize PHP string literals for the generated file.
				);
			}
		}
		sort( $strings, SORT_STRING );

		return "<?php\n/**\n * Pattern metadata strings for translation extraction.\n *\n * Generated from pattern headers by `composer generate-pattern-translations`.\n * This file is not loaded at runtime.\n *\n * @package WooCommerce\n */\n\ndefined( 'ABSPATH' ) || exit;\n\nreturn array(\n"
			. implode( '', $strings )
			. ");\n";
	}
}
