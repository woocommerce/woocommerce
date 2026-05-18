import { dispatch } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { WC_ORDER_ENTITY } from './constants';

const registered = new Set< string >();

/**
 * Registers the WooCommerce order entity with `@wordpress/core-data`.
 * Idempotent — safe to call multiple times from different boot paths.
 */
export function registerWooCommerceOrderEntity(): void {
	const key = `${ WC_ORDER_ENTITY.kind }/${ WC_ORDER_ENTITY.name }`;
	if ( registered.has( key ) ) {
		return;
	}

	const { addEntities } = dispatch( coreStore );
	void addEntities( [ WC_ORDER_ENTITY ] );
	registered.add( key );
}
