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
 * Reads the header row of a fulfillments CSV, detects the column mapping and counts
 * the data rows so the import wizard can present its column-mapping step.
 *
 * @since 11.3.0
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
	 * @since 11.3.0
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
	 * @since 11.3.0
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
	 * @since 11.3.0
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
	 * @since 11.3.0
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
	 * @since 11.3.0
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
		 * Keys are canonical column identifiers; values are arrays of accepted (lowercase,
		 * snake-cased) aliases. Only the importer's own canonical keys are honoured: the
		 * wizard works from that fixed set, so a new key here would be detected and then
		 * rejected.
		 *
		 * @since 11.3.0
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
