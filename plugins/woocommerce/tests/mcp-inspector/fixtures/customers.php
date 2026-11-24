<?php
/**
 * Two registered customers, a buyer with a completed order and a browser without one, and a guest with a completed order.
 *
 * Analytics imports orders and customers in the background, so the fixture writes them to its lookup tables itself.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore as CustomersDataStore;
use Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler;

/**
 * @return array<string, array{id: int, user_id?: int, email: string}>
 */
return static function (): array {
	$users = array(
		'buyer'   => array( 'Buyer', '2024-01-01 09:00:00', '2024-02-01 09:00:00' ),
		'browser' => array( 'Browser', '2024-01-02 09:00:00', '2024-02-02 09:00:00' ),
	);

	$customers = array();
	foreach ( $users as $key => list( $last_name, $registered, $last_active ) ) {
		$login = "mcp-customer-{$key}";
		$email = "{$login}@fixtures.mcp.test";
		$user  = get_user_by( 'login', $login );
		$id    = $user ? $user->ID : wp_create_user( $login, wp_generate_password(), $email );

		wp_update_user(
			array(
				'ID'              => $id,
				'role'            => 'customer',
				'first_name'      => 'MCP',
				'last_name'       => $last_name,
				'user_registered' => $registered,
			)
		);
		update_user_meta( $id, 'wc_last_active', (string) strtotime( $last_active . ' UTC' ) );
		CustomersDataStore::update_registered_customer( $id );

		$customers[ $key ] = array(
			'user_id' => $id,
			'email'   => $email,
		);
	}

	$guest_email = 'mcp-customer-guest@fixtures.mcp.test';
	$orders      = array(
		'buyer' => array( $customers['buyer']['email'], $customers['buyer']['user_id'], '25.00', '2024-03-01 09:00:00' ),
		'guest' => array( $guest_email, 0, '12.00', '2024-03-02 09:00:00' ),
	);
	foreach ( $orders as list( $email, $user_id, $total, $date ) ) {
		$ids   = wc_get_orders(
			array(
				'billing_email' => $email,
				'limit'         => 1,
				'return'        => 'ids',
			)
		);
		$order = $ids ? wc_get_order( $ids[0] ) : wc_create_order( array( 'customer_id' => $user_id ) );
		$order->set_billing_email( $email );
		$order->set_billing_first_name( 'MCP' );
		$order->set_billing_last_name( 'Guest' );
		$order->set_total( $total );
		$order->set_date_created( $date );
		$order->set_status( 'completed' );
		$order->save();
		OrdersScheduler::import( $order->get_id() );
	}

	foreach ( $customers as $key => $customer ) {
		$customers[ $key ]['id'] = (int) CustomersDataStore::get_customer_id_by_user_id( $customer['user_id'] );
	}
	$customers['guest'] = array(
		'id'    => (int) CustomersDataStore::get_guest_id_by_email( $guest_email ),
		'email' => $guest_email,
	);

	return $customers;
};
