/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { PRODUCT_ENTITY } from '../../internal/entities/product/constants';

const registered: string[] = [];

export const registerProductEntity = () => {
	if ( registered.includes( PRODUCT_ENTITY.name ) ) {
		return;
	}
	const { addEntities } = dispatch( coreStore );
	void addEntities( [ PRODUCT_ENTITY ] );
	registered.push( PRODUCT_ENTITY.name );
};

/**
 * No-op. The v4 settings REST endpoints it depended on were removed.
 *
 * Kept exported so the deprecated `wc.wcEntities.registerSettingsEntity()` compatibility
 * wrapper does not fatal.
 *
 * @deprecated Since WooCommerce 11.5.0. Will be removed in a future version.
 */
export const registerSettingsEntity = () => {};
