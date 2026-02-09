<?php
declare( strict_types=1 );

/**
 * Package_Formatter class
 *
 * @package  WooCommerce
 */

namespace Automattic\WooCommerce\MonorepoTools\Changelogger;

use Automattic\Jetpack\Changelogger\FormatterPlugin;

/**
 * Jetpack Changelogger Formatter for WooCommerce packages
 */

require_once 'class-formatter.php';

/**
 * Jetpack Changelogger Formatter for WooCommerce Packages
 *
 * Class Formatter
 */
class Php_Package_Formatter extends Formatter implements FormatterPlugin {
	/**
	 * Prologue text.
	 *
	 * @var string
	 */
	public $prologue = "# Changelog \n\nThis project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).";

	/**
	 * Get Release link given a version number.
	 *
	 * @throws \InvalidArgumentException When cannot find the package name.
	 * @param string $version Release version.
	 *
	 * @return string Link to the version's release.
	 */
	public function getReleaseLink( string $version ): string {
		$composer_config = json_decode( file_get_contents( getcwd() . '/composer.json' ), true );
		$package_name = $composer_config['name'] ?? null;

		if ( ! $package_name ) {
			throw new \InvalidArgumentException( "Can't find package name in composer.json." );
		}

		return 'https://github.com/' .  $package_name . '/releases/tag/' . $version;
	}
}
