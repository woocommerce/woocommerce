/**
 * External dependencies
 */
import { createClient, WP_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect } from '../fixtures/fixtures';
import { admin } from '../test-data/data';
import playwrightConfig from '../playwright.config';

/**
 * Check that an email exists in the WP Mail Logging plugin Email Log page. WP Mail Logging plugin must be installed.
 *
 * @param {import('@playwright/test').Page } page                 The Playwright page.
 * @param {string}                           receiverEmailAddress The email address of the email receiver.
 * @param {RegExp}                           subject              The subject of the email, in regular expression format.
 * @return {Promise<*>} Returns the row element of the email in the Email Log page.
 */
export async function expectEmail( page, receiverEmailAddress, subject ) {
	await page.goto(
		`wp-admin/tools.php?page=wpml_plugin_log&search[place]=receiver&search[term]=${ encodeURIComponent(
			receiverEmailAddress
		) }&orderby=timestamp&order=desc`
	);

	const row = page
		.getByRole( 'row' )
		.filter( {
			has: page.getByRole( 'cell', {
				name: receiverEmailAddress,
				exact: true,
			} ),
		} )
		.filter( {
			has: page.getByRole( 'cell', {
				name: subject,
				exact: true,
			} ),
		} );

	await expect( row ).toBeVisible();

	return row;
}

/**
 * Check the content of an email in the WP Mail Logging plugin Email Log page. WP Mail Logging plugin must be installed.
 *
 * @param {import('@playwright/test').Page } page                 The Playwright page.
 * @param {string}                           receiverEmailAddress The email address of the email receiver.
 * @param {RegExp}                           emailSubject         The subject of the email, in regular expression format.
 * @param {RegExp}                           emailContent         A part of the email content, in regular expression format.
 */
export async function expectEmailContent(
	page,
	receiverEmailAddress,
	emailSubject,
	emailContent
) {
	const modalContent = page.locator(
		'#wp-mail-logging-modal-content-body-content'
	);

	await expect(
		modalContent.getByText( `Receiver ${ receiverEmailAddress }` )
	).toBeVisible();
	await expect( modalContent.getByText( emailSubject ) ).toBeVisible();

	const emailContentFrame = modalContent.locator( 'iframe' ).contentFrame();

	await expect( emailContentFrame.locator( 'body' ) ).toContainText(
		emailContent
	);
}

export async function getWooEmails( params ) {
	const apiClient = createClient( playwrightConfig.use.baseURL, {
		type: 'basic',
		username: admin.username,
		password: admin.password,
	} );
	const emails = await apiClient.get( `${ WP_API_PATH }/woo_email`, {
		...params,
	} );
	return emails;
}

/**
 * Access the email editor and using the WooCommerce settings page.
 * Note: Ensure the block email editor feature flag is already enabled.
 *
 * @param {import('@playwright/test').Page } page       The Playwright page.
 * @param {string}                           emailTitle The transactional email title.
 */
export async function accessTheEmailEditor( page, emailTitle = 'New order' ) {
	await page.goto( '/wp-admin/admin.php?page=wc-settings&tab=email' );
	const theRow = page.getByRole( 'row', {
		name: new RegExp( emailTitle ),
	} );
	await theRow
		.getByRole( 'button', { name: 'Actions', exact: true } )
		.click();
	await page.getByRole( 'menuitem', { name: 'Edit', exact: true } ).click();
	await expect( page.locator( '#woocommerce-email-editor' ) ).toBeVisible();
}

export async function ensureEmailEditorSettingsPanelIsOpened( page ) {
	const status = await page.evaluate( async () => {
		const elem = document.querySelector(
			'.woocommerce-email-editor__settings-panel'
		);
		return elem?.classList?.contains( 'is-opened' ) || false;
	} );

	if ( ! status ) {
		await page
			.locator( '.woocommerce-email-editor__settings-panel' )
			.click();
	}
}
