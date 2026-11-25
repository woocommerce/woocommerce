<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Admin\Features\Fulfillments;

use Automattic\WooCommerce\Admin\Features\Fulfillments\DataStore\FulfillmentsDataStore;
use WC_Data_Store;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Wires fulfillment (shipment tracking) data into the WordPress personal-data
 * export and erasure framework.
 *
 * Export appends the customer's fulfillment records onto each order in their
 * personal-data export, through the core order exporter.
 * Erasure redacts the tracking meta whenever an order is anonymized, which covers
 * both the admin "Erase Personal Data" tool and the automatic retention cleanup,
 * since both route through {@see \WC_Privacy_Erasers::remove_order_personal_data()}.
 *
 * Redaction writes directly to the core fulfillment tables, and the export asks the data
 * store for soft-deleted records, which FulfillmentsDataStoreInterface does not declare. So a
 * store swapped in through the `woocommerce_data_stores` filter owns its own erasure and
 * whatever it returns to the export.
 *
 * @since 11.4.0
 */
class FulfillmentsPrivacy {

	/**
	 * Register the privacy hooks.
	 *
	 * @since 11.4.0
	 */
	public function register(): void {
		add_filter( 'woocommerce_privacy_export_order_personal_data', array( $this, 'export_order_fulfillment_data' ), 10, 2 );
		add_action( 'woocommerce_privacy_remove_order_personal_data', array( $this, 'remove_fulfillment_personal_data' ) );
	}

	/**
	 * Whether this store ever created the fulfillment tables.
	 *
	 * Reads the marker option rather than probing with SHOW TABLES, which carries no table
	 * name for a db-routing drop-in to route on and so can be answered by the wrong dataset.
	 *
	 * @return bool
	 */
	private function tables_exist(): bool {
		return (bool) get_option( 'woocommerce_fulfillments_db_tables_created', false );
	}

	/**
	 * Append an order's fulfillment tracking data to its personal-data export entry.
	 *
	 * Hooked onto the core order exporter rather than registered as a standalone exporter,
	 * and each shipment is emitted as a single row. Core reloads and rewrites the whole
	 * accumulated export on every page, so both the item count and the row count are paid
	 * again on each remaining page, and a large order history exhausts memory.
	 *
	 * @since 11.4.0
	 *
	 * @param mixed $personal_data Name/value rows the core exporter built for the order.
	 * @param mixed $order         The order being exported.
	 * @return array
	 */
	public function export_order_fulfillment_data( $personal_data, $order ): array {
		$personal_data = is_array( $personal_data ) ? $personal_data : array();

		if ( ! $order instanceof WC_Order ) {
			return $personal_data;
		}

		// Nothing to read without the tables, and on a store with the feature off the data
		// store is registered only because they exist.
		if ( ! $this->tables_exist() ) {
			return $personal_data;
		}

		// A read failure here must not abort the customer's whole personal-data export, which
		// spans every extension's order data, so it is logged and this order's fulfillment rows
		// are skipped. Core reports the request complete either way, so the log line is the
		// only signal a failure leaves.
		try {
			$fulfillments = $this->read_order_fulfillments( $order );

			$shipments = array();

			foreach ( $fulfillments as $fulfillment ) {
				// Defensive: a fulfillment saved through the data store always has a valid
				// status, so it always yields at least one field. A malformed stored row
				// might not, and must not take a shipment number with it.
				$rows = $this->get_fulfillment_personal_data( $fulfillment, $order );
				if ( empty( $rows ) ) {
					continue;
				}

				// Values are joined with plain punctuation rather than a translatable format
				// string: a locale that lost a placeholder would delete exported data.
				$fields = array();
				foreach ( $rows as $row ) {
					$fields[] = $row['name'] . ': ' . $row['value'];
				}

				$shipments[] = implode( '; ', $fields );
			}

			// Several shipments share the one order entry, so number them to tell them apart,
			// off the shipments that produced fields so the export shows neither a gap nor a
			// number implying one it left out.
			$numbered      = count( $shipments ) > 1;
			$shipment_rows = array();

			foreach ( $shipments as $index => $shipment ) {
				$shipment_rows[] = array(
					'name'  => $numbered
						/* translators: %s: shipment number within the order. */
						? sprintf( _x( 'Shipment %s', 'fulfillment export field', 'woocommerce' ), number_format_i18n( $index + 1 ) )
						: _x( 'Shipment', 'fulfillment export field', 'woocommerce' ),
					'value' => $shipment,
				);
			}

			// Appended in one go, so a failure part way through cannot leave the order half
			// exported while the request still reports it complete.
			$personal_data = array_merge( $personal_data, $shipment_rows );
		} catch ( \Throwable $e ) {
			wc_get_logger()->error(
				'Failed to export fulfillment personal data for order ' . $order->get_id() . ': ' . $e->getMessage(),
				array( 'source' => 'fulfillments' )
			);
		}

		return $personal_data;
	}

	/**
	 * Read every fulfillment of an order, including soft-deleted ones.
	 *
	 * Soft-deleted records are included because their tracking meta is retained and must be
	 * covered by the access request, mirroring what the eraser clears.
	 *
	 * @param WC_Order $order The order being exported.
	 * @return Fulfillment[]
	 * @throws \Exception If the read left a database error behind.
	 */
	private function read_order_fulfillments( WC_Order $order ): array {
		/**
		 * Fulfillments data store.
		 *
		 * @var FulfillmentsDataStore $datastore
		 */
		$datastore = WC_Data_Store::load( 'order-fulfillment' );

		global $wpdb;

		// A replacement store can answer without querying, which would leave an unrelated
		// error in place and drop this order's rows for no reason. flush() is what wpdb runs
		// at the start of every query anyway.
		$wpdb->flush();

		$fulfillments = $datastore->read_fulfillments( WC_Order::class, (string) $order->get_id(), true );

		// get_results() returns an empty array on a failed query, so a database error is
		// otherwise indistinguishable from "no fulfillments" and would be reported as a
		// complete export. wpdb resets the error on every query, so this sees the fulfillment
		// read and the last meta read, not an earlier meta read a later one masked.
		if ( '' !== (string) $wpdb->last_error ) {
			throw new \Exception( esc_html( 'Failed to read fulfillment data: ' . $wpdb->last_error ) );
		}

		return $fulfillments;
	}

	/**
	 * Build the exportable fields for a single fulfillment.
	 *
	 * The order's items cover everything purchased, but an order can be split across
	 * several fulfillments, so the shipment-level breakdown (which items shipped where,
	 * and with what tracking) is not otherwise in the export. The labels are read inside
	 * a row already named for the shipment, so they stay short.
	 *
	 * @param Fulfillment $fulfillment The fulfillment.
	 * @param WC_Order    $order       The order the fulfillment belongs to.
	 * @return array<int, array{name: string, value: string}>
	 */
	private function get_fulfillment_personal_data( Fulfillment $fulfillment, WC_Order $order ): array {
		$data = array();

		$status = (string) $fulfillment->get_status();
		if ( '' !== $status ) {
			$statuses = FulfillmentUtils::get_fulfillment_statuses();
			$label    = $statuses[ $status ]['label'] ?? $status;
			$data[]   = array(
				'name'  => _x( 'Status', 'fulfillment export field', 'woocommerce' ),
				'value' => is_scalar( $label ) ? (string) $label : $status,
			);
		}

		$item_names = array();
		foreach ( $fulfillment->get_items() as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$item_id    = isset( $item['item_id'] ) ? (int) $item['item_id'] : 0;
			$order_item = $item_id > 0 ? $order->get_item( $item_id, false ) : false;
			if ( $order_item ) {
				$quantity     = isset( $item['qty'] ) ? (int) $item['qty'] : 0;
				$item_names[] = $order_item->get_name() . ' x ' . $quantity;
			}
		}
		if ( ! empty( $item_names ) ) {
			$data[] = array(
				'name'  => _x( 'Items', 'fulfillment export field', 'woocommerce' ),
				'value' => implode( ', ', $item_names ),
			);
		}

		$tracking_number = $fulfillment->get_tracking_number();
		if ( ! empty( $tracking_number ) ) {
			$data[] = array(
				'name'  => _x( 'Tracking number', 'fulfillment export field', 'woocommerce' ),
				'value' => $tracking_number,
			);
		}

		$provider = FulfillmentUtils::get_provider_display_name( $fulfillment );
		if ( '' !== $provider ) {
			$data[] = array(
				'name'  => _x( 'Provider', 'fulfillment export field', 'woocommerce' ),
				'value' => $provider,
			);
		}

		$tracking_url = $fulfillment->get_tracking_url();
		if ( ! empty( $tracking_url ) ) {
			$data[] = array(
				'name'  => _x( 'Tracking URL', 'fulfillment export field', 'woocommerce' ),
				'value' => $tracking_url,
			);
		}

		return $data;
	}

	/**
	 * Redact fulfillment tracking data for an order being anonymized.
	 *
	 * Includes soft-deleted fulfillments, since their tracking meta is retained in
	 * the database and a tracking number can be resolved to a delivery address.
	 *
	 * @since 11.4.0
	 *
	 * @param mixed $order The order being anonymized.
	 */
	public function remove_fulfillment_personal_data( $order ): void {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		// Nearly every store with the feature off never created these tables, so skip those
		// instead of failing a query for each order anonymized.
		if ( ! $this->tables_exist() ) {
			return;
		}

		$default_keys = array( '_tracking_number', '_tracking_url' );

		/**
		 * Filters the fulfillment meta keys redacted during personal-data erasure.
		 *
		 * A non-array return is ignored and the default keys are cleared instead. An empty array clears nothing.
		 *
		 * @since 11.4.0
		 *
		 * @param string[] $meta_keys The meta keys to clear.
		 * @param WC_Order $order     The order being anonymized.
		 */
		$meta_keys = apply_filters(
			'woocommerce_privacy_remove_fulfillment_personal_data_meta',
			$default_keys,
			$order
		);

		if ( ! is_array( $meta_keys ) ) {
			wc_get_logger()->warning(
				'The woocommerce_privacy_remove_fulfillment_personal_data_meta filter returned a non-array value for order ' . $order->get_id() . ', so the default keys were used.',
				array( 'source' => 'fulfillments' )
			);
			$meta_keys = $default_keys;
		}

		$meta_keys = array_values(
			array_unique(
				array_filter(
					array_map( 'strval', array_filter( $meta_keys, 'is_scalar' ) ),
					function ( string $key ): bool {
						return '' !== $key;
					}
				)
			)
		);
		if ( empty( $meta_keys ) ) {
			return;
		}

		global $wpdb;

		// Redact the tracking meta for every fulfillment of the order, including soft-deleted
		// records. The data store keeps deleted fulfillments immutable, but their retained
		// tracking number can still be resolved to a delivery address, so it must be cleared
		// directly. Values are stored JSON-encoded, so an empty string is written as `""`.
		$placeholders = implode( ', ', array_fill( 0, count( $meta_keys ), '%s' ) );
		$params       = array_merge(
			array( wp_json_encode( '' ), WC_Order::class, (string) $order->get_id() ),
			$meta_keys
		);

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}wc_order_fulfillment_meta AS m
				INNER JOIN {$wpdb->prefix}wc_order_fulfillments AS f ON f.fulfillment_id = m.fulfillment_id
				SET m.meta_value = %s
				WHERE f.entity_type = %s AND f.entity_id = %s AND m.meta_key IN ( {$placeholders} )",
				$params
			)
		);
		// phpcs:enable

		// The erasure action cannot report failure back to the request, so a failed redaction
		// would otherwise leave tracking in place while the order reads as anonymized. Zero rows
		// is not a failure, so only false is logged.
		if ( false === $result ) {
			wc_get_logger()->error(
				'Failed to redact fulfillment tracking for order ' . $order->get_id() . ': ' . $wpdb->last_error,
				array( 'source' => 'fulfillments' )
			);
		}
	}
}
