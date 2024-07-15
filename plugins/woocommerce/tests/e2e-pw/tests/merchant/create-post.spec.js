const { test: baseTest, expect } = require( '../../fixtures/fixtures' );
const { request } = require( '@playwright/test' );
const {
	goToPostEditor,
	fillPageTitle,
	getCanvas,
	publishPage,
} = require( '../../utils/editor' );
const { encodeCredentials } = require( '../../utils/plugin-utils' );

const test = baseTest.extend( {
	storageState: process.env.ADMINSTATE,
} );

test.describe(
	'Can create a new post',
	{ tag: [ '@gutenberg', '@services' ] },
	() => {
		test( 'can create new post', async ( { page, testPost, baseURL } ) => {
			// check if Gutenberg is installed
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
			const gutenbergPlugin = pluginsList.find(
				( { textdomain } ) => textdomain === 'gutenberg'
			);

			await goToPostEditor( { page } );

			// if Gutenberg is active, wait for element before filling page title
			if ( gutenbergPlugin ) {
				await expect(
					page.getByRole( 'button', { name: 'Set featured image' } )
				).toBeVisible();
			}

			await fillPageTitle( page, testPost.title );

			const canvas = await getCanvas( page );

			await canvas
				.getByRole( 'button', { name: 'Add default block' } )
				.click();

			await canvas
				.getByRole( 'document', {
					name: 'Empty block; start writing or type forward slash to choose a block',
				} )
				.fill( 'Test Post' );

			await publishPage( page, testPost.title );
		} );
	}
);
