const { test: base, expect } = require( '@playwright/test' );
const { AssemblerPage } = require( '../assembler/assembler.page' );
const {
	createRequestsToSetupStoreDictionary,
	setupRequestInterceptor,
} = require( './loading-screen.utils' );

const test = base.extend( {
	pageObject: async ( { page }, use ) => {
		const pageObject = new AssemblerPage( { page } );
		await use( pageObject );
	},
} );

const steps = [
	'Setting up the foundations',
	'Turning on the lights',
	'Opening the doors',
];

test.describe.skip( 'Assembler - Loading Page', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test( 'should display loading screen and steps on first run', async ( {
		pageObject,
		baseURL,
		page,
	} ) => {
		await pageObject.setupSite( baseURL );

		const requestToSetupStore = createRequestsToSetupStoreDictionary();

		setupRequestInterceptor( page, requestToSetupStore );

		for ( const step of steps ) {
			await expect(
				page.locator( '.woocommerce-onboarding-loader' )
			).toBeVisible();
			await expect( page.getByText( step ) ).toBeVisible();
			await expect( page.getByAltText( step ) ).toBeVisible();
		}

		expect( Object.values( requestToSetupStore ) ).toEqual( [
			true,
			true,
			true,
		] );

		await pageObject.waitForLoadingScreenFinish();
	} );

	test( 'should redirect to intro page in case of errors', async ( {
		pageObject,
		baseURL,
		page,
	} ) => {
		await pageObject.setupSite( baseURL );

		const requestToSetupStore = createRequestsToSetupStoreDictionary();

		// Abort one of the requests to simulate an error.
		await page.route(
			requestToSetupStore[ Math.floor( Math.random() * 3 ) ],
			( route ) => route.abort()
		);

		setupRequestInterceptor( page, requestToSetupStore );

		await expect(
			page
				.locator( '#woocommerce-layout__primary' )
				.getByText(
					'Oops! We encountered a problem while setting up the foundations. Please try again or start with a theme.'
				)
		).toBeVisible();
	} );

	test( 'should hide loading screen and steps on subsequent runs', async ( {
		pageObject,
		baseURL,
		page,
	} ) => {
		await pageObject.setupSite( baseURL );
		await pageObject.waitForLoadingScreenFinish();

		const assembler = await pageObject.getAssembler();
		await assembler.getByRole( 'button', { name: 'Skip' } ).click();
		await assembler.getByRole( 'button', { name: 'Done' } ).click();
		await pageObject.setupSite( baseURL );

		const requestToSetupStore = createRequestsToSetupStoreDictionary();

		setupRequestInterceptor( page, requestToSetupStore );

		for ( const step of steps ) {
			await expect( page.getByText( step ) ).toBeHidden();
			await expect( page.getByAltText( step ) ).toBeHidden();
		}

		expect( Object.values( requestToSetupStore ) ).toEqual( [
			false,
			false,
			false,
		] );
	} );
} );
