<?php
/**
 * A customer with one completed order, a customer without orders and a subscriber, registered a day apart in that order.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

/**
 * @return array{buyer: int, browser: int, subscriber: int}
 */
return static function (): array {
	$users = array(
		'buyer'      => array( 'customer', '2024-01-01 09:00:00' ),
		'browser'    => array( 'customer', '2024-01-02 09:00:00' ),
		'subscriber' => array( 'subscriber', '2024-01-03 09:00:00' ),
	);

	$ids = array();
	foreach ( $users as $key => list( $role, $registered ) ) {
		$login = "mcp-customer-{$key}";
		$user  = get_user_by( 'login', $login );
		$id    = $user ? $user->ID : wp_create_user( $login, wp_generate_password(), "{$login}@fixtures.mcp.test" );

		wp_update_user(
			array(
				'ID'              => $id,
				'role'            => $role,
				'user_registered' => $registered,
			)
		);
		$ids[ $key ] = $id;
	}

	if ( ! wc_get_orders(
		array(
			'customer_id' => $ids['buyer'],
			'limit'       => 1,
			'return'      => 'ids',
		)
	) ) {
		$order = wc_create_order( array( 'customer_id' => $ids['buyer'] ) );
		$order->set_total( '25.00' );
		$order->set_status( 'completed' );
		$order->save();
	}

	foreach ( $ids as $key => $id ) {
		$customer = new WC_Customer( $id );
		$customer->set_is_paying_customer( 'buyer' === $key );
		$customer->save();
		// The order count and total spent are cached in user meta.
		delete_user_meta( $id, '_order_count' );
		delete_user_meta( $id, '_money_spent' );
	}

	return $ids;
};
