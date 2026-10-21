/**
 * External dependencies
 */
import type { APIRequest } from '@playwright/test';

/**
 * Internal dependencies
 */
import { encodeCredentials } from './plugin-utils';
import { admin } from '../test-data/data';

export const setOption = async (
	request: APIRequest,
	baseURL: string,
	optionName: string,
	optionValue: string
) => {
	const apiContext = await request.newContext( {
		baseURL,
		extraHTTPHeaders: {
			Authorization: `Basic ${ encodeCredentials(
				admin.username,
				admin.password
			) }`,
			cookie: '',
		},
	} );

	return await apiContext
		.post( './wp-json/e2e-options/update', {
			failOnStatusCode: true,
			data: { option_name: optionName, option_value: optionValue },
		} )
		.then( ( response ) => {
			return response.json();
		} );
};

export const deleteOption = async (
	request: APIRequest,
	baseURL: string,
	optionName: string
) => {
	const apiContext = await request.newContext( {
		baseURL,
		extraHTTPHeaders: {
			Authorization: `Basic ${ encodeCredentials(
				admin.username,
				admin.password
			) }`,
			cookie: '',
		},
	} );

	return await apiContext
		.post( './wp-json/e2e-options/delete', {
			failOnStatusCode: true,
			data: { option_name: optionName },
		} )
		.then( ( response ) => {
			return response.json();
		} );
};
