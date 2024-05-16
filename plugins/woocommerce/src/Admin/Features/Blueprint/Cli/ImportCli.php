<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Cli;

use Automattic\WooCommerce\Admin\Features\Blueprint\CliResultFormatter;
use Automattic\WooCommerce\Admin\Features\Blueprint\ImportSchema;

class ImportCli {
	private $schema_path;
	public function __construct($schema_path) {
		$this->schema_path = $schema_path;
	}

	public function run($optional_args) {
	    $blueprint = ImportSchema::crate_from_file($this->schema_path);
		$results = $blueprint->process();

		$result_formatter = new CliResultFormatter($results);
		$is_success = $result_formatter->is_success();

		if (isset($optional_args['show-messages'])) {
			$result_formatter->format($optional_args['show-messages']);
		}

		$is_success && \WP_CLI::success("$this->schema_path imported successfully");
		!$is_success && \WP_CLI::error("Failed to import $this->schema_path. Run with --message=all to debug");
	}
}
