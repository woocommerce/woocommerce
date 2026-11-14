<?php
/**
 * One order per status, sharing a billing email so a query can be limited to them.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

/**
 * @return array{billing_email: string, ids: array<string, int>} The order IDs by status.
 */
return static function (): array {
	$email = 'orders@fixtures.mcp.test';
	$ids   = array();

	foreach ( array( 'processing', 'completed', 'on-hold' ) as $status ) {
		$found = wc_get_orders(
			array(
				'billing_email' => $email,
				'status'        => $status,
				'limit'         => 1,
				'return'        => 'ids',
			)
		);
		if ( $found ) {
			$ids[ $status ] = $found[0];
			continue;
		}

		$order = wc_create_order();
		$order->set_billing_email( $email );
		$order->set_status( $status );
		$ids[ $status ] = $order->save();
	}

	return array(
		'billing_email' => $email,
		'ids'           => $ids,
	);
};
