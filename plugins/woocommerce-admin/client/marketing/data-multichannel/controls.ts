/**
 * External dependencies
 */
import { controls as dataControls } from '@wordpress/data-controls';
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

export const awaitResponseJson = (
	response: Response
): AnyAction & { response: Response } => {
	return {
		type: 'AWAIT_RESPONSE_JSON',
		response,
	};
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
			} ) )
			.catch( ( response ) => {
				return response.json().then( ( data: unknown ) => {
					throw data;
				} );
			} );
	},
	AWAIT_RESPONSE_JSON( action: AnyAction ) {
		return action.response.json();
	},
};

export default controls;
