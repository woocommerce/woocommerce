<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\ImportExport\Product;

use Automattic\WooCommerce\Internal\Admin\ImportExport\Product\ProductExporter;
use InvalidArgumentException;
use RuntimeException;
use WC_Admin_Exporters;
use WP_CLI;

/**
 * The `wp wc product export` command.
 *
 * @since 11.4.0
 */
final class ExportCommand {

	/**
	 * Default for `--batch-size`.
	 */
	private const DEFAULT_BATCH_SIZE = 50;

	/**
	 * The product exporter.
	 *
	 * @var ProductExporter
	 */
	private ProductExporter $exporter;

	/**
	 * Initialize the command with its dependencies.
	 *
	 * @internal
	 *
	 * @param ProductExporter $exporter The product exporter.
	 */
	final public function init( ProductExporter $exporter ): void { // phpcs:ignore Generic.CodeAnalysis.UnnecessaryFinalModifier.Found -- Required by WooCommerce injection method rules
		$this->exporter = $exporter;
	}

	/**
	 * Export products to a CSV file.
	 *
	 * ## OPTIONS
	 *
	 * [<file>]
	 * : Path to write the CSV to, or `-` for STDOUT. Required unless `--list-columns` is used.
	 *
	 * [--columns=<list>]
	 * : Comma-separated column keys to export. Defaults to all. Use `--list-columns` to see keys.
	 * `downloads` and `attributes` each select the whole numbered group of columns.
	 *
	 * [--types=<list>]
	 * : Comma-separated product types. Defaults to all. The available types are the registered
	 * product types plus `variation`, as filtered by `woocommerce_exporter_product_types`.
	 *
	 * [--category=<list>]
	 * : Comma-separated product category slugs.
	 *
	 * [--ids=<list>]
	 * : Comma-separated product IDs. Takes priority over `--types` and `--category`, which are
	 * ignored. A variable product's variations are included.
	 *
	 * [--meta]
	 * : Include custom meta columns.
	 *
	 * [--batch-size=<n>]
	 * : Products per batch.
	 * ---
	 * default: 50
	 * ---
	 *
	 * [--list-columns]
	 * : Print available column keys and labels, then exit.
	 *
	 * [--porcelain]
	 * : Print only the number of exported products. Cannot be used with `-`.
	 *
	 * ## EXAMPLES
	 *
	 *     # List the available column keys.
	 *     wp wc product export --list-columns
	 *
	 *     # Export every simple product to a file.
	 *     wp wc product export products.csv --types=simple
	 *
	 *     # Export a specific set of products and columns, including meta.
	 *     wp wc product export products.csv --ids=12,34,56 --columns=id,sku,name --meta
	 *
	 *     # Export to STDOUT and pipe it elsewhere.
	 *     wp wc product export - --types=simple | gzip > products.csv.gz
	 *
	 * @param array $args       The positional arguments.
	 * @param array $assoc_args The associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		if ( (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'list-columns', false ) ) {
			$this->list_columns();
			return;
		}

		if ( empty( $args[0] ) ) {
			WP_CLI::error( 'Please specify a destination file, or `-` for STDOUT.' );
			return;
		}

		$batch_size = (int) WP_CLI\Utils\get_flag_value( $assoc_args, 'batch-size', self::DEFAULT_BATCH_SIZE );

		if ( $batch_size < 1 ) {
			WP_CLI::error( 'The --batch-size argument must be a positive integer.' );
			return;
		}

		$destination = (string) $args[0];
		$columns     = $this->parse_list( (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'columns', '' ) );
		$types       = $this->parse_list( (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'types', '' ) );
		$category    = $this->parse_list( (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'category', '' ) );
		$ids         = $this->parse_list( (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'ids', '' ) );
		$meta        = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'meta', false );
		$porcelain   = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain', false );

		if ( '-' === $destination && $porcelain ) {
			WP_CLI::error( 'The --porcelain flag cannot be used with `-`, because STDOUT carries the CSV.' );
			return;
		}

		// A filter that ends up empty would export the whole catalog, so a passed flag must yield values.
		$filters = array(
			'types'    => $types,
			'category' => $category,
		);

		foreach ( $filters as $flag => $values ) {
			if ( isset( $assoc_args[ $flag ] ) && empty( $values ) ) {
				WP_CLI::error( sprintf( 'The --%s argument requires at least one value.', $flag ) );
			}
		}

		$this->validate_columns( $columns );
		$this->validate_types( $types );
		$ids = $this->validate_ids( $ids, isset( $assoc_args['ids'] ) );

		try {
			$destination = $this->exporter->validate_destination( $destination );

			$results = $this->exporter->run(
				$destination,
				array(
					'columns'  => $columns,
					'types'    => $types,
					'category' => $category,
					'ids'      => $ids,
					'meta'     => $meta,
					'limit'    => $batch_size,
				),
				function ( int $page, int $total_exported ) use ( $destination, $porcelain ) {
					if ( '-' !== $destination && ! $porcelain ) {
						WP_CLI::log( sprintf( 'Batch %d: %d products exported so far.', $page, $total_exported ) );
					}
				}
			);
		} catch ( InvalidArgumentException | RuntimeException $e ) {
			// Engine exceptions are HTML-escaped for any output context; this one is a terminal.
			WP_CLI::error( wp_specialchars_decode( $e->getMessage(), ENT_QUOTES ) );
			return;
		}

		$this->report_results( $results, $porcelain );
	}

	/**
	 * Print available column keys and labels.
	 */
	private function list_columns(): void {
		$rows = array();

		foreach ( $this->exporter->get_available_columns() as $key => $label ) {
			$rows[] = array(
				'key'   => $key,
				'label' => $label,
			);
		}

		WP_CLI\Utils\format_items( 'table', $rows, array( 'key', 'label' ) );
	}

	/**
	 * Print the final export result.
	 *
	 * @param array $results   The `ProductExporter::run()` result.
	 * @param bool  $porcelain Whether to print only the exported count.
	 */
	private function report_results( array $results, bool $porcelain ): void {
		if ( '-' === $results['destination'] ) {
			return;
		}

		if ( $porcelain ) {
			WP_CLI::log( (string) $results['exported'] );
			return;
		}

		WP_CLI::success( sprintf( 'Exported %d products to %s.', $results['exported'], $results['destination'] ) );
	}

	/**
	 * Split a comma-separated CLI argument into a trimmed, non-empty list.
	 *
	 * @param string $value The raw comma-separated value.
	 *
	 * @return string[]
	 */
	private function parse_list( string $value ): array {
		if ( '' === $value ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map( 'trim', explode( ',', $value ) ),
				static fn( string $item ): bool => '' !== $item
			)
		);
	}

	/**
	 * Validate `--columns` against the exporter's known column keys.
	 *
	 * @param string[] $columns Requested column keys.
	 */
	private function validate_columns( array $columns ): void {
		if ( empty( $columns ) ) {
			return;
		}

		$available = array_keys( $this->exporter->get_available_columns() );
		$unknown   = array_diff( $columns, $available );

		if ( ! empty( $unknown ) ) {
			WP_CLI::error( sprintf( 'Unknown column key(s): %s. Use --list-columns to see the available keys.', implode( ', ', $unknown ) ) );
		}
	}

	/**
	 * Validate `--types` against the product types the admin exporter supports.
	 *
	 * Goes through `WC_Admin_Exporters::get_product_types()` rather than `wc_get_product_types()`
	 * so the `woocommerce_exporter_product_types` filter decides what is exportable here too, the
	 * same list `WC_Product_CSV_Exporter` seeds itself with.
	 *
	 * @param string[] $types Requested product types.
	 */
	private function validate_types( array $types ): void {
		if ( empty( $types ) ) {
			return;
		}

		$available = array_keys( WC_Admin_Exporters::get_product_types() );
		$unknown   = array_diff( $types, $available );

		if ( ! empty( $unknown ) ) {
			WP_CLI::error( sprintf( 'Unknown product type(s): %s.', implode( ', ', $unknown ) ) );
		}
	}

	/**
	 * Validate `--ids` as positive integers and cast them.
	 *
	 * A filter that ends up empty would export the whole catalog, so a passed flag must yield IDs.
	 *
	 * @param string[] $ids      Requested product IDs.
	 * @param bool     $provided Whether `--ids` was passed.
	 *
	 * @return int[]
	 */
	private function validate_ids( array $ids, bool $provided ): array {
		if ( $provided && empty( $ids ) ) {
			WP_CLI::error( 'The --ids argument requires at least one product ID.' );
		}

		$invalid = array_filter(
			$ids,
			static function ( string $id ): bool {
				return ! ctype_digit( $id ) || (int) $id < 1;
			}
		);

		if ( ! empty( $invalid ) ) {
			WP_CLI::error( sprintf( 'Invalid product ID(s): %s.', implode( ', ', $invalid ) ) );
		}

		return array_map( 'intval', $ids );
	}
}
