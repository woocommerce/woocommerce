<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Importer;

use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\FulfillmentsCsvImporter;

/**
 * Tests for the Fulfillments CSV importer service.
 */
class FulfillmentsCsvImporterTest extends \WC_Unit_Test_Case {

	/**
	 * Original value of the fulfillments feature flag.
	 *
	 * @var mixed
	 */
	private static $original_fulfillments_flag;

	/**
	 * Paths to temporary CSV files created by tests; cleaned up in tearDown.
	 *
	 * @var array<int, string>
	 */
	private array $temp_files = array();

	/**
	 * Bootstrap the fulfillments feature for the test run.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$original_fulfillments_flag = get_option( 'woocommerce_feature_fulfillments_enabled' );
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();
	}

	/**
	 * Restore the original feature flag value.
	 */
	public static function tearDownAfterClass(): void {
		if ( false === self::$original_fulfillments_flag ) {
			delete_option( 'woocommerce_feature_fulfillments_enabled' );
		} else {
			update_option( 'woocommerce_feature_fulfillments_enabled', self::$original_fulfillments_flag );
		}
		parent::tearDownAfterClass();
	}

	/**
	 * Clean up any temp files between tests.
	 */
	public function tearDown(): void {
		foreach ( $this->temp_files as $path ) {
			if ( file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}
		$this->temp_files = array();
		parent::tearDown();
	}

	/**
	 * Write the given CSV content to a temp file and return its path.
	 *
	 * @param string $content CSV content.
	 * @return string
	 */
	private function make_csv( string $content ): string {
		$path = wp_tempnam( 'wc-fulfillments-import-' );
		file_put_contents( $path, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write.
		$this->temp_files[] = $path;
		return $path;
	}

	/**
	 * @testdox Header aliases (e.g. "Tracking" / "Carrier") are normalized to the canonical columns.
	 */
	public function test_header_aliases_are_accepted(): void {
		$csv  = "Order,Tracking,Carrier\n1,ALIAS-1,ups\n";
		$file = $this->make_csv( $csv );

		$parsed = ( new FulfillmentsCsvImporter( $file ) )->parse_headers();

		$this->assertArrayNotHasKey( 'error', $parsed );
		$this->assertSame(
			array(
				0 => FulfillmentsCsvImporter::COL_ORDER_NUMBER,
				1 => FulfillmentsCsvImporter::COL_TRACKING_NUMBER,
				2 => FulfillmentsCsvImporter::COL_PROVIDER,
			),
			$parsed['detected_mapping']
		);
	}

	/**
	 * @testdox Canonical keys the importer does not know are dropped from the column-aliases filter.
	 */
	public function test_alias_filter_ignores_unknown_canonical_keys(): void {
		$filter = function ( $aliases ) {
			$aliases['warehouse_bay'] = array( 'bay' );
			return $aliases;
		};
		add_filter( 'woocommerce_fulfillments_csv_importer_column_aliases', $filter );

		try {
			$csv    = "order_number,tracking_number,shipment_provider,bay\n1,UNKNOWN-KEY,ups,A1\n";
			$sut    = new FulfillmentsCsvImporter( $this->make_csv( $csv ) );
			$parsed = $sut->parse_headers();
		} finally {
			remove_filter( 'woocommerce_fulfillments_csv_importer_column_aliases', $filter );
		}

		// An unknown key would be auto-detected here even though the importer can never import it.
		$this->assertNotContains( 'warehouse_bay', $parsed['detected_mapping'] );
	}

	/**
	 * @testdox Malformed values from the column-aliases filter are dropped instead of fataling the import.
	 */
	public function test_malformed_alias_filter_output_is_tolerated(): void {
		$filter = function ( $aliases ) {
			// A misbehaving callback: non-array alias list, non-string entries, junk key.
			$aliases[ FulfillmentsCsvImporter::COL_TRACKING_URL ] = 'not-an-array';
			$aliases[ FulfillmentsCsvImporter::COL_ITEMS ]        = array( 42, null, 'items' );
			$aliases[0] = array( 'zero' );
			return $aliases;
		};
		add_filter( 'woocommerce_fulfillments_csv_importer_column_aliases', $filter );

		try {
			$csv    = "order_number,tracking_number,shipment_provider,items\n1,ALIAS-BAD,ups,\n";
			$parsed = ( new FulfillmentsCsvImporter( $this->make_csv( $csv ) ) )->parse_headers();
		} finally {
			remove_filter( 'woocommerce_fulfillments_csv_importer_column_aliases', $filter );
		}

		$this->assertArrayNotHasKey( 'error', $parsed );
		$this->assertSame(
			array(
				0 => FulfillmentsCsvImporter::COL_ORDER_NUMBER,
				1 => FulfillmentsCsvImporter::COL_TRACKING_NUMBER,
				2 => FulfillmentsCsvImporter::COL_PROVIDER,
				3 => FulfillmentsCsvImporter::COL_ITEMS,
			),
			$parsed['detected_mapping'],
			'Valid alias entries must keep working when the filter returns junk for others'
		);
	}

	/**
	 * @testdox parse_headers returns headers, sample row, total and auto-detected mapping.
	 */
	public function test_parse_headers_returns_metadata_and_mapping(): void {
		$csv  = "Order ID,Tracking,Carrier,URL,Items\n"
			. "12345,1Z999AA,UPS,https://example.com,SKU-A:1\n"
			. "67890,1Z000AA,UPS,https://example.com,SKU-B:1\n";
		$file = $this->make_csv( $csv );

		$sut    = new FulfillmentsCsvImporter( $file );
		$parsed = $sut->parse_headers();

		$this->assertArrayNotHasKey( 'error', $parsed );
		$this->assertSame( array( 'Order ID', 'Tracking', 'Carrier', 'URL', 'Items' ), $parsed['headers'] );
		$this->assertSame( array( '12345', '1Z999AA', 'UPS', 'https://example.com', 'SKU-A:1' ), $parsed['sample'] );
		$this->assertSame( 2, $parsed['total'] );
		$this->assertFalse( $parsed['total_capped'], 'A file under the cap has an exact total' );
		$this->assertSame( ',', $parsed['delimiter'] );

		$mapping = $parsed['detected_mapping'];
		$this->assertSame( FulfillmentsCsvImporter::COL_ORDER_NUMBER, $mapping[0] );
		$this->assertSame( FulfillmentsCsvImporter::COL_TRACKING_NUMBER, $mapping[1] );
		$this->assertSame( FulfillmentsCsvImporter::COL_PROVIDER, $mapping[2] );
		$this->assertSame( FulfillmentsCsvImporter::COL_TRACKING_URL, $mapping[3] );
		$this->assertSame( FulfillmentsCsvImporter::COL_ITEMS, $mapping[4] );
	}

	/**
	 * @testdox parse_headers honors an explicit non-comma delimiter.
	 */
	public function test_parse_headers_honors_explicit_delimiter(): void {
		$csv  = "order_number;tracking_number;shipment_provider\n1;TRACK-1;ups\n";
		$file = $this->make_csv( $csv );

		$parsed = ( new FulfillmentsCsvImporter( $file, array( 'delimiter' => ';' ) ) )->parse_headers();

		$this->assertArrayNotHasKey( 'error', $parsed );
		$this->assertSame( ';', $parsed['delimiter'] );
		$this->assertSame( array( 'order_number', 'tracking_number', 'shipment_provider' ), $parsed['headers'] );
	}

	/**
	 * @testdox An empty delimiter option falls back to comma.
	 */
	public function test_empty_delimiter_falls_back_to_comma(): void {
		$csv  = "order_number,tracking_number,shipment_provider\n1,TRACK-1,ups\n";
		$file = $this->make_csv( $csv );

		$parsed = ( new FulfillmentsCsvImporter( $file, array( 'delimiter' => '' ) ) )->parse_headers();

		$this->assertArrayNotHasKey( 'error', $parsed );
		$this->assertSame( ',', $parsed['delimiter'] );
	}

	/**
	 * @testdox A multi-character delimiter is clamped to its first byte so fgetcsv() does not throw.
	 */
	public function test_multi_character_delimiter_is_clamped_to_single_byte(): void {
		$csv  = "order_number;tracking_number;shipment_provider\n1;TRACK-1;ups\n";
		$file = $this->make_csv( $csv );

		$parsed = ( new FulfillmentsCsvImporter( $file, array( 'delimiter' => ';;' ) ) )->parse_headers();

		$this->assertArrayNotHasKey( 'error', $parsed );
		$this->assertSame( ';', $parsed['delimiter'] );
	}

	/**
	 * @testdox An enclosure fgetcsv() cannot take falls back to the double quote instead of throwing.
	 *
	 * @testWith ["ab"]
	 *           [""]
	 *           [["\""]]
	 *
	 * @param mixed $enclosure Enclosure option.
	 */
	public function test_invalid_enclosure_falls_back_to_double_quote( $enclosure ): void {
		$csv  = "\"order_number\",\"tracking_number\",\"shipment_provider\"\n\"1\",\"TRACK, 1\",\"ups\"\n";
		$file = $this->make_csv( $csv );

		$parsed = ( new FulfillmentsCsvImporter( $file, array( 'enclosure' => $enclosure ) ) )->parse_headers();

		$this->assertArrayNotHasKey( 'error', $parsed, 'An unusable enclosure must not fail the parse' );
		$this->assertSame( array( 'order_number', 'tracking_number', 'shipment_provider' ), $parsed['headers'], 'Headers must be read with the default enclosure' );
		$this->assertSame( array( '1', 'TRACK, 1', 'ups' ), $parsed['sample'], 'The enclosed comma must stay inside its field' );
	}

	/**
	 * @testdox A multibyte delimiter falls back to comma instead of a truncated byte fragment.
	 */
	public function test_multibyte_delimiter_falls_back_to_comma(): void {
		// Em dash and left double quotation mark, written as UTF-8 byte sequences.
		$this->assertSame( ',', FulfillmentsCsvImporter::normalize_delimiter( "\xE2\x80\x94" ) );
		$this->assertSame( ',', FulfillmentsCsvImporter::normalize_delimiter( "\xE2\x80\x9C" ) );
		$this->assertSame( "\t", FulfillmentsCsvImporter::normalize_delimiter( "\t" ), 'ASCII control delimiters like tab must be preserved' );
		$this->assertSame( '|', FulfillmentsCsvImporter::normalize_delimiter( '|' ) );
	}

	/**
	 * @testdox The spelled-out tab forms map to a tab character.
	 *
	 * @testWith ["\\t"]
	 *           ["\\\\t"]
	 *           ["tab"]
	 *           ["TAB"]
	 *
	 * @param string $spelling Delimiter input.
	 */
	public function test_tab_spellings_map_to_tab( string $spelling ): void {
		$this->assertSame( "\t", FulfillmentsCsvImporter::normalize_delimiter( $spelling ) );
		$this->assertTrue( FulfillmentsCsvImporter::is_tab_spelling( $spelling ) );
	}

	/**
	 * @testdox Line breaks and the enclosure character fall back to comma.
	 *
	 * @testWith ["\n"]
	 *           ["\r"]
	 *           ["\""]
	 *
	 * @param string $delimiter Delimiter input.
	 */
	public function test_unusable_delimiters_fall_back_to_comma( string $delimiter ): void {
		$this->assertSame( ',', FulfillmentsCsvImporter::normalize_delimiter( $delimiter ) );
		$this->assertFalse( FulfillmentsCsvImporter::is_tab_spelling( $delimiter ) );
	}

	/**
	 * @testdox parse_headers reads a tab-separated file when the delimiter is spelled out.
	 */
	public function test_parse_headers_honors_spelled_out_tab_delimiter(): void {
		$file = $this->make_csv( "order_number\ttracking_number\tshipment_provider\n1\tTAB-1\tups\n" );

		$parsed = ( new FulfillmentsCsvImporter( $file ) )->parse_headers( 'tab' );

		$this->assertArrayNotHasKey( 'error', $parsed );
		$this->assertSame( "\t", $parsed['delimiter'] );
		$this->assertSame( array( 'order_number', 'tracking_number', 'shipment_provider' ), $parsed['headers'] );
		$this->assertSame( array( '1', 'TAB-1', 'ups' ), $parsed['sample'] );
	}

	/**
	 * @testdox parse_headers strips a UTF-8 BOM from the first header cell.
	 */
	public function test_parse_headers_strips_bom(): void {
		$file = $this->make_csv( "\xEF\xBB\xBForder_number,tracking_number,shipment_provider\n1,BOM-1,ups\n" );

		$parsed = ( new FulfillmentsCsvImporter( $file ) )->parse_headers();

		$this->assertArrayNotHasKey( 'error', $parsed );
		$this->assertSame( 'order_number', $parsed['headers'][0] );
		$this->assertSame( FulfillmentsCsvImporter::COL_ORDER_NUMBER, $parsed['detected_mapping'][0] );
	}

	/**
	 * @testdox parse_headers reports zero rows and no sample for a header-only file.
	 */
	public function test_parse_headers_reports_zero_rows_for_header_only_file(): void {
		$file = $this->make_csv( "order_number,tracking_number,shipment_provider\n" );

		$parsed = ( new FulfillmentsCsvImporter( $file ) )->parse_headers();

		$this->assertArrayNotHasKey( 'error', $parsed );
		$this->assertSame( 0, $parsed['total'] );
		$this->assertSame( array(), $parsed['sample'] );
		$this->assertSame( array( 'order_number', 'tracking_number', 'shipment_provider' ), $parsed['headers'] );
	}

	/**
	 * @testdox parse_headers skips blank lines before the header.
	 */
	public function test_parse_headers_skips_leading_blank_lines(): void {
		$file = $this->make_csv( "\n\norder_number,tracking_number,shipment_provider\n1,BLANK-1,ups\n" );

		$parsed = ( new FulfillmentsCsvImporter( $file ) )->parse_headers();

		$this->assertArrayNotHasKey( 'error', $parsed );
		$this->assertSame( array( 'order_number', 'tracking_number', 'shipment_provider' ), $parsed['headers'] );
		$this->assertSame( 1, $parsed['total'] );
		$this->assertSame( array( '1', 'BLANK-1', 'ups' ), $parsed['sample'] );
	}

	/**
	 * @testdox parse_headers reports an empty CSV when the file holds only blank lines.
	 */
	public function test_parse_headers_reports_empty_csv_for_blank_lines_only(): void {
		$file = $this->make_csv( "\n\n,,\n" );

		$parsed = ( new FulfillmentsCsvImporter( $file ) )->parse_headers();

		$this->assertArrayHasKey( 'error', $parsed );
		$this->assertSame( 'empty_csv', $parsed['error']['code'] );
	}

	/**
	 * @testdox parse_headers does not count blank data lines towards the total.
	 */
	public function test_parse_headers_ignores_blank_data_lines(): void {
		$file = $this->make_csv( "order_number,tracking_number,shipment_provider\n\n1,ROW-1,ups\n,,\n   \n2,ROW-2,ups\n\n" );

		$parsed = ( new FulfillmentsCsvImporter( $file ) )->parse_headers();

		$this->assertArrayNotHasKey( 'error', $parsed );
		$this->assertSame( 2, $parsed['total'] );
		$this->assertSame( array( '1', 'ROW-1', 'ups' ), $parsed['sample'] );
	}

	/**
	 * @testdox parse_headers counts rows past the cap by one so the caller can reject the file.
	 */
	public function test_parse_headers_counts_one_row_past_the_cap(): void {
		$csv = "order_number,tracking_number,shipment_provider\n";
		for ( $i = 1; $i <= FulfillmentsCsvImporter::MAX_IMPORT_ROWS + 50; $i++ ) {
			$csv .= "1,CAP-{$i},ups\n";
		}
		$file = $this->make_csv( $csv );

		$parsed = ( new FulfillmentsCsvImporter( $file ) )->parse_headers();

		$this->assertSame( FulfillmentsCsvImporter::MAX_IMPORT_ROWS + 1, $parsed['total'] );
		$this->assertTrue( $parsed['total_capped'], 'A file larger than the cap must not report an exact total' );
	}

	/**
	 * @testdox A file with exactly one row past the cap still reports that exact total.
	 */
	public function test_parse_headers_exact_total_one_row_past_the_cap_is_not_a_sentinel(): void {
		$csv = "order_number,tracking_number,shipment_provider\n";
		for ( $i = 1; $i <= FulfillmentsCsvImporter::MAX_IMPORT_ROWS + 1; $i++ ) {
			$csv .= "1,EXACT-{$i},ups\n";
		}

		$parsed = ( new FulfillmentsCsvImporter( $this->make_csv( $csv ) ) )->parse_headers();

		$this->assertSame( FulfillmentsCsvImporter::MAX_IMPORT_ROWS + 1, $parsed['total'] );
		$this->assertFalse( $parsed['total_capped'], 'Exactly 5,001 data rows is a real total, not the over-cap sentinel' );
	}

	/**
	 * @testdox Blank rows do not count toward the import cap.
	 */
	public function test_parse_headers_does_not_count_blank_rows_toward_the_cap(): void {
		$csv = "order_number,tracking_number,shipment_provider\n";
		for ( $i = 1; $i <= FulfillmentsCsvImporter::MAX_IMPORT_ROWS; $i++ ) {
			$csv .= "1,CAP-{$i},ups\n,,,\n";
		}

		$parsed = ( new FulfillmentsCsvImporter( $this->make_csv( $csv ) ) )->parse_headers();

		$this->assertSame( FulfillmentsCsvImporter::MAX_IMPORT_ROWS, $parsed['total'] );
		$this->assertFalse( $parsed['total_capped'], 'Blank rows between data rows must not push the file over the cap' );
	}

	/**
	 * @testdox The first of two headers mapping to the same canonical column wins.
	 */
	public function test_parse_headers_first_duplicate_header_wins(): void {
		$file = $this->make_csv( "tracking_number,tracking,order_number,shipment_provider\nT-1,T-2,1,ups\n" );

		$parsed = ( new FulfillmentsCsvImporter( $file ) )->parse_headers();

		$this->assertSame( FulfillmentsCsvImporter::COL_TRACKING_NUMBER, $parsed['detected_mapping'][0] );
		$this->assertArrayNotHasKey( 1, $parsed['detected_mapping'] );
		$this->assertSame( FulfillmentsCsvImporter::COL_ORDER_NUMBER, $parsed['detected_mapping'][2] );
	}

	/**
	 * @testdox parse_headers handles CRLF line endings without leaking the carriage return into cells.
	 */
	public function test_parse_headers_handles_crlf_line_endings(): void {
		$file = $this->make_csv( "order_number,tracking_number,shipment_provider\r\n1,CRLF-1,ups\r\n2,CRLF-2,ups\r\n" );

		$parsed = ( new FulfillmentsCsvImporter( $file ) )->parse_headers();

		$this->assertArrayNotHasKey( 'error', $parsed );
		$this->assertSame( array( 'order_number', 'tracking_number', 'shipment_provider' ), $parsed['headers'] );
		$this->assertSame( array( '1', 'CRLF-1', 'ups' ), $parsed['sample'] );
		$this->assertSame( 2, $parsed['total'] );
	}

	/**
	 * @testdox An alias added through the filter is matched regardless of its casing and punctuation.
	 */
	public function test_alias_filter_entries_are_normalized(): void {
		$filter = function ( $aliases ) {
			$aliases[ FulfillmentsCsvImporter::COL_ORDER_NUMBER ][] = 'PO Number';
			return $aliases;
		};
		add_filter( 'woocommerce_fulfillments_csv_importer_column_aliases', $filter );

		try {
			$file   = $this->make_csv( "po_number,tracking_number,shipment_provider\n1,PO-1,ups\n" );
			$parsed = ( new FulfillmentsCsvImporter( $file ) )->parse_headers();
		} finally {
			remove_filter( 'woocommerce_fulfillments_csv_importer_column_aliases', $filter );
		}

		$this->assertSame( FulfillmentsCsvImporter::COL_ORDER_NUMBER, $parsed['detected_mapping'][0] );
	}

	/**
	 * @testdox The default aliases are used when the filter leaves nothing usable.
	 */
	public function test_alias_filter_without_usable_entries_falls_back_to_defaults(): void {
		$filter = function () {
			return array( FulfillmentsCsvImporter::COL_ORDER_NUMBER => array( 42, '', '  ' ) );
		};
		add_filter( 'woocommerce_fulfillments_csv_importer_column_aliases', $filter );

		try {
			$file   = $this->make_csv( "order_number,tracking_number,shipment_provider\n1,DEF-1,ups\n" );
			$parsed = ( new FulfillmentsCsvImporter( $file ) )->parse_headers();
		} finally {
			remove_filter( 'woocommerce_fulfillments_csv_importer_column_aliases', $filter );
		}

		$this->assertSame(
			array(
				0 => FulfillmentsCsvImporter::COL_ORDER_NUMBER,
				1 => FulfillmentsCsvImporter::COL_TRACKING_NUMBER,
				2 => FulfillmentsCsvImporter::COL_PROVIDER,
			),
			$parsed['detected_mapping']
		);
	}

	/**
	 * @testdox parse_headers reports an error when the file is empty.
	 */
	public function test_parse_headers_reports_empty_csv(): void {
		$file   = $this->make_csv( '' );
		$parsed = ( new FulfillmentsCsvImporter( $file ) )->parse_headers( ',' );

		$this->assertArrayHasKey( 'error', $parsed );
		$this->assertSame( 'empty_csv', $parsed['error']['code'] );
	}

	/**
	 * @testdox parse_headers reports an error when the file is missing.
	 */
	public function test_parse_headers_reports_missing_file(): void {
		$parsed = ( new FulfillmentsCsvImporter( '/nonexistent/path/missing.csv' ) )->parse_headers( ',' );

		$this->assertArrayHasKey( 'error', $parsed );
		$this->assertSame( 'file_not_readable', $parsed['error']['code'] );
	}

	/**
	 * @testdox Rows whose cells all trim to empty are blank, including ",,," and whitespace.
	 */
	public function test_blank_rows_include_empty_fields_and_whitespace(): void {
		$file = $this->make_csv( ",,,\n   \norder_number,tracking_number,shipment_provider\n , , \n1,ROW-1,ups\n\t\t\t\n0,0,ups\n,,,\n" );

		$parsed = ( new FulfillmentsCsvImporter( $file ) )->parse_headers();
		$read   = ( new FulfillmentsCsvImporter( $file ) )->read_data_rows( 0, 10 );

		$this->assertArrayNotHasKey( 'error', $parsed );
		$this->assertSame( array( 'order_number', 'tracking_number', 'shipment_provider' ), $parsed['headers'] );
		$this->assertSame( 2, $parsed['total'], 'Only the two non-blank data rows count' );
		$this->assertSame( array( '1', 'ROW-1', 'ups' ), $parsed['sample'] );
		$this->assertSame( $parsed['total'], $read['consumed'] );
		$this->assertSame(
			array(
				array(
					'row_number' => 5,
					'cells'      => array( '1', 'ROW-1', 'ups' ),
				),
				array(
					'row_number' => 7,
					'cells'      => array( '0', '0', 'ups' ),
				),
			),
			$read['rows'],
			'Physical record numbers keep the leading blanks, the header, and the blank data rows'
		);
		$this->assertSame( 3, $read['header_row_number'] );
		$this->assertTrue( $read['eof'] );
	}

	/**
	 * @testdox Offset fast-forward and a resumed byte offset both count only real data rows.
	 */
	public function test_read_data_rows_skips_and_resumes_on_real_data_rows(): void {
		$file = $this->make_csv( "\norder_number,tracking_number,shipment_provider\n\n1,ONE,ups\n,,,\n , , \n2,TWO,ups\n\n3,THREE,ups\n" );
		$sut  = new FulfillmentsCsvImporter( $file );

		$first = $sut->read_data_rows( 0, 1 );
		$this->assertSame( 4, $first['rows'][0]['row_number'], 'The first data row keeps its physical record number' );
		$this->assertSame( array( '1', 'ONE', 'ups' ), $first['rows'][0]['cells'] );
		$this->assertSame( 1, $first['consumed'] );
		$this->assertSame( 0, $first['skipped'] );
		$this->assertFalse( $first['eof'] );
		$this->assertSame( 7, $first['next_record_number'], 'The saved record number moves past the blank rows after the first data row' );

		$resumed = $sut->read_data_rows(
			0,
			1,
			array(
				'byte_offset'   => $first['byte_offset'],
				'record_number' => $first['next_record_number'],
			)
		);
		$this->assertSame( array( '2', 'TWO', 'ups' ), $resumed['rows'][0]['cells'] );
		$this->assertSame( 7, $resumed['rows'][0]['row_number'] );

		$skipped = $sut->read_data_rows( 1, 1 );
		$this->assertSame( $resumed['rows'], $skipped['rows'], 'Skipping one data row lands on the same record as the byte offset' );
		$this->assertSame( 1, $skipped['skipped'] );
		$this->assertSame( 1, $skipped['consumed'] );
		$this->assertFalse( $skipped['eof'] );

		$rest = $sut->read_data_rows(
			0,
			10,
			array(
				'byte_offset'   => $resumed['byte_offset'],
				'record_number' => $resumed['next_record_number'],
			)
		);
		$this->assertSame( array( '3', 'THREE', 'ups' ), $rest['rows'][0]['cells'] );
		$this->assertTrue( $rest['eof'], 'Trailing blank rows are not another data row' );
		$this->assertSame( 1, $rest['consumed'] );
	}

	/**
	 * @testdox A CR-only file is rejected and the merchant is told to re-save it as CSV.
	 */
	public function test_cr_only_line_endings_are_rejected(): void {
		$file   = $this->make_csv( "order_number,tracking_number,shipment_provider\r1,CR-1,ups\r2,CR-2,ups\r" );
		$parsed = ( new FulfillmentsCsvImporter( $file ) )->parse_headers();
		$read   = ( new FulfillmentsCsvImporter( $file ) )->read_data_rows( 0, 10 );

		$this->assertSame( 'cr_line_endings', $parsed['error']['code'] );
		$this->assertStringContainsString( 'Re-save', $parsed['error']['message'] );
		$this->assertStringContainsString( 'CSV', $parsed['error']['message'] );
		$this->assertSame( 'cr_line_endings', $read['error']['code'] );
	}

	/**
	 * @testdox Windows-1252 text is converted at the shared read boundary, and valid UTF-8 is left unchanged.
	 */
	public function test_character_encoding_is_normalized_for_prepare_and_row_reads(): void {
		if ( ! function_exists( 'mb_check_encoding' ) || ! function_exists( 'mb_convert_encoding' ) ) {
			$this->markTestSkipped( 'The mbstring extension is required for this test.' );
		}

		$utf8_file = $this->make_csv( "caf\xC3\xA9,tracking_number,shipment_provider\n1,Spediteur M\xC3\xBCller,ups\n" );
		$utf8      = ( new FulfillmentsCsvImporter( $utf8_file ) )->parse_headers();
		$this->assertSame( 'café', $utf8['headers'][0], 'Valid UTF-8 headers must not be reinterpreted as Windows-1252' );
		$this->assertSame( 'Spediteur Müller', $utf8['sample'][1] );

		$euro    = "\xE2\x82\xAC";
		$padding = str_repeat( 'a', 9000 );
		$long    = ( new FulfillmentsCsvImporter( $this->make_csv( "order_number,tracking_number,shipment_provider\n1,{$padding}{$euro}{$padding},ups\n" ) ) )->parse_headers();
		$this->assertSame( $padding . $euro . $padding, $long['sample'][1], 'A multibyte character past a read boundary must stay valid UTF-8' );

		$legacy_file = $this->make_csv( "order_number,tracking_number,shipment_provider\n1,\xC3\xA9,Spediteur M\xFCller\n" );
		$legacy      = ( new FulfillmentsCsvImporter( $legacy_file ) )->parse_headers();
		$legacy_rows = ( new FulfillmentsCsvImporter( $legacy_file ) )->read_data_rows( 0, 1 );

		$this->assertSame( 'Ã©', $legacy['sample'][1], 'Once the file is not UTF-8, every cell is converted from Windows-1252' );
		$this->assertSame( 'Spediteur Müller', $legacy['sample'][2] );
		$this->assertTrue( mb_check_encoding( $legacy['sample'][2], 'UTF-8' ) );
		$this->assertSame( $legacy['sample'], $legacy_rows['rows'][0]['cells'], 'Prepare and the row reader must decode the same text' );
	}

	/**
	 * @testdox An enclosure fgetcsv() would misread falls back to the double quote.
	 */
	public function test_unusable_enclosure_values_fall_back_to_double_quote(): void {
		$csv = "order_number,tracking_number,shipment_provider\n1042,\"1Z999,AA\",\"Acme, Inc\"\n";
		foreach ( array( "\n", "\r", ',', "\x80" ) as $enclosure ) {
			$parsed = ( new FulfillmentsCsvImporter( $this->make_csv( $csv ), array( 'enclosure' => $enclosure ) ) )->parse_headers();
			$this->assertSame( array( '1042', '1Z999,AA', 'Acme, Inc' ), $parsed['sample'], 'A line break, the delimiter, or a non-ASCII enclosure must not split quoted fields' );
		}

		$this->assertSame( '"', FulfillmentsCsvImporter::normalize_enclosure( ';', ';' ) );
		$semicolon = "\"order_number\";\"tracking_number\";\"shipment_provider\"\n\"1\";\"TRACK; 1\";\"ups\"\n";
		$parsed    = ( new FulfillmentsCsvImporter(
			$this->make_csv( $semicolon ),
			array(
				'delimiter' => ';',
				'enclosure' => ';',
			)
		) )->parse_headers();
		$this->assertSame( ';', $parsed['delimiter'] );
		$this->assertSame( array( '1', 'TRACK; 1', 'ups' ), $parsed['sample'] );
	}

	/**
	 * @testdox A one-byte enclosure other than the delimiter is kept.
	 */
	public function test_single_quote_enclosure_is_preserved(): void {
		$csv    = "'order_number','tracking_number','shipment_provider'\n'1','TRACK, 1','ups'\n";
		$parsed = ( new FulfillmentsCsvImporter( $this->make_csv( $csv ), array( 'enclosure' => "'" ) ) )->parse_headers();

		$this->assertSame( array( 'order_number', 'tracking_number', 'shipment_provider' ), $parsed['headers'] );
		$this->assertSame( array( '1', 'TRACK, 1', 'ups' ), $parsed['sample'] );
	}

	/**
	 * @testdox mapping_to_header_map keeps the first column for a repeated canonical key.
	 */
	public function test_mapping_to_header_map_keeps_the_first_duplicate_canonical_key(): void {
		$map = FulfillmentsCsvImporter::mapping_to_header_map(
			array(
				0   => 'order_number',
				1   => 'order_number',
				2   => '',
				3   => ' tracking_number ',
				'4' => 'shipment_provider',
				5   => 12,
			)
		);

		$this->assertSame(
			array(
				'order_number'      => 0,
				'tracking_number'   => 3,
				'shipment_provider' => 4,
			),
			$map,
			'Empty and non-string targets are ignored, and the first duplicate canonical key wins'
		);
	}

	/**
	 * @testdox find_missing_required_columns reports order number, tracking number, and shipment provider.
	 */
	public function test_find_missing_required_columns_reports_the_required_set(): void {
		$this->assertSame(
			array(),
			FulfillmentsCsvImporter::find_missing_required_columns(
				array(
					'order_number'      => 0,
					'tracking_number'   => 1,
					'shipment_provider' => 2,
					'tracking_url'      => 3,
					'items'             => 4,
				)
			)
		);
		$this->assertSame(
			array( 'order_number', 'shipment_provider' ),
			FulfillmentsCsvImporter::find_missing_required_columns(
				array(
					'tracking_number' => 1,
					'tracking_url'    => 3,
					'items'           => 4,
				)
			),
			'Optional columns do not satisfy a missing required column'
		);
	}
}
