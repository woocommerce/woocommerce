const { test: base, expect, request } = require( '@playwright/test' );
const { AssemblerPage } = require( './assembler.page' );
const { activateTheme, DEFAULT_THEME } = require( '../../../utils/themes' );
const { setOption } = require( '../../../utils/options' );
const { setFeatureFlag } = require( '../../../utils/features' );

const test = base.extend( {
	pageObject: async ( { page }, use ) => {
		const pageObject = new AssemblerPage( { page } );
		await use( pageObject );
	},
} );

async function prepareAssembler( pageObject, baseURL ) {
	await pageObject.setupSite( baseURL );
	await pageObject.waitForLoadingScreenFinish();
	const assembler = await pageObject.getAssembler();
	await assembler.getByText( 'Design your homepage' ).click();
	await assembler
		.locator( '.components-placeholder__preview' )
		.waitFor( { state: 'hidden' } );
}

test.describe( 'Assembler -> Full composability', { tag: '@gutenberg' }, () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { baseURL } ) => {
		try {
			// In some environments the tour blocks clicking other elements.
			await setOption(
				request,
				baseURL,
				'woocommerce_customize_store_onboarding_tour_hidden',
				'yes'
			);
		} catch ( error ) {
			console.log( 'Store completed option not updated' );
		}

		await setFeatureFlag(
			request,
			baseURL,
			'pattern-toolkit-full-composability',
			true
		);
	} );

	test.afterAll( async ( { baseURL } ) => {
		try {
			// In some environments the tour blocks clicking other elements.
			await setOption(
				request,
				baseURL,
				'woocommerce_customize_store_onboarding_tour_hidden',
				'no'
			);
			await setOption(
				request,
				baseURL,
				'woocommerce_admin_customize_store_completed',
				'no'
			);

			await activateTheme( DEFAULT_THEME );
		} catch ( error ) {
			console.log( 'Store completed option not updated' );
		}
	} );

	test( 'The list of categories should be displayed', async ( {
		pageObject,
		baseURL,
	} ) => {
		await prepareAssembler( pageObject, baseURL );
		const assembler = await pageObject.getAssembler();

		const categories = assembler.locator(
			'.woocommerce-customize-store__sidebar-homepage-content .components-item-group'
		);

		await expect( categories ).toHaveCount( 6 );
	} );

	test( 'Clicking on "Design your homepage" should open the Intro sidebar by default', async ( {
		pageObject,
		baseURL,
	} ) => {
		await prepareAssembler( pageObject, baseURL );
		const assembler = await pageObject.getAssembler();

		await expect(
			await assembler
				.locator(
					'.woocommerce-customize-store-edit-site-layout__sidebar-extra__pattern__header'
				)
				.textContent()
		).toContain( 'Intro' );
	} );

	test( 'Clicking on a category should open the sidebar for it', async ( {
		pageObject,
		baseURL,
	} ) => {
		await prepareAssembler( pageObject, baseURL );
		const assembler = await pageObject.getAssembler();

		const categories = await assembler
			.locator(
				'.edit-site-sidebar-navigation-screen-patterns__group-homepage-label-container > span:first-child'
			)
			.all();

		for ( const category of categories ) {
			const name = await category.textContent();
			await category.click();

			const sidebar = assembler.locator(
				'.woocommerce-customize-store-edit-site-layout__sidebar-extra'
			);
			await expect( sidebar ).toBeVisible();

			const sidebarTitle = await sidebar
				.locator(
					'.woocommerce-customize-store-edit-site-layout__sidebar-extra__pattern__header'
				)
				.textContent();

			await expect( sidebarTitle ).toBe( name );
			await expect( async () => {
				const count = await sidebar
					.locator( '.block-editor-block-patterns-list__list-item' )
					.count();
				expect( count ).toBeGreaterThan( 0 );
			} ).toPass();
		}
	} );
} );
