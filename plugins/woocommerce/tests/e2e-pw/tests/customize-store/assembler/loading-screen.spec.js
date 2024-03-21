/**
 * External dependencies
 */
const { test: base, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
const { AssemblerPage } = require( './assembler.page' );

const test = base.extend( {
	pageObject: async ( { page }, use ) => {
		const pageObject = new AssemblerPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Assembler - Loading Page', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test( 'should be visible when it is the first run', async ( {
		page,
		pageObject,
		baseURL,
	} ) => {
		await pageObject.setupSite( baseURL );

		const firstStepLabel = 'Setting up the foundations';
		const secondStepLabel = 'Turning on the lights';
		const thirdStepLabel = 'Opening the doors';

		await expect( page.getByText( firstStepLabel ) ).toBeVisible();
		await expect( page.getByAltText( firstStepLabel ) ).toBeVisible();
		await expect( page.getByText( secondStepLabel ) ).toBeVisible();
		await expect( page.getByAltText( secondStepLabel ) ).toBeVisible();
		await expect( page.getByText( thirdStepLabel ) ).toBeVisible();
		await expect( page.getByAltText( thirdStepLabel ) ).toBeVisible();

		await pageObject.waitForLoadingScreenFinish();
		await page.screenshot( { path: 'screenshots/assembler-loading.png' } );
	} );

	test( 'should not be visible when it is not the first run', async ( {
		page,
		pageObject,
		baseURL,
	} ) => {
		await pageObject.setupSite( baseURL );
		await pageObject.waitForLoadingScreenFinish();
		const assembler = await pageObject.getAssembler();
		await assembler.getByRole( 'button', { name: 'Skip' } ).click();
		await assembler.getByRole( 'button', { name: 'Done' } ).click();
		await pageObject.setupSite( baseURL );

		const firstStepLabel = 'Setting up the foundations';
		const secondStepLabel = 'Turning on the lights';
		const thirdStepLabel = 'Opening the doors';

		await expect( page.getByText( firstStepLabel ) ).toBeVisible();
		await expect( page.getByAltText( firstStepLabel ) ).toBeHidden();
		await expect( page.getByText( secondStepLabel ) ).toBeHidden();
		await expect( page.getByAltText( secondStepLabel ) ).toBeHidden();
		await expect( page.getByText( thirdStepLabel ) ).toBeHidden();
		await expect( page.getByAltText( thirdStepLabel ) ).toBeHidden();
	} );
} );
