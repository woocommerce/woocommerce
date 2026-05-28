/**
 * External dependencies
 */
import { createElement, createRoot } from '@wordpress/element';
import type { SettingsUISchema } from '@woocommerce/settings-ui-sdk';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';

declare global {
	interface Window {
		wc?: {
			settingsUiSdk?: {
				SettingsUIPage: ( props: {
					schema: SettingsUISchema;
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
): SettingsUISchema | undefined => {
	const settings = getAdminSetting( 'settingsUI', {} );
	const sectionKey = section || 'default';
	return settings?.[ page ]?.[ sectionKey ];
};

export const registerSettingsUIScreens = () => {
	const SettingsUIPage = window.wc?.settingsUiSdk?.SettingsUIPage;

	if ( ! SettingsUIPage ) {
		if (
			document.querySelector< HTMLElement >( '[data-wc-settings-ui="1"]' )
		) {
			// eslint-disable-next-line no-console
			console.warn( '[WooCommerce settings UI] SDK script is missing.' );
		}
		return;
	}

	document
		.querySelectorAll< HTMLElement >( '[data-wc-settings-ui="1"]' )
		.forEach( ( element ) => {
			const page = element.dataset.wcSettingsPage || '';
			const section = element.dataset.wcSettingsSection || '';
			const schema = getSchema( page, section );

			if ( ! schema ) {
				// eslint-disable-next-line no-console
				console.warn(
					'[WooCommerce settings UI] Settings payload is missing.',
					{ page, section }
				);
				return;
			}

			createRoot( element ).render(
				createElement( SettingsUIPage, {
					schema,
					page,
					section: section || schema.section,
				} )
			);
		} );
};
