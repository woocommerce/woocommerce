const { test, expect, request } = require( '@playwright/test' );
const { admin } = require( '../../test-data/data' );
const { goToPageEditor } = require( '../../utils/editor' );

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
		await goToPageEditor( { page } );

		await page
			.getByRole( 'textbox', { name: 'Add Title' } )
			.fill( pageTitle );

		await page.getByRole( 'button', { name: 'Add default block' } ).click();

		await page
			.getByRole( 'document', {
				name: 'Empty block; start writing or type forward slash to choose a block',
			} )
			.fill( 'Test Page' );

		await page
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();

		await page
			.getByRole( 'region', { name: 'Editor publish' } )
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();

		await expect(
			page.getByText( `${ pageTitle } is now live.` )
		).toBeVisible();
	} );
} );
