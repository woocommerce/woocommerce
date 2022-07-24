/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { default as Viewer } from './viewer';

async function logRecentTracksEvent( properties, eventName ) {
	const event = {
		eventname: eventName,
		eventprops: properties,
	};

	try {
		await apiFetch( {
			path: '/wc-admin-test-helper/recent-tracks-events',
			method: 'PUT',
			data: event,
		} );
	} catch ( ex ) {
		// not sure how to best handle an error here
	}
}

function hookupLogging() {
	if ( window.wp?.hooks?.addFilter ) {
		window.wp.hooks.addFilter(
			'woocommerce_tracks_client_event_properties',
			'wca_test_helper',
			( properties, eventName ) => {
				logRecentTracksEvent( properties, eventName );
				return properties;
			},
			20,
			2
		);
	}
}

function isOptionValueTruthy( value ) {
	return value === true || value === 'yes' || value === '1' || value === 1;
}

const RecentTracksEvents = () => {
	const [ isLoggingHookedUp, setIsLoggingHookedUp ] = useState( false );
	const { isResolving, recentTracksEnabled } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );

		return {
			isResolving: ! hasFinishedResolution( 'getOption', [
				'wc_beta_tester_recent_tracks_enabled',
			] ),
			recentTracksEnabled: isOptionValueTruthy(
				getOption( 'wc_beta_tester_recent_tracks_enabled' )
			),
		};
	} );

	useEffect( () => {
		if ( recentTracksEnabled && ! isLoggingHookedUp ) {
			setIsLoggingHookedUp( true );
			hookupLogging();
		}
	}, [ isLoggingHookedUp, recentTracksEnabled ] );

	if ( isResolving || ! recentTracksEnabled ) {
		return null;
	}

	return (
		<>
			<Viewer />
		</>
	);
};

export default RecentTracksEvents;
