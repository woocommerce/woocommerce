/**
 * External dependencies
 */
import { createRoot, useEffect, useLayoutEffect } from '@wordpress/element';
import {
	SettingsEditor,
	useActiveRoute,
	RouterProvider,
} from '@woocommerce/settings-editor';

/**
 * Internal dependencies
 */
import { possiblyRenderSettingsSlots } from './settings-slots';
import { registerTaxSettingsConflictErrorFill } from './conflict-error-slotfill';
import { registerPaymentsSettingsBannerFill } from '../payments/payments-settings-banner-slotfill';
import { registerSiteVisibilitySlotFill } from '../launch-your-store';
import './settings.scss';

const node = document.getElementById( 'wc-settings-page' );

const appendSettingsScripts = ( scripts ) => {
	return scripts.map( ( script ) => {
		const scriptElement = document.createElement( 'script' );
		scriptElement.src = script;
		scriptElement.onerror = () => {
			// eslint-disable-next-line no-console
			console.error( `Failed to load script: ${ script }` );
		};
		document.body.appendChild( scriptElement );
		return scriptElement;
	} );
};

const removeSettingsScripts = ( scripts ) => {
	scripts.forEach( ( script ) => {
		document.body.removeChild( script );
	} );
};

const SETTINGS_SCRIPTS = window.wcSettings?.admin?.settingsScripts || [];

registerTaxSettingsConflictErrorFill();
registerPaymentsSettingsBannerFill();
registerSiteVisibilitySlotFill();

const Settings = () => {
	const { activePage, activeSection } = useActiveRoute();

	useLayoutEffect( () => {
		const scripts = Array.from(
			new Set( [
				...( SETTINGS_SCRIPTS._default || [] ),
				...( SETTINGS_SCRIPTS[ activePage ] || [] ),
			] )
		);

		const scriptsElements = appendSettingsScripts( scripts );

		return () => {
			removeSettingsScripts( scriptsElements );
		};
	}, [ activePage, activeSection ] );

	// Render the settings slots every time the page or section changes.
	useEffect( () => {
		possiblyRenderSettingsSlots();
	}, [ activePage, activeSection ] );

	return <SettingsEditor />;
};

if ( node ) {
	createRoot( node ).render(
		<RouterProvider>
			<Settings />
		</RouterProvider>
	);
}
