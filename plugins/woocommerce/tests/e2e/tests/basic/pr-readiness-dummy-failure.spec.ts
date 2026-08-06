/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

test.describe( 'DO NOT MERGE: pr-readiness-comment bot dummy (see PR #67081)', () => {
	test( 'intentionally failing', async () => {
		expect( 1 ).toBe( 2 );
	} );
} );
