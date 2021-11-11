<?php
/**
 * Jetpack Changelogger Formatter for WooCommerce packages
 */

require_once 'Formatter.php';

/**
 * Jetpack Changelogger Formatter for WooCommerce Packages
 *
 * Class Formatter
 */
class PackageFormatter extends Formatter {
	/**
	 * Prologue text.
	 *
	 * @var string
	 */
	public $prologue = "# Changelog \n\nThis project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).";

	/**
	 * Epilogue text.
	 *
	 * @var string
	 */
	public $epilogue = "---\n\n[See legacy changelogs for previous versions](https://github.com/woocommerce/woocommerce-admin/blob/main/packages/components/CHANGELOG.md).";
}
