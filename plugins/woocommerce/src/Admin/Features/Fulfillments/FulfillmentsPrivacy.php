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
 * Redaction writes directly to the core fulfillment tables; a data store swapped in
 * through the `woocommerce_data_stores` filter is responsible for its own erasure.
 *
 * @since 11.4.0
 */
class FulfillmentsPrivacy {

	/**
	 * Register the privacy hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_privacy_export_order_personal_data', array( $this, 'export_order_fulfillment_data' ), 10, 2 );
		add_action( 'woocommerce_privacy_remove_order_personal_data', array( $this, 'remove_fulfillment_personal_data' ) );
	}

	/**
	 * Append an order's fulfillment tracking data to its personal-data export entry.
	 *
	 * Hooked onto the core order exporter rather than registered as a standalone exporter.
	 * A standalone exporter adds one personal-data item per fulfillment, and the export
	 * framework reloads and rewrites every accumulated item on each page, so a large order
	 * history exhausts memory. Riding on the order's existing item keeps that cost flat and
	 * reuses the core exporter's own order pagination.
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

		// A read failure here must not abort the customer's whole personal-data export, which
		// spans every extension's order data, so it is logged and this order's fulfillment rows
		// are skipped. No completeness signal is faked: core owns the export's pagination.
		try {
			/**
			 * Fulfillments data store.
			 *
			 * @var FulfillmentsDataStore $datastore
			 */
			$datastore = WC_Data_Store::load( 'order-fulfillment' );

			// Include soft-deleted fulfillments: their tracking meta is retained and must be
			// covered by the access request, mirroring what the eraser clears. The final flag makes
			// a read failure throw rather than return empty, so a DB error is not mistaken for
			// "no fulfillments" and silently reported as a complete export.
			$fulfillments = $datastore->read_fulfillments( WC_Order::class, (string) $order->get_id(), true, true );

			$number_shipments = count( $fulfillments ) > 1;
			$shipment_index   = 0;

			foreach ( $fulfillments as $fulfillment ) {
				$rows = $this->get_fulfillment_personal_data( $fulfillment, $order );
				if ( empty( $rows ) ) {
					continue;
				}

				++$shipment_index;

				// Several shipments share the one order entry, so number the rows to tell them apart.
				$prefix = $number_shipments
					/* translators: %d: shipment number within the order. */
					? sprintf( __( 'Shipment %d: ', 'woocommerce' ), $shipment_index )
					: '';

				foreach ( $rows as $row ) {
					$personal_data[] = array(
						'name'  => $prefix . $row['name'],
						'value' => $row['value'],
					);
				}
			}
		} catch ( \Throwable $e ) {
			wc_get_logger()->error(
				'Failed to export fulfillment personal data for order ' . $order->get_id() . ': ' . $e->getMessage(),
				array( 'source' => 'fulfillments' )
			);
		}

		return $personal_data;
	}

	/**
	 * Build the exportable rows for a single fulfillment.
	 *
	 * The order's items cover everything purchased, but an order can be split across
	 * several fulfillments, so the shipment-level breakdown (which items shipped where,
	 * and with what tracking) is not otherwise in the export.
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
			$data[]   = array(
				'name'  => __( 'Fulfillment status', 'woocommerce' ),
				'value' => $statuses[ $status ]['label'] ?? $status,
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
				'name'  => __( 'Items in this shipment', 'woocommerce' ),
				'value' => implode( ', ', $item_names ),
			);
		}

		$tracking_number = $fulfillment->get_tracking_number();
		if ( ! empty( $tracking_number ) ) {
			$data[] = array(
				'name'  => __( 'Tracking number', 'woocommerce' ),
				'value' => $tracking_number,
			);
		}

		$provider = FulfillmentUtils::get_provider_display_name( $fulfillment );
		if ( '' !== $provider ) {
			$data[] = array(
				'name'  => __( 'Shipment provider', 'woocommerce' ),
				'value' => $provider,
			);
		}

		$tracking_url = $fulfillment->get_tracking_url();
		if ( ! empty( $tracking_url ) ) {
			$data[] = array(
				'name'  => __( 'Tracking URL', 'woocommerce' ),
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
	 * @param mixed $order The order being anonymized.
	 */
	public function remove_fulfillment_personal_data( $order ): void {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		/**
		 * Filters the fulfillment meta keys redacted during personal-data erasure.
		 *
		 * @since 11.4.0
		 *
		 * @param string[] $meta_keys The meta keys to clear.
		 * @param WC_Order $order     The order being anonymized.
		 */
		$meta_keys = apply_filters(
			'woocommerce_privacy_remove_fulfillment_personal_data_meta',
			array( '_tracking_number', '_tracking_url' ),
			$order
		);

		if ( ! is_array( $meta_keys ) ) {
			return;
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
