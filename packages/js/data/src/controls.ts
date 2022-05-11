/**
 * External dependencies
 */
import { controls as dataControls } from '@wordpress/data-controls';
import { Action } from '@wordpress/data';
import apiFetch, { APIFetchOptions } from '@wordpress/api-fetch';
import { AnyAction } from 'redux';

export const fetchWithHeaders = (
	options: APIFetchOptions
): AnyAction & { options: APIFetchOptions } => {
	return {
		type: 'FETCH_WITH_HEADERS',
		options,
	};
};

export type FetchWithHeadersResponse< Data > = {
	headers: Response[ 'headers' ];
	status: Response[ 'status' ];
	data: Data;
};

const controls = {
	...dataControls,
	FETCH_WITH_HEADERS( action: AnyAction ) {
		return apiFetch< Response >( { ...action.options, parse: false } )
			.then( ( response ) => {
				return Promise.all( [
					response.headers,
					response.status,
					response.json(),
				] );
			} )
			.then( ( [ headers, status, data ] ) => ( {
				headers,
				status,
				data,
			} ) );
	},
};

export default controls;
