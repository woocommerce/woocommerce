/**
 * External dependencies
 */
import type { APIRequest } from '@playwright/test';

/**
 * Internal dependencies
 */
import { encodeCredentials } from './plugin-utils';
import { admin } from '../test-data/data';

const setFeatureFlag = async (
	request: APIRequest,
	baseURL: string,
	flagName: string,
	enable: boolean
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

	await apiContext.post( './wp-json/e2e-feature-flags/update', {
		failOnStatusCode: true,
		data: { [ flagName ]: enable },
	} );
};

const resetFeatureFlags = async ( request: APIRequest, baseURL: string ) => {
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

	await apiContext.get( './wp-json/e2e-feature-flags/reset', {
		failOnStatusCode: true,
	} );
};

export { setFeatureFlag, resetFeatureFlags };
