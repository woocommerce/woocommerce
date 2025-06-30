/**
 * Internal dependencies
 */
import { test as baseTest, expect, tags } from '../../fixtures/fixtures';
import { CUSTOMER_STATE_PATH } from '../../playwright.config';
import { WP_API_PATH } from '../../utils/api-client';

const test = baseTest.extend( {
	storageState: CUSTOMER_STATE_PATH,
} );

test.beforeAll( async ( { restApi } ) => {
	// Jetpack Comments replaces the default WordPress comment form when activated, and will cause this test to fail.
	// Make sure it's disabled prior to running this test.
	await test.step( 'disable Jetpack comments if Jetpack is installed and active', async () => {
		try {
			const statusResponse = await restApi.get(
				`${ WP_API_PATH }/plugins/jetpack/jetpack`
			);
			const { status } = await statusResponse.data;

			// eslint-disable-next-line playwright/no-conditional-in-test
			if ( status === 'active' ) {
				await restApi.post( `jetpack/v4/settings`, {
					comments: false,
				} );

				const response = await restApi.get( `jetpack/v4/settings` );
				const { comments } = await response.data;
				console.log( 'Jetpack comments status:', comments );
			}
		} catch ( error ) {
			console.log(
				`Attempt to disable Jetpack comments failed: ${
					error.data?.message ?? error
				}`
			);
		}
	} );
} );

test(
	'logged-in customer can comment on a post',
	{
		tag: [ tags.WP_CORE ],
	},
	async ( { page } ) => {
		await page.goto( 'hello-world/' );
		await expect(
			page.getByRole( 'heading', { name: 'Hello world!', exact: true } )
		).toBeVisible();

		await expect( page.getByText( `Logged in as` ) ).toBeVisible();

		const comment = `This is a test comment ${ Date.now() }`;
		await page.getByRole( 'textbox', { name: 'comment' } ).fill( comment );

		await expect(
			page.getByRole( 'textbox', { name: 'comment' } )
		).toHaveValue( comment );

		await page.getByRole( 'button', { name: 'Post Comment' } ).click();
		await expect( page.getByText( comment ) ).toBeVisible();
	}
);
