<?php
/**
 * Tests for updating CSV variation parents across import batches.
 *
 * @package WooCommerce\Tests\Importer
 */

declare( strict_types=1 );

use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\ProductCSVImport\DeferredRows;

/**
 * Tests the deferred parent resolution pass.
 */
class WC_Product_CSV_Importer_Deferred_Rows_Test extends WC_Unit_Test_Case {

	/**
	 * Temporary source files to remove even when assertions fail.
	 *
	 * @var string[]
	 */
	private $csv_files = array();

	/**
	 * Load importer dependencies.
	 */
	public function setUp(): void {
		parent::setUp();
		require_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';
	}

	/**
	 * Remove temporary files and pending rows.
	 */
	public function tearDown(): void {
		try {
			foreach ( $this->csv_files as $file ) {
				( new DeferredRows( $file ) )->clear();
				wp_delete_file( $file );
			}
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Parent and variation SKUs update together regardless of row order or batch boundaries.
	 * @testWith [false, -1]
	 *           [true, -1]
	 *           [false, 1]
	 *           [true, 1]
	 *
	 * @param bool $child_first Whether the variation precedes its parent.
	 * @param int  $batch_size Number of source rows per request.
	 */
	public function test_updates_parent_and_variation_skus( bool $child_first, int $batch_size ): void {
		list( $parent, $variation ) = $this->create_products();
		$rows                       = array(
			array( $parent->get_id(), ProductType::VARIABLE, 'parent-new', '' ),
			array( $variation->get_id(), ProductType::VARIATION, 'variation-new', 'parent-new' ),
		);
		$file                       = $this->create_csv( $child_first ? array_reverse( $rows ) : $rows );
		$results                    = $this->run_import( $file, $batch_size );

		$this->assertEqualsCanonicalizing( array( $parent->get_id(), $variation->get_id() ), $results['updated'], 'Both rows should update exactly once.' );
		$this->assertEmpty( $results['failed'], 'Neither row should fail.' );
		$this->assertSame( 'parent-new', wc_get_product( $parent->get_id() )->get_sku(), 'The parent should receive its new SKU.' );
		$this->assertSame( 'variation-new', wc_get_product( $variation->get_id() )->get_sku(), 'The variation should receive its new SKU.' );
		$this->assertSame( $parent->get_id(), wc_get_product( $variation->get_id() )->get_parent_id(), 'The relationship must survive the import.' );
		$this->assertFalse( ( new DeferredRows( $file ) )->has_rows(), 'Completed imports should leave no pending rows.' );
	}

	/**
	 * @testdox An unresolved child is not changed or given a placeholder while later batches are pending.
	 */
	public function test_defers_child_without_saving_or_creating_placeholder(): void {
		list( $parent, $variation ) = $this->create_products();
		$file                       = $this->create_csv(
			array(
				array( $variation->get_id(), ProductType::VARIATION, 'variation-new', 'parent-new' ),
				array( $parent->get_id(), ProductType::VARIABLE, 'parent-new', '' ),
			)
		);
		$sut                        = new WC_Product_CSV_Importer( $file, $this->get_args( 1 ) );

		$this->assertSame( 0, wc_get_product_id_by_sku( 'parent-new' ), 'Parsing must not create an update parent placeholder.' );
		$result = $sut->import();

		$this->assertEmpty( $result['updated'], 'The child must wait for its parent.' );
		$this->assertEmpty( $result['failed'], 'Deferral is not an import failure.' );
		$this->assertSame( 'variation-old', wc_get_product( $variation->get_id() )->get_sku(), 'Deferral must not partially save the child.' );
		$this->assertTrue( ( new DeferredRows( $file ) )->has_rows(), 'The formatted row must survive the next request.' );
	}

	/**
	 * @testdox A parent that never resolves produces one final failure without changing the existing variation.
	 */
	public function test_reports_unresolved_parent_once(): void {
		list( $parent, $variation ) = $this->create_products();
		$file                       = $this->create_csv( array( array( $variation->get_id(), ProductType::VARIATION, 'variation-new', 'missing-parent' ) ) );
		$results                    = $this->run_import( $file, 1 );

		$this->assertCount( 1, $results['failed'], 'An unresolved row should fail exactly once.' );
		$this->assertEmpty( $results['updated'], 'An unresolved child should not be saved.' );
		$this->assertSame( 'variation-old', wc_get_product( $variation->get_id() )->get_sku(), 'Failure must preserve the existing SKU.' );
		$this->assertSame( $parent->get_id(), wc_get_product( $variation->get_id() )->get_parent_id(), 'Failure must preserve the existing parent.' );
		$this->assertSame( 0, wc_get_product_id_by_sku( 'missing-parent' ), 'No placeholder should reserve the missing SKU.' );
		$this->assertFalse( ( new DeferredRows( $file ) )->has_rows(), 'A failed final attempt must not be retried.' );
	}

	/**
	 * Create an existing variable parent and variation.
	 *
	 * @return array{WC_Product_Variable, WC_Product_Variation}
	 */
	private function create_products(): array {
		$parent = new WC_Product_Variable();
		$parent->set_sku( 'parent-old' );
		$parent->save();
		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $parent->get_id() );
		$variation->set_sku( 'variation-old' );
		$variation->save();
		return array( $parent, $variation );
	}

	/**
	 * Write a CSV fixture.
	 *
	 * @param array $rows Product rows.
	 * @return string
	 */
	private function create_csv( array $rows ): string {
		$file              = trailingslashit( get_temp_dir() ) . wp_generate_uuid4() . '.csv';
		$this->csv_files[] = $file;
		$contents          = "ID,Type,SKU,Parent\n";
		foreach ( $rows as $row ) {
			$contents .= implode( ',', $row ) . "\n";
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture written to the temp directory.
		file_put_contents( $file, $contents );
		return $file;
	}

	/**
	 * Get mapped update-import arguments.
	 *
	 * @param int $batch_size Source rows per request.
	 * @param int $position Source file cursor.
	 * @return array
	 */
	private function get_args( int $batch_size, int $position = 0 ): array {
		return array(
			'parse'           => true,
			'update_existing' => true,
			'lines'           => $batch_size,
			'start_pos'       => $position,
			'mapping'         => array(
				'ID'     => 'id',
				'Type'   => 'type',
				'SKU'    => 'sku',
				'Parent' => 'parent_id',
			),
		);
	}

	/**
	 * Simulate the controller's requests, including repeated EOF requests for pending rows.
	 *
	 * @param string $file CSV fixture.
	 * @param int    $batch_size Source rows per request.
	 * @return array Aggregated results.
	 */
	private function run_import( string $file, int $batch_size ): array {
		$position = 0;
		$results  = array(
			'updated'             => array(),
			'failed'              => array(),
			'skipped'             => array(),
			'imported'            => array(),
			'imported_variations' => array(),
		);
		for ( $request = 0; $request < 10; ++$request ) {
			$sut    = new WC_Product_CSV_Importer( $file, $this->get_args( $batch_size, $position ) );
			$result = $sut->import();
			foreach ( $result as $type => $items ) {
				$results[ $type ] = array_merge( $results[ $type ], $items );
			}
			if ( 100 === $sut->get_percent_complete() ) {
				return $results;
			}
			$position = $sut->get_file_position();
		}
		$this->fail( 'The import did not complete after ten bounded requests.' );
	}
}
