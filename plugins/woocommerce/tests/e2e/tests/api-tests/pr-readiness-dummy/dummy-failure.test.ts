/**
 * Internal dependencies
 */
import { test, expect } from '../../../fixtures/api-tests-fixtures';

test.describe( 'DO NOT MERGE: pr-readiness-comment bot dummy failure (see PR #67081)', () => {
	test( 'intentionally fails to exercise the readiness bot\'s "API tests" task', async () => {
		expect( 1 ).toEqual( 2 );
	} );
} );
