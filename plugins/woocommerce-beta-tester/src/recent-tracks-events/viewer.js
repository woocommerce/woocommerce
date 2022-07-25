/**
 * External dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './viewer.scss';

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
	const listRef = useRef( null );
	const bottomOfListRef = useRef( null );
	const [ doInitialScroll, setDoInitialScroll ] = useState( true );
	const [ recentTracksEvents, setRecentTracksEvents ] = useState( [] );

	async function getRecentTracksEvents() {
		const response = await apiFetch( {
			path: '/wc-admin-test-helper/recent-tracks-events',
			method: 'GET',
		} );

		setRecentTracksEvents( response );
	}

	useInterval( () => {
		getRecentTracksEvents();
	}, 1000 );

	useEffect( () => {
		const amountScrolledBack =
			listRef.current?.scrollHeight - listRef.current?.scrollTop;
		const shouldScroll =
			doInitialScroll ||
			amountScrolledBack < listRef.current?.clientHeight + 50;

		if ( doInitialScroll && recentTracksEvents.length > 0 ) {
			setDoInitialScroll( false );
		}

		if ( shouldScroll ) {
			bottomOfListRef.current?.scrollIntoView( { behavior: 'smooth' } );
		}
	}, [ doInitialScroll, recentTracksEvents ] );

	return (
		<div className="wc-beta-tester-recent-tracks-events-viewer">
			<div className="wc-beta-tester-recent-tracks-events-viewer__header">
				Recent Tracks Events
			</div>
			<ul
				ref={ listRef }
				className="wc-beta-tester-recent-tracks-events-viewer__events-list"
			>
				{ recentTracksEvents &&
					recentTracksEvents.map( ( tracksEvent ) => (
						<TracksEvent tracksEvent={ tracksEvent } />
					) ) }
				<li ref={ bottomOfListRef } />
			</ul>
		</div>
	);
};

const TracksEvent = ( props ) => {
	const [ isExpanded, setIsExpanded ] = useState( false );

	function renderTracksEventProperty( tracksEventProperty ) {
		let propertyValue = tracksEventProperty[ 1 ];

		if ( propertyValue === true ) {
			propertyValue = 'true';
		} else if ( propertyValue === false ) {
			propertyValue = 'false';
		}

		return (
			<li className="wc-beta-tester-recent-tracks-events-viewer__event-property">
				<span className="wc-beta-tester-recent-tracks-events-viewer__event-property__name">
					{ tracksEventProperty[ 0 ] }:{ ' ' }
				</span>
				<span className="wc-beta-tester-recent-tracks-events-viewer__event-property__value">
					{ propertyValue }
				</span>
			</li>
		);
	}

	function renderTracksEventProperties( tracksEventProperties ) {
		return (
			<ul className="wc-beta-tester-recent-tracks-events-viewer__event-properties-list">
				{ Object.entries( tracksEventProperties ).map(
					renderTracksEventProperty
				) }
			</ul>
		);
	}

	function toggleIsExpanded() {
		setIsExpanded( ! isExpanded );
	}

	return (
		<li
			className={ classnames(
				'wc-beta-tester-recent-tracks-events-viewer__event',
				{ 'is-expanded': isExpanded }
			) }
		>
			<div
				onClick={ toggleIsExpanded }
				className="wc-beta-tester-recent-tracks-events-viewer__event__name"
			>
				<span>{ props.tracksEvent.eventname }</span>
			</div>
			{ props.tracksEvent.eventprops &&
				renderTracksEventProperties( props.tracksEvent.eventprops ) }
		</li>
	);
};

export default Viewer;
