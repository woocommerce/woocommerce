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
}
