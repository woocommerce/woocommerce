/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { App } from './app';
import './index.scss';
import { default as renderRecentTracksEventsRoot } from './recent-tracks-events/render-recent-tracks-events-root';

const appRoot = document.getElementById(
	'woocommerce-admin-test-helper-app-root'
);

if ( appRoot ) {
	render( <App />, appRoot );
}

renderRecentTracksEventsRoot();
