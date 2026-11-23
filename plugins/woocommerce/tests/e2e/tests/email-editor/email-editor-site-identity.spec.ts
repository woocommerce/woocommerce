/**
 * External dependencies
 */
import { createClient, WP_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, request, test, locks } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import {
	deleteEmailPost,
	disableEmailEditor,
	enableEmailEditor,
} from './helpers/enable-email-editor-feature';
import { accessTheEmailEditor } from '../../utils/email';
import { setOption } from '../../utils/options';
import { admin } from '../../test-data/data';

const SITE_TITLE = 'Site Identity E2E Store';

const getSiteTitle = async ( baseURL: string ): Promise< string > => {
	const apiClient = createClient( baseURL, {
		type: 'basic',
		username: admin.username,
		password: admin.password,
	} );
	const response = await apiClient.get( `${ WP_API_PATH }/settings` );
	return response.data.title;
};

test.describe(
	'WooCommerce Email Editor site identity blocks',
	{ lock: locks.EMAIL_FEATURE_FLAGS },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		let originalSiteTitle: string | undefined;
		let emailPostId: string | null = null;

		test.beforeAll( async ( { baseURL } ) => {
			originalSiteTitle = await getSiteTitle( baseURL );
			await setOption( request, baseURL, 'blogname', SITE_TITLE );
			await enableEmailEditor( baseURL );
		} );

		test.afterAll( async ( { baseURL } ) => {
			try {
				if ( emailPostId ) {
					await deleteEmailPost( baseURL, emailPostId );
				}
			} finally {
				try {
					if ( originalSiteTitle !== undefined ) {
						await setOption(
							request,
							baseURL,
							'blogname',
							originalSiteTitle
						);
					}
				} finally {
					await disableEmailEditor( baseURL );
				}
			}
		} );

		test( 'Saving an email with a Site Title block does not change the site title', async ( {
			page,
			baseURL,
		} ) => {
			await accessTheEmailEditor( page, 'New order' );
			emailPostId = new URL( page.url() ).searchParams.get( 'post' );
			const canvas = page
				.locator( 'iframe[name="editor-canvas"]' )
				.contentFrame();

			// Set the insertion point inside the email content.
			await canvas.getByLabel( 'Block: Heading' ).click();
			await page
				.getByRole( 'button', { name: 'Block Inserter' } )
				.click();
			await page
				.getByRole( 'searchbox', { name: 'Search' } )
				.fill( 'Site Title' );
			await page
				.getByRole( 'option', { name: 'Site Title', exact: true } )
				.click();

			// The template header can contain a Site Title too, but it is locked in the email mode.
			const siteTitle = canvas.locator(
				'[data-type="core/site-title"]:not([inert])'
			);
			await expect( siteTitle ).toHaveText( SITE_TITLE );
			await expect(
				siteTitle.locator( '[contenteditable="true"]' )
			).toHaveCount( 0 );

			await siteTitle.click();
			await page.keyboard.type( ' changed' );
			await expect( siteTitle ).toHaveText( SITE_TITLE );

			await page
				.getByRole( 'button', { name: 'Save', exact: true } )
				.click();
			// Edited records stay dirty until saved, the site settings included.
			await expect
				.poll( () =>
					page.evaluate(
						() =>
							window.wp.data
								.select( 'core' )
								.__experimentalGetDirtyEntityRecords().length
					)
				)
				.toBe( 0 );

			expect( await getSiteTitle( baseURL ) ).toBe( SITE_TITLE );
		} );
	}
);
