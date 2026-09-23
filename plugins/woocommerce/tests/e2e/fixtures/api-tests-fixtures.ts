/**
 * External dependencies
 */
import { test as base, expect, request } from '@playwright/test';

/**
 * Internal dependencies
 */
import { admin } from '../test-data/data';
import { encodeCredentials } from '../utils/plugin-utils';
import { tags } from './fixtures';

export const test = base.extend( {
	extraHTTPHeaders: {
		// Add authorization token to all requests.
		Authorization: `Basic ${ encodeCredentials(
			admin.username,
			admin.password
		) }`,
	},
} );

export { expect, tags, request };
