<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\ImportExport\Product;

use Automattic\WooCommerce\Internal\Admin\ImportExport\Product\ProductImportLock;
use Automattic\WooCommerce\Internal\Admin\ImportExport\Product\ProductImportPlaceholderCleanup;
use Automattic\WooCommerce\Internal\Admin\ImportExport\ImportLockedException;
use Automattic\WooCommerce\Internal\Admin\ImportExport\Product\ProductImporter;
use Automattic\WooCommerce\Internal\Admin\ImportExport\Product\MappingResolver;
use WC_Unit_Test_Case;
use WC_Unit_Tests_Bootstrap;

/**
 * Tests for the ProductImporter class.
 */
class ProductImporterTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ProductImporter
	 */
	private $sut;

	/**
	 * Files created by tests that should be deleted during tear down.
	 *
	 * @var string[]
	 */
	private $files_to_delete = array();

	/**
	 * The site-wide product import lock, used directly by tests to simulate a concurrent import.
	 *
	 * @var ProductImportLock
	 */
	private $import_lock;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$bootstrap = WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/import/class-wc-product-csv-importer.php';
		require_once $bootstrap->plugin_dir . '/includes/admin/importers/class-wc-product-csv-importer-controller.php';

		$this->import_lock = new ProductImportLock();

		$this->sut = new ProductImporter();
		$this->sut->init( new MappingResolver(), $this->import_lock, new ProductImportPlaceholderCleanup() );
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
	 * @testdox Should throw ImportLockedException when another run already holds the import lock.
	 */
	public function test_run_throws_when_the_import_lock_is_held(): void {
		$this->assertTrue( $this->import_lock->acquire( ProductImportLock::HOLDER_CLI ) );

		$file = $this->write_csv( "Type,SKU,Name\nsimple,IMPORT-RUNNER-LOCKED,Locked Product\n" );

		$this->expectException( ImportLockedException::class );

		$this->sut->run(
			$file,
			array(
				'mapping' => array(
					'Type' => 'type',
					'SKU'  => 'sku',
					'Name' => 'name',
				),
			)
		);
	}

	/**
	 * @testdox Should not create the product for a run refused because the lock is held.
	 */
	public function test_run_does_not_import_when_the_import_lock_is_held(): void {
		$this->import_lock->acquire( ProductImportLock::HOLDER_CLI );

		$file = $this->write_csv( "Type,SKU,Name\nsimple,IMPORT-RUNNER-LOCKED-2,Locked Product\n" );

		try {
			$this->sut->run(
				$file,
				array(
					'mapping' => array(
						'Type' => 'type',
						'SKU'  => 'sku',
						'Name' => 'name',
					),
				)
			);
			$this->fail( 'Expected ImportLockedException to be thrown.' );
		} catch ( ImportLockedException $exception ) {
			$this->assertSame( 0, wc_get_product_id_by_sku( 'IMPORT-RUNNER-LOCKED-2' ) );
		}
	}

	/**
	 * @testdox Should refresh the lock before a row once the refresh interval has passed.
	 */
	public function test_run_refreshes_the_lock_during_a_batch_when_due(): void {
		$age = $this->get_lock_age_seen_by_first_row( MINUTE_IN_SECONDS );

		$this->assertLessThan( MINUTE_IN_SECONDS, $age );
	}

	/**
	 * @testdox Should not write the lock before a row while the last refresh is recent.
	 */
	public function test_run_does_not_refresh_the_lock_before_the_interval_has_passed(): void {
		$age = $this->get_lock_age_seen_by_first_row( 0 );

		$this->assertGreaterThanOrEqual( 5 * MINUTE_IN_SECONDS, $age );
	}

	/**
	 * @testdox Should remove its lock refresh callback when a batch throws.
	 */
	public function test_run_removes_the_lock_refresh_callback_when_a_batch_throws(): void {
		$hook  = 'woocommerce_product_import_before_import';
		$count = static function () use ( $hook ): int {
			global $wp_filter;
			return count( $wp_filter[ $hook ]->callbacks[10] ?? array() );
		};

		$before = $count();
		$throw  = static function (): void {
			throw new \RuntimeException( 'Batch failed' );
		};
		add_action( $hook, $throw, 5 );

		try {
			$this->sut->run( $this->write_csv( "Type,SKU,Name\nsimple,IMPORT-RUNNER-THROW,Throwing\n" ), array( 'mapping' => $this->get_simple_mapping() ) );
			$this->fail( 'Expected the batch to throw.' );
		} catch ( \RuntimeException $exception ) {
			$this->assertSame( 'Batch failed', $exception->getMessage() );
		} finally {
			remove_action( $hook, $throw, 5 );
		}

		$this->assertSame( $before, $count() );
	}

	/**
	 * @testdox Should leave no placeholder-status posts behind after a variation's forward parent SKU reference resolves.
	 */
	public function test_run_leaves_no_placeholders_for_a_forward_parent_reference(): void {
		// The variation's row comes before its parent's row, so the parent is first created
		// as a placeholder that the parent row below must turn into the real product.
		$file = $this->write_csv(
			"Type,SKU,Name,Parent\n" .
			"variation,IMPORT-RUNNER-CHILD,,IMPORT-RUNNER-PARENT\n" .
			"variable,IMPORT-RUNNER-PARENT,Forward Reference Parent,\n"
		);

		$results = $this->sut->run(
			$file,
			array(
				'mapping' => array(
					'Type'   => 'type',
					'SKU'    => 'sku',
					'Name'   => 'name',
					'Parent' => 'parent_id',
				),
			)
		);

		$this->assertSame( array(), $results['errors'], 'Neither row should fail' );

		$parent_id = wc_get_product_id_by_sku( 'IMPORT-RUNNER-PARENT' );
		$child_id  = wc_get_product_id_by_sku( 'IMPORT-RUNNER-CHILD' );

		$this->assertNotSame( 0, $parent_id, 'The parent product should have been created' );
		$this->assertNotSame( 0, $child_id, 'The variation should have been created' );
		$this->assertSame( 'publish', get_post_status( $parent_id ), 'The placeholder parent should have been turned into a published product' );
		$this->assertSame( $parent_id, wp_get_post_parent_id( $child_id ), "The variation's parent should be the resolved parent product" );
		$this->assertSame( 0, ( new ProductImportPlaceholderCleanup() )->count_pending_placeholders(), 'No placeholder products should remain once cleanup has run' );
	}

	/**
	 * @testdox Should finish a run on an empty file instead of looping forever.
	 */
	public function test_run_finishes_on_an_empty_file(): void {
		$results = $this->sut->run( $this->write_csv( '' ), array( 'mapping' => array() ) );

		$this->assertSame( 0, $results['imported'] );
		$this->assertSame( 0, $results['failed'] );
	}

	/**
	 * @testdox Should finish a run on a file of only blank lines.
	 */
	public function test_run_finishes_on_a_blank_line_file(): void {
		$results = $this->sut->run( $this->write_csv( "\n\n\n" ), array( 'mapping' => array() ) );

		$this->assertSame( 0, $results['imported'] );
	}

	/**
	 * @testdox Should report a batch as done when it does not advance past the start position.
	 */
	public function test_run_batch_is_done_for_a_zero_byte_file(): void {
		$batch = $this->sut->run_batch( $this->write_csv( '' ), array( 'mapping' => array() ), 0 );

		$this->assertTrue( $batch['done'] );
		$this->assertSame( 0, $batch['position'] );
	}

	/**
	 * @testdox Should read no headers from a zero-byte file.
	 */
	public function test_read_headers_returns_no_headers_for_a_zero_byte_file(): void {
		$read = $this->sut->read_headers( $this->write_csv( '' ), ',', 'UTF-8' );

		$this->assertSame( array(), $read['headers'] );
	}

	/**
	 * @testdox Should finish a header-only file with nothing imported.
	 */
	public function test_run_finishes_a_header_only_file_with_zero_imported(): void {
		$results = $this->sut->run(
			$this->write_csv( "Type,SKU,Name\n" ),
			array(
				'mapping' => array(
					'Type' => 'type',
					'SKU'  => 'sku',
					'Name' => 'name',
				),
			)
		);

		$this->assertSame( 0, $results['imported'] );
		$this->assertSame( array(), $results['errors'] );
	}

	/**
	 * @testdox Should return the IDs of imported variations.
	 */
	public function test_run_returns_imported_variation_ids(): void {
		$file = $this->write_csv(
			"Type,SKU,Name,Parent\n" .
			"variable,IMPORT-RUNNER-VAR-PARENT,Variable Parent,\n" .
			"variation,IMPORT-RUNNER-VAR-CHILD,,IMPORT-RUNNER-VAR-PARENT\n"
		);

		$results = $this->sut->run(
			$file,
			array(
				'mapping' => array(
					'Type'   => 'type',
					'SKU'    => 'sku',
					'Name'   => 'name',
					'Parent' => 'parent_id',
				),
			)
		);

		$child_id = wc_get_product_id_by_sku( 'IMPORT-RUNNER-VAR-CHILD' );

		$this->assertSame( 1, $results['imported_variations'] );
		$this->assertSame( array( $child_id ), $results['imported_variation_ids'] );
	}

	/**
	 * Get a mapping for the `Type,SKU,Name` CSV columns.
	 *
	 * @return array<string, string>
	 */
	private function get_simple_mapping(): array {
		return array(
			'Type' => 'type',
			'SKU'  => 'sku',
			'Name' => 'name',
		);
	}

	/**
	 * Import one row with the lock aged 5 minutes, and report the lock's age right after the importer's own callback runs.
	 *
	 * @param int $seconds_since_refresh How long ago the run should believe it last refreshed the lock.
	 *
	 * @return int The lock's age in seconds, as seen while the row is being imported.
	 */
	private function get_lock_age_seen_by_first_row( int $seconds_since_refresh ): int {
		global $wpdb;

		$hook = 'woocommerce_product_import_before_import';
		$age  = -1;

		$last_refresh = new \ReflectionProperty( ProductImporter::class, 'last_lock_refresh' );
		$last_refresh->setAccessible( true );

		$arrange = function () use ( $wpdb, $last_refresh, $seconds_since_refresh ): void {
			$last_refresh->setValue( $this->sut, time() - $seconds_since_refresh );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$wpdb->options,
				array( 'option_value' => ( time() - 5 * MINUTE_IN_SECONDS ) . ':' . ProductImportLock::HOLDER_CLI ),
				array( 'option_name' => 'wc_product_importing' )
			);
		};
		$observe = function () use ( &$age ): void {
			$age = (int) $this->import_lock->get_age();
		};

		add_action( $hook, $arrange, 5 );
		add_action( $hook, $observe, 20 );

		try {
			$this->sut->run( $this->write_csv( "Type,SKU,Name\nsimple,IMPORT-RUNNER-REFRESH,Refreshing\n" ), array( 'mapping' => $this->get_simple_mapping() ) );
		} finally {
			remove_action( $hook, $arrange, 5 );
			remove_action( $hook, $observe, 20 );
		}

		return $age;
	}

	/**
	 * Write CSV content to a new temporary file with a `.csv` extension and mark it for clean up.
	 *
	 * @param string $content CSV file content.
	 *
	 * @return string Path to the file.
	 */
	private function write_csv( string $content ): string {
		$temp_file = wp_tempnam( 'wc-product-importer-test' );
		$csv_file  = $temp_file . '.csv';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- local test fixture file.
		rename( $temp_file, $csv_file );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- writing a local test fixture file.
		file_put_contents( $csv_file, $content );

		$this->files_to_delete[] = $csv_file;

		return $csv_file;
	}
}
