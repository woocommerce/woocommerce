/**
 * External dependencies
 */
import type { Page, Locator } from '@playwright/test';
import e2eUtils from '@woocommerce/e2e-utils-playwright';

const { createClient, WP_API_PATH } = e2eUtils;

/**
 * Internal dependencies
 */
import { expect } from '../fixtures/fixtures';
import type { RestApiClient } from '../fixtures/fixtures';
import { admin } from '../test-data/data';
import playwrightConfig from '../playwright.config';

/**
 * WooCommerce email response from API.
 */
interface WooEmail {
	id: number;
	title: string;
	type: string;
	description: string;
	settings: Record< string, unknown >;
}

/**
 * Parameters for fetching WooCommerce emails.
 */
interface GetWooEmailsParams {
	context?: 'view' | 'edit';
	per_page?: number;
	page?: number;
}

/**
 * Check that an email exists in the WP Mail Logging plugin Email Log page. WP Mail Logging plugin must be installed.
 *
 * @param page                 - The Playwright page.
 * @param receiverEmailAddress - The email address of the email receiver.
 * @param subject              - The subject of the email, in regular expression format.
 * @return Returns the row element of the email in the Email Log page.
 */
export async function expectEmail(
	page: Page,
	receiverEmailAddress: string,
	subject: RegExp
): Promise< Locator > {
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
 * @param page                 - The Playwright page.
 * @param receiverEmailAddress - The email address of the email receiver.
 * @param emailSubject         - The subject of the email, in regular expression format.
 * @param emailContent         - A part of the email content, in regular expression format.
 */
export async function expectEmailContent(
	page: Page,
	receiverEmailAddress: string,
	emailSubject: RegExp,
	emailContent: RegExp
): Promise< void > {
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

/**
 * Get WooCommerce emails from the API.
 *
 * @param params - Parameters for the API request.
 * @return Array of WooCommerce emails.
 */
export async function getWooEmails(
	params?: GetWooEmailsParams
): Promise< WooEmail[] > {
	const apiClient = createClient( playwrightConfig.use?.baseURL as string, {
		type: 'basic',
		username: admin.username,
		password: admin.password,
	} ) as RestApiClient;
	const response = await apiClient.get( `${ WP_API_PATH }/woo_email`, {
		...params,
	} );
	return response.data as WooEmail[];
}

/**
 * Access the email editor and using the WooCommerce settings page.
 * Note: Ensure the block email editor feature flag is already enabled.
 *
 * @param page       - The Playwright page.
 * @param emailTitle - The transactional email title.
 */
export async function accessTheEmailEditor(
	page: Page,
	emailTitle = 'New order'
): Promise< void > {
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

/**
 * Ensure the email editor settings panel is opened.
 *
 * @param page - The Playwright page.
 */
export async function ensureEmailEditorSettingsPanelIsOpened(
	page: Page
): Promise< void > {
	const status = await page.evaluate( (): boolean => {
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
