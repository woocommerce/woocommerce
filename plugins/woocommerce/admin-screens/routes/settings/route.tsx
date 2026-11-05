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
import { ENTITY_KIND, getCoreEntities, getSection } from './entities';
import { registerEntityFields } from './entity-fields';

export const route = {
	beforeLoad: async ( {
		params,
	}: {
		params: { section?: string; page?: string };
	} ) => {
		await dispatch( coreStore ).addEntities( getCoreEntities() );
		registerEntityFields( ENTITY_KIND, 'cheque', chequeFields );
		const section = getSection( params.section );
		if (
			! section ||
			! params.page ||
			! select( coreStore ).getEntityConfig( section.kind, params.page )
		) {
			throw notFound();
		}
	},
};
