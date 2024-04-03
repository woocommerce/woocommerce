const { test: base, expect, request } = require( '@playwright/test' );
const { AssemblerPage } = require( './assembler.page' );
const { activateTheme } = require( '../../../utils/themes' );
const { setOption } = require( '../../../utils/options' );

const test = base.extend( {
	pageObject: async ( { page }, use ) => {
		const pageObject = new AssemblerPage( { page } );
		await use( pageObject );
	},
} );

test.describe( 'Assembler -> Homepage', () => {
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

			await activateTheme( 'twentynineteen' );
		} catch ( error ) {
			console.log( 'Store completed option not updated' );
		}
	} );

	test.beforeEach( async ( { baseURL, pageObject } ) => {
		await pageObject.setupSite( baseURL );
		await pageObject.waitForLoadingScreenFinish();
		const assembler = await pageObject.getAssembler();
		await assembler.getByText( 'Design your homepage' ).click();
		await assembler
			.locator( '.components-placeholder__preview' )
			.waitFor( { state: 'hidden' } );
	} );

	test( 'Available homepage should be displayed', async ( {
		pageObject,
	} ) => {
		const assembler = await pageObject.getAssembler();

		const homepages = assembler.locator(
			'.block-editor-block-patterns-list__list-item'
		);

		await expect( homepages ).toHaveCount( 3 );
	} );

	test( 'The selected homepage should be focused when is clicked', async ( {
		pageObject,
	} ) => {
		const assembler = await pageObject.getAssembler();
		const homepage = assembler
			.locator( '.block-editor-block-patterns-list__item' )
			.nth( 2 );

		await homepage.click();
		await expect( homepage ).toHaveClass( /is-selected/ );
	} );

	test( 'The selected homepage should be visible on the site preview', async ( {
		pageObject,
	} ) => {
		const assembler = await pageObject.getAssembler();
		const editor = await pageObject.getEditor();

		const homepages = await assembler
			.locator(
				'.block-editor-block-patterns-list__item:not(.is-selected)'
			)
			.all();

		const selectedHomepage = await assembler
			.locator( '.block-editor-block-patterns-list__item.is-selected' )
			.all();

		// This is necessary to ensure that the trigger works correctly for all the templates in the list because if the pattern is already selected, the trigger doesn't run.
		const allHomepages = [ ...homepages, ...selectedHomepage ];

		for ( const homepage of allHomepages ) {
			await homepage.click();
			const homepageElements = await homepage
				.locator( '.block-editor-block-list__layout > *' )
				.all();

			const homepageElementsIds = await Promise.all(
				homepageElements.map( ( element ) =>
					element.getAttribute( 'id' )
				)
			);

			for ( const elementId of homepageElementsIds ) {
				const element = editor.locator( `#${ elementId }` );
				await expect( element ).toBeVisible();
			}
		}
	} );

	test( 'The Done button should be visible after clicking save', async ( {
		pageObject,
		page,
	} ) => {
		const assembler = await pageObject.getAssembler();
		const homepage = assembler
			.locator( '.block-editor-block-patterns-list__item' )
			.nth( 2 );

		await homepage.click();

		const saveButton = assembler.getByText( 'Save' );
		const waitResponse = page.waitForResponse(
			( response ) =>
				response
					.url()
					.includes(
						'wp-json/wp/v2/templates/twentytwentyfour//home'
					) && response.status() === 200
		);

		await saveButton.click();

		await waitResponse;

		await expect( assembler.getByText( 'Done' ) ).toBeEnabled();
	} );

	test( 'Selected homepage should be applied on the frontend', async ( {
		pageObject,
		page,
		baseURL,
	}, testInfo ) => {
		testInfo.snapshotSuffix = '';
		const assembler = await pageObject.getAssembler();
		const homepage = assembler
			.locator( '.block-editor-block-patterns-list__item' )
			.nth( 2 );

		await homepage.click();

		const saveButton = assembler.getByText( 'Save' );

		const waitResponse = page.waitForResponse(
			( response ) =>
				response
					.url()
					.includes(
						'wp-json/wp/v2/templates/twentytwentyfour//home'
					) && response.status() === 200
		);

		await saveButton.click();

		await waitResponse;

		await page.goto( baseURL );
		// Get all the content between the header and the footer.
		const homepageHTML = await page
			.locator(
				'//header/following-sibling::*[following-sibling::footer]'
			)
			.all();

		let index = 0;
		for ( const element of homepageHTML ) {
			await expect(
				await element.getAttribute( 'class' )
			).toMatchSnapshot( {
				name: `selected-homepage-blocks-class-frontend-${ index }`,
			} );
			index++;
		}
	} );
} );
