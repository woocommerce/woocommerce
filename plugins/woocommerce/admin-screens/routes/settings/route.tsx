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
import type { SettingsEntityConfig } from './entities';
import { registerEntityFields } from './entity-fields';

interface RouteParams {
	params: { section?: string; page?: string };
}

function getPageConfig( {
	params,
}: RouteParams ): SettingsEntityConfig | undefined {
	const section = getSection( params.section );
	if ( ! section || ! params.page ) {
		return undefined;
	}
	return select( coreStore ).getEntityConfig( section.kind, params.page ) as
		SettingsEntityConfig | undefined;
}

export const route = {
	beforeLoad: async ( context: RouteParams ) => {
		await dispatch( coreStore ).addEntities( getCoreEntities() );
		registerEntityFields( ENTITY_KIND, 'cheque', chequeFields );
		if ( ! getPageConfig( context ) ) {
			throw notFound();
		}
	},
	title: ( context: RouteParams ) => getPageConfig( context )?.label,
};
