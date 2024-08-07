const { expect } = require( '@playwright/test' );

const SETTINGS_URL =
	'wp-admin/admin.php?page=wc-settings&tab=advanced&section=features';

/**
 * This function checks whether the block product editor is enabled on a page.
 *
 * Navigates to the block product editor and verifies it's enabled.
 *
 * @param {import('@playwright/test').Page} page
 *
 * @return {Promise<boolean>} Boolean value based on the visibility of the element.
 */
async function isBlockProductEditorEnabled( page ) {
	await page.goto( SETTINGS_URL );
	return await page
		.locator( '#woocommerce_feature_product_block_editor_enabled' )
		.isChecked();
}

/**
 * This function is typically used for enabling/disabling the block product editor in settings page.
 *
 * @param {string}                          action The action that will be performed.
 * @param {import('@playwright/test').Page} page
 */
async function toggleBlockProductEditor( action = 'enable', page ) {
	await page.goto( SETTINGS_URL );

	const enableProductEditor = page.locator(
		'#woocommerce_feature_product_block_editor_enabled'
	);
	const isEnabled = await enableProductEditor.isChecked();

	if (
		( action === 'enable' && isEnabled ) ||
		( action === 'disable' && ! isEnabled )
	) {
		// No need to toggle the setting.
		return;
	}

	if ( action === 'enable' ) {
		await enableProductEditor.check();
	} else if ( action === 'disable' ) {
		await enableProductEditor.uncheck();
	}

	await page
		.getByRole( 'button', {
			name: 'Save changes',
		} )
		.click();
}

/**
 * This function simulates the clicking of the "Add New" link under the "product" section in the menu.
 *
 * @param {import('@playwright/test').Page} page
 */
async function clickAddNewMenuItem( page ) {
	await page
		.locator( '#menu-posts-product' )
		.getByRole( 'link', { name: 'Add New' } )
		.click();
}

/**
 * This function checks if the old product editor is visible.
 *
 * @param {import('@playwright/test').Page} page
 */
async function expectOldProductEditor( page ) {
	await expect(
		page.getByRole( 'heading', { name: 'Product data' } )
	).toBeVisible();
}

/**
 * This function checks if the block product editor is visible.
 *
 * @param {import('@playwright/test').Page} page
 */
async function expectBlockProductEditor( page ) {
	await expect(
		page.locator( '.woocommerce-product-header__inner h1' )
	).toContainText( 'Add new product' );
}

/**
 * Click on a block editor tab.
 *
 * @param {string}                          tabName
 * @param {import('@playwright/test').Page} page
 */
async function clickOnTab( tabName, page ) {
	await page
		.locator( '.woocommerce-product-tabs' )
		.getByRole( 'tab', { name: tabName } )
		.click();
}

module.exports = {
	expectBlockProductEditor,
	expectOldProductEditor,
	clickAddNewMenuItem,
	clickOnTab,
	isBlockProductEditorEnabled,
	toggleBlockProductEditor,
};
