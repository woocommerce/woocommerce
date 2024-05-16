<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Cli;

use Automattic\WooCommerce\Admin\Features\Blueprint\ExportSchema;
use Automattic\WooCommerce\Admin\Features\Blueprint\ZipExportedSchema;

class ExportCli {
	private string $save_to;
	public function __construct($save_to) {
		$this->save_to = $save_to;
	}

	public function run($args = array()) {
		$export_as_zip = isset($args['format']) && $args['format'] === 'zip';

		$exporter = new ExportSchema();

		if ($export_as_zip) {
			$exporter->get_exporter('installPlugins')->include_private_plugins(true);
		}

		$schema = $exporter->export($args['steps']);


		if ($export_as_zip) {
			$zipExportedSchema = new ZipExportedSchema($schema);
			$this->save_to = $zipExportedSchema->zip();
		} else {
			file_put_contents( $this->save_to, json_encode( $schema, JSON_PRETTY_PRINT ) );
		}

		\WP_CLI::success("Exported to {$this->save_to}");
	}
}
