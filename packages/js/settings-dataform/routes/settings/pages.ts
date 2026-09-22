/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { resolveSelect } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { SETTINGS_ENTITY } from '@woocommerce-settings-ui-experimental/settings-ui';

export type SettingsPage = {
	id: string;
	label: string;
	url: string;
	entity?: { kind: string; name: string; baseURL: string };
};

export type SettingsRouteOptions = {
	params: { page?: string };
};

export const SETTINGS_PAGES_ENTITY = {
	kind: 'woo_settings',
	name: 'page',
	baseURL: '/wc/v4/settings/pages',
};

export const SETTINGS_PAGES_ARGS = [
	SETTINGS_PAGES_ENTITY.kind,
	SETTINGS_PAGES_ENTITY.name,
	{ per_page: -1 },
] as const;

export function getRegisteredSettingsPages(
	pages: SettingsPage[]
): SettingsPage[] {
	return applyFilters(
		'woocommerce.settingsDataform.pages',
		pages
	) as SettingsPage[];
}

export async function loadSettingsPages(): Promise< SettingsPage[] > {
	const pages = await resolveSelect( coreStore ).getEntityRecords(
		...SETTINGS_PAGES_ARGS
	);
	if ( ! pages ) {
		throw new Error(
			__( 'Unable to load settings pages.', 'woocommerce' )
		);
	}
	return getRegisteredSettingsPages( pages as SettingsPage[] );
}

export function getSettingsPage(
	pages: SettingsPage[],
	{ params }: SettingsRouteOptions
) {
	return pages.find( ( item ) => item.id === params.page );
}

export function getSettingsEntity( page: SettingsPage ) {
	return {
		kind: page.entity?.kind ?? SETTINGS_ENTITY.kind,
		name: page.entity?.name ?? page.id,
		baseURL:
			page.entity?.baseURL ??
			`/wc/v4/settings/${ encodeURIComponent( page.id ) }`,
		key: false,
	};
}

export type SettingsEntity = ReturnType< typeof getSettingsEntity >;

export function getLegacySettingsEntity( page: SettingsPage ) {
	return {
		kind: 'woo_settings',
		name: `legacy-${ page.id }`,
		baseURL: `/wc/v4/settings/${ encodeURIComponent(
			page.id
		) }?legacy-view-config=1`,
		key: false,
	};
}

export type LegacySettingsEntity = ReturnType< typeof getLegacySettingsEntity >;

export function hasSettingsForm( page: SettingsPage ) {
	return !! page.entity || page.id === 'products';
}
