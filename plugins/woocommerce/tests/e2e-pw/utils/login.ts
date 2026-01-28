/**
 * External dependencies
 */
import type { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { expect } from '../fixtures/fixtures';

/**
 * Log in to WordPress admin.
 *
 * @param page          - Playwright Page object
 * @param username      - Username to log in with
 * @param password      - Password to log in with
 * @param assertSuccess - Whether to assert successful login (default: true)
 */
export const logIn = async (
	page: Page,
	username: string,
	password: string,
	assertSuccess = true
): Promise< void > => {
	await page
		.getByLabel( 'Username or Email Address' )
		.click( { delay: 100 } );
	await page.getByLabel( 'Username or Email Address' ).fill( username );
	await page
		.getByRole( 'textbox', { name: 'Password' } )
		.click( { delay: 100 } );
	await page.getByRole( 'textbox', { name: 'Password' } ).fill( password );
	await page.getByRole( 'button', { name: 'Log In' } ).click();

	if ( assertSuccess ) {
		await expect( page ).toHaveTitle( /Dashboard/ );
	}
};

/**
 * Log in from the My Account page.
 *
 * @param page          - Playwright Page object
 * @param username      - Username to log in with
 * @param password      - Password to log in with
 * @param assertSuccess - Whether to assert successful login (default: true)
 */
export const logInFromMyAccount = async (
	page: Page,
	username: string,
	password: string,
	assertSuccess = true
): Promise< void > => {
	await page.locator( '#username' ).fill( username );
	await page.locator( '#password' ).fill( password );
	const loginButton = page.locator( 'button[name="login"]' );
	await loginButton.click();

	await expect( loginButton ).toBeHidden();

	if ( assertSuccess ) {
		await expect(
			page
				.getByLabel( 'Account pages' )
				.getByRole( 'link', { name: 'Log out' } )
		).toBeVisible();
	}
};
