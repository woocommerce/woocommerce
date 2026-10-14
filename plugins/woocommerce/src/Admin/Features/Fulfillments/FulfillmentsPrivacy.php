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
 * Export adds the customer's fulfillment records to their personal-data export.
 * Erasure redacts the tracking meta whenever an order is anonymized, which covers
 * both the admin "Erase Personal Data" tool and the automatic retention cleanup,
 * since both route through {@see \WC_Privacy_Erasers::remove_order_personal_data()}.
 *
 * Redaction writes directly to the core fulfillment tables; a data store swapped in
 * through the `woocommerce_data_stores` filter is responsible for its own erasure.
 *
 * @since 11.3.0
 */
class FulfillmentsPrivacy {

	/**
	 * Register the privacy hooks.
	 */
	public function register(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
		add_action( 'woocommerce_privacy_remove_order_personal_data', array( $this, 'remove_fulfillment_personal_data' ) );
	}

	/**
	 * Register the fulfillment personal-data exporter.
	 *
	 * @param array $exporters Registered exporters.
	 * @return array
	 */
	public function register_exporter( array $exporters ): array {
		$exporters['woocommerce-customer-fulfillments'] = array(
			'exporter_friendly_name' => __( 'WooCommerce Customer Fulfillments', 'woocommerce' ),
			'callback'               => array( $this, 'fulfillment_data_exporter' ),
		);

		return $exporters;
	}

	/**
	 * Export fulfillment tracking data for a customer, paginated by order.
	 *
	 * @param string $email_address The customer email address.
	 * @param int    $page          Page number (1-based).
	 * @return array{data: array, done: bool}
	 */
	public function fulfillment_data_exporter( string $email_address, int $page = 1 ): array {
		$page           = max( 1, $page );
		$user           = get_user_by( 'email', $email_address );
		$data_to_export = array();

		$order_query = array(
			'limit'    => 10,
			'page'     => $page,
			'customer' => array( $email_address ),
		);
		if ( $user instanceof \WP_User ) {
			$order_query['customer'][] = (int) $user->ID;
		}

		$orders = wc_get_orders( $order_query );
		$orders = is_array( $orders ) ? $orders : array();

		try {
			/**
			 * Fulfillments data store.
			 *
			 * @var FulfillmentsDataStore $datastore
			 */
			$datastore = WC_Data_Store::load( 'order-fulfillment' );

			foreach ( $orders as $order ) {
				$fulfillments = $datastore->read_fulfillments( WC_Order::class, (string) $order->get_id() );

				foreach ( $fulfillments as $fulfillment ) {
					$data = $this->get_fulfillment_personal_data( $fulfillment, $order );
					if ( empty( $data ) ) {
						continue;
					}

					$data_to_export[] = array(
						'group_id'          => 'woocommerce_customer_fulfillments',
						'group_label'       => __( 'Fulfillments', 'woocommerce' ),
						'group_description' => __( 'User&#8217;s WooCommerce fulfillment (shipment) data.', 'woocommerce' ),
						'item_id'           => 'fulfillment-' . $fulfillment->get_id(),
						'data'              => $data,
					);
				}
			}
		} catch ( \Throwable $e ) {
			wc_get_logger()->error(
				'Failed to export fulfillment personal data: ' . $e->getMessage(),
				array( 'source' => 'fulfillments' )
			);
		}

		return array(
			'data' => $data_to_export,
			'done' => count( $orders ) < 10,
		);
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
			$order_item = $item_id > 0 ? $order->get_item( $item_id ) : false;
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

		$provider = FulfillmentUtils::resolve_provider_name( $fulfillment );
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
		 * @since 11.3.0
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
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}wc_order_fulfillment_meta AS m
				INNER JOIN {$wpdb->prefix}wc_order_fulfillments AS f ON f.fulfillment_id = m.fulfillment_id
				SET m.meta_value = %s
				WHERE f.entity_type = %s AND f.entity_id = %s AND m.meta_key IN ( {$placeholders} )",
				$params
			)
		);
		// phpcs:enable
	}
}
