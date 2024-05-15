const { test, expect, request } = require( '@playwright/test' );
const { admin } = require( '../../test-data/data' );
const {
	goToPostEditor,
	fillPageTitle,
	getCanvas,
} = require( '../../utils/editor' );

const postTitle = `Post-${ new Date().getTime().toString() }`;

test.describe( 'Can create a new post', () => {
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

		let response = await wpApi.get( `posts` );
		const allPosts = await response.json();

		await allPosts.forEach( async ( post ) => {
			if ( post.title.rendered === postTitle ) {
				response = await wpApi.delete( `posts/${ post.id }`, {
					data: {
						force: true,
					},
				} );
				//				expect( response.ok() ).toBeTruthy();
			}
		} );
	} );

	test( 'can create new post', async ( { page } ) => {
		await goToPostEditor( { page } );

		await fillPageTitle( page, postTitle );

		const canvas = await getCanvas( page );

		await canvas
			.getByRole( 'button', { name: 'Add default block' } )
			.click();

		await canvas
			.getByRole( 'document', {
				name: 'Empty block; start writing or type forward slash to choose a block',
			} )
			.fill( 'Test Post' );

		await page
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();

		await page
			.getByRole( 'region', { name: 'Editor publish' } )
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();

		await expect(
			page.getByText( `${ postTitle } is now live.` )
		).toBeVisible();
	} );
} );
