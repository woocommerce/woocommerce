/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';
import {
	withCurrentUserHydration,
	withSettingsHydration,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { initRemoteLogging } from './lib/init-remote-logging';
// Initialize remote logging early to log any errors that occur during initialization.
initRemoteLogging();

/**
 * Internal dependencies
 */
import './stylesheets/_index.scss';
import './xstate.js';

import { renderCustomerEffortScoreTracks } from './shared';
import { Layout } from './layout';
import { getAdminSetting } from '~/utils/admin-settings';
import { deriveWpAdminBackgroundColours } from './utils/derive-wp-admin-background-colours';
import { ErrorBoundary } from './error-boundary';

const appRoot = document.getElementById( 'root' );

deriveWpAdminBackgroundColours();

if ( appRoot ) {
	// Hydrate the page layout with the settings and current user so that the layout is ready to render immediately.
	const settingsGroup = 'wc_admin';
	let HydratedPageLayout = withSettingsHydration(
		settingsGroup,
		window.wcSettings.admin
	)( Layout );

	const preloadGeneralSettings =
		window.wcSettings.admin?.preloadSettings?.general || false;
	if ( preloadGeneralSettings ) {
		HydratedPageLayout = withSettingsHydration( 'general', {
			general: preloadGeneralSettings,
		} )( HydratedPageLayout );
	}

	const hydrateUser = getAdminSetting( 'currentUserData' );
	if ( hydrateUser ) {
		HydratedPageLayout =
			withCurrentUserHydration( hydrateUser )( HydratedPageLayout );
	}

	// Render the App.
	createRoot( appRoot ).render(
		<ErrorBoundary>
			<HydratedPageLayout />
		</ErrorBoundary>
	);

	// Render the Customer Effort Score Tracks.
	renderCustomerEffortScoreTracks( appRoot );
}
