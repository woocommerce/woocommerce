/**
 * External dependencies
 */
import {
	getHistory,
	getQuery,
	updateQueryString,
} from '@woocommerce/navigation';
import { AnyEventObject } from 'xstate5';

/**
 * This seemingly complex piece of code is just a function that creates a listener that
 * sends off the EXTERNAL_URL_UPDATE event when the specified query parameter
 * changes.
 *
 * @param paramName The query parameter to listen for changes
 */
export const createQueryParamsListener = (
	paramName: string,
	sendBack: ( event: AnyEventObject ) => void
) => {
	let previousLocation: ReturnType< typeof getHistory >[ 'location' ] =
		getHistory().location;
	const unlisten = getHistory().listen( ( { action, location } ) => {
		if ( action === 'POP' ) {
			const previousQuery = new URLSearchParams(
				previousLocation.search
			);
			const currentQuery = new URLSearchParams( location.search );
			if (
				previousQuery.get( paramName ) !== currentQuery.get( paramName )
			) {
				previousLocation = location;
				sendBack( { type: 'EXTERNAL_URL_UPDATE' } );
			}
		}
		previousLocation = location;
	} );

	return () => {
		unlisten();
	};
};

export const updateQueryParams = < T extends Record< string, string > >(
	params: Partial< T >
) => {
	const queryParams = getQuery() as T;

	const changes = Object.entries( params ).reduce(
		( acc: Partial< T >, [ key, value ] ) => {
			// Check if the value is different from the current queryParams.
			// Include if explicitly passed, even if it's undefined.
			if ( queryParams[ key as keyof T ] !== value ) {
				acc[ key as keyof T ] = value;
			}
			return acc;
		},
		{} as Partial< T >
	);

	if ( Object.keys( changes ).length > 0 ) {
		updateQueryString( changes );
	}
};
