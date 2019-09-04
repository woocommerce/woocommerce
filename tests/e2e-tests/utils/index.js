/**
 * Internal dependencies
 */
const flows = require( './flows' );

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
	await page.waitForFunction( () => ! Boolean( document.querySelector( '.blockUI' ) ) );
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
	clickTab,
	settingsPageSaveChanges,
	setCheckbox,
	unsetCheckbox,
	uiUnblocked,
	verifyCheckboxIsSet,
	verifyCheckboxIsUnset,
	verifyValueOfInputField,
};
