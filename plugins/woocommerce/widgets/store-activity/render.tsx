import { useState, useMemo, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/icons';
import { StoreActivityEmptyState, ActivitySourcesLoader } from './components';
import { useActivitySources } from './hooks';
import type { StoreActivityEvent, ActivityHookResult } from './types';

/**
 * Formats an ISO 8601 datetime into a short local time string.
 */
function formatEventTime( datetime: string ): string {
	return new Date( datetime ).toLocaleTimeString( 'en-US', {
		hour: 'numeric',
		minute: '2-digit',
		hour12: true,
	} );
}

/**
 * Groups events by their date (YYYY-MM-DD), preserving sort order within
 * each group.
 */
function groupEventsByDate(
	events: StoreActivityEvent[]
): Array< [ string, StoreActivityEvent[] ] > {
	const groups = new Map< string, StoreActivityEvent[] >();

	for ( const event of events ) {
		const date = event.datetime.split( 'T' )[ 0 ];
		const bucket = groups.get( date ) ?? [];
		bucket.push( event );
		groups.set( date, bucket );
	}

	return Array.from( groups.entries() );
}

/**
 * Store Activity widget render component.
 * Aggregates events from registered sources and renders them as a
 * date-grouped list.
 */
export default function StoreActivityRender() {
	const sources = useActivitySources();
	const [ results, setResults ] = useState<
		Record< string, ActivityHookResult >
	>( {} );

	const updateResults = useCallback(
		( sourceId: string, result: ActivityHookResult ) => {
			setResults( ( prev ) => ( {
				...prev,
				[ sourceId ]: result,
			} ) );
		},
		[]
	);

	const allResults = useMemo( () => Object.values( results ), [ results ] );

	const events = useMemo(
		() =>
			allResults
				.flatMap( ( result ) =>
					result.state === 'success' ? result.events ?? [] : []
				)
				.sort(
					( a, b ) =>
						new Date( b.datetime ).getTime() -
						new Date( a.datetime ).getTime()
				),
		[ allResults ]
	);

	const isLoading = allResults.some(
		( result ) => result.state === 'loading'
	);

	const waitingForResults =
		sources.length > 0 && allResults.length < sources.length;

	const groupedEvents = useMemo(
		() => groupEventsByDate( events ),
		[ events ]
	);

	return (
		<>
			<ActivitySourcesLoader
				sources={ sources }
				onResult={ updateResults }
			/>

			{ ( isLoading || waitingForResults ) && (
				<div className="store-activity-widget__loading">
					<p>{ __( 'Loading…', 'woocommerce' ) }</p>
				</div>
			) }

			{ ! isLoading &&
				! waitingForResults &&
				events.length === 0 && <StoreActivityEmptyState /> }

			{ ! isLoading && ! waitingForResults && events.length > 0 && (
				<div className="store-activity-widget">
					{ groupedEvents.map( ( [ date, dayEvents ] ) => (
						<section
							key={ date }
							className="store-activity-widget__group"
						>
							<header className="store-activity-widget__group-header">
								{ date }
							</header>
							<ul className="store-activity-widget__list">
								{ dayEvents.map( ( event ) => (
									<li
										key={ event.id }
										className="store-activity-widget__event"
									>
										<span className="store-activity-widget__event-icon">
											<Icon icon={ event.icon } />
										</span>
										<span className="store-activity-widget__event-content">
											{ event.renderContent() }
										</span>
										<span className="store-activity-widget__event-time">
											{ formatEventTime(
												event.datetime
											) }
										</span>
									</li>
								) ) }
							</ul>
						</section>
					) ) }
				</div>
			) }
		</>
	);
}
