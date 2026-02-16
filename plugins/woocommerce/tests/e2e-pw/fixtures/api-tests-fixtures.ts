import { test as base, expect, request } from '@playwright/test';
import { admin } from '../test-data/data';
import { tags } from './fixtures';

export const test = base.extend( {
	extraHTTPHeaders: {
		// Add authorization token to all requests.
		Authorization: `Basic ${ btoa(
			`${ admin.username }:${ admin.password }`
		) }`,
	},
} );

export { expect, tags, request };
