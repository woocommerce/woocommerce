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
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentUtils;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsTracker;
use WC_Data_Store;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Parses a CSV file of fulfillment rows and creates/updates fulfillment records.
 *
 * @since 10.9.0
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
	 * Per-run cache of resolved orders, keyed by the raw order number string from the CSV.
	 *
	 * @var array<string, WC_Order|null>
	 */
	private array $order_cache = array();

	/**
	 * Per-run cache of non-deleted fulfillments keyed by order ID.
	 *
	 * @var array<int, array<int, Fulfillment>>
	 */
	private array $fulfillments_cache = array();

	/**
	 * Constructor.
	 *
	 * @since 10.9.0
	 *
	 * @param string               $file    Absolute path to the CSV file.
	 * @param array<string, mixed> $options Importer options:
	 *                                      - notify_customer (bool): Whether to fire customer notifications. Default false.
	 *                                      - delimiter (string): CSV delimiter. Default ','.
	 *                                      - enclosure (string): CSV enclosure. Default '"'.
	 *                                      - update_existing (bool): Update fulfillment when one with the same tracking number
	 *                                                                already exists on the order. Default true.
	 */
	public function __construct( string $file, array $options = array() ) {
		$this->file    = $file;
		$this->options = array(
			'notify_customer' => ! empty( $options['notify_customer'] ),
			'delimiter'       => isset( $options['delimiter'] ) && '' !== $options['delimiter'] ? (string) $options['delimiter'] : ',',
			'enclosure'       => isset( $options['enclosure'] ) && '' !== $options['enclosure'] ? (string) $options['enclosure'] : '"',
			'update_existing' => array_key_exists( 'update_existing', $options ) ? (bool) $options['update_existing'] : true,
		);
	}

	/**
	 * Parse and process the CSV file.
	 *
	 * Each row is processed independently — a single bad row never aborts the run.
	 *
	 * @since 10.9.0
	 *
	 * @return array{
	 *     created: int,
	 *     updated: int,
	 *     skipped: int,
	 *     failed:  int,
	 *     notified: int,
	 *     rows:    array<int, array{row:int, status:string, message:string, order_id?:int, fulfillment_id?:int}>
	 * }
	 */
	public function run(): array {
		$this->order_cache        = array();
		$this->fulfillments_cache = array();

		$summary = array(
			'created'  => 0,
			'updated'  => 0,
			'skipped'  => 0,
			'failed'   => 0,
			'notified' => 0,
			'rows'     => array(),
		);

		if ( ! is_readable( $this->file ) ) {
			$summary['rows'][] = $this->fail( 0, 'file_not_readable', __( 'File is not readable.', 'woocommerce' ) );
			++$summary['failed'];
			return $summary;
		}

		$handle = fopen( $this->file, 'rb' );
		if ( false === $handle ) {
			$summary['rows'][] = $this->fail( 0, 'file_open_failed', __( 'Could not open file.', 'woocommerce' ) );
			++$summary['failed'];
			return $summary;
		}

		try {
			// Strip UTF-8 BOM if present so Excel-saved CSVs match the first header alias.
			$bom = fread( $handle, 3 );
			if ( "\xEF\xBB\xBF" !== $bom ) {
				rewind( $handle );
			}

			$header_raw = fgetcsv( $handle, 0, $this->options['delimiter'], $this->options['enclosure'], '' );
			if ( false === $header_raw || null === $header_raw ) {
				$summary['rows'][] = $this->fail( 0, 'empty_csv', __( 'CSV file is empty.', 'woocommerce' ) );
				++$summary['failed'];
				return $summary;
			}

			$header_map = $this->build_header_map( $header_raw );
			$missing    = $this->find_missing_required_columns( $header_map );

			if ( ! empty( $missing ) ) {
				$summary['rows'][] = $this->fail(
					0,
					'missing_required_columns',
					sprintf(
						/* translators: %s: comma-separated list of missing column names. */
						__( 'CSV is missing required column(s): %s.', 'woocommerce' ),
						implode( ', ', $missing )
					)
				);
				++$summary['failed'];
				return $summary;
			}

			$row_number          = 1; // Header is row 1.
			$seen_tracking_pairs = array(); // Map of "order_id|tracking" => true for in-file duplicate detection.

			while ( true ) {
				$row = fgetcsv( $handle, 0, $this->options['delimiter'], $this->options['enclosure'], '' );
				if ( false === $row ) {
					break;
				}
				++$row_number;

				if ( $this->is_blank_row( $row ) ) {
					continue;
				}

				$result = $this->process_row( $row, $header_map, $row_number, $seen_tracking_pairs );

				if ( isset( $result['status'] ) ) {
					switch ( $result['status'] ) {
						case 'created':
							++$summary['created'];
							break;
						case 'updated':
							++$summary['updated'];
							break;
						case 'skipped':
							++$summary['skipped'];
							break;
						case 'failed':
						default:
							++$summary['failed'];
							break;
					}

					if ( ! empty( $result['notified'] ) ) {
						++$summary['notified'];
					}
				}

				$summary['rows'][] = $result;
			}
		} finally {
			fclose( $handle );
		}

		return $summary;
	}

	/**
	 * Process a single CSV row.
	 *
	 * @param array<int, string>             $row                  Raw CSV row.
	 * @param array<string, int>             $header_map           Map of canonical column key => CSV column index.
	 * @param int                            $row_number           1-based row number (header is row 1).
	 * @param array<string, true>            $seen_tracking_pairs  Reference to in-file dedupe tracker.
	 *
	 * @return array{row:int, status:string, message:string, order_id?:int, fulfillment_id?:int, notified?:bool}
	 */
	private function process_row( array $row, array $header_map, int $row_number, array &$seen_tracking_pairs ): array {
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

		// In-file duplicate guard.
		$dedupe_key = $order->get_id() . '|' . strtolower( $tracking_number );
		if ( isset( $seen_tracking_pairs[ $dedupe_key ] ) ) {
			return array(
				'row'      => $row_number,
				'status'   => 'skipped',
				'message'  => __( 'Duplicate order/tracking number combination in CSV.', 'woocommerce' ),
				'order_id' => $order->get_id(),
			);
		}
		$seen_tracking_pairs[ $dedupe_key ] = true;

		try {
			$items = $this->parse_items( $items_raw, $order );
		} catch ( \Exception $e ) {
			return $this->fail( $row_number, 'invalid_items', $e->getMessage(), $order->get_id() );
		}

		// Determine create vs update by looking for an existing fulfillment with the same tracking number.
		$existing = $this->find_existing_fulfillment( $order->get_id(), $tracking_number );

		try {
			if ( $existing instanceof Fulfillment ) {
				if ( ! $this->options['update_existing'] ) {
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
				if ( '' !== $tracking_url ) {
					$existing->set_tracking_url( $tracking_url );
				}
				$existing->set_items( $items );
				$changed_fields = $existing->get_changes();
				$existing->save();
				unset( $this->fulfillments_cache[ $order->get_id() ] );

				$notified = false;
				if ( $this->options['notify_customer'] ) {
					if ( ! $previous_state ) {
						/** This hook is documented in OrderFulfillmentsRestController.php. */
						do_action( 'woocommerce_fulfillment_created_notification', $order->get_id(), $existing, $order );
						FulfillmentsTracker::track_fulfillment_notification_sent( 'fulfillment_created', $existing->get_id(), $order->get_id() );
					} else {
						/** This hook is documented in OrderFulfillmentsRestController.php. */
						do_action( 'woocommerce_fulfillment_updated_notification', $order->get_id(), $existing, $order, '' );
						FulfillmentsTracker::track_fulfillment_notification_sent( 'fulfillment_updated', $existing->get_id(), $order->get_id() );
					}
					$notified = true;
				}

				FulfillmentsTracker::track_fulfillment_update(
					'csv_importer',
					$existing->get_id(),
					$previous_status,
					is_array( $changed_fields ) ? $changed_fields : array(),
					$this->options['notify_customer']
				);

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
			$fulfillment->set_items( $items );
			$fulfillment->save();
			unset( $this->fulfillments_cache[ $order->get_id() ] );

			$notified = false;
			if ( $this->options['notify_customer'] && $fulfillment->get_is_fulfilled() ) {
				/** This hook is documented in OrderFulfillmentsRestController.php. */
				do_action( 'woocommerce_fulfillment_created_notification', $order->get_id(), $fulfillment, $order );
				FulfillmentsTracker::track_fulfillment_notification_sent( 'fulfillment_created', $fulfillment->get_id(), $order->get_id() );
				$notified = true;
			}

			FulfillmentsTracker::track_fulfillment_creation(
				'csv_importer',
				$fulfillment->get_is_fulfilled() ? 'fulfilled' : 'draft',
				$fulfillment->get_item_count() === (int) $order->get_item_count() ? 'full' : 'partial',
				$fulfillment->get_item_count(),
				(int) $order->get_item_count(),
				$this->options['notify_customer']
			);

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
	 * Build a failed-row result and fire a validation_error tracker event.
	 *
	 * @param int      $row_number Row number.
	 * @param string   $error_code Stable machine-readable code (used for analytics).
	 * @param string   $message    Human-readable failure message (used in the report).
	 * @param int|null $order_id   Optional order ID for context.
	 * @return array<string, mixed>
	 */
	private function fail( int $row_number, string $error_code, string $message, ?int $order_id = null ): array {
		FulfillmentsTracker::track_fulfillment_validation_error( 'import', $error_code, 'csv_importer' );

		$result = array(
			'row'     => $row_number,
			'status'  => 'failed',
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
	 * @param array<int, string> $header Raw header row.
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
	 * @param array<string, int> $header_map Header map.
	 * @return array<int, string> Human-friendly names of missing columns.
	 */
	private function find_missing_required_columns( array $header_map ): array {
		$required = array(
			self::COL_ORDER_NUMBER    => __( 'order_number', 'woocommerce' ),
			self::COL_TRACKING_NUMBER => __( 'tracking_number', 'woocommerce' ),
			self::COL_PROVIDER        => __( 'shipment_provider', 'woocommerce' ),
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
	 * @param array<int, string> $row        CSV row.
	 * @param array<string, int> $header_map Header map.
	 * @param string             $column     Canonical column key.
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
		$value = is_scalar( $value ) ? (string) $value : '';
		return trim( wp_check_invalid_utf8( $value ) );
	}

	/**
	 * Normalize a header cell to a lowercase snake-case string for fuzzy matching.
	 *
	 * @param string $name Raw header.
	 * @return string
	 */
	private function normalize_header( string $name ): string {
		$name = strtolower( trim( $name ) );
		// Replace any non a-z/0-9 sequence with underscore.
		$name = preg_replace( '/[^a-z0-9]+/', '_', $name );
		return trim( (string) $name, '_' );
	}

	/**
	 * Aliases recognized for each canonical column.
	 *
	 * @return array<string, array<int, string>>
	 */
	private function get_column_aliases(): array {
		/**
		 * Filter the header aliases recognized by the fulfillments CSV importer.
		 *
		 * Lets stores accept additional header names from third-party WMS/3PL exports.
		 * Keys are canonical column identifiers; values are arrays of accepted (lowercase,
		 * snake-cased) aliases.
		 *
		 * @since 10.9.0
		 *
		 * @param array<string, array<int, string>> $aliases Default alias map.
		 */
		return apply_filters(
			'woocommerce_fulfillments_csv_importer_column_aliases',
			array(
				self::COL_ORDER_NUMBER    => array( 'order_number', 'order', 'order_id', 'order_no', 'order_num' ),
				self::COL_TRACKING_NUMBER => array( 'tracking_number', 'tracking', 'tracking_no', 'tracking_num' ),
				self::COL_PROVIDER        => array( 'shipment_provider', 'provider', 'carrier', 'shipping_provider', 'shipping_carrier' ),
				self::COL_TRACKING_URL    => array( 'tracking_url', 'url' ),
				self::COL_ITEMS           => array( 'items', 'line_items' ),
			)
		);
	}

	/**
	 * Resolve a CSV "order number" cell to a WC_Order.
	 *
	 * First tries a direct numeric match (the default WooCommerce order number is the order ID).
	 * Then fires a filter so extensions providing custom order numbering schemes can resolve it.
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
			$candidate = wc_get_order( (int) $order_number );
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
		 * @since 10.9.0
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
	 */
	private function get_order_fulfillments( int $order_id ): array {
		if ( isset( $this->fulfillments_cache[ $order_id ] ) ) {
			return $this->fulfillments_cache[ $order_id ];
		}

		try {
			/** @var FulfillmentsDataStore $store */
			$store        = WC_Data_Store::load( 'order-fulfillment' );
			$fulfillments = $store->read_fulfillments( WC_Order::class, (string) $order_id );
		} catch ( \Throwable $e ) {
			$this->fulfillments_cache[ $order_id ] = array();
			return $this->fulfillments_cache[ $order_id ];
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
	 *   - "sku:<sku>:<qty>"   — resolves to the matching order item.
	 *
	 * When the column is empty, defaults to all order line items at full ordered quantity.
	 *
	 * @param string   $raw   Raw value from the CSV.
	 * @param WC_Order $order The matched order.
	 * @return array<int, array{item_id:int, qty:int}>
	 *
	 * @throws \Exception When parsing fails or quantities are invalid.
	 */
	private function parse_items( string $raw, WC_Order $order ): array {
		if ( '' === $raw ) {
			return $this->default_items_from_order( $order );
		}

		$entries = preg_split( '/[|;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY );
		if ( false === $entries || empty( $entries ) ) {
			return $this->default_items_from_order( $order );
		}

		$order_items = $order->get_items( 'line_item' );
		$by_id       = array();
		$by_sku      = array();
		foreach ( $order_items as $order_item ) {
			$by_id[ (int) $order_item->get_id() ] = $order_item;
			if ( method_exists( $order_item, 'get_product' ) ) {
				$product = $order_item->get_product();
				if ( $product && $product->get_sku() ) {
					$by_sku[ strtolower( $product->get_sku() ) ] = $order_item;
				}
			}
		}

		$resolved = array();
		foreach ( $entries as $entry ) {
			$entry = trim( $entry );
			if ( '' === $entry ) {
				continue;
			}

			$parts = array_map( 'trim', explode( ':', $entry ) );

			if ( count( $parts ) === 3 && 'sku' === strtolower( $parts[0] ) ) {
				$sku = strtolower( $parts[1] );
				$qty = $parts[2];
				if ( ! isset( $by_sku[ $sku ] ) ) {
					throw new \Exception(
						sprintf(
							/* translators: %s: SKU value from CSV. */
							__( 'SKU "%s" not found on the order.', 'woocommerce' ),
							$parts[1]
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
						sprintf(
							/* translators: %s: order item ID from CSV. */
							__( 'Item ID "%s" is not part of this order.', 'woocommerce' ),
							(string) $item_id
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
				sprintf(
					/* translators: %s: items value from CSV. */
					__( 'Invalid items entry: "%s".', 'woocommerce' ),
					$entry
				)
			);
		}

		$result = array();
		foreach ( $resolved as $entry ) {
			$order_item = $entry['item'];
			$qty_input  = $entry['qty'];

			// Require an integer-valued positive quantity. Reject fractional values rather than silently truncating.
			if ( ! is_numeric( $qty_input ) || (float) $qty_input <= 0 || (float) $qty_input !== (float) (int) $qty_input ) {
				throw new \Exception( __( 'Item quantity must be a positive integer.', 'woocommerce' ) );
			}
			$qty           = (int) $qty_input;
			$ordered_qty   = method_exists( $order_item, 'get_quantity' ) ? (int) $order_item->get_quantity() : 0;
			if ( $ordered_qty > 0 && $qty > $ordered_qty ) {
				throw new \Exception(
					sprintf(
						/* translators: 1: requested quantity, 2: ordered quantity. */
						__( 'Item quantity %1$d exceeds the ordered quantity %2$d.', 'woocommerce' ),
						$qty,
						$ordered_qty
					)
				);
			}

			$result[] = array(
				'item_id' => (int) $order_item->get_id(),
				'qty'     => $qty,
			);
		}

		if ( empty( $result ) ) {
			return $this->default_items_from_order( $order );
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
	 * @param array<int, string>|null $row Row to test.
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

	/**
	 * Return the list of fulfillment status keys that are considered "fulfilled".
	 *
	 * Kept for completeness; the importer always creates fulfilled fulfillments.
	 *
	 * @since 10.9.0
	 *
	 * @return array<int, string>
	 */
	public static function get_fulfilled_status_keys(): array {
		$statuses = FulfillmentUtils::get_fulfillment_statuses();
		$keys     = array();
		foreach ( $statuses as $key => $info ) {
			if ( ! empty( $info['is_fulfilled'] ) ) {
				$keys[] = (string) $key;
			}
		}
		return $keys;
	}
}
