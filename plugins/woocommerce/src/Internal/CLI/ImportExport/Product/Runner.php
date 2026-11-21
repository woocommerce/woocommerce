<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\ImportExport\Product;

use WP_CLI;

/**
 * Registers the `wp wc product import` and `wp wc product export` WP-CLI commands.
 *
 * @since 11.4.0
 */
final class Runner {

	/**
	 * The import command.
	 *
	 * @var ImportCommand
	 */
	private ImportCommand $import_command;

	/**
	 * The export command.
	 *
	 * @var ExportCommand
	 */
	private ExportCommand $export_command;

	/**
	 * Initialize the runner with dependencies.
	 *
	 * @internal
	 *
	 * @param ImportCommand $import_command The import command.
	 * @param ExportCommand $export_command The export command.
	 */
	final public function init( ImportCommand $import_command, ExportCommand $export_command ): void { // phpcs:ignore Generic.CodeAnalysis.UnnecessaryFinalModifier.Found -- Required by WooCommerce injection method rules
		$this->import_command = $import_command;
		$this->export_command = $export_command;
	}

	/**
	 * Register the product CSV commands with WP-CLI.
	 */
	public function register_commands(): void {
		if ( ! class_exists( 'WP_CLI' ) ) {
			return;
		}

		WP_CLI::add_command(
			'wc product import',
			$this->import_command,
			array( 'shortdesc' => 'Import products from a CSV file.' )
		);

		WP_CLI::add_command(
			'wc product export',
			$this->export_command,
			array( 'shortdesc' => 'Export products to a CSV file.' )
		);
	}
}
