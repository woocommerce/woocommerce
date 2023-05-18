const { test, expect, request } = require( '@playwright/test' );
const { admin } = require( '../../test-data/data' );
const { getTextForLanguage } = require( './../../test-data/data' );

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
		await page.goto( 'wp-admin/post-new.php' );

		const welcomeModalVisible = await test.step(
			'Check if the Welcome modal appeared',
			async () => {
				return await page
					.getByRole( 'heading', {
						name: `${getTextForLanguage()['Welcometotheblockeditor']}`,
					} )
					.isVisible();
			}
		);

		if ( welcomeModalVisible ) {
			await test.step( 'Welcome modal appeared. Close it.', async () => {
				await page
					.getByRole( 'document' )
					.getByRole( 'button', { name: `${getTextForLanguage()['Close']}` } )
					.click();
			} );
		} else {
			await test.step( 'Welcome modal did not appear.', async () => {
				// do nothing.
			} );
		}

		await page
			.getByRole( 'textbox', { name: `${getTextForLanguage()['AddTitle']}` } )
			.fill( postTitle );

		await page.getByRole( 'button', { name: `${getTextForLanguage()['Adddefaultblock']}` } ).click();

		await page
			.getByRole( 'document', {
				name:
				`${getTextForLanguage()['Emptyblockstartwritingortypeforwardslashtochooseablock']}`,
			} )
			.fill( 'Test Post' );

		await page
			.getByRole( 'button', { name: `${getTextForLanguage()['Publish']}`, exact: true } )
			.click();

		await page
			.getByRole( 'region', { name: `${getTextForLanguage()['Editorpublish']}` } )
			.getByRole( 'button', { name: `${getTextForLanguage()['Publish']}`, exact: true } )
			.click();

		await expect(
			page.getByText( `${ postTitle } ${getTextForLanguage()['isnowlive']}` )
		).toBeVisible();
	} );
} );
