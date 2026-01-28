/**
 * External dependencies
 */
import type { APIRequest } from '@playwright/test';

/**
 * Internal dependencies
 */
import { encodeCredentials } from './plugin-utils';
import { admin } from '../test-data/data';

/**
 * Response from the options API.
 */
export interface OptionResponse {
	[ key: string ]: unknown;
}

/**
 * Set a WordPress option via the E2E options API.
 *
 * @param request     - Playwright APIRequest object
 * @param baseURL     - Base URL of the site
 * @param optionName  - Name of the option to set
 * @param optionValue - Value to set for the option
 * @return Promise resolving to the API response
 */
export const setOption = async (
	request: APIRequest,
	baseURL: string,
	optionName: string,
	optionValue: string
): Promise< OptionResponse > => {
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
			return response.json() as Promise< OptionResponse >;
		} );
};

/**
 * Delete a WordPress option via the E2E options API.
 *
 * @param request    - Playwright APIRequest object
 * @param baseURL    - Base URL of the site
 * @param optionName - Name of the option to delete
 * @return Promise resolving to the API response
 */
export const deleteOption = async (
	request: APIRequest,
	baseURL: string,
	optionName: string
): Promise< OptionResponse > => {
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
			return response.json() as Promise< OptionResponse >;
		} );
};
