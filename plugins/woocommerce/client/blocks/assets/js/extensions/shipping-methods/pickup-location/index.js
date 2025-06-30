/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SettingsPage from './settings-page';

const settingsContainer = document.getElementById(
	'wc-shipping-method-pickup-location-settings-container'
);

if ( settingsContainer ) {
	const root = createRoot( settingsContainer );
	root.render( <SettingsPage /> );
}
