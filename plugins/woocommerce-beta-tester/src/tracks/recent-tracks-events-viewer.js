/**
 * External dependencies
 */
import { useEffect, useState } from "@wordpress/element";
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

	return (
		<div style={ {position: 'absolute', bottom: 0, height: '100px', overflow: 'scroll', zIndex: 9999, backgroundColor: 'red', color: 'white'} }>
			{ recentTracksEvents && recentTracksEvents.map( ( tracksEvent ) => (
				<div>
					{tracksEvent.eventname}
				</div>
			) ) }
		</div>
	);
}

export default RecentTracksEventsViewer;
