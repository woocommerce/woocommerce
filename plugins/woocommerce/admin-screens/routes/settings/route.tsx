/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { dispatch, select } from '@wordpress/data';
import { notFound } from '@wordpress/route';

/**
 * Internal dependencies
 */
import { getCoreEntities, getEntityKind } from './entities';
import type { SettingsEntityConfig } from './entities';

interface RouteParams {
	params: { section?: string; page?: string };
}

function getPageConfig( {
	params,
}: RouteParams ): SettingsEntityConfig | undefined {
	if ( ! params.section || ! params.page ) {
		return undefined;
	}
	return select( coreStore ).getEntityConfig(
		getEntityKind( params.section ),
		params.page
	) as SettingsEntityConfig | undefined;
}

export const route = {
	beforeLoad: async ( context: RouteParams ) => {
		await dispatch( coreStore ).addEntities( getCoreEntities() );
		if ( ! getPageConfig( context ) ) {
			throw notFound();
		}
	},
	title: ( context: RouteParams ) => getPageConfig( context )?.label,
};
