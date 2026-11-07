<?php
/**
 * An order of its own per `key`, so a test that changes it leaves other tests' orders alone.
 *
 * Every seed resets its status and replaces its notes, including the ones status changes add.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

/**
 * @param array{key: string, status?: string, notes?: list<array{note: string, customer_note?: bool}>} $args
 * @return array{id: int, billing_email: string}
 */
return static function ( array $args ): array {
	$email = $args['key'] . '@fixtures.mcp.test';
	$ids   = wc_get_orders(
		array(
			'billing_email' => $email,
			'limit'         => 1,
			'return'        => 'ids',
		)
	);
	$order = $ids ? wc_get_order( $ids[0] ) : wc_create_order();

	$order->set_billing_email( $email );
	$order->set_status( $args['status'] ?? 'pending' );
	$order->save();

	foreach ( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ) as $note ) {
		wc_delete_order_note( $note->id );
	}

	// Each customer note would also email the customer, and record the attempt as one more note.
	add_filter( 'woocommerce_email_enabled_customer_note', '__return_false' );
	foreach ( $args['notes'] ?? array() as $note ) {
		$order->add_order_note( $note['note'], (int) ! empty( $note['customer_note'] ) );
	}

	return array(
		'id'            => $order->get_id(),
		'billing_email' => $email,
	);
};
