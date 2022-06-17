<?php

namespace Automattic\WooCommerce\Internal\GraphQL;

/**
 * Extremely simplified to retrieve orders and order addresses from the custom order tables.
 */
class OrderRetriever {

	public function retrieve_order( int $id, array $fields = array( '*' ) ): array {
		global $wpdb;

		$fields = join( ',', $fields );
		$sql    = "select $fields from {$wpdb->prefix}wc_orders where id=$id";

		return $wpdb->get_row( $sql, ARRAY_A );
	}

	public function retrieve_address( int $order_id, string $address_type, array $fields = array( '*' ) ): array {
		global $wpdb;

		$fields = join( ',', $fields );
		$sql    = $wpdb->prepare( "select $fields from {$wpdb->prefix}wc_order_addresses where order_id=$order_id and address_type=%s", $address_type );

		return $wpdb->get_row( $sql, ARRAY_A );
	}
}
