/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { Page } from '@playwright/test';

const openMiniCart = async ( page: Page ) => {
	await page.getByLabel( 'items in cart,' ).hover();
	await page.getByLabel( 'items in cart,' ).click();
};

test.describe( `Mini Cart Block`, () => {
	/**
	 * This is a workaround to run tests in isolation.
	 * Ideally, the test should be run in isolation by default. But we're
	 * overriding the storageState in config which make all tests run with admin
	 * user.
	 */
	test.use( {
		storageState: {
			origins: [],
			cookies: [],
		},
	} );

	test.beforeEach( async ( { page } ) => {
		await page.goto( `/mini-cart-block`, { waitUntil: 'commit' } );
	} );

	test( 'should open the empty cart drawer', async ( { page } ) => {
		await openMiniCart( page );

		await expect( page.getByRole( 'dialog' ) ).toContainText(
			'Your cart is currently empty!'
		);
	} );

	test( 'should close the drawer when clicking on the close button', async ( {
		page,
	} ) => {
		await openMiniCart( page );

		await expect( page.getByRole( 'dialog' ) ).toContainText(
			'Your cart is currently empty!'
		);

		await page.getByRole( 'button', { name: 'Close' } ).click();

		await expect( page.getByRole( 'dialog' ) ).toHaveCount( 0 );
	} );

	test( 'should close the drawer when clicking outside the drawer', async ( {
		page,
	} ) => {
		await openMiniCart( page );

		await expect( page.getByRole( 'dialog' ) ).toContainText(
			'Your cart is currently empty!'
		);

		await expect(
			page.getByRole( 'button', { name: 'Close' } )
		).toBeInViewport();

		await page.mouse.click( 50, 200 );

		await expect( page.getByRole( 'dialog' ) ).toHaveCount( 0 );
	} );

	test( 'should open the filled cart drawer', async ( { page } ) => {
		await page.click( 'text=Add to cart' );

		await openMiniCart( page );

		await expect( page.getByRole( 'dialog' ) ).toContainText(
			'Your cart (1 item)'
		);
	} );
} );
