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
 * @param {string} tabName Tab label
 */
const clickTab = async ( tabName ) => {
	await expect( page ).toClick( '.wc-tabs > li > a', { text: tabName } );
};

/**
 * Delete product from wp-admin product page.
 */
const deleteProduct = async () => {
	await page.waitForSelector( 'a', { text: 'Move to Trash' } );
	// Trash product
	await expect( page ).toClick( 'a', { text: 'Move to Trash' } );
	await page.waitForSelector( '.updated.notice', { text: '1 product moved to the Trash.' } );
	// Verify
	await expect( page ).toMatchElement( '.updated.notice', { text: '1 product moved to the Trash.' } );
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
 * Save changes on Permalink settings page.
 */
const permalinkSettingsPageSaveChanges = async () => {
	await page.focus( '.wp-core-ui .button-primary' );
	await Promise.all( [
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		page.click( '.wp-core-ui .button-primary' ),
	] );
};

/**
 * Set checkbox.
 *
 * @param {string} selector
 */
const setCheckbox = async( selector ) => {
	await page.focus( selector );
	const checkbox = await page.$( selector );
	const checkboxStatus = ( await ( await checkbox.getProperty( 'checked' ) ).jsonValue() );
	if ( checkboxStatus !== true ) {
		await page.click( selector );
	}
};

/**
 * Unset checkbox.
 *
 * @param {string} selector
 */
const unsetCheckbox = async( selector ) => {
	await page.focus( selector );
	const checkbox = await page.$( selector );
	const checkboxStatus = ( await ( await checkbox.getProperty( 'checked' ) ).jsonValue() );
	if ( checkboxStatus === true ) {
		await page.click( selector );
	}
};

/**
 * Wait for UI blocking to end.
 */
const uiUnblocked = async () => {
	await page.waitForFunction( () => ! Boolean( document.querySelector( '.blockUI' ) ), { polling: 100 } );
};

/**
 * Publish, verify that item was published. Trash, verify that item was trashed.
 *
 * @param {string} button (Publish)
 * @param {string} publishNotice
 * @param {string} publishVerification
 * @param {string} trashVerification
 */
const verifyPublishAndTrash = async ( button, publishNotice, publishVerification, trashVerification ) => {
	// Wait for auto save
	await page.waitFor( 2000 );

	// Publish
	await expect( page ).toClick( button );
	await page.waitForSelector( publishNotice );

	// Verify
	await expect( page ).toMatchElement( publishNotice, { text: publishVerification } );
	if ( button === '.order_actions li .save_order' ) {
		await expect( page ).toMatchElement( '#select2-order_status-container', { text: 'Processing' } );
		await expect( page ).toMatchElement(
			'#woocommerce-order-notes .note_content',
			{
				text: 'Order status changed from Pending payment to Processing.',
			}
		);
	}

	// Trash
	await expect( page ).toClick( 'a', { text: "Move to Trash" } );
	await page.waitForSelector( '#message' );

	// Verify
	await expect( page ).toMatchElement( publishNotice, { text: trashVerification } );
};

/**
 * Verify that checkbox is set.
 *
 * @param {string} selector Selector of the checkbox that needs to be verified.
 */
const verifyCheckboxIsSet = async( selector ) => {
	await page.focus( selector );
	const checkbox = await page.$( selector );
	const checkboxStatus = ( await ( await checkbox.getProperty( 'checked' ) ).jsonValue() );
	await expect( checkboxStatus ).toBe( true );
};

/**
 * Verify that checkbox is unset.
 *
 * @param {string} selector Selector of the checkbox that needs to be verified.
 */
const verifyCheckboxIsUnset = async( selector ) => {
	await page.focus( selector );
	const checkbox = await page.$( selector );
	const checkboxStatus = ( await ( await checkbox.getProperty( 'checked' ) ).jsonValue() );
	await expect( checkboxStatus ).not.toBe( true );
};

/**
 * Verify the value of input field once it was saved (can be used for radio buttons verification as well).
 *
 * @param {string} selector Selector of the input field that needs to be verified.
 * @param {string} value Value of the input field that needs to be verified.
 */
const verifyValueOfInputField = async( selector, value ) => {
	await page.focus( selector );
	const field = await page.$( selector );
	const fieldValue = ( await ( await field.getProperty( 'value' ) ).jsonValue() );
	await expect( fieldValue ).toBe( value );
};

module.exports = {
	...flows,
	clearAndFillInput,
	clickTab,
	deleteProduct,
	settingsPageSaveChanges,
	permalinkSettingsPageSaveChanges,
	setCheckbox,
	unsetCheckbox,
	uiUnblocked,
	verifyPublishAndTrash,
	verifyCheckboxIsSet,
	verifyCheckboxIsUnset,
	verifyValueOfInputField,
};
