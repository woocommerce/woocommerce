/**
 * Internal dependencies
 */
import { initRemoteLogging } from './lib/init-remote-logging';
// Initialize remote logging early to log any errors that occur during initialization.
initRemoteLogging();

/**
 * Internal dependencies
 */
import './stylesheets/_embed.scss';
import { renderCustomerEffortScoreTracks } from './shared';
import { getAdminSetting } from '~/utils/admin-settings';
import { renderEmbeddedLayout } from './embedded-body-layout';

const embeddedRoot = document.getElementById( 'woocommerce-embedded-root' );

if ( embeddedRoot ) {
	const settingsGroup = 'wc_admin';
	const hydrateUser = getAdminSetting( 'currentUserData' );

	renderEmbeddedLayout( embeddedRoot, hydrateUser, settingsGroup );
	renderCustomerEffortScoreTracks( embeddedRoot );
}
