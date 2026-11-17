<?php
/**
 * Permanently deletes the orders with the given billing emails, including trashed ones.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

/**
 * @param array{emails: list<string>} $args
 * @return list<int> The deleted order IDs.
 */
return static function ( array $args ): array {
	$ids = array();
	foreach ( $args['emails'] as $email ) {
		$ids = array_merge(
			$ids,
			wc_get_orders(
				array(
					'billing_email' => $email,
					'status'        => array_merge( array_keys( wc_get_order_statuses() ), array( 'trash' ) ),
					'limit'         => -1,
					'return'        => 'ids',
				)
			)
		);
	}

	foreach ( $ids as $id ) {
		wc_get_order( $id )->delete( true );
	}

	return array_map( 'intval', $ids );
};
