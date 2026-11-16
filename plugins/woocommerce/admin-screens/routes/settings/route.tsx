/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { dispatch, select } from '@wordpress/data';
import { notFound } from '@wordpress/route';

/**
 * Internal dependencies
 */
import chequeFields from '../../entity-fields/cheque';
import { ENTITY_KIND, getCoreEntities } from './entities';
import { registerEntityFields } from './entity-fields';

export const route = {
	beforeLoad: async ( { params }: { params: { page?: string } } ) => {
		await dispatch( coreStore ).addEntities( getCoreEntities() );
		registerEntityFields( ENTITY_KIND, 'cheque', chequeFields );
		if (
			! params.page ||
			! select( coreStore ).getEntityConfig( ENTITY_KIND, params.page )
		) {
			throw notFound();
		}
	},
};
