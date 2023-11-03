<?php
/**
 * Legacy_Core_Formatter class
 *
 * @package  WooCommerce
 */

namespace Automattic\WooCommerce\MonorepoTools\Changelogger;

use Automattic\Jetpack\Changelog\Changelog;
use Automattic\Jetpack\Changelogger\FormatterPlugin;

/**
 * Jetpack Changelogger Formatter for WooCommerce plugins
 */

require_once 'class-formatter.php';

/**
 * Jetpack Changelogger Formatter for Legacy Core Changelog
 *
 * Class Formatter
 */
class Legacy_Core_Formatter extends Formatter implements FormatterPlugin {

	/**
	 * Bullet for changes.
	 *
	 * @var string
	 */
	public $bullet = '* ';


	/**
	 * Entry pattern regex.
	 *
	 * @var string
	 */
	public $entry_pattern = '/^##?#\s+([^\n=]+)\s+((?:(?!^##).)+)/ms';

	/**
	 * Returns an mapping the subheading to the type key.
	 *
	 * @return array
	 */
	private function getSubheadingTypeMapping() {
		$woocommerce_path = dirname( dirname( __DIR__ ) ) . '/plugins/woocommerce';
		$composer_file    = $woocommerce_path . '/composer.json';
		// phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$composer_config = json_decode( file_get_contents( $composer_file ), true );
		return array_flip( $composer_config['extra']['changelogger']['types'] );
	}

	/**
	 * Write a Changelog object to a string.
	 *
	 * @param Changelog $changelog Changelog object.
	 * @return string
	 */
	public function format( Changelog $changelog ) {
		$ret            = '';
		$bullet         = $this->bullet;
		$indent         = str_repeat( ' ', strlen( $bullet ) );
		$subheading_map = $this->getSubheadingTypeMapping();

		foreach ( $changelog->getEntries() as $entry ) {
			$version = $entry->getVersion();
			if ( substr_count( $version, '.' ) === 1 ) {
				$version .= '.0';
			}

			$ret .= "= $version YYYY-mm-dd =\n\n";
			$ret .= "**WooCommerce**\n\n";

			foreach ( $entry->getChangesBySubheading() as $heading => $changes ) {
				foreach ( $changes as $change ) {
					$text = trim( $change->getContent() );
					$type = isset( $subheading_map[ $heading ] ) ? $subheading_map[ $heading ] : 'update';
					if ( '' !== $text ) {
						$preamble = $bullet . ucfirst( $type ) . ' - ';
						$ret     .= $preamble . str_replace( "\n", "\n$indent", $text ) . "\n";
					}
				}
			}
			$ret = trim( $ret ) . "\n\n";
		}

		$ret = trim( $ret ) . "\n";

		return $ret;
	}
}
