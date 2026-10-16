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
	 * @testdox Deferred rows reuse plugin-formatted SKUs and parent references without running callbacks or import hooks twice.
	 */
	public function test_preserves_formatting_and_hook_calls(): void {
		list( $parent, $variation ) = $this->create_products();
		$file                       = $this->create_csv(
			array(
				array( $variation->get_id(), ProductType::VARIATION, 'VARIATION-NEW', 'PARENT-NEW' ),
				array( $parent->get_id(), ProductType::VARIABLE, 'PARENT-NEW', '' ),
			)
		);
		$sku_calls                  = 0;
		$parent_calls               = 0;
		$imported_ids               = array();
		add_filter(
			'woocommerce_product_importer_formatting_callbacks',
			static function ( $callbacks ) use ( &$sku_calls, &$parent_calls ) {
				$callbacks[2] = static function ( $value ) use ( &$sku_calls ) {
					++$sku_calls;
					return strtolower( wc_clean( $value ) );
				};
				$parse_parent = $callbacks[3];
				$callbacks[3] = static function ( $value ) use ( $parse_parent, &$parent_calls ) {
					++$parent_calls;
					return $parse_parent( strtolower( $value ) );
				};
				return $callbacks;
			}
		);
		add_action(
			'woocommerce_product_import_before_import',
			static function ( $row ) use ( &$imported_ids ) {
				$imported_ids[] = $row['id'];
			}
		);

		$results = $this->run_import( $file, 1 );

		$this->assertEmpty( $results['failed'], 'Formatted parent references should resolve against the saved SKU.' );
		$this->assertSame( 2, $sku_calls, 'SKU formatting must run once per source row.' );
		$this->assertSame( 2, $parent_calls, 'Parent formatting must not run again during the final pass.' );
		$this->assertSame( array( $parent->get_id(), $variation->get_id() ), $imported_ids, 'The before-import hook should run once per attempted row.' );
		$this->assertSame( 'variation-new', wc_get_product( $variation->get_id() )->get_sku(), 'The deferred child should retain its formatted SKU.' );
		$this->assertSame( $parent->get_id(), wc_get_product( $variation->get_id() )->get_parent_id(), 'The formatted reference should identify the existing parent.' );
	}

	/**
	 * @testdox Conflicting parent renames use actual SKU ownership, never the last proposed mapping.
	 * @testWith [false, -1]
	 *           [true, -1]
	 *           [false, 1]
	 *           [true, 1]
	 *
	 * @param bool $child_first Whether the child needs to wait for the winning rename.
	 * @param int  $batch_size Source rows per request.
	 */
	public function test_duplicate_skus_use_actual_owner( bool $child_first, int $batch_size ): void {
		list( $original_parent, $variation ) = $this->create_products();
		$winner                              = new WC_Product_Variable();
		$winner->set_sku( 'winning-parent-old' );
		$winner->save();
		$rows  = array(
			array( $winner->get_id(), ProductType::VARIABLE, 'shared-new', '' ),
			array( $original_parent->get_id(), ProductType::VARIABLE, 'shared-new', '' ),
		);
		$child = array( $variation->get_id(), ProductType::VARIATION, 'variation-new', 'shared-new' );
		if ( $child_first ) {
			array_unshift( $rows, $child );
		} else {
			$rows[] = $child;
		}
		$file    = $this->create_csv( $rows );
		$results = $this->run_import( $file, $batch_size );

		$this->assertCount( 1, $results['failed'], 'Only the conflicting parent rename should fail.' );
		$this->assertEqualsCanonicalizing( array( $winner->get_id(), $variation->get_id() ), $results['updated'], 'The winning parent and child should update.' );
		$this->assertSame( 'parent-old', wc_get_product( $original_parent->get_id() )->get_sku(), 'The rejected rename should retain its old SKU.' );
		$this->assertSame( $winner->get_id(), wc_get_product( $variation->get_id() )->get_parent_id(), 'The child must follow actual ownership, not the rejected last proposal.' );
	}

	/**
	 * @testdox A failed parent update must not establish a relationship to its proposed SKU.
	 */
	public function test_failed_parent_update_preserves_child(): void {
		list( $parent, $variation ) = $this->create_products();
		$file                       = $this->create_csv(
			array(
				array( $variation->get_id(), ProductType::VARIATION, 'variation-new', 'parent-new' ),
				array( $parent->get_id(), 'invalid-type', 'parent-new', '' ),
			)
		);
		$results                    = $this->run_import( $file, 1 );

		$this->assertCount( 2, $results['failed'], 'Both the invalid parent row and unresolved child should fail.' );
		$this->assertEmpty( $results['updated'], 'A rejected proposal must not update either row.' );
		$this->assertSame( 'variation-old', wc_get_product( $variation->get_id() )->get_sku(), 'The child must not be partially updated.' );
		$this->assertSame( $parent->get_id(), wc_get_product( $variation->get_id() )->get_parent_id(), 'The existing relationship must remain unchanged.' );
	}

	/**
	 * @testdox A child follows a SKU's existing eligible owner even when the CSV's parent rename is rejected.
	 */
	public function test_existing_sku_owner_takes_precedence(): void {
		list( $parent, $variation ) = $this->create_products();
		$owner                      = new WC_Product_Variable();
		$owner->set_sku( 'already-owned' );
		$owner->save();
		$file    = $this->create_csv(
			array(
				array( $parent->get_id(), ProductType::VARIABLE, 'already-owned', '' ),
				array( $variation->get_id(), ProductType::VARIATION, 'variation-new', 'already-owned' ),
			)
		);
		$results = $this->run_import( $file, 1 );

		$this->assertCount( 1, $results['failed'], 'The conflicting parent rename should fail.' );
		$this->assertSame( array( $variation->get_id() ), $results['updated'], 'The child can still follow the actual owner.' );
		$this->assertSame( $owner->get_id(), wc_get_product( $variation->get_id() )->get_parent_id(), 'Ownership should take precedence over the rejected CSV proposal.' );
	}

	/**
	 * @testdox The final pass rejects ineligible parents without detaching an existing child.
	 * @testWith ["simple", "publish"]
	 *           ["variable", "trash"]
	 *           ["variable", "importing"]
	 *           ["variation", "publish"]
	 *
	 * @param string $type Ineligible parent type.
	 * @param string $status Ineligible parent status.
	 */
	public function test_rejects_ineligible_parent( string $type, string $status ): void {
		list( $parent, $variation ) = $this->create_products();
		$invalid                    = wc_get_product_object( $type );
		$invalid->set_sku( 'invalid-parent' );
		$invalid->set_status( $status );
		$invalid->save();
		$file    = $this->create_csv( array( array( $variation->get_id(), ProductType::VARIATION, 'variation-new', 'invalid-parent' ) ) );
		$results = $this->run_import( $file, 1 );

		$this->assertCount( 1, $results['failed'], 'An ineligible parent should produce one failure.' );
		$this->assertSame( 'variation-old', wc_get_product( $variation->get_id() )->get_sku(), 'The child should not be partially saved.' );
		$this->assertSame( $parent->get_id(), wc_get_product( $variation->get_id() )->get_parent_id(), 'The existing parent must remain attached.' );
	}

	/**
	 * @testdox A final pass resumes after a spent time budget without reformatting or retrying completed rows.
	 */
	public function test_final_pass_respects_time_budget(): void {
		list( $parent, $variation ) = $this->create_products();
		$file                       = $this->create_csv( array( array( $variation->get_id(), ProductType::VARIATION, 'variation-new', 'parent-new' ) ) );
		$sut                        = $this->getMockBuilder( WC_Product_CSV_Importer::class )
			->setConstructorArgs( array( $file, $this->get_args( 1 ) ) )
			->onlyMethods( array( 'time_exceeded' ) )
			->getMock();
		$sut->method( 'time_exceeded' )->willReturn( true );
		$result = $sut->import();

		$this->assertEmpty( $result['failed'], 'The final attempt must wait for a fresh request budget.' );
		$this->assertSame( 99, $sut->get_percent_complete(), 'Pending rows must prevent premature completion at EOF.' );
		$parent->set_sku( 'parent-new' );
		$parent->save();
		$result = $this->run_import( $file, 1, $sut->get_file_position() );
		$this->assertSame( array( $variation->get_id() ), $result['updated'], 'The fresh EOF request should process the pending child once.' );
		$this->assertEmpty( $result['failed'], 'The parent should now resolve.' );
	}

	/**
	 * @testdox A parsed-data filter can resolve a parent without being overridden by a pending reference.
	 */
	public function test_respects_parent_resolved_by_parsed_data_filter(): void {
		list( $parent, $variation ) = $this->create_products();
		$file                       = $this->create_csv( array( array( $variation->get_id(), ProductType::VARIATION, 'variation-new', 'missing-parent' ) ) );
		add_filter(
			'woocommerce_product_importer_parsed_data',
			static function ( $row ) use ( $parent ) {
				$row['parent_id'] = $parent->get_id();
				return $row;
			}
		);

		$results = $this->run_import( $file, 1 );

		$this->assertSame( array( $variation->get_id() ), $results['updated'], 'A plugin-resolved parent must take precedence.' );
		$this->assertEmpty( $results['failed'], 'The filter supplied a valid parent.' );
	}

	/**
	 * @testdox Time-limited imports resume after the last consumed row, including skipped, deferred, and blank rows.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $skip_first Whether the consumed row is skipped instead of deferred.
	 */
	public function test_resume_cursor_after_consumed_rows( bool $skip_first ): void {
		list( $parent, $variation ) = $this->create_products();
		$child                      = array( $variation->get_id(), ProductType::VARIATION, 'variation-new', 'parent-new' );
		$rows                       = array(
			array( '', '', '', '' ),
			$skip_first ? array( 999999999, ProductType::SIMPLE, 'missing-product', '' ) : $child,
		);
		if ( $skip_first ) {
			$rows[] = $child;
		}
		$rows[] = array( $parent->get_id(), ProductType::VARIABLE, 'parent-new', '' );
		$file   = $this->create_csv( $rows );
		$sut    = $this->getMockBuilder( WC_Product_CSV_Importer::class )
			->setConstructorArgs( array( $file, $this->get_args( -1 ) ) )
			->onlyMethods( array( 'time_exceeded' ) )
			->getMock();
		$sut->method( 'time_exceeded' )->willReturn( true );
		$first = $sut->import();

		$this->assertEmpty( $first['updated'], 'The time budget should stop before subsequent product rows.' );
		$this->assertLessThan( filesize( $file ), $sut->get_file_position(), 'The source cursor must not skip unread product rows.' );
		$results = $this->run_import( $file, -1, $sut->get_file_position() );
		$this->assertEqualsCanonicalizing( array( $parent->get_id(), $variation->get_id() ), $results['updated'], 'Resuming should update both remaining products once.' );
		$this->assertEmpty( $results['failed'], 'Resuming must not lose the deferred relationship.' );
	}

	/**
	 * @testdox New-product imports keep their existing placeholder-based parent resolution.
	 */
	public function test_new_imports_still_create_placeholders(): void {
		$file                    = $this->create_csv( array( array( '', ProductType::VARIATION, 'new-child', 'new-parent' ) ) );
		$args                    = $this->get_args( 1 );
		$args['update_existing'] = false;
		$sut                     = new WC_Product_CSV_Importer( $file, $args );
		$placeholder_id          = wc_get_product_id_by_sku( 'new-parent' );

		$this->assertGreaterThan( 0, $placeholder_id, 'Creating new products should still reserve a parent placeholder.' );
		$this->assertSame( 'importing', wc_get_product( $placeholder_id )->get_status(), 'The placeholder lifecycle must be unchanged.' );
		$this->assertSame( $placeholder_id, $sut->get_parsed_data()[0]['parent_id'], 'The new child should reference its placeholder.' );
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
	 * @param int    $position Initial source cursor.
	 * @return array Aggregated results.
	 */
	private function run_import( string $file, int $batch_size, int $position = 0 ): array {
		$results = array(
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
