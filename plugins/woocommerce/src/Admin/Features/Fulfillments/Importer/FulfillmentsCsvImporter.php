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
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsTracker;
use WC_Data_Store;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Parses a CSV file of fulfillment rows and creates/updates fulfillment records.
 *
 * @since 11.2.0
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
	 * Per-run cache of resolved orders, keyed by the raw order number cell from the CSV.
	 * Numeric keys appear when PHP canonicalizes numeric strings.
	 *
	 * @var array<int|string, WC_Order|null>
	 */
	private array $order_cache = array();

	/**
	 * Orders loaded in one batch for the current chunk, keyed by order ID.
	 *
	 * Kept apart from the resolution cache so priming stays a lookup shortcut and never
	 * skips the woocommerce_fulfillments_csv_importer_resolve_order filter.
	 *
	 * @var array<int, WC_Order>
	 */
	private array $primed_orders = array();

	/**
	 * Per-run cache of non-deleted fulfillments keyed by order ID.
	 *
	 * @var array<int, array<int, Fulfillment>>
	 */
	private array $fulfillments_cache = array();

	/**
	 * Validation-error codes seen since the last Tracks flush.
	 *
	 * @var array<string, int>
	 */
	private array $pending_validation_errors = array();

	/**
	 * Default chunk size when looping import_chunk().
	 */
	public const DEFAULT_CHUNK_SIZE = 200;

	/**
	 * Hard ceiling enforced on the filtered chunk size, regardless of what callers request.
	 */
	public const MAX_CHUNK_SIZE = 1000;

	/**
	 * Chunk ceiling when customer notifications are on. Each notified row sends mail
	 * synchronously, so large chunks would run past typical execution time limits.
	 */
	public const NOTIFY_CHUNK_SIZE = 25;

	/**
	 * Maximum number of CSV records accepted per import.
	 *
	 * The cross-chunk dedupe set is serialized into the session transient and
	 * rewritten on every chunk, so the row count must stay bounded. Larger files
	 * should be split and imported in parts.
	 */
	public const MAX_IMPORT_ROWS = 5000;

	/**
	 * Constructor.
	 *
	 * @since 11.2.0
	 *
	 * @param string               $file    Absolute path to the CSV file.
	 * @param array<string, mixed> $options Importer options:
	 *                                      - notify_customer (bool): Whether to fire customer notifications. Default false.
	 *                                      - delimiter (string): Single-character CSV delimiter. Default ','.
	 *                                      - enclosure (string): CSV enclosure. Default '"'.
	 *                                      - update_existing (bool): Update fulfillment when one with the same tracking number
	 *                                                                already exists on the order. Default true.
	 */
	public function __construct( string $file, array $options = array() ) {
		$this->file    = $file;
		$this->options = array(
			'notify_customer' => ! empty( $options['notify_customer'] ),
			'delimiter'       => self::normalize_delimiter( $options['delimiter'] ?? ',' ),
			'enclosure'       => isset( $options['enclosure'] ) && '' !== $options['enclosure'] ? (string) $options['enclosure'] : '"',
			'update_existing' => array_key_exists( 'update_existing', $options ) ? (bool) $options['update_existing'] : true,
		);
	}

	/**
	 * Normalize a delimiter input, falling back to ',' when empty or non-string.
	 *
	 * @since 11.2.0
	 *
	 * @param mixed $delimiter Raw delimiter input.
	 * @return string Delimiter string (defaults to ',').
	 */
	public static function normalize_delimiter( $delimiter ): string {
		if ( ! is_string( $delimiter ) || '' === $delimiter ) {
			return ',';
		}
		// substr() slices by byte; the first byte of a multibyte character is a
		// malformed fragment that would make fgetcsv() silently mis-parse the file,
		// so anything outside the ASCII range falls back to the default.
		$first = substr( $delimiter, 0, 1 );
		return ord( $first ) < 0x80 ? $first : ',';
	}

	/**
	 * Parse the CSV header row and return metadata sufficient to drive the column-mapping UI.
	 *
	 * Streams through the file once to count remaining rows and capture a single sample row.
	 * Does not fail when required canonical columns cannot be auto-detected; the caller can
	 * present the mapping UI so the user resolves it manually.
	 *
	 * @since 11.2.0
	 *
	 * @param string $delimiter Delimiter override; falls back to the constructor delimiter when empty.
	 * @return array{
	 *     headers?: array<int, string>,
	 *     sample?: array<int, string>,
	 *     total?: int,
	 *     detected_mapping?: array<int, string>,
	 *     delimiter?: string,
	 *     error?: array{code:string, message:string}
	 * }
	 */
	public function parse_headers( string $delimiter = '' ): array {
		if ( ! is_readable( $this->file ) ) {
			return array(
				'error' => array(
					'code'    => 'file_not_readable',
					'message' => __( 'File is not readable.', 'woocommerce' ),
				),
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Streaming a staged local CSV; WP_Filesystem has no line reader.
		$handle = fopen( $this->file, 'rb' );
		if ( false === $handle ) {
			return array(
				'error' => array(
					'code'    => 'file_open_failed',
					'message' => __( 'Could not open file.', 'woocommerce' ),
				),
			);
		}

		try {
			$this->strip_bom( $handle );

			$effective_delimiter = '' === $delimiter ? $this->options['delimiter'] : self::normalize_delimiter( $delimiter );

			$header_raw = fgetcsv( $handle, 0, $effective_delimiter, $this->options['enclosure'], '' );
			if ( false === $header_raw || null === $header_raw ) {
				return array(
					'error' => array(
						'code'    => 'empty_csv',
						'message' => __( 'CSV file is empty.', 'woocommerce' ),
					),
				);
			}

			$headers = array();
			foreach ( $header_raw as $value ) {
				$headers[] = is_scalar( $value ) ? (string) $value : '';
			}

			$header_map       = $this->build_header_map( $header_raw );
			$detected_mapping = array();
			foreach ( $header_map as $canonical => $col_index ) {
				$detected_mapping[ (int) $col_index ] = (string) $canonical;
			}

			$sample = array();
			$total  = 0;
			while ( true ) {
				$row = fgetcsv( $handle, 0, $effective_delimiter, $this->options['enclosure'], '' );
				if ( false === $row || null === $row ) {
					break;
				}
				++$total;
				if ( empty( $sample ) && ! $this->is_blank_row( $row ) ) {
					foreach ( $row as $value ) {
						$sample[] = is_scalar( $value ) ? (string) $value : '';
					}
				}
				// One row past the cap is enough for the caller to reject the file; counting
				// the rest of a very large upload only burns request time.
				if ( $total > self::MAX_IMPORT_ROWS ) {
					break;
				}
			}

			return array(
				'headers'          => $headers,
				'sample'           => $sample,
				'total'            => $total,
				'detected_mapping' => $detected_mapping,
				'delimiter'        => $effective_delimiter,
			);
		} finally {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing the handle opened above.
			fclose( $handle );
		}
	}

	/**
	 * Process a contiguous slice of CSV rows.
	 *
	 * @since 11.2.0
	 *
	 * @param int                  $offset  0-based row offset to start at (header is implicitly skipped).
	 * @param int                  $limit   Maximum number of CSV records to consume from the slice.
	 * @param array<int, string>   $mapping CSV column index => canonical column key. Unmapped columns
	 *                                      may be omitted or set to "".
	 * @param array<string, mixed> $options {
	 *     Chunk state; behavior options come from the constructor.
	 *
	 *     @type array<string, true>    $seen_tracking_pairs Cross-chunk dedupe state; pass back in to subsequent calls.
	 *     @type int                    $byte_offset         Byte position from a prior chunk's result to fseek to instead
	 *                                                       of forward-reading rows. When the seek succeeds, the header
	 *                                                       read and the row fast-forward are both skipped; mapping is
	 *                                                       still validated.
	 * }
	 * @return array{
	 *     counts: array{created:int, updated:int, skipped:int, failed:int, notified:int},
	 *     rows: array<int, array<string, mixed>>,
	 *     seen_tracking_pairs: array<string, true>,
	 *     byte_offset: int,
	 *     consumed: int,
	 *     eof: bool,
	 *     aborted: bool
	 * }
	 */
	public function import_chunk( int $offset, int $limit, array $mapping, array $options = array() ): array {
		$delimiter = $this->options['delimiter'];

		$seen_tracking_pairs = isset( $options['seen_tracking_pairs'] ) && is_array( $options['seen_tracking_pairs'] )
			? $options['seen_tracking_pairs']
			: array();

		$byte_offset_in = isset( $options['byte_offset'] ) ? max( 0, (int) $options['byte_offset'] ) : 0;

		$counts = array(
			'created'  => 0,
			'updated'  => 0,
			'skipped'  => 0,
			'failed'   => 0,
			'notified' => 0,
		);
		$rows   = array();

		try {
			if ( $limit <= 0 ) {
				return array(
					'counts'              => $counts,
					'rows'                => $rows,
					'seen_tracking_pairs' => $seen_tracking_pairs,
					'byte_offset'         => $byte_offset_in,
					'consumed'            => 0,
					'eof'                 => false,
					'aborted'             => false,
				);
			}

			if ( ! is_readable( $this->file ) ) {
				$rows[] = $this->fail( 0, 'file_not_readable', __( 'File is not readable.', 'woocommerce' ) );
				++$counts['failed'];
				return array(
					'counts'              => $counts,
					'rows'                => $rows,
					'seen_tracking_pairs' => $seen_tracking_pairs,
					'byte_offset'         => $byte_offset_in,
					'consumed'            => 0,
					'eof'                 => false,
					'aborted'             => true,
				);
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Streaming a staged local CSV; WP_Filesystem has no line reader.
			$handle = fopen( $this->file, 'rb' );
			if ( false === $handle ) {
				$rows[] = $this->fail( 0, 'file_open_failed', __( 'Could not open file.', 'woocommerce' ) );
				++$counts['failed'];
				return array(
					'counts'              => $counts,
					'rows'                => $rows,
					'seen_tracking_pairs' => $seen_tracking_pairs,
					'byte_offset'         => $byte_offset_in,
					'consumed'            => 0,
					'eof'                 => false,
					'aborted'             => true,
				);
			}

			$byte_offset_out = $byte_offset_in;

			try {
				$header_map = self::mapping_to_header_map( $mapping );
				$missing    = self::find_missing_required_columns( $header_map );
				$row_number = $offset + 1;
				$resumed    = false;

				if ( $byte_offset_in > 0 && 0 === fseek( $handle, $byte_offset_in ) ) {
					// Resuming from a prior chunk: header and earlier rows are already past.
					$resumed = true;
				} else {
					$this->strip_bom( $handle );

					$header_raw = fgetcsv( $handle, 0, $delimiter, $this->options['enclosure'], '' );
					if ( false === $header_raw || null === $header_raw ) {
						$rows[] = $this->fail( 0, 'empty_csv', __( 'CSV file is empty.', 'woocommerce' ) );
						++$counts['failed'];
						return array(
							'counts'              => $counts,
							'rows'                => $rows,
							'seen_tracking_pairs' => $seen_tracking_pairs,
							'byte_offset'         => $byte_offset_in,
							'consumed'            => 0,
							'eof'                 => false,
							'aborted'             => true,
						);
					}
				}

				if ( ! empty( $missing ) ) {
					$rows[] = $this->fail(
						0,
						'missing_required_columns',
						sprintf(
							/* translators: %s: comma-separated list of missing column names. */
							__( 'CSV is missing required column(s): %s.', 'woocommerce' ),
							implode( ', ', $missing )
						)
					);
					++$counts['failed'];
					return array(
						'counts'              => $counts,
						'rows'                => $rows,
						'seen_tracking_pairs' => $seen_tracking_pairs,
						'byte_offset'         => $byte_offset_in,
						'consumed'            => 0,
						'eof'                 => false,
						'aborted'             => true,
					);
				}

				if ( ! $resumed ) {
					// No byte offset available, so fast-forward past $offset records after the header.
					$row_number = 1;
					for ( $i = 0; $i < $offset; $i++ ) {
						$row = fgetcsv( $handle, 0, $delimiter, $this->options['enclosure'], '' );
						if ( false === $row || null === $row ) {
							$position = ftell( $handle );
							return array(
								'counts'              => $counts,
								'rows'                => $rows,
								'seen_tracking_pairs' => $seen_tracking_pairs,
								'byte_offset'         => false === $position ? $byte_offset_in : (int) $position,
								'consumed'            => 0,
								'eof'                 => true,
								'aborted'             => false,
							);
						}
						++$row_number;
					}
				}

				$consumed    = 0;
				$reached_eof = false;
				$batch       = array();
				while ( $consumed < $limit ) {
					$row = fgetcsv( $handle, 0, $delimiter, $this->options['enclosure'], '' );
					if ( false === $row || null === $row ) {
						$reached_eof = true;
						break;
					}
					++$row_number;
					++$consumed;
					$batch[] = array(
						'row_number' => $row_number,
						'row'        => $row,
						'blank'      => $this->is_blank_row( $row ),
					);
				}

				// A fresh importer is constructed per chunked REST request, so the per-row order
				// cache starts cold each chunk. Load the chunk's orders in one batch instead.
				$this->prime_chunk_caches( $batch, $header_map );

				foreach ( $batch as $entry ) {
					if ( $entry['blank'] ) {
						continue;
					}

					$result = $this->process_row( $entry['row'], $header_map, $entry['row_number'], $seen_tracking_pairs );

					if ( isset( $result['status'] ) ) {
						switch ( $result['status'] ) {
							case 'created':
								++$counts['created'];
								break;
							case 'updated':
								++$counts['updated'];
								break;
							case 'skipped':
								++$counts['skipped'];
								break;
							case 'failed':
							default:
								++$counts['failed'];
								break;
						}

						if ( ! empty( $result['notified'] ) ) {
							++$counts['notified'];
						}
					}

					$rows[] = $result;
				}

				$position = ftell( $handle );
				if ( false !== $position ) {
					$byte_offset_out = (int) $position;
				}
			} finally {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing the handle opened above.
				fclose( $handle );
			}

			return array(
				'counts'              => $counts,
				'rows'                => $rows,
				'seen_tracking_pairs' => $seen_tracking_pairs,
				'byte_offset'         => $byte_offset_out,
				'consumed'            => $consumed,
				'eof'                 => $reached_eof,
				'aborted'             => false,
			);
		} finally {
			$this->flush_validation_error_events();
		}
	}

	/**
	 * Fire one validation-error Tracks event per distinct code seen since the last flush.
	 *
	 * A malformed file can fail thousands of rows in one chunk; per-row events would
	 * flood Tracks with identical payloads.
	 */
	private function flush_validation_error_events(): void {
		foreach ( array_keys( $this->pending_validation_errors ) as $code ) {
			FulfillmentsTracker::track_fulfillment_validation_error( 'import', (string) $code, 'csv_importer' );
		}
		$this->pending_validation_errors = array();
	}

	/**
	 * Resolve the filtered chunk size. Falls back to the default when the filter returns
	 * less than 1, and caps the result at MAX_CHUNK_SIZE.
	 *
	 * @since 11.2.0
	 *
	 * @return int
	 */
	public static function resolve_chunk_size(): int {
		/**
		 * Filter the chunk size used when looping CSV rows through the importer.
		 *
		 * Used by the wizard's per-chunk REST handler. The server enforces sane
		 * bounds regardless of what callers send.
		 *
		 * @since 11.2.0
		 *
		 * @param int $chunk_size Default chunk size (200).
		 */
		$size = (int) apply_filters( 'woocommerce_fulfillments_csv_importer_chunk_size', self::DEFAULT_CHUNK_SIZE );
		if ( $size < 1 ) {
			$size = self::DEFAULT_CHUNK_SIZE;
		}
		return min( $size, self::MAX_CHUNK_SIZE );
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
	 * Invert a column-index-keyed mapping into the canonical-keyed header map used by process_row().
	 *
	 * @since 11.2.0
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
	 * Process a single CSV row. Every result carries the raw order number from
	 * the CSV so the report can name the order even when it was not found.
	 *
	 * @param array<int, string|null> $row              Raw CSV row.
	 * @param array<string, int>      $header_map           Map of canonical column key => CSV column index.
	 * @param int                     $row_number           1-based row number (header is row 1).
	 * @param array<string, true>     $seen_tracking_pairs  Reference to in-file dedupe tracker.
	 *
	 * @return array<string, mixed>
	 */
	private function process_row( array $row, array $header_map, int $row_number, array &$seen_tracking_pairs ): array {
		$result                 = $this->import_row( $row, $header_map, $row_number, $seen_tracking_pairs );
		$result['order_number'] = $this->get_field( $row, $header_map, self::COL_ORDER_NUMBER );
		return $result;
	}

	/**
	 * Validate and import a single CSV row.
	 *
	 * @param array<int, string|null> $row              Raw CSV row.
	 * @param array<string, int>      $header_map           Map of canonical column key => CSV column index.
	 * @param int                     $row_number           1-based row number (header is row 1).
	 * @param array<string, true>     $seen_tracking_pairs  Reference to in-file dedupe tracker.
	 *
	 * @return array<string, mixed>
	 */
	private function import_row( array $row, array $header_map, int $row_number, array &$seen_tracking_pairs ): array {
		$order_number    = $this->get_field( $row, $header_map, self::COL_ORDER_NUMBER );
		$tracking_number = $this->get_field( $row, $header_map, self::COL_TRACKING_NUMBER );
		$provider        = $this->get_field( $row, $header_map, self::COL_PROVIDER );
		$tracking_url    = $this->get_field( $row, $header_map, self::COL_TRACKING_URL );
		$items_raw       = $this->get_field( $row, $header_map, self::COL_ITEMS );

		if ( '' === $order_number ) {
			return $this->fail( $row_number, 'missing_order_number', __( 'Missing order number.', 'woocommerce' ) );
		}
		if ( '' === $tracking_number ) {
			return $this->fail( $row_number, 'missing_tracking_number', __( 'Missing tracking number.', 'woocommerce' ) );
		}
		if ( '' === $provider ) {
			return $this->fail( $row_number, 'missing_provider', __( 'Missing carrier/provider.', 'woocommerce' ) );
		}

		// The stored URL is rendered as a clickable link in the admin and in customer emails,
		// and CSV files typically come from third parties, so only accept http(s) URLs.
		// esc_url_raw() applies its protocol allowlist only when a scheme is present, so a
		// scheme-relative "//example.com/track" would otherwise survive; check the scheme too.
		if ( '' !== $tracking_url ) {
			$tracking_url = esc_url_raw( $tracking_url, array( 'http', 'https' ) );
			$scheme       = '' === $tracking_url ? '' : (string) wp_parse_url( $tracking_url, PHP_URL_SCHEME );
			if ( ! in_array( strtolower( $scheme ), array( 'http', 'https' ), true ) ) {
				return $this->fail( $row_number, 'invalid_tracking_url', __( 'Tracking URL must be a valid http or https URL.', 'woocommerce' ) );
			}
		}

		$order = $this->resolve_order( $order_number );
		if ( ! $order instanceof WC_Order ) {
			return $this->fail(
				$row_number,
				'order_not_found',
				sprintf(
					/* translators: %s: order number string from CSV. */
					__( 'Order not found for order number "%s".', 'woocommerce' ),
					$order_number
				)
			);
		}

		// In-file duplicate guard. The set is persisted with the session on every chunk,
		// so keys are hashed to a fixed short length instead of embedding raw values.
		// The pair is only marked as seen once the row lands (created/updated/skipped),
		// so a failed row does not make an identical valid row later skip as duplicate.
		$dedupe_key = substr( md5( $order->get_id() . '|' . strtolower( $tracking_number ) ), 0, 20 );
		if ( isset( $seen_tracking_pairs[ $dedupe_key ] ) ) {
			return array(
				'row'      => $row_number,
				'status'   => 'skipped',
				'message'  => __( 'Duplicate order/tracking number combination in CSV.', 'woocommerce' ),
				'order_id' => $order->get_id(),
			);
		}

		try {
			$items = $this->parse_items( $items_raw, $order );
		} catch ( \Exception $e ) {
			return $this->fail( $row_number, 'invalid_items', $e->getMessage(), $order->get_id() );
		}

		try {
			$existing = $this->find_existing_fulfillment( $order->get_id(), $tracking_number );
		} catch ( \RuntimeException $e ) {
			return $this->fail(
				$row_number,
				'store_read_failed',
				__( 'Could not read existing fulfillments for this order. Please retry the row.', 'woocommerce' ),
				$order->get_id()
			);
		}

		try {
			if ( $existing instanceof Fulfillment ) {
				if ( ! $this->options['update_existing'] ) {
					$seen_tracking_pairs[ $dedupe_key ] = true;
					return array(
						'row'            => $row_number,
						'status'         => 'skipped',
						'message'        => __( 'Fulfillment with this tracking number already exists.', 'woocommerce' ),
						'order_id'       => $order->get_id(),
						'fulfillment_id' => $existing->get_id(),
					);
				}

				$previous_state  = $existing->get_is_fulfilled();
				$previous_status = $existing->get_status() ?? 'unfulfilled';
				$existing->set_status( 'fulfilled' );
				$existing->set_tracking_number( $tracking_number );
				$existing->set_shipment_provider( $provider );
				// An empty tracking_url cell on an update row intentionally preserves the existing URL;
				// merchants who want to clear it should remove the fulfillment instead.
				if ( '' !== $tracking_url ) {
					$existing->set_tracking_url( $tracking_url );
				}
				// Likewise, a blank items cell preserves the existing items; applying the
				// all-items default here would silently expand partial fulfillments.
				if ( null !== $items ) {
					$existing->set_items( $items );
				}
				$changed_fields = $existing->get_changes();
				$existing->save();
				unset( $this->fulfillments_cache[ $order->get_id() ] );

				$notified = false;
				if ( $this->options['notify_customer'] ) {
					$notified = $this->notify_customer( $order, $existing, $previous_state, $row_number );
				}

				FulfillmentsTracker::track_fulfillment_update(
					'csv_importer',
					$existing->get_id(),
					$previous_status,
					$changed_fields,
					$this->options['notify_customer']
				);

				$seen_tracking_pairs[ $dedupe_key ] = true;
				return array(
					'row'            => $row_number,
					'status'         => 'updated',
					'message'        => __( 'Fulfillment updated.', 'woocommerce' ),
					'order_id'       => $order->get_id(),
					'fulfillment_id' => $existing->get_id(),
					'notified'       => $notified,
				);
			}

			$fulfillment = new Fulfillment();
			$fulfillment->set_entity_type( WC_Order::class );
			$fulfillment->set_entity_id( (string) $order->get_id() );
			$fulfillment->set_status( 'fulfilled' );
			$fulfillment->set_tracking_number( $tracking_number );
			$fulfillment->set_shipment_provider( $provider );
			if ( '' !== $tracking_url ) {
				$fulfillment->set_tracking_url( $tracking_url );
			}
			$fulfillment->set_items( $items ?? $this->default_items_from_order( $order ) );
			$fulfillment->save();
			unset( $this->fulfillments_cache[ $order->get_id() ] );

			$notified = false;
			if ( $this->options['notify_customer'] && $fulfillment->get_is_fulfilled() ) {
				$notified = $this->notify_customer( $order, $fulfillment, false, $row_number );
			}

			FulfillmentsTracker::track_fulfillment_creation(
				'csv_importer',
				$fulfillment->get_is_fulfilled() ? 'fulfilled' : 'draft',
				$fulfillment->get_item_count() === (int) $order->get_item_count() ? 'full' : 'partial',
				$fulfillment->get_item_count(),
				(int) $order->get_item_count(),
				$this->options['notify_customer']
			);

			$seen_tracking_pairs[ $dedupe_key ] = true;
			return array(
				'row'            => $row_number,
				'status'         => 'created',
				'message'        => __( 'Fulfillment created.', 'woocommerce' ),
				'order_id'       => $order->get_id(),
				'fulfillment_id' => $fulfillment->get_id(),
				'notified'       => $notified,
			);
		} catch ( \Throwable $e ) {
			wc_get_logger()->error(
				sprintf( 'Fulfillment import save failed on row %1$d for order %2$d: %3$s', $row_number, $order->get_id(), $e->getMessage() ),
				array( 'source' => 'fulfillments-csv-importer' )
			);
			return $this->fail( $row_number, 'save_failed', __( 'Could not save fulfillment.', 'woocommerce' ), $order->get_id() );
		}
	}

	/**
	 * Build a failed-row result and queue a validation-error event for the next flush.
	 *
	 * @param int      $row_number Row number.
	 * @param string   $error_code Stable machine-readable code (surfaced to the UI and analytics).
	 * @param string   $message    Human-readable failure message (used in the report).
	 * @param int|null $order_id   Optional order ID for context.
	 * @return array<string, mixed>
	 */
	private function fail( int $row_number, string $error_code, string $message, ?int $order_id = null ): array {
		$this->pending_validation_errors[ $error_code ] = ( $this->pending_validation_errors[ $error_code ] ?? 0 ) + 1;

		$result = array(
			'row'     => $row_number,
			'status'  => 'failed',
			'code'    => $error_code,
			'message' => $message,
		);
		if ( null !== $order_id ) {
			$result['order_id'] = $order_id;
		}
		return $result;
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
	 * @since 11.2.0
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
	 * Read a normalized field value from a CSV row.
	 *
	 * @param array<int, string|null> $row   CSV row.
	 * @param array<string, int>      $header_map Header map.
	 * @param string                  $column     Canonical column key.
	 * @return string Trimmed string value (empty string when missing).
	 */
	private function get_field( array $row, array $header_map, string $column ): string {
		if ( ! isset( $header_map[ $column ] ) ) {
			return '';
		}
		$index = $header_map[ $column ];
		if ( ! array_key_exists( $index, $row ) ) {
			return '';
		}
		$value = $row[ $index ];
		if ( null === $value ) {
			return '';
		}
		return trim( wp_check_invalid_utf8( (string) $value ) );
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
	 * Index an order's line items by SKU.
	 *
	 * @param array<int, \WC_Order_Item> $order_items Order line items.
	 * @return array<string, \WC_Order_Item> Lowercased SKU => line item.
	 */
	private function index_items_by_sku( array $order_items ): array {
		$by_sku = array();
		foreach ( $order_items as $order_item ) {
			if ( ! method_exists( $order_item, 'get_product' ) ) {
				continue;
			}
			$product = $order_item->get_product();
			if ( $product && $product->get_sku() ) {
				$by_sku[ strtolower( $product->get_sku() ) ] = $order_item;
			}
		}
		return $by_sku;
	}

	/**
	 * Fire the customer shipment notification for a saved row.
	 *
	 * The row is already in the database by this point, so a listener that throws must not
	 * turn it into a failed row or leave the dedupe pair unrecorded.
	 *
	 * @param WC_Order    $order            Order the fulfillment belongs to.
	 * @param Fulfillment $fulfillment      Saved fulfillment.
	 * @param bool        $was_fulfilled    Whether the fulfillment was already fulfilled before this row.
	 * @param int         $row_number       Row being processed, for the log line.
	 * @return bool Whether the notification was dispatched.
	 */
	private function notify_customer( WC_Order $order, Fulfillment $fulfillment, bool $was_fulfilled, int $row_number ): bool {
		try {
			if ( $was_fulfilled ) {
				/**
				 * This action is documented in OrderFulfillmentsRestController.php.
				 *
				 * @since 10.1.0
				 */
				do_action( 'woocommerce_fulfillment_updated_notification', $order->get_id(), $fulfillment, $order, '' );
				FulfillmentsTracker::track_fulfillment_notification_sent( 'fulfillment_updated', $fulfillment->get_id(), $order->get_id() );
			} else {
				/**
				 * This action is documented in OrderFulfillmentsRestController.php.
				 *
				 * @since 10.1.0
				 */
				do_action( 'woocommerce_fulfillment_created_notification', $order->get_id(), $fulfillment, $order );
				FulfillmentsTracker::track_fulfillment_notification_sent( 'fulfillment_created', $fulfillment->get_id(), $order->get_id() );
			}
			return true;
		} catch ( \Throwable $e ) {
			wc_get_logger()->error(
				sprintf( 'Fulfillment import notification failed on row %1$d for order %2$d: %3$s', $row_number, $order->get_id(), $e->getMessage() ),
				array( 'source' => 'fulfillments-csv-importer' )
			);
			return false;
		}
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
		 * Keys are canonical column identifiers; values are arrays of accepted (lowercase,
		 * snake-cased) aliases. Only the importer's own canonical keys are honoured: the
		 * wizard and the /run route both work from that fixed set, so a new key here would
		 * be detected and then rejected.
		 *
		 * @since 11.2.0
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
				if ( is_string( $alias ) && '' !== $alias ) {
					$clean[] = $alias;
				}
			}
			$sanitized[ $canonical ] = $clean;
		}

		return $sanitized;
	}

	/**
	 * Load every order a chunk references in one batch.
	 *
	 * REST callers build a fresh importer per chunk, so without this each row would issue
	 * its own wc_get_order() call.
	 *
	 * @param array<int, array{row_number:int, row:array<int, string|null>, blank:bool}> $batch The chunk's raw rows.
	 * @param array<string, int>                                                         $header_map Canonical column => CSV column index.
	 */
	private function prime_chunk_caches( array $batch, array $header_map ): void {
		$this->primed_orders = array();

		if ( ! isset( $header_map[ self::COL_ORDER_NUMBER ] ) || empty( $batch ) ) {
			return;
		}

		$numeric_ids = array();
		foreach ( $batch as $entry ) {
			if ( $entry['blank'] ) {
				continue;
			}
			$order_number = $this->get_field( $entry['row'], $header_map, self::COL_ORDER_NUMBER );
			if ( '' === $order_number || array_key_exists( $order_number, $this->order_cache ) ) {
				continue;
			}
			if ( ctype_digit( $order_number ) ) {
				$numeric_ids[] = (int) $order_number;
			}
		}

		if ( empty( $numeric_ids ) ) {
			return;
		}

		$numeric_ids = array_values( array_unique( $numeric_ids ) );
		// post__in is the ID filter both order data stores understand; HPOS maps it onto the
		// orders table and the posts store passes it straight to WP_Query.
		$orders = wc_get_orders(
			array(
				'limit'    => count( $numeric_ids ),
				'post__in' => $numeric_ids,
				'type'     => 'shop_order',
			)
		);

		if ( ! is_array( $orders ) ) {
			return;
		}

		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}
			$this->primed_orders[ $order->get_id() ] = $order;
		}
	}

	/**
	 * Resolve a CSV "order number" cell to a WC_Order.
	 *
	 * Tries a direct numeric match first (the default WooCommerce order number is the order ID),
	 * then fires a filter so extensions with custom order-numbering schemes can resolve it.
	 *
	 * @param string $order_number Raw order number from the CSV.
	 * @return WC_Order|null
	 */
	private function resolve_order( string $order_number ): ?WC_Order {
		if ( array_key_exists( $order_number, $this->order_cache ) ) {
			return $this->order_cache[ $order_number ];
		}

		$order = null;

		if ( ctype_digit( $order_number ) ) {
			$order_id  = (int) $order_number;
			$candidate = $this->primed_orders[ $order_id ] ?? wc_get_order( $order_id );
			if ( $candidate instanceof WC_Order ) {
				$order = $candidate;
			}
		}

		/**
		 * Filter the order resolved from a CSV order number cell.
		 *
		 * Return a WC_Order to override resolution (for custom order-number schemes), or null
		 * to indicate the order number could not be resolved.
		 *
		 * @since 11.2.0
		 *
		 * @param WC_Order|null $order        The order resolved by default behavior, or null.
		 * @param string        $order_number The raw order number string from the CSV.
		 */
		$order = apply_filters( 'woocommerce_fulfillments_csv_importer_resolve_order', $order, $order_number );

		$resolved = $order instanceof WC_Order ? $order : null;

		$this->order_cache[ $order_number ] = $resolved;

		return $resolved;
	}

	/**
	 * Look up an existing non-deleted fulfillment with the given tracking number for an order.
	 *
	 * @param int    $order_id        Order ID.
	 * @param string $tracking_number Tracking number.
	 * @return Fulfillment|null
	 */
	private function find_existing_fulfillment( int $order_id, string $tracking_number ): ?Fulfillment {
		$fulfillments = $this->get_order_fulfillments( $order_id );
		$needle       = strtolower( $tracking_number );

		foreach ( $fulfillments as $fulfillment ) {
			if ( strtolower( (string) $fulfillment->get_tracking_number() ) === $needle ) {
				return $fulfillment;
			}
		}

		return null;
	}

	/**
	 * Load (and cache) the non-deleted fulfillments for an order for the duration of this import run.
	 *
	 * @param int $order_id Order ID.
	 * @return array<int, Fulfillment>
	 *
	 * @throws \RuntimeException When the fulfillments data store cannot be read.
	 */
	private function get_order_fulfillments( int $order_id ): array {
		if ( isset( $this->fulfillments_cache[ $order_id ] ) ) {
			return $this->fulfillments_cache[ $order_id ];
		}

		try {
			/**
			 * Data store for order fulfillments.
			 *
			 * @var FulfillmentsDataStore $store
			 */
			$store        = WC_Data_Store::load( 'order-fulfillment' );
			$fulfillments = $store->read_fulfillments( WC_Order::class, (string) $order_id );
		} catch ( \Throwable $e ) {
			wc_get_logger()->error(
				sprintf( 'Could not read fulfillments for order %1$d during CSV import: %2$s', $order_id, $e->getMessage() ),
				array( 'source' => 'fulfillments-csv-importer' )
			);
			// Do not cache on failure: a transient store error would silently downgrade
			// subsequent rows for the same order from "update" to "create".
			throw new \RuntimeException( 'fulfillment_store_read_failed' );
		}

		$filtered = array();
		foreach ( $fulfillments as $fulfillment ) {
			if ( ! $fulfillment instanceof Fulfillment ) {
				continue;
			}
			if ( $fulfillment->get_date_deleted() ) {
				continue;
			}
			$filtered[] = $fulfillment;
		}

		$this->fulfillments_cache[ $order_id ] = $filtered;
		return $filtered;
	}

	/**
	 * Parse the optional items column into a Fulfillment items array.
	 *
	 * Supported formats per entry, separated by '|' or ';':
	 *   - "<order_item_id>:<qty>"
	 *   - "sku:<sku>:<qty>", which resolves to the matching order item.
	 *
	 * Returns null when the cell contains no entries; the caller decides the
	 * default (all line items on create, keep existing items on update).
	 *
	 * @param string   $raw   Raw value from the CSV.
	 * @param WC_Order $order The matched order.
	 * @return array<int, array{item_id:int, qty:int}>|null
	 *
	 * @throws \Exception When parsing fails or quantities are invalid.
	 */
	private function parse_items( string $raw, WC_Order $order ): ?array {
		if ( '' === $raw ) {
			return null;
		}

		$entries = preg_split( '/[|;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY );
		if ( false === $entries || empty( $entries ) ) {
			return null;
		}

		$order_items = $order->get_items( 'line_item' );
		$by_id       = array();
		foreach ( $order_items as $order_item ) {
			$by_id[ (int) $order_item->get_id() ] = $order_item;
		}

		// Built on first use: indexing by SKU loads every line item's product, which most
		// rows never need because they address items by ID.
		$by_sku   = null;
		$resolved = array();
		foreach ( $entries as $entry ) {
			$entry = trim( $entry );
			if ( '' === $entry ) {
				continue;
			}

			$parts = array_map( 'trim', explode( ':', $entry ) );

			if ( count( $parts ) === 3 && 'sku' === strtolower( $parts[0] ) ) {
				$sku    = strtolower( $parts[1] );
				$qty    = $parts[2];
				$by_sku = $by_sku ?? $this->index_items_by_sku( $order_items );
				if ( ! isset( $by_sku[ $sku ] ) ) {
					throw new \Exception(
						esc_html(
							sprintf(
								/* translators: %s: SKU value from CSV. */
								__( 'SKU %s was not found on the order.', 'woocommerce' ),
								$parts[1]
							)
						)
					);
				}
				$resolved[] = array(
					'item' => $by_sku[ $sku ],
					'qty'  => $qty,
				);
				continue;
			}

			if ( count( $parts ) === 2 ) {
				$item_id = $parts[0];
				$qty     = $parts[1];
				if ( ! ctype_digit( $item_id ) || ! isset( $by_id[ (int) $item_id ] ) ) {
					throw new \Exception(
						esc_html(
							sprintf(
								/* translators: %s: order item ID from CSV. */
								__( 'Item ID %s is not part of this order.', 'woocommerce' ),
								(string) $item_id
							)
						)
					);
				}
				$resolved[] = array(
					'item' => $by_id[ (int) $item_id ],
					'qty'  => $qty,
				);
				continue;
			}

			throw new \Exception(
				esc_html(
					sprintf(
						/* translators: %s: items value from CSV. */
						__( 'Invalid items entry: %s.', 'woocommerce' ),
						$entry
					)
				)
			);
		}

		// Aggregate quantities per order item first, so repeated entries for the
		// same item (e.g. "123:1|123:1") are validated against the ordered
		// quantity as a total instead of slipping past the check individually.
		$items_by_id = array();
		$totals      = array();
		foreach ( $resolved as $entry ) {
			$order_item = $entry['item'];
			$qty_input  = $entry['qty'];

			// Require an integer-valued positive quantity. Reject fractional values rather than silently truncating.
			if ( ! is_numeric( $qty_input ) || 0 >= (float) $qty_input || 0.0 !== fmod( (float) $qty_input, 1.0 ) ) {
				throw new \Exception( esc_html__( 'Item quantity must be a positive integer.', 'woocommerce' ) );
			}

			$item_id                 = (int) $order_item->get_id();
			$items_by_id[ $item_id ] = $order_item;
			$totals[ $item_id ]      = ( $totals[ $item_id ] ?? 0 ) + (int) $qty_input;
		}

		$result = array();
		foreach ( $totals as $item_id => $qty ) {
			$order_item  = $items_by_id[ $item_id ];
			$ordered_qty = method_exists( $order_item, 'get_quantity' ) ? (int) $order_item->get_quantity() : 0;
			if ( $ordered_qty > 0 && $qty > $ordered_qty ) {
				throw new \Exception(
					esc_html(
						sprintf(
							/* translators: 1: requested quantity, 2: ordered quantity. */
							__( 'Item quantity %1$d exceeds the ordered quantity %2$d.', 'woocommerce' ),
							$qty,
							$ordered_qty
						)
					)
				);
			}

			$result[] = array(
				'item_id' => $item_id,
				'qty'     => $qty,
			);
		}

		if ( empty( $result ) ) {
			return null;
		}

		return $result;
	}

	/**
	 * Build the default items array from an order's line items at full ordered quantity.
	 *
	 * @param WC_Order $order Order.
	 * @return array<int, array{item_id:int, qty:int}>
	 */
	private function default_items_from_order( WC_Order $order ): array {
		$items = array();
		foreach ( $order->get_items( 'line_item' ) as $order_item ) {
			$qty = method_exists( $order_item, 'get_quantity' ) ? (int) $order_item->get_quantity() : 0;
			if ( $qty <= 0 ) {
				continue;
			}
			$items[] = array(
				'item_id' => (int) $order_item->get_id(),
				'qty'     => $qty,
			);
		}
		return $items;
	}

	/**
	 * Whether a parsed CSV row is effectively blank.
	 *
	 * @param array<int, string|null>|null $row Row to test.
	 * @return bool
	 */
	private function is_blank_row( ?array $row ): bool {
		if ( null === $row ) {
			return true;
		}
		foreach ( $row as $cell ) {
			if ( null !== $cell && '' !== trim( (string) $cell ) ) {
				return false;
			}
		}
		return true;
	}
}
