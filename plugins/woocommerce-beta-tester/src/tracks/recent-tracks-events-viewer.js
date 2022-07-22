/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const RecentTracksEventsViewer = () => {
	const [ recentTracksEvents, setRecentTracksEvents ] = useState();

	const getRecentTracksEvents = async () => {
		const response = await apiFetch( {
			path: '/wc-admin-test-helper/recent-tracks-events',
			method: 'GET',
		} );

		setRecentTracksEvents( response );
	};

	useEffect( () => {
		getRecentTracksEvents();
	}, [] );

	const renderTracksEventProperty = ( tracksEventProperty ) => {
		let propertyValue = tracksEventProperty[ 1 ];

		if ( propertyValue === true ) {
			propertyValue = 'true';
		} else if ( propertyValue === false ) {
			propertyValue = 'false';
		}

		return (
			<li style={ { color: 'gray' } }>
				{ tracksEventProperty[ 0 ] }: { propertyValue }
			</li>
		);
	};

	const renderTracksEventProperties = ( tracksEventProperties ) => {
		return (
			<ul>
				{ Object.entries( tracksEventProperties ).map(
					renderTracksEventProperty
				) }
			</ul>
		);
	};

	const renderTracksEvent = ( tracksEvent ) => {
		return (
			<li>
				<div>{ tracksEvent.eventname }</div>
				{ tracksEvent.eventprops &&
					renderTracksEventProperties( tracksEvent.eventprops ) }
			</li>
		);
	};

	return (
		<div
			style={ {
				position: 'fixed',
				bottom: 0,
				height: '150px',
				width: '100%',
				overflow: 'scroll',
				zIndex: 9999,
				backgroundColor: 'black',
				color: 'white',
			} }
		>
			<div>Recent Tracks Events</div>
			<ul>
				{ recentTracksEvents &&
					recentTracksEvents.map( renderTracksEvent ) }
			</ul>
		</div>
	);
};

export default RecentTracksEventsViewer;
