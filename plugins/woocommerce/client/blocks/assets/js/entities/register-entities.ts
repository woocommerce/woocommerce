/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { PRODUCT_ENTITY } from './product/constants';

const registered: string[] = [];

export const registerProductEntity = () => {
	if ( registered.includes( PRODUCT_ENTITY.name ) ) {
		return;
	}
	const { addEntities } = dispatch( coreStore );
	addEntities( [ PRODUCT_ENTITY ] );
	registered.push( PRODUCT_ENTITY.name );
};
