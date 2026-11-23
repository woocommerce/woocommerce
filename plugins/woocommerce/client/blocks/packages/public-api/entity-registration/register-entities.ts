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
 * No-op. Its REST backing was the removed experimental wc/v4 settings endpoints; kept exported so
 * the deprecated `wc.wcEntities.registerSettingsEntity()` compatibility wrapper doesn't fatal.
 */
export const registerSettingsEntity = () => {};
