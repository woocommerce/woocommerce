<?php

namespace Automattic\WooCommerce\MonorepoTools\Changelogger;

use Automattic\Jetpack\Changelogger\FormatterPlugin;

/**
 * Jetpack Changelogger Formatter for WooCommerce packages
 */

require_once 'Formatter.php';

/**
 * Jetpack Changelogger Formatter for WooCommerce Packages
 *
 * Class Formatter
 */
class PackageFormatter extends Formatter implements FormatterPlugin {
	/**
	 * Prologue text.
	 *
	 * @var string
	 */
	public $prologue = "# Changelog \n\nThis project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).";

	/**
	 * Return the epilogue string based on the package being released.
	 */
	public function getEpilogue() {
		$cwd = getcwd();
		$pos = stripos( $cwd, 'packages/js/' );
		$package = substr( $cwd, $pos + 12 );

		return '[See legacy changelogs for previous versions](https://github.com/woocommerce/woocommerce/blob/68581955106947918d2b17607a01bdfdf22288a9/packages/js/' . $package . '/CHANGELOG.md).';
	}
}
