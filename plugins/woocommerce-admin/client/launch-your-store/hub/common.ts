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
export const createQueryParamsListener =
	( paramName: string ) =>
	( sendBack: ( event: AnyEventObject ) => void ) => {
		let previousLocation: ReturnType< typeof getHistory >[ 'location' ];
		const unlisten = getHistory().listen( ( { action, location } ) => {
			if ( action === 'POP' ) {
				const previousQuery = new URLSearchParams(
					previousLocation.search
				);
				const currentQuery = new URLSearchParams( location.search );
				if (
					previousQuery.get( paramName ) !==
					currentQuery.get( paramName )
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

type LaunchYourStoreQueryParams = {
	sidebar?: string;
	content?: string;
};

export const updateQueryParams = ( {
	sidebar,
	content,
}: LaunchYourStoreQueryParams ) => {
	const queryParams = getQuery() as LaunchYourStoreQueryParams;
	const changes = Object.entries( { sidebar, content } ).reduce(
		( acc: LaunchYourStoreQueryParams, [ key, value ] ) => {
			if (
				value !== null &&
				value !== undefined &&
				value !== queryParams[ key as keyof LaunchYourStoreQueryParams ]
			) {
				acc[ key as keyof LaunchYourStoreQueryParams ] = value;
			}
			return acc;
		},
		{}
	);

	if ( Object.keys( changes ).length > 0 ) {
		updateQueryString( changes );
	}
};
