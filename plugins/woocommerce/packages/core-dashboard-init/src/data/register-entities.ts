import { dispatch } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import type { Entity } from './types';
import {
	WC_CUSTOMER_ENTITY,
	WC_ORDER_ENTITY,
	WC_PRODUCT_REVIEW_ENTITY,
} from './constants';

const registered = new Set< string >();

/**
 * Idempotent register helper for a single entity. Safe to call from multiple
 * boot paths — subsequent calls for the same `{kind}/{name}` are no-ops.
 */
function registerEntity( entity: Entity ): void {
	const key = `${ entity.kind }/${ entity.name }`;
	if ( registered.has( key ) ) {
		return;
	}

	const { addEntities } = dispatch( coreStore );
	void addEntities( [ entity ] );
	registered.add( key );
}

/**
 * Registers the WooCommerce order entity with `@wordpress/core-data`.
 */
export function registerWooCommerceOrderEntity(): void {
	registerEntity( WC_ORDER_ENTITY );
}

/**
 * Registers the WooCommerce product-review entity with `@wordpress/core-data`.
 */
export function registerWooCommerceProductReviewEntity(): void {
	registerEntity( WC_PRODUCT_REVIEW_ENTITY );
}

/**
 * Registers the WooCommerce customer entity with `@wordpress/core-data`.
 */
export function registerWooCommerceCustomerEntity(): void {
	registerEntity( WC_CUSTOMER_ENTITY );
}
