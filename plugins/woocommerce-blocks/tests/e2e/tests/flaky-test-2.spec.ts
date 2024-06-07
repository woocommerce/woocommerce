/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

test.describe( 'Flaky test describe block 2', () => {
	test.describe( 'Flaky test inner describe block 2', () => {
		test( 'I should pass on retry 2', async ( {}, testInfo ) => {
			expect( testInfo.retry ).toBeFalsy();
		} );
	} );
} );
