/**
 * External dependencies
 */
import { pressKeyWithModifier } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import { AdminEdit } from './pages/admin-edit';
import { waitForTimeout } from './flows/utils';

/**
 * Perform a "select all" and then fill a input.
 *
 * @param {string} selector
 * @param {string} value
 */
export const clearAndFillInput = async ( selector, value ) => {
	await page.waitForSelector( selector );
	await page.focus( selector );
	await pressKeyWithModifier( 'primary', 'a' );
	await page.type( selector, value );
};

/**
 * Click a tab (on post type edit screen).
 *
 * @param {string} tabName Tab label
 */
export const clickTab = async ( tabName ) => {
	await expect( page ).toClick( '.wc-tabs > li > a', { text: tabName } );
};

/**
 * Save changes on a WooCommerce settings page.
 */
export const settingsPageSaveChanges = async () => {
	await page.focus( 'button.woocommerce-save-button' );
	await Promise.all( [
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		page.click( 'button.woocommerce-save-button' ),
	] );
};

/**
 * Save changes on Permalink settings page.
 */
export const permalinkSettingsPageSaveChanges = async () => {
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
export const setCheckbox = async( selector ) => {
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
export const unsetCheckbox = async( selector ) => {
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
export const uiUnblocked = async () => {
	await page.waitForFunction( () => ! Boolean( document.querySelector( '.blockUI' ) ) );
};

/**
 * Wait for backbone blocking to end.
 */
export const backboneUnblocked = async () => {
	await page.waitForFunction( () => ! Boolean( document.querySelector( '.wc-backbone-modal' ) ) );
};

/**
 * Conditionally wait for a selector without throwing an error.
 *
 * @param selector
 * @param timeoutInSeconds
 * @returns {Promise<boolean>}
 */
export const waitForSelectorWithoutThrow = async ( selector, timeoutInSeconds = 5 ) => {
	let selected = await page.$( selector );
	for ( let s = 0; s < timeoutInSeconds; s++ ) {
		if ( selected ) {
			break;
		}
		await waitForTimeout( 1000 );
		selected = await page.$( selector );
	}
	return Boolean( selected );
};

/**
 * Publish, verify that item was published. Trash, verify that item was trashed.
 *
 * @param {string} button (Publish)
 * @param {string} publishNotice
 * @param {string} publishVerification
 * @param {string} trashVerification
 */
export const verifyPublishAndTrash = async ( button, publishNotice, publishVerification, trashVerification ) => {
	const adminEdit = new AdminEdit();
	await adminEdit.verifyPublish( button, publishNotice, publishVerification );

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
export const verifyCheckboxIsSet = async( selector ) => {
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
export const verifyCheckboxIsUnset = async( selector ) => {
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
export const verifyValueOfInputField = async( selector, value ) => {
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
export const clickFilter = async ( selector ) => {
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
export const moveAllItemsToTrash = async () => {
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
export const evalAndClick = async ( selector ) => {
	// We use this when `expect(page).toClick()` is unable to find the element
	// See: https://github.com/puppeteer/puppeteer/issues/1769#issuecomment-637645219
	page.$eval( selector, elem => elem.click() );
};

/**
 * Select a value from select2 search input field.
 *
 * @param {string} value Value of what to be selected
 * @param {string} selector Selector of the select2 search field
 */
export const selectOptionInSelect2 = async ( value, selector = 'input.select2-search__field' ) => {
	await page.waitForSelector(selector);
	await page.click(selector);
	await page.type(selector, value);
	await waitForTimeout( 2000 ); // to avoid flakyness, must wait before pressing Enter
	await page.keyboard.press('Enter');
};

/**
 * Search by any term for an order
 *
 * @param {string} value Value to be entered into the search field
 * @param {string} orderId Order ID
 * @param {string} customerName Customer's full name attached to order ID.
 */
export const searchForOrder = async (value, orderId, customerName) => {
	await clearAndFillInput('#post-search-input', value);
	await expect(page).toMatchElement('#post-search-input', value);
	await expect(page).toClick('#search-submit' );
	await page.waitForSelector('#the-list', { timeout: 10000 } );
	await expect(page).toMatchElement('.order_number > a.order-view', {text: `#${orderId} ${customerName}`});
};

/**
 * Apply a coupon code within cart or checkout.
 * Method will try to apply a coupon in the checkout, otherwise will try to apply in the cart.
 *
 * @param couponCode string
 * @returns {Promise<void>}
 */
export const applyCoupon = async ( couponCode ) => {
	try {
		await Promise.all([
			page.reload(),
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		]);
		await expect(page).toClick('a', {text: 'Click here to enter your code'});
		await uiUnblocked();
		await clearAndFillInput('#coupon_code', couponCode);
		await expect(page).toClick('button', {text: 'Apply coupon'});
		await uiUnblocked();
	} catch (error) {
		await clearAndFillInput('#coupon_code', couponCode);
		await expect(page).toClick('button', {text: 'Apply coupon'});
		await uiUnblocked();
	};
};

/**
 * Remove one coupon within cart or checkout.
 *
 * @param couponCode Coupon name.
 * @returns {Promise<void>}
 */
export const removeCoupon = async ( couponCode ) => {
	await Promise.all([
		page.reload(),
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
	]);
	await expect(page).toClick('[data-coupon="'+couponCode.toLowerCase()+'"]', {text: '[Remove]'});
	await uiUnblocked();
	await expect(page).toMatchElement('.woocommerce-message', {text: 'Coupon has been removed.'});
};

/**
 *
 * Select and perform an order action in the `Order actions` postbox.
 *
 * @param {string} action The action to take on the order.
 */
export const selectOrderAction = async ( action ) => {
	await page.select( 'select[name=wc_order_action]', action );
	await Promise.all( [
		page.click( '.wc-reload' ),
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
	] );
}
