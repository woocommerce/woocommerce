/**
 * External dependencies
 */
import { pressKeyWithModifier } from '@wordpress/e2e-test-utils';

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
		await checkbox.click();
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
		await checkbox.click();
	}
};

/**
 * Wait for UI blocking to end.
 */
const uiUnblocked = async () => {
	await page.waitForFunction( () => ! Boolean( document.querySelector( '.blockUI' ) ) );
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
	await page.focus( 'a.submitdelete' );
	await expect( page ).toClick( 'a.submitdelete' );
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

/**
 * Clicks on a filter on a list page, such as WooCommerce > Orders or Posts > All Posts.
 *
 * @param {string} selector Selector of the filter link to be clicked.
 */
const clickFilter = async ( selector ) => {
	await page.waitForSelector( selector );
	await page.focus( selector );
	await Promise.all( [
		page.click( `.subsubsub > ${selector} > a` ),
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
	] );
};

/**
 * Moves all items in a list view to the trash.
 *
 * If there's more than 20 items, it moves all 20 items on the current page.
 */
const moveAllItemsToTrash = async () => {
	await setCheckbox( '#cb-select-all-1' );
	await expect( page ).toSelect( '#bulk-action-selector-top', 'Move to Trash' );
	await Promise.all( [
		page.click( '#doaction' ),
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
	] );
};

/**
 * Use puppeteer page eval to click an element.
 *
 * Useful for clicking items that have been added to the DOM via ajax.
 *
 * @param {string} selector Selector of the filter link to be clicked.
 */
const evalAndClick = async ( selector ) => {
	// We use this when `expect(page).toClick()` is unable to find the element
	// See: https://github.com/puppeteer/puppeteer/issues/1769#issuecomment-637645219
	page.$eval( selector, elem => elem.click() );
};

/**
 * Select a value from select2 input field.
 *
 * @param {string} value Value of what to be selected
 * @param {string} selector Selector of the select2
 */
const selectOptionInSelect2 = async ( value, selector = 'input.select2-search__field' ) => {
	await page.waitForSelector(selector);
	await page.type(selector, value);
	await page.waitFor(2000); // to avoid flakyness, must wait before pressing Enter
	await page.keyboard.press('Enter');
};

export {
	clearAndFillInput,
	clickTab,
	settingsPageSaveChanges,
	permalinkSettingsPageSaveChanges,
	setCheckbox,
	unsetCheckbox,
	uiUnblocked,
	verifyPublishAndTrash,
	verifyCheckboxIsSet,
	verifyCheckboxIsUnset,
	verifyValueOfInputField,
	clickFilter,
	moveAllItemsToTrash,
	evalAndClick,
	selectOptionInSelect2,
};
