/**
 * External dependencies
 */
import { createElement, createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';

type ModernSettingsNavigationItem = {
	id: string;
	label: string;
	href: string;
	active?: boolean;
};

type ModernSettingsSchema = {
	id: string;
	section?: string;
	shell?: {
		title?: string;
		navigation?: ModernSettingsNavigationItem[];
		sectionNavigation?: ModernSettingsNavigationItem[];
	};
	groups: Record< string, unknown >;
};

declare global {
	interface Window {
		wc?: {
			modernSettingsSdk?: {
				ModernSettingsPage: ( props: {
					schema: ModernSettingsSchema;
					page: string;
					section?: string;
				} ) => JSX.Element | null;
			};
		};
	}
}

const getSchema = (
	page: string,
	section: string
): ModernSettingsSchema | undefined => {
	const settings = getAdminSetting( 'modernSettings', {} );
	const sectionKey = section || 'default';
	return settings?.[ page ]?.[ sectionKey ];
};

export const registerModernSettingsScreens = () => {
	const ModernSettingsPage = window.wc?.modernSettingsSdk?.ModernSettingsPage;

	if ( ! ModernSettingsPage ) {
		if (
			document.querySelector< HTMLElement >(
				'[data-wc-modern-settings="1"]'
			)
		) {
			// eslint-disable-next-line no-console
			console.warn(
				'[WooCommerce modern settings] SDK script is missing.'
			);
		}
		return;
	}

	document
		.querySelectorAll< HTMLElement >( '[data-wc-modern-settings="1"]' )
		.forEach( ( element ) => {
			const page = element.dataset.wcSettingsPage || '';
			const section = element.dataset.wcSettingsSection || '';
			const schema = getSchema( page, section );

			if ( ! schema ) {
				// eslint-disable-next-line no-console
				console.warn(
					'[WooCommerce modern settings] Settings payload is missing.',
					{ page, section }
				);
				return;
			}

			createRoot( element ).render(
				createElement( ModernSettingsPage, {
					schema,
					page,
					section: section || schema.section,
				} )
			);
		} );
};
