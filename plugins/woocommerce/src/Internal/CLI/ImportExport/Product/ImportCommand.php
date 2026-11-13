<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\ImportExport\Product;

use Automattic\WooCommerce\Internal\Admin\ImportExport\ImportLockedException;
use Automattic\WooCommerce\Internal\Admin\ImportExport\Product\MappingResolver;
use Automattic\WooCommerce\Internal\Admin\ImportExport\Product\ProductImporter;
use Automattic\WooCommerce\Internal\CLI\ImportExport\Support\StdinSpooler;
use InvalidArgumentException;
use WP_CLI;

/**
 * The `wp wc product import` command.
 *
 * @since 11.4.0
 */
final class ImportCommand {

	/**
	 * Upper bound for `--batch-size`.
	 */
	private const MAX_BATCH_SIZE = 1000;

	/**
	 * Default for `--batch-size`.
	 */
	private const DEFAULT_BATCH_SIZE = 100;

	/**
	 * The product importer.
	 *
	 * @var ProductImporter
	 */
	private ProductImporter $importer;

	/**
	 * The mapping resolver.
	 *
	 * @var MappingResolver
	 */
	private MappingResolver $mapping_resolver;

	/**
	 * The STDIN spooler.
	 *
	 * @var StdinSpooler
	 */
	private StdinSpooler $stdin_spooler;

	/**
	 * Initialize the command with its dependencies.
	 *
	 * @internal
	 *
	 * @param ProductImporter $importer         The product importer.
	 * @param MappingResolver $mapping_resolver The mapping resolver.
	 * @param StdinSpooler    $stdin_spooler    The STDIN spooler.
	 */
	final public function init( ProductImporter $importer, MappingResolver $mapping_resolver, StdinSpooler $stdin_spooler ): void { // phpcs:ignore Generic.CodeAnalysis.UnnecessaryFinalModifier.Found -- Required by WooCommerce injection method rules
		$this->importer         = $importer;
		$this->mapping_resolver = $mapping_resolver;
		$this->stdin_spooler    = $stdin_spooler;
	}

	/**
	 * Import products from a CSV file.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Path to a CSV file, or `-` to read from STDIN.
	 *
	 * [--update-existing]
	 * : Update products that already exist (matched by ID or SKU) instead of skipping them.
	 *
	 * [--delimiter=<char>]
	 * : The CSV field delimiter.
	 * ---
	 * default: ,
	 * ---
	 *
	 * [--character-encoding=<encoding>]
	 * : The character encoding of the input file.
	 * ---
	 * default: UTF-8
	 * ---
	 *
	 * [--mapping=<spec>]
	 * : Column overrides: `Header=target,...`, inline JSON, or `@file.json`. Target `-` skips a column. Headers must match the CSV exactly, including case.
	 *
	 * [--map-unknown=<mode>]
	 * : What to do with CSV columns that have no known target.
	 * ---
	 * default: skip
	 * options:
	 *   - skip
	 *   - meta
	 * ---
	 *
	 * [--show-mapping]
	 * : Print the resolved column mapping and a sample row, then exit without importing.
	 *
	 * [--batch-size=<n>]
	 * : Number of rows to process per batch, between 1 and 1000. Defaults to 100, or to the value of the `woocommerce_product_import_batch_size` filter when one is set. Passing this flag overrides the filter.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * [--porcelain]
	 * : Output only imported and updated product IDs, one per line.
	 *
	 * [--strict]
	 * : Exit with status 1 if any row failed.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt shown when a previous, possibly interrupted import
	 * left placeholder products behind. Required in that case when reading the CSV from STDIN,
	 * or with `--porcelain` or a `--format` other than `table`.
	 *
	 * There is no dry-run mode: the importer cannot parse a row without persisting
	 * some of its effects (images, terms, attributes, placeholder and parent products
	 * are written as rows are read). Test against a staging site or a database
	 * snapshot first.
	 *
	 * Importing needs a WordPress user with the `edit_products`, `import`, and `manage_product_terms`
	 * capabilities, and the importer silently drops categories without the last one. Pass
	 * `--user=<admin>`. `--show-mapping` does not need one.
	 *
	 * Cleanup of placeholder products at the end of an import is site-wide, so this
	 * command takes a lock for the duration of the run and refuses to start while an
	 * admin CSV import (or another `wp wc product import`) holds it. Do not run this
	 * command at the same time as an import in **WooCommerce > Products > Import**.
	 *
	 * ## EXAMPLES
	 *
	 *     # Preview the resolved column mapping without importing.
	 *     wp wc product import products.csv --show-mapping
	 *
	 *     # Import, overriding two columns and sending unknown columns to post meta.
	 *     wp wc product import products.csv --mapping="Vendor SKU=sku,Notes=meta:notes" --map-unknown=meta --user=admin
	 *
	 *     # Import from STDIN and print only the resulting product IDs.
	 *     cat products.csv | wp wc product import - --porcelain --user=admin
	 *
	 * @param array $args       The positional arguments.
	 * @param array $assoc_args The associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$batch_size = (int) WP_CLI\Utils\get_flag_value( $assoc_args, 'batch-size', $this->get_default_batch_size() );

		// The import lock is only refreshed between batches, so a batch must finish well within the lock's stale window.
		if ( $batch_size < 1 || $batch_size > self::MAX_BATCH_SIZE ) {
			WP_CLI::error( sprintf( 'The --batch-size argument must be an integer between 1 and %d.', self::MAX_BATCH_SIZE ) );
			return;
		}

		$show_mapping = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'show-mapping', false );

		$file            = $args[0];
		$is_stdin        = '-' === $file;
		$format          = (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$porcelain       = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain', false );
		$strict          = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'strict', false );
		$delimiter       = (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'delimiter', ',' );
		$encoding        = (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'character-encoding', 'UTF-8' );
		$map_unknown     = (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'map-unknown', MappingResolver::UNKNOWN_SKIP );
		$mapping_spec    = (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'mapping', '' );
		$update_existing = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'update-existing', false );

		// The importer silently drops categories without these capabilities, so fail before anything is written.
		if ( ! $show_mapping && ! ( current_user_can( 'edit_products' ) && current_user_can( 'import' ) && current_user_can( 'manage_product_terms' ) ) ) {
			WP_CLI::error( 'Importing products requires a user with the edit_products, import, and manage_product_terms capabilities. Pass --user=<admin>.' );
			return;
		}

		if ( 1 !== strlen( $delimiter ) ) {
			WP_CLI::error( "The --delimiter argument must be exactly one character. To use a tab, pass --delimiter=$'\\t'." );
			return;
		}

		if ( ! $this->is_supported_encoding( $encoding ) ) {
			WP_CLI::error( sprintf( 'Unsupported --character-encoding "%s". See mb_list_encodings() for the supported values.', $encoding ) );
			return;
		}

		// A mapping preview does not touch product data, so it needs neither the lock nor this check.
		if ( ! $show_mapping && ! $this->check_for_concurrent_import() ) {
			return;
		}

		try {
			if ( $is_stdin ) {
				$file = $this->stdin_spooler->spool( 'wc-product-import' );

				// WP_CLI::error() exits, which skips a finally block, so delete the spooled copy at shutdown.
				register_shutdown_function(
					static function () use ( $file ): void {
						if ( file_exists( $file ) ) {
							wp_delete_file( $file );
						}
					}
				);
			} else {
				$file = $this->importer->validate_file( $file );
			}

			$this->run_import(
				$file,
				array(
					'delimiter'          => $delimiter,
					'character_encoding' => $encoding,
					'map_unknown'        => $map_unknown,
					'mapping_spec'       => $mapping_spec,
					'update_existing'    => $update_existing,
					'batch_size'         => $batch_size,
					'format'             => $format,
					'porcelain'          => $porcelain,
					'strict'             => $strict,
					'show_mapping'       => $show_mapping,
					'is_stdin'           => $is_stdin,
					'yes'                => (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'yes', false ),
				)
			);
		} catch ( ImportLockedException | InvalidArgumentException $e ) {
			// Engine exceptions are HTML-escaped for any output context; this one is a terminal.
			WP_CLI::error( wp_specialchars_decode( $e->getMessage(), ENT_QUOTES ) );
		}
	}

	/**
	 * Whether mbstring can convert from the given encoding.
	 *
	 * Empty means autodetect and is accepted. Without mbstring the importer skips conversion, so any value is accepted.
	 *
	 * @param string $encoding The `--character-encoding` value.
	 *
	 * @return bool
	 */
	private function is_supported_encoding( string $encoding ): bool {
		if ( '' === $encoding || ! function_exists( 'mb_list_encodings' ) ) {
			return true;
		}

		foreach ( mb_list_encodings() as $name ) {
			if ( 0 === strcasecmp( $name, $encoding ) ) {
				return true;
			}

			$aliases = mb_encoding_aliases( $name );

			if ( false !== $aliases && in_array( strtolower( $encoding ), array_map( 'strtolower', $aliases ), true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the default number of rows to process per batch.
	 *
	 * The admin CSV importer sizes its batches through the same filter, so an extension that
	 * lowers it for memory reasons applies here as well. An explicit `--batch-size` still wins.
	 *
	 * @return int
	 */
	private function get_default_batch_size(): int {
		/**
		 * Batch size for the product import process.
		 *
		 * @param int $size Batch size.
		 *
		 * @since 3.1.0
		 */
		return (int) apply_filters( 'woocommerce_product_import_batch_size', self::DEFAULT_BATCH_SIZE );
	}

	/**
	 * Fail before attempting an import that could collide with another one.
	 *
	 * Cleanup at the end of an import is site-wide, so this is a best-effort pre-flight
	 * check: `ProductImporter::run()` still takes the actual lock and can throw
	 * `ImportLockedException` if another run wins the race after this check passes.
	 *
	 * @return bool Whether the import may proceed.
	 */
	private function check_for_concurrent_import(): bool {
		$lock_age = $this->importer->get_import_lock_age();

		if ( null !== $lock_age ) {
			WP_CLI::error(
				sprintf(
					'Another product import is already running (last active %d minute(s) ago). Wait for it to finish, or if you are sure nothing is actually running, clear the lock with `wp option delete wc_product_importing`.',
					(int) ceil( $lock_age / MINUTE_IN_SECONDS )
				)
			);

			return false;
		}

		return true;
	}

	/**
	 * Warn about placeholders the import will clean up, and ask to continue unless `--yes` was passed.
	 *
	 * Runs while `ProductImporter::run()` holds the lock, so it throws to abort: `WP_CLI::confirm()`
	 * and `WP_CLI::error()` exit, which would skip the lock's release.
	 *
	 * @param int   $count   Number of placeholders left behind by a previous, possibly interrupted import.
	 * @param array $options Parsed command options, see `__invoke()`.
	 *
	 * @throws InvalidArgumentException When the import should not continue.
	 */
	private function confirm_placeholder_cleanup( int $count, array $options ): void {
		WP_CLI::warning(
			sprintf(
				'%d placeholder product(s) left behind by a previous, possibly interrupted import were found. This import will clean them up.',
				$count
			)
		);

		if ( $options['yes'] ) {
			return;
		}

		if ( $options['is_stdin'] || $options['porcelain'] || 'table' !== $options['format'] ) {
			throw new InvalidArgumentException( 'Re-run with --yes: the confirmation prompt cannot be used while the CSV is read from STDIN, or with --porcelain or a --format other than table.' );
		}

		fwrite( STDOUT, 'Continue with the import? [y/n] ' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Terminal prompt, WP_Filesystem does not apply.

		if ( 'y' !== strtolower( trim( (string) fgets( STDIN ) ) ) ) {
			throw new InvalidArgumentException( 'Import cancelled.' );
		}
	}

	/**
	 * Resolve the mapping, then either show it or run the import.
	 *
	 * @param string $file    The validated (or spooled) CSV file path.
	 * @param array  $options Parsed command options, see `__invoke()`.
	 *
	 * @throws InvalidArgumentException When the file is empty, or the mapping spec or resolved mapping is invalid.
	 */
	private function run_import( string $file, array $options ): void {
		$read = $this->importer->read_headers( $file, $options['delimiter'], $options['character_encoding'] );

		if ( '' === implode( '', $read['headers'] ) ) {
			throw new InvalidArgumentException( 'The CSV file is empty.' );
		}

		$overrides = $this->mapping_resolver->parse_overrides( $options['mapping_spec'] );
		$resolved  = $this->mapping_resolver->resolve( $read['headers'], $overrides, $options['map_unknown'] );
		$mapping   = $resolved['mapping'];

		if ( ! empty( $resolved['unknown'] ) ) {
			$action = MappingResolver::UNKNOWN_META === $options['map_unknown'] ? 'mapped to post meta' : 'skipped';
			WP_CLI::warning( sprintf( 'Unrecognized column(s) %s were %s.', implode( ', ', $resolved['unknown'] ), $action ) );
		}

		if ( $options['show_mapping'] ) {
			$this->show_mapping( $read, $mapping, $options['format'] );
			return;
		}

		$this->run_and_report( $file, $mapping, $options );
	}

	/**
	 * Print the resolved column mapping and a sample row.
	 *
	 * @param array  $read    The result of `ProductImporter::read_headers()`.
	 * @param array  $mapping Header => target mapping.
	 * @param string $format  Output format for `WP_CLI\Utils\format_items()`.
	 */
	private function show_mapping( array $read, array $mapping, string $format ): void {
		$rows = array();

		foreach ( $read['headers'] as $index => $header ) {
			$target = $mapping[ $header ] ?? '';
			$rows[] = array(
				'header'    => $header,
				'mapped_to' => '' === $target ? '(skipped)' : $target,
				'sample'    => (string) ( $read['sample'][ $index ] ?? '' ),
			);
		}

		WP_CLI\Utils\format_items( $format, $rows, array( 'header', 'mapped_to', 'sample' ) );
	}

	/**
	 * Run the import with a progress bar and report the outcome.
	 *
	 * @param string $file    The validated (or spooled) CSV file path.
	 * @param array  $mapping Header => target mapping.
	 * @param array  $options Parsed command options, see `__invoke()`.
	 *
	 * @throws ImportLockedException When another import already holds the site-wide import lock.
	 */
	private function run_and_report( string $file, array $mapping, array $options ): void {
		$progress = $options['porcelain'] ? null : WP_CLI\Utils\make_progress_bar( 'Importing products', (int) filesize( $file ) );
		$position = 0;

		$results = $this->importer->run(
			$file,
			array(
				'mapping'            => $mapping,
				'update_existing'    => $options['update_existing'],
				'delimiter'          => $options['delimiter'],
				'character_encoding' => $options['character_encoding'],
				'batch_size'         => $options['batch_size'],
			),
			function ( int $new_position ) use ( $progress, &$position ) {
				if ( null !== $progress ) {
					$progress->tick( max( 0, $new_position - $position ) );
				}
				$position = $new_position;
			},
			function ( int $count ) use ( $options ): void {
				$this->confirm_placeholder_cleanup( $count, $options );
			}
		);

		if ( null !== $progress ) {
			$progress->finish();
		}

		if ( $options['porcelain'] ) {
			foreach ( array_merge( $results['imported_ids'], $results['imported_variation_ids'], $results['updated_ids'] ) as $id ) {
				WP_CLI::log( (string) $id );
			}

			if ( $results['failed'] > 0 ) {
				WP_CLI::warning( sprintf( '%d row(s) failed.', $results['failed'] ) );

				if ( $options['strict'] ) {
					WP_CLI::error( 'Import completed with failures.' );
				}
			}
			return;
		}

		// Machine-readable formats always print the report, so a clean run still outputs a parseable document.
		if ( empty( $results['errors'] ) && 'yaml' === $options['format'] ) {
			// The YAML formatter renders an empty list as a bare `---`, which parses as null.
			WP_CLI::line( '[]' );
		} elseif ( ! empty( $results['errors'] ) || 'table' !== $options['format'] ) {
			WP_CLI\Utils\format_items( $options['format'], $results['errors'], array( 'row', 'code', 'message' ) );
		}

		$this->report_summary( $results, $options );
	}

	/**
	 * Print the final import summary.
	 *
	 * @param array $results The `ProductImporter::run()` result.
	 * @param array $options Parsed command options, see `__invoke()`.
	 */
	private function report_summary( array $results, array $options ): void {
		$summary = sprintf(
			'imported %d, imported variations %d, updated %d, skipped %d, failed %d.',
			$results['imported'],
			$results['imported_variations'],
			$results['updated'],
			$results['skipped'],
			$results['failed']
		);

		$strict_failure = $options['strict'] && $results['failed'] > 0;

		if ( 'table' !== $options['format'] ) {
			// STDOUT holds only the machine-readable report.
			fwrite( STDERR, $summary . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- WP_CLI has no plain STDERR logger.
		} elseif ( $strict_failure ) {
			WP_CLI::log( $summary );
		} else {
			WP_CLI::success( $summary );
		}

		if ( $strict_failure ) {
			WP_CLI::error( 'Import completed with failures.' );
		}
	}
}
