<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\ImportExport\Product;

use Automattic\WooCommerce\Internal\Admin\ImportExport\Product\ProductExporter;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductExporter class.
 */
class ProductExporterTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ProductExporter
	 */
	private $sut;

	/**
	 * Files created by tests that should be deleted during tear down.
	 *
	 * @var string[]
	 */
	private $files_to_delete = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new ProductExporter();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			foreach ( $this->files_to_delete as $file ) {
				wp_delete_file( $file );
			}
			$this->files_to_delete = array();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should export created products to a CSV file with an ID and SKU header.
	 */
	public function test_run_exports_products_to_a_file(): void {
		WC_Helper_Product::create_simple_product();
		WC_Helper_Product::create_simple_product();

		$destination = $this->new_temp_path();

		$results = $this->sut->run( $destination, array() );

		$this->assertSame( 2, $results['exported'] );
		$lines = $this->read_csv_lines( $destination );
		$this->assertStringContainsString( 'ID', $lines[0] );
		$this->assertStringContainsString( 'SKU', $lines[0] );
		$this->assertCount( 3, $lines, 'Expected a header row plus one row per exported product' );
	}

	/**
	 * @testdox Should restrict the export to the given product IDs.
	 */
	public function test_run_restricts_export_to_given_ids(): void {
		WC_Helper_Product::create_simple_product();
		$target = WC_Helper_Product::create_simple_product();

		$destination = $this->new_temp_path();

		$results = $this->sut->run( $destination, array( 'ids' => array( $target->get_id() ) ) );

		$this->assertSame( 1, $results['exported'] );
		$lines = $this->read_csv_lines( $destination );
		$this->assertCount( 2, $lines, 'Expected a header row plus the single requested product' );
		$this->assertStringContainsString( (string) $target->get_id(), $lines[1] );
	}

	/**
	 * @testdox Should restrict the exported header to the given columns.
	 */
	public function test_run_restricts_columns(): void {
		WC_Helper_Product::create_simple_product();

		$destination = $this->new_temp_path();

		$this->sut->run( $destination, array( 'columns' => array( 'id', 'sku' ) ) );

		$lines  = $this->read_csv_lines( $destination );
		$header = str_getcsv( $lines[0] );
		$this->assertSame( array( 'ID', 'SKU' ), $header );
	}

	/**
	 * @testdox Should offer the grouped `downloads` and `attributes` keys alongside the default columns.
	 */
	public function test_get_available_columns_includes_the_grouped_special_columns(): void {
		$columns = $this->sut->get_available_columns();

		$this->assertArrayHasKey( 'id', $columns );
		$this->assertArrayHasKey( 'downloads', $columns );
		$this->assertArrayHasKey( 'attributes', $columns );
	}

	/**
	 * @testdox Should offer columns added through the `woocommerce_product_export_column_names` filter.
	 */
	public function test_get_available_columns_includes_filtered_column_names(): void {
		$add_column = static function ( array $columns ): array {
			$columns['custom_column'] = 'Custom column';
			return $columns;
		};
		add_filter( 'woocommerce_product_export_column_names', $add_column );

		try {
			$columns = $this->sut->get_available_columns();
		} finally {
			remove_filter( 'woocommerce_product_export_column_names', $add_column );
		}

		$this->assertSame( 'Custom column', $columns['custom_column'] ?? null );
	}

	/**
	 * @testdox Should export the numbered attribute columns when `attributes` is the only selected column.
	 */
	public function test_run_exports_the_attribute_group_when_selected_as_a_column(): void {
		WC_Helper_Product::create_variation_product();

		$destination = $this->new_temp_path();

		$this->sut->run( $destination, array( 'columns' => array( 'attributes' ) ) );

		$lines  = $this->read_csv_lines( $destination );
		$header = str_getcsv( $lines[0] );

		$this->assertContains( 'Attribute 1 name', $header );
	}

	/**
	 * @testdox Should print the header and rows and leave no file when the destination is `-`.
	 */
	public function test_run_prints_csv_for_stdout_destination(): void {
		$product = WC_Helper_Product::create_simple_product();

		$before_listing = glob( trailingslashit( get_temp_dir() ) . 'wc-product-export-*' );

		ob_start();
		try {
			$this->sut->run( '-', array() );
		} finally {
			$output = (string) ob_get_clean();
		}

		$this->assertStringContainsString( 'ID', $output );
		$this->assertStringContainsString( $product->get_sku(), $output );

		$after_listing = glob( trailingslashit( get_temp_dir() ) . 'wc-product-export-*' );
		$this->assertSame( $before_listing, $after_listing, 'No temp export file should remain on disk' );
	}

	/**
	 * @testdox Should delete the temporary files once the export completes.
	 */
	public function test_run_cleans_up_temp_files(): void {
		WC_Helper_Product::create_simple_product();

		$destination    = $this->new_temp_path();
		$before_listing = glob( trailingslashit( get_temp_dir() ) . 'wc-product-export-*' );

		$this->sut->run( $destination, array() );

		$after_listing = glob( trailingslashit( get_temp_dir() ) . 'wc-product-export-*' );
		$this->assertSame( $before_listing, $after_listing, 'Temporary export files should be removed from the temp directory' );
	}

	/**
	 * @testdox Should write the temporary export file under get_temp_dir(), not the uploads directory.
	 */
	public function test_run_writes_temp_file_under_temp_dir_not_uploads(): void {
		WC_Helper_Product::create_simple_product();

		$destination     = $this->new_temp_path();
		$upload_dir      = wp_upload_dir();
		$seen_temp_files = null;

		$this->sut->run(
			$destination,
			array(),
			function () use ( &$seen_temp_files ) {
				$seen_temp_files = glob( trailingslashit( get_temp_dir() ) . 'wc-product-export-*.csv' );
			}
		);

		$this->assertNotEmpty( $seen_temp_files, 'Expected the export temp file to exist under get_temp_dir() during the run' );

		$upload_matches = glob( trailingslashit( $upload_dir['basedir'] ) . 'wc-product-export-*' );
		$this->assertSame( array(), $upload_matches, 'No export temp file should be written to the uploads directory' );
	}

	/**
	 * @testdox Should delete the temp CSV and headers files even when a batch throws.
	 */
	public function test_run_cleans_up_temp_files_when_a_batch_throws(): void {
		WC_Helper_Product::create_simple_product();

		$destination    = $this->new_temp_path();
		$before_listing = glob( trailingslashit( get_temp_dir() ) . 'wc-product-export-*' );
		$callback       = function () {
			throw new \RuntimeException( 'Simulated failure mid-batch.' );
		};

		add_filter( 'woocommerce_product_export_row_data', $callback );

		try {
			$this->sut->run( $destination, array() );
			$this->fail( 'Expected a RuntimeException to be thrown mid-batch.' );
		} catch ( \RuntimeException $exception ) {
			$this->assertSame( 'Simulated failure mid-batch.', $exception->getMessage() );
		} finally {
			remove_filter( 'woocommerce_product_export_row_data', $callback );
		}

		$after_listing = glob( trailingslashit( get_temp_dir() ) . 'wc-product-export-*' );
		$this->assertSame( $before_listing, $after_listing, 'Temporary export files should be removed even when a batch throws' );
	}

	/**
	 * @testdox Should export every product exactly once when the export spans several batches.
	 */
	public function test_run_exports_all_products_across_batches(): void {
		for ( $i = 0; $i < 5; $i++ ) {
			WC_Helper_Product::create_simple_product();
		}

		$destination = $this->new_temp_path();

		$results = $this->sut->run( $destination, array( 'limit' => 2 ) );

		$rows = $this->read_csv_rows( $destination );
		$ids  = array_column( $rows, 0 );
		$this->assertSame( 5, $results['exported'] );
		$this->assertCount( 5, $rows, 'Expected one data row per product' );
		$this->assertCount( 5, array_unique( $ids ), 'No product should be exported twice' );
	}

	/**
	 * @testdox Should export and count the variations of a variable product when the export spans several batches.
	 */
	public function test_run_counts_variations_across_batches(): void {
		$variable   = WC_Helper_Product::create_variation_product();
		$simple     = WC_Helper_Product::create_simple_product();
		$variations = count( $variable->get_children() );

		$destination = $this->new_temp_path();

		$results = $this->sut->run(
			$destination,
			array(
				'ids'   => array( $variable->get_id(), $simple->get_id() ),
				'limit' => 1,
			)
		);

		$this->assertGreaterThan( 0, $variations );
		$this->assertSame( 2 + $variations, $results['exported'] );
		$this->assertCount( 2 + $variations, $this->read_csv_rows( $destination ) );
	}

	/**
	 * @testdox Should keep attribute columns in the header and align later rows when only an early batch has attributes.
	 */
	public function test_run_keeps_columns_aligned_when_attributes_only_on_early_batch(): void {
		WC_Helper_Product::create_variation_product();
		WC_Helper_Product::create_simple_product();
		WC_Helper_Product::create_simple_product();

		$destination = $this->new_temp_path();

		$this->sut->run( $destination, array( 'limit' => 1 ) );

		$lines  = $this->read_csv_lines( $destination );
		$header = str_getcsv( $lines[0] );
		$this->assertContains( 'Attribute 1 name', $header );

		foreach ( array_slice( $lines, 1 ) as $line ) {
			$this->assertCount( count( $header ), str_getcsv( $line ), 'Every row should have one value per header column' );
		}
	}

	/**
	 * @testdox Should reject a URL, a stream wrapper, and a path in a non-existent directory.
	 * @testWith ["http://example.com/out.csv"]
	 *           ["php://memory"]
	 *           ["/path/does/not/exist/out.csv"]
	 *
	 * @param string $destination The invalid destination under test.
	 */
	public function test_validate_destination_rejects_invalid_destinations( string $destination ): void {
		$this->expectException( \InvalidArgumentException::class );

		$this->sut->validate_destination( $destination );
	}

	/**
	 * @testdox Should pass a valid local path and STDOUT marker through unchanged.
	 */
	public function test_validate_destination_accepts_valid_destinations(): void {
		$path = $this->new_temp_path();

		$this->assertSame( $path, $this->sut->validate_destination( $path ) );
		$this->assertSame( '-', $this->sut->validate_destination( '-' ) );
	}

	/**
	 * Reserve a writable temporary file path and mark it for clean up.
	 *
	 * @return string
	 */
	private function new_temp_path(): string {
		$path                    = wp_tempnam( 'wc-product-exporter-test' );
		$this->files_to_delete[] = $path;

		return $path;
	}

	/**
	 * Read a written CSV file and return its data rows, without the header.
	 *
	 * @param string $path Path to the CSV file.
	 *
	 * @return array[]
	 */
	private function read_csv_rows( string $path ): array {
		return array_map( 'str_getcsv', array_slice( $this->read_csv_lines( $path ), 1 ) );
	}

	/**
	 * Read a written CSV file and split it into non-empty lines.
	 *
	 * @param string $path Path to the CSV file.
	 *
	 * @return string[]
	 */
	private function read_csv_lines( string $path ): array {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a local test fixture file.
		$content = file_get_contents( $path );
		$content = str_replace( chr( 239 ) . chr( 187 ) . chr( 191 ), '', $content );

		return array_values( array_filter( preg_split( "/\r\n|\n|\r/", $content ) ) );
	}
}
