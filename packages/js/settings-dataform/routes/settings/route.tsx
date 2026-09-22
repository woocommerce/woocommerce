/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { dispatch, resolveSelect } from '@wordpress/data';
import { doAction } from '@wordpress/hooks';
import { store as editorStore } from '@wordpress/editor';
import { notFound } from '@wordpress/route';
import {
	registerProductFields,
	unlock,
} from '@woocommerce-settings-ui-experimental/settings-ui';

/**
 * Internal dependencies
 */
import {
	SETTINGS_PAGES_ENTITY,
	loadSettingsPages,
	getSettingsPage,
	getSettingsEntity,
	getLegacySettingsEntity,
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
		await dispatch( coreStore ).addEntities(
			pages
				.filter( ( item ) => ! item.entity )
				.map( getLegacySettingsEntity )
		);
		const page = getSettingsPage( pages, options );
		if ( ! page ) {
			throw notFound();
		}
		if ( page.id === 'products' && ! page.entity ) {
			registerProductFields( getSettingsEntity( page ) );
		}
		doAction(
			'woocommerce.settingsDataform.registerFields',
			getSettingsEntity( page ),
			unlock( dispatch( editorStore ) ).registerEntityField
		);
	},
	loader: async ( options: SettingsRouteOptions ) => {
		const pages = await loadSettingsPages();
		const page = getSettingsPage( pages, options );
		if ( ! page ) {
			return { page };
		}
		const { getEntityRecord } = resolveSelect( coreStore );
		const entity = getSettingsEntity( page );
		const legacyEntity = getLegacySettingsEntity( page );

		// Let the stage handle request errors and missing form configuration.
		await Promise.allSettled( [
			...( page.entity
				? []
				: [
						getEntityRecord(
							legacyEntity.kind,
							legacyEntity.name,
							undefined
						),
				  ] ),
			...( hasSettingsForm( page )
				? [ getEntityRecord( entity.kind, entity.name, undefined ) ]
				: [] ),
		] );
		return { page };
	},
};
