import fastDeepEqual from 'fast-deep-equal';
import { useEffect, useRef } from '@wordpress/element';
import type {
	ActivitySource,
	ActivityHookResult,
	StoreActivityEvent,
} from '../../types';

/**
 * Extracts data properties from events for comparison.
 * Excludes function references to allow proper deep comparison.
 */
function extractEventsData( events: StoreActivityEvent[] ): object[] {
	return events.map( ( event ) => ( {
		id: event.id,
		icon: event.icon,
		datetime: event.datetime,
	} ) );
}

/**
 * Compares two ActivityHookResult objects to determine if they changed.
 */
function hasResultChanged(
	prev: ActivityHookResult | null,
	current: ActivityHookResult
): boolean {
	if ( ! prev ) {
		return true;
	}

	if ( prev.state !== current.state ) {
		return true;
	}

	if ( current.state === 'loading' || current.state === 'empty' ) {
		return false;
	}

	if ( ! prev.events || ! current.events ) {
		return prev.events !== current.events;
	}

	const prevData = extractEventsData( prev.events );
	const currentData = extractEventsData( current.events );

	return ! fastDeepEqual( prevData, currentData );
}

type ActivitySourceLoaderProps = {
	source: ActivitySource;
	onResult: ( sourceId: string, result: ActivityHookResult ) => void;
};

type ActivitySourcesLoaderProps = {
	sources: ActivitySource[];
	onResult: ( sourceId: string, result: ActivityHookResult ) => void;
};

/**
 * Ghost component that executes an activity source hook and reports the result.
 */
function ActivitySourceLoader( {
	source,
	onResult,
}: ActivitySourceLoaderProps ) {
	const result = source.useActivity();
	const prevResultRef = useRef< ActivityHookResult | null >( null );

	useEffect( () => {
		if ( ! hasResultChanged( prevResultRef.current, result ) ) {
			return;
		}

		prevResultRef.current = result;
		onResult( source.id, result );
	}, [ source.id, result, onResult ] );

	return null;
}

/**
 * Component that loads all activity sources by executing their hooks.
 */
export function ActivitySourcesLoader( {
	sources,
	onResult,
}: ActivitySourcesLoaderProps ) {
	return (
		<>
			{ sources.map( ( source ) => (
				<ActivitySourceLoader
					key={ source.id }
					source={ source }
					onResult={ onResult }
				/>
			) ) }
		</>
	);
}
