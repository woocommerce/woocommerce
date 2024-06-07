/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

test.describe( 'Flaky test describe block', () => {
	test.describe( 'Flaky test inner describe block', () => {
		test( 'I should pass on retry', async ( {}, testInfo ) => {
			expect( testInfo.retry ).toBeTruthy();
		} );
	} );
} );
