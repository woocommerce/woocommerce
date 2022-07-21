/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

async function logRecentTracksEvent( properties, eventName ) {
	const event = {
		'eventname': eventName,
		'properties': properties,
	};

	try {
		await apiFetch( {
			path: '/wc-admin-test-helper/recent-tracks-events',
			method: 'PUT',
			data: event,
		} );
	} catch ( ex ) {
		console.log( ex );
	}
}

export const hookupLoggingOfRecentClientTracksEvents = () => {
	if ( window.wp && window.wp.hooks && window.wp.hooks.addFilter ) {
		window.wp.hooks.addFilter( 'woocommerce_tracks_client_event_properties', 'wca_test_helper', ( properties, eventName ) => {
			logRecentTracksEvent( properties, eventName );
			return properties;
		}, 20, 2 );
	}
};
