<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\ImportExport\Product;

use Automattic\WooCommerce\Internal\Admin\ImportExport\TempDirectoryGuard;
use InvalidArgumentException;
use RuntimeException;
use WC_Product_CSV_Exporter;

/**
 * Drives a product CSV export through the legacy `WC_Product_CSV_Exporter` engine.
 *
 * @since 11.4.0
 */
final class ProductExporter {

	/**
	 * Validate a CSV export destination.
	 *
	 * `-` (STDOUT) always passes through unchanged. Any other destination must not be a
	 * stream wrapper or URL, and must have a writable parent directory.
	 *
	 * @param string $destination Path to write the CSV to, or `-` for STDOUT.
	 *
	 * @return string The destination, unchanged.
	 *
	 * @throws InvalidArgumentException When the destination is invalid.
	 */
	public function validate_destination( string $destination ): string {
		if ( '-' === $destination ) {
			return $destination;
		}

		if ( preg_match( '/^[a-z][a-z0-9+.-]*:\/\//i', $destination ) ) {
			throw new InvalidArgumentException( 'The destination cannot be a URL or stream wrapper.' );
		}

		$directory = dirname( $destination );

		if ( ! is_dir( $directory ) ) {
			throw new InvalidArgumentException( esc_html( sprintf( 'The directory "%s" does not exist.', $directory ) ) );
		}

		if ( ! is_writable( $directory ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- CLI local filesystem check, no WP_Filesystem context here.
			throw new InvalidArgumentException( esc_html( sprintf( 'The directory "%s" is not writable.', $directory ) ) );
		}

		return $destination;
	}

	/**
	 * Return the exportable columns as key => label.
	 *
	 * Uses `get_column_names()`, so columns added through `woocommerce_product_export_column_names`
	 * are listed and selectable, matching what an unfiltered export writes.
	 *
	 * `downloads` and `attributes` are not part of `get_default_column_names()` because they
	 * expand into numbered columns (`downloads:url1`, `attributes:value1`, ...) as products are
	 * read. `WC_CSV_Exporter::is_column_exporting()` matches them by their prefix, so they are
	 * selectable even though no column carries the bare key. The admin export form adds them the
	 * same way.
	 *
	 * @return array<string, string>
	 */
	public function get_available_columns(): array {
		$this->load_exporter();

		return array_merge(
			( new WC_Product_CSV_Exporter() )->get_column_names(),
			array(
				'downloads'  => __( 'Downloads', 'woocommerce' ),
				'attributes' => __( 'Attributes', 'woocommerce' ),
			)
		);
	}

	/**
	 * Run a product CSV export to completion, one batch at a time.
	 *
	 * @param string        $destination Path to write the CSV to, or `-` for STDOUT.
	 * @param array         $options     Options: columns, types, category, ids, meta, limit.
	 * @param callable|null $on_batch    Optional callback invoked as `$on_batch( int $page, int $total_exported )` after each batch.
	 *
	 * @return array{exported: int, destination: string}
	 *
	 * @throws InvalidArgumentException When the temporary directory is inside the web root.
	 * @throws RuntimeException When a batch or the destination cannot be fully written.
	 */
	public function run( string $destination, array $options, ?callable $on_batch = null ): array {
		$this->load_exporter();

		$exporter  = $this->build_exporter( $options );
		$file_path = $exporter->get_file_path();

		if ( TempDirectoryGuard::is_inside_web_root( dirname( $file_path ) ) ) {
			throw new InvalidArgumentException( 'The temporary directory resolves inside the web root. Set WP_TEMP_DIR to a location outside it.' );
		}

		wp_suspend_cache_addition( true );

		try {
			$page = 1;

			do {
				$exporter->set_page( $page );

				if ( ! $exporter->generate_file() ) {
					throw new RuntimeException( esc_html( sprintf( 'Could not write export batch %d to the temporary file.', $page ) ) );
				}

				if ( null !== $on_batch ) {
					$on_batch( $page, $exporter->get_total_exported() );
				}

				++$page;
			} while ( $exporter->get_percent_complete() < 100 );

			$this->write_output( $destination, $exporter );
		} finally {
			wp_suspend_cache_addition( false );

			wp_delete_file( $file_path );
			wp_delete_file( $file_path . '.headers' );
		}

		return array(
			'exported'    => $exporter->get_total_exported(),
			'destination' => $destination,
		);
	}

	/**
	 * Stream the finished export to its destination, so a large catalog is never held in memory.
	 *
	 * @param string      $destination Path to write the CSV to, or `-` for STDOUT.
	 * @param CsvExporter $exporter    The exporter holding the finished temporary files.
	 *
	 * @throws RuntimeException When the destination cannot be fully written.
	 */
	private function write_output( string $destination, CsvExporter $exporter ): void {
		$file_path = $exporter->get_file_path();
		$header    = $exporter->get_headers_row_file();

		// The exporter's own writes leave a stale size in PHP's stat cache.
		clearstatcache( true, $file_path );

		// phpcs:disable WordPress.WP.AlternativeFunctions -- CLI streams to an operator-provided local path, not the uploads directory WP_Filesystem targets.
		$source = fopen( $file_path, 'rb' );
		$output = fopen( '-' === $destination ? 'php://output' : $destination, 'wb' );

		$complete = false !== $source && false !== $output
			&& strlen( $header ) === fwrite( $output, $header )
			&& filesize( $file_path ) === stream_copy_to_stream( $source, $output );

		if ( false !== $source ) {
			fclose( $source );
		}

		if ( false !== $output ) {
			fclose( $output );
		}
		// phpcs:enable WordPress.WP.AlternativeFunctions

		if ( ! $complete ) {
			throw new RuntimeException( esc_html( sprintf( 'Could not write the export to %s.', $destination ) ) );
		}
	}

	/**
	 * Build and configure a `CsvExporter`, applying only the non-empty options.
	 *
	 * @param array $options Options: columns, types, category, ids, meta, limit.
	 *
	 * @return CsvExporter
	 */
	private function build_exporter( array $options ): CsvExporter {
		$exporter = new CsvExporter();

		$exporter->set_filename( 'wc-product-export-' . wp_generate_password( 8, false ) . '.csv' );

		if ( ! empty( $options['columns'] ) ) {
			$exporter->set_columns_to_export( (array) $options['columns'] );
		}

		if ( ! empty( $options['types'] ) ) {
			$exporter->set_product_types_to_export( (array) $options['types'] );
		}

		if ( ! empty( $options['category'] ) ) {
			$exporter->set_product_category_to_export( (array) $options['category'] );
		}

		if ( ! empty( $options['ids'] ) ) {
			$exporter->set_product_ids_to_export( (array) $options['ids'] );
		}

		if ( ! empty( $options['meta'] ) ) {
			$exporter->enable_meta_export( true );
		}

		if ( ! empty( $options['limit'] ) ) {
			$exporter->set_limit( (int) $options['limit'] );
		}

		return $exporter;
	}

	/**
	 * Load the admin-only exporter class, which is not included outside admin requests.
	 */
	private function load_exporter(): void {
		if ( ! class_exists( 'WC_Product_CSV_Exporter' ) ) {
			include_once WC_ABSPATH . 'includes/export/class-wc-product-csv-exporter.php';
		}
	}
}
