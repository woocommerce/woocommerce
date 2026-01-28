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
 * Set a feature flag value via the E2E feature flags API.
 *
 * @param request  - Playwright APIRequest object
 * @param baseURL  - Base URL of the site
 * @param flagName - Name of the feature flag to set
 * @param enable   - Whether to enable or disable the flag
 */
export const setFeatureFlag = async (
	request: APIRequest,
	baseURL: string,
	flagName: string,
	enable: boolean
): Promise< void > => {
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

/**
 * Reset all feature flags to their default values.
 *
 * @param request - Playwright APIRequest object
 * @param baseURL - Base URL of the site
 */
export const resetFeatureFlags = async (
	request: APIRequest,
	baseURL: string
): Promise< void > => {
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
