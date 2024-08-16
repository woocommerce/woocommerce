<?php

namespace Automattic\WooCommerce\Blueprint\Cli;

use Automattic\WooCommerce\Blueprint\ExportSchema;
use Automattic\WooCommerce\Blueprint\ZipExportedSchema;

/**
 * Class ExportCli
 *
 * This class handles the CLI commands for exporting schemas.
 *
 * @package Automattic\WooCommerce\Blueprint\Cli
 */
class ExportCli {
	/**
	 * The path where the exported schema will be saved.
	 *
	 * @var string The path where the exported schema will be saved.
	 */
	private string $save_to;

	/**
	 * ExportCli constructor.
	 *
	 * @param string $save_to The path where the exported schema will be saved.
	 */
	public function __construct( $save_to ) {
		$this->save_to = $save_to;
	}

	/**
	 * Run the export process.
	 *
	 * @param array $args The arguments for the export process.
	 */
	public function run( $args = array() ) {
		$export_as_zip = isset( $args['format'] ) && 'zip' === $args['format'];
		if ( ! isset( $args['steps'] ) ) {
			$args['steps'] = array();
		}

		$exporter = new ExportSchema();

		$schema = $exporter->export( $args['steps'], $export_as_zip );

		if ( $export_as_zip ) {
			$zip_exported_schema = new ZipExportedSchema( $schema );
			$this->save_to       = $zip_exported_schema->zip();
			\WP_CLI::success( "Exported zip to {$this->save_to}" );
		} else {
			// phpcs:ignore
			$save = file_put_contents( $this->save_to, json_encode( $schema, JSON_PRETTY_PRINT ) );
			if ( false === $save ) {
				\WP_CLI::error( "Failed to save to {$this->save_to}" );
			} else {
				\WP_CLI::success( "Exported JSON to {$this->save_to}" );
			}
		}
	}
}
