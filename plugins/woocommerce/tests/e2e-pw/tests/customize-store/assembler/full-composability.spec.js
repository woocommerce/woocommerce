const { test: base, expect, request } = require( '@playwright/test' );
const { AssemblerPage } = require( './assembler.page' );
const { activateTheme, DEFAULT_THEME } = require( '../../../utils/themes' );
const { setOption } = require( '../../../utils/options' );
const { getInstalledWordPressVersion } = require( '../../../utils/wordpress' );

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

async function deleteAllPatterns( editor, assembler ) {
	const previewPatterns = await editor
		.locator(
			'[data-is-parent-block="true"]:not([data-type="core/template-part"])'
		)
		.all();

	for ( const pattern of previewPatterns ) {
		await pattern.click();
		const deleteButton = assembler.locator( 'button[aria-label="Delete"]' );
		await deleteButton.click();
	}
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

			await setOption(
				request,
				baseURL,
				'woocommerce_allow_tracking',
				'no'
			);
		} catch ( error ) {
			console.log( 'Store completed option not updated' );
		}

		const wordPressVersion = await getInstalledWordPressVersion();

		if ( wordPressVersion <= 6.5 ) {
			test.skip(
				'Skipping Full Composability tests: WordPress version is below 6.5, which does not support this feature.'
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

			await setOption(
				request,
				baseURL,
				'woocommerce_allow_tracking',
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

	test( 'Clicking on a pattern should insert it in the preview', async ( {
		pageObject,
		baseURL,
	} ) => {
		await prepareAssembler( pageObject, baseURL );
		const assembler = await pageObject.getAssembler();
		const editor = await pageObject.getEditor();

		await deleteAllPatterns( editor, assembler );

		const sidebarPattern = assembler
			.locator( '.block-editor-block-patterns-list__list-item' )
			.first();

		const sidebarPatternContent = await sidebarPattern
			.frameLocator( 'iframe' )
			.locator( '.is-root-container' )
			.textContent();

		await sidebarPattern.click();

		const insertedPatternContent = await editor
			.locator(
				'[data-is-parent-block="true"]:not([data-type="core/template-part"])'
			)
			.first()
			.textContent();

		await expect( insertedPatternContent ).toContain(
			sidebarPatternContent
		);
	} );

	test( 'Clicking the "Move up/down" buttons should change the pattern order in the preview', async ( {
		pageObject,
		baseURL,
	} ) => {
		await prepareAssembler( pageObject, baseURL );
		const assembler = await pageObject.getAssembler();
		const editor = await pageObject.getEditor();

		const sidebarPattern = assembler
			.locator( '.block-editor-block-patterns-list__list-item' )
			.nth( 2 );

		const sidebarPatternContent = await sidebarPattern
			.frameLocator( 'iframe' )
			.locator( '.is-root-container' )
			.textContent();

		await sidebarPattern.click();

		const insertedPattern = editor
			.locator(
				'[data-is-parent-block="true"]:not([data-type="core/template-part"])'
			)
			.nth( 1 );
		await insertedPattern.click();

		const moveUpButton = assembler.locator(
			'button[aria-label="Move up"]'
		);
		await moveUpButton.click();

		const firstPattern = editor
			.locator(
				'[data-is-parent-block="true"]:not([data-type="core/template-part"])'
			)
			.first();
		const firstPatternContent = await firstPattern.textContent();

		expect( firstPatternContent ).toContain( sidebarPatternContent );
	} );

	test( 'Clicking the "Shuffle" button on a patterns should replace it for another one', async ( {
		pageObject,
		baseURL,
	} ) => {
		await prepareAssembler( pageObject, baseURL );
		const assembler = await pageObject.getAssembler();
		const editor = await pageObject.getEditor();

		await deleteAllPatterns( editor, assembler );

		const sidebarPattern = assembler
			.locator( '.block-editor-block-patterns-list__list-item' )
			.first();
		await sidebarPattern.click();

		const insertedPattern = editor
			.locator(
				'[data-is-parent-block="true"]:not([data-type="core/template-part"])'
			)
			.first();

		const insertedPatternContent = await insertedPattern.textContent();

		await insertedPattern.click();
		const shuffleButton = assembler.locator(
			'button[aria-label="Shuffle"]'
		);
		await shuffleButton.click();

		const shuffledPattern = editor.locator(
			'[data-is-parent-block="true"]:not([data-type="core/template-part"])'
		);

		await expect( await shuffledPattern ).not.toHaveText(
			insertedPatternContent
		);
	} );

	test( 'Clicking the "Delete" button on a pattern should remove it from the preview', async ( {
		pageObject,
		baseURL,
	} ) => {
		await prepareAssembler( pageObject, baseURL );
		const assembler = await pageObject.getAssembler();
		const editor = await pageObject.getEditor();

		await deleteAllPatterns( editor, assembler );

		const emptyPatternsBlock = editor.getByText(
			'Add one or more of our homepage patterns to create a page that welcomes shoppers.'
		);
		await expect( emptyPatternsBlock ).toBeVisible();
	} );

	test( 'Clicking the "Add patterns" button on the No Blocks view should add a default pattern', async ( {
		pageObject,
		baseURL,
	} ) => {
		await prepareAssembler( pageObject, baseURL );
		const assembler = await pageObject.getAssembler();
		const editor = await pageObject.getEditor();

		await deleteAllPatterns( editor, assembler );
		const addPatternsButton = editor.locator(
			'.no-blocks-insert-pattern-button'
		);
		await addPatternsButton.click();
		const emptyPatternsBlock = editor.getByText(
			'Add one or more of our homepage patterns to create a page that welcomes shoppers.'
		);
		const defaultPattern = editor.locator(
			'[data-is-parent-block="true"]:not([data-type="core/template-part"])'
		);
		await expect( emptyPatternsBlock ).toBeHidden();
		await expect( defaultPattern ).toBeVisible();
	} );

	test( 'Clicking opt-in new patterns should be available', async ( {
		pageObject,
		baseURL,
	} ) => {
		await prepareAssembler( pageObject, baseURL );
		const assembler = await pageObject.getAssembler();

		await assembler.getByText( 'Usage tracking' ).click();
		await expect(
			assembler.getByText( 'Access more patterns' )
		).toBeVisible();

		await assembler.getByRole( 'button', { name: 'Opt in' } ).click();

		await assembler
			.getByText( 'Access more patterns' )
			.waitFor( { state: 'hidden' } );

		const sidebarPattern = assembler.locator(
			'.block-editor-block-patterns-list'
		);

		await sidebarPattern.waitFor( { state: 'visible' } );

		await expect(
			assembler.locator( '.block-editor-block-patterns-list__list-item' )
		).toHaveCount( 10 );
	} );
} );
