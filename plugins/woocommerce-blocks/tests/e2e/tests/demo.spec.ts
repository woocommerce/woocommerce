/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

test( 'Passing test', async ( { page } ) => {
	await page.goto( 'https://example.com' );
	expect( true ).toBeTruthy();
} );

test( 'Flaky test', async ( { page }, testInfo ) => {
	await page.goto( 'https://example.com' );
	// Introduce flakiness
	if ( testInfo.retry < 2 ) {
		throw new Error( 'Flaky failure' );
	}
	expect( true ).toBeTruthy();
} );

test( 'Always failing test', async ( { page } ) => {
	await page.goto( 'https://example.com' );
	expect( false ).toBeTruthy();
} );
