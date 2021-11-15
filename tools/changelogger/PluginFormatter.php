<?php
/**
 * Jetpack Changelogger Formatter for WooCommerce plugins
 */

require_once 'Formatter.php';

/**
 * Jetpack Changelogger Formatter for WooCommerce Plugins
 *
 * Class Formatter
 */
class PluginFormatter extends Formatter {
	/**
	 * Epilogue text.
	 *
	 * @var string
	 */
	public $epilogue = "---\n\n[See changelogs for previous versions](https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/changelog.txt).";

	/**
	 * Entry pattern regex.
	 *
	 * @var string
	 */
	public $entry_pattern = '/^##?#\s+([^\n=]+)\s+((?:(?!^##).)+)/ms';
} 
