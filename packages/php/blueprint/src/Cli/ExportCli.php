<?php

namespace Automattic\WooCommerce\Blueprint\Cli;

use Automattic\WooCommerce\Blueprint\ExportSchema;
use Automattic\WooCommerce\Blueprint\ZipExportedSchema;

class ExportCli {
	private string $save_to;
	public function __construct($save_to) {
		$this->save_to = $save_to;
	}

	public function run($args = array()) {
		$export_as_zip = isset($args['format']) && $args['format'] === 'zip';
		if ( ! isset( $args['steps'] ) ) {
			$args['steps'] = array();
		}

		$exporter = new ExportSchema();

		$schema = $exporter->export($args['steps'], $export_as_zip);

		if ($export_as_zip) {
			$zipExportedSchema = new ZipExportedSchema($schema);
			$this->save_to = $zipExportedSchema->zip();
		} else {
			file_put_contents( $this->save_to, json_encode( $schema, JSON_PRETTY_PRINT ) );
		}

		\WP_CLI::success("Exported to {$this->save_to}");
	}
}
