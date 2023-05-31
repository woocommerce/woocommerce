const { test, expect, request } = require( '@playwright/test' );
const { admin } = require( '../../test-data/data' );
const { getTranslationFor } = require( './../../test-data/data' );

const pageTitle = `Page-${ new Date().getTime().toString() }`;

test.describe( 'Can create a new page', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.afterAll( async ( { baseURL } ) => {
		const base64auth = Buffer.from(
			`${ admin.username }:${ admin.password }`
		).toString( 'base64' );
		const wpApi = await request.newContext( {
			baseURL: `${ baseURL }/wp-json/wp/v2/`,
			extraHTTPHeaders: {
				Authorization: `Basic ${ base64auth }`,
			},
		} );

		let response = await wpApi.get( `pages` );
		const allPages = await response.json();

		await allPages.forEach( async ( page ) => {
			if ( page.title.rendered === pageTitle ) {
				response = await wpApi.delete( `pages/${ page.id }`, {
					data: {
						force: true,
					},
				} );
			}
		} );
	} );

	test( 'can create new page', async ( { page } ) => {
		await page.goto( 'wp-admin/post-new.php?post_type=page' );

		const welcomeModalVisible = await page
			.getByRole( 'heading', {
				name: `${getTranslationFor('Welcome to the block editor')}`,
			} )
			.isVisible();

		if ( welcomeModalVisible ) {
			await page.getByRole( 'button', { name: `${getTranslationFor('Close')}` } ).click();
		}

		await page
			.getByRole( 'textbox', { name: `${getTranslationFor('Add Title')}` } )
			.fill( pageTitle );

		await page.getByRole( 'button', { name: `${getTranslationFor('Add default block')}` } ).click();

		await page
			.getByRole( 'document', {
				name:
				`${getTranslationFor('Empty block; start writing or type forward slash to choose a block')}`,
			} )
			.fill( 'Test Page' );

		await page
			.getByRole( 'button', { name: `${getTranslationFor('Publish')}`, exact: true } )
			.click();

		await page
			.getByRole( 'region', { name: `${getTranslationFor('Editor publish')}` } )
			.getByRole( 'button', { name: `${getTranslationFor('Publish')}`, exact: true } )
			.click();

		await expect(
			page.getByText( `${ pageTitle } ${getTranslationFor('is now live.')}` )
		).toBeVisible();
	} );
} );
