const { test: base, expect, request } = require( '@playwright/test' );
const { AssemblerPage } = require( './assembler.page' );
const { activateTheme, DEFAULT_THEME } = require( '../../../utils/themes' );
const { getInstalledWordPressVersion } = require( '../../../utils/wordpress' );
const { setOption } = require( '../../../utils/options' );
const { encodeCredentials } = require( '../../../utils/plugin-utils' );

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

test.describe( 'Assembler -> Homepage', { tag: '@gutenberg' }, () => {
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

		const wordPressVersion = await getInstalledWordPressVersion();

		if ( wordPressVersion > 6.5 ) {
			test.skip(
				'Skipping Assembler Homepage tests: WordPress version is above 6.5, which does not support this feature.'
			);
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

			await activateTheme( DEFAULT_THEME );
		} catch ( error ) {
			console.log( 'Store completed option not updated' );
		}
	} );

	test( 'Available homepage should be displayed', async ( {
		pageObject,
		baseURL,
	} ) => {
		await prepareAssembler( pageObject, baseURL );

		const assembler = await pageObject.getAssembler();

		const homepages = assembler.locator(
			'.block-editor-block-patterns-list__list-item'
		);

		await expect( homepages ).toHaveCount( 3 );
	} );

	test( 'The selected homepage should be focused when is clicked', async ( {
		pageObject,
		baseURL,
	} ) => {
		await prepareAssembler( pageObject, baseURL );

		const assembler = await pageObject.getAssembler();
		const homepage = assembler
			.locator( '.block-editor-block-patterns-list__item' )
			.nth( 2 );

		await homepage.click();
		await expect( homepage ).toHaveClass( /is-selected/ );
	} );

	test( 'The selected homepage should be visible on the site preview', async ( {
		pageObject,
		baseURL,
	} ) => {
		await prepareAssembler( pageObject, baseURL );

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

	test( 'Selected homepage should be applied on the frontend', async ( {
		pageObject,
		page,
		baseURL,
	}, testInfo ) => {
		testInfo.snapshotSuffix = '';
		await prepareAssembler( pageObject, baseURL );

		const assembler = await pageObject.getAssembler();
		const homepage = assembler
			.locator( '.block-editor-block-patterns-list__item' )
			.nth( 2 );

		await homepage.click();

		await assembler.locator( '[aria-label="Back"]' ).click();

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

		// Check if Gutenberg is installed
		const apiContext = await request.newContext( {
			baseURL,
			extraHTTPHeaders: {
				Authorization: `Basic ${ encodeCredentials(
					'admin',
					'password'
				) }`,
				cookie: '',
			},
		} );
		const listPluginsResponse = await apiContext.get(
			`/wp-json/wp/v2/plugins`,
			{
				failOnStatusCode: true,
			}
		);
		const pluginsList = await listPluginsResponse.json();
		const withGutenbergPlugin = pluginsList.find(
			( { textdomain } ) => textdomain === 'gutenberg'
		);

		// if testing with Gutenberg, perform Gutenberg-specific testing
		// eslint-disable-next-line playwright/no-conditional-in-test
		if ( withGutenbergPlugin ) {
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
					name: `${
						withGutenbergPlugin ? 'gutenberg' : ''
					}-selected-homepage-blocks-class-frontend-${ index }`,
				} );
				index++;
			}
		}
	} );
} );

test.describe( 'Homepage tracking banner', () => {
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

		const wordPressVersion = await getInstalledWordPressVersion();

		if ( wordPressVersion <= 6.5 ) {
			test.skip(
				'Skipping PTK API test: WordPress version is below 6.5, which does not support this feature.'
			);
		}
	} );

	test( 'Should show the "Want more patterns?" banner with the PTK API unavailable message', async ( {
		baseURL,
		pageObject,
		page,
	} ) => {
		await setOption( request, baseURL, 'woocommerce_allow_tracking', 'no' );

		await page.route( '**/wp-json/wc/private/patterns*', ( route ) => {
			route.fulfill( {
				status: 500,
			} );
		} );

		await prepareAssembler( pageObject, baseURL );

		const assembler = await pageObject.getAssembler();
		await expect(
			assembler.getByText( 'Want more patterns?' )
		).toBeVisible();
		await expect(
			assembler.getByText(
				"Unfortunately, we're experiencing some technical issues — please come back later to access more patterns."
			)
		).toBeVisible();
	} );

	test( 'Should show the "Want more patterns?" banner with the Opt-in message when tracking is not allowed', async ( {
		pageObject,
		baseURL,
	} ) => {
		await setOption( request, baseURL, 'woocommerce_allow_tracking', 'no' );

		await prepareAssembler( pageObject, baseURL );

		const assembler = await pageObject.getAssembler();
		await expect(
			assembler.getByText( 'Want more patterns?' )
		).toBeVisible();
		await expect(
			assembler.getByText(
				'Opt in to usage tracking to get access to more patterns.'
			)
		).toBeVisible();
	} );

	test( 'Should show the "Want more patterns?" banner with the offline message when the user is offline and tracking is not allowed', async ( {
		context,
		pageObject,
		baseURL,
	} ) => {
		await setOption( request, baseURL, 'woocommerce_allow_tracking', 'no' );

		await prepareAssembler( pageObject, baseURL );

		await context.setOffline( true );

		const assembler = await pageObject.getAssembler();
		await expect(
			assembler.getByText( 'Want more patterns?' )
		).toBeVisible();
		await expect(
			assembler.getByText(
				"Looks like we can't detect your network. Please double-check your internet connection and refresh the page."
			)
		).toBeVisible();
	} );

	test( 'Should not show the "Want more patterns?" banner when tracking is allowed', async ( {
		baseURL,
		pageObject,
	} ) => {
		await setOption(
			request,
			baseURL,
			'woocommerce_allow_tracking',
			'yes'
		);

		await prepareAssembler( pageObject, baseURL );

		const assembler = await pageObject.getAssembler();
		await expect(
			assembler.getByText( 'Want more patterns?' )
		).toBeHidden();
	} );
} );
