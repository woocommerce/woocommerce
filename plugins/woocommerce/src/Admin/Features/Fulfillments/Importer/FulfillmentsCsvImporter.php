<?php
/**
 * Fulfillments CSV importer service.
 *
 * @package Automattic\WooCommerce\Admin\Features\Fulfillments\Importer
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Features\Fulfillments\Importer;

defined( 'ABSPATH' ) || exit;

/**
 * Reads a fulfillments CSV for the import wizard and for the chunked import run.
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
	 * Maximum number of data rows accepted per file.
	 *
	 * The cap bounds the time the prepare request spends counting rows and the
	 * size of the session transient. Larger files must be split and imported in parts.
	 */
	public const MAX_IMPORT_ROWS = 5000;

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
	 *                                      enclosure; the other two are kept for the import run.
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
