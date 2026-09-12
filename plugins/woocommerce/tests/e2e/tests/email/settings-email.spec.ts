/**
 * External dependencies
 */
import { test, expect, type Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { setFeatureEmailImprovementsFlag } from './helpers/set-email-improvements-feature-flag';
import { disableEmailEditor } from '../email-editor/helpers/enable-email-editor-feature';
import { tags } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

const pickImageFromLibrary = async ( page: Page, imageName: string ) => {
	await page.getByRole( 'tab', { name: 'Media Library' } ).click();
	await page.getByLabel( imageName ).first().click();
	await page.getByRole( 'button', { name: 'Select', exact: true } ).click();
};

test.describe( 'WooCommerce Email Settings', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	const storeName = 'WooCommerce Core E2E Test Suite';

	test.beforeEach( async ( { baseURL } ) => {
		await disableEmailEditor( baseURL );
	} );

	test.afterAll( async ( { baseURL } ) => {
		await setFeatureEmailImprovementsFlag( baseURL, 'no' );
		await disableEmailEditor( baseURL );
	} );

	test(
		'Live preview when changing email settings',
		{ tag: [ tags.SKIP_ON_EXTERNAL_ENV ] },
		async ( { page, baseURL } ) => {
			await setFeatureEmailImprovementsFlag( baseURL, 'no' );
			await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=email' );

			const iframeSelector = '#wc_settings_email_preview_slotfill iframe';
			const iframe = page.frameLocator( iframeSelector );
			const subject = page.locator(
				'.wc-settings-email-preview-header-subject'
			);

			const iframeContainsHtml = async ( code: string ) => {
				const content = await iframe.locator( 'html' ).innerHTML();
				return content.includes( code );
			};

			await expect(
				iframe.getByText( 'Thank you for your order' )
			).toBeVisible();
			await expect( subject ).toContainText(
				`Your ${ storeName } order has been received!`
			);

			await page
				.getByLabel( 'Email preview type' )
				.selectOption( 'Reset password' );
			await expect(
				iframe.getByText( 'Someone has requested a new password' )
			).toBeVisible();
			await expect( subject ).toContainText(
				`Password Reset Request for ${ storeName }`
			);

			const baseColorId = 'woocommerce_email_base_color';
			const baseColorValue = '#012345';

			await page.locator( `#${ baseColorId }` ).fill( baseColorValue );

			await page.evaluate(
				async ( args ) => {
					const input = document.getElementById( args.baseColorId );
					const iframeElement = document.querySelector(
						args.iframeSelector
					);
					if ( ! input || ! iframeElement ) {
						throw new Error(
							'The live-preview inputs must be mounted.'
						);
					}

					await Promise.all( [
						new Promise( ( resolve ) => {
							input.addEventListener(
								'transient-saved',
								() => resolve(),
								{ once: true }
							);
						} ),
						new Promise( ( resolve ) => {
							iframeElement.addEventListener(
								'load',
								() => resolve(),
								{
									once: true,
								}
							);
						} ),
						Promise.resolve().then( () => input.blur() ),
					] );
				},
				{ baseColorId, iframeSelector }
			);

			expect( await iframeContainsHtml( baseColorValue ) ).toBeTruthy();

			await page.reload();
			expect( await iframeContainsHtml( baseColorValue ) ).toBeFalsy();
		}
	);

	test( 'Choose image in email image url field', async ( { page } ) => {
		const logoImageElement = '.wc-settings-email-logo-image';
		const uploadIconElement = '.wc-settings-email-select-image-icon';

		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=email' );

		// Pick image
		await page.locator( '.wc-settings-email-select-image' ).click();
		await pickImageFromLibrary( page, 'image-03' );
		await expect( page.locator( logoImageElement ) ).toBeVisible();
		await expect( page.locator( uploadIconElement ) ).toBeHidden();

		// Remove an image
		await page
			.locator( '#wc_settings_email_image_url_slotfill' )
			.getByRole( 'button', { name: 'Remove', exact: true } )
			.click();
		await expect( page.locator( logoImageElement ) ).toBeHidden();
		await expect( page.locator( uploadIconElement ) ).toBeVisible();
	} );
} );
