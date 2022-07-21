/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { App } from './app';
import './index.scss';
import { default as RecentTracksEventsViewer } from './tracks/recent-tracks-events-viewer';
import { hookupLoggingOfRecentClientTracksEvents } from './tracks/recent-tracks-events';

hookupLoggingOfRecentClientTracksEvents();

const appRoot = document.getElementById(
	'woocommerce-admin-test-helper-app-root'
);

if ( appRoot ) {
	render( <App />, appRoot );
}

const recentTracksEventsViewerRoot = document.createElement( 'div' );
recentTracksEventsViewerRoot.id = 'wc-beta-tester-recent-tracks-events-viewer';
document.body.append( recentTracksEventsViewerRoot );
render( <RecentTracksEventsViewer />, recentTracksEventsViewerRoot );
