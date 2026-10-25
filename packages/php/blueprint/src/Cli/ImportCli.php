<?php

namespace Automattic\WooCommerce\Blueprint\Cli;

use Automattic\WooCommerce\Blueprint\ImportSchema;
use Automattic\WooCommerce\Blueprint\ResultFormatters\CliResultFormatter;

/**
 * Class ImportCli
 */
class ImportCli {
	/**
	 * Schema path
	 *
	 * @var string $schema_path The path to the schema file.
	 */
	private $schema_path;

	/**
	 * ImportCli constructor.
	 *
	 * @param string $schema_path The path to the schema file.
	 */
	public function __construct( $schema_path ) {
		$this->schema_path = $schema_path;
	}

	/**
	 * Run the import process.
	 *
	 * @param array $optional_args Optional arguments.
	 *
	 * @return void
	 */
	public function run( $optional_args ) {
		try {
			$blueprint = ImportSchema::create_from_file( $this->schema_path );
		} catch ( \Exception $e ) {
			\WP_CLI::error( $e->getMessage() );
			return;
		}

		\WP_CLI::warning( 'A Blueprint imported with WP-CLI can change anything this command can access on the site — including data that is not described in the file. Only import files from a source you trust.' );
		\WP_CLI::confirm( 'Do you want to continue?', $optional_args );

		$results = $blueprint->import();

		$result_formatter = new CliResultFormatter( $results );
		$is_success       = $result_formatter->is_success();

		if ( isset( $optional_args['show-messages'] ) ) {
			$result_formatter->format( $optional_args['show-messages'] );
		}

		if ( $is_success ) {
			\WP_CLI::success( "$this->schema_path imported successfully" );
		} else {
			\WP_CLI::error( "Failed to import $this->schema_path. Run with --show-messages=all to debug" );
		}
	}
}
