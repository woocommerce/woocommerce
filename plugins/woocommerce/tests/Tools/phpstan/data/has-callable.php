<?php

use function PHPStan\Testing\assertType;

function has_callable_in_if( WC_Data_Store $store ): void {
	if ( $store->has_callable( 'get_orders_type' ) ) {
		assertType( 'WC_Data_Store&hasMethod(get_orders_type)', $store );
	}
	assertType( 'WC_Data_Store', $store );
}

function has_callable_in_and( WC_Data_Store $store ): void {
	$store->has_callable( 'delete_items_by_ids' ) && assertType( 'WC_Data_Store&hasMethod(delete_items_by_ids)', $store );
}

function has_callable_negated( WC_Data_Store $store ): void {
	if ( ! $store->has_callable( 'get_orders_type' ) ) {
		assertType( 'WC_Data_Store', $store );
	}
}

function has_callable_with_unknown_name( WC_Data_Store $store, string $method ): void {
	if ( $store->has_callable( $method ) ) {
		assertType( 'WC_Data_Store', $store );
	}
}
