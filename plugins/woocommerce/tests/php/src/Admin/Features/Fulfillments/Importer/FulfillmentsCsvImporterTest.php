<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Importer;

require_once __DIR__ . '/FulfillmentsCsvReadTracker.php';

use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\FulfillmentsCsvImporter;
use Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Helpers\FulfillmentsHelper;

/**
 * Tests for the Fulfillments CSV importer service.
 */
class FulfillmentsCsvImporterTest extends \WC_Unit_Test_Case {

	/**
	 * Paths to temporary CSV files created by tests; cleaned up in tearDown.
	 *
	 * @var array<int, string>
	 */
	private array $temp_files = array();

	/**
	 * Register the CSV read tracker and create the fulfillments tables.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		if ( ! in_array( 'wcimp', stream_get_wrappers(), true ) ) {
			stream_wrapper_register( 'wcimp', FulfillmentsCsvReadTracker::class );
		}
		FulfillmentsHelper::create_tables();
	}

	/**
	 * Turn the fulfillments feature on for each test.
	 */
	public function setUp(): void {
		parent::setUp();
		FulfillmentsHelper::enable_fulfillments();
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
	 * @testdox A file larger than the cap stops at 5,001 rows and does not read the rest.
	 */
	public function test_parse_headers_stops_before_the_rest_of_an_over_cap_file(): void {
		$prefix = "order_number,tracking_number,shipment_provider\n";
		for ( $i = 1; $i <= FulfillmentsCsvImporter::MAX_IMPORT_ROWS + 2; $i++ ) {
			$prefix .= "1,OVER-{$i},ups\n";
		}
		$tail = str_repeat( "9,TAIL,ups\n", 200000 );
		$path = $this->make_csv( $prefix . $tail );

		FulfillmentsCsvReadTracker::$max_position = 0;
		$parsed                                   = ( new FulfillmentsCsvImporter( 'wcimp://' . $path ) )->parse_headers();

		$this->assertSame( FulfillmentsCsvImporter::MAX_IMPORT_ROWS + 1, $parsed['total'] );
		$this->assertTrue( $parsed['total_capped'], 'MAX+2 data rows must set the sentinel and stop' );
		$this->assertLessThan( filesize( $path ), FulfillmentsCsvReadTracker::$max_position, 'The tail past the cap must not be read' );
		$this->assertLessThan( strlen( $prefix ) + 65536, FulfillmentsCsvReadTracker::$max_position, 'Reading must stop within one read-ahead of the proving row' );
	}

	/**
	 * @testdox A file with exactly one row past the cap still reports that exact total.
	 */
	public function test_parse_headers_exact_total_one_row_past_the_cap_is_not_a_sentinel(): void {
		$csv = "order_number,tracking_number,shipment_provider\n";
		for ( $i = 1; $i <= FulfillmentsCsvImporter::MAX_IMPORT_ROWS + 1; $i++ ) {
			$csv .= "1,EXACT-{$i},ups\n";
		}
		$csv .= "\n,,,\n";

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
	 * @testdox Windows-1252 cells are converted at the shared read boundary, and valid UTF-8 cells are left unchanged.
	 *
	 * Each cell is checked for well-formed UTF-8 on its own. Conversion uses
	 * mb_convert_encoding when that exists, otherwise iconv. This environment
	 * has mbstring, so the iconv-only branch and the neither-available branch
	 * are not executed. When neither converter exists, an invalid cell returns
	 * unsupported_encoding instead of the raw bytes.
	 */
	public function test_character_encoding_is_normalized_for_prepare_and_row_reads(): void {
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
		$can_convert = function_exists( 'mb_convert_encoding' ) || function_exists( 'iconv' );
		if ( ! $can_convert ) {
			$this->assertSame( 'unsupported_encoding', $legacy['error']['code'], 'Invalid text must not be returned raw when it cannot be converted' );
			return;
		}

		$legacy_rows = ( new FulfillmentsCsvImporter( $legacy_file ) )->read_data_rows( 0, 1 );
		$this->assertSame( 'é', $legacy['sample'][1], 'A valid UTF-8 cell is kept even when another cell in the row is Windows-1252' );
		$this->assertSame( 'Spediteur Müller', $legacy['sample'][2], 'A Windows-1252 cell is converted on its own' );
		$this->assertSame( $legacy['sample'], $legacy_rows['rows'][0]['cells'], 'Prepare and the row reader must decode the same text' );
	}

	/**
	 * @testdox A resumed read keeps BOM-stripped offsets and decodes each cell on its own.
	 */
	public function test_read_data_rows_resume_keeps_bom_offset_and_encoding(): void {
		if ( ! function_exists( 'mb_convert_encoding' ) && ! function_exists( 'iconv' ) ) {
			$this->markTestSkipped( 'Windows-1252 conversion is unavailable in this environment.' );
		}

		$file = $this->make_csv( "\xEF\xBB\xBForder_number,tracking_number,shipment_provider\n1,ONE,M\xFCller\n2,\xC3\xA9,TWO\n" );
		$sut  = new FulfillmentsCsvImporter( $file );

		$first = $sut->read_data_rows( 0, 1 );
		$this->assertSame( 'order_number', $first['headers'][0], 'The BOM must not remain on the header' );
		$this->assertSame( 'Müller', $first['rows'][0]['cells'][2] );

		$second = $sut->read_data_rows(
			0,
			1,
			array(
				'byte_offset'   => $first['byte_offset'],
				'record_number' => $first['next_record_number'],
			)
		);
		$this->assertSame( 'order_number', $second['headers'][0], 'Resuming must strip the BOM before seeking' );
		$this->assertSame( 'é', $second['rows'][0]['cells'][1], 'A valid UTF-8 cell in a later row is kept as it is' );
		$this->assertSame( 'TWO', $second['rows'][0]['cells'][2] );
		$this->assertSame( $first['next_record_number'], $second['rows'][0]['row_number'] );
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

	/**
	 * @testdox The prepare total and a chunked read agree on every blank-line shape and every chunk size.
	 */
	public function test_prepare_total_matches_a_chunked_read_across_blank_line_shapes(): void {
		$csv  = "\n";
		$csv .= ",,,\n";
		$csv .= "order_number,tracking_number,shipment_provider\n";
		$csv .= "1,ONE,ups\n";
		$csv .= "\n";
		$csv .= "2,TWO,ups\n";
		$csv .= ",,,\n";
		$csv .= "3,THREE,ups\n";
		$csv .= "4,FOUR,ups\n";
		$csv .= " , , \n";
		$csv .= "\n";
		$csv .= "5,FIVE,ups\n";
		$csv .= "6,SIX,ups\n";
		$csv .= "\n\n";
		$file = $this->make_csv( $csv );
		$sut  = new FulfillmentsCsvImporter( $file );

		$parsed = $sut->parse_headers();
		$this->assertSame( 6, $parsed['total'], 'Six real data rows, blank records excluded' );

		$whole = $sut->read_data_rows( 0, 100 );
		$this->assertSame( array( 4, 6, 8, 9, 12, 13 ), array_column( $whole['rows'], 'row_number' ), 'Row numbers are the physical record numbers in the file' );
		$this->assertSame( 3, $whole['header_row_number'] );

		for ( $chunk = 1; $chunk <= 8; $chunk++ ) {
			$rows    = array();
			$options = array();
			$calls   = 0;
			do {
				$result = $sut->read_data_rows( 0, $chunk, $options );
				$this->assertArrayNotHasKey( 'error', $result, "Chunk size {$chunk} must read cleanly" );
				$rows    = array_merge( $rows, $result['rows'] );
				$options = array(
					'byte_offset'   => $result['byte_offset'],
					'record_number' => $result['next_record_number'],
				);
				++$calls;
			} while ( ! $result['eof'] && $calls < 50 );

			$this->assertSame( $parsed['total'], count( $rows ), "Chunk size {$chunk} must return exactly the prepare total" );
			$this->assertSame( $whole['rows'], $rows, "Chunk size {$chunk} must return the same rows and row numbers as one read" );
		}
	}

	/**
	 * @testdox A blank record on the chunk boundary is neither returned nor counted on resume.
	 */
	public function test_blank_record_on_a_chunk_boundary_is_skipped_once(): void {
		$file = $this->make_csv( "order_number,tracking_number,shipment_provider\n1,ONE,ups\n2,TWO,ups\n,,,\n\n3,THREE,ups\n" );
		$sut  = new FulfillmentsCsvImporter( $file );

		$first = $sut->read_data_rows( 0, 2 );
		$this->assertSame( array( 2, 3 ), array_column( $first['rows'], 'row_number' ) );
		$this->assertFalse( $first['eof'], 'A real row remains after the blank records' );
		$this->assertSame( 6, $first['next_record_number'], 'The resume point is the next real row, past both blank records' );

		$second = $sut->read_data_rows(
			0,
			2,
			array(
				'byte_offset'   => $first['byte_offset'],
				'record_number' => $first['next_record_number'],
			)
		);
		$this->assertSame(
			array(
				array(
					'row_number' => 6,
					'cells'      => array( '3', 'THREE', 'ups' ),
				),
			),
			$second['rows']
		);
		$this->assertSame( 1, $second['consumed'] );
		$this->assertTrue( $second['eof'] );
	}

	/**
	 * @testdox Cells are decoded one at a time, so UTF-8 and Windows-1252 text in the same file both come out right.
	 */
	public function test_mixed_encoding_file_decodes_each_cell_on_its_own(): void {
		if ( ! function_exists( 'mb_convert_encoding' ) && ! function_exists( 'iconv' ) ) {
			$this->markTestSkipped( 'Windows-1252 conversion is unavailable in this environment.' );
		}

		$file = $this->make_csv( "order_number,tracking_number,shipment_provider\n101,1Z1,Spediteur M\xC3\xBCller\n102,1Z2,Caf\xE9\n" );

		$parsed = ( new FulfillmentsCsvImporter( $file ) )->parse_headers();
		$this->assertSame( 'Spediteur Müller', $parsed['sample'][2], 'A valid UTF-8 cell must not be re-decoded because another row is Windows-1252' );

		$rows = ( new FulfillmentsCsvImporter( $file ) )->read_data_rows( 0, 2 );
		$this->assertSame( 'Spediteur Müller', $rows['rows'][0]['cells'][2] );
		$this->assertSame( 'Café', $rows['rows'][1]['cells'][2], 'A Windows-1252 cell is converted on its own' );
	}

	/**
	 * @testdox A UTF-16 file is rejected with or without a BOM and the merchant is told to re-save it as UTF-8.
	 */
	public function test_utf16_files_are_rejected(): void {
		$text  = "order_number,tracking_number,shipment_provider\n101,1Z1,ups\n";
		$utf16 = '';
		foreach ( str_split( $text ) as $char ) {
			$utf16 .= $char . "\0";
		}

		foreach ( array( "\xFF\xFE" . $utf16, $utf16, "\xFE\xFF" . strrev( $utf16 ) ) as $content ) {
			$file   = $this->make_csv( $content );
			$parsed = ( new FulfillmentsCsvImporter( $file ) )->parse_headers();
			$read   = ( new FulfillmentsCsvImporter( $file ) )->read_data_rows( 0, 10 );

			$this->assertSame( 'utf16_encoding', $parsed['error']['code'] ?? null, 'UTF-16 must be rejected instead of parsed into unmappable headers' );
			$this->assertStringContainsString( 'UTF-8', $parsed['error']['message'] );
			$this->assertSame( 'utf16_encoding', $read['error']['code'] ?? null, 'The row reader must reject the same file' );
		}
	}

	/**
	 * @testdox Stream paths without a readable file are rejected like any other unreadable path.
	 */
	public function test_unreadable_stream_paths_are_rejected(): void {
		foreach ( array( 'php://memory', 'data://text/plain,order_number%0A1%0A' ) as $path ) {
			$parsed = ( new FulfillmentsCsvImporter( $path ) )->parse_headers();
			$this->assertSame( 'file_not_readable', $parsed['error']['code'] ?? null, "{$path} must not bypass the readability check" );
		}
	}

	/**
	 * @testdox A data row shorter than the header is padded with empty strings in the sample and in read rows.
	 */
	public function test_short_rows_are_padded_to_the_header_width(): void {
		$file = $this->make_csv(
			"order_number,tracking_number,shipment_provider,tracking_url\n"
			. "1042,1Z999\n"
			. "1043,1Z998,ups,https://example.com/t,extra\n"
			. "1044\n"
		);

		$parsed = ( new FulfillmentsCsvImporter( $file ) )->parse_headers();
		$this->assertSame( array( '1042', '1Z999', '', '' ), $parsed['sample'], 'The sample must have one entry per header cell' );
		$this->assertSame( 3, $parsed['total'] );

		$read = ( new FulfillmentsCsvImporter( $file ) )->read_data_rows( 0, 10 );
		$this->assertCount( 3, $read['rows'] );
		$this->assertSame( array( '1042', '1Z999', '', '' ), $read['rows'][0]['cells'], 'A two-cell row must be padded to four cells' );
		$this->assertSame( array( '1043', '1Z998', 'ups', 'https://example.com/t', 'extra' ), $read['rows'][1]['cells'], 'A longer row keeps its extra cell' );
		$this->assertSame( array( '1044', '', '', '' ), $read['rows'][2]['cells'], 'A one-cell row must be padded to four cells' );

		$header_map = FulfillmentsCsvImporter::mapping_to_header_map( $parsed['detected_mapping'] );
		foreach ( $read['rows'] as $row ) {
			foreach ( $header_map as $canonical => $index ) {
				$this->assertIsString( $row['cells'][ $index ], "Row {$row['row_number']} must have a string at the {$canonical} index" );
			}
		}

		$first   = ( new FulfillmentsCsvImporter( $file ) )->read_data_rows( 0, 1 );
		$resumed = ( new FulfillmentsCsvImporter( $file ) )->read_data_rows(
			0,
			10,
			array(
				'byte_offset'   => $first['byte_offset'],
				'record_number' => $first['next_record_number'],
			)
		);
		$this->assertSame( array( '1044', '', '', '' ), $resumed['rows'][1]['cells'], 'Padding must apply on the resume path too' );
	}

	/**
	 * @testdox Header and data cells are trimmed on both the prepare and the row-read path.
	 */
	public function test_cells_are_trimmed_in_headers_sample_and_rows(): void {
		$file = $this->make_csv(
			" order_number , Tracking ,Carrier\n"
			. "1042, 1Z999 , ups \n"
			. "\t1043 ,\" 1Z998 \",\"usps\"\n"
		);

		$parsed = ( new FulfillmentsCsvImporter( $file ) )->parse_headers();
		$this->assertSame( array( 'order_number', 'Tracking', 'Carrier' ), $parsed['headers'], 'Header cells must be trimmed' );
		$this->assertSame( array( '1042', '1Z999', 'ups' ), $parsed['sample'], 'Sample cells must be trimmed' );
		$this->assertSame(
			array(
				0 => 'order_number',
				1 => 'tracking_number',
				2 => 'shipment_provider',
			),
			$parsed['detected_mapping']
		);

		$read = ( new FulfillmentsCsvImporter( $file ) )->read_data_rows( 0, 10 );
		$this->assertSame( array( '1042', '1Z999', 'ups' ), $read['rows'][0]['cells'] );
		$this->assertSame( array( '1043', '1Z998', 'usps' ), $read['rows'][1]['cells'], 'Whitespace inside a quoted cell and a leading tab are trimmed too' );
		$this->assertSame( $parsed['headers'], $read['headers'], 'Both paths must return the same trimmed headers' );
	}
}
