/**
 * External dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

// from https://overreacted.io/making-setinterval-declarative-with-react-hooks/
function useInterval( callback, delay ) {
	const savedCallback = useRef();

	// Remember the latest callback.
	useEffect( () => {
		savedCallback.current = callback;
	}, [ callback ] );

	// Set up the interval.
	useEffect( () => {
		function tick() {
			savedCallback.current();
		}
		if ( delay !== null ) {
			const id = setInterval( tick, delay );
			return () => clearInterval( id );
		}
	}, [ delay ] );
}

const Viewer = () => {
	const [ recentTracksEvents, setRecentTracksEvents ] = useState();

	const getRecentTracksEvents = async () => {
		const response = await apiFetch( {
			path: '/wc-admin-test-helper/recent-tracks-events',
			method: 'GET',
		} );

		setRecentTracksEvents( response );
	};

	useInterval( () => {
		getRecentTracksEvents();
	}, 1000 );

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

export default Viewer;
