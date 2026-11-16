<?php
/**
 * Fulfillments CSV importer service.
 *
 * @package Automattic\WooCommerce\Admin\Features\Fulfillments\Importer
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Features\Fulfillments\Importer;

use Automattic\WooCommerce\Admin\Features\Fulfillments\DataStore\FulfillmentsDataStore;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentException;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentUtils;
use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Data_Store;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Reads a fulfillments CSV for the import wizard and imports it in chunks.
 *
 * The first non-blank record is the header. Later reads return only real data rows.
 * Blank records still advance the file position and keep their physical record numbers
 * so error messages can point at the row in the file.
 *
 * Cells are decoded one at a time: valid UTF-8 is kept and anything else is read as
 * Windows-1252, so a file that mixes both still comes out right. UTF-16 and CR-only
 * files are rejected with a message asking the merchant to re-save the file. Every cell
 * is trimmed, and a data row shorter than the header is padded with empty strings, so a
 * consumer can index a row by header position without a guard.
 *
 * Orders, providers and fulfillments looked up by import_chunk() are cached for the
 * life of the importer; the import run builds a new importer for each chunk.
 *
 * @internal
 * @since 11.4.0
 */
class FulfillmentsCsvImporter {

	/**
	 * Canonical column keys recognized by the importer.
	 */
	public const COL_ORDER_NUMBER    = 'order_number';
	public const COL_TRACKING_NUMBER = 'tracking_number';
	public const COL_PROVIDER        = 'shipment_provider';
	public const COL_TRACKING_URL    = 'tracking_url';
	public const COL_ITEMS           = 'items';

	/**
	 * Every canonical column key, in the order the wizard lists them.
	 *
	 * @return array<int, string>
	 */
	public static function canonical_columns(): array {
		return array(
			self::COL_ORDER_NUMBER,
			self::COL_TRACKING_NUMBER,
			self::COL_PROVIDER,
			self::COL_TRACKING_URL,
			self::COL_ITEMS,
		);
	}

	/**
	 * Absolute path to the CSV file.
	 *
	 * @var string
	 */
	private string $file;

	/**
	 * Options.
	 *
	 * @var array{notify_customer:bool,delimiter:string,enclosure:string,update_existing:bool}
	 */
	private array $options;

	/**
	 * Set when a cell that is not UTF-8 could not be converted from Windows-1252.
	 *
	 * Only happens when neither mbstring nor iconv is loaded. mbstring maps every
	 * Windows-1252 byte, so with it loaded the flag never trips.
	 *
	 * @var bool
	 */
	private bool $conversion_failed = false;

	/**
	 * Orders resolved so far, keyed by the order number cell. Null means not found.
	 *
	 * @var array<int|string, WC_Order|null>
	 */
	private array $order_cache = array();

	/**
	 * Orders named by ID in the chunks imported so far, loaded in one query per chunk and keyed by order ID.
	 *
	 * @var array<int, WC_Order>
	 */
	private array $primed_orders = array();

	/**
	 * Fulfillments of an order that are not deleted, keyed by order ID.
	 *
	 * @var array<int, array<int, Fulfillment>>
	 */
	private array $fulfillments_cache = array();

	/**
	 * Known provider keys by lowercase key and name, built on first use.
	 *
	 * @var array<string, string>|null
	 */
	private ?array $provider_keys = null;

	/**
	 * Maximum number of data rows accepted per file.
	 *
	 * The cap bounds the time the prepare request spends counting rows and the
	 * size of the session transient. Larger files must be split and imported in parts.
	 */
	public const MAX_IMPORT_ROWS = 5000;

	/**
	 * Data rows imported per chunk unless the chunk size filter changes it.
	 */
	public const DEFAULT_CHUNK_SIZE = 200;

	/**
	 * Largest chunk size accepted from the filter.
	 */
	public const MAX_CHUNK_SIZE = 1000;

	/**
	 * Largest chunk size while notifying customers, since every notified row sends an email.
	 */
	public const NOTIFY_CHUNK_SIZE = 25;

	/**
	 * Row statuses of import_chunk(); its counts use the same keys plus "notified".
	 */
	public const STATUS_CREATED = 'created';
	public const STATUS_UPDATED = 'updated';
	public const STATUS_SKIPPED = 'skipped';
	public const STATUS_FAILED  = 'failed';

	/**
	 * Codes of a failed row.
	 */
	public const CODE_MISSING_ORDER_NUMBER    = 'missing_order_number';
	public const CODE_MISSING_TRACKING_NUMBER = 'missing_tracking_number';
	public const CODE_MISSING_PROVIDER        = 'missing_provider';
	public const CODE_INVALID_TRACKING_URL    = 'invalid_tracking_url';
	public const CODE_ORDER_NOT_FOUND         = 'order_not_found';
	public const CODE_INVALID_ITEMS           = 'invalid_items';
	public const CODE_STORE_READ_FAILED       = 'store_read_failed';
	public const CODE_FULFILLMENT_LOCKED      = 'fulfillment_locked';
	public const CODE_NO_ITEMS                = 'no_items';
	public const CODE_SAVE_FAILED             = 'save_failed';
	public const CODE_ROW_FAILED              = 'row_failed';

	/**
	 * Spellings accepted for a tab delimiter, since multipart and JSON clients
	 * cannot always send a raw tab character.
	 */
	private const TAB_SPELLINGS = array( '\t', '\\\\t', 'tab' );

	/**
	 * Bytes read at a time while checking the file for UTF-16 and CR-only line endings.
	 */
	private const INSPECT_CHUNK_BYTES = 8192;

	/**
	 * Constructor.
	 *
	 * @since 11.4.0
	 *
	 * @param string               $file    Absolute path to the CSV file.
	 * @param array<string, mixed> $options Importer options. Header parsing and row reading use delimiter and
	 *                                      enclosure; import_chunk() also uses the other two.
	 *                                      - notify_customer (bool): Whether to fire customer notifications. Default false.
	 *                                      - delimiter (string): Single-character CSV delimiter. Default ','.
	 *                                      - enclosure (string): Single-byte CSV enclosure. A line break, a non-ASCII byte,
	 *                                        the delimiter, or anything that is not one byte falls back to '"'.
	 *                                      - update_existing (bool): Update fulfillment when one with the same tracking number
	 *                                                                already exists on the order. Default true.
	 */
	public function __construct( string $file, array $options = array() ) {
		$delimiter     = self::normalize_delimiter( $options['delimiter'] ?? ',' );
		$this->file    = $file;
		$this->options = array(
			'notify_customer' => ! empty( $options['notify_customer'] ),
			'delimiter'       => $delimiter,
			'enclosure'       => self::normalize_enclosure( $options['enclosure'] ?? '"', $delimiter ),
			'update_existing' => array_key_exists( 'update_existing', $options ) ? (bool) $options['update_existing'] : true,
		);
	}

	/**
	 * Normalize a delimiter input to the single ASCII byte fgetcsv() needs.
	 *
	 * The spellings "\t", "\\t" and "tab" map to a tab character. Longer strings are
	 * clamped to their first byte. Empty, non-string, non-ASCII, line-break and
	 * double-quote inputs fall back to a comma.
	 *
	 * @since 11.4.0
	 *
	 * @param mixed $delimiter Raw delimiter input.
	 * @return string Delimiter string (defaults to ',').
	 */
	public static function normalize_delimiter( $delimiter ): string {
		if ( ! is_string( $delimiter ) || '' === $delimiter ) {
			return ',';
		}
		if ( self::is_tab_spelling( $delimiter ) ) {
			return "\t";
		}
		// substr() slices by byte; the first byte of a multibyte character is a
		// malformed fragment that would make fgetcsv() silently mis-parse the file,
		// so anything outside the ASCII range falls back to the default.
		$first = substr( $delimiter, 0, 1 );
		if ( ord( $first ) >= 0x80 || in_array( $first, array( "\r", "\n", '"' ), true ) ) {
			return ',';
		}
		return $first;
	}

	/**
	 * Normalize an enclosure to the single byte fgetcsv() can use.
	 *
	 * Empty, non-string, and multi-byte values fall back to a double quote. So do
	 * line breaks, bytes outside ASCII, and the delimiter itself: fgetcsv() would
	 * accept them and then split quoted fields in the wrong places.
	 *
	 * @since 11.4.0
	 *
	 * @param mixed  $enclosure Raw enclosure input.
	 * @param string $delimiter Delimiter already normalized to one byte.
	 * @return string Enclosure character.
	 */
	public static function normalize_enclosure( $enclosure, string $delimiter ): string {
		if ( ! is_string( $enclosure ) || 1 !== strlen( $enclosure ) ) {
			return '"';
		}
		if ( $enclosure === $delimiter || ord( $enclosure ) >= 0x80 || "\r" === $enclosure || "\n" === $enclosure ) {
			return '"';
		}
		return $enclosure;
	}

	/**
	 * Number of data rows to import per chunk.
	 *
	 * The filtered value is kept between 1 and MAX_CHUNK_SIZE, and at most NOTIFY_CHUNK_SIZE
	 * when customers are notified, so a chunk stays within typical request time limits.
	 *
	 * @since 11.4.0
	 *
	 * @param bool $notify_customer Whether the import sends customer notifications.
	 * @return int
	 */
	public static function resolve_chunk_size( bool $notify_customer = false ): int {
		/**
		 * Filter the number of CSV data rows the fulfillments importer processes per request.
		 *
		 * The result is kept between 1 and 1000, and at most 25 while customer notifications
		 * are on. A value that is not a positive number falls back to the default.
		 *
		 * @since 11.4.0
		 *
		 * @param int  $chunk_size      Default chunk size (200).
		 * @param bool $notify_customer Whether the import sends customer notifications.
		 */
		$size = apply_filters( 'woocommerce_fulfillments_csv_importer_chunk_size', self::DEFAULT_CHUNK_SIZE, $notify_customer );
		$size = is_numeric( $size ) ? (int) $size : 0;
		if ( $size < 1 ) {
			$size = self::DEFAULT_CHUNK_SIZE;
		}
		return min( $size, $notify_customer ? self::NOTIFY_CHUNK_SIZE : self::MAX_CHUNK_SIZE );
	}

	/**
	 * Whether a delimiter input is one of the accepted spellings of a tab character.
	 *
	 * @since 11.4.0
	 *
	 * @param string $delimiter Raw delimiter input.
	 * @return bool
	 */
	public static function is_tab_spelling( string $delimiter ): bool {
		return in_array( strtolower( $delimiter ), self::TAB_SPELLINGS, true );
	}

	/**
	 * Parse the CSV header row and return metadata sufficient to drive the column-mapping UI.
	 *
	 * Streams the file, counting non-blank data rows and keeping the first as a sample.
	 * `sample` has at least as many entries as `headers`: a short row is padded with empty
	 * strings and a longer row keeps its extra cells. Header and data cells are trimmed.
	 * Blank records before the header and between data rows are skipped. Does not fail
	 * when required canonical columns cannot be auto-detected; the caller can present
	 * the mapping UI so the user resolves it manually.
	 *
	 * `total` is the exact number of non-blank data rows, except when the file has more
	 * than the cap. Counting stops once `MAX_IMPORT_ROWS + 1` data rows have been seen
	 * and one further data row proves the file is larger. `total` is then
	 * `MAX_IMPORT_ROWS + 1` and `total_capped` is true. That value is a sentinel, not
	 * an exact count. A file with exactly 5,001 data rows, including one that ends
	 * after blank records, has the same `total` and `total_capped` false. The unread
	 * tail of a larger file is not scanned. Callers reject either over-cap case with
	 * `total > MAX_IMPORT_ROWS`, and must not display `total` when `total_capped` is true.
	 *
	 * @since 11.4.0
	 *
	 * @param string $delimiter Delimiter override; falls back to the constructor delimiter when empty.
	 * @return array{
	 *     headers?: array<int, string>,
	 *     sample?: array<int, string>,
	 *     total?: int,
	 *     total_capped?: bool,
	 *     detected_mapping?: array<int, string>,
	 *     delimiter?: string,
	 *     error?: array{code:string, message:string}
	 * }
	 */
	public function parse_headers( string $delimiter = '' ): array {
		$opened = $this->open_csv( $delimiter );
		if ( isset( $opened['error'] ) ) {
			return $opened;
		}

		$handle = $opened['handle'];
		try {
			$this->strip_bom( $handle );
			$summary = $this->read_header_summary( $handle, $opened['delimiter'] );
			if ( isset( $summary['error'] ) ) {
				return $summary;
			}
			if ( $this->conversion_failed ) {
				return $this->unsupported_encoding_error();
			}

			$header_map       = $this->build_header_map( $summary['headers'] );
			$detected_mapping = array();
			foreach ( $header_map as $canonical => $col_index ) {
				$detected_mapping[ (int) $col_index ] = (string) $canonical;
			}

			return array(
				'headers'          => $summary['headers'],
				'sample'           => $summary['sample'],
				'total'            => $summary['total'],
				'total_capped'     => $summary['total_capped'],
				'detected_mapping' => $detected_mapping,
				'delimiter'        => $opened['delimiter'],
			);
		} finally {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing the handle opened above.
			fclose( $handle );
		}
	}

	/**
	 * Read a window of real data rows.
	 *
	 * This is the reader the chunked import run should use. The first non-blank record
	 * is the header and is not returned in `rows`. `$offset` and `$limit` count only
	 * real data rows. Blank records, including ",,," and whitespace-only rows, are not
	 * returned and do not count, but `byte_offset` still moves past them to the next
	 * data row (or to the end of the file). Each returned row's `row_number` is its
	 * 1-based physical record number, counting the header and blank records. Each row's
	 * `cells` has at least `count( headers )` entries: a short row is padded with empty
	 * strings so `$cells[ $index ]` is always a string for a mapped column, and a longer
	 * row keeps its extra cells. Every cell is trimmed.
	 *
	 * Pass a previous result's `byte_offset` and `next_record_number` to resume without
	 * reading the skipped rows again. `byte_offset` 0 always starts at the beginning.
	 * A non-zero `byte_offset` must be a position this method returned. `offset` still
	 * skips that many further data rows after the seek.
	 *
	 * @since 11.4.0
	 *
	 * @param int                  $offset  Real data rows to skip after the header or the seek point.
	 * @param int                  $limit   Maximum number of real data rows to return.
	 * @param array<string, mixed> $options {
	 *     Optional read state.
	 *
	 *     @type int    $byte_offset    File position from a previous result. 0 starts from the beginning.
	 *     @type int    $record_number  Physical record number to assign at `$byte_offset`. Required when resuming;
	 *                                  pass the previous `next_record_number`. Ignored when `$byte_offset` is 0.
	 *     @type string $delimiter      Delimiter override. Empty uses the constructor delimiter.
	 * }
	 * @return array{
	 *     headers?: array<int, string>,
	 *     header_row_number?: int,
	 *     rows?: array<int, array{row_number:int, cells:array<int, string>}>,
	 *     byte_offset?: int,
	 *     next_record_number?: int,
	 *     consumed?: int,
	 *     skipped?: int,
	 *     eof?: bool,
	 *     delimiter?: string,
	 *     error?: array{code:string, message:string}
	 * }
	 */
	public function read_data_rows( int $offset, int $limit, array $options = array() ): array {
		$offset      = max( 0, $offset );
		$limit       = max( 0, $limit );
		$byte_offset = isset( $options['byte_offset'] ) ? max( 0, (int) $options['byte_offset'] ) : 0;
		$delimiter   = isset( $options['delimiter'] ) && is_string( $options['delimiter'] ) ? $options['delimiter'] : '';
		$opened      = $this->open_csv( $delimiter );
		if ( isset( $opened['error'] ) ) {
			return $opened;
		}

		$handle = $opened['handle'];
		try {
			// Byte offsets are positions after a leading BOM has been removed. Strip
			// it on every open, including a resume, before seeking to one of them.
			$this->strip_bom( $handle );
			$result = $this->read_data_window( $handle, $opened['delimiter'], $offset, $limit, $byte_offset, $options );
			if ( isset( $result['error'] ) ) {
				return $result;
			}
			if ( $this->conversion_failed ) {
				return $this->unsupported_encoding_error();
			}
			return $result;
		} finally {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing the handle opened above.
			fclose( $handle );
		}
	}

	/**
	 * Import the next chunk of data rows as fulfillments.
	 *
	 * A row creates a fulfilled fulfillment, updates the order's fulfillment with the same
	 * tracking number (compared without case), or fails on its own with a code while the
	 * rest of the chunk imports. An update that would change nothing is skipped. Pass the
	 * previous result back as `$state` to continue; an empty state starts after the header.
	 * A chunk-level problem returns `error` and writes nothing.
	 *
	 * Each row has `row`, `order_number`, `status` (created, updated, skipped or failed) and
	 * `message`; a failed row adds `code`. `order_id` is set once the order resolves, and
	 * `fulfillment_id` when the row matches or saves a fulfillment. A created or updated row
	 * adds `notified`: whether the notification action ran without an error, which is not
	 * proof that an email was sent. Messages are plain text and can quote CSV values.
	 *
	 * @since 11.4.0
	 *
	 * @param int                  $limit   Data rows to import. Capped by resolve_chunk_size(), which is also used when 0 or less.
	 * @param array<int, string>   $mapping CSV column index => canonical column key.
	 * @param array<string, mixed> $state {
	 *     Resume state; a previous result can be passed as is.
	 *
	 *     @type int                 $byte_offset         Position to resume from. 0 starts after the header.
	 *     @type int                 $next_record_number  Physical record number at byte_offset.
	 *     @type array<string, true> $seen_tracking_pairs Order and tracking number pairs imported so far.
	 * }
	 * @return array{
	 *     counts?: array{created:int, updated:int, skipped:int, failed:int, notified:int},
	 *     rows?: array<int, array<string, mixed>>,
	 *     seen_tracking_pairs?: array<string, true>,
	 *     consumed?: int,
	 *     byte_offset?: int,
	 *     next_record_number?: int,
	 *     eof?: bool,
	 *     error?: array{code:string, message:string}
	 * }
	 */
	public function import_chunk( int $limit, array $mapping, array $state = array() ): array {
		$header_map = self::mapping_to_header_map( $mapping );
		$missing    = self::find_missing_required_columns( $header_map );
		if ( array() !== $missing ) {
			return $this->error(
				'missing_required_columns',
				sprintf(
					/* translators: %s: comma-separated list of missing column names. */
					__( 'The column mapping is missing required columns: %s.', 'woocommerce' ),
					implode( ', ', $missing )
				)
			);
		}

		$max  = self::resolve_chunk_size( $this->options['notify_customer'] );
		$read = $this->read_data_rows(
			0,
			$limit > 0 ? min( $limit, $max ) : $max,
			array(
				'byte_offset'   => (int) ( $state['byte_offset'] ?? 0 ),
				'record_number' => (int) ( $state['next_record_number'] ?? 0 ),
			)
		);
		if ( isset( $read['error'] ) ) {
			return $read;
		}

		/**
		 * Pairs imported by earlier chunks.
		 *
		 * @var array<string, true> $seen
		 */
		$seen = is_array( $state['seen_tracking_pairs'] ?? null ) ? $state['seen_tracking_pairs'] : array();
		$rows = array();
		$data = $read['rows'] ?? array();

		$hook_depth = count( $GLOBALS['wp_current_filter'] );
		$this->prime_orders( $data, $header_map );
		foreach ( $data as $row ) {
			$order_number = self::get_field( $row['cells'], $header_map, self::COL_ORDER_NUMBER );
			try {
				$result = $this->import_row( $row['cells'], $header_map, $order_number, $row['row_number'], $seen );
			} catch ( \Throwable $e ) {
				// One row that throws, for example in a resolve-order callback, must not stop the chunk.
				self::restore_filter_stack( $hook_depth );
				wc_get_logger()->error(
					sprintf( 'Fulfillments import could not import row %1$d: %2$s', $row['row_number'], $e->getMessage() ),
					array( 'source' => 'fulfillments-importer' )
				);
				$result = self::fail( self::CODE_ROW_FAILED, __( 'Could not import this row.', 'woocommerce' ) );
			}
			$rows[] = array(
				'row'          => $row['row_number'],
				'order_number' => self::clip( $order_number ),
			) + $result;
		}

		$statuses = array_count_values( array_column( $rows, 'status' ) );
		return array(
			'counts'              => array(
				self::STATUS_CREATED => $statuses[ self::STATUS_CREATED ] ?? 0,
				self::STATUS_UPDATED => $statuses[ self::STATUS_UPDATED ] ?? 0,
				self::STATUS_SKIPPED => $statuses[ self::STATUS_SKIPPED ] ?? 0,
				self::STATUS_FAILED  => $statuses[ self::STATUS_FAILED ] ?? 0,
				'notified'           => count( array_filter( array_column( $rows, 'notified' ) ) ),
			),
			'rows'                => $rows,
			'seen_tracking_pairs' => $seen,
			'consumed'            => (int) ( $read['consumed'] ?? 0 ),
			'byte_offset'         => (int) ( $read['byte_offset'] ?? 0 ),
			'next_record_number'  => (int) ( $read['next_record_number'] ?? 0 ),
			'eof'                 => ! empty( $read['eof'] ),
		);
	}

	/**
	 * Validate one data row and create or update its fulfillment.
	 *
	 * @param array<int, string>  $cells        Trimmed cells, padded to the header width.
	 * @param array<string, int>  $header_map   Canonical column key => CSV column index.
	 * @param string              $order_number Order number cell.
	 * @param int                 $row_number   Physical record number, for the log.
	 * @param array<string, true> $seen         Pairs imported so far; a pair is added once its row lands.
	 * @return array<string, mixed> Status and message, plus code, order_id, fulfillment_id and notified when they apply.
	 */
	private function import_row( array $cells, array $header_map, string $order_number, int $row_number, array &$seen ): array {
		$tracking_number = self::get_field( $cells, $header_map, self::COL_TRACKING_NUMBER );
		$provider        = self::get_field( $cells, $header_map, self::COL_PROVIDER );
		$tracking_url    = self::get_field( $cells, $header_map, self::COL_TRACKING_URL );

		if ( '' === $order_number ) {
			return self::fail( self::CODE_MISSING_ORDER_NUMBER, __( 'Missing order number.', 'woocommerce' ) );
		}
		if ( '' === $tracking_number ) {
			return self::fail( self::CODE_MISSING_TRACKING_NUMBER, __( 'Missing tracking number.', 'woocommerce' ) );
		}
		if ( '' === $provider ) {
			return self::fail( self::CODE_MISSING_PROVIDER, __( 'Missing shipment provider.', 'woocommerce' ) );
		}
		if ( '' !== $tracking_url ) {
			$tracking_url = $this->sanitize_tracking_url( $tracking_url );
			if ( '' === $tracking_url ) {
				return self::fail( self::CODE_INVALID_TRACKING_URL, __( 'Tracking URL must be a valid http or https URL.', 'woocommerce' ) );
			}
		}

		$order = $this->resolve_order( $order_number );
		if ( null === $order ) {
			return self::fail(
				self::CODE_ORDER_NOT_FOUND,
				/* translators: %s: order number from the CSV. */
				sprintf( __( 'Order not found for order number "%s".', 'woocommerce' ), self::clip( $order_number ) )
			);
		}
		$order_id = $order->get_id();

		// The set is stored with the session, so pairs are hashed to a fixed length.
		$pair = substr( md5( $order_id . '|' . strtolower( $tracking_number ) ), 0, 20 );
		if ( isset( $seen[ $pair ] ) ) {
			return array(
				'status'   => self::STATUS_SKIPPED,
				'message'  => __( 'Duplicate order and tracking number in this file.', 'woocommerce' ),
				'order_id' => $order_id,
			);
		}

		try {
			$existing = $this->find_existing_fulfillment( $order_id, $tracking_number );
		} catch ( \RuntimeException $e ) {
			return self::fail( self::CODE_STORE_READ_FAILED, __( 'Could not read the fulfillments of this order. Please try again.', 'woocommerce' ), $order_id );
		}

		$pending    = $this->pending_items( $order, $existing );
		$items_cell = self::get_field( $cells, $header_map, self::COL_ITEMS );
		$items      = '' === $items_cell ? null : $this->parse_items( $items_cell, $order, $pending );
		if ( is_string( $items ) ) {
			return self::fail( self::CODE_INVALID_ITEMS, $items, $order_id );
		}
		if ( null === $items && null === $existing ) {
			// A blank items cell fulfills what is left on create, but keeps the stored items on update.
			$items = array();
			foreach ( $pending as $item_id => $qty ) {
				$items[] = array(
					'item_id' => $item_id,
					'qty'     => $qty,
				);
			}
		}

		if ( null !== $existing && ! $this->options['update_existing'] ) {
			return array(
				'status'         => self::STATUS_SKIPPED,
				'message'        => __( 'Fulfillment with this tracking number already exists.', 'woocommerce' ),
				'order_id'       => $order_id,
				'fulfillment_id' => $existing->get_id(),
			);
		}
		if ( null !== $existing && $existing->is_locked() ) {
			return self::fail( self::CODE_FULFILLMENT_LOCKED, __( 'The fulfillment with this tracking number is locked and cannot be changed.', 'woocommerce' ), $order_id );
		}

		$result = $this->save_fulfillment(
			$order,
			$existing,
			$items,
			array(
				'tracking_number' => $tracking_number,
				'provider'        => $provider,
				'tracking_url'    => $tracking_url,
			),
			$row_number
		);
		if ( self::STATUS_FAILED !== $result['status'] ) {
			$seen[ $pair ] = true;
		}
		return $result;
	}

	/**
	 * Create a fulfilled fulfillment for the order, or update the matching one, and notify the customer when asked.
	 *
	 * @param WC_Order                                                            $order      Order of the row.
	 * @param Fulfillment|null                                                    $existing   Fulfillment with the row's tracking number, if any.
	 * @param array<int, array{item_id:int, qty:int}>|null                        $items      Items to store; null keeps the stored items of an update.
	 * @param array{tracking_number:string, provider:string, tracking_url:string} $fields     Validated cells; the URL may be empty.
	 * @param int                                                                 $row_number Physical record number, for the log.
	 * @return array<string, mixed> Row result without the row number and order number.
	 */
	private function save_fulfillment( WC_Order $order, ?Fulfillment $existing, ?array $items, array $fields, int $row_number ): array {
		$order_id      = $order->get_id();
		$fulfillment   = $existing ?? new Fulfillment();
		$was_fulfilled = null !== $existing && $existing->get_is_fulfilled();
		if ( null === $existing ) {
			if ( array() === $items ) {
				return self::fail( self::CODE_NO_ITEMS, __( 'This order has no items left to fulfill.', 'woocommerce' ), $order_id );
			}
			$fulfillment->set_entity_type( WC_Order::class );
			$fulfillment->set_entity_id( (string) $order_id );
		}
		if ( null !== $items ) {
			$fulfillment->set_items( $items );
		}
		if ( ! $fulfillment->get_is_fulfilled() ) {
			$fulfillment->set_status( 'fulfilled' );
		}
		$fulfillment->set_tracking_number( $fields['tracking_number'] );
		$provider_key = $this->known_provider_key( $fields['provider'] );
		// An unknown provider is stored the way the fulfillment editor stores a custom one.
		$fulfillment->set_shipment_provider( '' !== $provider_key ? $provider_key : 'other' );
		if ( '' === $provider_key ) {
			$fulfillment->update_meta_data( '_provider_name', $fields['provider'] );
		}
		// A blank URL cell keeps the stored URL on an update.
		if ( '' !== $fields['tracking_url'] ) {
			$fulfillment->set_tracking_url( $fields['tracking_url'] );
		}
		if ( null !== $existing && array() === $fulfillment->get_changes() ) {
			return array(
				'status'         => self::STATUS_SKIPPED,
				'message'        => __( 'Fulfillment unchanged.', 'woocommerce' ),
				'order_id'       => $order_id,
				'fulfillment_id' => $existing->get_id(),
			);
		}

		$hook_depth = count( $GLOBALS['wp_current_filter'] );
		// The data store fires this action only once the row is written.
		$written_hook = null === $existing ? 'woocommerce_fulfillment_after_create' : 'woocommerce_fulfillment_after_update';
		$writes       = did_action( $written_hook );
		try {
			$fulfillment->save();
		} catch ( \Throwable $e ) {
			self::restore_filter_stack( $hook_depth );
			wc_get_logger()->error(
				sprintf( 'Fulfillments import could not save row %1$d for order %2$d: %3$s', $row_number, $order_id, $e->getMessage() ),
				array( 'source' => 'fulfillments-importer' )
			);
			// The cached fulfillment may hold values that were not written, so the next row reads it again.
			unset( $this->fulfillments_cache[ $order_id ] );
			if ( did_action( $written_hook ) === $writes ) {
				return self::fail( self::CODE_SAVE_FAILED, $e instanceof FulfillmentException ? $e->getMessage() : __( 'Could not save the fulfillment.', 'woocommerce' ), $order_id );
			}
		}
		if ( null === $existing && isset( $this->fulfillments_cache[ $order_id ] ) ) {
			$this->fulfillments_cache[ $order_id ][] = $fulfillment;
		}

		return array(
			'status'         => null === $existing ? self::STATUS_CREATED : self::STATUS_UPDATED,
			'message'        => null === $existing ? __( 'Fulfillment created.', 'woocommerce' ) : __( 'Fulfillment updated.', 'woocommerce' ),
			'order_id'       => $order_id,
			'fulfillment_id' => $fulfillment->get_id(),
			'notified'       => $this->options['notify_customer'] && $fulfillment->get_is_fulfilled() && $this->notify_customer( $order, $fulfillment, $was_fulfilled, $row_number ),
		);
	}

	/**
	 * Fire the customer notification the fulfillments screen fires for the same change.
	 *
	 * The row is already saved, so a listener that throws is logged and the row still
	 * counts as imported. True means the action ran, not that an email was sent: the
	 * email can be turned off in the settings.
	 *
	 * @param WC_Order    $order         Order of the row.
	 * @param Fulfillment $fulfillment   Saved fulfillment.
	 * @param bool        $was_fulfilled Whether the fulfillment was fulfilled before this row.
	 * @param int         $row_number    Physical record number, for the log.
	 * @return bool Whether the notification action ran without an error.
	 */
	private function notify_customer( WC_Order $order, Fulfillment $fulfillment, bool $was_fulfilled, int $row_number ): bool {
		$hook_depth = count( $GLOBALS['wp_current_filter'] );
		try {
			if ( $was_fulfilled ) {
				/**
				 * This action is documented in src/Admin/Features/Fulfillments/OrderFulfillmentsRestController.php.
				 *
				 * @since 10.1.0
				 * @since 10.8.0 Added $customer_note parameter.
				 */
				do_action( 'woocommerce_fulfillment_updated_notification', $order->get_id(), $fulfillment, $order, '' );
			} else {
				/**
				 * This action is documented in src/Admin/Features/Fulfillments/OrderFulfillmentsRestController.php.
				 *
				 * @since 10.1.0
				 */
				do_action( 'woocommerce_fulfillment_created_notification', $order->get_id(), $fulfillment, $order );
			}
			return true;
		} catch ( \Throwable $e ) {
			self::restore_filter_stack( $hook_depth );
			wc_get_logger()->error(
				sprintf( 'Fulfillments import could not notify the customer for row %1$d of order %2$d: %3$s', $row_number, $order->get_id(), $e->getMessage() ),
				array( 'source' => 'fulfillments-importer' )
			);
			return false;
		}
	}

	/**
	 * Take the hooks a throwing listener left on the filter stack back off it.
	 *
	 * Otherwise doing_action() stays true for them, and the data store skips its
	 * after-save actions, which add order notes and update the order status, for later rows.
	 *
	 * @param int $depth Stack depth before the call that threw.
	 */
	private static function restore_filter_stack( int $depth ): void {
		global $wp_current_filter;
		array_splice( $wp_current_filter, $depth );
	}

	/**
	 * Build a failed row result.
	 *
	 * @param string $code     Machine-readable failure code.
	 * @param string $message  Plain text that can hold raw CSV values; escape it on output.
	 * @param int    $order_id Resolved order ID, or 0 when the order is not known.
	 * @return array<string, mixed>
	 */
	private static function fail( string $code, string $message, int $order_id = 0 ): array {
		$result = array(
			'status'  => self::STATUS_FAILED,
			'code'    => $code,
			'message' => $message,
		);
		if ( $order_id > 0 ) {
			$result['order_id'] = $order_id;
		}
		return $result;
	}

	/**
	 * Cut a CSV value echoed in a row result, since the results are stored with the session.
	 *
	 * @param string $value CSV value.
	 * @return string The value, or its first 100 characters followed by "...".
	 */
	private static function clip( string $value ): string {
		return mb_strlen( $value ) > 100 ? mb_substr( $value, 0, 100 ) . '...' : $value;
	}

	/**
	 * Read a mapped cell of a row.
	 *
	 * @param array<int, string> $cells      Trimmed cells.
	 * @param array<string, int> $header_map Canonical column key => CSV column index.
	 * @param string             $column     Canonical column key.
	 * @return string Empty when the column is not mapped or the row has no such cell.
	 */
	private static function get_field( array $cells, array $header_map, string $column ): string {
		return isset( $header_map[ $column ] ) ? (string) ( $cells[ $header_map[ $column ] ] ?? '' ) : '';
	}

	/**
	 * Keep a tracking URL only when it is an http or https URL.
	 *
	 * The URL is shown as a link in the admin and in customer emails. esc_url_raw() adds
	 * "http://" to a cell without a scheme, so the cell itself must start with one.
	 *
	 * @param string $url URL cell.
	 * @return string Sanitized URL, or empty when it is not allowed.
	 */
	private function sanitize_tracking_url( string $url ): string {
		$lower = strtolower( $url );
		if ( ! str_starts_with( $lower, 'http://' ) && ! str_starts_with( $lower, 'https://' ) ) {
			return '';
		}
		$url = esc_url_raw( $url, array( 'http', 'https' ) );
		return '' !== (string) wp_parse_url( $url, PHP_URL_HOST ) ? $url : '';
	}

	/**
	 * Map a provider cell to a known provider key, matching the key or the name without case.
	 *
	 * @param string $cell Provider cell.
	 * @return string Provider key, or empty when the provider is not known.
	 */
	private function known_provider_key( string $cell ): string {
		if ( null === $this->provider_keys ) {
			$this->provider_keys = array();
			foreach ( FulfillmentUtils::get_shipping_providers() as $key => $provider ) {
				$this->provider_keys[ wc_strtolower( $provider->get_name() ) ] = (string) $key;
				$this->provider_keys[ wc_strtolower( (string) $key ) ]         = (string) $key;
			}
		}
		return $this->provider_keys[ wc_strtolower( $cell ) ] ?? '';
	}

	/**
	 * Load the orders a chunk names by ID in one query.
	 *
	 * The import run builds a new importer for each request, so without this every row
	 * would load its order on its own. The resolve-order filter still runs once per distinct
	 * order number.
	 *
	 * @param array<int, array{row_number:int, cells:array<int, string>}> $rows       Rows of the chunk.
	 * @param array<string, int>                                          $header_map Canonical column key => CSV column index.
	 */
	private function prime_orders( array $rows, array $header_map ): void {
		$ids = array();
		foreach ( $rows as $row ) {
			$order_number = self::get_field( $row['cells'], $header_map, self::COL_ORDER_NUMBER );
			if ( ctype_digit( $order_number ) ) {
				$ids[] = (int) $order_number;
			}
		}
		$ids = array_values( array_unique( $ids ) );
		if ( array() === $ids ) {
			return;
		}

		// post__in is the ID argument both order data stores understand. The default status
		// list includes checkout drafts, which are carts that were never placed.
		$orders = wc_get_orders(
			array(
				'post__in' => $ids,
				'limit'    => count( $ids ),
				'status'   => array_diff( array_keys( wc_get_order_statuses() ), array( 'wc-' . OrderStatus::CHECKOUT_DRAFT ) ),
			)
		);
		// The result passes through the woocommerce_order_query filter, so it is checked.
		foreach ( is_array( $orders ) ? $orders : array() as $order ) {
			if ( $order instanceof WC_Order ) {
				$this->primed_orders[ $order->get_id() ] = $order;
			}
		}
	}

	/**
	 * Resolve an order number cell to an order.
	 *
	 * A number is looked up among the orders prime_orders() loaded by ID: orders with a registered
	 * order status other than checkout-draft, so trashed and checkout-draft orders are not found. The
	 * filter then decides, so a store with its own order numbers can map them. The result is cached
	 * on the importer.
	 *
	 * @param string $order_number Order number cell.
	 * @return WC_Order|null
	 */
	private function resolve_order( string $order_number ): ?WC_Order {
		if ( array_key_exists( $order_number, $this->order_cache ) ) {
			return $this->order_cache[ $order_number ];
		}

		$order = null;
		if ( ctype_digit( $order_number ) ) {
			$order = $this->primed_orders[ (int) $order_number ] ?? null;
		}

		/**
		 * Filter the order a fulfillments CSV row is imported into.
		 *
		 * The default looks up a numeric order number as an order ID. Return a WC_Order to
		 * map custom order numbers, or null when the order cannot be found. Any other value
		 * fails the row.
		 *
		 * @since 11.4.0
		 *
		 * @param WC_Order|null $order        Order found by ID, or null.
		 * @param string        $order_number Order number cell from the CSV.
		 */
		$order = apply_filters( 'woocommerce_fulfillments_csv_importer_resolve_order', $order instanceof WC_Order ? $order : null, $order_number );

		$this->order_cache[ $order_number ] = $order instanceof WC_Order ? $order : null;
		return $this->order_cache[ $order_number ];
	}

	/**
	 * Find the order's fulfillment that is not deleted and has this tracking number, ignoring case.
	 *
	 * @param int    $order_id        Order ID.
	 * @param string $tracking_number Tracking number.
	 * @return Fulfillment|null
	 *
	 * @throws \RuntimeException When the fulfillments cannot be read.
	 */
	private function find_existing_fulfillment( int $order_id, string $tracking_number ): ?Fulfillment {
		if ( ! isset( $this->fulfillments_cache[ $order_id ] ) ) {
			global $wpdb;
			/**
			 * Fulfillments data store.
			 *
			 * @var FulfillmentsDataStore $store
			 */
			$store        = WC_Data_Store::load( 'order-fulfillment' );
			$fulfillments = $store->read_fulfillments( WC_Order::class, (string) $order_id );
			// A failed query reads as no fulfillments, which would turn an update into a duplicate.
			if ( '' !== $wpdb->last_error ) {
				wc_get_logger()->error(
					sprintf( 'Fulfillments import could not read the fulfillments of order %1$d: %2$s', $order_id, $wpdb->last_error ),
					array( 'source' => 'fulfillments-importer' )
				);
				// Not cached, so the next row of this order reads again.
				throw new \RuntimeException( 'fulfillments_read_failed' );
			}
			$this->fulfillments_cache[ $order_id ] = $fulfillments;
		}

		$needle = strtolower( $tracking_number );
		foreach ( $this->fulfillments_cache[ $order_id ] as $fulfillment ) {
			if ( $fulfillment instanceof Fulfillment && strtolower( (string) $fulfillment->get_tracking_number() ) === $needle ) {
				return $fulfillment;
			}
		}
		return null;
	}

	/**
	 * Parse an items cell into fulfillment items.
	 *
	 * Entries are separated by "|" or ";" and are either "<order item ID>:<qty>" or
	 * "sku:<SKU>:<qty>"; empty entries are skipped. Quantities of a repeated item are
	 * added up before they are checked against the quantity left to fulfill.
	 *
	 * @param string          $cell    Items cell, not empty.
	 * @param WC_Order        $order   Order of the row.
	 * @param array<int, int> $pending Order item ID => quantity left to fulfill.
	 * @return array<int, array{item_id:int, qty:int}>|string Items, or a message saying why the cell is invalid.
	 */
	private function parse_items( string $cell, WC_Order $order, array $pending ) {
		$line_items = $order->get_items( 'line_item' );
		$by_sku     = null;
		$totals     = array();
		foreach ( explode( '|', str_replace( ';', '|', $cell ) ) as $entry ) {
			$entry = trim( $entry );
			if ( '' === $entry ) {
				continue;
			}
			$parts  = array_map( 'trim', explode( ':', $entry ) );
			$is_sku = 'sku' === strtolower( $parts[0] );
			if ( count( $parts ) !== ( $is_sku ? 3 : 2 ) ) {
				/* translators: %s: one entry of the items cell. */
				return sprintf( __( 'Invalid items entry: %s.', 'woocommerce' ), self::clip( $entry ) );
			}
			if ( $is_sku ) {
				// Built on first use, since it loads the product of every line item.
				$by_sku = $by_sku ?? $this->index_items_by_sku( $line_items );
				$item   = $by_sku[ strtolower( $parts[1] ) ] ?? null;
				if ( false === $item ) {
					/* translators: %s: SKU from the CSV. */
					return sprintf( __( 'SKU %s matches more than one item of this order. Use the item ID instead.', 'woocommerce' ), self::clip( $parts[1] ) );
				}
				if ( null === $item ) {
					/* translators: %s: SKU from the CSV. */
					return sprintf( __( 'SKU %s was not found on the order.', 'woocommerce' ), self::clip( $parts[1] ) );
				}
			} else {
				$item = ctype_digit( $parts[0] ) ? ( $line_items[ (int) $parts[0] ] ?? null ) : null;
				if ( null === $item ) {
					/* translators: %s: order item ID from the CSV. */
					return sprintf( __( 'Item ID %s is not part of this order.', 'woocommerce' ), self::clip( $parts[0] ) );
				}
			}

			$qty = (string) end( $parts );
			if ( ! ctype_digit( $qty ) || 0 === (int) $qty ) {
				return __( 'Item quantity must be a positive integer.', 'woocommerce' );
			}
			$totals[ $item->get_id() ] = ( $totals[ $item->get_id() ] ?? 0 ) + (int) $qty;
		}

		if ( array() === $totals ) {
			return __( 'The items cell has no item entries.', 'woocommerce' );
		}

		$items = array();
		foreach ( $totals as $item_id => $qty ) {
			$left = $pending[ $item_id ] ?? 0;
			if ( $qty > $left ) {
				/* translators: 1: quantity from the CSV, 2: quantity of the item not fulfilled yet. */
				return sprintf( __( 'Item quantity %1$d exceeds the quantity left to fulfill %2$d.', 'woocommerce' ), $qty, $left );
			}
			$items[] = array(
				'item_id' => (int) $item_id,
				'qty'     => $qty,
			);
		}
		return $items;
	}

	/**
	 * Index line items by lowercase SKU.
	 *
	 * @param array<int|string, \WC_Order_Item> $line_items Line items of the order.
	 * @return array<string, \WC_Order_Item|false> False for a SKU shared by more than one line item.
	 */
	private function index_items_by_sku( array $line_items ): array {
		$by_sku = array();
		foreach ( $line_items as $item ) {
			$product = $item instanceof \WC_Order_Item_Product ? $item->get_product() : null;
			$sku     = $product instanceof \WC_Product ? strtolower( (string) $product->get_sku() ) : '';
			if ( '' !== $sku ) {
				$by_sku[ $sku ] = isset( $by_sku[ $sku ] ) ? false : $item;
			}
		}
		return $by_sku;
	}

	/**
	 * Quantities left to fulfill per order item, after refunds and the order's other fulfillments.
	 *
	 * @param WC_Order         $order    Order of the row.
	 * @param Fulfillment|null $existing Fulfillment the row updates; its own items count as not fulfilled.
	 * @return array<int, int> Order item ID => quantity.
	 */
	private function pending_items( WC_Order $order, ?Fulfillment $existing ): array {
		$others = array_filter(
			$this->fulfillments_cache[ $order->get_id() ] ?? array(),
			static function ( $fulfillment ) use ( $existing ) {
				return $fulfillment !== $existing;
			}
		);
		return array_map( 'intval', array_column( FulfillmentUtils::get_pending_items( $order, $others ), 'qty', 'item_id' ) );
	}

	/**
	 * Read one window of data rows from a handle already past its BOM.
	 *
	 * @param resource             $handle      Open handle.
	 * @param string               $delimiter   Delimiter passed to fgetcsv().
	 * @param int                  $offset      Real data rows to skip.
	 * @param int                  $limit       Maximum real data rows to return.
	 * @param int                  $byte_offset Resume position, or 0 to start after the header.
	 * @param array<string, mixed> $options     Resume options. Uses record_number when $byte_offset is set.
	 * @return array<string, mixed>
	 */
	private function read_data_window( $handle, string $delimiter, int $offset, int $limit, int $byte_offset, array $options ): array {
		$this->conversion_failed = false;
		$records                 = $this->read_records( $handle, $delimiter, 1 );
		$header                  = $this->next_data_record( $records );
		if ( null === $header ) {
			return $this->error( 'empty_csv', __( 'CSV file is empty.', 'woocommerce' ) );
		}

		$last_row_number = (int) $header['row_number'];
		$width           = count( $header['cells'] );

		if ( $byte_offset > 0 ) {
			// Drop the header generator before seeking so it cannot read again from the new position.
			unset( $records );
			if ( 0 !== fseek( $handle, $byte_offset ) ) {
				return $this->error( 'byte_offset_invalid', __( 'Could not resume reading the CSV file.', 'woocommerce' ) );
			}
			$record_number   = isset( $options['record_number'] ) ? max( 1, (int) $options['record_number'] ) : 1;
			$records         = $this->read_records( $handle, $delimiter, $record_number );
			$last_row_number = $record_number - 1;
		}

		$skipped            = 0;
		$eof                = false;
		$position           = 0;
		$next_record_number = $last_row_number + 1;
		while ( $skipped < $offset ) {
			$record = $this->next_data_record( $records, $last_row_number );
			if ( null === $record ) {
				$eof = true;
				break;
			}
			++$skipped;
		}

		$rows     = array();
		$consumed = 0;
		if ( ! $eof ) {
			while ( $consumed < $limit ) {
				$record = $this->next_data_record( $records, $last_row_number );
				if ( null === $record ) {
					$eof = true;
					break;
				}
				$rows[] = array(
					'row_number' => (int) $record['row_number'],
					'cells'      => $this->pad_row( $record['cells'], $width ),
				);
				++$consumed;
			}
		}

		if ( ! $eof ) {
			$upcoming = $this->next_data_record( $records, $last_row_number );
			if ( null === $upcoming ) {
				$eof = true;
			} else {
				$position           = (int) $upcoming['start'];
				$next_record_number = (int) $upcoming['row_number'];
			}
		}

		if ( $eof ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_ftell -- Position of the handle opened above.
			$end_position       = ftell( $handle );
			$position           = false === $end_position ? 0 : (int) $end_position;
			$next_record_number = $last_row_number + 1;
		}

		return array(
			'headers'            => $header['cells'],
			'header_row_number'  => (int) $header['row_number'],
			'rows'               => $rows,
			'byte_offset'        => $position,
			'next_record_number' => $next_record_number,
			'consumed'           => $consumed,
			'skipped'            => $skipped,
			'eof'                => $eof,
			'delimiter'          => $delimiter,
		);
	}

	/**
	 * Build an error result.
	 *
	 * @param string $code    Machine-readable error code.
	 * @param string $message Message safe to show to the merchant.
	 * @return array{error: array{code:string, message:string}}
	 */
	private function error( string $code, string $message ): array {
		return array(
			'error' => array(
				'code'    => $code,
				'message' => $message,
			),
		);
	}

	/**
	 * Open the CSV and reject a UTF-16 or CR-only file.
	 *
	 * The handle is left at byte 0. The caller strips a BOM.
	 *
	 * @param string $delimiter_override Delimiter override. Empty uses the constructor delimiter.
	 * @return array{handle: resource, delimiter: string}|array{error: array{code:string, message:string}}
	 */
	private function open_csv( string $delimiter_override ): array {
		$delimiter = '' === $delimiter_override ? $this->options['delimiter'] : self::normalize_delimiter( $delimiter_override );

		if ( ! is_readable( $this->file ) ) {
			return $this->error( 'file_not_readable', __( 'File is not readable.', 'woocommerce' ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Streaming a staged local CSV; WP_Filesystem has no line reader.
		$handle = fopen( $this->file, 'rb' );
		if ( false === $handle ) {
			return $this->error( 'file_open_failed', __( 'Could not open file.', 'woocommerce' ) );
		}

		if ( $this->is_utf16( $handle ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing the handle opened above.
			fclose( $handle );
			// Excel's "Unicode Text" export. fgetcsv() would return cells with a NUL after every character.
			return $this->error(
				'utf16_encoding',
				__( 'This file is saved as UTF-16 and cannot be read. Re-save it as a UTF-8 CSV and try again.', 'woocommerce' )
			);
		}

		if ( $this->is_cr_only( $handle ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing the handle opened above.
			fclose( $handle );
			// fgetcsv() only splits on LF. auto_detect_line_endings is deprecated, so a CR-only file would be one record.
			return $this->error(
				'cr_line_endings',
				__( 'This file uses old Mac line endings and cannot be read. Re-save it as CSV and try again.', 'woocommerce' )
			);
		}

		rewind( $handle );
		return array(
			'handle'    => $handle,
			'delimiter' => $delimiter,
		);
	}

	/**
	 * Whether the file is UTF-16, judged from its first bytes.
	 *
	 * A UTF-16 BOM, or a NUL byte anywhere in the first chunk, is enough: a UTF-8
	 * CSV never contains NUL, while UTF-16 text has one after every ASCII character.
	 *
	 * @param resource $handle Open file handle.
	 * @return bool
	 */
	private function is_utf16( $handle ): bool {
		rewind( $handle );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Reading the handle opened by the caller.
		$chunk = fread( $handle, self::INSPECT_CHUNK_BYTES );
		if ( ! is_string( $chunk ) || '' === $chunk ) {
			return false;
		}
		$bom = substr( $chunk, 0, 2 );
		return "\xFF\xFE" === $bom || "\xFE\xFF" === $bom || false !== strpos( $chunk, "\0" );
	}

	/**
	 * Whether the file uses CR line endings and no LF.
	 *
	 * Stops at the first LF, so a normal CSV is not read to the end.
	 *
	 * @param resource $handle Open file handle.
	 * @return bool
	 */
	private function is_cr_only( $handle ): bool {
		$saw_cr = false;
		rewind( $handle );
		while ( ! feof( $handle ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Reading the handle opened by the caller.
			$chunk = fread( $handle, self::INSPECT_CHUNK_BYTES );
			if ( ! is_string( $chunk ) || '' === $chunk ) {
				break;
			}
			if ( false !== strpos( $chunk, "\n" ) ) {
				return false;
			}
			if ( false !== strpos( $chunk, "\r" ) ) {
				$saw_cr = true;
			}
		}
		return $saw_cr;
	}

	/**
	 * Read the header and count data rows, stopping once the cap is decided.
	 *
	 * The count is incremented before the cap check. After MAX_IMPORT_ROWS + 1
	 * data rows, one further data row sets total_capped and the read stops.
	 * Exactly that many data rows, or that many followed only by blanks, leaves
	 * total_capped false.
	 *
	 * @param resource $handle    Open handle positioned after a BOM strip.
	 * @param string   $delimiter Delimiter passed to fgetcsv().
	 * @return array{error: array{code:string, message:string}}|array{headers: array<int, string>, sample: array<int, string>, total: int, total_capped: bool}
	 */
	private function read_header_summary( $handle, string $delimiter ): array {
		$this->conversion_failed = false;
		$records                 = $this->read_records( $handle, $delimiter, 1 );
		$header                  = $this->next_data_record( $records );
		if ( null === $header ) {
			return $this->error( 'empty_csv', __( 'CSV file is empty.', 'woocommerce' ) );
		}

		$sample       = array();
		$total        = 0;
		$total_capped = false;
		while ( true ) {
			$record = $this->next_data_record( $records );
			if ( null === $record ) {
				break;
			}
			++$total;
			if ( 1 === $total ) {
				$sample = $this->pad_row( $record['cells'], count( $header['cells'] ) );
			}
			if ( $total > self::MAX_IMPORT_ROWS ) {
				$extra        = $this->next_data_record( $records );
				$total_capped = null !== $extra;
				break;
			}
		}

		return array(
			'headers'      => $header['cells'],
			'sample'       => $sample,
			'total'        => $total,
			'total_capped' => $total_capped,
		);
	}

	/**
	 * Error for text that is not UTF-8 and cannot be converted.
	 *
	 * @return array{error: array{code:string, message:string}}
	 */
	private function unsupported_encoding_error(): array {
		return $this->error(
			'unsupported_encoding',
			__( 'This file is not valid UTF-8 and could not be converted. Save it as a UTF-8 CSV and try again.', 'woocommerce' )
		);
	}

	/**
	 * Yield each physical CSV record, with blank rows flagged and cells decoded.
	 *
	 * @param resource $handle             Open file handle positioned at the first record to read.
	 * @param string   $delimiter          Delimiter passed to fgetcsv().
	 * @param int      $next_record_number Physical record number to assign to the first record read.
	 * @return \Generator<int, array{row_number:int, cells:array<int, string>, blank:bool, start:int}>
	 */
	private function read_records( $handle, string $delimiter, int $next_record_number ): \Generator {
		while ( true ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_ftell -- Position of the handle opened by the caller.
			$start = ftell( $handle );
			if ( false === $start ) {
				break;
			}

			$record = fgetcsv( $handle, 0, $delimiter, $this->options['enclosure'], '' );
			if ( ! is_array( $record ) ) {
				break;
			}

			$cells = $this->normalize_cells( $record );
			yield array(
				'row_number' => $next_record_number,
				'cells'      => $cells,
				'blank'      => $this->is_blank_row( $cells ),
				'start'      => (int) $start,
			);
			++$next_record_number;
		}
	}

	/**
	 * Return the next non-blank record, moving the reader past any blanks in between.
	 *
	 * @param \Generator $records         Record generator from read_records().
	 * @param int|null   $last_row_number Set to the physical number of the last record pulled, including blanks.
	 * @return array{row_number:int, cells:array<int, string>, blank:bool, start:int}|null
	 */
	private function next_data_record( \Generator $records, &$last_row_number = null ): ?array {
		// Advance with next() rather than foreach. A second foreach rewinds, and rewind()
		// either repeats the first record or throws once the generator has moved on.
		while ( $records->valid() ) {
			$record = $records->current();
			$records->next();
			$last_row_number = $record['row_number'];
			if ( ! $record['blank'] ) {
				return $record;
			}
		}
		return null;
	}

	/**
	 * Turn one fgetcsv() record into trimmed UTF-8 strings.
	 *
	 * Leading and trailing ASCII whitespace is removed from every cell, so "1042, 1Z999, ups"
	 * yields the same values as "1042,1Z999,ups" and a tracking number or carrier slug
	 * never carries a stray space into a URL or a lookup.
	 *
	 * Each cell is judged on its own: valid UTF-8 is kept, anything else is read as
	 * Windows-1252. A Windows-1252 cell whose high bytes happen to form valid UTF-8,
	 * such as a literal "Ã©", is kept as UTF-8; that is rarer than a file that mixes
	 * both encodings.
	 *
	 * @param array<int, string|null> $record Raw record.
	 * @return array<int, string>
	 */
	private function normalize_cells( array $record ): array {
		$cells = array();
		foreach ( $record as $value ) {
			$cell = is_scalar( $value ) ? trim( (string) $value ) : '';
			if ( '' !== $cell && ! self::is_utf8( $cell ) ) {
				$converted = $this->convert_windows_1252( $cell );
				if ( null === $converted ) {
					$this->conversion_failed = true;
					$cell                    = '';
				} else {
					$cell = $converted;
				}
			}
			$cells[] = $cell;
		}
		return $cells;
	}

	/**
	 * Pad a data row with empty strings up to the header width.
	 *
	 * A line with fewer fields than the header comes back from fgetcsv() as a shorter
	 * record, which would leave a mapped index undefined. Extra cells on a longer row are kept.
	 *
	 * @param array<int, string> $cells Decoded row.
	 * @param int                $width Number of header cells.
	 * @return array<int, string>
	 */
	private function pad_row( array $cells, int $width ): array {
		return count( $cells ) < $width ? array_pad( $cells, $width, '' ) : $cells;
	}

	/**
	 * Whether a string is well-formed UTF-8.
	 *
	 * Both checks reject overlong sequences, surrogate halves and stray high bytes.
	 *
	 * @param string $value Bytes to check.
	 * @return bool
	 */
	private static function is_utf8( string $value ): bool {
		if ( function_exists( 'mb_check_encoding' ) ) {
			return mb_check_encoding( $value, 'UTF-8' );
		}
		return 1 === preg_match( '//u', $value );
	}

	/**
	 * Convert one Windows-1252 cell to UTF-8.
	 *
	 * ASCII bytes are unchanged. mbstring is used when it is available, otherwise
	 * iconv. Null means neither converter could produce UTF-8.
	 *
	 * @param string $value Cell text in Windows-1252.
	 * @return string|null
	 */
	private function convert_windows_1252( string $value ): ?string {
		if ( '' === $value ) {
			return '';
		}
		if ( function_exists( 'mb_convert_encoding' ) ) {
			$converted = mb_convert_encoding( $value, 'UTF-8', 'Windows-1252' );
			if ( is_string( $converted ) ) {
				return $converted;
			}
		}
		if ( function_exists( 'iconv' ) ) {
			$converted = iconv( 'Windows-1252', 'UTF-8', $value );
			if ( is_string( $converted ) ) {
				return $converted;
			}
		}
		return null;
	}

	/**
	 * Skip a leading UTF-8 BOM, rewinding if not present.
	 *
	 * @param resource $handle Open file handle positioned at byte 0.
	 */
	private function strip_bom( $handle ): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Reading 3 bytes from an already-open handle.
		$bom = fread( $handle, 3 );
		if ( "\xEF\xBB\xBF" !== $bom ) {
			rewind( $handle );
		}
	}

	/**
	 * Invert a column-index-keyed mapping into a canonical-keyed header map.
	 *
	 * @since 11.4.0
	 *
	 * @param array<int, string> $mapping CSV column index => canonical column key. Unmapped slots may be "".
	 * @return array<string, int> Canonical column key => CSV column index. First wins on duplicates.
	 */
	public static function mapping_to_header_map( array $mapping ): array {
		$header_map = array();
		foreach ( $mapping as $col_index => $canonical ) {
			$canonical = is_string( $canonical ) ? trim( $canonical ) : '';
			if ( '' === $canonical ) {
				continue;
			}
			$index = (int) $col_index;
			if ( ! isset( $header_map[ $canonical ] ) ) {
				$header_map[ $canonical ] = $index;
			}
		}
		return $header_map;
	}

	/**
	 * Map normalized header values to column indexes.
	 *
	 * @param array<int, string|null> $header Raw header row.
	 * @return array<string, int> Map of canonical key => column index.
	 */
	private function build_header_map( array $header ): array {
		$aliases = $this->get_column_aliases();
		$map     = array();

		foreach ( $header as $index => $name ) {
			$normalized = $this->normalize_header( (string) $name );
			if ( '' === $normalized ) {
				continue;
			}
			foreach ( $aliases as $canonical => $alias_list ) {
				if ( in_array( $normalized, $alias_list, true ) && ! isset( $map[ $canonical ] ) ) {
					$map[ $canonical ] = $index;
					break;
				}
			}
		}

		return $map;
	}

	/**
	 * Determine which required columns are missing.
	 *
	 * @since 11.4.0
	 *
	 * @param array<string, int> $header_map Header map.
	 * @return array<int, string> Human-friendly names of missing columns.
	 */
	public static function find_missing_required_columns( array $header_map ): array {
		// Column keys are the literal header values merchants must put in their CSV; do not translate.
		$required = array(
			self::COL_ORDER_NUMBER    => self::COL_ORDER_NUMBER,
			self::COL_TRACKING_NUMBER => self::COL_TRACKING_NUMBER,
			self::COL_PROVIDER        => self::COL_PROVIDER,
		);
		$missing  = array();
		foreach ( $required as $key => $label ) {
			if ( ! isset( $header_map[ $key ] ) ) {
				$missing[] = $label;
			}
		}
		return $missing;
	}

	/**
	 * Normalize a header cell to a lowercase snake-case string for fuzzy matching.
	 *
	 * @param string $name Raw header.
	 * @return string
	 */
	private function normalize_header( string $name ): string {
		$name = strtolower( trim( $name ) );
		$name = preg_replace( '/[^a-z0-9]+/', '_', $name );
		return trim( (string) $name, '_' );
	}

	/**
	 * Aliases recognized for each canonical column.
	 *
	 * @return array<string, array<int, string>>
	 */
	private function get_column_aliases(): array {
		$defaults = array(
			self::COL_ORDER_NUMBER    => array( 'order_number', 'order', 'order_id', 'order_no', 'order_num' ),
			self::COL_TRACKING_NUMBER => array( 'tracking_number', 'tracking', 'tracking_no', 'tracking_num' ),
			self::COL_PROVIDER        => array( 'shipment_provider', 'provider', 'carrier', 'shipping_provider', 'shipping_carrier' ),
			self::COL_TRACKING_URL    => array( 'tracking_url', 'url' ),
			self::COL_ITEMS           => array( 'items', 'line_items' ),
		);

		/**
		 * Filter the header aliases recognized by the fulfillments CSV importer.
		 *
		 * Lets stores accept additional header names from third-party WMS/3PL exports.
		 * Keys are canonical column identifiers; values are arrays of accepted aliases.
		 * Aliases are normalized the same way as header cells (lowercase, non-alphanumeric
		 * runs collapsed to "_"), so "Order No." and "order_no" are the same alias. Only the
		 * importer's own canonical keys are honored: the wizard works from that fixed set,
		 * so a new key here would be detected and then rejected.
		 *
		 * @since 11.4.0
		 *
		 * @param array<string, array<int, string>> $aliases Default alias map.
		 */
		$aliases = apply_filters( 'woocommerce_fulfillments_csv_importer_column_aliases', $defaults );

		// Any callback can return anything; drop malformed entries instead of
		// letting a non-array alias list fatal in build_header_map().
		if ( ! is_array( $aliases ) ) {
			return $defaults;
		}

		$canonical_columns = self::canonical_columns();
		$sanitized         = array();
		foreach ( $aliases as $canonical => $alias_list ) {
			if ( ! is_string( $canonical ) || ! in_array( $canonical, $canonical_columns, true ) || ! is_array( $alias_list ) ) {
				continue;
			}
			$clean = array();
			foreach ( $alias_list as $alias ) {
				$normalized = is_string( $alias ) ? $this->normalize_header( $alias ) : '';
				if ( '' !== $normalized ) {
					$clean[] = $normalized;
				}
			}
			if ( array() !== $clean ) {
				$sanitized[ $canonical ] = $clean;
			}
		}

		// A filter that leaves nothing usable would make every header undetectable.
		return array() === $sanitized ? $defaults : $sanitized;
	}

	/**
	 * Whether every cell in a decoded row is empty.
	 *
	 * Cells arrive trimmed from normalize_cells(), so a blank line, a row of empty
	 * fields such as ",,,", and a whitespace-only row are all blank. A cell holding
	 * "0" is not.
	 *
	 * @param array<int, string> $row Decoded row.
	 * @return bool
	 */
	private function is_blank_row( array $row ): bool {
		foreach ( $row as $cell ) {
			if ( '' !== $cell ) {
				return false;
			}
		}
		return true;
	}
}
