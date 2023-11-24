const { test, expect } = require( '@playwright/test' );

test.describe(
	'This test suite fails on purpose to test flaky test reporting',
	() => {
		test( 'First failure', async ( { page } ) => {
			await page.goto( '/' );
			await expect( false ).toBeTruthy();
		} );

		test( 'Second failure', async ( { page } ) => {
			await page.goto( '/' );
			await expect( true ).toBeFalsy();
		} );
	}
);
