<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Importer;

require_once __DIR__ . '/FulfillmentsCsvReadTracker.php';

use Automattic\WooCommerce\Admin\Features\Fulfillments\DataStore\FulfillmentsDataStore;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentException;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\FulfillmentsCsvImporter;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Helpers\FulfillmentsHelper;
use WC_Data_Store;
use WC_Order;

/**
 * Tests for the Fulfillments CSV importer service.
 */
class FulfillmentsCsvImporterTest extends \WC_Unit_Test_Case {

	/**
	 * Column mapping the import tests use; their CSVs list the columns in this order.
	 */
	private const IMPORT_MAPPING = array(
		0 => FulfillmentsCsvImporter::COL_ORDER_NUMBER,
		1 => FulfillmentsCsvImporter::COL_TRACKING_NUMBER,
		2 => FulfillmentsCsvImporter::COL_PROVIDER,
		3 => FulfillmentsCsvImporter::COL_TRACKING_URL,
		4 => FulfillmentsCsvImporter::COL_ITEMS,
	);

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
	 * Import a CSV the way the import run does: a new importer per chunk, each resuming from the last result.
	 *
	 * @param string               $csv     CSV content with its columns in the order of IMPORT_MAPPING.
	 * @param array<string, mixed> $options Importer options.
	 * @param int                  $limit   Data rows per chunk.
	 * @return array{counts: array<string, int>, rows: array<int, array<string, mixed>>, chunks: int}
	 */
	private function import_csv( string $csv, array $options = array(), int $limit = 200 ): array {
		$file   = $this->make_csv( $csv );
		$state  = array();
		$counts = array_fill_keys( array( FulfillmentsCsvImporter::STATUS_CREATED, FulfillmentsCsvImporter::STATUS_UPDATED, FulfillmentsCsvImporter::STATUS_SKIPPED, FulfillmentsCsvImporter::STATUS_FAILED, 'notified' ), 0 );
		$rows   = array();
		$chunks = 0;
		do {
			$chunk = ( new FulfillmentsCsvImporter( $file, $options ) )->import_chunk( $limit, self::IMPORT_MAPPING, $state );
			$this->assertArrayNotHasKey( 'error', $chunk );
			foreach ( $chunk['counts'] as $key => $count ) {
				$counts[ $key ] += $count;
			}
			$rows  = array_merge( $rows, $chunk['rows'] );
			$state = $chunk;
			++$chunks;
		} while ( ! $chunk['eof'] && $chunks < 20 );

		return array(
			'counts' => $counts,
			'rows'   => $rows,
			'chunks' => $chunks,
		);
	}

	/**
	 * Fulfillments of an order that are not deleted.
	 *
	 * @param WC_Order $order Order.
	 * @return array<int, Fulfillment>
	 */
	private function fulfillments_of( WC_Order $order ): array {
		return WC_Data_Store::load( 'order-fulfillment' )->read_fulfillments( WC_Order::class, (string) $order->get_id() );
	}

	/**
	 * Counts with the given values and zero for the rest.
	 *
	 * @param array<string, int> $values Non-zero counts.
	 * @return array<string, int>
	 */
	private static function counts( array $values ): array {
		return array_merge( array_fill_keys( array( FulfillmentsCsvImporter::STATUS_CREATED, FulfillmentsCsvImporter::STATUS_UPDATED, FulfillmentsCsvImporter::STATUS_SKIPPED, FulfillmentsCsvImporter::STATUS_FAILED, 'notified' ), 0 ), $values );
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

	/**
	 * @testdox resolve_chunk_size() keeps the filtered size between 1 and 1000, and at most 25 while notifying.
	 * @testWith [null, false, 200]
	 *           [50, false, 50]
	 *           ["75", false, 75]
	 *           [0, false, 200]
	 *           ["junk", false, 200]
	 *           [5000, false, 1000]
	 *           [[500], false, 200]
	 *           [null, true, 25]
	 *           [10, true, 10]
	 *           [500, true, 25]
	 *
	 * @param mixed $filtered Value the filter returns; null leaves the default.
	 * @param bool  $notify   Whether the import notifies customers.
	 * @param int   $expected Expected chunk size.
	 */
	public function test_resolve_chunk_size_clamps_the_filtered_value( $filtered, bool $notify, int $expected ): void {
		$received = array();
		add_filter(
			'woocommerce_fulfillments_csv_importer_chunk_size',
			function ( $size, $notify_customer ) use ( $filtered, &$received ) {
				$received = array( $size, $notify_customer );
				return $filtered ?? $size;
			},
			10,
			2
		);

		$this->assertSame( $expected, FulfillmentsCsvImporter::resolve_chunk_size( $notify ) );
		$this->assertSame( array( 200, $notify ), $received, 'The filter receives the default size and the notify flag' );
	}

	/**
	 * @testdox A valid row creates a fulfilled fulfillment covering every order item at its ordered quantity.
	 */
	public function test_valid_row_creates_a_fulfilled_fulfillment_with_every_order_item(): void {
		$order = OrderHelper::create_order();
		$id    = $order->get_id();
		$zero  = new \WC_Order_Item_Product();
		$zero->set_quantity( 0 );
		$order->add_item( $zero );
		$order->save();

		$result = $this->import_csv( "order_number,tracking_number,shipment_provider,tracking_url\n{$id},TRK-1,ups,https://example.com/t/<b>TRK-1\n" );

		$this->assertSame( self::counts( array( FulfillmentsCsvImporter::STATUS_CREATED => 1 ) ), $result['counts'] );
		$fulfillments = $this->fulfillments_of( $order );
		$this->assertCount( 1, $fulfillments );
		$fulfillment = $fulfillments[0];
		$this->assertSame(
			array(
				'row'            => 2,
				'order_number'   => (string) $id,
				'status'         => FulfillmentsCsvImporter::STATUS_CREATED,
				'message'        => 'Fulfillment created.',
				'order_id'       => $id,
				'fulfillment_id' => $fulfillment->get_id(),
				'notified'       => false,
			),
			$result['rows'][0]
		);
		$this->assertTrue( $fulfillment->get_is_fulfilled() );
		$this->assertSame( 'TRK-1', $fulfillment->get_tracking_number() );
		$this->assertSame( 'ups', $fulfillment->get_shipment_provider() );
		$this->assertSame( 'https://example.com/t/bTRK-1', $fulfillment->get_tracking_url(), 'The URL is sanitized before it is stored' );
		$item = current( $order->get_items() );
		$this->assertEquals(
			array(
				array(
					'item_id' => $item->get_id(),
					'qty'     => 4,
				),
			),
			$fulfillment->get_items(),
			'A row without an items cell fulfills every line item with a quantity'
		);
	}

	/**
	 * @testdox Each invalid row fails with its own code while the valid rows of the file still import.
	 */
	public function test_invalid_rows_fail_with_a_code_while_the_rest_imports(): void {
		$order    = OrderHelper::create_order();
		$id       = $order->get_id();
		$no_items = wc_create_order()->get_id();
		add_filter(
			'woocommerce_fulfillment_before_create',
			function ( $fulfillment ) {
				if ( 'TRK-BLOCKED' === $fulfillment->get_tracking_number() ) {
					throw new FulfillmentException( 'Blocked by an extension.' );
				}
				if ( 'TRK-CRASH' === $fulfillment->get_tracking_number() ) {
					throw new \RuntimeException( 'Database gone away.' );
				}
				return $fulfillment;
			}
		);

		$result = $this->import_csv(
			"order_number,tracking_number,shipment_provider,tracking_url\n"
			. ",TRK-A,ups,\n{$id},,ups,\n{$id},TRK-B,,\n999999999,TRK-C,ups,\nINV-1,TRK-D,ups,\n"
			. "{$id},TRK-E,ups,javascript:alert(1)\n{$id},TRK-F,ups,//example.com/t\n{$id},TRK-G,ups,/wp-admin/\n{$id},TRK-H,ups,ftp://example.com/t\n"
			. "{$no_items},TRK-I,ups,\n{$id},TRK-BLOCKED,ups,\n{$id},TRK-CRASH,ups,\n{$id},TRK-OK,ups,http://example.com/t\n"
		);

		$this->assertSame(
			array( FulfillmentsCsvImporter::CODE_MISSING_ORDER_NUMBER, FulfillmentsCsvImporter::CODE_MISSING_TRACKING_NUMBER, FulfillmentsCsvImporter::CODE_MISSING_PROVIDER, FulfillmentsCsvImporter::CODE_ORDER_NOT_FOUND, FulfillmentsCsvImporter::CODE_ORDER_NOT_FOUND, FulfillmentsCsvImporter::CODE_INVALID_TRACKING_URL, FulfillmentsCsvImporter::CODE_INVALID_TRACKING_URL, FulfillmentsCsvImporter::CODE_INVALID_TRACKING_URL, FulfillmentsCsvImporter::CODE_INVALID_TRACKING_URL, FulfillmentsCsvImporter::CODE_NO_ITEMS, FulfillmentsCsvImporter::CODE_SAVE_FAILED, FulfillmentsCsvImporter::CODE_SAVE_FAILED, null ),
			array_map(
				static function ( $row ) {
					return $row['code'] ?? null;
				},
				$result['rows']
			)
		);
		$this->assertSame(
			self::counts(
				array(
					FulfillmentsCsvImporter::STATUS_CREATED => 1,
					FulfillmentsCsvImporter::STATUS_FAILED => 12,
				)
			),
			$result['counts']
		);
		$this->assertSame( range( 2, 14 ), array_column( $result['rows'], 'row' ) );
		$this->assertSame( 'Blocked by an extension.', $result['rows'][10]['message'], 'An extension message from a blocked save reaches the merchant' );
		$this->assertSame( 'Could not save the fulfillment.', $result['rows'][11]['message'], 'Other save errors are not shown to the merchant' );
		$this->assertSame( 'INV-1', $result['rows'][4]['order_number'], 'A failed row still names the order number from the file' );
		$fulfillments = $this->fulfillments_of( $order );
		$this->assertCount( 1, $fulfillments );
		$this->assertSame( 'http://example.com/t', $fulfillments[0]->get_tracking_url() );
	}

	/**
	 * @testdox A tracking URL must start with http:// or https:// and keep a host after sanitizing.
	 * @testWith ["N/A", null]
	 *           ["http:example.com", null]
	 *           ["https:/example.com", null]
	 *           ["http:///path", null]
	 *           ["HTTPS://Host/x", "https://Host/x"]
	 *
	 * @param string      $cell   Tracking URL cell.
	 * @param string|null $stored Expected stored URL, or null when the row fails.
	 */
	public function test_tracking_url_needs_an_http_scheme_and_a_host( string $cell, ?string $stored ): void {
		$order = OrderHelper::create_order();

		$result = $this->import_csv( "order_number,tracking_number,shipment_provider,tracking_url\n{$order->get_id()},TRK-1,ups,{$cell}\n" );

		$this->assertSame( null === $stored ? FulfillmentsCsvImporter::CODE_INVALID_TRACKING_URL : null, $result['rows'][0]['code'] ?? null );
		$this->assertSame( $stored, null === $stored ? null : $this->fulfillments_of( $order )[0]->get_tracking_url() );
	}

	/**
	 * @testdox A provider cell maps to a known provider by key or name ignoring case, and anything else is stored as a custom provider.
	 * @testWith ["UPS", "ups", ""]
	 *           ["royal mail", "royal-mail", ""]
	 *           ["Royal-Mail", "royal-mail", ""]
	 *           ["Acme Freight", "other", "Acme Freight"]
	 *
	 * @param string $cell     Provider cell.
	 * @param string $provider Expected _shipment_provider.
	 * @param string $name     Expected _provider_name.
	 */
	public function test_provider_cell_maps_to_a_known_or_custom_provider( string $cell, string $provider, string $name ): void {
		$order = OrderHelper::create_order();

		$this->import_csv( "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRK-1,{$cell}\n" );

		$fulfillment = $this->fulfillments_of( $order )[0];
		$this->assertSame( $provider, $fulfillment->get_shipment_provider() );
		$this->assertSame( $name, (string) $fulfillment->get_meta( '_provider_name', true ) );
	}

	/**
	 * @testdox A repeated order and tracking number pair is skipped across chunks, and a row that failed to save does not count as seen.
	 */
	public function test_duplicate_pairs_are_skipped_after_the_first_imported_row(): void {
		$order  = OrderHelper::create_order();
		$other  = OrderHelper::create_order();
		$id     = $order->get_id();
		$blocks = 1;
		add_filter(
			'woocommerce_fulfillment_before_create',
			function ( $fulfillment ) use ( &$blocks ) {
				if ( $blocks-- > 0 ) {
					throw new FulfillmentException( 'Blocked once.' );
				}
				return $fulfillment;
			}
		);

		$result = $this->import_csv(
			"order_number,tracking_number,shipment_provider,tracking_url\n{$id},TRK-1,ups,\n{$id},trk-1,ups,\n{$id},TRK-1,fedex,\n{$other->get_id()},TRK-1,ups,\n",
			array(),
			1
		);

		$this->assertSame( array( FulfillmentsCsvImporter::STATUS_FAILED, FulfillmentsCsvImporter::STATUS_CREATED, FulfillmentsCsvImporter::STATUS_SKIPPED, FulfillmentsCsvImporter::STATUS_CREATED ), array_column( $result['rows'], 'status' ) );
		$this->assertSame( 'Duplicate order and tracking number in this file.', $result['rows'][2]['message'] );
		$fulfillments = $this->fulfillments_of( $order );
		$this->assertCount( 1, $fulfillments );
		$this->assertSame( 'ups', $fulfillments[0]->get_shipment_provider(), 'The skipped duplicate must not change the imported row' );
		$this->assertCount( 1, $this->fulfillments_of( $other ) );
	}

	/**
	 * @testdox A row whose tracking number matches a stored fulfillment of the order, in any case, updates it and keeps its URL when the cell is blank.
	 */
	public function test_existing_fulfillment_with_the_same_tracking_number_is_updated(): void {
		$order = OrderHelper::create_order();
		$id    = $order->get_id();
		$this->import_csv( "order_number,tracking_number,shipment_provider,tracking_url\n{$id},TRK-1,ups,https://example.com/1\n" );
		add_filter(
			'woocommerce_fulfillment_fulfillment_statuses',
			fn( $statuses ) => $statuses + array(
				'delivered' => array(
					'label'        => 'Delivered',
					'is_fulfilled' => true,
				),
			)
		);
		$stored = $this->fulfillments_of( $order )[0];
		$stored->set_status( 'delivered' );
		$stored->save();
		$stored_id = $stored->get_id();

		$result = $this->import_csv( "order_number,tracking_number,shipment_provider,tracking_url\n{$id},trk-1,fedex,\n" );

		$this->assertSame( self::counts( array( FulfillmentsCsvImporter::STATUS_UPDATED => 1 ) ), $result['counts'] );
		$this->assertSame( 'Fulfillment updated.', $result['rows'][0]['message'] );
		$this->assertSame( $stored_id, $result['rows'][0]['fulfillment_id'] );
		$fulfillments = $this->fulfillments_of( $order );
		$this->assertCount( 1, $fulfillments );
		$this->assertSame( 'trk-1', $fulfillments[0]->get_tracking_number() );
		$this->assertSame( 'fedex', $fulfillments[0]->get_shipment_provider() );
		$this->assertSame( 'https://example.com/1', $fulfillments[0]->get_tracking_url() );
		$this->assertSame( 'delivered', $fulfillments[0]->get_status(), 'A status that is already fulfilled is kept' );
	}

	/**
	 * @testdox A row whose mapped values match the stored fulfillment is skipped without a save or a notification.
	 */
	public function test_unchanged_update_is_skipped_without_a_save_or_notification(): void {
		$order   = OrderHelper::create_order();
		$csv     = "order_number,tracking_number,shipment_provider,tracking_url\n{$order->get_id()},TRK-1,UPS,HTTPS://example.com/t\n{$order->get_id()},TRK-2,Acme Freight,\n";
		$options = array(
			'notify_customer' => true,
			'update_existing' => true,
		);
		$this->import_csv( $csv, $options );
		$updates = did_action( 'woocommerce_fulfillment_after_update' );
		$emails  = did_action( 'woocommerce_fulfillment_updated_notification' );

		$result = $this->import_csv( $csv, $options );

		$this->assertSame( array( FulfillmentsCsvImporter::STATUS_SKIPPED, FulfillmentsCsvImporter::STATUS_SKIPPED ), array_column( $result['rows'], 'status' ) );
		$this->assertSame( 'Fulfillment unchanged.', $result['rows'][0]['message'] );
		$this->assertSame( 0, $result['counts']['notified'] );
		$this->assertSame( $updates, did_action( 'woocommerce_fulfillment_after_update' ), 'An unchanged row is not saved' );
		$this->assertSame( $emails, did_action( 'woocommerce_fulfillment_updated_notification' ) );
	}

	/**
	 * @testdox A matching fulfillment is left alone when updates are off or when it is locked.
	 * @testWith [false, false, "skipped", null]
	 *           [true, true, "failed", "fulfillment_locked"]
	 *
	 * @param bool        $update_existing Importer update_existing option.
	 * @param bool        $locked          Whether the stored fulfillment is locked.
	 * @param string      $status          Expected row status.
	 * @param string|null $code            Expected row code.
	 */
	public function test_matching_fulfillment_is_kept_when_updates_are_off_or_it_is_locked( bool $update_existing, bool $locked, string $status, ?string $code ): void {
		$order = OrderHelper::create_order();
		$id    = $order->get_id();
		$this->import_csv( "order_number,tracking_number,shipment_provider\n{$id},TRK-1,ups\n" );
		$stored = $this->fulfillments_of( $order )[0];
		$stored->set_locked( $locked );
		$stored->save();

		$result = $this->import_csv( "order_number,tracking_number,shipment_provider\n{$id},TRK-1,fedex\n", array( 'update_existing' => $update_existing ) );

		$this->assertSame( $status, $result['rows'][0]['status'] );
		$this->assertSame( $code, $result['rows'][0]['code'] ?? null );
		$this->assertSame( 'ups', $this->fulfillments_of( $order )[0]->get_shipment_provider() );
	}

	/**
	 * @testdox The resolve-order filter maps custom order numbers, runs for primed orders, and a non-order result fails the row.
	 */
	public function test_resolve_order_filter_decides_the_order(): void {
		$order = OrderHelper::create_order();
		$other = OrderHelper::create_order();
		$calls = array();
		add_filter(
			'woocommerce_fulfillments_csv_importer_resolve_order',
			function ( $resolved, $order_number ) use ( $order, $other, &$calls ) {
				$calls[] = $order_number;
				if ( 'INV-7' === $order_number ) {
					return $order;
				}
				return (string) $other->get_id() === $order_number ? 'not an order' : $resolved;
			},
			10,
			2
		);

		$result = $this->import_csv( "order_number,tracking_number,shipment_provider\nINV-7,TRK-1,ups\n{$other->get_id()},TRK-2,ups\n{$order->get_id()},TRK-3,ups\nINV-7,TRK-4,ups\n" );

		$this->assertSame( array( FulfillmentsCsvImporter::STATUS_CREATED, FulfillmentsCsvImporter::STATUS_FAILED, FulfillmentsCsvImporter::STATUS_CREATED, FulfillmentsCsvImporter::STATUS_CREATED ), array_column( $result['rows'], 'status' ) );
		$this->assertSame( $order->get_id(), $result['rows'][0]['order_id'] );
		$this->assertSame( FulfillmentsCsvImporter::CODE_ORDER_NOT_FOUND, $result['rows'][1]['code'] );
		$this->assertSame( array( 'INV-7', (string) $other->get_id(), (string) $order->get_id() ), $calls, 'The filter runs once per order number in a chunk' );
		$this->assertCount( 0, $this->fulfillments_of( $other ) );
	}

	/**
	 * @testdox Each chunk loads the orders it names by ID in one query, uses them, and checks the filtered query result.
	 */
	public function test_chunk_orders_are_loaded_in_one_query(): void {
		$first   = OrderHelper::create_order();
		$second  = OrderHelper::create_order();
		$queries = array();
		$primed  = array();
		add_filter(
			'woocommerce_order_query_args',
			function ( $args ) use ( &$queries ) {
				if ( isset( $args['post__in'] ) ) {
					$queries[] = array( $args['post__in'], $args['limit'] );
				}
				return $args;
			}
		);
		add_filter(
			'woocommerce_order_query',
			function ( $orders, $args ) use ( &$queries ) {
				if ( ! isset( $args['post__in'] ) ) {
					return $orders;
				}
				// The first priming query gets a junk entry, the second returns a non-array.
				if ( 1 === count( $queries ) ) {
					foreach ( $orders as $order ) {
						$order->add_meta_data( '_test_primed', 'yes' );
					}
					$orders[] = 'junk';
					return $orders;
				}
				return 'not an array';
			},
			10,
			2
		);
		add_filter(
			'woocommerce_fulfillments_csv_importer_resolve_order',
			function ( $order ) use ( &$primed ) {
				$primed[] = $order ? $order->get_meta( '_test_primed' ) : null;
				return $order;
			}
		);

		$result = $this->import_csv(
			"order_number,tracking_number,shipment_provider\nINV-1,TRK-1,ups\nINV-2,TRK-2,ups\n{$second->get_id()},TRK-3,ups\n{$second->get_id()},TRK-4,ups\n{$first->get_id()},TRK-5,ups\n",
			array(),
			2
		);

		$this->assertSame(
			array(
				array( array( $second->get_id() ), 1 ),
				array( array( $first->get_id() ), 1 ),
			),
			$queries,
			'A chunk without numeric order numbers runs no query, and IDs are not repeated'
		);
		$this->assertSame( array( null, null, 'yes', '' ), $primed, 'The primed order is used; a non-array query result falls back to a lookup' );
		$this->assertSame( array( FulfillmentsCsvImporter::STATUS_FAILED, FulfillmentsCsvImporter::STATUS_FAILED, FulfillmentsCsvImporter::STATUS_CREATED, FulfillmentsCsvImporter::STATUS_CREATED, FulfillmentsCsvImporter::STATUS_CREATED ), array_column( $result['rows'], 'status' ) );
	}

	/**
	 * @testdox A row whose order fulfillments cannot be read fails without caching the failure.
	 */
	public function test_unreadable_fulfillments_fail_the_row(): void {
		$order = OrderHelper::create_order();
		$reads = 0;
		$store = new class( $reads ) extends FulfillmentsDataStore {
			/**
			 * Read counter shared with the test.
			 *
			 * @var int
			 */
			private $reads;

			/**
			 * Constructor.
			 *
			 * @param int $reads Read counter.
			 */
			public function __construct( int &$reads ) {
				$this->reads = &$reads;
			}

			// phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn -- The test double only throws.
			/**
			 * Fail every read.
			 *
			 * @param string $entity_type  Entity type.
			 * @param string $entity_id    Entity ID.
			 * @param bool   $with_deleted Whether to include deleted fulfillments.
			 * @return array
			 * @throws \Exception Always.
			 */
			public function read_fulfillments( string $entity_type, string $entity_id, bool $with_deleted = false ): array {
				++$this->reads;
				throw new \Exception( 'Table is locked.' );
			}
			// phpcs:enable Squiz.Commenting.FunctionComment.InvalidNoReturn
		};
		add_filter( 'woocommerce_order-fulfillment_data_store', fn() => $store );

		$result = $this->import_csv( "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRK-1,ups\n{$order->get_id()},TRK-2,ups\n" );

		$this->assertSame( array( FulfillmentsCsvImporter::CODE_STORE_READ_FAILED, FulfillmentsCsvImporter::CODE_STORE_READ_FAILED ), array_column( $result['rows'], 'code' ) );
		$this->assertSame( 2, $reads, 'A failed read is not cached, so the next row reads again' );
	}

	/**
	 * @testdox Chunks resume from the previous result, keep physical row numbers across blank lines and report eof only at the end.
	 */
	public function test_chunks_resume_from_the_previous_result(): void {
		$id  = array_map( fn() => OrderHelper::create_order()->get_id(), range( 1, 5 ) );
		$csv = "order_number,tracking_number,shipment_provider\n\n{$id[0]},TRK-1,ups\n{$id[1]},TRK-2,\"ups, ground\"\n\n,,\n{$id[2]},TRK-3,ups\n{$id[3]},TRK-4,ups\n{$id[4]},TRK-5,ups\n";

		$result = $this->import_csv( $csv, array(), 2 );

		$this->assertSame( 3, $result['chunks'], 'Five rows in chunks of two take three chunks' );
		$this->assertSame( array( 3, 4, 7, 8, 9 ), array_column( $result['rows'], 'row' ) );
		$this->assertSame( self::counts( array( FulfillmentsCsvImporter::STATUS_CREATED => 5 ) ), $result['counts'] );
	}

	/**
	 * @testdox The chunk limit falls back to and is capped by the resolved chunk size.
	 * @testWith [100, 2]
	 *           [0, 2]
	 *           [1, 1]
	 *
	 * @param int $limit    Requested limit.
	 * @param int $consumed Expected rows consumed.
	 */
	public function test_chunk_limit_is_capped_by_the_resolved_chunk_size( int $limit, int $consumed ): void {
		add_filter( 'woocommerce_fulfillments_csv_importer_chunk_size', fn() => 2 );
		$id  = OrderHelper::create_order()->get_id();
		$sut = new FulfillmentsCsvImporter( $this->make_csv( "order_number,tracking_number,shipment_provider\n{$id},T-1,ups\n{$id},T-2,ups\n{$id},T-3,ups\n" ) );

		$chunk = $sut->import_chunk( $limit, self::IMPORT_MAPPING );

		$this->assertSame( $consumed, $chunk['consumed'] );
		$this->assertCount( $consumed, $chunk['rows'] );
		$this->assertFalse( $chunk['eof'] );
	}

	/**
	 * @testdox A chunk-level problem returns an error and writes nothing.
	 */
	public function test_chunk_errors_write_nothing(): void {
		$order = OrderHelper::create_order();
		$sut   = new FulfillmentsCsvImporter( $this->make_csv( "order_number,tracking_number,shipment_provider\n{$order->get_id()},T-1,ups\n" ) );

		$missing_column = $sut->import_chunk( 10, array_slice( self::IMPORT_MAPPING, 0, 2, true ) );
		$missing_file   = ( new FulfillmentsCsvImporter( '/path/that/does/not/exist.csv' ) )->import_chunk( 10, self::IMPORT_MAPPING );

		$this->assertSame( array( 'error' ), array_keys( $missing_column ) );
		$this->assertSame( 'missing_required_columns', $missing_column['error']['code'] );
		$this->assertStringContainsString( 'shipment_provider', $missing_column['error']['message'] );
		$this->assertSame( 'file_not_readable', $missing_file['error']['code'] );
		$this->assertCount( 0, $this->fulfillments_of( $order ) );
	}

	/**
	 * Create an order with item "a" (quantity 4, SKU "DUMMY SKU"), item "b" (quantity 2, SKU "SKU-B")
	 * and a third item whose product was deleted.
	 *
	 * @return array{0: WC_Order, 1: int, 2: int} The order and the IDs of items a and b.
	 */
	private function make_two_item_order(): array {
		$order     = OrderHelper::create_order();
		$product_b = \WC_Helper_Product::create_simple_product();
		$product_b->set_sku( 'SKU-B' );
		$product_b->save();
		$item_b  = $order->add_product( $product_b, 2 );
		$deleted = \WC_Helper_Product::create_simple_product();
		$order->add_product( $deleted, 1 );
		$order->save();
		$deleted->delete( true );
		return array( $order, (int) current( $order->get_items() )->get_id(), (int) $item_b );
	}

	/**
	 * @testdox The items cell takes item IDs and SKUs, skips empty entries and adds up a repeated item up to the ordered quantity.
	 */
	public function test_items_cell_selects_items_by_id_and_sku(): void {
		list( $order, $item_a, $item_b ) = $this->make_two_item_order();

		$result = $this->import_csv( "order_number,tracking_number,shipment_provider,tracking_url,items\n{$order->get_id()},TRK-1,ups,,\"{$item_a}:1| |SKU:Sku-B:1; {$item_a} : 3\"\n" );

		$this->assertSame( FulfillmentsCsvImporter::STATUS_CREATED, $result['rows'][0]['status'] );
		$this->assertSame(
			array(
				array(
					'item_id' => $item_a,
					'qty'     => 4,
				),
				array(
					'item_id' => $item_b,
					'qty'     => 1,
				),
			),
			$this->fulfillments_of( $order )[0]->get_items()
		);
	}

	/**
	 * @testdox An invalid items cell fails the row with invalid_items and a message saying why.
	 * @testWith ["{a}:5", "Item quantity 5 exceeds the quantity left to fulfill 4."]
	 *           ["{a}:3|{a}:2", "Item quantity 5 exceeds the quantity left to fulfill 4."]
	 *           ["{b}:3", "Item quantity 3 exceeds the quantity left to fulfill 2."]
	 *           ["99999999:1", "Item ID 99999999 is not part of this order."]
	 *           ["{a}:1.5", "Item quantity must be a positive integer."]
	 *           ["{a}:0", "Item quantity must be a positive integer."]
	 *           ["{a}", "Invalid items entry: {a}."]
	 *           ["{a}abc:1", "Item ID {a}abc is not part of this order."]
	 *           ["sku:NOPE:1", "SKU NOPE was not found on the order."]
	 *           ["sku:SKU-B", "Invalid items entry: sku:SKU-B."]
	 *           ["{a}:1:2", "Invalid items entry: {a}:1:2."]
	 *           ["0", "Invalid items entry: 0."]
	 *           ["sku::1", "SKU  was not found on the order."]
	 *           ["|;", "The items cell has no item entries."]
	 *
	 * @param string $cell    Items cell; {a} and {b} stand for the item IDs.
	 * @param string $message Expected message.
	 */
	public function test_invalid_items_cell_fails_the_row( string $cell, string $message ): void {
		list( $order, $item_a, $item_b ) = $this->make_two_item_order();
		$replace                         = array(
			'{a}' => $item_a,
			'{b}' => $item_b,
		);

		$result = $this->import_csv( "order_number,tracking_number,shipment_provider,tracking_url,items\n{$order->get_id()},TRK-1,ups,,\"" . strtr( $cell, $replace ) . "\"\n" );

		$this->assertSame( FulfillmentsCsvImporter::CODE_INVALID_ITEMS, $result['rows'][0]['code'] ?? null );
		$this->assertSame( strtr( $message, $replace ), $result['rows'][0]['message'] );
		$this->assertCount( 0, $this->fulfillments_of( $order ) );
	}

	/**
	 * @testdox Items default to and are capped by what is left to fulfill after refunds and the order's other fulfillments.
	 * @testWith ["{a}:1", "TRK-NEW", "", "{a}:3|{b}:1|{c}:1"]
	 *           ["{a}:1", "TRK-NEW", "{a}:3", "{a}:3"]
	 *           ["{a}:1", "TRK-NEW", "{a}:4", "Item quantity 4 exceeds the quantity left to fulfill 3."]
	 *           ["{a}:1", "TRK-NEW", "{b}:2", "Item quantity 2 exceeds the quantity left to fulfill 1."]
	 *           ["{a}:1", "TRK-OLD", "{a}:4", "{a}:4"]
	 *           ["{a}:4|{b}:1|{c}:1", "TRK-NEW", "", "This order has no items left to fulfill."]
	 *           ["{a}:4", "TRK-NEW", "{a}:1", "Item quantity 1 exceeds the quantity left to fulfill 0."]
	 *
	 * @param string $stored   Items of the stored fulfillment TRK-OLD.
	 * @param string $tracking Tracking number of the row.
	 * @param string $cell     Items cell of the row.
	 * @param string $expected Items of the imported fulfillment, or the failure message.
	 */
	public function test_items_are_limited_to_what_is_left_to_fulfill( string $stored, string $tracking, string $cell, string $expected ): void {
		list( $order, $item_a, $item_b ) = $this->make_two_item_order();
		$replace                         = array(
			'{a}' => $item_a,
			'{b}' => $item_b,
			'{c}' => array_keys( $order->get_items() )[2],
		);
		$to_items                        = static function ( string $entries ) use ( $replace ): array {
			return array_map(
				static function ( $entry ) {
					list( $item_id, $qty ) = array_map( 'intval', explode( ':', $entry ) );
					return array(
						'item_id' => $item_id,
						'qty'     => $qty,
					);
				},
				explode( '|', strtr( $entries, $replace ) )
			);
		};
		FulfillmentsHelper::create_fulfillment(
			array(
				'entity_id' => (string) $order->get_id(),
				'status'    => 'fulfilled',
			),
			array(
				'_tracking_number' => 'TRK-OLD',
				'_items'           => $to_items( $stored ),
			)
		);
		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 1,
				'line_items' => array(
					$item_b => array(
						'qty'          => 1,
						'refund_total' => 1,
					),
				),
			)
		);

		$result = $this->import_csv( "order_number,tracking_number,shipment_provider,tracking_url,items\n{$order->get_id()},{$tracking},ups,,\"" . strtr( $cell, $replace ) . "\"\n", array( 'update_existing' => true ) );

		$items = array();
		foreach ( $this->fulfillments_of( $order ) as $fulfillment ) {
			$items = $tracking === $fulfillment->get_tracking_number() ? $fulfillment->get_items() : $items;
		}
		if ( '{' === $expected[0] ) {
			$this->assertNotSame( FulfillmentsCsvImporter::STATUS_FAILED, $result['rows'][0]['status'] );
			$this->assertSame( $to_items( $expected ), $items );
		} else {
			$this->assertSame( $expected, $result['rows'][0]['message'] );
			$this->assertCount( 1, $this->fulfillments_of( $order ) );
		}
	}

	/**
	 * @testdox A SKU shared by two line items of the order fails the row instead of picking one.
	 */
	public function test_sku_shared_by_two_line_items_fails_the_row(): void {
		list( $order, , $item_b ) = $this->make_two_item_order();
		$order->add_product( $order->get_item( $item_b )->get_product(), 1 );
		$order->save();

		$result = $this->import_csv( "order_number,tracking_number,shipment_provider,tracking_url,items\n{$order->get_id()},TRK-1,ups,,sku:sku-b:1\n" );

		$this->assertSame( FulfillmentsCsvImporter::CODE_INVALID_ITEMS, $result['rows'][0]['code'] ?? null );
		$this->assertSame( 'SKU sku-b matches more than one item of this order. Use the item ID instead.', $result['rows'][0]['message'] );
	}

	/**
	 * @testdox On an update a blank items cell keeps the stored items and a filled one replaces them.
	 */
	public function test_items_on_update_are_kept_when_blank_and_replaced_when_given(): void {
		list( $order, $item_a, $item_b ) = $this->make_two_item_order();
		$id                              = $order->get_id();
		$this->import_csv( "order_number,tracking_number,shipment_provider,tracking_url,items\n{$id},TRK-1,ups,,{$item_b}:1\n" );

		$this->import_csv( "order_number,tracking_number,shipment_provider,tracking_url,items\n{$id},TRK-1,fedex,,\n" );
		$kept = $this->fulfillments_of( $order )[0]->get_items();
		$this->import_csv( "order_number,tracking_number,shipment_provider,tracking_url,items\n{$id},TRK-1,fedex,,{$item_a}:2\n" );

		$this->assertSame(
			array(
				array(
					'item_id' => $item_b,
					'qty'     => 1,
				),
			),
			$kept,
			'A blank items cell must not widen a partial fulfillment to every item'
		);
		$this->assertSame(
			array(
				array(
					'item_id' => $item_a,
					'qty'     => 2,
				),
			),
			$this->fulfillments_of( $order )[0]->get_items()
		);
	}

	/**
	 * @testdox With notify_customer on, a newly fulfilled row fires the created notification and an update of a fulfilled one the updated notification.
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $notify Importer notify_customer option.
	 */
	public function test_notify_customer_fires_the_fulfillment_notifications( bool $notify ): void {
		$order = OrderHelper::create_order();
		$id    = $order->get_id();
		$item  = current( $order->get_items() )->get_id();
		FulfillmentsHelper::create_fulfillment(
			array(
				'entity_id' => (string) $id,
				'status'    => 'unfulfilled',
			),
			array(
				'_tracking_number' => 'TRK-2',
				'_items'           => array(
					array(
						'item_id' => $item,
						'qty'     => 1,
					),
				),
			)
		);
		$fired = array();
		add_action(
			'woocommerce_fulfillment_created_notification',
			function ( $order_id, $fulfillment, $notified_order ) use ( &$fired ) {
				$fired[] = array( 'created', $order_id, $fulfillment->get_tracking_number(), $notified_order->get_id() );
			},
			10,
			3
		);
		add_action(
			'woocommerce_fulfillment_updated_notification',
			function ( $order_id, $fulfillment, $notified_order, $note ) use ( &$fired ) {
				$fired[] = array( 'updated', $order_id, $fulfillment->get_tracking_number(), $notified_order->get_id(), $note );
			},
			10,
			4
		);

		$first  = $this->import_csv( "order_number,tracking_number,shipment_provider\n{$id},TRK-1,ups\n{$id},TRK-2,ups\n", array( 'notify_customer' => $notify ) );
		$second = $this->import_csv( "order_number,tracking_number,shipment_provider\n{$id},TRK-1,fedex\n", array( 'notify_customer' => $notify ) );

		$expected = array(
			array( 'created', $id, 'TRK-1', $id ),
			array( 'created', $id, 'TRK-2', $id ),
			array( 'updated', $id, 'TRK-1', $id, '' ),
		);
		$this->assertSame( $notify ? $expected : array(), $fired );
		$this->assertSame( $notify ? 2 : 0, $first['counts']['notified'] );
		$this->assertSame( $notify ? 1 : 0, $second['counts']['notified'] );
		$this->assertSame( array( FulfillmentsCsvImporter::STATUS_CREATED, FulfillmentsCsvImporter::STATUS_UPDATED, FulfillmentsCsvImporter::STATUS_UPDATED ), array_column( array_merge( $first['rows'], $second['rows'] ), 'status' ) );
		$this->assertSame( array( $notify, $notify, $notify ), array_column( array_merge( $first['rows'], $second['rows'] ), 'notified' ) );
	}

	/**
	 * @testdox A notification listener that throws leaves the row imported and not counted as notified.
	 */
	public function test_failing_notification_keeps_the_row_imported(): void {
		$order = OrderHelper::create_order();
		add_action(
			'woocommerce_fulfillment_created_notification',
			function () {
				throw new \Exception( 'Mail server down' );
			}
		);

		$result = $this->import_csv( "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRK-1,ups\n", array( 'notify_customer' => true ) );

		$this->assertSame( self::counts( array( FulfillmentsCsvImporter::STATUS_CREATED => 1 ) ), $result['counts'] );
		$this->assertFalse( $result['rows'][0]['notified'] );
		$this->assertCount( 1, $this->fulfillments_of( $order ) );
	}
}
