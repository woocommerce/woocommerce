/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { default as RecentTracksEvents } from './recent-tracks-events';

function renderRecentTracksEventsRoot() {
	const recentTracksEventsRoot = document.createElement( 'div' );
	recentTracksEventsRoot.id = 'wc-beta-tester-recent-tracks-events';
	document.body.append( recentTracksEventsRoot );
	render( <RecentTracksEvents />, recentTracksEventsRoot );
}

export default renderRecentTracksEventsRoot;
