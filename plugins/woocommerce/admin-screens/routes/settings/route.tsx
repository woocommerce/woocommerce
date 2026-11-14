/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { dispatch, select } from '@wordpress/data';
import { notFound } from '@wordpress/route';

/**
 * Internal dependencies
 */
import getBacsFields from '../../entity-fields/bacs';
import getChequeFields from '../../entity-fields/cheque';
import getCodFields from '../../entity-fields/cod';
import {
	ENTITY_KIND,
	getCoreEntities,
	getCoreRelatedEntities,
} from './entities';
import { registerEntityFields } from './entity-fields';

export const route = {
	beforeLoad: async ( { params }: { params: { page?: string } } ) => {
		await dispatch( coreStore ).addEntities( [
			...getCoreEntities(),
			...getCoreRelatedEntities(),
		] );
		registerEntityFields(
			ENTITY_KIND,
			'bacs',
			getBacsFields( ENTITY_KIND )
		);
		registerEntityFields(
			ENTITY_KIND,
			'cheque',
			getChequeFields( ENTITY_KIND )
		);
		registerEntityFields( ENTITY_KIND, 'cod', getCodFields( ENTITY_KIND ) );
		if (
			! params.page ||
			! select( coreStore ).getEntityConfig( ENTITY_KIND, params.page )
		) {
			throw notFound();
		}
	},
};
