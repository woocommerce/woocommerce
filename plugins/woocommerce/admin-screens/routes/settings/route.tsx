/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { dispatch, select } from '@wordpress/data';
import { notFound } from '@wordpress/route';

/**
 * Internal dependencies
 */
import { ENTITY_KIND, getCoreEntities } from './entities';

export const route = {
	beforeLoad: async ( { params }: { params: { page?: string } } ) => {
		await dispatch( coreStore ).addEntities( getCoreEntities() );
		if (
			! params.page ||
			! select( coreStore ).getEntityConfig( ENTITY_KIND, params.page )
		) {
			throw notFound();
		}
	},
};
