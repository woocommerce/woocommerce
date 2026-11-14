<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\ImportExport\Product;

use Automattic\WooCommerce\Internal\Admin\ImportExport\ImportLockedException;
use InvalidArgumentException;
use WC_Product_CSV_Importer_Controller;
use WP_Error;

/**
 * Drives a product CSV import through the legacy `WC_Product_CSV_Importer` engine.
 *
 * @phpstan-type ImportResult array{
 *     imported: int,
 *     imported_variations: int,
 *     imported_variation_ids: int[],
 *     updated: int,
 *     skipped: int,
 *     failed: int,
 *     imported_ids: int[],
 *     updated_ids: int[],
 *     errors: array
 * }
 *
 * @since 11.4.0
 */
final class ProductImporter {

	/**
	 * The mapping resolver.
	 *
	 * @var MappingResolver
	 */
	private MappingResolver $mapping_resolver;

	/**
	 * The site-wide product import lock.
	 *
	 * @var ProductImportLock
	 */
	private ProductImportLock $import_lock;

	/**
	 * The placeholder product cleanup routine.
	 *
	 * @var ImportPlaceholders
	 */
	private ImportPlaceholders $cleanup;

	/**
	 * Initialize the product importer with dependencies.
	 *
	 * @internal
	 *
	 * @param MappingResolver    $mapping_resolver The mapping resolver.
	 * @param ProductImportLock  $import_lock      The site-wide product import lock.
	 * @param ImportPlaceholders $cleanup          The placeholder product cleanup routine.
	 */
	final public function init( MappingResolver $mapping_resolver, ProductImportLock $import_lock, ImportPlaceholders $cleanup ): void { // phpcs:ignore Generic.CodeAnalysis.UnnecessaryFinalModifier.Found -- Required by WooCommerce injection method rules
		$this->mapping_resolver = $mapping_resolver;
		$this->import_lock      = $import_lock;
		$this->cleanup          = $cleanup;
	}

	/**
	 * Validate a CSV file path and return its realpath.
	 *
	 * Callers already have filesystem access, so the file is not restricted to the uploads
	 * directory the way the admin importer restricts it.
	 *
	 * @param string $file Path to the file to validate.
	 *
	 * @return string The realpath of the validated file.
	 *
	 * @throws InvalidArgumentException When the file is not a valid, readable, local CSV file.
	 */
	public function validate_file( string $file ): string {
		if ( preg_match( '/^[a-z][a-z0-9+.-]*:\/\//i', $file ) ) {
			throw new InvalidArgumentException( 'The file path cannot be a URL or stream wrapper.' );
		}

		$real_path = realpath( $file );

		if ( false === $real_path || ! is_file( $real_path ) || ! is_readable( $real_path ) ) {
			throw new InvalidArgumentException( 'The file does not exist or is not readable.' );
		}

		if ( ! wc_is_file_valid_csv( $real_path, false ) ) {
			throw new InvalidArgumentException( 'The file is not a valid CSV file.' );
		}

		return $real_path;
	}

	/**
	 * Get how long ago the site-wide product import lock was taken or last refreshed.
	 *
	 * Lets a caller warn the operator before attempting a run that would otherwise fail
	 * with `ImportLockedException` once batches are already underway.
	 *
	 * @return int|null Seconds since the lock was taken or last refreshed, or null when no import is running.
	 */
	public function get_import_lock_age(): ?int {
		return $this->import_lock->get_age();
	}

	/**
	 * Read the raw headers and a sample row from a CSV file.
	 *
	 * @param string $file      Path to the CSV file.
	 * @param string $delimiter CSV delimiter.
	 * @param string $encoding  Character encoding, or empty string for autodetect.
	 *
	 * @return array{headers: string[], sample: array}
	 */
	public function read_headers( string $file, string $delimiter, string $encoding ): array {
		$this->load_importer();

		$importer = WC_Product_CSV_Importer_Controller::get_importer(
			$file,
			array(
				'lines'              => 1,
				'delimiter'          => $delimiter,
				'character_encoding' => $encoding,
			)
		);

		return array(
			'headers' => $importer->get_raw_keys(),
			'sample'  => (array) current( $importer->get_raw_data() ),
		);
	}

	/**
	 * Run a product CSV import to completion, one batch at a time.
	 *
	 * @param string        $file     Path to the CSV file.
	 * @param array         $options  Options: mapping, update_existing, delimiter, character_encoding, batch_size.
	 * @param callable|null $on_batch Optional callback invoked as `$on_batch( int $position, array $batch_results )` after each batch.
	 * @param callable|null $on_pending_placeholders Optional callback invoked as `$on_pending_placeholders( int $count )` while the lock is held,
	 *                                               when placeholders from an earlier run would be cleaned up. It can throw to abort the import.
	 *
	 * @return ImportResult
	 *
	 * @throws ImportLockedException When another import already holds the site-wide import lock.
	 */
	public function run( string $file, array $options, ?callable $on_batch = null, ?callable $on_pending_placeholders = null ): array {
		$this->load_importer();
		$this->validate_mapping( (array) ( $options['mapping'] ?? array() ) );

		if ( ! $this->import_lock->acquire( ProductImportLock::HOLDER_CLI ) ) {
			throw new ImportLockedException( esc_html( 'Another product import is already running.' ) );
		}

		try {
			$this->cleanup->release_stranded_cleanup_claims();

			$pending_placeholders = null === $on_pending_placeholders ? 0 : $this->cleanup->count_pending_placeholders();

			if ( $pending_placeholders > 0 ) {
				$on_pending_placeholders( $pending_placeholders );
			}

			$totals = array(
				'imported'               => 0,
				'imported_variations'    => 0,
				'imported_variation_ids' => array(),
				'updated'                => 0,
				'skipped'                => 0,
				'failed'                 => 0,
				'imported_ids'           => array(),
				'updated_ids'            => array(),
				'errors'                 => array(),
			);

			wp_defer_term_counting( true );
			wp_suspend_cache_addition( true );

			$refresh_lock = array( $this->import_lock, 'refresh_if_due' );
			add_action( 'woocommerce_product_import_before_import', $refresh_lock );

			try {
				$position = 0;

				do {
					$batch    = $this->run_batch( $file, $options, $position );
					$position = $batch['position'];

					foreach ( array( 'imported', 'imported_variations', 'updated', 'skipped', 'failed' ) as $count ) {
						$totals[ $count ] += $batch['results'][ $count ];
					}
					foreach ( array( 'imported_ids', 'imported_variation_ids', 'updated_ids', 'errors' ) as $list ) {
						$totals[ $list ] = array_merge( $totals[ $list ], $batch['results'][ $list ] );
					}

					// A long import can outlast the lock's stale timeout, so keep it fresh between batches.
					$this->import_lock->refresh();

					if ( null !== $on_batch ) {
						$on_batch( $position, $batch['results'] );
					}
				} while ( ! $batch['done'] );
			} finally {
				remove_action( 'woocommerce_product_import_before_import', $refresh_lock );
				wp_suspend_cache_addition( false );
				wp_defer_term_counting( false );
			}

			$cursor = 0;
			do {
				$this->import_lock->refresh();
				$cursor = $this->cleanup->cleanup_after_import( (int) $cursor );
			} while ( null !== $cursor );

			return $totals;
		} finally {
			$this->import_lock->release();
		}
	}

	/**
	 * Import one batch of rows starting at a byte position in the file.
	 *
	 * `run()` loops this under the import lock. A caller that drives batches itself must
	 * hold the lock for the whole run.
	 *
	 * @param string $file     Path to the CSV file.
	 * @param array  $options  Options: mapping, update_existing, delimiter, character_encoding, batch_size.
	 * @param int    $position Byte position to start reading from, 0 for the first batch.
	 *
	 * @return array{results: ImportResult, position: int, done: bool}
	 *
	 * @throws InvalidArgumentException When a mapping target is not a valid importer schema key.
	 */
	public function run_batch( string $file, array $options, int $position ): array {
		$this->load_importer();
		$mapping = (array) ( $options['mapping'] ?? array() );
		$this->validate_mapping( $mapping );

		$importer = WC_Product_CSV_Importer_Controller::get_importer(
			$file,
			array(
				'start_pos'          => $position,
				'lines'              => max( 1, (int) ( $options['batch_size'] ?? 100 ) ),
				// The importer reads `from` and `to` keys as this shape, so a flat mapping would clash with CSV columns of those names.
				'mapping'            => array(
					'from' => array_keys( $mapping ),
					'to'   => array_values( $mapping ),
				),
				'update_existing'    => ! empty( $options['update_existing'] ),
				'delimiter'          => (string) ( $options['delimiter'] ?? ',' ),
				'character_encoding' => (string) ( $options['character_encoding'] ?? 'UTF-8' ),
				'parse'              => true,
				'prevent_timeouts'   => false,
			)
		);

		$results = $importer->import();

		return array(
			'results'  => $this->summarize_batch( $results ),
			'position' => $importer->get_file_position(),
			// A batch that does not advance also ends the run, so an empty file cannot loop forever.
			'done'     => $importer->get_percent_complete() >= 100 || $importer->get_file_position() <= $position,
		);
	}

	/**
	 * Summarize one batch's `WC_Product_CSV_Importer::import()` result into the shape `run()` returns.
	 *
	 * @param array $results The importer's result for this batch.
	 *
	 * @return ImportResult
	 */
	private function summarize_batch( array $results ): array {
		$imported_ids  = array_map( 'absint', (array) $results['imported'] );
		$updated_ids   = array_map( 'absint', (array) $results['updated'] );
		$variation_ids = array_map( 'absint', (array) $results['imported_variations'] );
		$errors        = $this->collect_errors( array_merge( (array) $results['failed'], (array) $results['skipped'] ) );

		return array(
			'imported'               => count( $imported_ids ),
			'imported_variations'    => count( $variation_ids ),
			'imported_variation_ids' => $variation_ids,
			'updated'                => count( $updated_ids ),
			'skipped'                => count( (array) $results['skipped'] ),
			'failed'                 => count( (array) $results['failed'] ),
			'imported_ids'           => $imported_ids,
			'updated_ids'            => $updated_ids,
			'errors'                 => $errors,
		);
	}

	/**
	 * Flatten a list of `WP_Error` objects from the importer into plain report rows.
	 *
	 * The importer describes a row by name, ID and SKU (see `WC_Product_CSV_Importer::get_row_id()`)
	 * rather than by line number, so `row` is that description.
	 *
	 * @param WP_Error[] $wp_errors The `WP_Error` objects from the importer's `failed`/`skipped` results.
	 *
	 * @return array<int, array{row: string, code: string, message: string}>
	 */
	private function collect_errors( array $wp_errors ): array {
		$errors = array();

		foreach ( $wp_errors as $wp_error ) {
			if ( ! $wp_error instanceof WP_Error ) {
				continue;
			}

			$data     = $wp_error->get_error_data();
			$data     = is_array( $data ) ? $data : array();
			$errors[] = array(
				'row'     => (string) ( $data['row'] ?? '' ),
				'code'    => (string) $wp_error->get_error_code(),
				'message' => wp_strip_all_tags( (string) $wp_error->get_error_message() ),
			);
		}

		return $errors;
	}

	/**
	 * Guard against an invalid mapping reaching the importer, for callers that build `$options['mapping']`
	 * themselves rather than going through `MappingResolver::resolve()`.
	 *
	 * @param array<string, string> $mapping Header => target mapping.
	 *
	 * @throws InvalidArgumentException When a mapping target is not a valid importer schema key.
	 */
	private function validate_mapping( array $mapping ): void {
		$invalid = array();

		foreach ( $mapping as $header => $target ) {
			if ( '' !== $target && ! $this->mapping_resolver->is_valid_target( $target ) ) {
				$invalid[] = $header . ' => ' . $target;
			}
		}

		if ( ! empty( $invalid ) ) {
			throw new InvalidArgumentException( esc_html( 'Invalid mapping target(s): ' . implode( ', ', $invalid ) ) );
		}
	}

	/**
	 * Load the admin-only importer and controller classes, which are not included outside admin requests.
	 */
	private function load_importer(): void {
		if ( ! class_exists( 'WC_Product_CSV_Importer_Controller' ) ) {
			include_once WC_ABSPATH . 'includes/admin/importers/class-wc-product-csv-importer-controller.php';
		}

		if ( ! class_exists( 'WC_Product_CSV_Importer' ) ) {
			include_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';
		}
	}
}
