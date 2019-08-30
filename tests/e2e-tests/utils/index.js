/**
 * External dependencies
 */
import { pressKeyWithModifier } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
const flows = require( './flows' );

/**
 * Perform a "select all" and then fill a input.
 *
 * @param {string} selector
 * @param {string} value
 */
const clearAndFillInput = async ( selector, value ) => {
	await page.focus( selector );
	await pressKeyWithModifier( 'primary', 'a' );
	await page.type( selector, value );
};

/**
 * Click a tab (on post type edit screen).
 *
 * @param {string} tabName Tab label.
 */
const clickTab = async ( tabName ) => {
	await expect( page ).toClick( '.wc-tabs > li > a', { text: tabName } );
};

/**
 * Save changes on a WooCommerce settings page.
 */
const settingsPageSaveChanges = async () => {
	await page.focus( 'button.woocommerce-save-button' );
	await Promise.all( [
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		page.click( 'button.woocommerce-save-button' ),
	] );
};

/**
 * Wait for UI blocking to end.
 */
const uiUnblocked = async () => {
	await page.waitForFunction( () => ! Boolean( document.querySelector( '.blockUI' ) ) );
};

module.exports = {
	...flows,
	clearAndFillInput,
	clickTab,
	settingsPageSaveChanges,
	uiUnblocked,
};
