/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { dispatch, resolveSelect } from '@wordpress/data';
import { notFound } from '@wordpress/route';
import { registerProductFields } from '@woocommerce-settings-ui-experimental/settings-ui';

/**
 * Internal dependencies
 */
import {
	SETTINGS_PAGES_ENTITY,
	loadSettingsPages,
	getSettingsPage,
	getSettingsEntity,
	hasSettingsForm,
} from './pages';
import type { SettingsRouteOptions } from './pages';

export const route = {
	beforeLoad: async ( options: SettingsRouteOptions ) => {
		await dispatch( coreStore ).addEntities( [ SETTINGS_PAGES_ENTITY ] );
		const pages = await loadSettingsPages();
		await dispatch( coreStore ).addEntities(
			pages.map( getSettingsEntity )
		);
		const page = getSettingsPage( pages, options );
		if ( ! page ) {
			throw notFound();
		}
		if ( hasSettingsForm( page ) ) {
			registerProductFields( getSettingsEntity( page ) );
		}
	},
	loader: async ( options: SettingsRouteOptions ) => {
		const pages = await loadSettingsPages();
		const page = getSettingsPage( pages, options );
		if ( ! page || ! hasSettingsForm( page ) ) {
			return { page };
		}
		const { getEntityRecord } = resolveSelect( coreStore );
		const entity = getSettingsEntity( page );

		// Let the stage handle request errors and missing form configuration.
		await Promise.allSettled( [
			getEntityRecord( entity.kind, entity.name, undefined ),
		] );
		return { page };
	},
};
