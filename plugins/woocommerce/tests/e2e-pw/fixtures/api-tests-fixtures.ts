/**
 * External dependencies
 */
import {
	test as baseTest,
	expect as baseExpect,
	request as baseRequest,
} from '@playwright/test';

/**
 * Internal dependencies
 */
import { admin } from '../test-data/data.js';
import { tags } from './fixtures';

/**
 * API test fixtures interface.
 */
interface ApiTestFixtures {
	extraHTTPHeaders: Record< string, string >;
}

export const test = baseTest.extend< ApiTestFixtures >( {
	extraHTTPHeaders: {
		// Add authorization token to all requests.
		Authorization: `Basic ${ btoa(
			`${ admin.username }:${ admin.password }`
		) }`,
	},
} );

export const expect = baseExpect;
export { tags };
export const request = baseRequest;
