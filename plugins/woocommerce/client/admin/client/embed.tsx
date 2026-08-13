/**
 * Internal dependencies
 */
import { initRemoteLogging } from './lib/init-remote-logging';
import { initDateLocale } from './lib/date-locale';
// Initialize remote logging early to log any errors that occur during initialization.
initRemoteLogging();
// Replace the PHP-style moment date formats WordPress core injects with
// moment-style formats and localized weekday names.
initDateLocale();

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
