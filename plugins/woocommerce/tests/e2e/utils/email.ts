/**
 * External dependencies
 */
import type { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { expect } from '../fixtures/fixtures';

/**
 * How long to keep re-navigating to the mail log while waiting for an email.
 *
 * Comfortably covers an Action Scheduler-backed dispatch, while keeping a
 * genuine miss from eating a quarter of the 120s per-test budget. Every caller
 * of `expectEmail()` pays this on the failure path, not just the ones that wait
 * on an async email.
 */
const MAIL_LOG_POLL_TIMEOUT = 30 * 1000;

/**
 * How long a single mail log lookup may take before the poll re-navigates.
 *
 * Set explicitly: without it the inner assertion inherits the project's
 * `expect` timeout (20s on CI), which would leave the budget above room for
 * about two attempts instead of a steady poll.
 */
const MAIL_LOG_ATTEMPT_TIMEOUT = 1000;

/**
 * Check that an email exists in the WP Mail Logging plugin Email Log page. WP Mail Logging plugin must be installed.
 *
 * Polls by re-navigating to the log on every attempt, not just re-querying the
 * already-loaded DOM, so an email that lands after the first navigation is
 * still found instead of timing out.
 *
 * @param {import('@playwright/test').Page } page                 The Playwright page.
 * @param {string}                           receiverEmailAddress The email address of the email receiver.
 * @param {RegExp}                           subject              The subject of the email, in regular expression format.
 * @param {number}                           [expectedCount]      Expected number of matching rows. Defaults to 1.
 * @return {Promise<*>} Returns the row locator for the matching email(s) in the Email Log page. Resolves to `expectedCount` rows, so callers that want a single row must narrow it themselves.
 */
export async function expectEmail(
	page: Page,
	receiverEmailAddress: string,
	subject: RegExp,
	expectedCount = 1
) {
	const mailLogUrl = `wp-admin/tools.php?page=wpml_plugin_log&search[place]=receiver&search[term]=${ encodeURIComponent(
		receiverEmailAddress
	) }&orderby=timestamp&order=desc`;

	// Locators are lazy, so building this once outside the poll still re-queries
	// the freshly loaded page on every attempt.
	const row = page
		.getByRole( 'row' )
		.filter( {
			has: page.getByRole( 'cell', {
				name: receiverEmailAddress,
				exact: true,
			} ),
		} )
		.filter( {
			// No `exact` here: Playwright ignores it when `name` is a RegExp,
			// so passing it would only imply a guarantee the matcher doesn't give.
			has: page.getByRole( 'cell', { name: subject } ),
		} );

	await expect( async () => {
		await page.goto( mailLogUrl );

		await expect( row ).toHaveCount( expectedCount, {
			timeout: MAIL_LOG_ATTEMPT_TIMEOUT,
		} );
	} ).toPass( { timeout: MAIL_LOG_POLL_TIMEOUT } );

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
	page: Page,
	receiverEmailAddress: string,
	emailSubject: RegExp,
	emailContent: RegExp
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

/**
 * Access the email editor and using the WooCommerce settings page.
 * Note: Ensure the block email editor feature flag is already enabled.
 *
 * @param {import('@playwright/test').Page } page       The Playwright page.
 * @param {string}                           emailTitle The transactional email title.
 */
export async function accessTheEmailEditor(
	page: Page,
	emailTitle = 'New order'
) {
	await page.goto( '/wp-admin/admin.php?page=wc-settings&tab=email' );
	const theRow = page.getByRole( 'row', {
		name: emailTitle,
	} );
	await theRow
		.getByRole( 'button', { name: 'Actions', exact: true } )
		.waitFor( { timeout: 20000 } );
	await theRow
		.getByRole( 'button', { name: 'Actions', exact: true } )
		.click();
	await page.getByRole( 'menuitem', { name: 'Edit', exact: true } ).click();
	await expect( page.locator( '#woocommerce-email-editor' ) ).toBeVisible( {
		timeout: 20000,
	} );
}

export async function ensureEmailEditorSettingsPanelIsOpened( page: Page ) {
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
