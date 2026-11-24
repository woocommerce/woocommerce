<?php
/**
 * A customer of its own per `key`, so orders created for it leave the other fixture customers alone.
 *
 * Every seed clears its name and addresses, and writes it to the Analytics customer lookup, which the customer abilities read.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore as CustomersDataStore;

/**
 * @param array{key: string} $args
 * @return array{id: int, customer_id: int, email: string}
 */
return static function ( array $args ): array {
	$login = "mcp-{$args['key']}";
	$email = "{$login}@fixtures.mcp.test";
	$user  = get_user_by( 'login', $login );
	$id    = $user ? $user->ID : wp_create_user( $login, wp_generate_password(), $email );

	wp_update_user(
		array(
			'ID'   => $id,
			'role' => 'customer',
		)
	);

	$customer = new WC_Customer( $id );
	$customer->set_first_name( '' );
	$customer->set_last_name( '' );
	foreach ( array( 'billing', 'shipping' ) as $type ) {
		foreach ( array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'phone' ) as $field ) {
			$customer->{"set_{$type}_{$field}"}( '' );
		}
	}
	$customer->set_billing_email( $email );
	$customer->save();
	CustomersDataStore::update_registered_customer( $id );

	return array(
		'id'          => $id,
		'customer_id' => (int) CustomersDataStore::get_customer_id_by_user_id( $id ),
		'email'       => $email,
	);
};
