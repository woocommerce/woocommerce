/**
 * External dependencies
 */
import { createClient, WP_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, test, locks } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import {
	deleteEmailPost,
	disableEmailEditor,
	enableEmailEditor,
} from './helpers/enable-email-editor-feature';
import { accessTheEmailEditor } from '../../utils/email';
import { admin } from '../../test-data/data';

test.describe(
	'WooCommerce Email Editor Delete',
	{ lock: locks.EMAIL_FEATURE_FLAGS },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		let emailPostId: string | null = null;

		test.beforeAll( async ( { baseURL } ) => {
			await enableEmailEditor( baseURL );
		} );

		test.afterAll( async ( { baseURL } ) => {
			try {
				if ( emailPostId ) {
					// The test already deleted the post when it passed.
					await deleteEmailPost( baseURL, emailPostId ).catch(
						( error ) => {
							if ( error.response?.status !== 404 ) {
								throw error;
							}
						}
					);
				}
			} finally {
				await disableEmailEditor( baseURL );
			}
		} );

		test( 'Deleting an email from the editor opens the email listing', async ( {
			page,
			baseURL,
		} ) => {
			await accessTheEmailEditor( page, 'Customer note' );
			emailPostId = new URL( page.url() ).searchParams.get( 'post' );
			expect( emailPostId ).toMatch( /^[1-9]\d*$/ );

			// WP 7.0 renders the sidebar tabs with the button role.
			await page
				.getByRole( 'tab', { name: 'Email' } )
				.or(
					page.getByRole( 'button', { name: 'Email', exact: true } )
				)
				.click();
			await page
				.locator( '.editor-sidebar' )
				.getByRole( 'button', { name: 'Actions' } )
				.first()
				.click();
			// WooCommerce replaces core's "Trash…" with an action that deletes
			// the email permanently, and adds "Reset" for the email content.
			await expect(
				page.getByRole( 'menuitem', { name: 'Reset' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'menuitem', { name: /trash/i } )
			).toHaveCount( 0 );
			await page
				.getByRole( 'menuitem', { name: 'Permanently delete' } )
				.click();
			await page
				.getByRole( 'dialog' )
				.getByRole( 'button', { name: 'Delete permanently' } )
				.click();

			await expect( page ).toHaveURL( /page=wc-settings&tab=email/ );

			const apiClient = createClient( baseURL, {
				type: 'basic',
				username: admin.username,
				password: admin.password,
			} );
			const postState = await apiClient
				.get(
					`${ WP_API_PATH }/woo_email/${ emailPostId }?context=edit`
				)
				.then( ( response ) => response.data.status )
				.catch( ( error ) => error.response?.status );
			expect( postState ).toBe( 404 );
		} );
	}
);
