/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

test.describe( 'DO NOT MERGE: pr-readiness-comment bot dummy failure (see PR #67081)', () => {
	test( 'formerly intentionally failed; now fixed to exercise the readiness bot\'s "E2E tests" task', async () => {
		expect( 1 ).toBe( 1 );
	} );
} );
