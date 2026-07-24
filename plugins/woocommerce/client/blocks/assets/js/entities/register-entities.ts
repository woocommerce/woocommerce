/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { PRODUCT_ENTITY } from './product/constants';
import { SETTINGS_ENTITY } from './settings/constants';

const registered: string[] = [];

export const registerProductEntity = () => {
	if ( registered.includes( PRODUCT_ENTITY.name ) ) {
		return;
	}
	const { addEntities } = dispatch( coreStore );
	void addEntities( [ PRODUCT_ENTITY ] );
	registered.push( PRODUCT_ENTITY.name );
};

export const registerSettingsEntity = () => {
	if ( registered.includes( SETTINGS_ENTITY.name ) ) {
		return;
	}
	const { addEntities } = dispatch( coreStore );
	void addEntities( [ SETTINGS_ENTITY ] );
	registered.push( SETTINGS_ENTITY.name );
};
